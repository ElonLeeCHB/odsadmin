<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Helpers\Classes\DateHelper;
use App\Helpers\Classes\LogHelper;
use App\Helpers\Classes\OrmHelper;
use App\Models\Sale\Order;
use App\Models\Sale\OrderPayment;

/*
public function index()    // GET 所有資料
public function store()    // POST 新增
public function show()     // GET 單筆資料
public function update()   // PUT/PATCH 更新
public function destroy()  // DELETE 刪除
*/

class PaymentController extends ApiPosController
{
    // 取得某訂單的所有付款紀錄
    public function index(Order $order)
    {
        try {

            $data = [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'personal_name' => $order->personal_name,
                'payment_company' => $order->payment_company,
                'order_payments' => $order->orderPayments,
            ];

            return response()->json(['success' => true, 'data' => $data], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $th) {
            return $this->sendJsonErrorResponse(response: ['sys_error' => $th->getMessage()], status_code: 500);
        }
    }

    // 新增一筆付款紀錄
    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'method' => 'required|string|max:50',
            'note' => 'nullable|string',
        ]);

        $payment = new OrderPayment($validated);
        $payment->order_id = $order->id;
        $payment->save();

        return response()->json($payment, 201);
    }

    // 顯示特定付款紀錄
    public function show(Order $order, OrderPayment $payment)
    {
        $this->ensurePaymentBelongsToOrder($order, $payment);

        return response()->json($payment);
    }

    // 更新付款紀錄
    public function update(Request $request, Order $order, OrderPayment $payment)
    {
        $this->ensurePaymentBelongsToOrder($order, $payment);

        $validated = $request->validate([
            'amount' => 'sometimes|integer|min:1',
            'method' => 'sometimes|string|max:50',
            'note' => 'nullable|string',
        ]);

        $payment->update($validated);

        return response()->json($payment);
    }

    // 刪除付款紀錄
    public function destroy(Order $order, OrderPayment $payment)
    {
        $this->ensurePaymentBelongsToOrder($order, $payment);

        $payment->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    // 確保這筆付款紀錄真的屬於這筆訂單
    protected function ensurePaymentBelongsToOrder(Order $order, OrderPayment $payment)
    {
        if ($payment->order_id !== $order->id) {
            abort(404, 'Payment does not belong to this order');
        }
    }
}
