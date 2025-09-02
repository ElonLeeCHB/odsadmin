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
