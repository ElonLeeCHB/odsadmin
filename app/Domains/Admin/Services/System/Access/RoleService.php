<?php

namespace App\Domains\Admin\Services\System\Access;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\Service;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleService extends Service
{
    protected $modelName = "Spatie\Permission\Models\Role";

    public function __construct()
    {
        $this->model = new Role();
    }

    /**
     * 獲取角色列表
     */
    public function getRoles($data = [])
    {
        $query = Role::query();

        // 搜尋過濾
        if (!empty($data['filter_name'])) {
            $query->where('name', 'like', '%' . $data['filter_name'] . '%');
        }

        // 排序
        $sort = $data['sort'] ?? 'id';
        $order = $data['order'] ?? 'ASC';
        $query->orderBy($sort, $order);

        // 分頁
        $limit = $data['limit'] ?? 20;

        return $query->paginate($limit);
    }

    /**
     * 查找或創建新記錄
     */
    public function findOrFailOrNew($id = null)
    {
        if ($id) {
            return Role::findOrFail($id);
        }

        return new Role();
    }

    /**
     * 獲取所有權限
     */
    public function getAllPermissions()
    {
        return Permission::orderBy('name')->get();
    }

    /**
     * 驗證器
     */
    public function validator($data)
    {
        $rules = [
            'name' => 'required|string|max:255',
        ];

        // 如果是編輯，檢查名稱唯一性（排除自己）
        if (!empty($data['role_id'])) {
            $rules['name'] .= '|unique:roles,name,' . $data['role_id'];
        } else {
            $rules['name'] .= '|unique:roles,name';
        }

        $messages = [
            'name.required' => '角色名稱為必填',
            'name.unique' => '角色名稱已存在',
            'name.max' => '角色名稱最多 255 個字元',
        ];

        return Validator::make($data, $rules, $messages);
    }

    /**
     * 建立或更新角色
     */
    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $role_id = $data['role_id'] ?? null;

            if ($role_id) {
                $role = Role::findOrFail($role_id);
            } else {
                $role = new Role();
            }

            $role->name = $data['name'];
            $role->guard_name = $data['guard_name'] ?? 'web';
            $role->description = $data['description'] ?? null;

            $role->save();

            // 同步權限
            if (isset($data['permissions'])) {
                $permissions = is_array($data['permissions']) ? $data['permissions'] : [];
                $role->syncPermissions($permissions);
            } else {
                // 如果沒有傳遞 permissions，清空所有權限
                $role->syncPermissions([]);
            }

            $result['data']['role_id'] = $role->id;

            DB::commit();

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();

            $result['error'] = $e->getMessage();

            return $result;
        }
    }

    /**
     * 刪除角色
     */
    public function destroy($ids)
    {
        DB::beginTransaction();

        try {
            if (!is_array($ids)) {
                $ids = [$ids];
            }

            foreach ($ids as $id) {
                $role = Role::findOrFail($id);

                // 檢查是否有用戶正在使用此角色
                if ($role->users()->count() > 0) {
                    throw new \Exception("角色「{$role->name}」正在被用戶使用，無法刪除");
                }

                $role->delete();
            }

            DB::commit();

            return ['success' => true];

        } catch (\Exception $e) {
            DB::rollBack();

            return ['error' => $e->getMessage()];
        }
    }
}
