# MenuService 選單服務使用說明

## 設計理念

### 快取策略

本服務採用**兩層快取**機制，大幅提升效能並減少快取數量：

#### 1. 全域選單樹（24小時）

```
快取鍵格式：menu.tree.{system}
範例：menu.tree.admin, menu.tree.pos, menu.tree.www
```

**說明：**
- 快取所有 `type=menu` 的權限項目（樹狀結構）
- 不分用戶，所有用戶共用
- 24 小時自動失效
- 當 `permissions` 表變更時手動清除

#### 2. 角色組合選單（1小時）

```
快取鍵格式：menu.{system}.roles.{hash}
範例：menu.admin.roles.a3f5c8d2e4b7f9a1
```

**hash 產生方式：**
```php
// 1. 取得用戶所有角色 ID
$roleIds = [2, 5, 7];

// 2. 排序（確保相同角色組合產生相同 hash）
sort($roleIds); // [2, 5, 7]

// 3. 產生 MD5
$hash = md5('2,5,7'); // a3f5c8d2e4b7f9a1
```

**優勢：**
- 相同角色組合的用戶共用快取
- 100個用戶可能只需要10份快取（假設有10種角色組合）
- 快取命中率高

---

## 核心方法

### 1. getUserMenus()

取得用戶在特定系統的選單（已過濾權限）

```php
use App\Services\MenuService;

public function index(MenuService $menuService)
{
    $user = auth()->user();
    $menus = $menuService->getUserMenus($user, 'admin');

    return view('admin.dashboard', compact('menus'));
}
```

**參數：**
- `$user`：當前用戶
- `$system`：系統前綴（`admin`、`pos`、`www`）

**返回：**
- 過濾後的樹狀選單 Collection

---

### 2. getUserPermissions()

取得用戶所有權限（角色聯集）

```php
$permissions = $menuService->getUserPermissions(auth()->user());

// 範例結果：
// Collection: [
//     'admin.dashboard',
//     'admin.sales.order.list',
//     'admin.sales.order.create',
//     'admin.sales.order.edit'
// ]
```

---

### 3. getBreadcrumb()

取得麵包屑路徑

```php
$breadcrumb = $menuService->getBreadcrumb('admin.sales.order.list', 'admin');

// 範例結果：
// Collection: [
//     Permission { name: 'admin', title: '後台管理' },
//     Permission { name: 'admin.sales', title: '銷售' },
//     Permission { name: 'admin.sales.order', title: '訂單作業' },
//     Permission { name: 'admin.sales.order.list', title: '訂單列表' }
// ]
```

---

## 使用範例

### Admin 後台 Controller

```php
namespace App\Domains\Admin\Http\Controllers;

use App\Services\MenuService;

class DashboardController extends Controller
{
    public function index(MenuService $menuService)
    {
        $user = auth()->user();

        // 取得選單
        $menus = $menuService->getUserMenus($user, 'admin');

        // 取得麵包屑
        $breadcrumb = $menuService->getBreadcrumb('admin.dashboard', 'admin');

        return view('admin.dashboard', compact('menus', 'breadcrumb'));
    }
}
```

---

### Admin Blade 視圖

```blade
{{-- resources/views/admin/layout/sidebar.blade.php --}}

<nav class="sidebar">
    @foreach ($menus as $menu)
        <div class="menu-item">
            <a href="{{ route($menu->name) }}">
                <i class="{{ $menu->icon }}"></i>
                {{ $menu->title }}
            </a>

            @if ($menu->children && $menu->children->isNotEmpty())
                <ul class="submenu">
                    @foreach ($menu->children as $child)
                        <li>
                            <a href="{{ route($child->name) }}">
                                {{ $child->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach
</nav>
```

---

### POS API Controller

```php
namespace App\Domains\ApiPosV2\Http\Controllers;

use App\Services\MenuService;

class AuthController extends Controller
{
    /**
     * 取得用戶權限與選單（登入後呼叫）
     */
    public function permissions(MenuService $menuService)
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data' => [
                'menus' => $menuService->getUserMenus($user, 'pos'),
                'permissions' => $menuService->getUserPermissions($user)
            ]
        ]);
    }
}
```

