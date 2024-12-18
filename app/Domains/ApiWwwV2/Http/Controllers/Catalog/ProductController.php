<?php

namespace App\Domains\ApiWwwV2\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Domains\ApiWwwV2\Http\Controllers\ApiWwwV2Controller;
use App\Domains\ApiWwwV2\Services\Catalog\ProductService;

class ProductController extends ApiWwwV2Controller
{
    protected $lang;

    public function __construct(private Request $request, private ProductService $ProductService)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }


    public function info($product_id)
    {
        $product = $this->ProductService->getInfo($product_id);

        return $this->sendResponse(['data' => $product]);
    }
}
