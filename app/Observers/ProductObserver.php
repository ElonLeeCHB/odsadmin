<?php

namespace App\Observers;

use App\Models\Catalog\Product;
use App\Caches\FileCustomCacheManager;

class ProductObserver
{
    // 在創建或更新後都會觸發此方法
    public function saved(Product $product)
    {
        $this->clearProductCache($product->id);
    }

    public function deleted(Product $product)
    {
        $this->clearProductCache($product->id);
    }

    protected function clearProductCache(int $product_id)
    {
        FileCustomCacheManager::clearByUniqueKey("id-{$product_id}", ['entity', 'product']);
    }
    
}

?>