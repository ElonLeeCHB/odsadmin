<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Domains\ApiPosV2\Services\Sale\OrderService;
use App\Helpers\Classes\DataHelper;

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
            $orders = $this->OrderService->getList($this->url_data);

            $orders = DataHelper::unsetArrayIndexRecursively($orders->toArray(), ['translation', 'translations']);
            
            return $this->sendJsonResponse(data:$orders);

        } catch (\Throwable $th) {
            return $this->sendJsonResponse(data:['error' => $th->getMessage()]);
        }
    }

    public function info($order_id)
    {
        try {
            $order = $this->OrderService->getOrderByIdOrCode($order_id, 'id');

            return $this->sendJsonResponse(data:$order);
            
        } catch (\Throwable $th) {
            return $this->sendJsonResponse(data:['error' => $th->getMessage()]);
        }
    }

    public function infoByCode($code)
    {
        try {
            $order = $this->OrderService->getOrderByIdOrCode($code, 'code');
    
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
    
            $order = $this->OrderService->save($this->post_data);

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
            $order = $this->OrderService->save($this->post_data, $order_id);

            $data = [
                'id' => $order->id,
                'code' => $order->code,
            ];
    
            return $this->sendJsonResponse(data:$data, status_code:200, message:'訂單更新成功');

        } catch (\Throwable $th) {
            return $this->sendJsonResponse(data:['error' => $th->getMessage()]);
        }
    }

    public function updateHeader($order_id)
    {
        try {
            // old order
            if (!empty($order_id)){
                $old_order = $this->OrderService->getOrderByIdOrCode($order_id, 'id');
                $old_order_id = $order_id;
            }

            //驗證內容
            $json = [];

            if(empty($this->post_data['status_code'])){
                $json['errors']['status_code'] = '請設定訂單狀態';
            }

            if(empty($this->post_data['delivery_date'])){
                $json['errors']['delivery_date'] = '請設定送達日期';
            }

            if(!isset($this->post_data['is_payment_tin_required'])){
                $json['errors']['is_payment_tin_required'] = '請選擇是否需要統編';
            }

            if(!empty($this->post_data['is_payment_tin_required']) && empty($this->post_data['payment_tin'])){
                $json['errors']['payment_tin'] = '尚未輸入統編';
            }

            if(empty($json)){
                $order = $this->OrderService->updateHeader($order_id, $this->post_data);

                if(empty($old_order_id) && !empty($order)){
                    event(new \App\Events\SaleOrderSavedEvent(saved_order:$order));

                    $message = '訂單新增成功';

                } else if(!empty($old_order_id) && !empty($old_order)){
                    event(new \App\Events\SaleOrderSavedEvent(saved_order:$order, old_order:$old_order));
                    
                    $message = '訂單修改成功';
                }

                $data = [
                    'id' => $order->id,
                    'code' => $order->code,
                    'customer_id' => $order->customer_id,
                ];
    
                return $this->sendJsonResponse(data:$data, status_code:200, message:$message);

            }
            else {
                $json['error'] = '請檢查輸入內容';
                return $this->sendJsonResponse(data:['error' => $json['error']]);
            }

        } catch (\Throwable $th) {
            return $this->sendJsonResponse(data:['error' => $th->getMessage()]);
        }
    }

    public function orderTagsList()
    {
        $data = $this->OrderService->getOrderTagsList();

        return $this->sendJsonResponse(data:$data, status_code:200);
    }

}
