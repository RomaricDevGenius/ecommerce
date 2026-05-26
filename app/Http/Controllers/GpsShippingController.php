<?php

namespace App\Http\Controllers;

use App\Models\GpsQuoteRequest;
use App\Models\GpsShippingTier;
use App\Utility\NotificationUtility;
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
            'gps_weight_mode',
            'gps_weight_threshold_kg',
            'gps_weight_surcharge_fcfa',
            'gps_volume_threshold_l',
            'gps_volume_surcharge_fcfa',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                \DB::table('business_settings')->updateOrInsert(
                    ['type' => $key],
                    ['value' => $request->input($key)]
                );
            }
        }

        \Cache::forget('business_settings');

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

    // ── Devis GPS en attente ───────────────────────────────────────────────

    public function pendingOrders()
    {
        $quotes = GpsQuoteRequest::with(['user', 'shop'])
            ->orderByRaw("FIELD(status, 'pending', 'confirmed', 'accepted', 'refused', 'expired')")
            ->latest()
            ->paginate(20);

        return view('backend.setup_configurations.gps_shipping.pending_orders', compact('quotes'));
    }

    public function destroyQuote(GpsQuoteRequest $quote)
    {
        $quote->delete();
        flash(translate('Devis supprimé.'))->success();
        return back();
    }

    public function setQuoteSupplement(Request $request, GpsQuoteRequest $quote)
    {
        $request->validate([
            'supplement' => 'required|numeric|min:0',
        ]);

        $quote->update([
            'supplement_amount' => $request->supplement,
            'status'            => 'confirmed',
        ]);

        $user        = $quote->user;
        $totalAmount = number_format($quote->total_amount, 0, ',', ' ');
        $siteName    = get_setting('site_name', 'Boutique');

        // Push Firebase
        try {
            if ($user && $user->device_token && get_setting('google_firebase') == 1) {
                NotificationUtility::sendFirebaseNotification(
                    deviceToken : $user->device_token,
                    title       : translate('Devis de livraison confirmé'),
                    body        : translate('Vos frais de livraison ont été estimés à ') . $totalAmount . ' FCFA. Ouvrez l\'app pour confirmer.',
                    type        : 'gps_quote',
                    typeId      : $quote->id,
                    userId      : $user->id,
                );
            }
        } catch (\Throwable $e) {}

        // Email au client
        try {
            if ($user && $user->email) {
                \Mail::raw(
                    "Bonjour {$user->name},\n\nVotre devis de livraison a été confirmé.\n\nMontant total : {$totalAmount} FCFA\nDistance : " . number_format($quote->distance_km, 1) . " km\n\nOuvrez l'application pour accepter ou refuser ce devis.\n\nCordialement,\n{$siteName}",
                    function ($msg) use ($user, $totalAmount, $siteName) {
                        $msg->to($user->email)
                            ->subject("[{$siteName}] Votre devis de livraison : {$totalAmount} FCFA");
                    }
                );
            }
        } catch (\Throwable $e) {}

        flash(translate('Supplément défini. Le client a été notifié.'))->success();
        return back();
    }
}
