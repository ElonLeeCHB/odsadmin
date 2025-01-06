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

        $filter_data['select'] = ['id', 'code', 'name', 'price'];

        $filter_data['equal_is_on_web'] = 1;

        $rows = $this->ProductService->getList($filter_data);

        foreach ($rows as $row) {
            $row->web_name = $row->translation->web_name;
        }

        $json = [];

        $json = DataHelper::unsetArrayIndexRecursively($rows->toArray(), ['translation', 'translations']);

        return $this->sendResponse($json);
    }
    
    public function info($product_id)
    {
        $filter_data = $this->url_data;

        
        $filter_data['equal_id'] = $product_id;
        $filter_data['select'] = ['id', 'code', 'name', 'price'];
        $filter_data['with'] = ['product_options.translation',
                                'product_options.product_option_values.translation'];

        $product = $this->ProductService->getInfo($filter_data);

        if(empty($product)){
            $json['error'] = '找不到商品';
        }else{
            $json['data'] = $product;
        }
        return $this->sendResponse($json);
    }
}
