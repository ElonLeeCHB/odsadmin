<?php

namespace App\Domains\Admin\Http\Controllers\System;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Repositories\LogFileRepository;
use Carbon\Carbon;

class LogController extends BackendController
{
    public function __construct(
        private Request $request,
        private LogFileRepository $logFileRepository
    ) {
        parent::__construct();

        $this->getLang(['admin/common/common']);
    }

    /**
     * 日誌主頁面
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
            'text' => '日誌查看',
            'href' => route('lang.admin.system.logs.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // 初始化篩選參數
        $data['filter_date'] = $this->request->get('filter_date', Carbon::today()->format('Y-m-d'));
        $data['filter_method'] = $this->request->get('filter_method', '');
        $data['filter_status'] = $this->request->get('filter_status', '');
        $data['filter_keyword'] = $this->request->get('filter_keyword', '');

        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.system.logs.list');

        return view('admin.system.log', $data);
    }

    /**
     * 日誌列表（AJAX）
     */
    public function list()
    {
        return $this->getList();
    }

    /**
     * 取得日誌列表
     */
    private function getList()
    {
        $data['lang'] = $this->lang;

        // 取得篩選參數
        $date = $this->request->get('filter_date', Carbon::today()->format('Y-m-d'));
        $method = $this->request->get('filter_method', '');
        $status = $this->request->get('filter_status', '');
        $keyword = $this->request->get('filter_keyword', '');
        $page = (int)$this->request->get('page', 1);
        $limit = (int)$this->request->get('limit', 50);
        $sort = $this->request->get('sort', 'time'); // 排序欄位
        $order = $this->request->get('order', 'desc'); // 排序方向，預設降序（由新到舊）

        // 讀取日誌
        $result = $this->logFileRepository->readLogsByDate($date, 0);

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
        foreach ($logs as &$log) {
            // 格式化時間
            if (isset($log['timestamp'])) {
                try {
                    $log['formatted_time'] = Carbon::parse($log['timestamp'])->format('H:i:s');
                } catch (\Exception $e) {
                    $log['formatted_time'] = '';
                }
            }

            // 簡短顯示 URL
            if (isset($log['url'])) {
                $log['short_url'] = mb_strlen($log['url']) > 60
                    ? mb_substr($log['url'], 0, 60) . '...'
                    : $log['url'];
            }

            // 簡短顯示 note
            if (isset($log['note'])) {
                $log['short_note'] = mb_strlen($log['note']) > 100
                    ? mb_substr($log['note'], 0, 100) . '...'
                    : $log['note'];
            }
        }

        $data['logs'] = $logs;
        $data['total'] = $total;
        $data['page'] = $page;
        $data['limit'] = $limit;
        $data['total_pages'] = $limit > 0 ? ceil($total / $limit) : 0;
        $data['sort'] = $sort;
        $data['order'] = $order;

        // 分頁 URL
        $query_data = [
            'filter_date' => $date,
            'filter_method' => $method,
            'filter_status' => $status,
            'filter_keyword' => $keyword,
            'limit' => $limit,
            'sort' => $sort,
            'order' => $order,
        ];

        $data['pagination_url'] = route('lang.admin.system.logs.list') . '?' . http_build_query($query_data);
        $data['list_url'] = route('lang.admin.system.logs.list');

        return view('admin.system.log_list', $data);
    }

    /**
     * 日誌詳情
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
            'text' => '日誌查看',
            'href' => route('lang.admin.system.logs.index'),
        ];

        $breadcumbs[] = (object)[
            'text' => '日誌詳情',
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $date = $this->request->get('date');
        $uniqueid = $this->request->get('uniqueid');

        if (!$date || !$uniqueid) {
            return response()->json(['error' => '參數錯誤'], 400);
        }

        // 讀取日誌
        $result = $this->logFileRepository->readLogsByDate($date, 0);

        $log = null;
        if ($result['success']) {
            foreach ($result['logs'] as $item) {
                if (($item['uniqueid'] ?? '') === $uniqueid) {
                    $log = $item;
                    break;
                }
            }
        }

        if (!$log) {
            return response()->json(['error' => '找不到日誌'], 404);
        }

        $data['log'] = $log;
        $data['log_json'] = json_encode($log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return view('admin.system.log_form', $data);
    }

    /**
     * 取得可用的日誌檔案列表
     */
    public function files()
    {
        $files = $this->logFileRepository->listLogFiles();

        return response()->json([
            'success' => true,
            'files' => $files,
        ]);
    }
}
