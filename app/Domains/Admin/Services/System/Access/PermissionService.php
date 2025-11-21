<?php

namespace App\Domains\Admin\Services\System\Access;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\Service;
use Spatie\Permission\Models\Permission;

class PermissionService extends Service
{
    protected $modelName = "Spatie\Permission\Models\Permission";

    public function __construct()
    {
        $this->model = new Permission();
    }

    /**
     * 獲取權限列表
     */
    public function getPermissions($data = [])
    {
        $query = Permission::query();

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
            return Permission::findOrFail($id);
        }

        return new Permission();
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
        if (!empty($data['permission_id'])) {
            $rules['name'] .= '|unique:permissions,name,' . $data['permission_id'];
        } else {
            $rules['name'] .= '|unique:permissions,name';
        }

        $messages = [
            'name.required' => '權限名稱為必填',
            'name.unique' => '權限名稱已存在',
            'name.max' => '權限名稱最多 255 個字元',
        ];

        return Validator::make($data, $rules, $messages);
    }

    /**
     * 建立或更新權限
     */
    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $permission_id = $data['permission_id'] ?? null;

            if ($permission_id) {
                $permission = Permission::findOrFail($permission_id);
            } else {
                $permission = new Permission();
            }

            $permission->name = $data['name'];
            $permission->guard_name = $data['guard_name'] ?? 'web';
            $permission->description = $data['description'] ?? null;

            $permission->save();

            $result['data']['permission_id'] = $permission->id;

            DB::commit();

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();

            $result['error'] = $e->getMessage();

            return $result;
        }
    }

    /**
     * 刪除權限
     */
    public function destroy($ids)
    {
        DB::beginTransaction();

        try {
            if (!is_array($ids)) {
                $ids = [$ids];
            }

            foreach ($ids as $id) {
                $permission = Permission::findOrFail($id);

                // 檢查是否有角色正在使用此權限
                if ($permission->roles()->count() > 0) {
                    throw new \Exception("權限「{$permission->name}」正在被角色使用，無法刪除");
                }

                $permission->delete();
            }

            DB::commit();

            return ['success' => true];

        } catch (\Exception $e) {
            DB::rollBack();

            return ['error' => $e->getMessage()];
        }
    }
}
