<?php

namespace App\Domains\Admin\Services\System\Access;

use App\Models\Access\Permission;
use App\Services\MenuService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * 選單管理服務
 *
 * 用於後台選單的 CRUD 操作、樹狀結構管理、拖放更新等
 */
class MenuManageService
{
    protected MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    /**
     * 取得 jstree 格式的選單樹
     *
     * @param string $system 系統前綴 (admin, pos, www)
     * @return array
     */
    public function getJsTreeData(string $system): array
    {
        $menus = Permission::where('type', 'menu')
            ->where('name', 'like', "{$system}.%")
            ->orderBy('sort_order')
            ->get();

        return $this->buildJsTreeNodes($menus, null);
    }

    /**
     * 遞迴建立 jstree 節點
     *
     * @param Collection $menus
     * @param int|null $parentId
     * @return array
     */
    protected function buildJsTreeNodes(Collection $menus, ?int $parentId): array
    {
        $nodes = [];

        $children = $menus->where('parent_id', $parentId)->sortBy('sort_order');

        foreach ($children as $menu) {
            $node = [
                'id' => $menu->id,
                'text' => $menu->title . ' <small class="text-muted">(' . $menu->name . ')</small>',
                'icon' => $menu->icon ?: 'fas fa-file',
                'data' => [
                    'name' => $menu->name,
                    'title' => $menu->title,
                    'icon' => $menu->icon,
                    'sort_order' => $menu->sort_order,
                    'description' => $menu->description,
                ],
                'children' => $this->buildJsTreeNodes($menus, $menu->id),
                'state' => [
                    'opened' => true,
                ],
            ];

            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * 取得選單詳情
     *
     * @param int $id
     * @return Permission|null
     */
    public function find(int $id): ?Permission
    {
        return Permission::find($id);
    }

    /**
     * 取得可選的父層選單（排除自己和子孫）
     *
     * @param string $system
     * @param int|null $excludeId
     * @return Collection
     */
    public function getParentOptions(string $system, ?int $excludeId = null): Collection
    {
        $query = Permission::where('type', 'menu')
            ->where('name', 'like', "{$system}.%")
            ->orderBy('sort_order');

        if ($excludeId) {
            // 取得要排除的節點及其所有子孫
            $excludeIds = $this->getDescendantIds($excludeId);
            $excludeIds[] = $excludeId;
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->get();
    }

    /**
     * 取得所有子孫 ID
     *
     * @param int $id
     * @return array
     */
    protected function getDescendantIds(int $id): array
    {
        $ids = [];
        $children = Permission::where('parent_id', $id)->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child->id));
        }

        return $ids;
    }

    /**
     * 驗證器
     *
     * @param array $data
     * @param int|null $id
     * @return \Illuminate\Validation\Validator
     */
    public function validator(array $data, ?int $id = null)
    {
        $rules = [
            'name' => 'required|string|max:100',
            'title' => 'required|string|max:50',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:255',
            'parent_id' => 'nullable|integer|exists:permissions,id',
        ];

        // 檢查名稱唯一性（排除自己）
        if ($id) {
            $rules['name'] .= '|unique:permissions,name,' . $id;
        } else {
            $rules['name'] .= '|unique:permissions,name';
        }

        $messages = [
            'name.required' => '權限代碼為必填',
            'name.unique' => '權限代碼已存在',
            'name.max' => '權限代碼最多 100 個字元',
            'title.required' => '顯示名稱為必填',
            'title.max' => '顯示名稱最多 50 個字元',
            'parent_id.exists' => '父層選單不存在',
        ];

        return Validator::make($data, $rules, $messages);
    }

