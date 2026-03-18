<?php

namespace App\Http\Resources\V2;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

class DeliveryHistoryCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $items = $this->collection;

        // Quand Laravel envoie une pagination, $this->collection peut ne pas être une Collection.
        // On normalise toujours pour que Flutter reçoive un tableau (même vide), jamais null.
        if ($items instanceof AbstractPaginator) {
            $items = $items->items();
        }

        $items = $items instanceof Collection ? $items : collect($items ?? []);

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
            })->values()->all(),
        ];
    }

    public function with($request)
    {
        $meta = [
            'current_page' => null,
            'from' => null,
            'last_page' => null,
            'path' => null,
            'per_page' => null,
            'to' => null,
            'total' => 0,
        ];

        if ($this->resource instanceof AbstractPaginator) {
            $meta = [
                'current_page' => $this->resource->currentPage(),
                'from' => $this->resource->firstItem(),
                'last_page' => $this->resource->lastPage(),
                'path' => method_exists($this->resource, 'path') ? $this->resource->path() : null,
                'per_page' => $this->resource->perPage(),
                'to' => $this->resource->lastItem(),
                'total' => $this->resource->total(),
            ];
        }

        return [
            'success' => true,
            'status' => 200,
            'meta' => $meta,
        ];
    }
}
