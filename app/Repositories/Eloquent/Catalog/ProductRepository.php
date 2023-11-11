<?php

namespace App\Repositories\Eloquent\Catalog;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Repositories\Eloquent\Inventory\UnitRepository;
use App\Repositories\Eloquent\Catalog\ProductUnitRepository;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use App\Models\Catalog\ProductTranslation;
use App\Models\Common\TermRelation;
use App\Models\Inventory\BOM;
use App\Models\Inventory\BomProduct;
use Carbon\Carbon;

use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\ExportsLaravelExcel\CommonExport;

class ProductRepository extends Repository
{
    public $modelName = "\App\Models\Catalog\Product";


    public function __construct(private TermRepository $TermRepository, private UnitRepository $UnitRepository, private ProductUnitRepository $ProductUnitRepository)
    {
        parent::__construct();
    }

    public function getProducts($data = [], $debug = 0)
    {
        $filter_data = $this->resetQueryData($data);
        $products = $this->getRows($filter_data, $debug);
        
        if(count($products) == 0){
            return $products;
        }

        // 額外欄位 預先處理是否需要 load() 或是抓取其它資料集
        // 沒有用 with, 有時候好像 with 會失敗
        if(!empty($data['extra_columns'])){

            // units table
            $product_unit_names = ['stock_unit_name', 'counting_unit_name', 'usage_unit_name']; // 如果有用到這些單位
            $matches = array_intersect($product_unit_names, $data['extra_columns']);
            
            if (!empty($matches) || in_array('available_units', $data['extra_columns'])) {
                $filter_data = [
                    'equal_is_active' => 1,
                ];
                $units = $this->UnitRepository->getCodeKeyedActiveUnits($filter_data);
                $products->load('stock_unit.translation');
            }

            // supplier_columns
            $supplier_columns = ['supplier_name', 'supplier_short_name'];
            $matches = array_intersect($supplier_columns, $data['extra_columns']);

            if (!empty($matches)) {
                $products->load('supplier');
            }

            // terms table
            $term_columns = ['accounting_category_name'];
            $matches = array_intersect($term_columns, $data['extra_columns']);
            
            if (!empty($matches)) {
                $products->load('accounting_category.translation');
            }

            $term_columns = ['source_type_name'];
            $matches = array_intersect($term_columns, $data['extra_columns']);
            
            if (!empty($matches)) {
                $products->load('source_type.translation');
            }
            
        }

        foreach ($products as $row) {

            // 額外欄位 掛載到資料集
            if(!empty($data['extra_columns'])){

                // product_units
                $matches = array_intersect($product_unit_names, $data['extra_columns']);
                if (!empty($matches)) {
                    $row->stock_unit_name = $units[$row->stock_unit_code]->name ?? '';
                    $row->counting_unit_name = $units[$row->counting_unit_code]->name ?? '';
                    $row->usage_unit_name = $units[$row->usage_unit_code]->name ?? '';
                }

                // supplier_columns
                $matches = array_intersect($supplier_columns, $data['extra_columns']);
                if (!empty($matches)) {
                    $row->supplier_name = $row->supplier->name ?? '';
                    $row->supplier_short_name = $row->supplier->short_name ?? '';
                }

                if(in_array('source_type_name', $data['extra_columns'])){
                    $row->source_type_name = $row->source_type->name ?? '';
                }

                if(in_array('accounting_category_name', $data['extra_columns'])){
                    $row->accounting_category_name = !empty($row->accounting_category->name) ? $row->accounting_category->code . ':' .$row->accounting_category->name : '';
                }
            }
        }

        return $products;
    }

    public function getProduct($data = [], $debug = 0)
    {
        $data = $this->resetQueryData($data);

        $row = $this->getRow($data, $debug);

        $row->supplier_name = $row->supplier->name ?? '';

        return $row;
    }

