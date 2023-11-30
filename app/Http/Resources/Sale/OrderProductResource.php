<?php
 
namespace App\Http\Resources\Sale;
 
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Models\Sale\OrderProduct;

class OrderProductResource extends JsonResource
{    
    public function toArray($request)
    {
        return $this->toCleanObject();
    }


    public function toCleanObject()
    {
        return $this;
    }
}