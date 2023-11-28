<?php
 
namespace App\Http\Resources\Member;
 
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray($params = null): array
    {
        $arr = $this->resource->toArray();

        $keep_array = $params['keep_array'] ?? [];

        foreach ($arr as $key => $value) {
            if(is_array($value) && !in_array($key, $keep_array)){
                unset($arr[$key]);
            }
        }

        return $arr;
    }
}