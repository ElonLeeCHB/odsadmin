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
use Carbon\Carbon;

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
    public function index(Request $request)
    {
        $order_id = $request->input('order_id');

        if (empty($order_id)) {
            abort(400, '缺少 order_id 參數！');
        }

        $order = Order::find($order_id);

        if (empty($order)) {
            abort(404, '找不到該訂單！');
        }

        $data = [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'personal_name' => $order->personal_name,
            'payment_company' => $order->payment_company,
            'order_payments' => $order->orderPayments,
        ];

        return response()->json(['success' => true, 'data' => $data], 200, [], JSON_UNESCAPED_UNICODE);
    }

    // 新增一筆付款紀錄
    public function store(Request $request)
    {
        $post_data = $request->post();

        $order_id = $post_data['order_id'] ?? null;

        if (empty($order_id)) {
            abort(400, '缺少 order_id 參數！');
        }

        $order = Order::find($order_id);

        if (empty($order)) {
            abort(404, '找不到該訂單！');
        }

        // 驗證 payment 資料
        if (empty($post_data['payment'])) {
            abort(400, '缺少 payment 資料！');
        }

        try {
            DB::beginTransaction();

            // 更新訂單付款資訊
            $order->payment_date = $post_data['payment_date'] ?? null;
            $order->payment_paid = $post_data['payment_paid'] ?? null;
            $order->payment_unpaid = $post_data['payment_unpaid'] ?? null;
            $order->payment_total = $post_data['payment_total'] ?? null;
            $order->is_payed_off = (isset($post_data['is_payed_off']) && $post_data['is_payed_off'] === 1) ? 1 : 0;
            $order->payment_method = $post_data['payment_method'] ?? null;
            $order->scheduled_payment_date = $post_data['scheduled_payment_date'] ?? null;
            $order->payment_comment = $post_data['payment_comment'] ?? null;
            $order->save();

            // 新增付款記錄
            $payment = new OrderPayment;
            $payment->order_id = $order->id;
            $payment->order_code = $order->code;
            $payment->amount = $post_data['payment']['amount'];
            $payment->status = $post_data['payment']['status']; // enum: pending, complete, canceled, failed, refunded
            $payment->payment_type_code = $post_data['payment']['payment_type_code'];
            $payment->payment_date = $post_data['payment']['payment_date'];
            $payment->scheduled_payment_date = $post_data['payment']['scheduled_payment_date'] ?? null;
            $payment->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '已新增付款記錄',
                'data' => [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th; // 拋出異常讓 Handler.php 處理
        }
    }

    // 顯示特定付款紀錄
    public function show(Request $request, $payment_id)
    {
        $order_id = $request->input('order_id');

        if (empty($order_id)) {
            abort(400, '缺少 order_id 參數！');
        }

        $payment = OrderPayment::find($payment_id);

        if (empty($payment)) {
            abort(404, '找不到該付款記錄！');
        }

        // 驗證付款記錄是否屬於該訂單
        if ($payment->order_id != $order_id) {
            abort(403, '付款記錄不屬於該訂單！');
        }

        return response()->json(['success' => true, 'data' => $payment], 200, [], JSON_UNESCAPED_UNICODE);
    }

    // 更新付款紀錄
    public function update(Request $request, $payment_id)
    {
        $post_data = $request->all();
        $order_id = $post_data['order_id'] ?? null;

        if (empty($order_id)) {
            abort(400, '缺少 order_id 參數！');
        }

        $payment = OrderPayment::find($payment_id);

        if (empty($payment)) {
            abort(404, '找不到該付款記錄！');
        }

        // 驗證付款記錄是否屬於該訂單
        if ($payment->order_id != $order_id) {
            abort(403, '付款記錄不屬於該訂單！');
        }

        try {
            DB::beginTransaction();

            // 更新付款記錄
            if (isset($post_data['amount'])) {
                $payment->amount = $post_data['amount'];
            }
            if (isset($post_data['status'])) {
                $payment->status = $post_data['status']; // enum: pending, complete, canceled, failed, refunded
            }
            if (isset($post_data['payment_type_code'])) {
                $payment->payment_type_code = $post_data['payment_type_code'];
            }
            if (isset($post_data['payment_date'])) {
                $payment->payment_date = $post_data['payment_date'];
            }
            if (isset($post_data['scheduled_payment_date'])) {
                $payment->scheduled_payment_date = $post_data['scheduled_payment_date'];
            }

            $payment->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '付款記錄已更新',
                'data' => $payment
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th; // 拋出異常讓 Handler.php 處理
        }
    }

    // 刪除付款紀錄
    public function destroy($payment_id)
    {
        $payment = OrderPayment::find($payment_id);

        if (empty($payment)) {
            abort(404, '找不到該付款記錄！');
        }

        // 檢查刪除限制：付款完成超過一天不可刪除
        if ($payment->status === 'complete') {
            $updated_at = Carbon::parse($payment->updated_at);
            $now = Carbon::now();

            if ($updated_at->diffInDays($now) >= 1) {
                abort(403, '付款完成超過一天，不可刪除。請洽詢資訊人員。');
            }
        }

        try {
            DB::beginTransaction();

            // 取得關聯的訂單
            if (!empty($payment->order_id)) {
                $order = Order::find($payment->order_id);
            } else if (!empty($payment->order_code)) {
                $order = Order::where('code', $payment->order_code)->first();
            }

            if (empty($order)) {
                abort(404, '有付款記錄但關聯的訂單不存在！');
            }

            // 保存付款金額用於計算
            $payment_amount = $payment->amount;

            // 刪除付款記錄
            $payment->delete();

            // 更新訂單的付款資訊
            // payment_paid 扣除刪除的金額
            $order->payment_paid = max(0, ($order->payment_paid ?? 0) - $payment_amount);

            // payment_unpaid 增加刪除的金額
            $order->payment_unpaid = ($order->payment_unpaid ?? 0) + $payment_amount;

            // 如果還有未付金額，則標記為未付清
            if ($order->payment_unpaid > 0) {
                $order->is_payed_off = 0;
            }

            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '付款記錄已刪除',
                'data' => [
                    'order_id' => $order->id,
                    'payment_total' => $order->payment_total,
                    'payment_paid' => $order->payment_paid,
                    'payment_unpaid' => $order->payment_unpaid,
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th; // 拋出異常讓 Handler.php 處理
        }
    }
}
