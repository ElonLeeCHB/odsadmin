<?php

namespace App\Domains\Admin\Http\Controllers\System;

use Illuminate\Http\Request;
use App\Domains\Admin\Http\Controllers\BackendController;
use ZipArchive;
use Carbon\Carbon;

class ArchivedLogController extends BackendController
{
    protected string $archivePath;

    public function __construct(
        private Request $request,
    ) {
        parent::__construct();
        $this->getLang(['admin/common/common']);
        $this->archivePath = storage_path('logs/archived');
    }

    /**
     * 歷史日誌主頁面
     */
    public function index()
    {
        $data['lang'] = $this->lang;

        // Breadcrumb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => '系統',
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => '歷史日誌',
            'href' => route('lang.admin.system.logs.archived.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // 取得所有壓縮檔清單
        $data['archives'] = $this->getArchiveList();

        // 篩選參數
        $data['filter_month'] = $this->request->get('filter_month', '');
        $data['filter_date'] = $this->request->get('filter_date', '');
        $data['filter_method'] = $this->request->get('filter_method', '');
        $data['filter_status'] = $this->request->get('filter_status', '');
        $data['filter_keyword'] = $this->request->get('filter_keyword', '');

        // 初始載入列表（如果有選月份）
        $data['list'] = $data['filter_month'] ? $this->getList() : '';

        $data['list_url'] = route('lang.admin.system.logs.archived.list');

        return view('admin.system.log_archived', $data);
    }

    /**
     * 歷史日誌列表（AJAX）
     */
    public function list()
    {
        return $this->getList();
    }

    /**
     * 取得壓縮檔清單
     */
    private function getArchiveList(): array
    {
        $archives = [];

        if (!is_dir($this->archivePath)) {
            return $archives;
        }

        $files = glob($this->archivePath . '/logs_*.zip');

        foreach ($files as $file) {
            $filename = basename($file);
            // 從檔名取得月份 logs_2025-12.zip -> 2025-12
            if (preg_match('/logs_(\d{4}-\d{2})\.zip/', $filename, $matches)) {
                $archives[] = [
                    'month' => $matches[1],
                    'filename' => $filename,
                    'size' => filesize($file),
                    'size_formatted' => $this->formatBytes(filesize($file)),
                    'modified' => date('Y-m-d H:i:s', filemtime($file)),
                ];
            }
        }

        // 按月份排序（新到舊）
        usort($archives, fn($a, $b) => strcmp($b['month'], $a['month']));

        return $archives;
    }

    /**
     * 取得歷史日誌列表
     */
    private function getList()
    {
        $data['lang'] = $this->lang;

        $month = $this->request->get('filter_month', '');
        $date = $this->request->get('filter_date', '');
        $method = $this->request->get('filter_method', '');
        $status = $this->request->get('filter_status', '');
        $keyword = $this->request->get('filter_keyword', '');
        $page = (int)$this->request->get('page', 1);
        $limit = (int)$this->request->get('limit', 10);
        $sort = $this->request->get('sort', 'time');
        $order = $this->request->get('order', 'desc');

        if (!$month) {
            $data['logs'] = [];
            $data['total'] = 0;
            $data['page'] = 1;
            $data['limit'] = $limit;
            $data['total_pages'] = 0;
            $data['sort'] = $sort;
            $data['order'] = $order;
            $data['pagination_url'] = '';
            $data['list_url'] = route('lang.admin.system.logs.archived.list');
            return view('admin.system.log_archived_list', $data);
        }

        // 從 ZIP 讀取日誌
        $allLogs = $this->readLogsFromZip($month);

        // 篩選
        $filteredLogs = array_filter($allLogs, function($log) use ($date, $method, $status, $keyword) {
            // 日期篩選
            if ($date && isset($log['created_at'])) {
                $logDate = substr($log['created_at'], 0, 10);
                if ($logDate !== $date) {
                    return false;
                }
            }

            // Method 篩選
            if ($method && ($log['method'] ?? '') !== $method) {
                return false;
            }

            // 狀態篩選
            if ($status) {
                $logStatus = $log['status'] ?? '';
                if ($status === 'empty') {
                    if ($logStatus !== '' && $logStatus !== null) {
                        return false;
                    }
                } elseif ($logStatus !== $status) {
                    return false;
                }
            }

            // 關鍵字篩選
            if ($keyword) {
                $searchFields = [
                    $log['url'] ?? '',
                    $log['note'] ?? '',
                    json_encode($log['data'] ?? []),
                    $log['request_trace_id'] ?? '',
                ];
                $found = false;
                foreach ($searchFields as $field) {
                    if (stripos($field, $keyword) !== false) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    return false;
                }
            }

            return true;
        });

        // 排序
        usort($filteredLogs, function($a, $b) use ($sort, $order) {
            $aTime = $a['created_at'] ?? '';
            $bTime = $b['created_at'] ?? '';
            $cmp = strcmp($aTime, $bTime);
            return $order === 'desc' ? -$cmp : $cmp;
        });

        $total = count($filteredLogs);
        $totalPages = $limit > 0 ? ceil($total / $limit) : 0;

        // 分頁
        $offset = ($page - 1) * $limit;
        $pagedLogs = array_slice($filteredLogs, $offset, $limit);

        // 格式化
        $formattedLogs = [];
        foreach ($pagedLogs as $log) {
            $formattedLogs[] = $this->formatLog($log);
        }

        $data['logs'] = $formattedLogs;
        $data['total'] = $total;
        $data['page'] = $page;
        $data['limit'] = $limit;
        $data['total_pages'] = $totalPages;
        $data['sort'] = $sort;
        $data['order'] = $order;

        // 分頁 URL
        $query_data = [
            'filter_month' => $month,
            'filter_date' => $date,
            'filter_method' => $method,
            'filter_status' => $status,
            'filter_keyword' => $keyword,
            'limit' => $limit,
            'sort' => $sort,
            'order' => $order,
        ];

        $data['pagination_url'] = route('lang.admin.system.logs.archived.list') . '?' . http_build_query($query_data);
        $data['list_url'] = route('lang.admin.system.logs.archived.list');

        return view('admin.system.log_archived_list', $data);
    }

    /**
     * 從 ZIP 讀取日誌
     */
    private function readLogsFromZip(string $month): array
    {
        $zipFile = $this->archivePath . "/logs_{$month}.zip";

        if (!file_exists($zipFile)) {
            return [];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            return [];
        }

        $jsonlFilename = "logs_{$month}.jsonl";
        $content = $zip->getFromName($jsonlFilename);
        $zip->close();

        if ($content === false) {
            return [];
        }

        $logs = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $log = json_decode($line, true);
            if ($log !== null) {
                $logs[] = $log;
            }
        }

        return $logs;
    }

