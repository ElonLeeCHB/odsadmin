<?php

namespace App\Domains\ApiWwwV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiWwwV2\Http\Controllers\ApiWwwV2Controller;
use App\Domains\ApiWwwV2\Services\Sale\DeliveryService;
use App\Helpers\Classes\DataHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\Order;
use App\Models\Sale\OrderDelivery;

class OrderDeliveryController extends ApiWwwV2Controller
{

    public function list()
    {
        try {
            $request_data = request()->all();
    
            $allowed_query_keys = ['equal_code', 'equal_personal_name', 'equal_mobile', ];
    
            // 計算有填寫的欄位數
            $filled_count = 0;
            foreach ($allowed_query_keys as $key) {
                if (!empty($request_data[$key])) {
                    $filled_count++;
                }
            }

            // 檢查是否有至少兩個欄位被填寫
            if ($filled_count < 2) {
                return response()->json([
                    'error' => '至少填寫兩個欄位: equal_code, equal_personal_name, equal_mobile'
                ], 400);
            }
    
            foreach ($request_data as $key => $value) {
                if(empty($request_data[$key])){
                    unset($request_data[$key]);
                }
    
                // equal_, 僅保留指定的三個精確欄位
                if(str_starts_with($key, 'equal_') && !in_array($key, $allowed_query_keys)){
                    unset($request_data[$key]);
                }
                // filter_, 刪除所有模糊欄位
                if(str_starts_with($key, 'filter_')){
                    unset($request_data[$key]);
                }
            }
    
            $order = Order::select(['id','code'])->applyFilters($request_data)->with('deliveries')->first();
    
            $deliveries = [];
    
            if($order->deliveries){
                $deliveries = $order->deliveries;
            }
    
            return $this->sendJsonResponse($deliveries);

        } catch (\Throwable $th) {
            return $this->sendJsonResponse(['error' => $th->getMessage()], $th->getCode());
        }
        
    }


    public function deliveries()
    {
        $code = request()->query('equal_order_code');
        $personal_name = request()->query('equal_personal_name');

        if(empty($code)){
            return response()->json(['error' => '請提供訂單編號',], 400);
        }

        if(empty($personal_name)){
            return response()->json(['error' => '請提供姓名',], 400);
        }

        $builder = DB::table('order_delivery as od')
            ->select('od.*')
            ->leftJoin('orders as o', 'o.code', '=', 'od.order_code')
            ->where('o.code', $code);
            
        $rows = $builder->get();

        return response()->json($rows, 200);
    }

}
