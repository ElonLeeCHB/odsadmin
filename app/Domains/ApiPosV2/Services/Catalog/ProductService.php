<?php

namespace App\Domains\ApiPosV2\Services\Catalog;

use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\OrmHelper;
use App\Services\Service;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Traits\Model\EloquentTrait;
use App\Models\Catalog\Option;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use App\Models\Common\TermTranslation;
use Illuminate\Support\Facades\DB;
use App\Caches\Catalog\Product\Sale\ProductByLocale;

class ProductService extends Service
{
    use EloquentTrait;

    public $modelName = "\App\Models\Catalog\Product";

    public function getProductById($product_id)
    {
        // $product = ProductByLocale::getById($product_id, app()->getLocale());

        // $product = DataHelper::arrayRemoveKeyRecursive($product, 'translation');

        // return $product;
        return (new Product)->getLocaleProductByIdForSale($product_id);
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


    public function getList($filter_data)
    {
        $query = Product::query();

        if(!empty($filter_data['filter_product_tag_names'])){
            $product_tag_names = explode(',', $filter_data['filter_product_tag_names']);

            $tags = TermTranslation::where('locale', app()->getLocale())
                ->where(function($query) use ($product_tag_names) {
                    foreach ($product_tag_names ?? [] as $product_tag_name) {
                        $query->orWhere('name', 'like', "%{$product_tag_name}%");
                    }
                })
                ->whereHas('master', function($query) {
                    $query->where('taxonomy_code', 'ProductTag');
                    $query->where('is_active', 1);
                });

            $tag_ids = $tags->pluck('term_id')->toArray();

            $query->whereHas('productTags', function($query) use ($tag_ids) {
                $query->whereIn('term_id', $tag_ids)
                      ->havingRaw('COUNT(DISTINCT term_id) = ?', [count($tag_ids)]);
            });
        }
        // $builder->debug();

        $params['select'] = ['id', 'price'];

        OrmHelper::prepare($query, $params);

        $products = OrmHelper::getResult($query, $params);

        foreach ($products as $product) {
            $result[] = [
                'id' => $product->id,
                'name' => $product->translation->name,
                'web_name' => $product->translation->web_name,
                'price' => $product->price,
            ];
        }

        return $result;
    }
}
