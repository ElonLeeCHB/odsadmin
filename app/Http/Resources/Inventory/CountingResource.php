<?php
 
namespace App\Http\Resources\Inventory;
 
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Inventory\CountingProductResource;
use App\Http\Resources\Inventory\CountingProductCollection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CountingResource extends JsonResource
{
    public function toArray(Request $request = null): array
    {
        return parent::toArray($request);
    }

    /**
     * 寫在這裡而不是 CountingProductResource 或 CountingProductCollection
     * 1 若要判斷關聯是否已載入，要使用 relationLoaded()，而這個函數在 model 裡面。
     * 2 EloquentTrait 的 with() 今天發現無法作用 2023-11-02 
     */
    public function getCountingProductsObject()
    {
        $countingProductResource = CountingProductResource::collection($this->counting_products);

        $stdClassProducts = $countingProductResource->map(function ($productResource) {
            return $productResource->toStdClass();
        });
        
        return $stdClassProducts;
    }
}