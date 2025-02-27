<?php

namespace App\Domains\ApiPosV2\Services\Catalog;

use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Repositories\Eloquent\Material\ProductRepository;
use App\Traits\Model\EloquentTrait;
use App\Models\Material\Product;
use App\Models\Material\ProductTag;
use App\Models\Common\TermTranslation;

class ProductService extends Service
{
    use EloquentTrait;

    public $modelName = "\App\Models\Material\Product";

    public function getInfo($product_id)
    {
        $productModel = new Product;
        
        return $productModel->getCache($product_id);
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
        $builder = Product::query();

        if(!empty($filter_data['filter_product_tag_names'])){
            $product_tag_names = explode(',', $filter_data['filter_product_tag_names']);

            $tags = TermTranslation::where('locale', app()->getLocale())
                ->where(function($query) use ($product_tag_names) {
                    foreach ($product_tag_names ?? [] as $product_tag_name) {
                        $query->orWhere('name', 'like', "%{$product_tag_name}%");
                    }
                })
                ->whereHas('master', function($query) {
                    $query->where('taxonomy_code', 'ProductTag');  // 過濾 taxonomy_code 為 'ProductTag' 的 term
                });
            // $rows->debug();

            $tag_ids = $tags->pluck('term_id')->toArray();

            $builder->whereHas('productTags', function($query) use ($tag_ids) {
                $query->whereIn('term_id', $tag_ids);  // 過濾 productTag 中的 tag_id
            });
        }
        
        $builder->select(['id', 'price']);

        $products = $builder->getResult($filter_data);

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
