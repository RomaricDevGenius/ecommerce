<?php

namespace App\Services;

use App\Models\BroadcastOffer;
use App\Models\DeliveryBoy;
use App\Models\DeliveryHistory;
use App\Models\Order;
use App\Models\OrderBroadcast;
use App\Models\User;
use App\Utility\NotificationUtility;
use App\Utility\SmsUtility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\GpsShippingService;

class DeliveryBroadcastService
{
    // Ouagadougou center — fallback when no seller coordinates available
    private const DEFAULT_LAT = 12.3714;
    private const DEFAULT_LNG = -1.5197;

    private const DEFAULT_RADIUS_KM  = 5;
    private const DEFAULT_TIMEOUT_S  = 90;

    /**
     * Main entry point: broadcast a new order to nearby available delivery boys.
     * Returns null if mode is manual or no eligible boys found.
     */
    public static function broadcast(Order $order): ?OrderBroadcast
    {
        if ((get_setting('delivery_assignment_mode') ?? 'manual') !== 'dynamic') {
            return null;
        }

        if ($order->assign_delivery_boy) {
            return null;
        }

        $radiusKm      = (float) (get_setting('delivery_broadcast_radius_km') ?? self::DEFAULT_RADIUS_KM);
        $timeoutSeconds = (int)  (get_setting('delivery_broadcast_timeout_seconds') ?? self::DEFAULT_TIMEOUT_S);

        $eligible = self::getEligibleDeliveryBoys($order, $radiusKm);

        if (empty($eligible)) {
            Log::info("[Broadcast] No eligible delivery boys for order #{$order->id} within {$radiusKm} km");
            return null;
        }

        $broadcast = OrderBroadcast::create([
            'order_id'   => $order->id,
            'radius_km'  => $radiusKm,
            'started_at' => now(),
            'expires_at' => now()->addSeconds($timeoutSeconds),
            'status'     => 'pending',
        ]);

        foreach ($eligible as $item) {
            BroadcastOffer::create([
                'broadcast_id'   => $broadcast->id,
                'delivery_boy_id' => $item['user']->id,
                'priority_score' => $item['score'],
                'distance_km'    => $item['distance'],
                'sent_at'        => now(),
                'response'       => 'pending',
            ]);
        }

        // Push FCM to all eligible delivery boys
        if (get_setting('google_firebase') == 1) {
            foreach ($eligible as $item) {
                $user = $item['user'];
                if ($user->device_token) {
                    try {
                        NotificationUtility::sendFirebaseNotification(
                            deviceToken: $user->device_token,
                            title:       translate('Nouvelle commande disponible !'),
                            body:        translate('Une commande est disponible près de vous. Tapez pour accepter.'),
                            type:        'delivery_offer',
                            typeId:      $broadcast->id,
                            userId:      $user->id,
                        );
                    } catch (\Throwable $e) {
                        Log::warning("[Broadcast] FCM failed for user #{$user->id}: " . $e->getMessage());
                    }
                }
            }
        }

        Log::info("[Broadcast] Order #{$order->id} broadcast to " . count($eligible) . " delivery boys. Broadcast #{$broadcast->id}");
        return $broadcast;
    }

    /**
     * Accept a broadcast offer.
     * Uses a DB transaction to prevent two delivery boys from accepting the same order simultaneously.
     */
    public static function acceptOffer(int $offerId, int $deliveryBoyUserId): array
    {
        $offer = BroadcastOffer::with(['broadcast.order.user'])->find($offerId);

        if (!$offer) {
            return ['success' => false, 'message' => 'Offre introuvable.'];
        }
        if ($offer->delivery_boy_id !== $deliveryBoyUserId) {
            return ['success' => false, 'message' => 'Non autorisé.'];
        }
        if ($offer->response !== 'pending') {
            return ['success' => false, 'message' => 'Cette offre n\'est plus disponible.'];
        }

        return DB::transaction(function () use ($offer, $deliveryBoyUserId) {
            $broadcast = $offer->broadcast()->lockForUpdate()->first();

            if (!$broadcast || $broadcast->status !== 'pending') {
                return ['success' => false, 'message' => 'Cette commande a déjà été assignée.'];
            }
            if ($broadcast->expires_at && $broadcast->expires_at->isPast()) {
                return ['success' => false, 'message' => 'L\'offre a expiré.'];
            }

            $deliveryBoy = DeliveryBoy::where('user_id', $deliveryBoyUserId)->lockForUpdate()->first();
            if (!$deliveryBoy || $deliveryBoy->availability_status !== 'available') {
                return ['success' => false, 'message' => 'Vous avez déjà une livraison en cours.'];
            }

            $order = $broadcast->order;
            if (!$order) {
                return ['success' => false, 'message' => 'Commande introuvable.'];
            }

            // Assign order
            $order->assign_delivery_boy = $deliveryBoyUserId;
            $order->delivery_history_date = now()->toDateTimeString();
            $order->save();

            // Close broadcast
            $broadcast->status      = 'assigned';
            $broadcast->assigned_to = $deliveryBoyUserId;
            $broadcast->assigned_at = now();
            $broadcast->save();

            // Accept this offer
            $offer->response      = 'accepted';
            $offer->responded_at  = now();
            $offer->save();

            // Expire all other pending offers for this broadcast
            BroadcastOffer::where('broadcast_id', $broadcast->id)
                ->where('id', '!=', $offer->id)
                ->where('response', 'pending')
                ->update(['response' => 'timeout', 'responded_at' => now()]);

            // Create delivery history record (mirrors admin assignment logic)
            $existing = DeliveryHistory::where('order_id', $order->id)
                ->where('delivery_status', $order->delivery_status)
                ->first();
            if (!$existing) {
                $history = new DeliveryHistory;
                $history->order_id         = $order->id;
                $history->delivery_status  = $order->delivery_status;
                $history->payment_type     = $order->payment_type;
                $history->delivery_boy_id  = $deliveryBoyUserId;
                $history->save();
            } else {
                $existing->delivery_boy_id = $deliveryBoyUserId;
                $existing->save();
            }

            // Mark delivery boy as busy
            DeliveryBoy::where('user_id', $deliveryBoyUserId)
                ->update(['availability_status' => 'busy']);

            // SMS to delivery boy
            $deliveryBoyUser = User::find($deliveryBoyUserId);
            if ($deliveryBoyUser && $deliveryBoyUser->phone) {
                try {
                    SmsUtility::assign_delivery_boy($deliveryBoyUser->phone, $order->code);
                } catch (\Throwable) {}
            }

            // FCM push to customer
            $customer = $order->user;
            if (get_setting('google_firebase') == 1 && $customer && $customer->device_token) {
                try {
                    NotificationUtility::sendFirebaseNotification(
                        deviceToken: $customer->device_token,
                        title:       translate('Livreur assigné !'),
                        body:        translate('Un livreur a été assigné à votre commande') . ' ' . $order->code . '.',
                        type:        'order',
                        typeId:      $order->id,
                        userId:      $customer->id,
                    );
                } catch (\Throwable) {}
            }

            return [
                'success'  => true,
                'message'  => 'Commande acceptée avec succès.',
                'order_id' => $order->id,
                'order_code' => $order->code,
            ];
        });
    }

