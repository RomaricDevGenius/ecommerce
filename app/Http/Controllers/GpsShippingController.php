<?php

namespace App\Http\Controllers;

use App\Models\GpsShippingTier;
use App\Models\Order;
use App\Services\GpsShippingService;
use Illuminate\Http\Request;

class GpsShippingController extends Controller
{
    // ── Page de configuration ──────────────────────────────────────────────

    public function config()
    {
        $tiers = GpsShippingTier::ordered()->get();

        return view('backend.setup_configurations.gps_shipping.config', compact('tiers'));
    }

    public function updateConfig(Request $request)
    {
        $keys = [
            'gps_weight_mode',           // 'weight_only' | 'weight_volume'
            'gps_weight_threshold_kg',
            'gps_weight_surcharge_fcfa',
            'gps_volume_threshold_l',
            'gps_volume_surcharge_fcfa',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                \App\Models\BusinessSetting::updateOrCreate(
                    ['type' => $key],
                    ['value' => $request->input($key)]
                );
            }
        }

        flash(translate('Configuration GPS mise à jour.'))->success();
        return back();
    }

    // ── CRUD Paliers ───────────────────────────────────────────────────────

    public function storeTier(Request $request)
    {
        $request->validate([
            'min_km'           => 'required|numeric|min:0',
            'max_km'           => 'nullable|numeric|gt:min_km',
            'price'            => 'required|numeric|min:0',
            'is_manual_review' => 'boolean',
        ]);

        $count = GpsShippingTier::count();

        GpsShippingTier::create([
            'min_km'           => $request->min_km,
            'max_km'           => $request->max_km ?: null,
            'price'            => $request->price,
            'is_manual_review' => $request->boolean('is_manual_review'),
            'sort_order'       => $count + 1,
        ]);

        flash(translate('Palier ajouté.'))->success();
        return back();
    }

    public function updateTier(Request $request, GpsShippingTier $tier)
    {
        $request->validate([
            'min_km' => 'required|numeric|min:0',
            'max_km' => 'nullable|numeric',
            'price'  => 'required|numeric|min:0',
        ]);

        $tier->update([
            'min_km'           => $request->min_km,
            'max_km'           => $request->max_km ?: null,
            'price'            => $request->price,
            'is_manual_review' => $request->boolean('is_manual_review'),
        ]);

        flash(translate('Palier mis à jour.'))->success();
        return back();
    }

    public function destroyTier(GpsShippingTier $tier)
    {
        $tier->delete();
        flash(translate('Palier supprimé.'))->success();
        return back();
    }

    // ── Commandes GPS en attente de supplément ─────────────────────────────

    public function pendingOrders()
    {
        $orders = Order::where('gps_shipping_pending', true)
            ->with(['user', 'orderDetails.product'])
            ->latest()
            ->paginate(20);

        return view('backend.setup_configurations.gps_shipping.pending_orders', compact('orders'));
    }

    public function setSupplement(Request $request, Order $order)
    {
        $request->validate([
            'supplement' => 'required|numeric|min:0',
        ]);

        $order->update([
            'gps_supplement'      => $request->supplement,
            'shipping_cost'       => $request->supplement,
            'gps_shipping_pending'=> false,
        ]);

        // Notifier le client
        try {
            $user = $order->user;
            if ($user && $user->email) {
                \Mail::to($user->email)->send(
                    new \App\Mail\GpsSupplementNotification($order)
                );
            }
        } catch (\Throwable $e) {
            // Ne pas bloquer si l'email échoue
        }

        flash(translate('Supplément défini. Le client a été notifié.'))->success();
        return back();
    }
}
