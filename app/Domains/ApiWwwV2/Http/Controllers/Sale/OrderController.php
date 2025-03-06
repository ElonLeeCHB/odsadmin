<?php

namespace App\Domains\ApiWwwV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiWwwV2\Http\Controllers\ApiWwwV2Controller;
use App\Domains\ApiWwwV2\Services\Sale\OrderService;
use Illuminate\Support\Facades\DB;

class OrderController extends ApiWwwV2Controller
{
    private $status_code = 200;
    private $default_error_status_code = 400;

    public function __construct(private Request $request,private OrderService $OrderService)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }


    public function list()
    {
        try {
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
            if ($filled_count < 2) {
                return response()->json([
                    'error' => '至少填寫兩個欄位: equal_code, equal_personal_name, equal_mobile'
                ], 400);
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
    
            $result = $this->OrderService->getList($queries);

            if(empty($result['error'])){
                $json = [
                    'success' => 'ok',
                    'data' => $result,
                ];
            }else{
                $json = [
                    'error' => $result['error'],
                ];

                $this->status_code = 400;

                $this->logError($result['error']);
            }

            return response()->json($json, $this->status_code);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], $th->getCode());
        }
    }

    public function infoByCode($order_code)
    {
        try {
            if(empty(request()->query('personal_name'))){
                throw new \Exception('姓名錯誤', 401);
            }
    
            $filter_data = [
                'equal_personal_name' => request()->query('personal_name'),
                'equal_code' => $order_code,
                'first' => true,
            ];
    
            $result = $this->OrderService->getInfo($filter_data, 'code');
    
            if(empty($result['error'])){
                $json = [
                    'success' => 'ok',
                    'data' => $result,
                ];
            }else{
                $json = [
                    'error' => $result['error'],
                ];

                $this->status_code = 400;

                $this->logError($result['error']);
            }

            return response()->json($json, $this->status_code);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], $th->getCode());
        }
    }

    public function store()
    {
        try {
            $data = request()->all();

            $data['order_taker'] = 'web';
    
            $result = $this->OrderService->store($data);
    
            if(empty($result['error'])){
                $json = [
                    'success' => 'ok',
                    'message' => '新增成功！',
                    'data' => $result,
                ];

            }else{
                $json = [
                    'error' => $result['error'],
                    'message' => '新增失敗！',
                ];

                $this->status_code = 400;

                $this->logError($result['error']);
            }
            
            return response()->json($json, $this->status_code);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], $th->getCode());
        }
    }


    public function deliveries()
    {
        try {
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
                
            $result = $builder->get();

            if(empty($result['error'])){
                $json = [
                    'success' => 'ok',
                    'data' => $result,
                ];

            }else{
                $json = [
                    'error' => $result['error'],
                ];

                $this->status_code = 400;

                $this->logError($result['error']);
            }

            return response()->json($json, $this->status_code);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], $th->getCode());
        }
    }

}
