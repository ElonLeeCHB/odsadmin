<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Models\Sale\Order;
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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @queryParam date string required 日期 (Y-m-d 格式). Example: 2025-12-10
     */
    public function dailySummary()
    {
        try {
            $date = $this->request->query('date');

            // 驗證日期格式
            if (empty($date)) {
                return $this->sendJsonResponse(['error' => '請提供日期參數 (date)'], 400);
            }

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

            // 驗算：paid + unpaid 應該等於 total
            $isBalanced = ($paidAmount + $unpaidAmount) === $totalAmount;

            $data = [
                'date' => $date,
                'order_count' => (int) ($summary->order_count ?? 0),
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'unpaid_amount' => $unpaidAmount,
                'is_balanced' => $isBalanced,
            ];

            // 如果驗算不符，加入差額資訊
            if (!$isBalanced) {
                $data['balance_diff'] = $totalAmount - ($paidAmount + $unpaidAmount);
            }

            return $this->sendJsonResponse(data: $data);

        } catch (\Throwable $th) {
            return $this->sendJsonResponse(['error' => $th->getMessage()], 500);
        }
    }
}
