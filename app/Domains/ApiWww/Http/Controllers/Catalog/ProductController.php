<?php

namespace App\Domains\ApiWww\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\ApiWww\Http\Controllers\ApiWwwController;
use App\Domains\ApiWww\Services\Catalog\ProductService;
use App\Helpers\Classes\DataHelper;

class ProductController extends ApiWwwController
{
    protected $lang;

    public function __construct(private Request $request, private ProductService $ProductService)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }


    public function list()
    {
        if(!empty($this->url_data['simplelist'])){
            $products = $this->ProductService->getSimplelist($this->url_data);
        }else{
            $products = $this->ProductService->getList($this->url_data);
        }

        return response(json_encode($products))->header('Content-Type','application/json');
    }


    public function info($product_id)
    {
        $product = $this->ProductService->getInfo($product_id);

        return response(json_encode($product))->header('Content-Type','application/json');
    }
}
