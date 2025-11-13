<?php

namespace App\Observers;

use App\Models\Catalog\OptionValue;
use App\Models\Catalog\ProductOptionValue;
use App\Caches\FileCustomCacheManager;

class OptionValueObserver
{
    // 在創建或更新後都會觸發此方法
    public function saved(OptionValue $optionValue)
    {
        $this->clearRelatedProductsCache($optionValue->id);
    }

    public function deleted(OptionValue $optionValue)
    {
        $this->clearRelatedProductsCache($optionValue->id);
    }

    /**
     * 刪除所有使用此選項值的商品快取
     *
     * @param int $option_value_id
     * @return void
     */
    protected function clearRelatedProductsCache(int $option_value_id)
    {
        // 查詢所有使用此選項值的商品
        $productIds = ProductOptionValue::where('option_value_id', $option_value_id)
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
