<?php

namespace App\Domains\Admin\Http\Controllers\System;

use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\System\StoreService;
use Illuminate\Http\Request;

class StoreController extends BackendController
{
    public function __construct(
        protected Request $request,
        protected StoreService $StoreService
    ) {
        parent::__construct();
        $this->getLang(['admin/common/common', 'admin/system/store']);
    }

    public function index()
    {
        $data['lang'] = $this->lang;
        $query_data = $this->url_data ?? [];

        // Breadcrumb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home ?? '首頁',
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => '系統管理',
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => '門市管理',
            'href' => route('lang.admin.system.stores.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;
        $data['list'] = $this->getList();
        $data['list_url'] = route('lang.admin.system.stores.list');
        $data['add_url'] = route('lang.admin.system.stores.form');
        $data['delete_url'] = route('lang.admin.system.stores.destroy');

        // Filters
        $data['filter_name'] = $query_data['filter_name'] ?? '';
        $data['filter_code'] = $query_data['filter_code'] ?? '';

        return view('admin.system.store', $data);
    }

    public function list()
    {
        $data['lang'] = $this->lang;
        $data['form_action'] = route('lang.admin.system.stores.list');

        return $this->getList();
    }

    private function getList()
    {
        $data['lang'] = $this->lang;
        $query_data = $this->url_data ?? [];

        $stores = $this->StoreService->getStores($query_data);

        if (!empty($stores)) {
            foreach ($stores as $row) {
                $row->edit_url = route('lang.admin.system.stores.form',
                    array_merge([$row->id], $query_data));

                // 取得店長名稱
                if ($row->manager_id && $row->manager) {
                    $row->manager_name = $row->manager->name;
                } else {
                    $row->manager_name = '';
                }

                // 取得縣市名稱
                if ($row->state_id && $row->state) {
                    $row->state_name = $row->state->name;
                } else {
                    $row->state_name = '';
                }

                // 取得鄉鎮市區名稱
                if ($row->city_id && $row->city) {
                    $row->city_name = $row->city->name;
                } else {
                    $row->city_name = '';
                }
            }
        }

        $data['stores'] = $stores->withPath(route('lang.admin.system.stores.list'))
                                  ->appends($query_data);

        // 排序連結
        $sort = $query_data['sort'] ?? 'id';
        $order = $query_data['order'] ?? 'ASC';
        $next_order = ($order == 'ASC') ? 'DESC' : 'ASC';

        $data['sort'] = $sort;
        $data['order'] = strtolower($order);

        $url_params = $query_data;
        unset($url_params['sort']);
        unset($url_params['order']);

        $url = '';
        foreach ($url_params as $key => $value) {
            if (is_string($value)) {
                $url .= "&$key=$value";
            }
        }

        $route = route('lang.admin.system.stores.list');
        $data['sort_id'] = $route . "?sort=id&order=$next_order" . $url;
        $data['sort_code'] = $route . "?sort=code&order=$next_order" . $url;
        $data['sort_name'] = $route . "?sort=name&order=$next_order" . $url;

        $data['list_url'] = route('lang.admin.system.stores.list');

        return view('admin.system.store_list', $data);
    }

    public function form($store_id = null)
    {
        $data['lang'] = $this->lang;
        $this->lang->text_form = empty($store_id) ? '新增' : '編輯';

        // Breadcrumb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home ?? '首頁',
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => '系統管理',
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => '門市管理',
            'href' => route('lang.admin.system.stores.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $queries = [];
        foreach ($this->request->all() as $key => $value) {
            if (strpos($key, 'filter_') !== false) {
                $queries[$key] = $value;
            }
        }

        $data['save'] = route('lang.admin.system.stores.save',
            $store_id ? [$store_id] : []);
        $data['back'] = route('lang.admin.system.stores.index', $queries);

        $store = $this->StoreService->findOrFailOrNew($store_id);

        $data['store'] = $store;
        $data['store_id'] = $store_id;

        // 取得縣市列表
        $data['states'] = \App\Models\SysData\Division::where('level', 1)
            ->where('country_code', 'tw')
            ->orderBy('sort_order', 'ASC')
            ->get();

        // 如果是編輯且有縣市，載入該縣市下的鄉鎮市區
        $data['cities'] = [];
        if ($store_id && $store->state_id) {
            $data['cities'] = \App\Models\SysData\Division::where('level', 2)
                ->where('parent_id', $store->state_id)
                ->orderBy('sort_order', 'ASC')
                ->get();
        }

        // AJAX 取得鄉鎮市區列表的 URL
        $data['cities_list_url'] = route('lang.admin.localization.divisions.getJsonCities');

        return view('admin.system.store_form', $data);
    }

    public function save($store_id = null)
    {
        $data = $this->request->all();
        $data['store_id'] = $store_id;

        $json = [];

        // Validation
        $validator = $this->StoreService->validator($this->request->post());

        if ($validator->fails()) {
            $messages = $validator->errors()->toArray();
            foreach ($messages as $key => $rows) {
                $json['error'][$key] = $rows[0];
            }
        }

        if (isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = '請檢查輸入的內容';
        }

        if (!$json) {
            $result = $this->StoreService->updateOrCreate($data);

            if (empty($result['error'])) {
                $json['store_id'] = $result['data']['store_id'];
                $json['success'] = '儲存成功';
                $json['redirectUrl'] = route('lang.admin.system.stores.form',
                    $result['data']['store_id']);
            } else {
                $json['error'] = $result['error'];
            }
        }

        return response(json_encode($json))->header('Content-Type', 'application/json');
    }

    public function destroy()
    {
        $ids = $this->request->input('selected', []);
        $json = [];

        if (empty($ids)) {
            $json['error'] = '請選擇要刪除的項目';
            return response(json_encode($json))->header('Content-Type', 'application/json');
        }

        $result = $this->StoreService->destroy($ids);

        if (empty($result['error'])) {
            $json['success'] = '刪除成功';
        } else {
            $json['error'] = $result['error'];
        }

        return response(json_encode($json))->header('Content-Type', 'application/json');
    }
}
