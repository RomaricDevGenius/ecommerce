<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\BroadcastOffer;
use App\Models\DeliveryBoy;
use App\Services\DeliveryBroadcastService;
use Illuminate\Http\Request;

class DeliveryAvailabilityController extends Controller
{
    private const ALLOWED_STATUSES = ['available', 'offline', 'pause'];

    /**
     * POST /v2/delivery-boy/availability
     * Set the delivery boy's availability status + optional GPS position.
     */
    public function setAvailability(Request $request)
    {
        $user = auth()->user();
        if ($user->user_type !== 'delivery_boy') {
            return response()->json(['result' => false, 'message' => 'Non autorisé.'], 403);
        }

        $status = $request->input('status');
        if (!in_array($status, self::ALLOWED_STATUSES)) {
            return response()->json(['result' => false, 'message' => 'Statut invalide.'], 422);
        }

        $data = ['availability_status' => $status, 'last_seen_at' => now()];

        if ($status === 'available' && $request->filled('lat') && $request->filled('lng')) {
            $data['lat'] = (float) $request->input('lat');
            $data['lng'] = (float) $request->input('lng');
        }

        DeliveryBoy::where('user_id', $user->id)->update($data);

        return response()->json([
            'result'  => true,
            'message' => 'Statut mis à jour.',
            'status'  => $status,
        ]);
    }

    /**
     * POST /v2/delivery-boy/location
     * Update GPS position while available (silent background update).
     */
    public function updateLocation(Request $request)
    {
        $user = auth()->user();
        if ($user->user_type !== 'delivery_boy') {
            return response()->json(['result' => false], 403);
        }

        if (!$request->filled('lat') || !$request->filled('lng')) {
            return response()->json(['result' => false, 'message' => 'lat et lng requis.'], 422);
        }

        DeliveryBoy::where('user_id', $user->id)->update([
            'lat'          => (float) $request->input('lat'),
            'lng'          => (float) $request->input('lng'),
            'last_seen_at' => now(),
        ]);

        return response()->json(['result' => true]);
    }

    /**
     * GET /v2/delivery-boy/availability-status
     * Get current availability status.
     */
    public function getAvailabilityStatus()
    {
        $user = auth()->user();
        $db   = DeliveryBoy::where('user_id', $user->id)->first();

        return response()->json([
            'result' => true,
            'status' => $db ? $db->availability_status : 'offline',
        ]);
    }

    /**
     * GET /v2/delivery-boy/pending-offers
     * List all active, non-expired offers for the authenticated delivery boy.
     */
    public function pendingOffers()
    {
        $user = auth()->user();

        $offers = BroadcastOffer::with([
                'broadcast.order.orderDetails',
            ])
            ->where('delivery_boy_id', $user->id)
            ->where('response', 'pending')
            ->whereHas('broadcast', function ($q) {
                $q->where('status', 'pending')
                  ->where('expires_at', '>', now());
            })
            ->orderByDesc('priority_score')
            ->get()
            ->map(function ($offer) {
                $broadcast = $offer->broadcast;
                $order     = $broadcast->order;
                $address   = json_decode($order->shipping_address ?? '{}', true);

                return [
                    'offer_id'          => $offer->id,
                    'broadcast_id'      => $offer->broadcast_id,
                    'priority_score'    => $offer->priority_score,
                    'distance_km'       => $offer->distance_km,
                    'expires_at'        => $broadcast->expires_at?->toIso8601String(),
                    'seconds_remaining' => max(0, (int) now()->diffInSeconds($broadcast->expires_at, false)),
                    'order' => [
                        'id'              => $order->id,
                        'code'            => $order->code,
                        'grand_total'     => $order->grand_total,
                        'item_count'      => $order->orderDetails->count(),
                        'delivery_name'   => $address['name']    ?? '',
                        'delivery_phone'  => $address['phone']   ?? '',
                        'delivery_city'   => $address['city']    ?? '',
                        'delivery_address'=> $address['address'] ?? '',
                    ],
                ];
            });

        return response()->json(['result' => true, 'offers' => $offers]);
    }

    /**
     * POST /v2/delivery-boy/offers/{id}/accept
     */
    public function acceptOffer(int $id)
    {
        $result = DeliveryBroadcastService::acceptOffer($id, auth()->user()->id);

        return response()->json([
            'result'     => $result['success'],
            'message'    => $result['message'],
            'order_id'   => $result['order_id']   ?? null,
            'order_code' => $result['order_code'] ?? null,
        ]);
    }

    /**
     * POST /v2/delivery-boy/offers/{id}/decline
     */
    public function declineOffer(int $id)
    {
        $result = DeliveryBroadcastService::declineOffer($id, auth()->user()->id);

        return response()->json([
            'result'  => $result['success'],
            'message' => $result['message'],
        ]);
    }
}