**API 回應範例：**

```json
{
  "success": true,
  "data": {
    "menus": [
      {
        "id": 10,
        "name": "pos.dashboard",
        "title": "首頁",
        "icon": "fas fa-home",
        "children": []
      },
      {
        "id": 12,
        "name": "pos.order",
        "title": "點餐",
        "icon": "fas fa-utensils",
        "children": [
          {
            "id": 20,
            "name": "pos.order.list",
            "title": "訂單列表",
            "icon": "fas fa-list"
          }
        ]
      }
    ],
    "permissions": [
      "pos.dashboard",
      "pos.order",
      "pos.order.list",
      "pos.order.create",
      "pos.checkout"
    ]
  }
}
```

---

## 快取清除策略

### 自動清除（Event Listener）

在 `EventServiceProvider` 註冊監聽器：

```php
use App\Listeners\ClearMenuCacheListener;
use Illuminate\Support\Facades\Event;

protected $listen = [
    // 權限表變更
    'permission.created' => [ClearMenuCacheListener::class . '@handlePermissionChanged'],
    'permission.updated' => [ClearMenuCacheListener::class . '@handlePermissionChanged'],
    'permission.deleted' => [ClearMenuCacheListener::class . '@handlePermissionChanged'],

    // 角色權限變更
    'role.permissions.synced' => [ClearMenuCacheListener::class . '@handleRolePermissionsChanged'],

    // 用戶角色變更
    'user.roles.synced' => [ClearMenuCacheListener::class . '@handleUserRolesChanged'],
];
```

---

### 手動清除

#### 1. 清除全域選單快取

```php
use App\Services\MenuService;

$menuService = app(MenuService::class);

// 清除特定系統
$menuService->clearGlobalMenuCache('admin');

// 清除所有系統
$menuService->clearAllGlobalMenuCache();
```

**觸發時機：**
- 新增、修改、刪除 `permissions` 表的資料
- 調整選單結構、排序、圖示等

---

#### 2. 清除角色選單快取

```php
// 清除特定用戶的選單快取
$menuService->clearUserMenuCache($user, 'admin');

// 清除特定系統的所有角色組合快取
$menuService->clearAllRoleMenuCache('admin');
```

**觸發時機：**
- 角色權限變更（`role_has_permissions` 表異動）
- 用戶角色變更（`model_has_roles` 表異動）

---

## 實際場景

### 場景 1：新增選單項目

```php
// 管理員新增選單
Permission::create([
    'name' => 'admin.reports.sales',
    'title' => '銷售報表',
    'type' => 'menu',
    'parent_id' => 3,
    'sort_order' => 10
]);

// 觸發 Event
event('permission.created', $permission);

// Listener 自動清除：
// 1. 所有系統的全域選單快取
// 2. 所有系統的角色選單快取
```

---

### 場景 2：調整角色權限

```php
// 管理員為「銷售經理」新增「刪除訂單」權限
$role = Role::find(2); // 銷售經理
$permission = Permission::where('name', 'admin.sales.order.delete')->first();

$role->givePermissionTo($permission);

// 觸發 Event
event('role.permissions.synced', compact('role'));

// Listener 自動清除：
// 1. 所有系統的全域選單快取
// 2. 所有包含該角色的角色組合快取
```

---

### 場景 3：用戶角色變更

```php
// 管理員調整用戶角色
$user = SystemUser::find(5);
$user->syncRoles(['sales_manager', 'pos_staff']);

// 觸發 Event
event('user.roles.synced', compact('user'));

// Listener 自動清除：
// 1. 該用戶在所有系統的選單快取
//    - menu.admin.roles.{舊hash}
//    - menu.admin.roles.{新hash}
//    - menu.pos.roles.{舊hash}
//    - menu.pos.roles.{新hash}
```

---

## 效能分析

### 快取數量比較

#### 傳統方案（按用戶）

