<?php

namespace App\Http\Resources\Member;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Resources\Member\MemberResource;

class MemberCollection extends ResourceCollection
{
    public $collects = MemberResource::class;

    public function toArray($params = null)
    {
        $rows = $this->collection->map(function ($row) use ($params){
                    return MemberResource::make($row)->toArray($params);
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

