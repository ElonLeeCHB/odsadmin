<?php

namespace App\Caches\Catalog\Product\Sale;

use Illuminate\Support\Facades\Cache;
use App\Models\Catalog\Product;

class ProductByLocale
{
    /**
     * 產生快取 key
     */
    protected static function key(int $productId, string $locale): string
    {
        return "product:{$productId}:locale:{$locale}";
    }

    /**
     * 取得快取資料
     */
    public static function getById(int $product_id, string $locale, int $ttl = 3600)
    {
        return Cache::remember(self::key($product_id, $locale),$ttl, function () use ($product_id, $locale) {
                $builder = Product::query();
                $builder->select(['id', 'code', 'price', 'quantity_of_flavor', 'quantity_for_control', 'is_option_qty_controlled']);
                $builder->where('id', $product_id)
                    ->with(['productOptions' => function ($query) {
                        $query->where('is_active', 1)
                            ->with(['productOptionValues' => function ($query) {
                                $query->where('is_active', 1)
                                    ->with('optionValue')
                                    ->with('translation')
                                    ->with(['materialProduct' => function ($query) {
                                        $query->select('products.id as material_product_id', 'products.quantity_for_control', 'products.is_option_qty_controlled')
                                            ->from('products');  // 另外指定使用 products 表的 id 來避免歧義，跟一開始的主表 products 區隔
                                    }]);
                            }])
                            ->with('option');
                    }])
                    ->with('translation');

                $product = $builder->first();

                // 重新組合選項，並合併到產品。這樣處理是因為如果直接改 $product->product_options，可能後續又會被加載成原始資料
                $productArray = $product->toArray();

                // 將原本的 product_options 重新 keyBy，保留裡面的子關聯
                // $productArray['product_options'] = collect($productArray['product_options'])
                //     ->sortBy('sort_order')
                //     ->keyBy('option_code')
                //     ->toArray();

                return $productArray;
            }
        );
    }

    /**
     * 清除快取
     */
    public static function forget(int $productId, string $locale): void
    {
        Cache::forget(self::key($productId, $locale));
    }

    /**
     * 強制刷新
     */
    public static function refresh(int $productId, string $locale, int $ttl = 3600)
    {
        self::forget($productId, $locale);
        return self::get($productId, $locale, $ttl);
    }
}
