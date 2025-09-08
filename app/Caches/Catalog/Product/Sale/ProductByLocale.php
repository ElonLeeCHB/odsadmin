<?php

namespace App\Caches\Catalog\Product\Sale;

use App\Models\Catalog\Product;
use App\Caches\FileCustomCacheManager;
use App\Traits\CacheByLocaleTrait;

class ProductByLocale
{
    use CacheByLocaleTrait;

    protected static array $baseKeyParts = [
        'entity',
        'product',
        'sale',
        'ProductByLocale'
    ];

    public static function getById(int $product_id, string $locale, int $ttl = 3600)
    {
        $key = self::key($product_id, $locale);

        return FileCustomCacheManager::remember($key, 3600, function () use ($product_id) {
            $query = Product::query();
            $query->select(['id', 'code', 'price', 'quantity_of_flavor', 'quantity_for_control', 'is_option_qty_controlled']);
            $query->where('id', $product_id)
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

            return $query->first();
        });
    }
}
