<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastOffer extends Model
{
    protected $fillable = [
        'broadcast_id',
        'delivery_boy_id',
        'priority_score',
        'distance_km',
        'sent_at',
        'responded_at',
        'response',
    ];

    protected $casts = [
        'sent_at'        => 'datetime',
        'responded_at'   => 'datetime',
        'priority_score' => 'float',
        'distance_km'    => 'float',
    ];

    public function broadcast()
    {
        return $this->belongsTo(OrderBroadcast::class, 'broadcast_id');
    }

    public function deliveryBoy()
    {
        return $this->belongsTo(User::class, 'delivery_boy_id');
    }
}
