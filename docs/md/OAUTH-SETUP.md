# OAuth 統一驗證架構使用說明

## 📦 架構說明（簡化版）

本系統使用**單一 Middleware 模式**統一管理 OAuth 驗證邏輯，避免每個系統重複撰寫相同的驗證程式碼。

### 核心檔案（只有 2 個）

```
app/
├── Libraries/
│   └── AccountsOAuthLibrary.php    # Accounts 中心 API 呼叫
└── Http/
    └── Middleware/
        └── CheckOAuthToken.php     # OAuth 驗證 Middleware（所有邏輯都在這）
```

### 優點

✅ 只需複製 **2 個檔案**（Library + Middleware）
✅ 所有邏輯在 **1 個檔案** 內，容易理解
✅ 內建緩存機制（減少 99% API 呼叫）
✅ 錯誤格式統一
✅ 註解清楚標示需要修改的地方
✅ 不需要理解 Trait/Abstract 概念

---

## 🚀 在新系統中使用

### 步驟 1：複製核心檔案（2 個）

從 POS 系統複製以下檔案到新系統：

```bash
# 複製 Library
cp app/Libraries/AccountsOAuthLibrary.php /path/to/new-system/app/Libraries/

# 複製 Middleware
cp app/Http/Middleware/CheckOAuthToken.php /path/to/new-system/app/Http/Middleware/
```

---

### 步驟 2：修改 Middleware（根據註解）

打開 `app/Http/Middleware/CheckOAuthToken.php`，根據註解修改：

#### 🔸 必改項目 1：User Model 路徑（Line 6）

```php
// POS 系統
use App\Models\User\User;

// 薪資系統範例
use App\Models\Employee;

// 資產系統範例
use App\Models\Member;
```

#### 🔸 必改項目 2：查找用戶邏輯（Line 162-176）

```php
protected function findLocalUser(array $oauthUser)
{
    $code = $oauthUser['code'] ?? null;

    if (!$code) {
        return null;
    }

    // 🔸 POS 系統
    return User::where('code', $code)->first();

    // 🔸 薪資系統範例（使用 employee_code）
    // return Employee::where('employee_code', $code)->first();

    // 🔸 資產系統範例（使用 member_code）
    // return Member::where('member_code', $code)->first();
}
```

#### 🔸 選改項目 3：權限檢查（Line 187-204）【可選】

預設已檢查 `is_active`，如需額外檢查可擴充：

```php
protected function checkUserPermissions($user, Request $request)
{
    // 預設檢查：使用者是否啟用
    if (property_exists($user, 'is_active') && !$user->is_active) {
        return $this->errorResponse('使用者已停用', 'USER_DISABLED', 403);
    }

    // 🔸 薪資系統額外檢查：是否離職
    if ($user->resigned_at) {
        return $this->errorResponse('員工已離職', 'EMPLOYEE_RESIGNED', 403);
    }

    // 🔸 POS 系統額外檢查：是否有 POS 權限
    if (!$user->hasPermission('pos.access')) {
        return $this->errorResponse('無 POS 存取權限', 'POS_ACCESS_DENIED', 403);
    }

    return true;
}
```

---

### 步驟 3：註冊 Middleware

**`app/Http/Kernel.php`**：

```php
protected $routeMiddleware = [
    // ...
    'auth.oauth' => \App\Http\Middleware\CheckOAuthToken::class,
];
```

---

### 步驟 4：套用到路由

**`routes/api.php`**：

```php
Route::middleware(['auth.oauth'])->group(function () {
    Route::post('/invoice-issue', [InvoiceController::class, 'issue']);
    Route::get('/salary/slips', [SalaryController::class, 'index']);
});
```

---

## ⚙️ 配置說明

### 環境變數

確保新系統的 `.env` 有以下設定：

```env
# Accounts 中心設定
ACCOUNTS_URL=https://accounts.huabing.tw
ACCOUNTS_CLIENT_CODE=your_client_code
ACCOUNTS_SYSTEM_CODE=your_system_code
ACCOUNTS_TIMEOUT=10
```

