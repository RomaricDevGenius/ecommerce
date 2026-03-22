<?php

namespace App\Http\Resources\V2;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

class DeliveryHistoryCollection extends ResourceCollection
{
    /**
     * Référence au paginateur d'origine : après parent::__construct(),
     * $this->resource devient une Collection et n'est plus un AbstractPaginator,
     * ce qui faisait toujours meta.total = 0 côté API (bug liste vide + résumé correct).
     */
    /** @var AbstractPaginator|null */
    protected $paginator = null;

    public function __construct($resource)
    {
        $this->paginator = $resource instanceof AbstractPaginator ? $resource : null;
        parent::__construct($resource);
    }

    public function toArray($request)
    {
        // $this->collection peut être une pagination => on force en liste d'items
        $items = $this->collection;
        if ($items instanceof AbstractPaginator) {
            $items = $items->items();
        }

        // Toujours renvoyer un tableau côté JSON (évite data: null)
        $items = $items ?? [];

        return [
            'data' => collect($items)->map(function ($data) {
                // earning / collection peuvent être NULL en base (commission vs COD) : éviter TypeError dans format_price()
                $earningRaw = $data->earning;
                $collectionRaw = $data->collection;

                return [
                    'id' => $data->id,
                    'delivery_boy_id' => $data->delivery_boy_id,
                    'order_id' => $data->order_id,
                    'order_code' => optional($data->order)->code ?? ('#' . (string) $data->order_id),
                    'delivery_status' => $data->delivery_status,
                    'earning' => format_price($earningRaw !== null ? (float) $earningRaw : 0.0),
                    'collection' => format_price($collectionRaw !== null ? (float) $collectionRaw : 0.0),
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

        $resource = $this->paginator ?? ($this->resource instanceof AbstractPaginator ? $this->resource : null);
        if ($resource instanceof AbstractPaginator) {
            $meta = [
                'current_page' => $resource->currentPage(),
                'from' => $resource->firstItem(),
                'last_page' => $resource->lastPage(),
                'path' => method_exists($resource, 'path') ? $resource->path() : null,
                'per_page' => $resource->perPage(),
                'to' => $resource->lastItem(),
                'total' => $resource->total(),
            ];
        }

        return [
            'success' => true,
            'status' => 200,
            'meta' => $meta,
        ];
    }
}
