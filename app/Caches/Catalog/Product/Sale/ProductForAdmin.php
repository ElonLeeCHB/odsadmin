<?php

namespace App\Caches\Catalog\Product\Sale;

use App\Models\Catalog\Product;
use App\Caches\FileCustomCacheManager;
use App\Traits\CacheByLocaleTrait;

class ProductForAdmin
{
    use CacheByLocaleTrait;

    protected static array $baseKeyParts = [
        'entity',
        'product',
        'sale',
        'ProductForAdmin'
    ];

    public static function getById(int $product_id, string $locale, int $ttl = 60*60*24*7*365)
    {
        $key = self::key($product_id, $locale);

        return FileCustomCacheManager::remember($key, $ttl, function () use ($product_id) {
            $query = Product::query();

            $query->with([
                'productOptions.translation',
                'productOptions.productOptionValues.translation',
                'productOptions.option.translation',
                'productOptions.option.optionValues.translation',
                'translations',
                'translation',
                'metas',
            ]);

            $product = $query->where('id', $product_id)->first();

            if (empty($product->id)) {
                abort(404, 'Product not found');
            }

            foreach ($product->metas ?? [] as $meta) {
                $product->setAttribute($meta->meta_key, $meta->meta_value);
            }

            return $product;
        });
    }
}