### config/services.php

```php
'accounts' => [
    'url' => env('ACCOUNTS_URL'),
    'client_code' => env('ACCOUNTS_CLIENT_CODE'),
    'system_code' => env('ACCOUNTS_SYSTEM_CODE'),
    'timeout' => env('ACCOUNTS_TIMEOUT', 10),
],
```

---

## 🔧 可調整參數

在 Middleware 內可調整：

```php
/**
 * 是否啟用緩存（預設啟用，可提升 99% 效能）
 */
protected bool $enableCache = true;

/**
 * 緩存 TTL（秒，預設 1 小時）
 */
protected int $cacheTtl = 3600;
```

---

## 📋 快速檢查清單

複製到新系統後，請確認：

- [ ] 複製了 2 個檔案（Library + Middleware）
- [ ] 修改了 User Model 路徑（Line 6）
- [ ] 修改了 `findLocalUser()` 方法（Line 162-176）
- [ ] （選用）修改了 `checkUserPermissions()` 方法（Line 187-204）
- [ ] 註冊了 Middleware 到 `Kernel.php`
- [ ] 設定了環境變數（`.env` 和 `config/services.php`）
- [ ] 套用到需要的路由

---

## 🔄 更新所有系統

當修改 Middleware 後（如修復 bug），需要同步到所有系統：

```bash
# 從 POS 系統複製到其他系統
cp app/Http/Middleware/CheckOAuthToken.php /path/to/salary-system/app/Http/Middleware/
cp app/Http/Middleware/CheckOAuthToken.php /path/to/assets-system/app/Http/Middleware/

# 記得檢查各系統的客製化部分是否需要調整
```

---

## 📊 錯誤代碼對照表

Middleware 統一的錯誤回應格式：

| error_code | HTTP Status | 說明 |
|-----------|------------|------|
| `TOKEN_MISSING` | 401 | 未提供 Bearer Token |
| `TOKEN_INVALID` | 401 | Token 驗證失敗 |
| `USER_NOT_FOUND` | 404 | 本地系統找不到使用者 |
| `USER_DISABLED` | 403 | 使用者已停用（is_active = false） |
| `OAUTH_SERVICE_UNAVAILABLE` | 503 | 無法連線至 Accounts 中心 |

---

## 🐛 故障排除

### 問題 1：Class not found

```bash
# 確認檔案存在
ls -la app/Http/Middleware/CheckOAuthToken.php
ls -la app/Libraries/AccountsOAuthLibrary.php

# 重新載入 autoload
./composer.bat dump-autoload
```

### 問題 2：緩存不生效

檢查 Redis/File Cache 是否正常：

```bash
./php.bat artisan cache:clear
./php.bat artisan config:clear
```

### 問題 3：連線 Accounts 中心失敗

檢查環境變數：

```bash
./php.bat artisan config:show services.accounts
```

---

## 💡 實際案例

### 薪資打卡系統範例

只需修改 3 個地方：

```php
// 1. 改 Model 路徑
use App\Models\Employee;

// 2. 改查找邏輯
protected function findLocalUser(array $oauthUser)
{
    return Employee::where('employee_code', $oauthUser['code'] ?? null)->first();
}

// 3. 加入離職檢查
protected function checkUserPermissions($user, Request $request)
{
    if (property_exists($user, 'is_active') && !$user->is_active) {
        return $this->errorResponse('使用者已停用', 'USER_DISABLED', 403);
    }

    if ($user->resigned_at) {
        return $this->errorResponse('員工已離職', 'EMPLOYEE_RESIGNED', 403);
    }

    return true;
}
```

---

## 📝 系統清單（使用此架構）

- ✅ POS 系統（已套用）
- ⏳ 薪資打卡系統（待套用）
- ⏳ 資產管理系統（待套用）

---

**文件版本**: 2.0（簡化版）
**最後更新**: 2025-10-30
**維護者**: Development Team
