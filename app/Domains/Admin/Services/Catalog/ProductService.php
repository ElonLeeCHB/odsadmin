<?php

namespace App\Domains\Admin\Services\Catalog;

use App\Helpers\Classes\DataHelper;
use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Models\Common\TermRelation;
use App\Models\Common\Taxonomy;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductMeta;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use App\Models\Catalog\ProductTerm;
use App\Models\Common\Term;
use App\Helpers\Classes\OrmHelper;

class ProductService extends Service
{
    public $modelName = "\App\Models\Catalog\Product";
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
                $product->pringting_category_id = $data['pringting_category_id'] ?? null;
                $product->comment = $data['comment'] ?? '';
                $product->price = $data['price'] ?? 0;
                $product->quantity = $data['quantity'] ?? 0;
                $product->quantity_for_control = $data['quantity_for_control'] ?? 0;
                $product->is_option_qty_controlled = $data['is_option_qty_controlled'] ?? 0;
                $product->comment = $data['comment'] ?? '';
                $product->is_active = $data['is_active'] ?? 0;
                $product->is_salable = $data['is_salable'] ?? 0;
                $product->is_on_web = $data['is_on_web'] ?? 0;
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

            // product_translationsl
                if(!empty($data['translations'])){
                    $this->saveRowTranslationData($product, $data['translations']);
                }
            //

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

            // // Product Categories - many to many
            // if(!empty($data['product_categories'])){
            //     // Delete all
            //     TermRelation::where('object_id',$product->id)
            //                 ->join('terms', function($join){
            //                     $join->on('term_id', '=', 'terms.id');
            //                     $join->where('terms.taxonomy','=','product_category');
            //                 })
            //                 ->delete();

            //     // Add new
            //     foreach ($data['product_categories'] as $category_id) {
            //         $insert_data[] = [
            //             'object_id' => $product->id,
            //             'term_id' => $category_id,
            //         ];
            //     }
            //     TermRelation::insert($insert_data);
            // }

            // ProductTag
            // taxonomy_id 31 = 餐點屬性
            
            // ProductPosCategory
            if (isset($data['product_pos_category'])) {
                $taxonomy_id = 32;

                ProductTerm::where('product_id', $product->id)->where('taxonomy_id', $taxonomy_id)->delete();

                $insert_data = [];

                foreach ($data['product_pos_category'] as $term_id) {
                    $insert_data[] = [
                        'product_id' => $product->id,
                        'term_id' => $term_id,
                        'taxonomy_id' => $taxonomy_id,
                    ];
                }

                if (!empty($insert_data)) {
                    ProductTerm::insert($insert_data);
                }
            }
            //

            // ProductWwwCategory
            if (isset($data['product_www_category'])) {
                $taxonomy_id = 36;

                ProductTerm::where('product_id', $product->id)->where('taxonomy_id', $taxonomy_id)->delete();

                $insert_data = [];

                foreach ($data['product_www_category'] as $term_id) {
                    $insert_data[] = [
                        'product_id' => $product->id,
                        'term_id' => $term_id,
                        'taxonomy_id' => $taxonomy_id,
                    ];
                }

                if (!empty($insert_data)) {
                    ProductTerm::insert($insert_data);
                }
            }
            //

            DB::commit();

            //刪除快取
            $product->deleteCacheByProductId();

            $result['product_id'] = $product->id;

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

    public function getList($data)
    {
        $data['equal_is_salable'] = 1;

        $query = Product::query();
        OrmHelper::applyFilters($query, $data);

        if (!empty($data['filter_product_tags'])) {
            $product_tags = explode(',', $data['filter_product_tags']);

            foreach ($product_tags as $term_id) {
                $query->whereHas('ProductTags', function ($qry) use ($term_id) {
                    $qry->where('term_id', $term_id);
                });
            }
        }

        return OrmHelper::getResult($query, $data);
    }

    public function getPosCategories($product_id)
    {
        // $rows = ProductTerm::where('product_id', $product_id)
        //                     ->with('term.translation')
        //                     ->join('taxonomies', 'product_terms.taxonomy_id', '=', 'taxonomies.id')
        //                     ->get();
        $poscategory_ids = ProductTerm::select('term_id')->where('product_id', $product_id)
                        ->join('taxonomies', 'product_terms.taxonomy_id', '=', 'taxonomies.id')
                        ->pluck('term_id');
        $result = [];

        foreach ($poscategory_ids as $poscategory_id) {
            // $query = $this->db->query("SELECT DISTINCT *, (SELECT GROUP_CONCAT(cd1.`name` ORDER BY `level` SEPARATOR ' > ') FROM `" . DB_PREFIX . "category_path` cp LEFT JOIN `" . DB_PREFIX . "category_description` cd1 ON (cp.`path_id` = cd1.`category_id` AND cp.`category_id` != cp.`path_id`) WHERE cp.`category_id` = c.`category_id` AND cd1.`language_id` = '" . (int)$this->config->get('config_language_id') . "' GROUP BY cp.`category_id`) AS `path` FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_description` cd2 ON (c.`category_id` = cd2.`category_id`) WHERE c.`category_id` = '" . (int)$category_id . "' AND cd2.`language_id` = '" . (int)$this->config->get('config_language_id') . "'");
            $category_info = Term::getTermWithPath($poscategory_id);

			if ($category_info) {
				$result[] = (object) [
					'category_id' => $category_info->term_id,
					'name'        => ($category_info->path) ? $category_info->path . ' &gt; ' . $category_info->name : $category_info->name
				];
			}
        }

        return $result;
    }


