<?php

namespace App\Domains\Admin\Http\Controllers\System\Access;

use App\Domains\Admin\Http\Controllers\BackendController;
use App\Repositories\Access\RoleRepository;
use App\Models\Access\Role;
use App\Helpers\Classes\OrmHelper;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class RoleController extends BackendController
{
    private $breadcumbs;

    public function __construct(
        protected Request $request,
        protected RoleRepository $roleRepo
    ) {
        parent::__construct();
        $this->getLang(['admin/common/common']);
        $this->setBreadcumbs();
    }

    protected function setBreadcumbs()
    {
        $this->breadcumbs = [];

        $this->breadcumbs[] = (object)[
            'text' => '首頁',
            'href' => route('lang.admin.dashboard'),
        ];

        $this->breadcumbs[] = (object)[
            'text' => '系統管理',
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $this->breadcumbs[] = (object)[
            'text' => '訪問控制',
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $this->breadcumbs[] = (object)[
            'text' => '角色管理',
            'href' => route('lang.admin.system.access.roles.index'),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['lang'] = $this->lang;

        $data['breadcumbs'] = (object)$this->breadcumbs;

        $data['list'] = $this->getList($this->request);

        $data['list_url'] = route('lang.admin.system.access.roles.list');
        $data['add_url'] = route('lang.admin.system.access.roles.form');
        $data['delete_url'] = route('lang.admin.system.access.roles.destroy');

        return view('admin.system.access.role', $data);
    }

    /**
     * AJAX 請求入口 - 僅返回表格 HTML
     */
    public function list()
    {
        return $this->getList($this->request);
    }

    /**
     * 核心查詢邏輯 - 處理資料查詢並渲染表格部分
     */
    protected function getList(Request $request): string
    {
        $data['lang'] = $this->lang;

        // ===== Query 與網址參數 =====
        $query = $this->roleRepo->query()->with(['permissions']);
        $filter_data = $request->all();
        OrmHelper::prepare($query, $filter_data);

        // ===== search 關鍵字查詢 =====
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                OrmHelper::filterOrEqualColumn($q, 'filter_title', $search);
                $q->orWhere(function ($subQ) use ($search) {
                    OrmHelper::filterOrEqualColumn($subQ, 'filter_description', $search);
                });
            });
        }

        // ===== 額外關聯查詢（需要時）=====
        // 目前不需要

        // ===== 預設 sort, order =====
        $filter_data['sort'] = $request->get('sort', 'id');
        $filter_data['order'] = $request->get('order', 'asc');

        // ===== 使用 OrmHelper 獲取結果 =====
        $roles = OrmHelper::getResult($query, $filter_data);

        // 補充資料
        foreach ($roles as $row) {
            $row->edit_url = route('lang.admin.system.access.roles.form', [$row->id]) . $this->buildUrlParams($request);
            $row->permissions_count = $row->permissions->count();
        }

        // ===== buildUrlParams =====
        $url = $this->buildUrlParams($request);

        // ===== 準備資料 =====
        $data['roles'] = $roles;
        $data['list_url'] = route('lang.admin.system.access.roles.list');

        // 排序連結
        $sort = $filter_data['sort'];
        $order = $filter_data['order'];
        $next_order = ($order == 'asc') ? 'desc' : 'asc';

        $data['sort'] = $sort;
        $data['order'] = $order;

        $base_url = route('lang.admin.system.access.roles.list');
        $data['sort_id'] = $base_url . "?sort=id&order={$next_order}" . str_replace('?', '&', $url);
        $data['sort_name'] = $base_url . "?sort=name&order={$next_order}" . str_replace('?', '&', $url);

        return view('admin.system.access.role_list', $data)->render();
    }

    /**
     * Show form for create/edit
     */
    public function form($role_id = null)
    {
        $data['lang'] = $this->lang;
        $this->lang->text_form = empty($role_id) ? '新增' : '編輯';

        // Breadcrumb
        $data['breadcumbs'] = (object)$this->breadcumbs;

        // URLs
        $url = $this->buildUrlParams($this->request);
        $data['save'] = route('lang.admin.system.access.roles.save', $role_id ? [$role_id] : []);
        $data['back'] = route('lang.admin.system.access.roles.index') . $url;

        // Get Record
        $data['role'] = $role_id
            ? $this->roleRepo->query()->findOrFail($role_id)
            : $this->roleRepo->getModel();
        $data['role_id'] = $role_id;

        // Get all permissions
        $data['permissions'] = Permission::all();

        // Get role's permissions
        $data['role_permissions'] = $role_id
            ? $data['role']->permissions->pluck('id')->toArray()
            : [];

        return view('admin.system.access.role_form', $data);
    }

    /**
     * Save (create or update)
     */
    public function save($role_id = null)
    {
        $input = $this->request->all();

        // Validation
        $this->request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . ($role_id ?? 'NULL'),
        ], [
            'name.required' => '名稱為必填',
            'name.unique' => '此名稱已存在',
        ]);

        // Create or Update
        $data = [
            'name' => $input['name'],
            'title' => $input['title'] ?? $input['name'],
            'description' => $input['description'] ?? null,
            'guard_name' => $input['guard_name'] ?? 'web',
        ];

        $role = OrmHelper::save(Role::class, $data, $role_id);

        // Sync permissions
        if (isset($input['permissions'])) {
            $role->syncPermissions($input['permissions']);
        } else {
            $role->syncPermissions([]);
        }

        return response()->json([
            'success' => '儲存成功',
            'role_id' => $role->id,
            'redirectUrl' => route('lang.admin.system.access.roles.form', $role->id),
        ]);
    }

    /**
     * Delete selected roles
     */
    public function destroy()
    {
        $ids = $this->request->input('selected', []);

        if (empty($ids)) {
            return response()->json(['error' => '請選擇要刪除的項目'], 400);
        }

        $this->roleRepo->query()->whereIn('id', $ids)->each(function ($role) {
            $role->permissions()->detach();
        });
        $this->roleRepo->query()->whereIn('id', $ids)->delete();

        return response()->json(['success' => '刪除成功']);
    }

    /**
     * Autocomplete for role selection
     */
    public function autocomplete()
    {
        $query = $this->roleRepo->query();

        if ($this->request->filled('filter_name')) {
            $query->where(function ($q) {
                $search = $this->request->filter_name;
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('title', 'like', '%' . $search . '%');
            });
        }

        $roles = $query->orderBy('name', 'asc')->limit(20)->get();

        $json = [];
        foreach ($roles as $row) {
            $json[] = [
                'role_id' => $row->id,
                'name' => $row->title ?: $row->name,
            ];
        }

        return response()->json($json);
    }

    /**
     * 建構 URL 參數字串
     */
    protected function buildUrlParams(Request $request): string
    {
        $params = [];
        $allowedFields = ['name', 'title', 'description', 'is_active'];
        $prefixes = ['filter_', 'equal_'];

        foreach ($allowedFields as $field) {
            foreach ($prefixes as $prefix) {
                $key = $prefix . $field;
                if ($request->filled($key)) {
                    $params[] = $key . '=' . urlencode($request->get($key));
                }
            }
        }

        if ($request->filled('search')) {
            $params[] = 'search=' . urlencode($request->search);
        }

        if ($request->filled('limit')) {
            $params[] = 'limit=' . $request->limit;
        }
        if ($request->filled('page')) {
            $params[] = 'page=' . $request->page;
        }
        if ($request->filled('sort')) {
            $params[] = 'sort=' . urlencode($request->sort);
        }
        if ($request->filled('order')) {
            $params[] = 'order=' . urlencode($request->order);
        }

        return $params ? '?' . implode('&', $params) : '';
    }
}
