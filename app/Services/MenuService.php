<?php

namespace App\Services;

use App\Models\Access\Permission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * 選單服務
 *
 * 快取策略：
 * 1. 全域選單樹：24小時（按系統）
 * 2. 特定選單：1小時（按角色組合）
 */
class MenuService
{
    /**
     * 全域選單快取時間（秒）
     */
    const GLOBAL_MENU_CACHE_TTL = 86400; // 24小時

    /**
     * 角色選單快取時間（秒）
     */
    const ROLE_MENU_CACHE_TTL = 3600; // 1小時

    /**
     * 取得使用者選單（公開方法）
     *
     * @param Authenticatable $user
     * @param string $system 系統前綴 (admin, pos, www)
     * @return Collection
     */
    public function getUserMenus(Authenticatable $user, string $system): Collection
    {
        // 1. 取得角色組合的快取鍵
        $rolesCacheKey = $this->generateRolesCacheKey($user, $system);

        // 2. 嘗試從快取取得（按角色組合）
        return Cache::remember($rolesCacheKey, self::ROLE_MENU_CACHE_TTL, function() use ($user, $system) {
            // 2.1 取得全域選單樹（24小時快取）
            $globalMenus = $this->getGlobalMenuTree($system);

            // 2.2 取得用戶所有權限（角色聯集）
            $userPermissions = $this->getUserPermissions($user);

            // 2.3 過濾選單：全域 ∩ 用戶權限
            return $this->filterMenusByPermissions($globalMenus, $userPermissions);
        });
    }

    /**
     * 取得用戶所有權限（角色聯集）
     *
     * @param Authenticatable $user
     * @return Collection
     */
    public function getUserPermissions(Authenticatable $user): Collection
    {
        return $user->roles
            ->flatMap->permissions
            ->pluck('name')
            ->unique();
    }

    /**
     * 產生角色組合的快取鍵
     *
     * @param Authenticatable $user
     * @param string $system
     * @return string
     */
    protected function generateRolesCacheKey(Authenticatable $user, string $system): string
    {
        // 1. 取得用戶所有角色 ID
        $roleIds = $user->roles->pluck('id')->toArray();

        // 2. 排序（確保相同角色組合產生相同的 key）
        sort($roleIds);

        // 3. 產生 hash
        $rolesHash = md5(implode(',', $roleIds));

        // 4. 組合快取鍵
        return "menu.{$system}.roles.{$rolesHash}";
    }

    /**
     * 取得全域選單樹（24小時快取）
     *
     * @param string $system
     * @return Collection
     */
    protected function getGlobalMenuTree(string $system): Collection
    {
        $cacheKey = "menu.tree.{$system}";

        return Cache::remember($cacheKey, self::GLOBAL_MENU_CACHE_TTL, function() use ($system) {
            return Permission::where('type', 'menu')
                ->where('name', 'like', "{$system}.%")
                ->orderBy('sort_order')
                ->with(['children' => function($query) {
                    $query->where('type', 'menu')->orderBy('sort_order');
                }])
                ->whereNull('parent_id')
                ->get();
        });
    }

    /**
     * 遞迴過濾選單：全域選單 ∩ 用戶權限
     *
     * @param Collection $menus
     * @param Collection $userPermissions
     * @return Collection
     */
    protected function filterMenusByPermissions(Collection $menus, Collection $userPermissions): Collection
    {
        return $menus->filter(function($menu) use ($userPermissions) {
            // 檢查自己是否有權限
            if (!$userPermissions->contains($menu->name)) {
                return false;
            }

            // 遞迴過濾子選單
            if ($menu->children && $menu->children->isNotEmpty()) {
                $menu->children = $this->filterMenusByPermissions(
                    $menu->children,
                    $userPermissions
                );
            }

            return true;
        })->values();
    }

    /**
     * 清除全域選單快取（權限表更新時呼叫）
     *
     * @param string $system
     * @return void
     */
    public function clearGlobalMenuCache(string $system): void
    {
        Cache::forget("menu.tree.{$system}");
    }

    /**
     * 清除所有系統的全域選單快取
     *
     * @return void
     */
    public function clearAllGlobalMenuCache(): void
    {
        Cache::forget("menu.tree.admin");
        Cache::forget("menu.tree.pos");
        Cache::forget("menu.tree.www");
    }

    /**
     * 清除特定角色組合的選單快取
     *
     * @param Authenticatable $user
     * @param string $system
     * @return void
     */
    public function clearUserMenuCache(Authenticatable $user, string $system): void
    {
        $rolesCacheKey = $this->generateRolesCacheKey($user, $system);
        Cache::forget($rolesCacheKey);
    }

    /**
     * 清除所有角色選單快取（暴力清除法）
     * 當角色權限變更，但不確定影響哪些角色組合時使用
     *
     * @param string $system
     * @return void
     */
    public function clearAllRoleMenuCache(string $system): void
    {
        // 如果使用 Redis + Tags
        // Cache::tags(['menu', $system])->flush();

        // 暴力清除：清除所有以 menu.{system}.roles. 開頭的快取
        // 注意：這需要 Redis 或支援 pattern 的 driver
        $pattern = "menu.{$system}.roles.*";

        if (config('cache.default') === 'redis') {
            $keys = Cache::getRedis()->keys($pattern);
            foreach ($keys as $key) {
                Cache::forget(str_replace(config('database.redis.options.prefix'), '', $key));
            }
        }
    }

    /**
     * 建立平面選單列表（用於顯示麵包屑或權限檢查）
     *
     * @param Collection $menuTree
     * @return Collection
     */
    public function flattenMenuTree(Collection $menuTree): Collection
    {
        $result = collect();

        foreach ($menuTree as $menu) {
            $result->push($menu);

            if ($menu->children && $menu->children->isNotEmpty()) {
                $result = $result->merge($this->flattenMenuTree($menu->children));
            }
        }

        return $result;
    }

    /**
     * 取得麵包屑路徑
     *
     * @param string $currentPermissionName
     * @param string $system
     * @return Collection
     */
    public function getBreadcrumb(string $currentPermissionName, string $system): Collection
    {
        $globalMenus = $this->getGlobalMenuTree($system);
        $flatMenus = $this->flattenMenuTree($globalMenus);

        $current = $flatMenus->firstWhere('name', $currentPermissionName);

        if (!$current) {
            return collect();
        }

        $breadcrumb = collect([$current]);

        while ($current->parent_id) {
            $current = $flatMenus->firstWhere('id', $current->parent_id);
            if ($current) {
                $breadcrumb->prepend($current);
            }
        }

        return $breadcrumb;
    }
}
