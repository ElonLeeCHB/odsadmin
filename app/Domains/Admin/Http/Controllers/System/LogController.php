<?php

namespace App\Domains\Admin\Http\Controllers\System;

use Illuminate\Http\Request;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Repositories\LogToDbRepository;
use Carbon\Carbon;

class LogController extends BackendController
{
    public function __construct(
        private Request $request,
        private LogToDbRepository $logRepository,
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
        $data['filter_date'] = $this->request->get('filter_date', '');
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
        $filters = [
            'date' => $this->request->get('filter_date', ''),
            'method' => $this->request->get('filter_method', ''),
            'status' => $this->request->get('filter_status', ''),
            'keyword' => $this->request->get('filter_keyword', ''),
            'page' => (int)$this->request->get('page', 1),
            'limit' => (int)$this->request->get('limit', 10),
            'sort' => $this->request->get('sort', 'time'),
            'order' => $this->request->get('order', 'desc'),
        ];

        // 從 Repository 取得資料（已格式化）
        $result = $this->logRepository->getList($filters);

        $data['logs'] = $result['logs'];
        $data['total'] = $result['total'];
        $data['page'] = $result['page'];
        $data['limit'] = $result['limit'];
        $data['total_pages'] = $result['total_pages'];
        $data['sort'] = $result['sort'];
        $data['order'] = $result['order'];

        // 分頁 URL
        $query_data = [
            'filter_date' => $filters['date'],
            'filter_method' => $filters['method'],
            'filter_status' => $filters['status'],
            'filter_keyword' => $filters['keyword'],
            'limit' => $filters['limit'],
            'sort' => $filters['sort'],
            'order' => $filters['order'],
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

        // 取得參數
        $id = $this->request->get('id');

        // 從 Repository 取得單筆日誌
        $log = $this->logRepository->find($id);

        if (!$log) {
            return response()->json(['error' => '找不到日誌'], 404);
        }

        $data['log'] = $log;
        $data['log_json'] = json_encode($log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return view('admin.system.log_form', $data);
    }
}