    // 商品管理的商品基本資料 save();
    public function updateOrCreateProduct($data)
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
            $product->is_salable = 1;
            $product->sort_order = $data['sort_order'] ?? 250;

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

            DB::commit();
            
            $result['product_id'] = $product->id;

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }


    public function getSalableProducts($data = [], $debug = 0)
    {
        $data['equal_is_salable'] = 1;

        $salable_products = $this->getProducts($data, $debug);

        return $salable_products;
    }


    public function getAllSalableProducts($data = [], $debug = 0)
    {
        $data['equal_is_salable'] = 1;
        $data['pagination'] = false;
        $data['limit'] = 0;
        $salable_products = $this->getProducts($data, $debug);

        return $salable_products;
    }

    /**
     * 不應該刪除商品，應設為不啟用。
     * 或者逐一檢查，若有其它地方用到，例如 BOM 表，則回傳提示，請使用者先刪除 BOM 表。而不是在此刪除 BOM 表 ，太危險。
     */
    // public function deleteProduct($product_id)
    // {
    //     try {

    //         DB::beginTransaction();

    //         ProductOption::where('product_id', $product_id)->delete();
    //         //ProductOptionTranslation::where('product_id', $product_id)->delete();
    //         ProductOptionValue::where('product_id', $product_id)->delete();
    //         //ProductOptionValueTranslation::where('product_id', $product_id)->delete();

    //         BomProduct::where('sub_product_id', $product_id)->delete();
    //         BomProduct::where('product_id', $product_id)->delete();
    //         Bom::where('product_id', $product_id)->delete();

    //         ProductTranslation::where('product_id', $product_id)->delete();

    //         TermRelation::join('terms', 'term_relations.term_id', '=', 'terms.id')
    //                     ->whereIn('terms.taxonomy_code', ['product_category', 'product_tag', 'product_inventory_category', 'product_accounting_category'])
    //                     ->delete();

    //         Product::where('id', $product_id)->delete();

    //         DB::commit();

    //     } catch (\Exception $ex) {
    //         DB::rollback();
    //         return ['error' => $ex->getMessage()];
    //     }
    // }



    public function resetQueryData($data)
    {
        if(!empty($data['filter_keyword'])){
            $data['filter_name'] = $data['filter_keyword'];
            $data['filter_specification'] = $data['filter_keyword'];
            $data['filter_model'] = $data['filter_keyword'];
            unset($data['filter_keyword']);
        }

        foreach ($data as $key => $value) {
            if(str_starts_with($key, 'filter_') || str_starts_with($key, 'equal_')){
                if($value == ''){
                    unset($data[$key]);
                }
            }
        }

        if(!empty($data['filter_supplier_name'])){
            $data['whereHas'] = ['supplier' => ['name' => $data['filter_supplier_name']]];
            unset($data['filter_supplier_name']);
        }

        return $data;
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
    

    public function getKeyedSourceCodes()
    {
        $filter_data = [
            'equal_taxonomy_code' => 'product_source_type',
            'equal_is_active' => 1,
            'pagination' => false,
            'limit' => 0,
            'sort' => 'code',
            'order' => 'ASC',
            'with' => ['taxonomy.translation'],
        ];

        $rows = $this->TermRepository->getRows($filter_data)->toArray();
        
        $new_rows = [];
        foreach ($rows as $key => $row) {
            unset($row['translation']);
            unset($row['taxonomy']);
            $code = $row['code'];
            $row['label'] = $row['code'] . ' '. $row['name'];
            
            $new_rows[$code] = (object)$row;
        }

        return $new_rows;
    }

    // 會計分類
    public function getKeyedAccountingCategory()
    {
        $filter_data = [
            'equal_taxonomy_code' => 'product_accounting_category',
            'equal_is_active' => 1,
            'pagination' => false,
            'limit' => 0,
            'sort' => 'code',
            'order' => 'ASC',
            'with' => ['taxonomy.translation'],
        ];

        $rows = $this->TermRepository->getRows($filter_data)->toArray();

        $new_rows = [];
        
        foreach ($rows as $key => $row) {
            unset($row['translation']);
            unset($row['taxonomy']);
            $code = $row['code'];
            $row['label'] = $row['code'] . ' '. $row['name'];
            
            $new_rows[$code] = (object)$row;
        }

        return $new_rows;
    }


    // 尋找關聯，並將關聯值賦予記錄
    public function optimizeRow($row)
    {
        // if(!empty($row->status)){
        //     $row->status_name = $row->status->name;
        // }

        return $row;
    }


    // 刪除關聯
    public function sanitizeRow($row)
    {
        $arrOrder = $row->toArray();

        if(!empty($arrOrder['translation'])){
            unset($arrOrder['translation']);
        }

        return (object) $arrOrder;
    }


    // 額外欄位 - 單筆記錄
    public function setRowExtraColumns($row, $columns)
    {
        if(in_array('usage_unit_code_name', $columns)){
            $row->usage_unit_code_name = $row->usage_unit->name ?? '';
        }

        // if(in_array('avaible_unit_codes', $columns) && !empty($row->avaible_unit_codes)){
        //     // $arr = json_decode($this->avaible_unit_codes);
        //     // $row->avaible_unit_codes = 
        // }

        if(in_array('available_units', $columns) && !empty($row->avaible_unit_codes)){
            $available_units = 11;
        }

        

        return $row;
    }
    

    // 額外欄位 - 多筆記錄
    private function getRowsExtraColumns($rows, $columns)
    {
        foreach ($rows as $row) {
            $row = $this->setRowExtraColumns($row, $columns);
        }
    }

    public function exportInventoryProductList($post_data = [], $debug = 0)
    {
        $post_data = $this->resetQueryData($post_data);

        if(empty($post_data['sort'])){
            $post_data['sort'] = 'id';
            $post_data['order'] = 'ASC';
        }

        $post_data['pagination'] = false;
        $post_data['limit'] = 1000;
        $post_data['extra_columns'] = ['supplier_name', 'accounting_category_name','source_type_name'
                                        , 'stock_unit_name', 'counting_unit_name', 'usage_unit_name'
                                      ];

        $products = $this->getProducts($post_data);

        $data = [];
        $rows = [];

        foreach ($products as $product) {
            $rows[] = [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'specification' => $product->specification,

                'supplier_own_product_code' => $product->supplier_own_product_code,
                'supplier_own_product_name' => $product->supplier_own_product_name,
                'supplier_own_product_specification' => $product->supplier_own_product_specification,
                'supplier_id' => $product->supplier_id,
                'supplier_name' => $product->supplier_name,
                'source_type_code' => $product->source_type_code,
                'source_type_name' => $product->source_type_name,
                'temperature_type_code' => $product->temperature_type_code,

                'accounting_category_code' => $product->accounting_category_code,
                'accounting_category_name' => $product->accounting_category_name,
                'stock_unit_code' => $product->stock_unit_code,
                'stock_unit_name' => $product->stock_unit_name,
                'counting_unit_code' => $product->counting_unit_code,
                'counting_unit_name' => $product->counting_unit_name,
                'usage_unit_code' => $product->usage_unit_code,
                'usage_unit_name' => $product->usage_unit_name,
                'is_inventory_managed' => $product->is_inventory_managed,
                'is_active' => $product->is_active,
                
            ];
        }
        $data['collection'] = collect($rows);

        $data['headings'] = ['ID', '品號', '品名', '規格'
                             , '廠商品號', '廠商品名', '廠商規格', '廠商ID', '廠商名稱', '來源碼', '來源名稱', '存放溫度類型'
                             , '會計分類碼', '會計分類', '庫存單位', '名稱', '盤點單位', '名稱', '用量單位', '名稱', '庫存管理', '啟用'
                            ];

        return Excel::download(new CommonExport($data), 'inventory_products.xlsx');
    }
}

