<?php

namespace App\Domains\Admin\Http\Controllers\System\Access;

use App\Domains\Admin\Http\Controllers\BackendController;
use Illuminate\Http\Request;
use App\Repositories\Access\UserRepository;
use App\Repositories\Access\RoleRepository;
use App\Repositories\Access\SystemUserRepository;

class UserController extends BackendController
{
    private $breadcumbs;

    public function __construct(
        protected Request $request,
        protected UserRepository $userRepo,
        protected RoleRepository $roleRepo,
        protected SystemUserRepository $systemUserRepo
    ) {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/admin/user']);
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
            'text' => '使用者管理',
            'href' => route('lang.admin.system.access.users.index'),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['lang'] = $this->lang;

        $query_data = $this->url_data;

        // Breadcrumb
        $data['breadcumbs'] = (object)$this->breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url']   =  route('lang.admin.system.access.users.list');
        $data['add_url']    = route('lang.admin.system.access.users.form');
        $data['delete_url'] = route('lang.admin.system.access.users.destroy');

        //Filters
        $data['filter_keyname'] = $query_data['filter_keyname'] ?? '';
        $data['filter_phone'] = $query_data['filter_phone'] ?? '';
        $data['equal_is_active'] = $query_data['equal_is_active'] ?? 1;

        // 角色列表
        $data['roles'] = $this->roleRepo->query()->orderBy('name')->get();

        return view('admin.system.access.user', $data);
    }

    public function list()
    {
        $data['lang'] = $this->lang;

        $data['form_action'] = route('lang.admin.system.access.users.list');

        return $this->getList();
    }

    /**
     * Show the list table.
     */
    protected function getList(): string
    {
        $data['lang'] = $this->lang;

        // ===== Query 與網址參數 =====
        $query = $this->systemUserRepo->query()->with(['user.metas']);
        $filter_data = $this->request->all();

        // ===== search 關鍵字查詢 =====
        if ($this->request->filled('filter_keyname')) {
            $search = $this->request->filter_keyname;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // ===== 角色篩選 =====
        if ($this->request->filled('filter_role_ids')) {
            $roleIds = explode(',', $this->request->filter_role_ids);
            $query->whereHas('user.roles', function ($q) use ($roleIds) {
                $q->whereIn('id', $roleIds);
            });
        }

        // ===== 預設 sort, order =====
        $sort = $this->request->get('sort', 'user_id');
        $order = $this->request->get('order', 'desc');
        $query->orderBy($sort, $order);

        // ===== 分頁 =====
        $limit = $this->request->get('limit', 10);
        $systemUsers = $query->paginate($limit);

        // 設置分頁路徑
        $systemUsers->withPath(route('lang.admin.system.access.users.list'));

        // 轉換為 user 資料並補充
        $url = $this->buildUrlParams($this->request);
        foreach ($systemUsers as $row) {
            $row->user->edit_url = route('lang.admin.system.access.users.form', [$row->user_id]) . $url;
        }

        $data['users'] = $systemUsers;
        $filter_data['sort'] = $sort;
        $filter_data['order'] = $order;

        // ===== 排序連結 =====
        $sort = $filter_data['sort'];
        $order = $filter_data['order'];
        $next_order = ($order == 'asc') ? 'desc' : 'asc';

        $data['sort'] = $sort;
        $data['order'] = $order;

        $base_url = route('lang.admin.system.access.users.list');
        $data['sort_username'] = $base_url . "?sort=username&order={$next_order}" . str_replace('?', '&', $url);
        $data['sort_name'] = $base_url . "?sort=name&order={$next_order}" . str_replace('?', '&', $url);
        $data['sort_email'] = $base_url . "?sort=email&order={$next_order}" . str_replace('?', '&', $url);
        $data['sort_created_at'] = $base_url . "?sort=created_at&order={$next_order}" . str_replace('?', '&', $url);

        $data['list_url'] = $base_url;

        return view('admin.system.access.user_list', $data)->render();
    }


    public function form($user_id = null)
    {
        $data['lang'] = $this->lang;

        $this->lang->text_form = empty($user_id) ? '新增' : '編輯';

        // Breadcrumb
        $data['breadcumbs'] = (object)$this->breadcumbs;

        // URLs
        $url = $this->buildUrlParams($this->request);

        $data['save_url'] = route('lang.admin.system.access.users.save');
        $data['back_url'] = route('lang.admin.system.access.users.index') . $url;

        // Get Record
        $user = $user_id
            ? $this->userRepo->query()->with(['metas'])->findOrFail($user_id)
            : $this->userRepo->getModel();

        // 將 metas 設定到 row
        if ($user->metas) {
            foreach ($user->metas as $meta) {
                $user->{$meta->meta_key} = $meta->meta_value;
            }
        }

        $data['user'] = $user;
        $data['user_id'] = $user_id;

        // 取得 system_user 記錄
        $data['system_user'] = $user_id
            ? $this->systemUserRepo->query()->where('user_id', $user_id)->first()
            : null;

        // 載入所有角色
        $data['roles'] = $this->roleRepo->query()->orderBy('name')->get();
        // 用戶已選角色 IDs
        $data['user_role_ids'] = $user_id ? $user->roles->pluck('id')->toArray() : [];

        return view('admin.system.access.user_form', $data);
    }


    public function save()
    {
        $input = $this->request->all();
        $user_id = $input['user_id'] ?? null;

        // 訪問控制只能編輯現有使用者，不能新增
        if (!$user_id) {
            return response()->json(['error' => '請從使用者列表選擇要編輯的使用者'], 422);
        }

        $user = $this->userRepo->query()->findOrFail($user_id);

        // 更新 system_users（只處理 is_active）
        $this->systemUserRepo->query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'user_code' => $user->code,
                'name' => $user->name,
                'is_active' => $input['is_active'] ?? 1,
            ]
        );

