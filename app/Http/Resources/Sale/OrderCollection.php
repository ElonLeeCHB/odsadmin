<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Resources\Sale\OrderResource;

class OrderCollection extends ResourceCollection
{
    public $collects = OrderResource::class;

    public function toArray($params = null)
    {
        $rows = $this->collection->map(function ($row) use ($params){
                    return OrderResource::make($row)->toArray($params);
                });

        $result = [];

        if ($this->resource instanceof LengthAwarePaginator) {
            $LengthAwarePaginator = $this->resource->toArray();

            $result = [
                'data' => $rows,
                'links' => $LengthAwarePaginator['links'],
                'total' => $LengthAwarePaginator['total'],
                'per_page' => $LengthAwarePaginator['per_page'],
                'current_page' => $LengthAwarePaginator['current_page'],
                'last_page' => $LengthAwarePaginator['last_page'],
                'from' => $LengthAwarePaginator['from'],
                'to' => $LengthAwarePaginator['to'],
            ];
        }else{
            $result = $rows;
        }

        return $result;
    }
}

