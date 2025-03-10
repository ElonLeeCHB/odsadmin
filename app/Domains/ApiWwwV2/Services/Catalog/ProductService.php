<?php

namespace App\Domains\ApiWwwV2\Services\Catalog;

use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\OrmHelper;
use App\Services\Service;
use App\Repositories\Eloquent\Material\ProductRepository;
use App\Traits\Model\EloquentTrait;
use App\Helpers\Classes\RowsArrayHelper;
use App\Models\Material\ProductTag;
use App\Models\Material\Product;

class ProductService extends Service
{
    use EloquentTrait;

    public $modelName = "\App\Models\Material\Product";

    public function getProductById($product_id)
    {
        $locale = app()->getLocale();
        $cache_key = 'cache/'.$locale.'/catalog/product/' . 'id-' . $product_id . '.txt';

        if(request()->has('no-cache') && request()->query('no-cache') == 1){
            DataHelper::deleteDataFromStorage($cache_key);
        }

        $product = DataHelper::remember($cache_key, 60*60*24, 'serialize', function() use ($product_id) {
            $builder = Product::query();
            $builder->select(['id', 'code', 'name', 'price', 'quantity_for_control', 'is_options_controlled']);
            $builder->where('id', $product_id)
                ->with(['productOptions' => function($query) {
                    $query->where('is_active', 1)
                        ->with(['productOptionValues' => function($query) {
                            $query->where('is_active', 1)
                                ->with('optionValue')
                                ->with('translation')
                                ->with(['materialProduct' => function($query) {
                                    $query->select('products.id as material_product_id', 'products.quantity_for_control', 'products.is_options_controlled')
                                        ->from('products');  // 另外指定使用 products 表的 id 來避免歧義，跟一開始的主表 products 區隔
                                }]);
                        }])
                        ->with('option');
                }])
                ->with('translation');
            // DataHelper::showSqlContent($builder);
            return $builder->first();
        });

        return DataHelper::unsetArrayIndexRecursively($product->toArray(), ['translation']);
    }
    

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


    public function getList($filters)
    {
        return $this->getRows($filters);
    }
}
