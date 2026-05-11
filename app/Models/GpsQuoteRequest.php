<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GpsQuoteRequest extends Model
{
    protected $fillable = [
        'user_id', 'seller_id', 'departure_lat', 'departure_lng',
        'delivery_lat', 'delivery_lng', 'distance_km',
        'base_amount', 'status', 'supplement_amount', 'expires_at',
    ];

    protected $casts = [
        'departure_lat'     => 'float',
        'departure_lng'     => 'float',
        'delivery_lat'      => 'float',
        'delivery_lng'      => 'float',
        'distance_km'       => 'float',
        'base_amount'       => 'float',
        'supplement_amount' => 'float',
        'expires_at'        => 'datetime',
    ];

    // Montant total = base calculée + supplément admin
    public function getTotalAmountAttribute(): float
    {
        return ($this->base_amount ?? 0.0) + ($this->supplement_amount ?? 0.0);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // seller_id → shops.user_id (null for inhouse/admin orders)
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'seller_id', 'user_id');
    }

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isConfirmed(): bool { return $this->status === 'confirmed'; }
    public function isActive(): bool    { return in_array($this->status, ['pending', 'confirmed']); }
}
