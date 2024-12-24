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
        $queries = request()->post('queries');
        $response = [];

        $validator = Validator::make(request()->post(), [
            'personal_name' => 'nullable|string',
            'mobile' => 'nullable|string',
            'code' => 'nullable|string',
        ]);

        // 在驗證後檢查至少有兩個欄位有填寫
        $validator->after(function ($validator) use ($queries) {
            $neededFields = array_keys($queries);

            // 計算有填寫的欄位數
            $coount = 0;
            foreach ($neededFields as $field) {
                if (!empty($queries[$field])) {
                    $coount++;
                }
            }

            // 如果填寫的欄位少於兩個，則返回錯誤
            if ($coount < 2) {
                $validator->errors()->add('at_least_two_fields', '至少填寫兩個欄位');
            }
        });

        // 如果驗證失敗，返回錯誤訊息
        if ($validator->fails()) {
            $response['error'] = $validator->errors()->first();
        }

        if(empty($response['error'])){

            $filter_data = [];

            if(!empty($queries['personal_name'])){
                $filter_data['filter_personal_name'] = $queries['personal_name'];
            }
            
            if(!empty($queries['mobile'])){
                $filter_data['filter_mobile'] = $queries['mobile'];
            }
            
            if(!empty($queries['code'])){
                $filter_data['filter_code'] = $queries['code'];
            }
            
            $response = $this->OrderService->getList($filter_data);
        }

        return $this->sendResponse($response);
    }

    public function infoByCode($order_code)
    {
        $filter_data = [
            'equal_code' => $order_code,
            'first' => true,
        ];
        $response = $this->OrderService->getInfoByCode($filter_data);

        return response(json_encode($response))->header('Content-Type','application/json');
    }

    public function store()
    {
        try {
            $result = $this->OrderService->storeOrder(request()->post());
    
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
