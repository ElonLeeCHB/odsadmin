<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Domains\ApiPosV2\Services\Sale\OrderService;
use App\Helpers\Classes\DataHelper;
use App\Models\Sale\Order;

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
        try {
            if(!empty($this->url_data['simplelist'])){
                $orders = $this->OrderService->getSimplelist($this->url_data);
            }else{
                $orders = $this->OrderService->getList($this->url_data);
            }

            $orders = DataHelper::unsetArrayIndexRecursively($orders->toArray(), ['translation', 'translations']);
            
            return $this->sendJsonResponse(data:$orders);

        } catch (\Throwable $th) {
            return $this->sendJsonResponse(data:['error' => $th->getMessage()]);
        }
    }

    public function info($order_id)
    {
        try {
            $order = $this->OrderService->getInfo($order_id, 'id');

            return $this->sendJsonResponse(data:$order);
            
        } catch (\Throwable $th) {
            return $this->sendJsonResponse(data:['error' => $th->getMessage()]);
        }
    }

    public function infoByCode($code)
    {
        try {
            $order = $this->OrderService->getInfo($code, 'code');
    
            $order = DataHelper::unsetArrayIndexRecursively($order->toArray(), ['translation', 'translations']);

            return $this->sendJsonResponse(data:$order, status_code:200);
            
        } catch (\Throwable $th) {
            return $this->sendJsonResponse(data:['error' => $th->getMessage()]);
        }
    }

    public function store()
    {
        try {
            $post_data = request()->post();
    
            $post_data['order_taker'] = auth()->user()->name;
    
            $order = $this->OrderService->store($post_data);
    
            event(new \App\Events\OrderSavedAfterCommit(action:'insert', saved_order:$order));

            $data = [
                'id' => $order->id,
                'code' => $order->code,
            ];

            return $this->sendJsonResponse(data:$data, status_code:200, message:'訂單新增成功');

        } catch (\Throwable $th) {
            return $this->sendJsonResponse(data:['error' => $th->getMessage()]);
        }
    }

    public function update($order_id)
    {
        try {
            // old order
            $old_order = (new Order)->getOrderByIdOrCode($order_id, 'id');

            $order = $this->OrderService->update($this->post_data, $order_id);

            event(new \App\Events\OrderSavedAfterCommit(action:'update', saved_order:$order, old_order:$old_order));

            $data = [
                'id' => $order->id,
                'code' => $order->code,
            ];
    
            return $this->sendJsonResponse(data:$data, status_code:200, message:'訂單更新成功');

        } catch (\Throwable $th) {
            return $this->sendJsonResponse(data:['error' => $th->getMessage()]);
        }
    }
}
