# POS 系統 Accounts OAuth 整合待辦事項

## 🚀 容錯機制實作（優先）

### 需求說明
實作 AUTH_DRIVER 環境變數，支援 Accounts 中心和本地認證雙模式，提供容錯備援機制。

**使用場景：**
- 正常情況：使用 Accounts 中心認證（accounts-center）
- 故障情況：Accounts 中心無法連線時，使用本地認證（local）
- 備援機制：每次遠端認證成功後，同步密碼到本地作為備份

---

## 📋 待辦清單

### ✅ 已完成
- [x] 安裝 `huabing/accounts-oauth` 套件
- [x] 發布配置檔到 `config/accounts-oauth.php`
- [x] 更新 `OAuthController` 使用新套件
- [x] 更新 `CheckSanctumOrOAuth` middleware 使用新套件
- [x] 設定 `.env` 環境變數
- [x] 修正 User Model 路徑為 `App\Models\User\User`

### 🔲 待實作

#### 1. 在 .env 新增 AUTH_DRIVER 變數
**檔案：** `.env`

```env
# Accounts OAuth Settings
AUTH_DRIVER=accounts-center  # 使用 Accounts 中心（預設）
# AUTH_DRIVER=local           # 使用本地認證（備援模式）
```

**檔案：** `config/accounts-oauth.php`

新增配置項：
```php
'auth_driver' => env('AUTH_DRIVER', 'accounts-center'),
```

---

#### 2. 建立 AuthStrategyService 統一管理認證策略切換
**新建檔案：** `app/Services/AuthStrategyService.php`

```php
<?php

namespace App\Services;

class AuthStrategyService
{
    /**
     * 取得目前的認證驅動
     */
    public function getDriver(): string
    {
        return config('accounts-oauth.auth_driver', 'accounts-center');
    }

    /**
     * 是否使用 OAuth 認證
     */
    public function shouldUseOAuth(): bool
    {
        return $this->getDriver() === 'accounts-center';
    }

    /**
     * 是否使用本地認證
     */
    public function shouldUseLocal(): bool
    {
        return $this->getDriver() === 'local';
    }

    /**
     * 嘗試自動降級（當 OAuth 失敗時）
     */
    public function canFallbackToLocal(): bool
    {
        // 可以根據業務需求決定是否允許自動降級
        return config('accounts-oauth.auto_fallback', true);
    }
}
```

---

#### 3. 修改 OAuthController 在登入成功後同步密碼到 users.password
**檔案：** `app/Domains/ApiPosV2/Http/Controllers/Auth/OAuthController.php`

在 `login()` 方法的登入成功邏輯中加入：

```php
// 驗證成功，同步使用者資料
$oauthUserData = $oauthResult['data']['user'] ?? null;

if (!$oauthUserData) {
    return response()->json([
        'success' => false,
        'message' => 'Accounts 中心回傳資料格式錯誤',
    ], 500);
}

// 使用套件的 syncUser 方法同步使用者
$user = $this->oauthClient->syncUser($oauthUserData);

// ✨ 新增：同步密碼到本地作為備援
$user->password = Hash::make($password);
$user->save();

Log::info('OAuth 登入成功，已同步密碼到本地', [
    'user_id' => $user->id,
    'username' => $user->username,
]);
```

**需要新增 use：**
```php
use Illuminate\Support\Facades\Hash;
```

---

#### 4. 修改路由根據 AUTH_DRIVER 動態選擇 Controller
**檔案：** `app/Domains/ApiPosV2/Routes/apipos.php`

修改登入路由：

```php
Route::group([
    'namespace' => 'App\Domains\ApiPosV2\Http\Controllers',
    'as' => 'api.posv2.',
    'middleware' => ['checkApiPosV2Authorization']
], function ()
{
    Route::post('login', 'Auth\LoginController@login');

    // 根據 AUTH_DRIVER 動態選擇
    $authDriver = config('accounts-oauth.auth_driver', 'accounts-center');

    if ($authDriver === 'accounts-center') {
        Route::post('oauth/login', 'Auth\OAuthController@login');
        Route::post('oauth/logout', 'Auth\OAuthController@logout');
    } else {
        // local 模式使用本地 LoginController
        Route::post('oauth/login', 'Auth\LoginController@login');
        Route::post('oauth/logout', 'Auth\LoginController@logout');
    }

    // 測試連線路由...
});
```

---

#### 5. 更新 CheckSanctumOrOAuth middleware 支援 AUTH_DRIVER 切換
**檔案：** `app/Http/Middleware/CheckSanctumOrOAuth.php`

在 `handle()` 方法開頭加入：

```php
public function handle(Request $request, Closure $next)
{
    $token = $request->bearerToken();

    if (!$token) {
        return $this->errorResponse(__('auth.error_codes.TOKEN_MISSING'), 'TOKEN_MISSING', 401);
    }

    // ✨ 新增：檢查 AUTH_DRIVER
    $authDriver = config('accounts-oauth.auth_driver', 'accounts-center');

    if ($authDriver === 'local') {
        // 直接使用 Sanctum 驗證（跳過 OAuth）
        Log::info('使用 local 認證模式，跳過 OAuth 驗證');
        return $this->handleLocalAuth($request, $next);
    }

    // 步驟 1: 嘗試 OAuth 驗證（優先）
    $oauthResult = $this->tryOAuthAuthentication($token, $request);

    // ... 原有邏輯
}

/**
 * 處理本地認證模式
 */
protected function handleLocalAuth(Request $request, Closure $next)
{
    $sanctumResult = $this->trySanctumAuthentication($request);

    if ($sanctumResult['success']) {
        return $next($request);
    }

    return $this->errorResponse(__('auth.error_codes.TOKEN_INVALID'), 'TOKEN_INVALID', 401);
}
```

