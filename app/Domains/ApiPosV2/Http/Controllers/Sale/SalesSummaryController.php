<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Models\Sale\Order;
use App\Models\Sale\OrderPayment;
use App\Models\Sale\Invoice;
use App\Enums\Sales\InvoiceStatus;
use App\Enums\OrderPaymentStatus;
use Illuminate\Support\Facades\DB;

class SalesSummaryController extends ApiPosController
{
    public function __construct(private Request $request)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }

    /**
     * 單日營收統計
     *
     * @param string $date 日期 (Y-m-d 格式)
     * @return \Illuminate\Http\JsonResponse
     *
     * @urlParam date string required 日期 (Y-m-d 格式). Example: 2025-12-10
     */
    public function dailySummary(string $date)
    {
        $indexNames = [
            'date' => '日期',
            'order_count' => '訂單數量',
            'total_amount' => '出餐金額',
            // 'paid_amount' => '已付金額',
            // 'unpaid_amount' => '未付金額',
            'cash' => '現金收款',
            'wire' => '匯款收款',
            'uber' => 'Uber收款',
            'invoice_amount_issued' => '發票開立金額',
            'receivable_amount' => '應收未收金額',
        ];

        try {
            // 驗證日期格式
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return $this->sendJsonResponse(['error' => '日期格式錯誤，請使用 Y-m-d 格式'], 400);
            }

            // 查詢該日期的訂單統計
            $summary = Order::whereDate('delivery_date', $date)
                ->select([
                    DB::raw('SUM(payment_total) as total_amount'),
                    DB::raw('SUM(payment_paid) as paid_amount'),
                    DB::raw('SUM(payment_unpaid) as unpaid_amount'),
                    DB::raw('COUNT(*) as order_count'),
                ])
                ->first();

            $totalAmount = (int) ($summary->total_amount ?? 0);
            $paidAmount = (int) ($summary->paid_amount ?? 0);
            $unpaidAmount = (int) ($summary->unpaid_amount ?? 0);

            // 今日開立發票總金額（不含作廢）
            $invoiceAmountIssued = (int) Invoice::whereDate('invoice_date', $date)
                ->where('status', InvoiceStatus::Issued)
                ->sum('total_amount');

            // 今日收款統計（依付款方式）- 只計算 CCP/Confirmed 訂單
            $paymentBase = OrderPayment::whereDate('order_payments.payment_date', $date)
                ->where('order_payments.status', OrderPaymentStatus::Complete)
                ->join('orders', 'order_payments.order_id', '=', 'orders.id')
                ->whereIn('orders.status_code', ['CCP', 'Confirmed']);

            $cashAmount = (int) (clone $paymentBase)->where('payment_type_code', 'cash')->sum('amount');
            $wireAmount = (int) (clone $paymentBase)->where('payment_type_code', 'wire')->sum('amount');
            $uberAmount = (int) (clone $paymentBase)->where('payment_type_code', 'uber')->sum('amount');

            // 應收未收金額：已出餐訂單(delivery_date <= 今天)應收總額 - 已完成付款總額
            $totalReceivable = (int) Order::where('delivery_date', '<=', $date)
                ->whereIn('status_code', ['CCP', 'Confirmed'])
                ->sum('payment_total');

            $totalPaidAmount = (int) OrderPayment::where('order_payments.status', OrderPaymentStatus::Complete)
                ->join('orders', 'order_payments.order_id', '=', 'orders.id')
                ->where('orders.delivery_date', '<=', $date)
                ->whereIn('orders.status_code', ['CCP', 'Confirmed'])
                ->sum('order_payments.amount');

            $receivableAmount = $totalReceivable - $totalPaidAmount;

            $data = [
                'date' => $date,
                'order_count' => (int) ($summary->order_count ?? 0),
                'total_amount' => $totalAmount,
                // 'paid_amount' => $paidAmount,
                // 'unpaid_amount' => $unpaidAmount,
                'cash' => $cashAmount,
                'wire' => $wireAmount,
                'uber' => $uberAmount,
                'invoice_amount_issued' => $invoiceAmountIssued,
                'receivable_amount' => $receivableAmount,
                'index_names' => $indexNames,
                'remark' => "出餐金額、已付金額、未付金額是使用訂單主表(送達日期=今天)的訂單總金額。<BR>現金收款、匯款收款、Uber收款是使用付款記錄表(付款日期=今天)的收款金額。<BR>應收未收=所有已出餐訂單(Confirmed/CCP, delivery_date<=今天)的payment_total加總，扣掉已付款金額。",
            ];

            return $this->sendJsonResponse(data: $data);

        } catch (\Throwable $th) {
            return $this->sendJsonResponse(['error' => $th->getMessage()], 500);
        }
    }
}
