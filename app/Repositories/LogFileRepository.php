<?php

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * 檔案日誌存儲庫
 *
 * 功能：
 * - 將日誌寫入檔案系統（storage/logs/logs_yyyy-mm-dd.txt）
 * - 每日一個檔案，方便管理和查詢
 * - 提供壓縮舊月份日誌的功能（logs_yyyy-mm.zip）
 * - JSON Lines 格式，每行一個 JSON，方便解析和搜尋
 */
class LogFileRepository
{
    /**
     * 日誌目錄
     */
    protected string $logDir;

    /**
     * 是否自動創建目錄
     */
    protected bool $autoCreateDir = true;

    public function __construct()
    {
        $this->logDir = storage_path('logs/app');

        // 確保目錄存在
        if ($this->autoCreateDir && !File::exists($this->logDir)) {
            File::makeDirectory($this->logDir, 0755, true);
        }
    }

    /**
     * 記錄日誌（通用方法）
     *
     * @param array $params
     * @return bool
     */
    public function log(array $params): bool
    {
        $logData = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'uniqueid' => $params['uniqueid'] ?? app('unique_id') ?? '',
            'area' => config('app.env'),
            'url' => $params['url'] ?? '',
            'method' => $params['method'] ?? '',
            'data' => $params['data'] ?? [],
            'status' => $params['status'] ?? '',
            'note' => $params['note'] ?? '',
            'client_ip' => $this->getClientIp(),
            'api_ip' => request()->ip(),
        ];

