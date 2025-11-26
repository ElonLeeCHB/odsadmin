<?php

namespace App\Domains\Admin\Http\Middleware;

use App\Services\MenuService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * 注入 Admin 後台選單到所有視圖
 *
 * 使用方式：
 * Route::middleware(['auth', InjectAdminMenus::class])->group(...);
 *
 * 在 Blade 中使用：
 * @foreach ($sidebarMenus as $menu)
 *     ...
 * @endforeach
 */
class InjectAdminMenus
{
    protected MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();

            // 取得 admin 系統的選單（已過濾權限、已快取）
            $menus = $this->menuService->getUserMenus($user, 'admin');

            // 取得使用者所有權限（用於按鈕級權限判斷）
            $permissions = $this->menuService->getUserPermissions($user);

            // 注入到所有視圖
            View::share('sidebarMenus', $menus);
            View::share('userPermissions', $permissions);
        }

        return $next($request);
    }
}
