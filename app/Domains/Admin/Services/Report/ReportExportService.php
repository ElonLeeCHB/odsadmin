<?php

namespace App\Domains\Admin\Services\Report;

use App\Models\Reports\MonthlyOperationReport;
use App\Models\Reports\MonthlyProductReport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportExportService
{
    /**
     * 匯出年度營運月報 XLSX
     */
    public function exportYearlyOperationReport(int $year): string
    {
        $spreadsheet = new Spreadsheet();

        // Sheet 1: 年度營運月報總覽
        $this->createOperationSummarySheet($spreadsheet, $year);

        // Sheet 2: 各月前十大商品
        $this->createTopProductsSheet($spreadsheet, $year);

        // 生成檔案
        $fileName = "營運月報表_{$year}.xlsx";
        $filePath = storage_path("app/temp/{$fileName}");

        // 確保目錄存在
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }

    /**
     * 建立營運總覽工作表
     */
    protected function createOperationSummarySheet(Spreadsheet $spreadsheet, int $year)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('營運月報總覽');

        // 標題
        $sheet->setCellValue('A1', "{$year} 年營運月報表");
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 表頭
        $headers = ['月份', '訂單總金額', '訂單數量', '訂單客戶數', '新客戶數', '進貨總金額', '毛利金額', '廠商數量'];
        $sheet->fromArray($headers, null, 'A3');

        // 設定表頭樣式
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E7E6E6']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ];
        $sheet->getStyle('A3:H3')->applyFromArray($headerStyle);

        // 取得數據
        $reports = MonthlyOperationReport::where('year', $year)
            ->orderBy('month')
            ->get();

        // 填充數據
        $row = 4;
        for ($m = 1; $m <= 12; $m++) {
            $report = $reports->firstWhere('month', $m);

            $sheet->setCellValue("A{$row}", "{$m} 月");

            if ($report) {
                $sheet->setCellValue("B{$row}", $report->order_total_amount);
                $sheet->setCellValue("C{$row}", $report->order_count);
                $sheet->setCellValue("D{$row}", $report->order_customer_count);
                $sheet->setCellValue("E{$row}", $report->new_customer_count);
                $sheet->setCellValue("F{$row}", $report->purchase_total_amount);
                $sheet->setCellValue("G{$row}", $report->gross_profit_amount);
                $sheet->setCellValue("H{$row}", $report->supplier_count);
            } else {
                $sheet->setCellValue("B{$row}", '-');
                $sheet->setCellValue("C{$row}", '-');
                $sheet->setCellValue("D{$row}", '-');
                $sheet->setCellValue("E{$row}", '-');
                $sheet->setCellValue("F{$row}", '-');
                $sheet->setCellValue("G{$row}", '-');
                $sheet->setCellValue("H{$row}", '-');
            }

            $row++;
        }

        // 設定數據樣式
        $dataStyle = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ];
        $sheet->getStyle("A4:H15")->applyFromArray($dataStyle);

        // 數字欄位靠右對齊
        $sheet->getStyle("B4:H15")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // 數字格式
        $sheet->getStyle("B4:B15")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("C4:H15")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("F4:G15")->getNumberFormat()->setFormatCode('#,##0');

        // 自動調整欄寬
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * 建立前十大商品工作表
     */
    protected function createTopProductsSheet(Spreadsheet $spreadsheet, int $year)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('各月前十大商品');

        // 標題
        $sheet->setCellValue('A1', "{$year} 年各月前十大商品");
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 3;

        // 每個月份的前十大商品
        for ($m = 1; $m <= 12; $m++) {
            // 月份標題
            $sheet->setCellValue("A{$row}", "{$m} 月");
            $sheet->mergeCells("A{$row}:E{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle("A{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D9E1F2');
            $row++;

            // 表頭
            $headers = ['排名', '商品代號', '商品名稱', '銷售數量', '銷售金額'];
            $sheet->fromArray($headers, null, "A{$row}");
            $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;

            // 取得該月前十大商品
            $topProducts = MonthlyProductReport::where('year', $year)
                ->where('month', $m)
                ->orderByDesc('total_amount')
                ->limit(10)
                ->get();

            if ($topProducts->count() > 0) {
                $rank = 1;
                foreach ($topProducts as $product) {
                    $sheet->setCellValue("A{$row}", $rank);
                    $sheet->setCellValue("B{$row}", $product->product_code);
                    $sheet->setCellValue("C{$row}", $product->product_name);
                    $sheet->setCellValue("D{$row}", $product->quantity);
                    $sheet->setCellValue("E{$row}", $product->total_amount);

                    $row++;
                    $rank++;
                }
            } else {
                $sheet->setCellValue("A{$row}", '尚無數據');
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row++;
            }

            $row++; // 空一行
        }

        // 數字欄位靠右對齊
        $sheet->getStyle("D:E")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // 數字格式
        $sheet->getStyle("D:D")->getNumberFormat()->setFormatCode('#,##0.000');
        $sheet->getStyle("E:E")->getNumberFormat()->setFormatCode('#,##0');

        // 自動調整欄寬
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
