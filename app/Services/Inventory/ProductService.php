<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Models\Common\TermRelation;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use App\Repositories\Eloquent\Catalog\ProductRepository;

class ProductService extends Service
{
    public $modelName = "\App\Models\Catalog\Product";


    public function __construct(private ProductRepository $ProductRepository)
    {}


    public function getProducts($data = [], $debug = 0)
    {
        return $this->ProductRepository->getProducts($data, $debug);
    }


    public function getProduct($data = [], $debug = 0)
    {
        return $this->ProductRepository->getProduct($data, $debug);
    }


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $product = $this->findIdOrFailOrNew($data['product_id']);

            $product->model = $data['model'] ?? null;
            $product->main_category_id = $data['main_category_id'] ?? null;
            $product->price = $data['price'] ?? 0;
            $product->quantity = $data['quantity'] ?? 0;
            $product->comment = $data['comment'] ?? '';
            $product->is_active = $data['is_active'] ?? 0;
            $product->is_salable = $data['is_salable'] ?? 0;
            $product->is_stock_management = $data['is_stock_management'] ?? 0;
            
            $product->sort_order = $data['sort_order'] ?? 250;
            $product->source_code = $data['source_code'] ?? '';
            
            $product->supplier_id = $data['supplier_id'] ?? 0;
            $product->supplier_product_id = $data['supplier_product_id'] ?? 0;

            $product->purchasing_unit_code = $data['purchasing_unit_code'] ?? null;
            $product->stock_unit_code = $data['stock_unit_code'] ?? null;
            $product->manufacturing_unit_code = $data['manufacturing_unit_code'] ?? null;
            
            $product->save();

            $product_id = $product->id;

            if(!empty($data['translations'])){
                $this->saveTranslationData($product, $data['translations']);
            }

            // Product Categories - many to many
            if(!empty($data['product_categories'])){
                // Delete all
                TermRelation::where('object_id',$product->id)
                            ->join('terms', function($join){
                                $join->on('term_id', '=', 'terms.id');
                                $join->where('terms.taxonomy','=','product_category');
                            })
                            ->delete();

                // Add new
                foreach ($data['product_categories'] as $category_id) {
                    $insert_data[] = [
                        'object_id' => $product->id,
                        'term_id' => $category_id,
                    ];
                }
                TermRelation::insert($insert_data);
            }

            DB::commit();

            $this->resetCachedSalableProducts();

            $this->resetCachedProducts($product->id);
            
            $result['product_id'] = $product->id;

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

    public function resetCachedProducts($product_id)
    {
        // ProductOptions - used in product model
        $cacheName = app()->getLocale() . '_ProductId_' . $product_id . '_ProductOptions';
        cache()->forget($cacheName);

        // Product
        $cacheName = app()->getLocale() . '_ProductId_' . $product_id;
        cache()->forget($cacheName);
    }

    public function resetCachedSalableProducts($filter_data = [])
    {

        $cacheName = app()->getLocale() . '_salable_products';

        cache()->forget($cacheName);

        if(empty($filter_data)){
            $filter_data = [
                'filter_is_active' => 1,
                'filter_is_salable' => 1,
                'regexp' => false,
                'limit' => 0,
                'pagination' => false,
                'sort' => 'sort_order',
                'order' => 'ASC',
                'with' => ['main_category','translation'],
            ];
        }

        $result = cache()->remember($cacheName, 60*60*24*14, function() use($filter_data){
            $collections = $this->getRows($filter_data);
            return $collections;
        });

        return $result;
    }


    public function getSalableProducts($filter_data = [])
    {
        $cacheName = app()->getLocale() . '_salable_products';

        $result = cache()->get($cacheName);

        if(empty($result)){
            $result = $this->resetCachedSalableProducts($filter_data);
        }

        return $result;
    }


    public function deleteProduct($product_id)
    {
        try {

            $this->ProductRepository->delete($product_id);

            return ['success' => true];

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }


    public function getProductSourceCodes()
    {
        return $this->ProductRepository->getProductSourceCodes();
    }
}
