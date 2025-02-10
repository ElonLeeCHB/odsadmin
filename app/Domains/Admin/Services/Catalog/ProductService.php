<?php

namespace App\Domains\Admin\Services\Catalog;

use App\Helpers\Classes\DataHelper;
use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Material\ProductRepository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Models\Common\TermRelation;
use App\Models\Material\Product;
use App\Models\Material\ProductMeta;
use App\Models\Material\ProductTranslation;
use App\Models\Material\ProductOption;
use App\Models\Material\ProductOptionValue;
use App\Models\Material\ProductTag;
use App\Models\Common\Term;

class ProductService extends Service
{
    public $modelName = "\App\Models\Material\Product";
    protected $repository;

	public function __construct(protected ProductRepository $ProductRepository)
	{
        $this->repository = $ProductRepository;
    }

    // 商品管理的商品基本資料 save();
    public function save($data)
    {
        try {
            DB::beginTransaction();

            $result = $this->findIdOrFailOrNew($data['product_id']);

            if(!empty($result['error'])){
                return response(json_encode($result))->header('Content-Type','application/json');
            }

            $product = $result['data'];

            // products
                $product->model = $data['model'] ?? null;
                $product->main_category_id = $data['main_category_id'] ?? null;
                $product->price = $data['price'] ?? 0;
                $product->quantity = $data['quantity'] ?? 0;
                $product->comment = $data['comment'] ?? '';
                $product->is_active = (int) $data['is_active'] ?? 0;
                $product->is_salable = (int) $data['is_salable'] ?? 0;
                $product->is_on_web = (int) $data['is_on_web'] ?? 0;
                $product->sort_order = $data['sort_order'] ?? 999;
                $product->save();
            //


            // product_metas
                //這裡不能用全刪再新增。比如 廠商料件名稱 supplier_product_name 不會出現在這裡，會誤刪。
                $meta_keys = ['is_web_product'];
                $metas_query = ProductMeta::where('product_id', $product->id)->whereIn('meta_key', $meta_keys);

                // 取出後刪除
                $metas = $metas_query->get()->keyBy('meta_key');
                $metas_query->delete();

                $upsertData = [];

                foreach ($meta_keys as $meta_key) {
                    if(!empty($data[$meta_key])){
                        $db_meta = $metas[$meta_key] ?? [];

                        $upsertData[] = [
                            'id' => optional($db_meta)->id ?? null,
                            'product_id' => $product->id,
                            'meta_key' => $meta_key,
                            'meta_value' => $data[$meta_key],
        
                        ];
                    }
                }

                if(!empty($upsertData)){
                    ProductMeta::upsert($upsertData, ['user_id','meta_key']);
                }
            //

            // product_translations
                if(!empty($data['translations'])){
                    $this->saveRowTranslationData($product, $data['translations']);
                }
            //

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
                                        'sort_order' => $product_option['sort_order'] ?? 1000,
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
                                            'quantity' => 0, //暫時不用
                                            'default_quantity' => $product_option_value['default_quantity'] ?? 0,
                                        ];
                                        ProductOptionValue::create($arr);

                                        $cacheName = 'ProductId_' . $product->id . '_ProductOptionId_' . $product_option_model->id . '_ ProductOptionValues';
                                        cache()->forget($cacheName);
                                    }
                                }
                            } else {
                                $arr = [
                                    'id' => $product_option['option_id'],
                                    'option_id' => $product_option['option_id'],
                                    'required' => $product_option['required'],
                                    'sort_order' => $product_option['sort_order'] ?? 1000,
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
            //

            // ProductTag
            // taxonomy_id 31 = 餐點屬性
            ProductTag::where('taxonomy_id', 31)->where('product_id', $product->id)->delete();

            foreach ($data['product_tag'] ?? [] as $term_id) {
                $insert_data[] = [
                    'product_id' => $product->id,
                    'term_id' => $term_id,
                    'taxonomy_id' => 31,
                ];
            }
            if(!empty($insert_data)){
                ProductTag::insert($insert_data);
            }

            DB::commit();

            $this->setJsonCache($product->id);

            $result['product_id'] = $product->id;

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

    public function getProductTags()
    {
        return (new ProductRepository)->getProductTags();
    }

    public function getProductsForList($data)
    {
        $data['equal_is_salable'] = 1;

        $builder = Product::select(['id','code','main_category_id','sort_order','price','is_active','is_salable'])->applyFilters($data);

        if (!empty($data['filter_product_tags'])) {
            $product_tags = explode(',', $data['filter_product_tags']);

            foreach ($product_tags as $term_id) {
                $builder->whereHas('ProductTags', function ($builder) use ($term_id) {
                    $builder->where('term_id', $term_id);
                });
            }
        }
        return DataHelper::getResult($builder);
    }

}
