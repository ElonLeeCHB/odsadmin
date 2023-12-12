<?php
 
namespace App\Http\Resources\Sale;
 
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Models\Sale\OrderProduct;

class OrderProductCollection extends JsonResource
{
    // public function toArray(Request $request = null): array
    // {
    //     return parent::toArray($request);
    // }


    
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->code,
            // 其他属性...
        ];
    }


    public function toCleanObject()
    {
        return $this;
    }
}