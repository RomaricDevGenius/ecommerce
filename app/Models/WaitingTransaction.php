<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaitingTransaction extends Model
{
    protected $table = 'waiting_transactions';

    protected $fillable = [
        'combined_order_id',
        'phone',
        'transaction_id',
    ];
}
