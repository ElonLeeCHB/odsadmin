<?php

namespace App\Observers;

use App\Models\Catalog\Product;
use App\Helpers\Classes\DataHelper;
use Illuminate\Support\Facades\Storage;

class ProductObserver
{

    public function creating(Product $product)
    {
        return $product->prepareArrayData($product);
    }

    public function updating(Product $product)
    {
        return $product->prepareArrayData($product);
    }

    // 在創建或更新後都會觸發此方法
    public function saved(Product $product)
    {
        return $this->deleteCache($product);
    }


    public function deleted(Product $product)
    {
        return $this->deleteCache($product);
    }



    /**
     * 自訂方法
     */
    
    private function deleteCache(Product $product)
    {
        return $product->deleteCacheByProductId($product->id);
    }
}

?>