<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class DeliveryBoyWithdrawRequest extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'delivery_boy_id',
        'amount',
        'payment_method',
        'account_number',
        'notes',
        'status',
        'rejection_reason',
        'admin_note',
        'viewed',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'viewed' => 'boolean',
    ];

    public function deliveryBoy()
    {
        return $this->belongsTo(User::class, 'delivery_boy_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'  => translate('Pending'),
            'approved' => translate('Approved'),
            'paid'     => translate('Paid'),
            'rejected' => translate('Rejected'),
            default    => ucfirst($this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending'  => 'warning',
            'approved' => 'info',
            'paid'     => 'success',
            'rejected' => 'danger',
            default    => 'secondary',
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'orange_money' => 'Orange Money',
            'moov_money'   => 'Moov Money',
            'coris_money'  => 'Coris Money',
            'cash'         => translate('Cash'),
            default        => ucfirst(str_replace('_', ' ', $this->payment_method ?? '')),
        };
    }
}
