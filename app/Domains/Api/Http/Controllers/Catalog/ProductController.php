<?php

namespace App\Domains\Api\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Domains\Api\Services\Common\OptionService;
use App\Domains\Api\Services\Catalog\ProductService;
use App\Domains\Api\Services\Catalog\CategoryService;

class ProductController extends Controller
{
    private $lang;

    public function __construct(
        private Request $request
        , private ProductService $ProductService
        , private CategoryService $CategoryService
        , private OptionService $OptionService
    )
    {
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/catalog/product']);
    }

    /**
     * Show the list table.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function list()
    {
        $data['lang'] = $this->lang;

        // Prepare link for action
        $queries = [];

        if(!empty($this->request->query('page'))){
            $page = $queries['page'] = $this->request->input('page');
        }else{
            $page = $queries['page'] = 1;
        }

        if(!empty($this->request->query('sort'))){
            $sort = $queries['sort'] = $this->request->input('sort');
        }else{
            $sort = $queries['sort'] = 'id';
        }

        if(!empty($this->request->query('order'))){
            $order = $queries['order'] = $this->request->query('order');
        }else{
            $order = $queries['order'] = 'asc';
        }

        if($this->request->has('pagination')){
           $queries['pagination'] = $this->request->query('pagination');
        }else{
            $queries['pagination'] = true;
        }

        if(!empty($this->request->query('limit'))){
            $limit = $queries['limit'] = $this->request->query('limit');
        }

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }

        // Rows
        $products = $this->ProductService->getProducts($queries);

        if(!empty($products)){
            foreach ($products as $row) {
                //$row->edit_url = route('api.catalog.products.form', array_merge([$row->id], $queries));
                if(!empty($this->request->query('simplelist'))){
                    $simplelist[] = [
                        'product_id' => $row->id,
                        'product_name' => $row->name,
                    ];
                    continue;
                }
            }
        }

        if(!empty($simplelist)){
            $products = $simplelist;
        }

        return response(json_encode($products))->header('Content-Type','application/json');
    }


    public function details($product_id)
    {
        $queries = [
            'filter_id' => $product_id,
            'regexp' => false,
        ];
        $product = $this->ProductService->first($queries);

        $product->load('product_options');
        $product->load('product_options.product_option_values');

        $product_options = $product->product_options->sortBy('sort_order')->keyBy('option_code')->toArray();

        $product = $product->toArray();
        unset($product['translation']);

        $product['product_options'] = $product_options;

        
        //$product->product_options = $product->product_options->keyBy('option_code')->toArray();

        return response(json_encode($product))->header('Content-Type','application/json');
    }


    public function options($product_id)
    {
        $queries = [
            'filter_id' => $product_id,
            'with' => ['product_options.translation', 'product_options.product_option_values.translation'],
        ];

        $product = $this->ProductService->getRecord($queries);
        $product_options = $product->product_options;

        foreach ($product_options as $key => $product_option) {
            $product_option->name = $product_option->translation->name;
        }

        return response(json_encode($product_options))->header('Content-Type','application/json');

    }
}
