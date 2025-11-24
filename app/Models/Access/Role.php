<?php

namespace App\Models\Access;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $table = 'roles';

    // 允許 mass assign
    protected $fillable = [
        'name',
        'title',
        'description',
    ];

    /**
     * 分配權限時自動分配所有父層權限
     */
    public function assignPermissionWithAncestors(Permission $permission)
    {
        // 分配權限本身
        if (!$this->hasPermissionTo($permission)) {
            $this->givePermissionTo($permission);
        }

        // 自動分配所有父層權限
        foreach ($permission->ancestors() as $ancestor) {
            if (!$this->hasPermissionTo($ancestor)) {
                $this->givePermissionTo($ancestor);
            }
        }

        return $this;
    }

    /**
     * 移除權限時自動移除所有沒有被其他權限使用的父層權限
     */
    public function removePermissionWithOrphans(Permission $permission)
    {
        // 移除權限本身
        if ($this->hasPermissionTo($permission)) {
            $this->revokePermissionTo($permission);
        }

        // 檢查並移除孤立的父層權限
        foreach ($permission->ancestors() as $ancestor) {
            // 檢查是否還有其他權限需要這個祖先
            $hasOtherChildren = $this->permissions()
                ->where('name', 'like', $ancestor->name . '.%')
                ->exists();

            // 如果沒有其他子孫權限，則移除這個祖先
            if (!$hasOtherChildren && $this->hasPermissionTo($ancestor)) {
                $this->revokePermissionTo($ancestor);
            }
        }

        return $this;
    }
}
