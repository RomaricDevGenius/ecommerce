<?php

namespace App\Http\Resources\V2;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DeliveryHistoryCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $items = $this->collection;

        // Si la ressource provient d'une pagination Laravel, $items peut être un paginator.
        // On convertit systématiquement vers une liste afin d'éviter "data: null"
        // côté mobile (qui appelle ensuite json["data"].map(...)).
        if (is_object($items) && method_exists($items, 'items')) {
            $items = $items->items();
        }

        $items = collect($items)->values();

        return [
            'data' => $items->map(function ($data) {
                return [
                    'id' => $data->id,
                    'delivery_boy_id' => $data->delivery_boy_id,
                    'order_id' => $data->order_id,
                    'order_code' => $data->order->code,
                    'delivery_status' => $data->delivery_status,
                    'earning' => format_price($data->earning) ,
                    'collection' => format_price($data->collection),
                    'payment_type' => $data->payment_type,
                    'date' => Carbon::parse($data->created_at)->format('d-m-Y'),
                ];
            })->all()
        ];
    }

    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }
}
