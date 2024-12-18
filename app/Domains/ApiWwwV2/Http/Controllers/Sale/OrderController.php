<?php

namespace App\Domains\ApiWwwV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiWwwV2\Http\Controllers\ApiWwwV2Controller;
use App\Domains\ApiWwwV2\Services\Sale\OrderService;

class OrderController extends ApiWwwV2Controller
{
    public function __construct(private Request $request,private OrderService $OrderService)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }


    public function list()
    {
        $response = $this->OrderService->getList($this->url_data);

        return $this->sendResponse($response);
    }

    public function info($order_id)
    {
        $response = $this->OrderService->getInfo($order_id, 'id');

        return response(json_encode($response))->header('Content-Type','application/json');
    }

    public function infoByCode($code)
    {
        $order = $this->OrderService->getInfo($code, 'code');

        return response(json_encode($order))->header('Content-Type','application/json');
    }

    public function store()
    {
        try {
            $result = $this->OrderService->storeOrder(request()->post());
    
            $json = [
                'success' => true,
                'message' => '新增成功！',
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

    public function edit($order_id)
    {
        try {
            if(!empty(request()->post('order_id')) && $order_id !== request()->post('order_id')){
                throw new \Exception('訂單序號錯誤！');
            }

            $result = $this->OrderService->editOrder(request()->post(), $order_id);
    
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
