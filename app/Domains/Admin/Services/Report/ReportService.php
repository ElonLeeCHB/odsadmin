<?php

namespace App\Domains\Admin\Services\Report;

use App\Models\Reports\MonthlyOperationReport;
use App\Models\Reports\MonthlyProductReport;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * 取得營運月報
     */
    public function getOperationReport(int $year, int $month): ?MonthlyOperationReport
    {
        return MonthlyOperationReport::yearMonth($year, $month)->first();
    }

    /**
     * 取得該月前十大商品
     */
    public function getTopProducts(int $year, int $month, int $limit = 10): Collection
    {
        return MonthlyProductReport::yearMonth($year, $month)
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get();
    }

    /**
     * 取得某年的所有月報（用於年度分析）
     */
    public function getYearlyReports(int $year): Collection
    {
        return MonthlyOperationReport::year($year)->get();
    }

    /**
     * 取得多年的月報數據（矩陣格式）
     */
    public function getYearlyMatrix(array $years): array
    {
        $reports = MonthlyOperationReport::whereIn('year', $years)
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $matrix = [];
        foreach ($reports as $report) {
            if (!isset($matrix[$report->year])) {
                $matrix[$report->year] = array_fill(1, 12, null);
            }
            $matrix[$report->year][$report->month] = $report->order_total_amount;
        }

        return $matrix;
    }

    /**
     * 取得已有報表的年份列表
     */
    public function getAvailableYears(): array
    {
        return MonthlyOperationReport::selectRaw('DISTINCT year')
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();
    }

    /**
     * 取得某年已有報表的月份列表
     */
    public function getAvailableMonths(int $year): array
    {
        return MonthlyOperationReport::where('year', $year)
            ->orderBy('month')
            ->pluck('month')
            ->toArray();
    }
}
