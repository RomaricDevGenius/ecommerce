<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsShippingTier extends Model
{
    protected $fillable = [
        'min_km',
        'max_km',
        'price',
        'is_manual_review',
        'sort_order',
    ];

    protected $casts = [
        'min_km'           => 'float',
        'max_km'           => 'float',
        'price'            => 'float',
        'is_manual_review' => 'boolean',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('min_km');
    }

    /** Retourne le label d'affichage de la tranche. */
    public function label(): string
    {
        if ($this->is_manual_review) {
            return ">{$this->min_km} km — Révision manuelle";
        }
        return "{$this->min_km} – {$this->max_km} km";
    }
}
