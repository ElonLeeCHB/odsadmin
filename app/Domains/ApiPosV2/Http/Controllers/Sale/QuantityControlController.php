<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use Illuminate\Support\Facades\Validator;
use App\Models\Setting\Setting;
use App\Models\Sale\Datelimit;
use App\Models\Sale\TimeSlotLimit;
use App\Services\Sales\QuantityControlService;

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
        $result = $this->QuantityControlService->getTimeslots();

        return $this->sendResponse($result);
    }
    
    // 預設時間段數量-更新
    public function updateTimeslots()
    {
        $result = $this->QuantityControlService->updateTimeslots(request()->post());

        return $this->sendResponse($result);
    }

    // 某日數量資料-獲取
    public function getOrderDateLimitsByDate($date)
    {
        $result = $this->QuantityControlService->getOrderDateLimitsByDate($date);

        return $this->sendResponse($result);
    }

    // 某日數量資料-更新上限
    public function updateMaxQuantityByDate($date)
    {
        $data['Date'] = $date;
        $data['TimeSlots'] = request()->post();
        $result = $this->QuantityControlService->updateMaxQuantityByDate($data);

        return $this->sendResponse($result);
    }

    // 某日數量資料-恢復預設上限
    public function resetDefaultMaxQuantityByDate($date)
    {
        $result = $this->QuantityControlService->resetDefaultMaxQuantityByDate($date);

        return $this->sendResponse($result);
    }

    // 某日數量資料-重算訂單
    public function refreshOrderedQuantityByDate($date)
    {
        $result = $this->QuantityControlService->refreshOrderedQuantityByDate($date);

        return $this->sendResponse($result);
    }

    // 未來資料
    public function getFutureDays($days = 30)
    {
        $days = min($days, 60);

        $result = $this->QuantityControlService->getFutureDays($days);

        return $this->sendResponse($result);
    }

    

    


    
}