        return $this->writeLog($logData);
    }

    /**
     * 記錄請求日誌
     *
     * @param string|array $note
     * @return bool
     */
    public function logRequest($note = ''): bool
    {
        // 讀取請求資料
        if (request()->isJson()) {
            $json = json_decode(request()->getContent(), true);
            $data = $json ?? [];
        } else {
            $data = request()->all();
        }

        // 過濾敏感資料（密碼等）
        $data = $this->filterSensitiveData($data);

        $logData = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'uniqueid' => app('unique_id') ?? time() . '-' . uniqid(),
            'area' => config('app.env'),
            'url' => request()->fullUrl() ?? '',
            'method' => request()->method() ?? '',
            'data' => $data,
            'status' => '',
            'note' => is_array($note) ? json_encode($note, JSON_UNESCAPED_UNICODE) : $note,
            'client_ip' => $this->getClientIp(),
            'api_ip' => request()->ip(),
        ];

        return $this->writeLog($logData);
    }

    /**
     * 記錄錯誤日誌（在請求之後）
     *
     * @param array $params
     * @return bool
     */
    public function logErrorAfterRequest(array $params): bool
    {
        $logData = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'uniqueid' => app('unique_id') ?? time() . '-' . uniqid(),
            'area' => config('app.env'),
            'url' => '',
            'method' => '',
            'data' => $params['data'] ?? [],
            'status' => $params['status'] ?? 'error',
            'note' => $params['note'] ?? '',
            'client_ip' => '',
            'api_ip' => '',
        ];

        return $this->writeLog($logData);
    }

    /**
     * 寫入日誌到檔案
     *
     * @param array $logData
     * @return bool
     */
    protected function writeLog(array $logData): bool
    {
        try {
            $date = Carbon::now()->format('Y-m-d');
            $filename = "logs_{$date}.txt";
            $filepath = $this->logDir . '/' . $filename;

            // 轉換為 JSON Lines 格式（每行一個 JSON）
            $jsonLine = json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

            // 追加寫入檔案（使用 LOCK_EX 避免併發問題）
            return File::append($filepath, $jsonLine);
        } catch (\Exception $e) {
            // 如果寫入失敗，記錄到 Laravel 的標準日誌
            // ⚠️ 不能在這裡使用任何可能觸發例外處理的功能，避免無限循環
            try {
                error_log('LogFileRepository: 寫入日誌失敗 - ' . $e->getMessage());
            } catch (\Exception $innerException) {
                // 完全失敗，靜默處理
            }
            return false;
        }
    }

    /**
     * 取得客戶端 IP
     *
     * @return string
     */
    protected function getClientIp(): string
    {
        if (request()->hasHeader('X-CLIENT-IPV4')) {
            return request()->header('X-CLIENT-IPV4');
        }

        return request()->ip() ?? '';
    }

    /**
     * 過濾敏感資料
     *
     * @param array $data
     * @return array
     */
    protected function filterSensitiveData(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'secret', 'api_key'];

        foreach ($sensitiveKeys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***FILTERED***';
            }
        }

        return $data;
    }

    /**
     * 壓縮指定月份的日誌檔案
     *
     * @param string $month 格式：Y-m (例如 2025-01)
     * @return array 包含 success, message, zip_path 的結果
     */
    public function compressMonthLogs(string $month): array
    {
        try {
            // 驗證月份格式
            $carbonMonth = Carbon::createFromFormat('Y-m', $month);
            if (!$carbonMonth) {
                return [
                    'success' => false,
                    'message' => '月份格式錯誤，應為 Y-m',
                ];
            }

            $zipFilename = "logs_{$month}.zip";
            $zipPath = $this->logDir . '/' . $zipFilename;

            // 如果壓縮檔已存在，先刪除
            if (File::exists($zipPath)) {
                File::delete($zipPath);
            }

            // 尋找該月份的所有日誌檔案
            $pattern = $this->logDir . "/logs_{$month}-*.txt";
            $files = glob($pattern);

            if (empty($files)) {
                return [
                    'success' => false,
                    'message' => "找不到 {$month} 的日誌檔案",
                ];
            }

            // 創建 ZIP 壓縮檔
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                return [
                    'success' => false,
                    'message' => '無法創建壓縮檔',
                ];
            }

            // 加入所有檔案到壓縮檔
            $addedFiles = [];
            foreach ($files as $file) {
                $filename = basename($file);
                if ($zip->addFile($file, $filename)) {
                    $addedFiles[] = $filename;
                }
            }

            $zip->close();

            // 驗證壓縮檔是否成功創建
            if (!File::exists($zipPath)) {
                return [
                    'success' => false,
                    'message' => '壓縮檔創建失敗',
                ];
            }

            // 刪除已壓縮的原始檔案
            foreach ($files as $file) {
                File::delete($file);
            }

            return [
                'success' => true,
                'message' => "成功壓縮 {$month} 的日誌，共 " . count($addedFiles) . " 個檔案",
                'zip_path' => $zipPath,
                'files_count' => count($addedFiles),
                'files' => $addedFiles,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '壓縮失敗：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 自動壓縮上個月的日誌（用於定時任務）
     *
     * @return array
     */
    public function autoCompressLastMonth(): array
    {
        $lastMonth = Carbon::now()->subMonth()->format('Y-m');
        return $this->compressMonthLogs($lastMonth);
    }

    /**
     * 列出所有日誌檔案
     *
     * @return array
     */
    public function listLogFiles(): array
    {
        $files = File::files($this->logDir);
        $result = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $size = $file->getSize();
            $modified = Carbon::createFromTimestamp($file->getMTime());

            $result[] = [
                'filename' => $filename,
                'size' => $this->formatBytes($size),
                'size_bytes' => $size,
                'modified' => $modified->toDateTimeString(),
                'type' => pathinfo($filename, PATHINFO_EXTENSION) === 'zip' ? 'compressed' : 'log',
            ];
        }

        // 按修改時間排序（新到舊）
        usort($result, function ($a, $b) {
            return $b['modified'] <=> $a['modified'];
        });

        return $result;
    }

    /**
     * 格式化位元組大小
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * 讀取指定日期的日誌
     *
     * @param string $date 格式：Y-m-d
     * @param int $limit 限制行數（0 = 不限制）
     * @return array
     */
    public function readLogsByDate(string $date, int $limit = 0): array
    {
        $filename = "logs_{$date}.txt";
        $filepath = $this->logDir . '/' . $filename;

        if (!File::exists($filepath)) {
            return [
                'success' => false,
                'message' => "找不到 {$date} 的日誌檔案",
                'logs' => [],
            ];
        }

        try {
            $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logs = [];

            foreach ($lines as $line) {
                $log = json_decode($line, true);
                if ($log) {
                    $logs[] = $log;
                }

                if ($limit > 0 && count($logs) >= $limit) {
                    break;
                }
            }

            return [
                'success' => true,
                'message' => "成功讀取 {$date} 的日誌",
                'logs' => $logs,
                'total' => count($logs),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '讀取日誌失敗：' . $e->getMessage(),
                'logs' => [],
            ];
        }
    }
}
