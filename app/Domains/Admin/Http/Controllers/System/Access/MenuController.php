<?php

namespace App\Domains\Admin\Http\Controllers\System\Access;

use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\System\Access\MenuManageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MenuController extends BackendController
{
    protected $breadcumbs;

    public function __construct(
        protected Request $request,
        protected MenuManageService $menuManageService
    ) {
        parent::__construct();
        $this->setBreadcumbs();
    }

    protected function setBreadcumbs(): void
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
            'text' => '選單管理',
            'href' => route('lang.admin.system.access.menus.index'),
        ];
    }

    /**
     * 選單管理主頁（樹狀圖）
     */
    public function index()
    {
        $data['breadcumbs'] = (object)$this->breadcumbs;

        // 預設顯示 admin 系統選單
        $data['current_system'] = $this->request->query('system', 'admin');
        $data['systems'] = [
            'admin' => '後台管理 (admin)',
            'pos' => 'POS 系統 (pos)',
            'www' => '官網 (www)',
        ];

        // API URLs
        $data['tree_url'] = route('lang.admin.system.access.menus.tree');
        $data['store_url'] = route('lang.admin.system.access.menus.store');
        $data['show_url'] = route('lang.admin.system.access.menus.show', ['id' => '__ID__']);
        $data['update_url'] = route('lang.admin.system.access.menus.update', ['id' => '__ID__']);
        $data['destroy_url'] = route('lang.admin.system.access.menus.destroy', ['id' => '__ID__']);
        $data['move_url'] = route('lang.admin.system.access.menus.move');
        $data['parents_url'] = route('lang.admin.system.access.menus.parents');

        return view('admin.system.access.menu', $data);
    }

    /**
     * 取得樹狀資料（jstree 格式）
     */
    public function tree(): JsonResponse
    {
        $system = $this->request->query('system', 'admin');

        $tree = $this->menuManageService->getJsTreeData($system);

        return response()->json($tree);
    }

    /**
     * 取得可選的父層選單
     */
    public function parents(): JsonResponse
    {
        $system = $this->request->query('system', 'admin');
        $excludeId = $this->request->query('exclude_id');

        $parents = $this->menuManageService->getParentOptions($system, $excludeId);

        $options = $parents->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'title' => $item->title,
                'level' => $item->getLevel(),
            ];
        });

        return response()->json($options);
    }

    /**
     * 取得單一選單詳情
     */
    public function show(int $id): JsonResponse
    {
        $menu = $this->menuManageService->find($id);

        if (!$menu) {
            return response()->json(['error' => '選單不存在'], 404);
        }

        return response()->json([
            'id' => $menu->id,
            'parent_id' => $menu->parent_id,
            'name' => $menu->name,
            'title' => $menu->title,
            'icon' => $menu->icon,
            'sort_order' => $menu->sort_order,
            'description' => $menu->description,
        ]);
    }

    /**
     * 新增選單
     */
    public function store(): JsonResponse
    {
        $data = $this->request->all();

        // 驗證
        $validator = $this->menuManageService->validator($data);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $result = $this->menuManageService->create($data);

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 500);
        }

        return response()->json([
            'success' => '新增成功',
            'data' => $result['data'],
        ]);
    }

    /**
     * 更新選單
     */
    public function update(int $id): JsonResponse
    {
        $data = $this->request->all();

        // 驗證
        $validator = $this->menuManageService->validator($data, $id);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $result = $this->menuManageService->update($id, $data);

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 500);
        }

        return response()->json([
            'success' => '更新成功',
            'data' => $result['data'],
        ]);
    }

    /**
     * 刪除選單
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->menuManageService->delete($id);

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 400);
        }

        return response()->json(['success' => '刪除成功']);
    }

    /**
     * 移動節點（拖放）
     */
    public function move(): JsonResponse
    {
        $id = $this->request->input('id');
        $newParentId = $this->request->input('parent_id');
        $position = $this->request->input('position', 0);

        // parent_id 為 '#' 表示根節點
        if ($newParentId === '#' || $newParentId === '') {
            $newParentId = null;
        }

        $result = $this->menuManageService->moveNode($id, $newParentId, $position);

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 500);
        }

        return response()->json(['success' => '移動成功']);
    }
}
