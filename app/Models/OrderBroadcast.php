<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderBroadcast extends Model
{
    protected $fillable = [
        'order_id',
        'radius_km',
        'started_at',
        'expires_at',
        'status',
        'assigned_to',
        'assigned_at',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'expires_at'  => 'datetime',
        'assigned_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function offers()
    {
        return $this->hasMany(BroadcastOffer::class, 'broadcast_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
