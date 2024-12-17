<?php

namespace App\Domains\ApiWwwV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiWwwV2\Http\Controllers\ApiWwwController;
use App\Domains\ApiWwwV2\Services\Sale\OrderService;

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

    public function store()
    {
        try {
        
            $result = $this->OrderService->store(request()->post());
    
            $json = [
                'success' => true,
                'message' => '更新成功！',
                'data' => [
                    'id' => $result['data']['id'],
                    'code' => $result['data']['code'],
                ],
            ];
    
            return response(json_encode($json))->header('Content-Type','application/json');

        } catch (\Throwable $th) {
            $json = [
                'error' => $th->getMessage(),
            ];
            return response(json_encode($json))->header('Content-Type','application/json');
        }


    }

}