    /**
     * 建立選單
     *
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        DB::beginTransaction();

        try {
            // 如果沒指定 sort_order，自動設定為同層最大值 + 1
            if (!isset($data['sort_order']) || $data['sort_order'] === '') {
                $data['sort_order'] = $this->getNextSortOrder($data['parent_id'] ?? null);
            }

            $menu = Permission::create([
                'name' => $data['name'],
                'title' => $data['title'],
                'icon' => $data['icon'] ?? null,
                'sort_order' => $data['sort_order'],
                'description' => $data['description'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'type' => 'menu',
                'guard_name' => 'web',
            ]);

            // 清除選單快取
            $system = $menu->getSystem();
            if ($system) {
                $this->menuService->clearGlobalMenuCache($system);
            }

            DB::commit();

            return ['success' => true, 'data' => ['id' => $menu->id]];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 更新選單
     *
     * @param int $id
     * @param array $data
     * @return array
     */
    public function update(int $id, array $data): array
    {
        DB::beginTransaction();

        try {
            $menu = Permission::findOrFail($id);

            $menu->update([
                'name' => $data['name'],
                'title' => $data['title'],
                'icon' => $data['icon'] ?? null,
                'sort_order' => $data['sort_order'] ?? $menu->sort_order,
                'description' => $data['description'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
            ]);

            // 清除選單快取
            $system = $menu->getSystem();
            if ($system) {
                $this->menuService->clearGlobalMenuCache($system);
            }

            DB::commit();

            return ['success' => true, 'data' => ['id' => $menu->id]];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 刪除選單
     *
     * @param int $id
     * @return array
     */
    public function delete(int $id): array
    {
        DB::beginTransaction();

        try {
            $menu = Permission::findOrFail($id);

            // 檢查是否有子選單
            if ($menu->children()->count() > 0) {
                throw new \Exception('此選單有子項目，請先刪除子選單');
            }

            // 檢查是否有角色正在使用
            if ($menu->roles()->count() > 0) {
                throw new \Exception('此選單正被角色使用，請先移除角色關聯');
            }

            $system = $menu->getSystem();

            $menu->delete();

            // 清除選單快取
            if ($system) {
                $this->menuService->clearGlobalMenuCache($system);
            }

            DB::commit();

            return ['success' => true];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 移動節點（拖放更新）
     *
     * @param int $id 被移動的節點 ID
     * @param int|null $newParentId 新的父節點 ID
     * @param int $position 在新父節點下的位置（0-based）
     * @return array
     */
    public function moveNode(int $id, ?int $newParentId, int $position): array
    {
        DB::beginTransaction();

        try {
            $menu = Permission::findOrFail($id);
            $oldParentId = $menu->parent_id;

            // 更新父節點
            $menu->parent_id = $newParentId;
            $menu->save();

            // 重新排序同層節點
            $siblings = Permission::where('parent_id', $newParentId)
                ->where('id', '!=', $id)
                ->orderBy('sort_order')
                ->get();

            // 插入到指定位置
            $sortOrder = 0;
            $inserted = false;

            foreach ($siblings as $index => $sibling) {
                if ($index == $position && !$inserted) {
                    $menu->sort_order = $sortOrder;
                    $menu->save();
                    $sortOrder++;
                    $inserted = true;
                }

                $sibling->sort_order = $sortOrder;
                $sibling->save();
                $sortOrder++;
            }

            // 如果還沒插入（放到最後）
            if (!$inserted) {
                $menu->sort_order = $sortOrder;
                $menu->save();
            }

            // 清除選單快取
            $system = $menu->getSystem();
            if ($system) {
                $this->menuService->clearGlobalMenuCache($system);
            }

            DB::commit();

            return ['success' => true];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 取得同層下一個排序值
     *
     * @param int|null $parentId
     * @return int
     */
    protected function getNextSortOrder(?int $parentId): int
    {
        $maxSort = Permission::where('parent_id', $parentId)->max('sort_order');
        return ($maxSort ?? 0) + 1;
    }

    /**
     * 批次更新排序
     *
     * @param array $orders [['id' => 1, 'sort_order' => 0], ...]
     * @return array
     */
    public function updateSortOrders(array $orders): array
    {
        DB::beginTransaction();

        try {
            foreach ($orders as $order) {
                Permission::where('id', $order['id'])->update([
                    'sort_order' => $order['sort_order'],
                ]);
            }

            // 清除所有系統的選單快取
            $this->menuService->clearAllGlobalMenuCache();

            DB::commit();

            return ['success' => true];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['error' => $e->getMessage()];
        }
    }
}