    /**
     * Decline a broadcast offer.
     */
    public static function declineOffer(int $offerId, int $deliveryBoyUserId): array
    {
        $offer = BroadcastOffer::find($offerId);

        if (!$offer) {
            return ['success' => false, 'message' => 'Offre introuvable.'];
        }
        if ($offer->delivery_boy_id !== $deliveryBoyUserId) {
            return ['success' => false, 'message' => 'Non autorisé.'];
        }
        if ($offer->response !== 'pending') {
            return ['success' => false, 'message' => 'Cette offre n\'est plus disponible.'];
        }

        $offer->response     = 'declined';
        $offer->responded_at = now();
        $offer->save();

        return ['success' => true, 'message' => 'Offre refusée.'];
    }

    /**
     * Find all available delivery boys within the given radius of the order.
     * Uses the customer's delivery coordinates stored in the order's shipping_address.
     * Falls back to Ouagadougou center if coordinates are missing.
     *
     * Returns array of ['user' => User, 'distance' => float, 'score' => float], sorted by score desc.
     */
    public static function getEligibleDeliveryBoys(Order $order, float $radiusKm): array
    {
        $origin = self::getOrderOrigin($order);

        $available = User::where('user_type', 'delivery_boy')
            ->where('banned', 0)
            ->whereHas('deliveryBoy', fn($q) => $q->where('availability_status', 'available'))
            ->with('deliveryBoy')
            ->get();

        $eligible = [];
        foreach ($available as $user) {
            $db = $user->deliveryBoy;
            if (!$db) {
                continue;
            }

            if ($db->lat && $db->lng) {
                $distance = GpsShippingService::distanceKm(
                    $origin['lat'], $origin['lng'],
                    (float) $db->lat, (float) $db->lng
                );
                if ($distance > $radiusKm) {
                    continue;
                }
            } else {
                // No GPS yet: include at max distance (lowest priority)
                $distance = $radiusKm;
            }

            $eligible[] = [
                'user'     => $user,
                'distance' => $distance,
                'score'    => self::calculateScore($user, $distance, $radiusKm),
            ];
        }

        usort($eligible, fn($a, $b) => $b['score'] <=> $a['score']);

        return $eligible;
    }

    /**
     * Extract delivery coordinates from the order's shipping_address JSON.
     * lat_lang format: "12.3714,-1.5197"
     */
    private static function getOrderOrigin(Order $order): array
    {
        try {
            $address = json_decode($order->shipping_address, true);
            if (isset($address['lat_lang'])) {
                [$lat, $lng] = explode(',', $address['lat_lang']);
                return ['lat' => (float) trim($lat), 'lng' => (float) trim($lng)];
            }
        } catch (\Throwable) {}

        return ['lat' => self::DEFAULT_LAT, 'lng' => self::DEFAULT_LNG];
    }

    /**
     * Priority score (0–1).
     * Distance 40% | Acceptance rate 30% | Today's load 20% | Default rating 10%
     */
    private static function calculateScore(User $user, float $distanceKm, float $radiusKm): float
    {
        $distanceScore = max(0.0, 1.0 - ($distanceKm / $radiusKm));

        $totalOffers = BroadcastOffer::where('delivery_boy_id', $user->id)
            ->whereIn('response', ['accepted', 'declined', 'timeout'])
            ->count();
        $accepted = BroadcastOffer::where('delivery_boy_id', $user->id)
            ->where('response', 'accepted')
            ->count();
        $acceptanceRate = $totalOffers > 0 ? ($accepted / $totalOffers) : 0.5;

        $todayCount = Order::where('assign_delivery_boy', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();
        $loadScore = max(0.0, 1.0 - ($todayCount / 10.0));

        return round(
            ($distanceScore * 0.4) + ($acceptanceRate * 0.3) + ($loadScore * 0.2) + (0.5 * 0.1),
            4
        );
    }

}
