<?php

namespace App\Repositories\Access;

use App\Repositories\Repository;
// use Spatie\Permission\Models\Role;
use App\Models\Access\Role;
use Illuminate\Support\Collection;

class RoleRepository extends Repository
{
    /**
     * 綁定的 Model
     */
    protected function model(): string
    {
        return Role::class;
    }

    /**
     * 根據名稱取得角色
     */
    public function findByName(string $name): ?Role
    {
        return $this->model->where('name', $name)->first();
    }

    public function attachPermission(string $name, array|string $permissions)
    {
        $role = $this->findByName($name);
        return $role?->givePermissionTo($permissions);
    }

    public function syncPermissions(string $name, array $permissions)
    {
        $role = $this->findByName($name);
        return $role?->syncPermissions($permissions);
    }

    /**
     * 刪除角色並清除關聯權限
     */
    public function deleteRole(Role $role): bool
    {
        $role->syncPermissions([]); // 清除權限
        return $role->delete();
    }
}
