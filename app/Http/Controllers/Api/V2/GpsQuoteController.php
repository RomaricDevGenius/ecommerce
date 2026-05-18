<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Cart;
use App\Models\GpsQuoteRequest;
use App\Models\GpsShippingTier;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;

class GpsQuoteController extends Controller
{
    // POST /api/v2/gps-quote/submit
    public function submit(Request $request)
    {
        $request->validate([
            'delivery_lat' => 'required|numeric',
            'delivery_lng' => 'required|numeric',
            'distance_km'  => 'required|numeric|min:0',
        ]);

        $user = auth()->user();

        // Expire any active quote for this user before creating a new one
        GpsQuoteRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->update(['status' => 'expired']);

        // Determine departure point: vendor shop if all active cart items share one seller with GPS coords
        $carts        = Cart::where('user_id', $user->id)->active()->get();
        $ownerIds     = $carts->pluck('owner_id')->unique()->filter()->values();
        $sellerId     = null;
        $departureLat = null;
        $departureLng = null;

        if ($ownerIds->count() === 1) {
            $shop = Shop::where('user_id', $ownerIds->first())->first();
            if ($shop && $shop->delivery_pickup_latitude) {
                $sellerId     = (int) $ownerIds->first();
                $departureLat = (float) $shop->delivery_pickup_latitude;
                $departureLng = (float) $shop->delivery_pickup_longitude;
            }
        }

        // Calculer le poids et volume réels du panier
        $totalWeightKg = 0.0;
        $totalVolumeL  = 0.0;
        $useVolume     = get_setting('gps_weight_mode', 'weight_only') === 'weight_volume';

        foreach ($carts as $cartItem) {
            $product = Product::find($cartItem->product_id);
            if ($product) {
                $totalWeightKg += (float) $product->weight * (int) $cartItem->quantity;
                if ($useVolume && $product->length && $product->breadth && $product->height) {
                    $totalVolumeL += ($product->length * $product->breadth * $product->height / 1000) * (int) $cartItem->quantity;
                }
            }
        }

        // Calculate base amount using the correct departure point
        $gpsResult = \App\Services\GpsShippingService::calculate(
            (float) $request->delivery_lat,
            (float) $request->delivery_lng,
            $totalWeightKg,
            $useVolume,
            $totalVolumeL,
            $departureLat,
            $departureLng
        );

        // Pour les zones en révision manuelle : utiliser le dernier palier automatique comme base de référence
        if ($gpsResult['is_manual_review']) {
            $lastAutoTier = GpsShippingTier::where('is_manual_review', false)
                ->orderByDesc('min_km')
                ->first();
            $baseAmount = ($lastAutoTier ? (float) $lastAutoTier->price : 0.0)
                        + $gpsResult['weight_surcharge']
                        + $gpsResult['volume_surcharge'];
        } else {
            $baseAmount = $gpsResult['base_price'] + $gpsResult['weight_surcharge'] + $gpsResult['volume_surcharge'];
        }

        // Use server-recalculated distance (authoritative)
        $distanceKm = $gpsResult['distance_km'];

        $quote = GpsQuoteRequest::create([
            'user_id'       => $user->id,
            'seller_id'     => $sellerId,
            'departure_lat' => $gpsResult['departure_lat'],
            'departure_lng' => $gpsResult['departure_lng'],
            'delivery_lat'  => $request->delivery_lat,
            'delivery_lng'  => $request->delivery_lng,
            'distance_km'   => $distanceKm,
            'base_amount'   => $baseAmount,
            'status'        => 'pending',
            'expires_at'    => now()->addHours(24),
        ]);

        // Notifier l'admin par email
        try {
            $admin = get_admin();
            if ($admin && $admin->email) {
                $siteName = get_setting('site_name', 'Boutique');
                $distance = number_format($quote->distance_km, 1);
                \Mail::raw(
                    "Bonjour,\n\nUn nouveau devis GPS est en attente.\n\nClient  : {$user->name} ({$user->email})\nDistance : {$distance} km\n\nConnectez-vous à l'administration pour fixer le tarif.",
                    function ($msg) use ($admin, $user, $siteName) {
                        $msg->to($admin->email)
                            ->subject("[{$siteName}] Nouveau devis GPS – {$user->name}");
                    }
                );
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'result'   => true,
            'quote_id' => $quote->id,
            'status'   => 'pending',
            'message'  => translate('Votre demande de devis a été soumise.'),
        ]);
    }

    // GET /api/v2/gps-quote/status
    public function status(Request $request)
    {
        $user = auth()->user();

        $quote = GpsQuoteRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->latest()
            ->first();

        if (!$quote) {
            return response()->json(['result' => true, 'status' => 'none']);
        }

        // Auto-expire if past deadline
        if ($quote->expires_at && $quote->expires_at->isPast()) {
            $quote->update(['status' => 'expired']);
            return response()->json(['result' => true, 'status' => 'expired']);
        }

        return response()->json([
            'result'      => true,
            'quote_id'    => $quote->id,
            'status'      => $quote->status,
            'amount'      => $quote->total_amount,
            'base_amount' => $quote->base_amount,
            'supplement'  => $quote->supplement_amount,
            'distance_km' => $quote->distance_km,
        ]);
    }

    // POST /api/v2/gps-quote/accept/{id}
    // Client accepts the confirmed quote: carts are updated with the supplement amount
    public function accept(Request $request, $id)
    {
        $user  = auth()->user();
        $quote = GpsQuoteRequest::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->firstOrFail();

        $carts = Cart::where('user_id', $user->id)->active()->get();
        $count = $carts->count();

        if ($count > 0) {
            $perItem = round($quote->total_amount / $count, 2);
            foreach ($carts as $cart) {
                $cart->shipping_cost = $perItem;
                $cart->save();
            }
        }

        $quote->update(['status' => 'accepted']);

        return response()->json([
            'result'   => true,
            'quote_id' => $quote->id,
            'amount'   => $quote->total_amount,
            'message'  => translate('Devis accepté.'),
        ]);
    }

    // POST /api/v2/gps-quote/refuse/{id}
    public function refuse(Request $request, $id)
    {
        $user  = auth()->user();
        $quote = GpsQuoteRequest::where('id', $id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->firstOrFail();

        $quote->update(['status' => 'refused']);

        return response()->json([
            'result'  => true,
            'message' => translate('Demande annulée.'),
        ]);
    }
}
