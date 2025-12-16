# Claude Code 系統說明文件

## 系統概述

本系統是一個**銷售管理系統**，採用 Laravel 框架開發，使用 `app\Domains` 架構區分不同的應用入口。

---

## 架構說明

### Domain 架構

系統使用 Domain-Driven Design (DDD) 概念，將不同功能區域拆分為獨立的 Domains：

#### 1. **後台管理系統** - `app\Domains\Admin`
- **用途**：內部管理後台
- **架構**：傳統 MVC（Controller + Blade 視圖）
- **路由**：`/admin/*`
- **Views 位置**：`app\Domains\Admin\Views\admin\`

#### 2. **官網 API** - `app\Domains\ApiWwwV2`
- **用途**：官網前端 API 服務
- **架構**：前後端分離（純 API）
- **路由**：`/api/www/v2/*`
- **特性**：只提供 JSON API，不包含視圖

#### 3. **POS API** - `app\Domains\ApiPosV2`
- **用途**：前端 POS 系統 API 服務
- **架構**：前後端分離（純 API）
- **路由**：`/api/pos/v2/*`
- **特性**：只提供 JSON API，不包含視圖

---

## 開發環境

### PHP 執行

**使用根目錄的 `php.bat`**

> ⚠️ 本地端 PHP 使用多版本方式建置，必須透過 `php.bat` 指明 PHP 版本。
> 因路徑問題，artisan 必須使用完整路徑。

```bash
# 執行 Artisan 命令
./php.bat "D:/Codes/PHP/DTSCorp/huabing.tw/pos.huabing.tw/httpdocs/laravel/artisan" migrate

# 執行 PHP 腳本
./php.bat -v
```

### Composer 執行

**使用根目錄的 `composer.bat`**
```bash
# 安裝套件
./composer.bat install

# 更新套件
./composer.bat update phpoffice/phpspreadsheet

# Dump autoload
./composer.bat dump-autoload
```

---

## 前端資源

### OpenCart 前端整合

本系統**借用 OpenCart 的前端 HTML**，並拆解為 Blade 模板檔案。

#### 前端資源位置
```
D:\Codes\PHP\DTSCorp\Chinabing\ods\htdocs\laravel\public\assets2\ocadmin
```

#### 參考版本
**OpenCart 4.1.0.3**

#### 資源結構
```
public/assets2/ocadmin/
├── css/              # 樣式檔
├── js/               # JavaScript 檔
├── image/            # 圖片資源
└── ...               # 其他 OpenCart 前端資源
```

#### Blade 使用範例
```blade
{{-- 引用 OpenCart CSS --}}
<link href="{{ asset('assets2/ocadmin/css/bootstrap.min.css') }}" rel="stylesheet">

{{-- 引用 OpenCart JS --}}
<script src="{{ asset('assets2/ocadmin/js/jquery-3.6.0.min.js') }}"></script>
```

---

## 資料庫連線

### 主要連線

#### 1. **mysql** (預設連線)
- 用途：主要業務資料（訂單、客戶、產品等）
- 設定：`config/database.php` → `connections.mysql`
- 環境變數：`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

#### 2. **sysdata** (系統資料連線)
- 用途：統計報表、系統數據
- 設定：`config/database.php` → `connections.sysdata`
- 環境變數：`DB_SYSDATA_HOST`, `DB_SYSDATA_DATABASE`, `DB_SYSDATA_USERNAME`, `DB_SYSDATA_PASSWORD`

### 使用範例

```php
// 使用預設連線（mysql）
$orders = Order::all();

// 使用 sysdata 連線
$reports = DB::connection('sysdata')
    ->table('monthly_operation_reports')
    ->get();

// Model 指定連線
class MonthlyReport extends Model
{
    protected $connection = 'sysdata';
}
```

---

## 目錄結構

```
D:\Codes\PHP\DTSCorp\Chinabing\ods\htdocs\laravel\
├── app/
│   ├── Domains/
│   │   ├── Admin/              # 後台管理系統
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   └── Middleware/
│   │   │   ├── Models/
│   │   │   ├── Services/
│   │   │   ├── Views/
│   │   │   │   └── admin/      # Blade 視圖
│   │   │   └── routes/
│   │   │
│   │   ├── ApiWwwV2/           # 官網 API
│   │   │   ├── Http/
│   │   │   │   └── Controllers/
│   │   │   ├── Models/
│   │   │   └── routes/
│   │   │
│   │   └── ApiPosV2/           # POS API
│   │       ├── Http/
│   │       │   └── Controllers/
│   │       ├── Models/
│   │       └── routes/
│   │
│   ├── Models/                 # 共用 Models
│   └── Console/
│       └── Commands/
│
├── database/
│   ├── migrations/
│   │   └── reports/            # 報表專用 Migration
│   └── seeders/
│
├── public/
│   └── assets2/
│       └── ocadmin/            # OpenCart 前端資源
│
├── config/
│   └── database.php            # 資料庫連線設定
│
├── php.bat                     # PHP 執行檔
├── composer.bat                # Composer 執行檔
├── Reports.md                  # 報表系統設計文件
└── claude.md                   # 本文件
```

---

## 開發規範

### 1. 命名空間規則

```php
// 後台 Controller
namespace App\Domains\Admin\Http\Controllers\Report;

// 官網 API Controller
namespace App\Domains\ApiWwwV2\Http\Controllers;

// POS API Controller
namespace App\Domains\ApiPosV2\Http\Controllers;

// 後台 Model
namespace App\Domains\Admin\Models\Report;
```

### 2. 路由定義

```php
// app/Domains/Admin/routes/admin.php
Route::prefix('admin')->middleware(['auth:admin'])->group(function () {
    Route::prefix('reports')->name('reports.')->group(function () {
        // 報表路由
    });
});

// app/Domains/ApiWwwV2/routes/api.php
Route::prefix('api/www/v2')->group(function () {
    // API 路由
});
```

### 3. Blade 視圖位置

```php
// 後台視圖放在各 Domain 內
app\Domains\Admin\Views\admin\reports\index.blade.php

// Controller 使用
return view('admin.reports.index', $data);
```

### 4. Migration 執行

```bash
# 一般 Migration
./php.bat artisan migrate

# 報表專用 Migration（指定路徑）
./php.bat artisan migrate --path=database/migrations/reports

# 指定連線
./php.bat artisan migrate --database=sysdata --path=database/migrations/reports
```

---

## 常用命令

### Artisan 命令

```bash
# 建立 Controller
./php.bat artisan make:controller Domains/Admin/Http/Controllers/Report/OperationMonthlyReportController

# 建立 Model
./php.bat artisan make:model Domains/Admin/Models/Report/MonthlyOperationReport

# 建立 Command
./php.bat artisan make:command Report/UpdateOperationMonthlyReport

# 建立 Migration（指定路徑）
./php.bat artisan make:migration create_monthly_operation_reports_table --path=database/migrations/reports

# 清除快取
./php.bat artisan cache:clear
./php.bat artisan config:clear
./php.bat artisan route:clear
./php.bat artisan view:clear

# 排程測試
./php.bat artisan schedule:list
./php.bat artisan schedule:test
```

### Composer 命令

```bash
# 安裝依賴
./composer.bat install

# 更新套件
./composer.bat update

# 安裝新套件
./composer.bat require phpoffice/phpspreadsheet

# Dump autoload
./composer.bat dump-autoload
```

---

## API 開發注意事項

### ApiWwwV2 / ApiPosV2

#### 1. 回應格式統一

```php
// 成功回應
return response()->json([
    'success' => true,
    'data' => $data,
    'message' => 'Operation successful'
], 200);

// 錯誤回應
return response()->json([
    'success' => false,
    'error' => 'Error message',
    'code' => 'ERROR_CODE'
], 400);
```

#### 2. 不提供視圖

```php
// ✅ 正確：返回 JSON
public function index()
{
    return response()->json(['data' => Order::all()]);
}

// ❌ 錯誤：不應返回視圖
public function index()
{
    return view('orders.index'); // API Domain 不應有視圖
}
```

#### 3. 認證機制

```php
// 使用 API Token 或 JWT
Route::middleware(['auth:api'])->group(function () {
    // 受保護的 API 路由
});
```

---

## OpenCart 前端整合

### CSS 引用

```blade
{{-- Bootstrap (OpenCart 4.1) --}}
<link href="{{ asset('assets2/ocadmin/css/bootstrap.min.css') }}" rel="stylesheet">

{{-- FontAwesome --}}
<link href="{{ asset('assets2/ocadmin/css/fontawesome.min.css') }}" rel="stylesheet">

{{-- OpenCart Admin Theme --}}
<link href="{{ asset('assets2/ocadmin/css/admin.css') }}" rel="stylesheet">
```

### JavaScript 引用

```blade
{{-- jQuery --}}
<script src="{{ asset('assets2/ocadmin/js/jquery-3.6.0.min.js') }}"></script>

{{-- Bootstrap --}}
<script src="{{ asset('assets2/ocadmin/js/bootstrap.bundle.min.js') }}"></script>

{{-- OpenCart Common JS --}}
<script src="{{ asset('assets2/ocadmin/js/common.js') }}"></script>
```

### 圖片資源

```blade
<img src="{{ asset('assets2/ocadmin/image/logo.png') }}" alt="Logo">
```

---

## 報表系統

詳細設計請參考 **`Reports.md`**

### 核心資料表

1. `monthly_operation_reports` - 營運月報主表（sysdata 連線）
2. `monthly_product_reports` - 商品月報主表（sysdata 連線）

### 相關檔案

- **Models**: `app\Domains\Admin\Models\Report\`
- **Controllers**: `app\Domains\Admin\Http\Controllers\Report\`
- **Services**: `app\Domains\Admin\Services\Report\`
- **Views**: `app\Domains\Admin\Views\admin\reports\`
- **Commands**: `app\Console\Commands\Report\`
- **Migrations**: `database\migrations\reports\`

---

## 環境變數範例

```env
# 主資料庫
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sales_db
DB_USERNAME=root
DB_PASSWORD=secret

# 系統資料庫（報表用）
DB_SYSDATA_HOST=127.0.0.1
DB_SYSDATA_PORT=3306
DB_SYSDATA_DATABASE=sysdata_db
DB_SYSDATA_USERNAME=root
DB_SYSDATA_PASSWORD=secret
```

---

## 故障排除

### 1. Autoload 問題

```bash
./composer.bat dump-autoload
```

### 2. 視圖快取問題

```bash
./php.bat artisan view:clear
./php.bat artisan cache:clear
```

### 3. 路由快取問題

```bash
./php.bat artisan route:clear
./php.bat artisan route:cache
```

### 4. Migration 執行失敗

```bash
# 檢查連線
./php.bat artisan db:show

# 重新執行 Migration
./php.bat artisan migrate:fresh --path=database/migrations/reports
```

---

## 版本資訊

- **Laravel**: (請查看 `composer.json`)
- **PHP**: (執行 `./php.bat -v` 查看)
- **OpenCart 參考版本**: 4.1.0.3
- **MariaDB/MySQL**: (請查看資料庫版本)

---

## 相關文件

- **報表系統設計**: `Reports.md`
- **Laravel 官方文件**: https://laravel.com/docs
- **OpenCart 文件**: https://docs.opencart.com/

---

**文件版本**: 1.0
**最後更新**: 2025-10-01
**維護團隊**: Development Team