    /**
     * 歷史日誌詳情
     */
    public function form()
    {
        $data['lang'] = $this->lang;

        // Breadcrumb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => '系統',
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => '歷史日誌',
            'href' => route('lang.admin.system.logs.archived.index'),
        ];

        $breadcumbs[] = (object)[
            'text' => '日誌詳情',
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // 取得 archive_id
        $archiveId = $this->request->get('archive_id');

        if (!$archiveId) {
            return response()->json(['error' => '缺少 archive_id 參數'], 400);
        }

        // 從 archive_id 反推月份
        // 格式：2025-12-20_12-20-33_000001 -> 2025-12
        if (!preg_match('/^(\d{4}-\d{2})-\d{2}_\d{2}-\d{2}-\d{2}_\d{6}$/', $archiveId, $matches)) {
            return response()->json(['error' => '無效的 archive_id 格式'], 400);
        }

        $month = $matches[1];

        // 從 ZIP 讀取日誌並找到對應的記錄
        $logs = $this->readLogsFromZip($month);
        $log = null;

        foreach ($logs as $item) {
            if (($item['archive_id'] ?? '') === $archiveId) {
                $log = $item;
                break;
            }
        }

        if (!$log) {
            return response()->json(['error' => '找不到日誌'], 404);
        }

        $data['log'] = $this->formatLog($log);
        $data['log_json'] = json_encode($log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return view('admin.system.log_archived_form', $data);
    }

    /**
     * 格式化單筆日誌
     */
    protected function formatLog(array $log): array
    {
        // 格式化時間
        $log['formatted_time'] = $log['created_at'] ?? '';
        $log['timestamp'] = $log['created_at'] ?? '';

        // 簡短顯示 URL
        $url = $log['url'] ?? '';
        $log['short_url'] = mb_strlen($url) > 60
            ? mb_substr($url, 0, 60) . '...'
            : $url;

        // 簡短顯示 note
        $note = $log['note'] ?? '';
        $log['short_note'] = mb_strlen($note) > 100
            ? mb_substr($note, 0, 100) . '...'
            : $note;

        return $log;
    }

    /**
     * 格式化位元組大小
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
