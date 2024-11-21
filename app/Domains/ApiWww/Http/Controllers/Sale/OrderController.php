<?php

namespace App\Domains\ApiWww\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiWww\Http\Controllers\ApiWwwController;
use App\Domains\ApiWww\Services\Sale\OrderService;

class OrderController extends ApiWwwController
{
    public function __construct(private Request $request,private OrderService $OrderService)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }


    public function list()
    {
        if(!empty($this->url_data['simplelist'])){
            $orders = $this->OrderService->getSimplelist($this->url_data);
        }else{
            $orders = $this->OrderService->getList($this->url_data);
        }

        return response(json_encode($orders))->header('Content-Type','application/json');
    }

    public function info($order_id)
    {
        $order = $this->OrderService->getInfo($order_id, 'id');

        return response(json_encode($order))->header('Content-Type','application/json');
    }

    public function infoByCode($code)
    {
        $order = $this->OrderService->getInfo($code, 'code');

        return response(json_encode($order))->header('Content-Type','application/json');
    }

}
