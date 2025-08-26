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

    public function getProduct($product_id, $filter_data)
    {
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

        // 重構選項並合併到產品數據
        $product = [
            ...$product->toArray(),
            'product_options' => $product->product_options
                ->sortBy('sort_order')
                ->keyBy('option_code')
                ->toArray(),
        ];

        // option_value 的 is_on_www 改為，如果 = 0， 而 product_option_value.is_on_www，則此項的意義是，仍然有此選項值，只是暫時缺貨不賣。
        // 所以不再 unset()
        // foreach ($product['product_options'] as $key1 => $product_option) {
        //     foreach ($product_option['product_option_values'] as $key2 => $product_option_value) {
        //         if(empty($product_option_value['option_value']['is_on_www'])){
        //             unset($product['product_options'][$key1]['product_option_values'][$key2]);
        //         }
        //     }
        // }

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