```
系統：admin, pos, www (3個)
用戶：100人
快取數量：100 × 3 = 300 份
```

#### 本方案（按角色組合）

```
系統：admin, pos, www (3個)
角色組合：假設 10 種
快取數量：10 × 3 = 30 份

減少：90%
```

---

### 快取命中率

```
假設：
- 100個用戶
- 10種角色組合
- 每種組合平均10個用戶

情況 1：用戶A登入（首次）
- 角色組合 [2,5,7]
- 快取未命中（MISS）
- 產生快取：menu.admin.roles.a3f5c8d2

情況 2：用戶B登入
- 角色組合 [2,5,7]（與用戶A相同）
- 快取命中（HIT）✅
- 直接返回，無需重新計算

命中率：90%（10個首次MISS，90個HIT）
```

---

## Redis Tags 支援（選用）

如果使用 Redis，可以啟用 Tags 功能，更方便管理快取：

```php
// config/cache.php
'redis' => [
    'driver' => 'redis',
    'connection' => 'cache',
    'options' => [
        'prefix' => 'laravel_cache:',
    ],
],
```

**修改 MenuService：**

```php
// 儲存時加上 Tags
Cache::tags(['menu', $system])
    ->remember($rolesCacheKey, self::ROLE_MENU_CACHE_TTL, function() {
        // ...
    });

// 清除時使用 Tags
Cache::tags(['menu', 'admin'])->flush(); // 清除所有 admin 選單快取
Cache::tags(['menu'])->flush();          // 清除所有選單快取
```

---

## 疑難排除

### 問題 1：選單沒有更新

**原因：** 快取未清除

**解決：**
```bash
# 手動清除所有快取
php artisan cache:clear

# 或只清除選單快取
php artisan tinker
>>> app(\App\Services\MenuService::class)->clearAllGlobalMenuCache();
>>> app(\App\Services\MenuService::class)->clearAllRoleMenuCache('admin');
```

---

### 問題 2：不同用戶看到相同選單（但應該不同）

**原因：** 角色組合 hash 相同，但權限應該不同

**排查：**
```php
// 檢查用戶角色
$user1->roles->pluck('id'); // [2, 5, 7]
$user2->roles->pluck('id'); // [2, 5, 7]

// 檢查角色權限
Role::find(2)->permissions->pluck('name');
Role::find(5)->permissions->pluck('name');
Role::find(7)->permissions->pluck('name');
```

**解決：** 確認角色權限設定是否正確

---

### 問題 3：選單顯示不完整（缺少父層）

**原因：** 角色缺少父層權限

**範例：**
```php
// ❌ 錯誤：只分配最底層
$role->givePermissionTo('admin.sales.order.list');
// 結果：看不到選單（缺少 admin, admin.sales, admin.sales.order）

// ✅ 正確：分配完整路徑
$permission = Permission::where('name', 'admin.sales.order.list')->first();
$role->assignPermissionWithAncestors($permission);
// 自動分配：admin, admin.sales, admin.sales.order, admin.sales.order.list
```

---

## 最佳實踐

### 1. 統一在 Middleware 注入選單

```php
// app/Http/Middleware/InjectMenus.php

class InjectMenus
{
    protected MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    public function handle($request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();

            // 注入選單到視圖
            view()->share('adminMenus', $this->menuService->getUserMenus($user, 'admin'));
        }

        return $next($request);
    }
}
```

### 2. 使用 View Composer

```php
// app/Providers/ViewServiceProvider.php

use App\Services\MenuService;
use Illuminate\Support\Facades\View;

public function boot()
{
    View::composer('admin.layouts.master', function ($view) {
        $menuService = app(MenuService::class);
        $user = auth()->user();

        $view->with('menus', $menuService->getUserMenus($user, 'admin'));
    });
}
```

### 3. 明確觸發 Event

```php
// app/Models/Access/Role.php

protected static function booted()
{
    static::updated(function ($role) {
        event('role.permissions.synced', compact('role'));
    });
}
```

---

**文件版本**: 1.0
**最後更新**: 2025-11-25
**維護團隊**: Development Team
