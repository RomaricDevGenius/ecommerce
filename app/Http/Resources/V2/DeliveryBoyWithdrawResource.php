<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryBoyWithdrawResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'amount'               => (float) $this->amount,
            'amount_formatted'     => format_price($this->amount),
            'payment_method'       => $this->payment_method,
            'payment_method_label' => $this->payment_method_label,
            'account_number'       => $this->account_number,
            'notes'                => $this->notes,
            'status'               => $this->status,
            'status_label'         => $this->status_label,
            'rejection_reason'          => $this->rejection_reason,
            'admin_note'                => $this->admin_note,
            'payment_method_image_url'  => static_asset('assets/img/cards/' . match ($this->payment_method) {
                'orange_money' => 'orange.png',
                'moov_money'   => 'moov.png',
                'coris_money'  => 'coris.png',
                default        => 'cod.png',
            }),
            'created_at'           => $this->created_at->format('d/m/Y H:i'),
            'created_at_timestamp' => $this->created_at->timestamp,
        ];
    }
}
