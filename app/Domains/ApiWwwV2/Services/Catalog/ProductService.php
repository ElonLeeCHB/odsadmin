<?php

namespace App\Domains\ApiWwwV2\Services\Catalog;

use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Traits\Model\EloquentTrait;

class ProductService extends Service
{
    use EloquentTrait;

    public $modelName = "\App\Models\Catalog\Product";

    public function getInfo($product_id)
    {
        $cache_key = 'cache/locale/'. app()->getLocale().'/product_' . $product_id;

        return DataHelper::remember($cache_key, 60*60, 'json', function() use ($product_id){
            $product = $this->getRow([
                'equal_id' => $product_id,
                'with' => ['product_options.product_option_values'],
            ]);

            // 重構選項並合併到產品數據
            $product = [
                ...$product->toArray(),
                'product_options' => $product->product_options
                    ->sortBy('sort_order')
                    ->keyBy('option_code')
                    ->toArray(),
            ];

            return DataHelper::removeIndexRecursive('translation', $product);
        });
    }

    /**
     * simplelist
     * basictable
     */

     public function getSimplelist($filters)
     {
        try {

            $filters['with'] = [];

            $filters['select'] = ['id', 'code', 'model', 'name'];

            return $products = $this->getRows($filters);

        } catch (\Exception $ex) {
            return ['error' => $ex->getMessage()];
        }
     }


    public function getList($filters)
    {
        return $this->getRows($filters);
    }
}
