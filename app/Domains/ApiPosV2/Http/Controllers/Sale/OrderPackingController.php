<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use Illuminate\Support\Facades\Validator;
use App\Models\Setting\Setting;
use App\Models\Sale\Datelimit;
use App\Models\Sale\TimeSlotLimit;
use App\Domains\ApiPosV2\Services\Sale\OrderPackingService;
use Carbon\Carbon;

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
        try {

            $json = [];

            // 驗證表單
            // if (env('APP_ENV') == 'production') {
            //     if (Carbon::parse($data['shipping_date'])->lessThan(Carbon::yesterday())) {
            //         throw new \Exception('只能修改當天的記錄。');
            //     }
            // }
            //

            if(!$json){
                $result = $this->OrderPackingService->save($this->all_data, $order_id);

                if ($result){
                    return response()->json(['success' => true]);
                }
            }

            return $this->sendJsonResponse($json, 400);


        } catch (\Throwable $th) {
            //throw $th;
            return $this->sendJsonResponse(['error' => $th->getMessage()], 500);
        }

    }

    public function statuses()
    {
        $result = $this->OrderPackingService->getStatuses();

        return $this->sendJsonResponse($result);
    }
}