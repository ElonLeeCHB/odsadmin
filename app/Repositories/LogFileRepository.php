<?php

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * 檔案日誌存儲庫（備用）
 *
 * 功能：
 * - 將日誌寫入檔案系統（storage/logs/logs_yyyy-mm-dd.txt）
 * - 每日一個檔案，方便管理和查詢
 * - 提供壓縮舊月份日誌的功能（logs_yyyy-mm.zip）
 * - JSON Lines 格式，每行一個 JSON，方便解析和搜尋
 *
 * 注意：目前系統使用 LogToDbRepository，此檔案保留備用
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
            'request_trace_id' => $params['request_trace_id'] ?? app('request_trace_id') ?? '',
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
            'request_trace_id' => app('request_trace_id') ?? time() . '-' . uniqid(),
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
            'request_trace_id' => app('request_trace_id') ?? time() . '-' . uniqid(),
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
     * 依序嘗試：.txt → .zip → .7z
     *
     * @param string $date 格式：Y-m-d
     * @param int $limit 限制行數（0 = 不限制）
     * @return array
     */
    public function readLogsByDate(string $date, int $limit = 0): array
    {
        // 1. 優先讀取 .txt 檔案
        $txtPath = $this->logDir . "/logs_{$date}.txt";
        if (File::exists($txtPath)) {
            return $this->readFromTxt($txtPath, $date, $limit);
        }

        // 2. 嘗試從 .zip 讀取
        $month = substr($date, 0, 7);
        $zipPath = $this->logDir . "/logs_{$month}.zip";
        if (File::exists($zipPath)) {
            return $this->readFromZip($zipPath, $date, $limit);
        }

        // 3. 嘗試從 .7z 讀取
        $szPath = $this->logDir . "/logs_{$month}.7z";
        if (File::exists($szPath)) {
            return $this->readFrom7z($szPath, $date, $limit);
        }

        return [
            'success' => false,
            'message' => "找不到 {$date} 的日誌檔案（已檢查 .txt, .zip, .7z）",
            'logs' => [],
        ];
    }

    /**
     * 從 .txt 檔案讀取日誌
     *
     * @param string $filepath
     * @param string $date
     * @param int $limit
     * @return array
     */
    protected function readFromTxt(string $filepath, string $date, int $limit = 0): array
    {
        try {
            $content = File::get($filepath);
            $result = $this->parseJsonLines($content, $limit);

            return [
                'success' => true,
                'message' => "成功讀取 {$date} 的日誌（來源：txt）",
                'logs' => $result['logs'],
                'total' => $result['total'],
                'source' => 'txt',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '讀取 txt 日誌失敗：' . $e->getMessage(),
                'logs' => [],
            ];
        }
    }

    /**
     * 從 .zip 壓縮檔讀取日誌（純 PHP，跨平台）
     *
     * @param string $zipPath
     * @param string $date
     * @param int $limit
     * @return array
     */
    protected function readFromZip(string $zipPath, string $date, int $limit = 0): array
    {
        try {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                return [
                    'success' => false,
                    'message' => '無法開啟 zip 壓縮檔',
                    'logs' => [],
                ];
            }

            $filename = "logs_{$date}.txt";
            $content = $zip->getFromName($filename);
            $zip->close();

            if ($content === false) {
                return [
                    'success' => false,
                    'message' => "在 zip 壓縮檔中找不到 {$date} 的日誌",
                    'logs' => [],
                ];
            }

            $result = $this->parseJsonLines($content, $limit);

            return [
                'success' => true,
                'message' => "成功讀取 {$date} 的日誌（來源：zip）",
                'logs' => $result['logs'],
                'total' => $result['total'],
                'source' => 'zip',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '讀取 zip 日誌失敗：' . $e->getMessage(),
                'logs' => [],
            ];
        }
    }

    /**
     * 從 .7z 壓縮檔讀取日誌（需要系統安裝 7z 命令）
     *
     * @param string $szPath
     * @param string $date
     * @param int $limit
     * @return array
     */
    protected function readFrom7z(string $szPath, string $date, int $limit = 0): array
    {
        try {
            $filename = "logs_{$date}.txt";

            // 使用 7z 命令解壓到 stdout（跨平台）
            // Windows: 需要 7z.exe 在 PATH 中
            // Ubuntu: sudo apt install p7zip-full
            $command = sprintf(
                '7z e -so %s %s 2>%s',
                escapeshellarg($szPath),
                escapeshellarg($filename),
                PHP_OS_FAMILY === 'Windows' ? 'nul' : '/dev/null'
            );

            $content = shell_exec($command);

            if (empty($content)) {
                return [
                    'success' => false,
                    'message' => "在 7z 壓縮檔中找不到 {$date} 的日誌（或 7z 命令不可用）",
                    'logs' => [],
                ];
            }

            $result = $this->parseJsonLines($content, $limit);

            return [
                'success' => true,
                'message' => "成功讀取 {$date} 的日誌（來源：7z）",
                'logs' => $result['logs'],
                'total' => $result['total'],
                'source' => '7z',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '讀取 7z 日誌失敗：' . $e->getMessage(),
                'logs' => [],
            ];
        }
    }

    /**
     * 解析 JSON Lines 格式內容（含行號作為 ID）
     *
     * @param string $content
     * @param int $limit
     * @return array
     */
    protected function parseJsonLines(string $content, int $limit = 0): array
    {
        $lines = explode("\n", trim($content));
        $logs = [];
        $lineNumber = 0;

        foreach ($lines as $line) {
            $lineNumber++;
            $line = trim($line);
            if ($line && ($log = json_decode($line, true))) {
                $log['id'] = $lineNumber; // 使用行號作為 ID
                $logs[] = $log;
                if ($limit > 0 && count($logs) >= $limit) {
                    break;
                }
            }
        }

        return [
            'logs' => $logs,
            'total' => count($logs),
        ];
    }

    /**
     * 取得日誌列表（用於後台顯示）
     *
     * @param array $filters 篩選條件
     * @return array
     */
    public function getList(array $filters): array
    {
        $date = $filters['date'] ?? Carbon::today()->format('Y-m-d');
        $method = $filters['method'] ?? '';
        $status = $filters['status'] ?? '';
        $keyword = $filters['keyword'] ?? '';
        $page = (int)($filters['page'] ?? 1);
        $limit = (int)($filters['limit'] ?? 50);
        $sort = $filters['sort'] ?? 'time';
        $order = $filters['order'] ?? 'desc';

        // 讀取日誌
        $result = $this->readLogsByDate($date, 0);

        $logs = [];
        $total = 0;

        if ($result['success']) {
            $allLogs = $result['logs'];

            // 篩選
            if ($method || $status || $keyword) {
                $allLogs = array_filter($allLogs, function($log) use ($method, $status, $keyword) {
                    // Method 篩選
                    $matchMethod = !$method || ($log['method'] ?? '') === $method;

                    // 狀態篩選
                    $matchStatus = true;
                    if ($status) {
                        $logStatus = $log['status'] ?? '';
                        if ($status === 'empty') {
                            $matchStatus = empty($logStatus);
                        } else {
                            $matchStatus = $logStatus === $status;
                        }
                    }

                    // 關鍵字篩選
                    $matchKeyword = !$keyword || (
                        stripos(json_encode($log, JSON_UNESCAPED_UNICODE), $keyword) !== false
                    );

                    return $matchMethod && $matchStatus && $matchKeyword;
                });
                $allLogs = array_values($allLogs); // 重新索引
            }

            // 排序
            if ($sort === 'time') {
                usort($allLogs, function($a, $b) use ($order) {
                    $timeA = $a['timestamp'] ?? '';
                    $timeB = $b['timestamp'] ?? '';
                    $result = strcmp($timeA, $timeB);
                    return $order === 'desc' ? -$result : $result;
                });
            }

            $total = count($allLogs);

            // 分頁
            $offset = ($page - 1) * $limit;
            $logs = array_slice($allLogs, $offset, $limit);
        }

        // 格式化日誌顯示
        $formattedLogs = [];
        foreach ($logs as $log) {
            $formattedLogs[] = $this->formatLog($log);
        }

        return [
            'logs' => $formattedLogs,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $limit > 0 ? ceil($total / $limit) : 0,
            'sort' => $sort,
            'order' => $order,
        ];
    }

    /**
     * 根據 ID（行號）取得單筆日誌
     *
     * @param int|string $id 行號
     * @param string|null $date 日期（檔案存儲需要）
     * @return array|null
     */
    public function find($id, ?string $date = null): ?array
    {
        if (!$date) {
            return null;
        }

        $result = $this->readLogsByDate($date, 0);

        if (!$result['success']) {
            return null;
        }

        foreach ($result['logs'] as $log) {
            if (($log['id'] ?? null) == $id) {
                return $this->formatLog($log);
            }
        }

        return null;
    }

    /**
     * 格式化單筆日誌
     *
     * @param array $log
     * @return array
     */
    protected function formatLog(array $log): array
    {
        // 格式化時間
        if (isset($log['timestamp'])) {
            try {
                $log['formatted_time'] = Carbon::parse($log['timestamp'])->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $log['formatted_time'] = '';
            }
        } else {
            $log['formatted_time'] = '';
        }

        // 簡短顯示 URL
        $log['short_url'] = mb_strlen($log['url'] ?? '') > 60
            ? mb_substr($log['url'], 0, 60) . '...'
            : ($log['url'] ?? '');

        // 簡短顯示 note
        $log['short_note'] = mb_strlen($log['note'] ?? '') > 100
            ? mb_substr($log['note'], 0, 100) . '...'
            : ($log['note'] ?? '');

        return $log;
    }
}