---

#### 6. 處理後台 Admin 區域的認證整合
**需確認：**
- 後台是否也要支援 OAuth 認證？
- 還是後台維持獨立的 Session 認證？

**檔案待檢查：**
- `app/Domains/Admin/Http/Controllers/Auth/LoginController.php`
- `app/Domains/Admin/Http/Middleware/IsAdmin.php`
- `app/Domains/Admin/Routes/admin.php`

**建議方案：**
1. 後台獨立：維持現有 Session 認證，不整合 OAuth
2. 後台整合：建立 `AdminOAuthController` 支援 OAuth 登入

---

#### 7. 測試 accounts-center 模式登入和密碼同步
**測試步驟：**

1. 設定 `.env`：
   ```env
   AUTH_DRIVER=accounts-center
   ```

2. 清除配置：
   ```bash
   php artisan config:clear
   ```

3. 使用 Postman 測試：
   ```
   POST http://ods.dtstw.test/api/posv2/oauth/login

   Body:
   {
       "account": "0928623353",
       "password": "EIbRtilU7B7c"
   }
   ```

4. 檢查資料庫：
   ```sql
   SELECT id, username, code, password
   FROM users
   WHERE username = '0928623353';
   ```

   確認 `password` 欄位已更新（Hash 值）

---

#### 8. 測試 local 模式使用本地密碼登入
**測試步驟：**

1. 設定 `.env`：
   ```env
   AUTH_DRIVER=local
   ```

2. 清除配置：
   ```bash
   php artisan config:clear
   ```

3. 使用上次同步的密碼登入：
   ```
   POST http://ods.dtstw.test/api/posv2/oauth/login

   Body:
   {
       "account": "0928623353",
       "password": "EIbRtilU7B7c"
   }
   ```

4. 確認：
   - 不會呼叫 Accounts 中心
   - 使用本地 `users.password` 驗證
   - 可以正常取得 Token

---

#### 9. 測試 Accounts 中心故障時自動切換到 local 模式
**測試場景 1：手動關閉 Accounts 中心**

1. 修改 `.env`：
   ```env
   ACCOUNTS_URL=https://invalid-url-for-test.com
   AUTH_DRIVER=accounts-center
   ```

2. 嘗試登入，確認錯誤處理

3. 檢查是否有適當的錯誤訊息和 Log

**測試場景 2：自動降級（選配）**

如果實作自動降級機制：
- OAuth 連線失敗時，自動使用本地密碼驗證
- 記錄 Warning Log
- 回傳時加入 `fallback: true` 標記

---

## 📝 實作注意事項

### 安全性
- ✅ 密碼必須使用 `Hash::make()` 加密
- ✅ 不要在 Log 中記錄原始密碼
- ✅ 確保 `users.password` 欄位不為 null

### 向下相容
- ✅ 預設 `AUTH_DRIVER=accounts-center`，維持現有行為
- ✅ 不影響現有 API 端點
- ✅ 不影響現有 Token 驗證機制

### 錯誤處理
- ✅ Accounts 中心連線失敗時記錄詳細 Log
- ✅ 提供清楚的錯誤訊息給前端
- ✅ 區分「認證失敗」和「連線失敗」

### 效能考量
- ✅ 密碼同步僅在登入成功時執行（不頻繁）
- ✅ local 模式下跳過 OAuth API 呼叫
- ✅ 維持 middleware 的快取機制

---

## 🗂️ 相關檔案清單

### 需要修改的檔案
- [ ] `.env` - 新增 AUTH_DRIVER
- [ ] `config/accounts-oauth.php` - 新增 auth_driver 配置
- [ ] `app/Services/AuthStrategyService.php` - 新建
- [ ] `app/Domains/ApiPosV2/Http/Controllers/Auth/OAuthController.php` - 同步密碼
- [ ] `app/Domains/ApiPosV2/Routes/apipos.php` - 動態路由
- [ ] `app/Http/Middleware/CheckSanctumOrOAuth.php` - 支援 local 模式

### 需要確認的檔案
- [ ] `app/Domains/Admin/Http/Controllers/Auth/LoginController.php`
- [ ] `app/Domains/Admin/Http/Middleware/IsAdmin.php`
- [ ] `app/Domains/Admin/Routes/admin.php`

---

## 📅 時程規劃

| 項目 | 預估時間 | 負責人 |
|------|---------|--------|
| 1-2. 環境變數和策略服務 | 30 分鐘 | - |
| 3. 密碼同步機制 | 30 分鐘 | - |
| 4. 動態路由 | 30 分鐘 | - |
| 5. Middleware 更新 | 1 小時 | - |
| 6. 後台整合確認 | 1 小時 | - |
| 7-9. 測試驗證 | 1 小時 | - |
| **總計** | **4.5 小時** | - |

---

## 🎯 優先級

**🔴 高優先級** - 提供系統容錯能力，避免 Accounts 中心故障導致所有系統無法登入

---

## 📞 聯絡資訊

如有問題請聯繫：
- Email: dev@dtscorp.com.tw
- Email: elonlee@huabing.tw

---

**建立日期：** 2025-01-17
**最後更新：** 2025-01-17
**狀態：** 待實作
