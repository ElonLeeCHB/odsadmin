<?php
 
namespace App\Http\Resources\Inventory;
 
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray($params = null): array
    {
        $arr = $this->resource->toArray();

        $keep_array = $params['keep_array'] ?? [];

        // 如果沒有 keep_array，並且值是陣列，一律 unset()
        foreach ($arr as $key => $value) {
            if(is_array($value) && !in_array($key, $keep_array)){
                unset($arr[$key]);
            }
        }

        return $arr;
    }
}