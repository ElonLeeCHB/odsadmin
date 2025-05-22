<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use Illuminate\Support\Facades\Validator;
use App\Models\Setting\Setting;
use App\Models\Sale\Datelimit;
use App\Models\Sale\TimeSlotLimit;
use App\Domains\ApiPosV2\Services\Sale\QuantityControlService;

class QuantityControlController extends ApiPosController
{
    public function __construct(private Request $request,private QuantityControlService $QuantityControlService)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }

    // 預設時間段數量-獲取
    public function getTimeslots()
    {
        try {

            $result = $this->QuantityControlService->getTimeslots();

            return $this->sendJsonResponse($result);

        } catch (\Throwable $th) {
            return $this->sendJsonResponse(['error' => $th->getMessage()]);
        }
    }
    
    // 預設時間段數量-更新
    public function updateTimeslots()
    {
        $result = $this->QuantityControlService->updateTimeslots(request()->post());

        return $this->sendJsonResponse($result);
    }

    // 某日數量資料-獲取
    public function getOrderDateLimitsByDate($date)
    {
        $result = $this->QuantityControlService->getOrderDateLimitsByDate($date);

        return $this->sendJsonResponse($result);
    }

    // 某日數量資料-更新上限 注意傳入的網址參數是 $date, 不是 $data
    public function updateMaxQuantityByDate($date)
    {
        $result = $this->QuantityControlService->updateMaxQuantityByDate($date, request()->post());

        return $this->sendJsonResponse($result);
    }

    // 某日數量資料-恢復預設上限
    public function resetDefaultMaxQuantityByDate($date)
    {
        $result = $this->QuantityControlService->resetDefaultMaxQuantityByDate($date);

        return $this->sendJsonResponse($result);
    }

    // 某日數量資料-重算訂單
    public function refreshOrderedQuantityByDate($date)
    {
        $result = $this->QuantityControlService->refreshOrderedQuantityByDate($date);

        return $this->sendJsonResponse($result);
    }

    // 未來資料
    public function getFutureDays($days = 30)
    {
        $days = min($days, 60);

        $result = $this->QuantityControlService->getFutureDays($days);

        return $this->sendJsonResponse($result);
    }

    // 重算全部未來訂單
    public function resetFutureOrders()
    {
        $result = $this->QuantityControlService->resetFutureOrders();

        return $this->sendJsonResponse($result);
    }

    // 某日訂單列表
    public function orderList($delivery_date)
    {
        $result = $this->QuantityControlService->orderList($delivery_date);

        return $this->sendJsonResponse($result);
    }

    public function quickSaveOrder()
    {
        try {

            if (empty($this->post_data['order_id'])){
                $json['errors']['order_id'] = '訂單ID錯誤';
            }

            if(empty($json)){
                $order = $this->QuantityControlService->quickSaveOrder($this->post_data);

                $json = [
                    'id' => $order->id,
                    'code' => $order->code,
                    'comment' => $order->comment,
                ];
    
                return $this->sendJsonResponse(data:$json, status_code:200, message:'更新成功');
            }
        } catch (\Throwable $th) {
            return $this->sendJsonResponse(data:['error' => $th->getMessage()]);
        }
    }
}