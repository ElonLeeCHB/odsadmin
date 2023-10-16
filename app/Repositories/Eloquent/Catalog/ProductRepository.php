<?php

namespace App\Repositories\Eloquent\Catalog;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use App\Models\Catalog\ProductBom;
use App\Models\Catalog\ProductTranslation;
use App\Models\Common\TermRelation;

class ProductRepository extends Repository
{
    public $modelName = "\App\Models\Catalog\Product";

    private $source_codes;


    public function __construct(protected TermRepository $TermRepository)
    {
        parent::__construct();
    }


    public function getProducts($data = [], $debug = 0)
    {
        $data = $this->resetQueryData($data);


        // Sort && Order
        if(isset($data['sort']) && $data['sort'] == 'name'){
            unset($data['sort']);

            if(!isset($data['order'])){
                $data['order'] = 'ASC';
            }
            
            $locale = app()->getLocale();

            $data['orderByRaw'] = "(SELECT name FROM product_translations WHERE locale='".$locale."' and product_translations.product_id = products.id) " . $data['order'];
        }

        $products = $this->getRows($data);

        $source_codes = $this->getProductSourceCodes();

        foreach ($products as $row) {
            if(!empty($row->status_id)){
                $row->source_code_name = $source_codes[$row->source_code]['name'];
                $row->supplier_name = $row->supplier->name ?? '';
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
        if(!empty($data['filter_keyword'])){
            $data['filter_name'] = $data['filter_keyword'];
            $data['filter_specification'] = $data['filter_keyword'];
            $data['filter_model'] = $data['filter_keyword'];
            unset($data['filter_keyword']);
        }

        if(!empty($data['filter_name'])){
            $data['whereHas']['translation'] = ['name' => $data['filter_name']];
        }

        if(!empty($data['filter_specification'])){
            $data['whereHas']['translation'] = ['specification' => $data['filter_specification']];
        }

        return $data;
    }


    public function getProductSourceCodes()
    {
        if(!empty($this->source_codes)){
            return $this->source_codes;
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


}

