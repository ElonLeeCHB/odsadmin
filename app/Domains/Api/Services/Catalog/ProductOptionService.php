<?php

namespace App\Domains\Api\Services\Catalog;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Domains\Api\Services\Service;
use App\Traits\Model\EloquentTrait;
use App\Libraries\TranslationLibrary;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;

class ProductOptionService extends Service
{
    use EloquentTrait;

    public $modelName;
    public $model;
    public $table;
    public $lang;

	public function __construct()
	{
        $this->modelName = "\App\Models\Catalog\ProductOption";
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/member/member',]);
	}


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            extract($data); //$data['some_id'] => $some_id;

            $product = $this->findOrNew(['id'=>$product_id]);

            $product->model = $data['model'] ?? 'model';
            $product->main_category_id = $data['main_category_id'] ?? null;
            $product->price = $data['price'] ?? 0;
            $product->quantity = $data['quantity'] ?? 0;
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

            $result['data']['product_id'] = $product->id;

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            $msg = $ex->getMessage();
            echo '<pre>', print_r($msg, 1), "</pre>"; exit;
            return response()->json(['error' => $ex->getMessage()], 500);
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


    public function getTotalProductsByOptionId($option_id)
    {
        $filter_data = [
            'filter_option_id' => $option_id,
            'regexp' => false,
        ];

        return $this->getCount($filter_data);
    }


    public function getSalableProducts($filter_data = [])
    {
        $cacheName = app()->getLocale() . '_salable_products';

        $result = cache()->remember($cacheName, 60*60*24*14, function() use($filter_data){
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
            $collections = $this->getRecords($filter_data);

            return $collections;
        });

        if(empty($result)){
            $result = [];
        }

        return $result;
    }


    public function validator(array $data)
    {
        return Validator::make($data, [
                'organization_id' =>'nullable|integer',
                'name' =>'nullable|max:10',
                'short_name' =>'nullable|max:10',
            ],[
                'organization_id.integer' => $this->lang->error_organization_id,
                'name.*' => $this->lang->error_name,
                'short_name.*' => $this->lang->error_short_name,
        ]);
    }

}
