<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class DeliveryBoy extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'user_id',
        'availability_status',
        'lat',
        'lng',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'lat'          => 'float',
        'lng'          => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
