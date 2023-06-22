<?php

namespace App\Domains\Admin\Services\Catalog;

use App\Domains\Admin\Services\Service;
use App\Models\Common\TermRelation;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use Illuminate\Support\Facades\DB;

class ProductService extends Service
{
    public $modelName = "\App\Models\Catalog\Product";

    public function getProducts($data=[], $debug = 0)
    {
        if(!empty($data['filter_keyword'])){
            $arr['filter_name'] = $data['filter_keyword'];
            $arr['filter_short_name'] = $data['filter_keyword'];
            $arr['filter_description'] = $data['filter_keyword'];
            unset($data['filter_keyword']);
        }

        if(!empty($arr)){
            $data['whereHas']['translation'] = $arr;
        }

        $rows = $this->getRows($data,$debug);

        return $rows;

    }


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $product = $this->findIdOrFailOrNew($data['product_id']);

            $product->model = $data['model'] ?? 'model';
            $product->main_category_id = $data['main_category_id'] ?? null;
            $product->price = $data['price'] ?? 0;
            $product->quantity = $data['quantity'] ?? 0;
            $product->comment = $data['comment'] ?? '';
            $product->is_active = $data['is_active'] ?? 0;
            $product->is_salable = $data['is_salable'] ?? 0;

            $product->save();

            $cacheName = app()->getLocale() . '_ProductId_' . $product->id;
            cache()->forget($cacheName);

            $product_id = $product->id;

            if(!empty($data['product_translations'])){
                $this->saveTranslationData($product, $data['product_translations']);
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

            // Product Options
            // Delete all
            ProductOption::where('product_id', $product->id)->delete();
            ProductOptionValue::where('product_id', $product->id)->delete();

            if(!empty($data['product_options'])){

                if(!empty($data['product_options'])){
                    foreach ($data['product_options'] as $product_option) {

                        if ($product_option['type'] == 'options_with_qty' || $product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                            if (isset($product_option['product_option_values'])) {
                                $arr = [
                                    'id' => $product_option['product_option_id'],
                                    'option_id' => $product_option['option_id'],
                                    'required' => $product_option['required'] ?? 0,
                                    'sort_order' => $product_option['sort_order'] ?? 999,
                                    'is_active' => $product_option['is_active'] ?? 1,
                                    'is_fixed' => $product_option['is_fixed'] ?? 0,
                                    'is_hidden' => $product_option['is_hidden'] ?? 0,
                                    'product_id' => $product->id,
                                    'type' => $product_option['type'],
                                ];
                                $product_option_model = ProductOption::create($arr);

                                foreach ($product_option['product_option_values'] as $product_option_value) {
                                    $arr = [
                                        'id' => $product_option_value['product_option_value_id'],
                                        'product_option_id' => $product_option_model->id,
                                        'option_id' => $product_option['option_id'],
                                        'option_value_id' => $product_option_value['option_value_id'],
                                        'product_id' => $product->id,
                                        'price_prefix' => $product_option_value['price_prefix'],
                                        'price' => $product_option_value['price'],
                                        'sort_order' => $product_option_value['sort_order'] ?? 0,
                                        'is_active' => $product_option_value['is_active'] ?? 1,
                                        'is_default' => $product_option_value['is_default'] ?? 0,
                                    ];
                                    $product_option_value_model = ProductOptionValue::create($arr);

                                    $cacheName = 'ProductId_' . $product->id . '_ProductOptionId_' . $product_option_model->id . '_ ProductOptionValues';
                                    cache()->forget($cacheName);
                                }
                            }
                        } else {
                            $arr = [
                                'id' => $product_option['option_id'],
                                'option_id' => $product_option['option_id'],
                                'required' => $product_option['required'],
                                'sort_order' => $product_option['sort_order'] ?? 999,
                                'is_active' => $product_option['is_active'] ?? 1,
                                'is_fixed' => $option_value['is_fixed'] ?? 0,
                                'is_hidden' => $option_value['is_hidden'] ?? 0,
                                'product_id' => $data['product_id'],
                                'value' => $product_option['value'],
                                'type' => $product_option['type'],
                            ];
                            $product_option = ProductOption::create($arr);
                        }
                    }
                }
            }

            DB::commit();

            $this->resetCachedSalableProducts();

            $result['product_id'] = $product->id;

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }


    public function getProduct($data = [])
    {
        $cacheName = app()->getLocale() . 'ProductId_' . $data['filter_id'];

        $result = cache()->remember($cacheName, 60*60*24*14, function() use($data){
            $collection = $this->getRow($data);

            return $collection;
        });

        if(empty($result)){
            $result = [];
        }

        return $result;
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

}
