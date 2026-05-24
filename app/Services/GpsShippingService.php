<?php

namespace App\Services;

use App\Models\GpsShippingTier;

class GpsShippingService
{
    /**
     * Calcule la distance routière en km via l'API OSRM (OpenStreetMap).
     * Précision ~97% vs Google Maps. Gratuit, sans clé API.
     * Retourne null en cas d'échec (timeout, réseau indisponible).
     */
    private static function distanceKmOsrm(float $lat1, float $lng1, float $lat2, float $lng2): ?float
    {
        $url = "https://router.project-osrm.org/route/v1/driving/{$lng1},{$lat1};{$lng2},{$lat2}?overview=false";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT      => 'DakwariDelivery/1.0',
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $response = curl_exec($ch);
        $curlError = curl_errno($ch);
        curl_close($ch);

        if ($curlError || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        if (!isset($data['code']) || $data['code'] !== 'Ok' || empty($data['routes'])) {
            return null;
        }

        return (float) $data['routes'][0]['distance'] / 1000.0;
    }

    /**
     * Fallback Haversine (ligne droite) — utilisé uniquement si OSRM est indisponible.
     */
    private static function distanceKmHaversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * asin(sqrt($a));
    }

    /**
     * Calcule la distance en km entre deux coordonnées GPS.
     * Utilise OSRM (route réelle, ~97% précis vs Google Maps).
     * Bascule automatiquement sur Haversine si OSRM est indisponible.
     */
    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return self::distanceKmOsrm($lat1, $lng1, $lat2, $lng2)
            ?? self::distanceKmHaversine($lat1, $lng1, $lat2, $lng2);
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
        float  $customerLat,
        float  $customerLng,
        float  $totalWeightKg = 0.0,
        bool   $useVolume     = false,
        float  $totalVolumeL  = 0.0,
        ?float $departureLat  = null,
        ?float $departureLng  = null,
    ): array {
        $pickupLat = $departureLat ?? (float) get_setting('delivery_pickup_latitude',  '12.3714');
        $pickupLng = $departureLng ?? (float) get_setting('delivery_pickup_longitude', '-1.5197');

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
            'departure_lat'    => $pickupLat,
            'departure_lng'    => $pickupLng,
            'base_price'       => $basePrice,
            'weight_surcharge' => $weightExtra,
            'volume_surcharge' => $volumeExtra,
            'total'            => $total,
            'is_manual_review' => $isManual,
            'tier'             => $tier,
        ];
    }
}
