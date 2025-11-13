<?php

namespace App\Caches\Catalog\Product\Www;

use App\Models\Catalog\Product;
use App\Caches\FileCustomCacheManager;
use App\Traits\CacheByLocaleTrait;
use App\Helpers\Classes\OrmHelper;
use App\Helpers\Classes\RowsArrayHelper;

class ProductByLocaleWithOptionsIndexedByOptionCode
{
    use CacheByLocaleTrait;

    protected static array $baseKeyParts = [
        'entity',
        'product',
        'www',
        'ProductWithOptionsIndexedByOptionCode'
    ];

    public static function getById(int $product_id, string $locale = '', int $ttl = 60*60*24*7)
    {
        if (empty($locale)) {
            $locale = app()->getLocale();
        }

        $key = self::key($product_id, $locale);

        return FileCustomCacheManager::remember($key, $ttl, function () use ($product_id) {
            $query = Product::query();

            if (!empty($product_id)) {
                $query->where('id', $product_id);
            }

            $query->with([
                'translation',
                'product_options' => function ($query) {
                    $query->with([
                        'translation',
                        'product_option_values' => function ($query) {
                            $query->with([
                                'translation',
                                'option_value'
                            ]);
                        }
                    ]);
                }
            ]);

            OrmHelper::prepare($query, $filter_data);

            $filter_data['first'] = true;

            $product = OrmHelper::getResult($query, $filter_data);

            if (empty($product)) {
                abort(404, 'Product not found');
            }

            $product->web_name = $product->translation->web_name;

            // 重新組合選項，並合併到產品。這樣處理是因為如果直接改 $product->product_options，可能後續又會被加載成原始資料
            $product = [
                ...$product->toArray(),
                'product_options' => $product->product_options
                    ->sortBy('sort_order')
                    ->keyBy('option_code')
                    ->toArray(),
            ];

            RowsArrayHelper::removeTranslation($product);

            return $product;
        });
    }
}
