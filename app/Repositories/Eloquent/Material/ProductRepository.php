<?php

namespace App\Repositories\Eloquent\Material;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Repositories\Eloquent\Inventory\UnitRepository;
use App\Models\Material\Product;
use App\Models\Material\ProductTranslation;
use App\Models\Material\ProductOption;
use App\Models\Material\ProductOptionValue;
use App\Models\Common\Term;
use App\Models\Inventory\Bom;
use App\Models\Inventory\BomProduct;
use App\Helpers\Classes\DataHelper;
use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\ExportsLaravelExcel\CommonExport;

class ProductRepository extends Repository
{
    public $modelName = "\App\Models\Material\Product";

    public function getProducts($data = [], $debug = 0)
    {
        $filter_data = $this->resetQueryData($data);

        $products = $this->getRows($filter_data, $debug);

        if(count($products) == 0){
            return [];
        }

        // 額外欄位 預先處理是否需要 load() 或是抓取其它資料集
        // 沒有用 with, 有時候好像 with 會失敗
        if(!empty($data['extra_columns'])){

            $products->load('metas');

            // metas
            foreach ($products as $product) {
                foreach ($product->metas as $meta) {
                    $key = $meta->meta_key;
                    $product->{$key} = $meta->meta_value;
                }
            }

            // units table
            $product_unit_names = ['stock_unit_name', 'counting_unit_name', 'usage_unit_name']; // 如果有用到這些單位
            $matches = array_intersect($product_unit_names, $data['extra_columns']);

            if (!empty($matches) || in_array('available_units', $data['extra_columns'])) {
                $filter_data = [
                    'equal_is_active' => 1,
                ];
                $units = (new UnitRepository)->getCodeKeyedActiveUnits($filter_data);
                $products->load('stock_unit.translation');
            }

            // supplier_columns
            $matches = array_intersect(['supplier_name', 'supplier_short_name'], $data['extra_columns']);

            if (!empty($matches)) {
                $products->load('supplier');
            }

            // terms table
            // - accounting category
            if(in_array('accounting_category_name', $data['extra_columns'])){
                $products->load('accounting_category.translation');
            }

            // - source type
            if(in_array('source_type_name', $data['extra_columns'])){
                $products->load('source_type.translation');
            }

            // - storage type
            if(in_array('temperature_type_name', $data['extra_columns'])){
                $temperature_types = TermRepository::getCodeKeyedTermsByTaxonomyCode('product_storage_temperature_type',toArray:false);
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
                $matches = array_intersect(['supplier_name', 'supplier_short_name'], $data['extra_columns']);
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

                // temperature_type_name
                if(in_array('temperature_type_name', $data['extra_columns'])){
                    $row->temperature_type_name = $temperature_types[$row->temperature_type_code]->name ?? '';
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

    public function setJsonCache($id)
    {
        $product = Product::with('translations', 'translation', 'product_options', 'metas')->find($id);

        $product_array = $product->toArray();

        if(!empty($product_array['translations'])) {
            foreach ($product_array['translations'] as $key => $translation) {
                $locale = $translation['locale'];
                $product_array['translations'][$locale] = $translation;
                unset($product_array['translations'][$key]);
            }
        }

        if(!empty($product_array['translation'])) {
            foreach ($product_array['translation'] as $key => $value) {
                $product_array[$key] = $value;
            }
        }

        if(!empty($product_array['metas'])) {
            foreach ($product_array['metas'] as $key => $meta_row) {
                $meta_key = $meta_row['meta_key'];
                $product_array[$meta_key] = $meta_row['meta_value'];

                $product_array['metas'][$meta_key] = $meta_row;
                unset($product_array['metas'][$key]);
            }
        }

        // temperature_type_code
        if(!empty($product_array['temperature_type_code'])){
            $temperature_type_code = $product_array['temperature_type_code'];
            $temperature_types = TermRepository::getCodeKeyedTermsByTaxonomyCode('product_storage_temperature_type',false);
            $product_array['temperature_type_name'] = $temperature_types[$temperature_type_code]->name;
        }

        // accounting_category_code
        if(!empty($product_array['accounting_category_code'])){
            $accounting_category_code = $product_array['accounting_category_code'];
            $accounting_categories = TermRepository::getCodeKeyedTermsByTaxonomyCode('product_accounting_category',false);
            $product_array['accounting_category_name'] = $accounting_categories[$accounting_category_code]->name;
        }

        // accounting_category_code
        if(!empty($product_array['source_type_code'])){
            $source_type_code = $product_array['source_type_code'];
            $source_types = TermRepository::getCodeKeyedTermsByTaxonomyCode('product_source_type',false);
            $product_array['source_type_name'] = $source_types[$source_type_code]->name;
        }

        $cache_name = 'cache/products/id_keyed/' . $id . '.json';

        return DataHelper::setJsonToStorage($cache_name, $product_array);
    }

    public function getJsonCache($id)
    {
        $cache_name = 'cache/products/id_keyed/' . $id . '.json';
        return DataHelper::getJsonFromStoragNew($cache_name, true);
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


    public function destroy($ids)
    {

        // //不應該刪除商品。太危險。太多地方要檢臺。還有訂單、進貨單、盤點表、備料表、料件需求表…
        // return ['error' => 'Product should not be deleted here.'];

        try {
            DB::beginTransaction();

            BomProduct::whereIn('sub_product_id', $ids)->delete();
            BomProduct::whereIn('product_id', $ids)->delete();
            Bom::whereIn('product_id', $ids)->delete();

            ProductOptionValue::whereIn('product_id', $ids)->delete();
            ProductOption::whereIn('product_id', $ids)->delete();

            ProductTranslation::whereIn('product_id', $ids)->delete();

            $result = Product::whereIn('id', $ids)->delete();

            DB::commit();

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

    public function deleteProductsById($ids)
    {
        return ['error' => '不允許批次刪除！'];
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

        $rows = (new TermRepository)->getRows($filter_data)->toArray();

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

        $rows = (new TermRepository)->getRows($filter_data)->toArray();

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


    public function getProductTags()
    {
        $rows = Term::where('taxonomy_code', 'ProductTag')->active()->with('translation')->get();

        foreach ($rows as $row) {
            $statuses[$row->id] = $row->name;
        }

        return $statuses;
    }

}

