<?php

namespace App\Console\Commands\Report;

use App\Models\Reports\MonthlyOperationReport;
use App\Models\Reports\MonthlyProductReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateMonthlyReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:update-monthly {yearmonth : 年月格式 YYYYMM 或年份 YYYY，例如: 202509 或 2025}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新指定年月的營運報表和商品報表';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $yearMonth = $this->argument('yearmonth');

        // 驗證格式（支援 YYYY 或 YYYYMM）
        if (preg_match('/^\d{4}$/', $yearMonth)) {
            // 四碼年份：執行整年 12 個月
            $year = (int) $yearMonth;
            return $this->updateFullYear($year);
        } elseif (preg_match('/^\d{6}$/', $yearMonth)) {
            // 六碼年月：執行單月
            $year = (int) substr($yearMonth, 0, 4);
            $month = (int) substr($yearMonth, 4, 2);
            return $this->updateSingleMonth($year, $month);
        } else {
            $this->error('格式錯誤！請使用 YYYY 或 YYYYMM 格式，例如: 2025 或 202509');
            return 1;
        }
    }

    /**
     * 更新整年數據
     */
    protected function updateFullYear(int $year): int
    {
        $this->info("開始更新 {$year} 年全年報表數據...");

        $failedMonths = [];

        for ($month = 1; $month <= 12; $month++) {
            try {
                DB::beginTransaction();
                $this->line("");
                $this->info(">>> 處理 {$year} 年 {$month} 月");
                $this->updateOperationReport($year, $month);
                $this->updateProductReports($year, $month);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("✗ {$month} 月更新失敗: {$e->getMessage()}");
                $failedMonths[] = $month;
            }
        }

        $this->line("");
        if (empty($failedMonths)) {
            $this->info("✓ {$year} 年全年報表更新完成！");
            return 0;
        } else {
            $this->warn("⚠ {$year} 年報表更新完成，但以下月份失敗: " . implode(', ', $failedMonths));
            return 1;
        }
    }

    /**
     * 更新單月數據
     */
    protected function updateSingleMonth(int $year, int $month): int
    {

        // 驗證月份
        if ($month < 1 || $month > 12) {
            $this->error('月份必須在 1-12 之間');
            return 1;
        }

        $this->info("開始更新 {$year} 年 {$month} 月的報表數據...");

        try {
            DB::beginTransaction();

            // 1. 更新營運月報主表
            $this->updateOperationReport($year, $month);

            // 2. 更新商品月報表
            $this->updateProductReports($year, $month);

            DB::commit();

            $this->info("✓ {$year} 年 {$month} 月報表更新完成！");
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("✗ 更新失敗: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * 更新營運月報主表
     */
    protected function updateOperationReport(int $year, int $month)
    {
        $this->line("  → 統計營運數據...");

        // 1. 統計訂單數據（使用 delivery_date 出貨日，只計算 Confirmed 或 CCP）
        $orderStats = DB::table('orders')
            ->whereYear('delivery_date', $year)
            ->whereMonth('delivery_date', $month)
            ->whereIn('status_code', ['Confirmed', 'CCP'])
            ->selectRaw('
                COALESCE(SUM(payment_total), 0) as order_total_amount,
                COUNT(*) as order_count,
                COUNT(DISTINCT customer_id) as order_customer_count
            ')
            ->first();

        // 2. 統計新客戶數量（該月首次下單的客戶，只計算 Confirmed 或 CCP）
        $newCustomerCount = DB::table('orders as o1')
            ->whereYear('o1.delivery_date', $year)
            ->whereMonth('o1.delivery_date', $month)
            ->whereIn('o1.status_code', ['Confirmed', 'CCP'])
            ->whereNotExists(function ($query) use ($year, $month) {
                $query->select(DB::raw(1))
                    ->from('orders as o2')
                    ->whereColumn('o2.customer_id', 'o1.customer_id')
                    ->whereIn('o2.status_code', ['Confirmed', 'CCP'])
                    ->where(function ($q) use ($year, $month) {
                        $q->where(DB::raw('YEAR(o2.delivery_date)'), '<', $year)
                          ->orWhere(function ($q2) use ($year, $month) {
                              $q2->where(DB::raw('YEAR(o2.delivery_date)'), '=', $year)
                                 ->where(DB::raw('MONTH(o2.delivery_date)'), '<', $month);
                          });
                    });
            })
            ->distinct('o1.customer_id')
            ->count('o1.customer_id');

        // 3. 統計進貨數據（只計算原物料 RMT，排除費用 EXP，排除作廢 V）
        $purchaseStats = DB::table('receiving_orders')
            ->where('form_type_code', 'RMT')
            ->where('status_code', '!=', 'V')
            ->whereYear('receiving_date', $year)
            ->whereMonth('receiving_date', $month)
            ->selectRaw('
                COALESCE(SUM(total), 0) as purchase_total_amount,
                COUNT(DISTINCT location_id) as supplier_count
            ')
            ->first();

        // 4. 計算毛利金額
        $orderAmount = $orderStats->order_total_amount ?? 0;
        $purchaseAmount = $purchaseStats->purchase_total_amount ?? 0;
        $grossProfitAmount = $orderAmount - $purchaseAmount;

        // 5. 寫入營運月報主表
        MonthlyOperationReport::updateOrCreate(
            ['year' => $year, 'month' => $month],
            [
                'order_total_amount' => $orderAmount,
                'order_count' => $orderStats->order_count ?? 0,
                'order_customer_count' => $orderStats->order_customer_count ?? 0,
                'new_customer_count' => $newCustomerCount,
                'purchase_total_amount' => $purchaseAmount,
                'gross_profit_amount' => $grossProfitAmount,
                'supplier_count' => $purchaseStats->supplier_count ?? 0,
                'updated_at' => now(),
            ]
        );

        $this->info("    ✓ 訂單總金額: " . number_format($orderAmount, 2));
        $this->info("    ✓ 訂單數量: " . ($orderStats->order_count ?? 0));
        $this->info("    ✓ 訂單客戶數: " . ($orderStats->order_customer_count ?? 0));
        $this->info("    ✓ 新客戶數: {$newCustomerCount}");
        $this->info("    ✓ 進貨總金額: " . number_format($purchaseAmount, 2));
        $this->info("    ✓ 毛利金額: " . number_format($grossProfitAmount, 2));
        $this->info("    ✓ 廠商數: " . ($purchaseStats->supplier_count ?? 0));
    }

    /**
     * 更新商品月報表
     */
    protected function updateProductReports(int $year, int $month)
    {
        $this->line("  → 統計商品銷售數據...");

        // 統計商品銷售（使用訂單出貨日，商品名稱取該月出現次數最多的名稱，只計算 Confirmed 或 CCP）
        $productStats = DB::table('order_products as op')
            ->join('orders as o', 'op.order_id', '=', 'o.id')
            ->join('products as p', 'op.product_id', '=', 'p.id')
            ->whereYear('o.delivery_date', $year)
            ->whereMonth('o.delivery_date', $month)
            ->whereIn('o.status_code', ['Confirmed', 'CCP'])
            ->selectRaw('
                p.id as product_id,
                (
                    SELECT op2.name
                    FROM order_products op2
                    JOIN orders o2 ON op2.order_id = o2.id
                    WHERE op2.product_id = p.id
                        AND YEAR(o2.delivery_date) = ?
                        AND MONTH(o2.delivery_date) = ?
                        AND o2.status_code IN (\'Confirmed\', \'CCP\')
                    GROUP BY op2.name
                    ORDER BY COUNT(*) DESC
                    LIMIT 1
                ) as product_name,
                SUM(op.quantity) as quantity,
                SUM(op.final_total) as total_amount
            ', [$year, $month])
            ->groupBy('p.id')
            ->get();

        // 先刪除該月舊數據
        MonthlyProductReport::where('year', $year)
            ->where('month', $month)
            ->delete();

        // 批次寫入新數據
        $insertData = [];
        foreach ($productStats as $product) {
            $insertData[] = [
                'year' => $year,
                'month' => $month,
                'product_code' => (string) $product->product_id, // 使用 product_id 作為代號
                'product_name' => $product->product_name,
                'quantity' => $product->quantity,
                'total_amount' => $product->total_amount,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($insertData)) {
            MonthlyProductReport::insert($insertData);
            $this->info("    ✓ 商品統計數: " . count($insertData));

            // 顯示前三名
            $top3 = collect($insertData)
                ->sortByDesc('total_amount')
                ->take(3);

            $rank = 1;
            foreach ($top3 as $item) {
                $this->line("      #{$rank} {$item['product_name']}: " .
                    number_format($item['total_amount'], 2));
                $rank++;
            }
        } else {
            $this->warn("    ! 該月無商品銷售數據");
        }
    }
}
