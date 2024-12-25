<?php

namespace App\Domains\ApiWwwV2\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Domains\ApiWwwV2\Http\Controllers\ApiWwwV2Controller;
use App\Domains\ApiWwwV2\Services\Catalog\ProductService;
use App\Helpers\Classes\DataHelper;

class ProductController extends ApiWwwV2Controller
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

        $filter_data['select'] = ['id', 'code', 'model', 'name'];

        $rows = $this->ProductService->getList($filter_data);

        $json = [];

        $json = DataHelper::getArrayDataByPaginatorOrCollection($rows);

        $json = DataHelper::unsetArrayIndexRecursively($json, ['translation', 'translations']);

        return $this->sendResponse($json);
    }
    
    public function info($product_id)
    {
        $product = $this->ProductService->getInfo($product_id);

        return $this->sendResponse(['data' => $product]);
    }
}
