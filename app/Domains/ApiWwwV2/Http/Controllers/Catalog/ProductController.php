<?php

namespace App\Domains\ApiWwwV2\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Domains\ApiWwwV2\Http\Controllers\ApiWwwV2Controller;
use App\Domains\ApiWwwV2\Services\Catalog\ProductService;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\OrmHelper;

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
        try {
            $filter_data = $this->url_data;
    
            $filter_data['select'] = ['id', 'code', 'name', 'price'];
    
            $filter_data['equal_is_on_web'] = 1;
    
            $rows = $this->ProductService->getList($filter_data);

            foreach ($rows as $row) {
                $row->web_name = $row->translation->web_name;
            }
    
            $json = [];
    
            $json = DataHelper::unsetArrayIndexRecursively($rows->toArray(), ['translation', 'translations']);
    
            return $this->sendJsonResponse($json);

        } catch (\Throwable $th) {
            return $this->sendJsonResponse(['error' => $th->getMessage()]);
        }
    }
    
    public function info($product_id = null)
    {
        try {
            $result = $this->ProductService->getProductById($product_id,$this->url_data);

            return $this->sendJsonResponse($result);

        } catch (\Throwable $th) {
            return $this->sendJsonResponse(['error' => $th->getMessage()]);
        }
    }
}
