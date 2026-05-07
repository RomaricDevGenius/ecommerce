<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GpsQuoteRequest extends Model
{
    protected $fillable = [
        'user_id', 'delivery_lat', 'delivery_lng', 'distance_km',
        'status', 'supplement_amount', 'expires_at',
    ];

    protected $casts = [
        'delivery_lat'      => 'float',
        'delivery_lng'      => 'float',
        'distance_km'       => 'float',
        'supplement_amount' => 'float',
        'expires_at'        => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isConfirmed(): bool { return $this->status === 'confirmed'; }
    public function isActive(): bool    { return in_array($this->status, ['pending', 'confirmed']); }
}
