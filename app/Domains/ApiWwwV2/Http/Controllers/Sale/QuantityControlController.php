<?php

namespace App\Domains\ApiWwwV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiWwwV2\Http\Controllers\ApiWwwV2Controller;
use App\Repositories\Eloquent\Sale\OrderDateLimitRepository;

class QuantityControlController extends ApiWwwV2Controller
{
    public function __construct(private Request $request,private OrderDateLimitRepository $OrderDateLimitRepository)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }

    // 未來資料
    public function getFutureDays($days = 30)
    {
        $days = min($days, 60);

        $rows =  (new OrderDateLimitRepository)->getFutureDays($days);

        $start_hour = 10;

        foreach ($rows as $date => $time_slots) {
            foreach ($time_slots as $time_slot_key => $row) {
                $cur_start_hour = substr($time_slot_key,0,2);
                if($cur_start_hour < $start_hour){
                    unset($rows[$date][$time_slot_key]);
                }
            }
        }

        return $this->sendJsonResponse($rows);
    }
}