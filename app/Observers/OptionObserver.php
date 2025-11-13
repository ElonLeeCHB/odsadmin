<?php

namespace App\Observers;

use App\Models\Catalog\Option;
use App\Models\Catalog\ProductOption;
use App\Caches\FileCustomCacheManager;

class OptionObserver
{
    // 在創建或更新後都會觸發此方法
    public function saved(Option $option)
    {
        $this->clearRelatedProductsCache($option->id);
    }

    public function deleted(Option $option)
    {
        $this->clearRelatedProductsCache($option->id);
    }

    /**
     * 刪除所有使用此選項的商品快取
     *
     * @param int $option_id
     * @return void
     */
    protected function clearRelatedProductsCache(int $option_id)
    {
        // 查詢所有使用此選項的商品
        $productIds = ProductOption::where('option_id', $option_id)
            ->pluck('product_id')
            ->unique()
            ->toArray();

        // 刪除每個商品的快取
        foreach ($productIds as $productId) {
            FileCustomCacheManager::clearByUniqueKey("id-{$productId}", ['entity', 'product']);
        }
    }
}

?>
