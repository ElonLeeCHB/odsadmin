<?php

namespace App\Domains\Admin\Http\Controllers\Report;

use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Report\ReportService;
use Illuminate\Http\Request;

class AnnualOrderReportController extends BackendController
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * 年度訂單分析頁
     */
    public function index(Request $request)
    {
        $availableYears = $this->reportService->getAvailableYears();

        // 預設選擇最近 3 年
        $defaultYears = array_slice($availableYears, 0, 3);
        $selectedYears = $request->get('years', $defaultYears);

        // 確保 selectedYears 是陣列
        if (!is_array($selectedYears)) {
            $selectedYears = [$selectedYears];
        }

        // 取得矩陣數據
        $matrix = $this->reportService->getYearlyMatrix($selectedYears);

        return view('admin.reports.annual-order.index', [
            'availableYears' => $availableYears,
            'selectedYears' => $selectedYears,
            'matrix' => $matrix,
        ]);
    }

    /**
     * 匯出年度報表 XLSX
     */
    public function export(Request $request)
    {
        // TODO: 實作 XLSX 匯出功能
        return response()->json(['message' => 'XLSX 匯出功能開發中']);
    }
}
