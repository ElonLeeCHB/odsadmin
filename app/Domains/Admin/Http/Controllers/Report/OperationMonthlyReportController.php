<?php

namespace App\Domains\Admin\Http\Controllers\Report;

use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Report\ReportService;
use App\Domains\Admin\Services\Report\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class OperationMonthlyReportController extends BackendController
{
    protected $reportService;
    protected $exportService;

    public function __construct(ReportService $reportService, ReportExportService $exportService)
    {
        $this->reportService = $reportService;
        $this->exportService = $exportService;
    }

    /**
     * 營運月報列表頁
     */
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $availableYears = $this->reportService->getAvailableYears();
        $availableMonths = $this->reportService->getAvailableMonths($year);

        // 取得該年所有月報
        $reports = $this->reportService->getYearlyReports($year);

        // 渲染列表 HTML
        $list = view('admin.reports.operation-monthly.list', [
            'year' => $year,
            'availableMonths' => $availableMonths,
            'reports' => $reports,
        ])->render();

        return view('admin.reports.operation-monthly.index', [
            'year' => $year,
            'availableYears' => $availableYears,
            'list' => $list,
        ]);
    }

    /**
     * 營運月報詳情頁
     */
    public function show(int $year, int $month)
    {
        $report = $this->reportService->getOperationReport($year, $month);

        if (!$report) {
            return redirect()
                ->route('admin.reports.operation-monthly.index', ['year' => $year])
                ->with('error', "查無 {$year} 年 {$month} 月的報表數據");
        }

        $topProducts = $this->reportService->getTopProducts($year, $month, 10);

        return view('admin.reports.operation-monthly.form', [
            'year' => $year,
            'month' => $month,
            'report' => $report,
            'topProducts' => $topProducts,
        ]);
    }

    /**
     * 匯出年度營運月報 XLSX
     */
    public function exportYear(int $year)
    {
        try {
            $filePath = $this->exportService->exportYearlyOperationReport($year);
            $fileName = "營運月報表_{$year}.xlsx";

            return response()->download($filePath, $fileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', "匯出失敗: {$e->getMessage()}");
        }
    }

    /**
     * 匯出單月 XLSX
     */
    public function export(int $year, int $month)
    {
        // TODO: 實作單月 XLSX 匯出功能
        return response()->json(['message' => '單月 XLSX 匯出功能開發中']);
    }

    /**
     * 重建報表數據
     */
    public function rebuild(int $year, int $month)
    {
        try {
            $yearMonth = sprintf('%04d%02d', $year, $month);
            Artisan::call('report:update-monthly', ['yearmonth' => $yearMonth]);

            return redirect()
                ->route('admin.reports.operation-monthly.show', ['year' => $year, 'month' => $month])
                ->with('success', "已成功重建 {$year} 年 {$month} 月的報表數據");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', "重建失敗: {$e->getMessage()}");
        }
    }
}
