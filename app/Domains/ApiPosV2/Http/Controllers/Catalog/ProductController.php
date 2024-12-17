<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Domains\ApiPosV2\Services\Catalog\ProductService;
use App\Helpers\Classes\DataHelper;

class ProductController extends ApiPosController
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

        $products = DataHelper::removeFromArray($products->toArray(), ['translation', 'translations']);

        return response(json_encode($products))->header('Content-Type','application/json');
    }


    public function info($product_id)
    {
        $product = $this->ProductService->getInfo($product_id);

        return response(json_encode($product))->header('Content-Type','application/json');
    }
}
