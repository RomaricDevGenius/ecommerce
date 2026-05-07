<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Cart;
use App\Models\GpsQuoteRequest;
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

        $quote = GpsQuoteRequest::create([
            'user_id'      => $user->id,
            'delivery_lat' => $request->delivery_lat,
            'delivery_lng' => $request->delivery_lng,
            'distance_km'  => $request->distance_km,
            'status'       => 'pending',
            'expires_at'   => now()->addHours(24),
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
            'amount'      => $quote->supplement_amount,
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
            $perItem = round($quote->supplement_amount / $count, 2);
            foreach ($carts as $cart) {
                $cart->shipping_cost = $perItem;
                $cart->save();
            }
        }

        $quote->update(['status' => 'accepted']);

        return response()->json([
            'result'   => true,
            'quote_id' => $quote->id,
            'amount'   => $quote->supplement_amount,
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
