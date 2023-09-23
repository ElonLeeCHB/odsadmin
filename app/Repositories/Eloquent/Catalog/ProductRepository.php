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

    public function getProducts($data = [], $debug = 0)
    {
        $data = $this->resetQueryData($data);

        $products = $this->getRows($data, $debug);

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
            $data['filter_short_name'] = $data['filter_keyword'];
            $data['filter_description'] = $data['filter_keyword'];
            unset($data['filter_keyword']);
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
        $collection = (new TermRepository)->getRows($filter_data)->toArray();

        $result = [];

        foreach ($collection as $key => $row) {
            unset($row['translation']);
            unset($row['taxonomy']);
            $code = $row['code'];
            $result[$code] = (object) $row;
        }

        return $result;
    }
}

