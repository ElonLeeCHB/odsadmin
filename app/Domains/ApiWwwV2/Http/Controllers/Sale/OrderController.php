<?php

namespace App\Domains\ApiWwwV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiWwwV2\Http\Controllers\ApiWwwV2Controller;
use App\Domains\ApiWwwV2\Services\Sale\OrderService;
use Illuminate\Support\Facades\DB;
use App\Helpers\Classes\DataHelper;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $queries = request()->all();

        $allowed_query_keys = ['equal_code', 'equal_personal_name', 'equal_mobile',];

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
            if (empty($queries[$key])) {
                unset($queries[$key]);
            }

            // equal_, 僅保留指定的三個精確欄位
            if (str_starts_with($key, 'equal_') && !in_array($key, $allowed_query_keys)) {
                unset($queries[$key]);
            }
            // filter_, 刪除所有模糊欄位
            if (str_starts_with($key, 'filter_')) {
                unset($queries[$key]);
            }
        }

        $listData = $this->OrderService->getList($queries);

        return response()->json(['success' => true, 'message' => '訂單新增成功', 'data' => $listData,], 200);
    }

    public function infoByCode($order_code)
    {
        if (empty(request()->query('equal_personal_name'))) {
            throw new \Exception('姓名錯誤', 404);
        }

        $filter_data = [
            'equal_code' => $order_code,
            'first' => true,
        ];

        $order = $this->OrderService->getInfo($filter_data, 'code');

        if ($order->personal_name !== request()->query('equal_personal_name')) {
            throw new \Exception('姓名錯誤', 404);
        }

        return response()->json(['success' => true, 'message' => '訂單新增成功', 'data' => $order,], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mobile' => 'required|string',
        ], [
            'mobile.required' => '請輸入手機號碼。',
        ]);

        // 取得 mobile 並移除所有非數字（保險起見，不只移除 `-`，也防其他符號）
        $mobile = preg_replace('/\D/', '', $validated['mobile']);

        // 驗證：長度是否為 10、是否為數字、是否 09 開頭
        if (!preg_match('/^09\d{8}$/', $mobile)) {
            return response()->json(['message' => '手機號碼格式錯誤，必須是 09 開頭的 10 碼數字。',], 422);
        }

        $request_data = request()->all();

        $request_data['order_taker'] = 'web';

        $order = $this->OrderService->addOrder($request_data);

        $data = [
            'id' => $order->id,
            'code' => $order->code,
        ];

        return response()->json(['success' => true, 'message' => '訂單新增成功', 'data' => $data,], 200 );
    }

    public function deliveries()
    {
        $code = request()->query('equal_order_code');
        $personal_name = request()->query('equal_personal_name');

        if (empty($code)) {
            return response()->json(['error' => '請提供訂單編號',], 400);
        }

        if (empty($personal_name)) {
            return response()->json(['error' => '請提供姓名',], 400);
        }

        $builder = DB::table('order_delivery as od')
            ->select('od.*')
            ->leftJoin('orders as o', 'o.code', '=', 'od.order_code')
            ->where('o.code', $code);

        $data = $builder->get();

        return response()->json(['success' => true, 'message' => '訂單新增成功', 'data' => $data,], 200);
    }

}
