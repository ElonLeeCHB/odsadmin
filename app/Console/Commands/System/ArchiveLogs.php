<?php

namespace App\Console\Commands\System;

use Illuminate\Console\Command;
use App\Models\System\Log;
use Carbon\Carbon;
use ZipArchive;

class ArchiveLogs extends Command
{
    protected $signature = 'logs:archive {--month= : 指定月份 YYYY-MM，預設為上個月}';

    protected $description = '歸檔日誌：匯出指定月份記錄至 ZIP，並刪除超過 6 個月的資料';

    /**
     * 歸檔目錄
     */
    protected string $archivePath;

    public function __construct()
    {
        parent::__construct();
        $this->archivePath = storage_path('logs/archived');
    }

    public function handle(): int
    {
        // 確保歸檔目錄存在
        if (!is_dir($this->archivePath)) {
            mkdir($this->archivePath, 0755, true);
            $this->info("建立歸檔目錄：{$this->archivePath}");
        }

        // 取得目標月份
        $monthOption = $this->option('month');
        if ($monthOption) {
            try {
                $targetMonth = Carbon::createFromFormat('Y-m', $monthOption)->startOfMonth();
            } catch (\Exception $e) {
                $this->error("無效的月份格式，請使用 YYYY-MM（例如：2025-12）");
                return 1;
            }
        } else {
            // 預設為上個月
            $targetMonth = Carbon::now()->subMonth()->startOfMonth();
        }

        $monthString = $targetMonth->format('Y-m');
        $this->info("開始歸檔 {$monthString} 的日誌...");

        // Step 1: 匯出日誌
        $exportResult = $this->exportLogs($targetMonth);
        if (!$exportResult['success']) {
            $this->error($exportResult['message']);
            return 1;
        }

        if ($exportResult['count'] === 0) {
            $this->info("該月份沒有日誌記錄，跳過歸檔");
        } else {
            $this->info("匯出完成：{$exportResult['count']} 筆記錄 → {$exportResult['zip_file']}");
        }

        // Step 2: 刪除超過 6 個月的記錄
        $deleteResult = $this->deleteOldLogs();
        $this->info("清理完成：刪除 {$deleteResult['deleted']} 筆超過 6 個月的記錄");

        $this->info('歸檔作業完成');
        return 0;
    }

    /**
     * 匯出指定月份的日誌
     */
    protected function exportLogs(Carbon $targetMonth): array
    {
        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();
        $monthString = $targetMonth->format('Y-m');

        // 查詢該月份的日誌
        $logs = Log::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'asc')
            ->get();

        if ($logs->isEmpty()) {
            return [
                'success' => true,
                'count' => 0,
                'message' => '該月份沒有日誌記錄',
            ];
        }

        // 建立 JSON Lines 暫存檔
        $jsonlFile = $this->archivePath . "/logs_{$monthString}.jsonl";
        $zipFile = $this->archivePath . "/logs_{$monthString}.zip";

        // 產生 archive_id 並寫入 JSON Lines
        $handle = fopen($jsonlFile, 'w');
        if (!$handle) {
            return [
                'success' => false,
                'message' => "無法建立暫存檔：{$jsonlFile}",
            ];
        }

        $lastSecond = null;
        $sequenceInSecond = 0;

        foreach ($logs as $log) {
            $logArray = $log->toArray();

            // 產生 archive_id
            $currentSecond = $log->created_at->format('Y-m-d_H-i-s');
            if ($currentSecond === $lastSecond) {
                $sequenceInSecond++;
            } else {
                $sequenceInSecond = 1;
                $lastSecond = $currentSecond;
            }

            $archiveId = $currentSecond . '_' . str_pad($sequenceInSecond, 6, '0', STR_PAD_LEFT);
            $logArray['archive_id'] = $archiveId;

            // 寫入 JSON Lines（每行一個 JSON 物件）
            fwrite($handle, json_encode($logArray, JSON_UNESCAPED_UNICODE) . "\n");
        }

        fclose($handle);

        // 壓縮成 ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            unlink($jsonlFile);
            return [
                'success' => false,
                'message' => "無法建立 ZIP 檔案：{$zipFile}",
            ];
        }

        $zip->addFile($jsonlFile, "logs_{$monthString}.jsonl");
        $zip->close();

        // 刪除暫存的 JSON Lines 檔案
        unlink($jsonlFile);

        return [
            'success' => true,
            'count' => $logs->count(),
            'zip_file' => $zipFile,
        ];
    }

    /**
     * 刪除超過 6 個月的日誌記錄
     */
    protected function deleteOldLogs(): array
    {
        $cutoffDate = Carbon::now()->subMonths(6)->startOfMonth();

        $deleted = Log::where('created_at', '<', $cutoffDate)->delete();

        return [
            'deleted' => $deleted,
            'cutoff_date' => $cutoffDate->toDateString(),
        ];
    }
}