    /**
     * 複製參考商品的選項值：
     *   以配菜為例。參考商品的配菜有蕃茄顆，目標商品的配菜選項沒有，則新增。product_option_values.id 自動遞增。
     *   以上處理完。再使目標商品的選項配菜各選項值欄位，等於參考商品的選項值欄位
     */
    public function copyProductOption($product_id, $option_id, $product_ids)
    {
        try {
            DB::beginTransaction();
        
            $query = ProductOption::select(['id'])
                        ->with(['productOptionValues'])
                        ->where('product_id', $product_id)
                        ->where('option_id', $option_id);
            
            $srcProductOption = $query->first();

            $srcProductOption->setRelation('productOptionValues', $srcProductOption->productOptionValues->keyBy('option_value_id'));

            $src_option_value_ids = $srcProductOption->productOptionValues->pluck('option_value_id')->toArray();

            if (in_array($product_id, $product_ids)){
                $product_ids = array_diff($product_ids, [$product_id]);
            }

            // products
            $query = Product::select(['id', 'is_active'])->with(['productOptions' => function ($query) {
                $query->where('option_id', 1005)
                    ->with('productOptionValues'); // 這裡不判斷 product_option_values.is_active, 要全抓。
            }])->whereIn('id', $product_ids);

            $products = $query->get();

            foreach ($products as $product) {
                $productOption = $product->productOptions[0];

                $dst_option_value_ids = $productOption->productOptionValues->pluck('option_value_id')->toArray();

                $add_option_value_ids = array_diff($src_option_value_ids, $dst_option_value_ids); // src 有， dst 沒有，要新增
                $del_option_value_ids = array_diff($dst_option_value_ids, $src_option_value_ids); // src 沒有， dst 有，要刪除
                $coexist_option_value_ids = array_intersect($dst_option_value_ids, $src_option_value_ids); // 都有
                
                if (!empty($add_option_value_ids)){
                    $insertProductOptionValue = [];

                    foreach ($add_option_value_ids as $option_value_id) {
                        $insertProductOptionValue[] = [
                            'product_option_id' => $productOption->id,
                            'product_id' => $product->id,
                            'option_id' => $productOption->option_id,
                            'option_value_id' => $option_value_id,
                            'default_quantity' => $srcProductOption->productOptionValues[$option_value_id]->default_quantity, // 預設使用多少量
                            'quantity' => 0, //庫存，預設0，不複製。
                            'is_default' => $srcProductOption->productOptionValues[$option_value_id]->is_default, 
                            'is_active' => $srcProductOption->productOptionValues[$option_value_id]->is_active, 
                            'subtract' => $srcProductOption->productOptionValues[$option_value_id]->subtract, 
                            'price' => $srcProductOption->productOptionValues[$option_value_id]->price, 
                            'price_prefix' => $srcProductOption->productOptionValues[$option_value_id]->price_prefix, 
                            'required' => $srcProductOption->productOptionValues[$option_value_id]->required, 
                            'sort_order' => $srcProductOption->productOptionValues[$option_value_id]->sort_order, 
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];
                    }
            
                    ProductOptionValue::insert($insertProductOptionValue);
                }

                if (!empty($coexist_option_value_ids)) {
                    foreach ($coexist_option_value_ids as $option_value_id) {
                        $srcValue = $srcProductOption->productOptionValues[$option_value_id];
                        
                        $query = ProductOptionValue::query()
                            ->where('product_id', $product->id)
                            ->where('product_option_id', $productOption->id)
                            ->where('option_id', $productOption->option_id)
                            ->where('option_value_id', $option_value_id);

                        $query->update([
                                'is_default' => $srcValue->is_default,
                                'is_active' => $srcValue->is_active,
                                'default_quantity' => $srcValue->default_quantity,
                                'quantity' => 0,
                                'subtract' => $srcValue->subtract,
                                'price' => $srcValue->price,
                                'price_prefix' => $srcValue->price_prefix,
                                'required' => $srcValue->required,
                                'sort_order' => $srcValue->sort_order,
                                'updated_at' => now(),
                            ]);
                    }
                }

                //刪除快取
                $product->deleteCacheByProductId();
            }
            
            DB::commit();

            return true;
            
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
    
}
