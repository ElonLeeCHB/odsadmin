<?php

namespace App\Repositories\Eloquent\Catalog;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use App\Models\Catalog\ProductBom;
use App\Models\Catalog\ProductTranslation;
use App\Models\Common\TermRelation;

class ProductRepository extends Repository
{
    public $modelName = "\App\Models\Catalog\Product";


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
}

