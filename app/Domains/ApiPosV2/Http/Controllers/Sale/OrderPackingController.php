<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use Illuminate\Support\Facades\Validator;
use App\Models\Setting\Setting;
use App\Models\Sale\Datelimit;
use App\Models\Sale\TimeSlotLimit;
use App\Domains\ApiPosV2\Services\Sale\OrderPackingService;

class OrderPackingController extends ApiPosController
{
    public function __construct(private Request $request,private OrderPackingService $OrderPackingService)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }

    // 某日訂單列表
    public function list($delivery_data = '')
    {
        if (empty($delivery_data)){
            $delivery_data = date('Y-m-d');
        }

        $result = $this->OrderPackingService->getListByDeliveryDate($delivery_data);

        return $this->sendJsonResponse($result);
    }

    public function update($order_id)
    {
        $result = $this->OrderPackingService->update($order_id, $this->all_data);

        return $this->sendJsonResponse($result);
    }

    public function statuses()
    {
        $result = $this->OrderPackingService->statuses();

        return $this->sendJsonResponse($result);
    }
}