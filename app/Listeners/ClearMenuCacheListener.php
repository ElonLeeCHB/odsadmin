<?php

namespace App\Listeners;

use App\Services\MenuService;
use Illuminate\Support\Facades\Log;

/**
 * 選單快取清除監聽器
 *
 * 監聽權限、角色變更事件，自動清除相關快取
 */
class ClearMenuCacheListener
{
    protected MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    /**
     * 權限表變更時：清除全域選單快取
     *
     * @param mixed $event
     * @return void
     */
    public function handlePermissionChanged($event): void
    {
        // 清除所有系統的全域選單快取
        $this->menuService->clearAllGlobalMenuCache();

        Log::info('全域選單快取已清除（權限表變更）', [
            'event' => get_class($event)
        ]);
    }

    /**
     * 角色權限變更時：清除特定系統的所有角色選單快取
     *
     * @param mixed $event
     * @return void
     */
    public function handleRolePermissionsChanged($event): void
    {
        // 清除全域選單快取（因為權限可能影響選單結構）
        $this->menuService->clearAllGlobalMenuCache();

        // 清除所有角色選單快取（因為無法確定哪些角色組合受影響）
        $systems = ['admin', 'pos', 'www'];
        foreach ($systems as $system) {
            $this->menuService->clearAllRoleMenuCache($system);
        }

        Log::info('選單快取已清除（角色權限變更）', [
            'role_id' => $event->role->id ?? null,
            'role_name' => $event->role->name ?? null
        ]);
    }

    /**
     * 用戶角色變更時：清除該用戶的選單快取
     *
     * @param mixed $event
     * @return void
     */
    public function handleUserRolesChanged($event): void
    {
        $user = $event->user;
        $systems = ['admin', 'pos', 'www'];

        // 清除該用戶在所有系統的選單快取
        foreach ($systems as $system) {
            $this->menuService->clearUserMenuCache($user, $system);
        }

        Log::info('用戶選單快取已清除（角色變更）', [
            'user_id' => $user->id,
            'user_name' => $user->username ?? $user->name ?? null
        ]);
    }
}
