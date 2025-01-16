<?php

namespace App\Domains\ApiWwwV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiWwwV2\Http\Controllers\ApiWwwV2Controller;
use App\Domains\ApiWwwV2\Services\Sale\OrderService;
use Illuminate\Support\Facades\Validator;

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
        $response = [];

        $queries = request()->all();

        $allowed_query_keys = ['equal_code', 'equal_personal_name', 'equal_mobile', ];

        // 計算有填寫的欄位數
        $filled_count = 0;
        foreach ($allowed_query_keys as $key) {
            if (!empty($queries[$key])) {
                $filled_count++;
            }
        }

        // 檢查是否有至少兩個欄位被填寫
        if ($filled_count > 1) {
            return response()->json([
                'error' => '至少填寫兩個欄位: equal_code, equal_personal_name, equal_mobile'
            ], 400);  // 400 表示錯誤的請求
        }

        foreach ($queries as $key => $value) {
            if(empty($queries[$key])){
                unset($queries[$key]);
            }

            // equal_, 僅保留指定的三個精確欄位
            if(str_starts_with($key, 'equal_') && !in_array($key, $allowed_query_keys)){
                unset($queries[$key]);
            }
            // filter_, 刪除所有模糊欄位
            if(str_starts_with($key, 'filter_')){
                unset($queries[$key]);
            }
        }


        if(empty($response['error']))
        {
            
            $response = $this->OrderService->getList($queries);
        }

        return $this->sendResponse($response);
    }

    public function infoByCode($order_code)
    {

        $filter_data = [
            'equal_code' => $order_code,
            'first' => true,
        ];

        $row = $this->OrderService->getInfoByCode($filter_data);

        return $this->sendResponse(['data' => $row]);
    }

    public function store()
    {
        try {
            $result = $this->OrderService->store(request()->post());
    
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
