<?php

namespace App\Repositories;

use App\Models\System\Log;
use Carbon\Carbon;

/**
 * 資料庫日誌存儲庫
 *
 * 功能：
 * - 將日誌寫入資料庫（sysdata 連線的 logs 資料表）
 * - 支援 SQL 查詢、篩選、統計
 * - 提供清理舊日誌的功能
 */
class LogToDbRepository
{
    /**
     * 記錄日誌（通用方法）
     *
     * @param array $params
     * @return bool
     */
    public function log(array $params): bool
    {
        try {
            Log::create([
                'request_trace_id' => $params['request_trace_id'] ?? app('request_trace_id'),
                'area' => config('app.env'),
                'url' => $params['url'] ?? '',
                'method' => $params['method'] ?? '',
                'data' => $params['data'] ?? [],
                'status' => $params['status'] ?? '',
                'note' => $params['note'] ?? '',
                'client_ip' => $this->getClientIp(),
                'api_ip' => request()->ip(),
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * 記錄請求日誌
     *
     * @param string|array $note
     * @return bool
     */
    public function logRequest($note = ''): bool
    {
        try {
            // 讀取請求資料
            if (request()->isJson()) {
                $json = json_decode(request()->getContent(), true);
                $data = $json ?? [];
            } else {
                $data = request()->all();
            }

            // 過濾敏感資料（密碼等）
            $data = $this->filterSensitiveData($data);

            // 過濾超過 1MB 的欄位（如 base64 圖片）
            $data = $this->filterLargeData($data);

            Log::create([
                'request_trace_id' => app('request_trace_id'),
                'area' => config('app.env'),
                'url' => request()->fullUrl() ?? '',
                'method' => request()->method() ?? '',
                'data' => $data,
                'status' => '',
                'note' => is_array($note) ? json_encode($note, JSON_UNESCAPED_UNICODE) : $note,
                'client_ip' => $this->getClientIp(),
                'api_ip' => request()->ip(),
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * 記錄錯誤日誌（在請求之後）
     * 在 middleware 會先用 logRequest() 記錄請求，這裡用來記錄錯誤。
     * 所以這裡的 data 不應包含請求資料，而是錯誤訊息。
     *
     * @param array $params
     * @return bool
     */
    public function logErrorAfterRequest(array $params): bool
    {
        try {
            Log::create([
                'request_trace_id' => app('request_trace_id'),
                'area' => config('app.env'),
                'url' => '',
                'method' => '',
                'data' => $params['data'] ?? [],
                'status' => $params['status'] ?? 'error',
                'note' => $params['note'] ?? '',
                'client_ip' => '',
                'api_ip' => '',
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logError($e);
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
     * 過濾超過大小限制的欄位及 Base64 圖片
     *
     * @param array $data
     * @param int $maxBytes 單一欄位最大位元組數（預設 1MB）
     * @return array
     */
    protected function filterLargeData(array $data, int $maxBytes = 1048576): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->filterLargeData($value, $maxBytes);
            } elseif (is_string($value)) {
                // 檢測 Base64 圖片
                $imageInfo = $this->detectBase64Image($value);
                if ($imageInfo) {
                    $data[$key] = "***BASE64_IMAGE:{$imageInfo['type']},{$imageInfo['size']}***";
                } elseif (strlen($value) > $maxBytes) {
                    // 超過大小限制
                    $data[$key] = '***TRUNCATED:' . $this->formatBytes(strlen($value)) . '***';
                }
            }
        }

        return $data;
    }

    /**
     * 檢測字串是否為 Base64 編碼的圖片
     *
     * @param string $value
     * @return array|null 返回 ['type' => 'png', 'size' => '1.5MB'] 或 null
     */
    protected function detectBase64Image(string $value): ?array
    {
        // 最小長度檢查（避免對短字串做複雜檢測）
        if (strlen($value) < 100) {
            return null;
        }

        // 格式 1: Data URI (data:image/png;base64,...)
        if (preg_match('/^data:image\/(\w+);base64,(.+)$/i', $value, $matches)) {
            $imageType = strtolower($matches[1]);
            $base64Data = $matches[2];
            $decodedSize = (int)(strlen($base64Data) * 3 / 4);

            return [
                'type' => $imageType,
                'size' => $this->formatBytes($decodedSize),
            ];
        }

        // 格式 2: 純 Base64 字串（檢測常見圖片格式的 magic bytes）
        // 只對較長的字串做檢測（可能是圖片）
        if (strlen($value) > 1000 && preg_match('/^[A-Za-z0-9+\/]+=*$/', substr($value, 0, 100))) {
            $decoded = base64_decode(substr($value, 0, 20), true);
            if ($decoded !== false) {
                $imageType = $this->detectImageTypeFromBytes($decoded);
                if ($imageType) {
                    $decodedSize = (int)(strlen($value) * 3 / 4);
                    return [
                        'type' => $imageType,
                        'size' => $this->formatBytes($decodedSize),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * 從二進位資料的 magic bytes 判斷圖片類型
     *
     * @param string $bytes
     * @return string|null
     */
    protected function detectImageTypeFromBytes(string $bytes): ?string
    {
        // JPEG: FF D8 FF
        if (substr($bytes, 0, 3) === "\xFF\xD8\xFF") {
            return 'jpeg';
        }

        // PNG: 89 50 4E 47
        if (substr($bytes, 0, 4) === "\x89PNG") {
            return 'png';
        }

        // GIF: 47 49 46 38
        if (substr($bytes, 0, 4) === "GIF8") {
            return 'gif';
        }

        // WebP: 52 49 46 46 ... 57 45 42 50
        if (substr($bytes, 0, 4) === "RIFF" && strlen($bytes) >= 12 && substr($bytes, 8, 4) === "WEBP") {
            return 'webp';
        }

        // BMP: 42 4D
        if (substr($bytes, 0, 2) === "BM") {
            return 'bmp';
        }

        return null;
    }

    /**
     * 格式化位元組大小
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . 'MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . 'KB';
        }
        return $bytes . 'B';
    }

    /**
     * 記錄錯誤到 error_log（當資料庫寫入失敗時）
     *
     * @param \Exception $e
     * @return void
     */
    protected function logError(\Exception $e): void
    {
        try {
            error_log('LogToDbRepository: 寫入日誌失敗 - ' . $e->getMessage());
        } catch (\Exception $innerException) {
            // 完全失敗，靜默處理
        }
    }

    /**
     * 讀取指定日期的日誌
     *
     * @param string $date 格式：Y-m-d
     * @param int $limit 限制筆數（0 = 不限制）
     * @return array
     */
    public function readLogsByDate(string $date, int $limit = 0): array
    {
        try {
            $query = Log::whereDate('created_at', $date)
                ->orderBy('created_at', 'desc');

            if ($limit > 0) {
                $query->limit($limit);
            }

            $logs = $query->get();

            return [
                'success' => true,
                'message' => "成功讀取 {$date} 的日誌",
                'logs' => $logs->toArray(),
                'total' => $logs->count(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '讀取日誌失敗：' . $e->getMessage(),
                'logs' => [],
            ];
        }
    }

    /**
     * 查詢日誌（支援多條件篩選）
     *
     * @param array $filters 篩選條件
     * @param int $perPage 每頁筆數
     * @return array
     */
    public function query(array $filters = [], int $perPage = 50): array
    {
        try {
            $query = Log::query()->orderBy('created_at', 'desc');

            // 日期範圍
            if (!empty($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
            }

            // 狀態篩選
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // 請求方法
            if (!empty($filters['method'])) {
                $query->where('method', $filters['method']);
            }

            // URL 關鍵字
            if (!empty($filters['url'])) {
                $query->where('url', 'like', '%' . $filters['url'] . '%');
            }

            // request_trace_id
            if (!empty($filters['request_trace_id'])) {
                $query->where('request_trace_id', $filters['request_trace_id']);
            }

            // IP 篩選
            if (!empty($filters['client_ip'])) {
                $query->where('client_ip', $filters['client_ip']);
            }

            $paginator = $query->paginate($perPage);

            return [
                'success' => true,
                'logs' => $paginator->items(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '查詢日誌失敗：' . $e->getMessage(),
                'logs' => [],
            ];
        }
    }

    /**
     * 統計日誌數量
     *
     * @param string|null $dateFrom 開始日期
     * @param string|null $dateTo 結束日期
     * @return array
     */
    public function getStats(string $dateFrom = null, string $dateTo = null): array
    {
        try {
            $query = Log::query();

            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->where('created_at', '<=', $dateTo . ' 23:59:59');
            }

            $total = $query->count();

            // 按狀態統計
            $byStatus = (clone $query)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // 按方法統計
            $byMethod = (clone $query)
                ->selectRaw('method, COUNT(*) as count')
                ->groupBy('method')
                ->pluck('count', 'method')
                ->toArray();

            return [
                'success' => true,
                'total' => $total,
                'by_status' => $byStatus,
                'by_method' => $byMethod,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '統計失敗：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 清理舊日誌（保留指定天數）
     *
     * @param int $keepDays 保留天數
     * @return array
     */
    public function cleanOldLogs(int $keepDays = 90): array
    {
        try {
            $cutoffDate = Carbon::now()->subDays($keepDays)->startOfDay();

            $deleted = Log::where('created_at', '<', $cutoffDate)->delete();

            return [
                'success' => true,
                'message' => "成功刪除 {$deleted} 筆舊日誌（{$keepDays} 天前）",
                'deleted_count' => $deleted,
                'cutoff_date' => $cutoffDate->toDateString(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '清理日誌失敗：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 列出日誌統計（按日期）
     *
     * @param int $days 統計天數
     * @return array
     */
    public function listLogsByDate(int $days = 30): array
    {
        try {
            $startDate = Carbon::now()->subDays($days)->startOfDay();

            $stats = Log::where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get()
                ->toArray();

            return [
                'success' => true,
                'stats' => $stats,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '統計失敗：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 取得日誌列表（用於後台顯示）
     *
     * @param array $filters 篩選條件
     * @return array
     */
    public function getList(array $filters): array
    {
        $date = $filters['date'] ?? '';
        $method = $filters['method'] ?? '';
        $status = $filters['status'] ?? '';
        $keyword = $filters['keyword'] ?? '';
        $page = (int)($filters['page'] ?? 1);
        $limit = (int)($filters['limit'] ?? 10);
        $sort = $filters['sort'] ?? 'time';
        $order = $filters['order'] ?? 'desc';

        // 建立查詢
        $query = Log::query();

        // 日期篩選（有指定才篩選）
        if ($date) {
            $query->whereDate('created_at', $date);
        }

        // Method 篩選
        if ($method) {
            $query->where('method', $method);
        }

        // 狀態篩選
        if ($status) {
            if ($status === 'empty') {
                $query->where(function($q) {
                    $q->whereNull('status')->orWhere('status', '');
                });
            } else {
                $query->where('status', $status);
            }
        }

        // 關鍵字篩選
        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('url', 'like', "%{$keyword}%")
                  ->orWhere('note', 'like', "%{$keyword}%")
                  ->orWhere('data', 'like', "%{$keyword}%")
                  ->orWhere('request_trace_id', 'like', "%{$keyword}%");
            });
        }

        // 排序
        $sortColumn = $sort === 'time' ? 'created_at' : $sort;
        $query->orderBy($sortColumn, $order);

        // 取得總數
        $total = $query->count();

        // 分頁
        $logs = $query->skip(($page - 1) * $limit)->take($limit)->get();

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
     * 根據 ID 取得單筆日誌
     *
     * @param int|string $id
     * @return array|null
     */
    public function find($id): ?array
    {
        $log = Log::find($id);

        if (!$log) {
            return null;
        }

        return $this->formatLog($log);
    }

    /**
     * 格式化單筆日誌
     *
     * @param Log $log
     * @return array
     */
    protected function formatLog(Log $log): array
    {
        $formatted = $log->toArray();

        // 格式化時間
        $formatted['formatted_time'] = $log->created_at
            ? $log->created_at->format('Y-m-d H:i:s')
            : '';

        // 簡短顯示 URL
        $formatted['short_url'] = mb_strlen($log->url ?? '') > 60
            ? mb_substr($log->url, 0, 60) . '...'
            : ($log->url ?? '');

        // 簡短顯示 note
        $formatted['short_note'] = mb_strlen($log->note ?? '') > 100
            ? mb_substr($log->note, 0, 100) . '...'
            : ($log->note ?? '');

        return $formatted;
    }
}
