<?php

namespace App\Domains\Api\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Api\Http\Controllers\ApiController;
use App\Libraries\TranslationLibrary;
use App\Domains\Api\Services\Catalog\OptionService;
use App\Domains\Api\Services\Catalog\ProductService;
use App\Domains\Api\Services\Catalog\CategoryService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductController extends ApiController
{
    protected $lang;

    public function __construct(
        private Request $request
        , private ProductService $ProductService
        , private CategoryService $CategoryService
        , private OptionService $OptionService
    )
    {
        parent::__construct();
    }

    /**
     * Show the list table.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function list()
    {
        $query_data = $this->request->query();

        $filter_data = $this->getQueries($query_data);

        $products = $this->ProductService->getProducts($filter_data);

        if(!empty($products) && !empty($data['simplelist'])){
            foreach ($products as $row) {
                $simplelist[] = [
                    'product_id' => $row->id,
                    'product_name' => $row->name,
                ];
            }
        }

        if(!empty($simplelist)){
            $products = $simplelist;
        }else{
            $products = $this->ProductService->unsetRelations($products, ['translation']);
        }

        return response(json_encode($products))->header('Content-Type','application/json');
    }


    public function details($product_id)
    {
        $queries = [
            'equal_id' => $product_id,
        ];
        $product = $this->ProductService->getRow($queries);

        $product->load('product_options');
        $product->load('product_options.product_option_values');

        $product_options = $product->product_options->sortBy('sort_order')->keyBy('option_code')->toArray();
        $product = $product->toArray();
        unset($product['translation']);

        $product['product_options'] = $product_options;

        return response(json_encode($product))->header('Content-Type','application/json');
    }


    public function options($product_id)
    {
        $queries = [
            'equal_product_id' => $product_id,
            'with' => ['product_options.translation', 'product_options.product_option_values.translation'],
        ];

        $product = $this->ProductService->getProduct($queries);
        $product_options = $product->product_options;

        foreach ($product_options as $key => $product_option) {
            $product_option->name = $product_option->translation->name;
        }

        return response(json_encode($product_options))->header('Content-Type','application/json');

    }
}
