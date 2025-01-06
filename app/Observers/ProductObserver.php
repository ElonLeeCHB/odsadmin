<?php

namespace App\Observers;

use App\Models\Material\Product;
use App\Helpers\Classes\DataHelper;
use Illuminate\Support\Facades\Storage;

class ProductObserver
{

    // 在創建或更新後都會觸發此方法
    public function saved(Product $product)
    {
        $this->deleteCache($product);
    }


    public function deleted(Product $product)
    {
        $this->deleteCache($product);
    }



    /**
     * 自訂方法
     */
    
    private function deleteCache(Product $product)
    {
        return $product->deleteCache($product->id);
    }
}

?>