        // Sync roles (使用角色 ID 直接操作 pivot table，避免 guard_name 問題)
        $roleIds = $input['user_role'] ?? [];
        $user->roles()->sync($roleIds);

        return response()->json([
            'success' => '儲存成功',
            'user_id' => $user->id,
            'redirectUrl' => route('lang.admin.system.access.users.form', $user->id),
        ]);
    }


    public function destroy()
    {
        $ids = $this->request->input('selected', []);

        if (empty($ids)) {
            return response()->json(['error' => '請選擇要刪除的項目'], 400);
        }

        // Permission
        if ($this->acting_username !== 'admin') {
            return response()->json(['error' => $this->lang->error_permission], 403);
        }

        $this->userRepo->query()->whereIn('id', $ids)->each(function ($user) {
            $user->metas()->delete();
        });
        $this->userRepo->query()->whereIn('id', $ids)->delete();

        return response()->json(['success' => '刪除成功']);
    }



    public function autocomplete()
    {
        $query = $this->userRepo->query();

        if ($this->request->filled('filter_personal_name')) {
            $query->where('name', 'like', '%' . $this->request->filter_personal_name . '%');
        }
        if ($this->request->filled('filter_mobile')) {
            $query->where('mobile', 'like', '%' . $this->request->filter_mobile . '%');
        }
        if ($this->request->filled('filter_telephone')) {
            $query->where('telephone', 'like', '%' . $this->request->filter_telephone . '%');
        }
        if ($this->request->filled('filter_email')) {
            $query->where('email', 'like', '%' . $this->request->filter_email . '%');
        }

        $sort = $this->request->get('sort', 'name');
        $order = $this->request->get('order', 'asc');
        $query->orderBy($sort, $order);

        $users = $query->limit(20)->get();

        $json = [];
        foreach ($users as $row) {
            $show_text = $row->name . '_' . $row->mobile;
            if ($this->request->filled('show_column1') && $this->request->filled('show_column2')) {
                $col1 = $this->request->show_column1;
                $col2 = $this->request->show_column2;
                $show_text = $row->$col1 . '_' . $row->$col2;
            }

            $json[] = [
                'label' => $show_text,
                'value' => $row->id,
                'user_id' => $row->id,
                'personal_name' => $row->name,
                'salutation_id' => $row->salutation_id ?? null,
                'telephone' => $row->telephone ?? null,
                'mobile' => $row->mobile ?? null,
                'email' => $row->email ?? null,
            ];
        }

        return response()->json($json);
    }

    /**
     * 從帳號中心查詢使用者資料（AJAX）
     */
    public function fetchFromAccountCenter(AccountCenterService $accountService)
    {
        $code = $this->request->input('code');

        if (empty($code)) {
            return response()->json(['error' => '請輸入使用者編號'], 422);
        }

        try {
            $accountData = $accountService->fetchUserData($code);

            // 檢查本系統是否已存在
            $existingUser = $this->userRepo->query()->where('code', $code)->first();
            if ($existingUser) {
                return response()->json([
                    'error' => "使用者編號 {$code} 已存在於系統中",
                    'existing_user_id' => $existingUser->id,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $accountData,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * 建構 URL 參數字串
     */
    protected function buildUrlParams(Request $request): string
    {
        $params = [];
        $allowedFields = ['username', 'name', 'email', 'is_active', 'keyname', 'phone'];
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
