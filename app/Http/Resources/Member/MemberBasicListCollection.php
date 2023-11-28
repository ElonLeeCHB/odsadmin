<?php

namespace App\Http\Resources\Member;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Resources\Member\MemberResource;

/**
 * Basic columns for autocomplete
 */
class MemberBasicListCollection extends ResourceCollection
{
    public function toArray($request = null)
    {

        if ($this->resource instanceof LengthAwarePaginator) {

            $LengthAwarePaginator = $this->resource->toArray();

            $result = [
                'data' => $this->collection->map(function ($member) {
                    return [
                        'label' => $member->id . ' ' . $member->name . ' ' . $member->mobile,
                        'value' => $member->id,
                        'id' => $member->id,
                        'code' => $member->code,
                        'name' => $member->name,
                        'email' => $member->email,
                        'mobile' => $member->mobile,
                    ];
                }),
                'links' => $LengthAwarePaginator['links'],
                'total' => $LengthAwarePaginator['total'],
                'per_page' => $LengthAwarePaginator['per_page'],
                'current_page' => $LengthAwarePaginator['current_page'],
                'last_page' => $LengthAwarePaginator['last_page'],
                'from' => $LengthAwarePaginator['from'],
                'to' => $LengthAwarePaginator['to'],
            ];

            return $result;
        }

        else{
            return $this->collection->map(function ($member) {
                return [
                    'label' => $member->id . ' ' . $member->name . ' ' . $member->mobile,
                    'value' => $member->id,
                    'id' => $member->id,
                    'code' => $member->code,
                    'name' => $member->name,
                    'email' => $member->email,
                    'mobile' => $member->mobile,
                ];
            });
        }

    }

    public function makeRowArray($row)
    {
        $arr = $row->toArray();

        foreach ($arr as $key => $value) {
            if(!is_array($value)){
                $arr[$key] = $value;
            }
        }
        return $arr;
    }
}

