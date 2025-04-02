<?php

namespace App\Domains\ApiWwwV2\Services\Catalog;

use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\OrmHelper;
use App\Services\Service;
use App\Traits\Model\EloquentTrait;
use App\Models\Catalog\Product;
use App\Helpers\Classes\RowsArrayHelper;

class ProductService extends Service
{
    use EloquentTrait;

    public $modelName = "\App\Models\Catalog\Product";

    public function getInfo($params)
    {
        $product = $this->getRow($params);
    
        if (empty($product)) {
            throw new \Exception('Product not found', 404);  // 404 是 HTTP 狀態碼
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

    public function getProductById($product_id)
    {
        $product = (new Product)->getLocaleProductByIdForSale($product_id);

        return DataHelper::unsetArrayIndexRecursively($product->toArray(), ['translation']);
    }


    public function getList($filters)
    {
        return $this->getRows($filters);
    }
}
