<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Domains\ApiPosV2\Services\Sale\OrderService;

class OrderController extends ApiPosController
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
        $post_data = request()->post();

        $post_data['order_taker'] = auth()->user()->name;

        $result = $this->OrderService->store($post_data);

        $json = [];

        if(empty($result['error'])){
            $json = [
                'success' => true,
                'message' => '新增成功！',
                'data' => [
                    'id' => $result->id,
                    'code' => $result->code,
                ],
            ];
        }else{
            $json['error'] = $result['error'];
        }

        return $this->sendResponse($json);
    }

    public function update($order_id)
    {
        $result = $this->OrderService->update(request()->post(), $order_id);

        $json = [];

        if(empty($result['error'])){

            $json = [
                'success' => true,
                'message' => '更新成功！',
                'data' => [
                    'id' => $result->id,
                    'code' => $result->code,
                ],
            ];
        }else{
            $json['error'] = $result['error'];
        }

        return $this->sendResponse($json);
    }
}
