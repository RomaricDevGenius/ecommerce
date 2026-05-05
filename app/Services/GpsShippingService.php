<?php

namespace App\Services;

use App\Models\GpsShippingTier;

class GpsShippingService
{
    /**
     * Calcule la distance en km entre deux coordonnées GPS (formule Haversine).
     */
    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * asin(sqrt($a));
    }

    /**
     * Calcule les frais de livraison GPS pour une commande donnée.
     *
     * @param  float  $customerLat
     * @param  float  $customerLng
     * @param  float  $totalWeightKg   Poids total du colis en kg
     * @param  bool   $useVolume       Activer le mode volume (L)
     * @param  float  $totalVolumeL    Volume total en litres (si useVolume)
     * @return array{
     *   distance_km: float,
     *   base_price: float,
     *   weight_surcharge: float,
     *   volume_surcharge: float,
     *   total: float,
     *   is_manual_review: bool,
     *   tier: GpsShippingTier|null
     * }
     */
    public static function calculate(
        float $customerLat,
        float $customerLng,
        float $totalWeightKg = 0.0,
        bool  $useVolume     = false,
        float $totalVolumeL  = 0.0
    ): array {
        $pickupLat = (float) get_setting('delivery_pickup_latitude',  '12.3714');
        $pickupLng = (float) get_setting('delivery_pickup_longitude', '-1.5197');

        $distanceKm = self::distanceKm($pickupLat, $pickupLng, $customerLat, $customerLng);

        // Trouver le palier correspondant
        $tier = GpsShippingTier::ordered()
            ->where('min_km', '<=', $distanceKm)
            ->where(function ($q) use ($distanceKm) {
                $q->whereNull('max_km')->orWhere('max_km', '>', $distanceKm);
            })
            ->first();

        if (!$tier) {
            // Fallback : palier le plus grand (révision manuelle)
            $tier = GpsShippingTier::ordered()->orderByDesc('min_km')->first();
        }

        $basePrice = $tier ? $tier->price : 0.0;
        $isManual  = $tier ? $tier->is_manual_review : false;

        // ── Surcharge poids ────────────────────────────────────────────────
        $weightThreshold  = (float) get_setting('gps_weight_threshold_kg',  '5');
        $weightSurcharge  = (float) get_setting('gps_weight_surcharge_fcfa', '200');
        $weightExtra = 0.0;

        if ($totalWeightKg > $weightThreshold) {
            $weightExtra = ceil($totalWeightKg - $weightThreshold) * $weightSurcharge;
        }

        // ── Surcharge volume (optionnel) ───────────────────────────────────
        $volumeExtra = 0.0;
        if ($useVolume && $totalVolumeL > 0) {
            $volumeThreshold = (float) get_setting('gps_volume_threshold_l',    '20');
            $volumeRate      = (float) get_setting('gps_volume_surcharge_fcfa', '100');
            if ($totalVolumeL > $volumeThreshold) {
                $volumeExtra = ceil($totalVolumeL - $volumeThreshold) * $volumeRate;
            }
        }

        $total = $isManual ? 0.0 : ($basePrice + $weightExtra + $volumeExtra);

        return [
            'distance_km'      => round($distanceKm, 2),
            'base_price'       => $basePrice,
            'weight_surcharge' => $weightExtra,
            'volume_surcharge' => $volumeExtra,
            'total'            => $total,
            'is_manual_review' => $isManual,
            'tier'             => $tier,
        ];
    }
}
