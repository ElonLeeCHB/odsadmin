<?php

namespace App\Repositories\Eloquent\Catalog;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Repositories\Eloquent\Common\UnitRepository;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use App\Models\Catalog\ProductBom;
use App\Models\Catalog\ProductTranslation;
use App\Models\Common\TermRelation;

class ProductRepository extends Repository
{
    public $modelName = "\App\Models\Catalog\Product";

    private $source_type_codes;


    public function __construct(protected TermRepository $TermRepository, protected UnitRepository $UnitRepository)
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

        
        $product_unit_codes = ['stock_unit_code', 'purchasing_unit_code', 'usage_unit_code'];
        $supplier_columns = ['name', 'short_name'];


        // 額外欄位 預處理
        if(!empty($data['extra_columns'])){

            // product_units
            $matches = array_intersect($product_unit_codes, $data['extra_columns']);
            
            if (!empty($matches)) {
                // units
                $filter_data = [
                    'equal_is_active' => 1
                ];
                $units = $this->UnitRepository->getKeyedActiveUnits($filter_data);
            }

            // supplier_columns
            $matches = array_intersect($supplier_columns, $data['extra_columns']);
            if (!empty($matches)) {
                $products->load('supplier_name');
            }
        }

        foreach ($products as $row) {

            // 額外欄位 掛載到資料集
            if(!empty($data['extra_columns'])){

                // product_units
                $matches = array_intersect($product_unit_codes, $data['extra_columns']);
                if (!empty($matches)) {
                    $row->stock_unit_name = $units[$row->stock_unit_code]->name ?? '';
                    $row->usage_unit_name = $units[$row->usage_unit_code]->name ?? '';
                }

                // supplier_columns
                $matches = array_intersect($supplier_columns, $data['extra_columns']);
                if (!empty($matches)) {
                    $row->supplier_name = $row->supplier->name;
                    $row->supplier_short_name = $row->supplier->short_name;
                }

                if(in_array('source_type_name', $data['extra_columns'])){
                    $row->source_type_name = $row->source_type->name;
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

    public function resetQueryData($data)
    {
        // 轉成陣列
        if(!empty($data['with']) && is_string($data['with'])){
            $data['with'] = [$data['with']];
        }

        if(!empty($data['filter_keyword'])){
            $data['filter_name'] = $data['filter_keyword'];
            $data['filter_specification'] = $data['filter_keyword'];
            $data['filter_model'] = $data['filter_keyword'];
            unset($data['filter_keyword']);
        }

        return $data;
    }


    public function getProductSourceCodes()
    {
        if(!empty($this->source_type_codes)){
            return $this->source_type_codes;
        }

        $filter_data = [
            'equal_taxonomy_code' => 'product_source',
            'pagination' => false,
            'limit' => 0,
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
            'equal_taxonomy_code' => 'product_source',
            'equal_is_active' => 1,
            'pagination' => false,
            'limit' => 0,
            'sort' => 'code',
            'order' => 'ASC',
        ];

        $rows = $this->TermRepository->getRows($filter_data)->toArray();
        
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

    // 單筆記錄
    private function getRowExtraColumns($row, $columns)
    {
        if(in_array('usage_unit_code_name', $columns)){
            $row->usage_unit_code_name = $row->usage_unit->name ?? 'no name';
        }

        return $row;
    }

    // 多筆記錄
    private function getRowsExtraColumns($rows, $columns)
    {
        foreach ($rows as $row) {
            $row = $this->getRowExtraColumns($row, $columns);
        }
    }

    public function exportOrders($data = [], $debug = 0)
    {
        $filter_data = [];

        $filter_data['equal_is_stock_management'] = 1;
        $filter_data['limit'] = 0;
        $filter_data['pagination'] = false;
        $filter_data['sort'] = 'name';
        $filter_data['order'] = 'DESC';

        $filter_data['with'] = ['supplier.translation'];

        $products = $this->getProducts($filter_data);
        echo '<pre>', print_r($products, 1), "</pre>"; exit;
        foreach ($orders as $order) {
            $htmlData['orders'][] = $this->getOrderPrintData($order);
        }

        $htmlData['countOrders'] = count($htmlData['orders']);


        $view = view('admin.sale.print_order_form', $htmlData);
        $html = $view->render();

        $mpdf = new Mpdf([
            'fontDir' => public_path('fonts/'), // 字体文件路径
            'fontdata' => [
                'sourcehanserif' => [
                    'R' => 'SourceHanSerifTC-VF.ttf', // 思源宋体的.ttf文件路径
                    // 'B' => 'SourceHanSerif-Bold.ttf', // 如果需要加粗样式，可以配置这里
                    // 'I' => 'SourceHanSerif-Italic.ttf', // 如果需要斜体样式，可以配置这里
                ]
            ]
        ]);
        
        $mpdf->WriteHTML($html);
        $mpdf->Output('example.pdf', 'D');

        return Excel::download(new CommonExport($data), 'invoices.pdf', \Maatwebsite\Excel\Excel::MPDF);
    }
}

