<?php

namespace App\Domains\ApiWwwV2\Services\Catalog;

use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Repositories\Eloquent\Material\ProductRepository;
use App\Traits\Model\EloquentTrait;
use App\Helpers\Classes\RowsArrayHelper;

class ProductService extends Service
{
    use EloquentTrait;

    public $modelName = "\App\Models\Material\Product";

    public function getInfo($params)
    {
        $product = $this->getRow($params);

        if(empty($product)){
            return [];
        }

        $product->web_name = $product->translation->web_name;

        // 重構選項並合併到產品數據
        $product = [
            ...$product->toArray(),
            'product_options' => $product->product_options
                ->sortBy('sort_order')
                ->keyBy('option_code')
                ->toArray(),
        ];

        foreach ($product['product_options'] as $key1 => $product_option) {
            foreach ($product_option['product_option_values'] as $key2 => $product_option_value) {
                if(empty($product_option_value['option_value']['is_on_www'])){
                    unset($product['product_options'][$key1]['product_option_values'][$key2]);
                }
            }
        }

        RowsArrayHelper::removeTranslation($product);
        
        return $product;
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
