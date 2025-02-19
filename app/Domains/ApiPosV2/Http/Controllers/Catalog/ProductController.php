<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Catalog;

use Illuminate\Http\Request;
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
        $filter_data = $this->url_data;

        $filter_data['pagination'] = false;

        $products = $this->ProductService->getList($filter_data);

        return $this->sendResponse(['data' => $products]);
    }

    public function info($product_id)
    {
        $info = $this->ProductService->getInfo($product_id);

        return $this->sendResponse(['data' => $info]);
    }
}
