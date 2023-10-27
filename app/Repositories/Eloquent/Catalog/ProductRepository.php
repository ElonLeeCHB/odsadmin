<?php

namespace App\Repositories\Eloquent\Catalog;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Repositories\Eloquent\Common\UnitRepository;
use App\Repositories\Eloquent\Catalog\ProductUnitRepository;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use App\Models\Catalog\ProductBom;
use App\Models\Catalog\ProductTranslation;
use App\Models\Common\TermRelation;
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


       // echo '<pre>', print_r($filter_data, 1), "</pre>"; exit;
        
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
                    'equal_is_active' => 1
                ];
                $units = $this->UnitRepository->getKeyedActiveUnits($filter_data);
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

    public function delete($product_id)
    {
        try {

            DB::beginTransaction();

            ProductOption::where('product_id', $product_id)->delete();
            //ProductOptionTranslation::where('product_id', $product_id)->delete();
            ProductOptionValue::where('product_id', $product_id)->delete();
            //ProductOptionValueTranslation::where('product_id', $product_id)->delete();

            ProductBom::where('product_id', $product_id)->delete();
            ProductTranslation::where('product_id', $product_id)->delete();

            TermRelation::join('terms', 'term_relations.term_id', '=', 'terms.id')
                        ->whereIn('terms.taxonomy_code', ['product_category', 'product_tag', 'product_inventory_category', 'product_accounting_category'])
                        ->delete();

            Product::where('id', $product_id)->delete();

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

    public function saveProduct($post_data, $debug = 0)
    {
        try {

            $product_id = $post_data['product_id'] ?? $post_data['id'] ?? null;

            // 若庫存單位已存在則不改
            // if(!empty($product->stock_unit_code)){
            //     unset($product->stock_unit_code);
            // }
            // if(!empty($post_data['stock_unit_code'])){
            //     unset($post_data['stock_unit_code']);
            // }
            
            $result = $this->saveRow($product_id, $post_data);

            if(!empty($result['error'])){
                throw new \Exception($result['error']);
            }


            $product = $this->findIdOrFailOrNew($product_id);

            // 商品單位表 product_units
            if(!empty($post_data['product_units'])){
                $upsert_data = [];
                foreach ($post_data['product_units'] as $product_unit) {

                    if(empty($product_unit['source_quantity']) || empty($product_unit['source_unit_code']) || empty($product_unit['destination_quantity']) || empty($product_unit['destination_unit_code'])){
                       continue;
                    }

                    $upsert_data[] = [
                        'id' => $product_unit['id'] ?? null,
                        'product_id' => $product->id,
                        'source_quantity' => $product_unit['source_quantity'],
                        'source_unit_code' => $product_unit['source_unit_code'],
                        'destination_unit_code' => $product_unit['destination_unit_code'],
                        'destination_quantity' => $product_unit['destination_quantity'],
                    ];
                }
                
                if(!empty($upsert_data)){
                    $this->ProductUnitRepository->newModel()->where('product_id', $product->id)->delete();
                    $this->ProductUnitRepository->newModel()->upsert($upsert_data, ['id']);
                }
            }
    
            return ['id' => $product->id];

        } catch (\Exception $ex) {
            $result['error'] = 'Error code: ' . $ex->getCode() . ', Message: ' . $ex->getMessage();
            return $result;
        }
    }

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
        }

        return $data;
    }


    public function getProductSourceCodes()
    {
        $filter_data = [
            'equal_taxonomy_code' => 'product_source_type',
            'pagination' => false,
            'limit' => 0,
            'with' => 'taxonomy.translation',
        ];
        $collection = $this->TermRepository->getRows($filter_data)->toArray();

        $result = [];

        foreach ($collection as $key => $row) {
            unset($row['translation']);
            unset($row['taxonomy']);
            $code = $row['code'];
            $result[$code] = (object) $row;
        }

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
           // echo '<pre>', print_r(999, 1), "</pre>"; exit;
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

    public function exportEmtpyInventoryList($post_data = [], $debug = 0)
    {
        $post_data = $this->resetQueryData($post_data);

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

                'supplier_product_code' => $product->supplier_product_code,
                'supplier_product_name' => $product->supplier_product_name,
                'supplier_product_specification' => $product->supplier_product_specification,
                'supplier_id' => $product->supplier_id,
                'supplier_name' => $product->supplier_name,
                'source_type_code' => $product->source_type_code,
                'source_type_name' => $product->source_type_name,

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

        $data['headings'] = ['ID', '品號', '品名', '規格',
                             '廠商品號', '廠商品名', '廠商規格', '廠商ID', '廠商名稱', '來源碼', '來源名稱',
                             '會計分類碼', '會計分類', '庫存單位', '名稱', '盤點單位', '名稱', '用量單位', '名稱', '庫存管理', '啟用'
                            ];

        return Excel::download(new CommonExport($data), 'inventory_products.xlsx');
    }
}

