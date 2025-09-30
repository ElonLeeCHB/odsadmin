# Reports 統計報表系統設計文件

## 一、系統概述

使用 MariaDB 儲存已統整的報表數據，透過後台介面提供查詢與匯出功能。

### 核心特性
- **儲存方式**：MariaDB 資料庫（使用 `sysdata` connection）
- **數據來源**：從主資料庫 (MySQL) 定期統整
- **匯出格式**：支援 XLSX
- **更新機制**：排程自動更新 + 手動重建

---

## 二、檔案結構

```
app/Domains/Admin/
├── Http/Controllers/Report/
│   ├── OperationMonthlyReportController.php    # 營運月報表
│   ├── AnnualOrderReportController.php         # 年度訂單分析
│   └── ReportBaseController.php                # 共用基礎
│
├── Models/Report/
│   ├── MonthlyOperationReport.php              # 營運月報主表
│   ├── MonthlyProductReport.php                # 商品月報主表
│   └── Concerns/
│       └── UsesSysdataConnection.php           # Trait: 使用 sysdata 連線
│
├── Services/Report/
│   ├── OperationReportService.php              # 營運報表業務邏輯
│   ├── AnnualOrderReportService.php            # 年度報表業務邏輯
│   └── ReportExportService.php                 # 匯出服務
│
├── Views/admin/reports/
│   ├── index.blade.php                         # 報表首頁
│   ├── operation-monthly/
│   │   ├── index.blade.php                     # 營運月報列表
│   │   └── show.blade.php                      # 單月詳情
│   └── annual-order/
│       └── index.blade.php                     # 年度訂單分析
│
└── routes/
    └── admin.php                                # 後台路由

app/Console/Commands/Report/
├── UpdateOperationMonthlyReport.php             # 更新營運月報
└── UpdateAnnualOrderReport.php                  # 更新年度報表

database/migrations/reports/
├── 2025_10_01_000001_create_monthly_operation_reports_table.php
└── 2025_10_01_000002_create_monthly_product_reports_table.php

```

---

## 三、資料庫設計

### 3.1 Laravel 連線配置

使用現有的 **`sysdata`** connection，已在 `config/database.php` 定義：

```php
'sysdata' => [
    'driver' => 'mysql',
    'host' => env('DB_SYSDATA_HOST', '127.0.0.1'),
    'port' => env('DB_SYSDATA_PORT', '3306'),
    'database' => env('DB_SYSDATA_DATABASE'),
    'username' => env('DB_SYSDATA_USERNAME', 'forge'),
    'password' => env('DB_SYSDATA_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => null,
],
```

### 3.2 資料表結構

#### Table 1: `monthly_operation_reports` (營運月報主表)

**Migration: `database/migrations/reports/2025_10_01_000001_create_monthly_operation_reports_table.php`**

```php
Schema::connection('sysdata')->create('monthly_operation_reports', function (Blueprint $table) {
    $table->id();
    $table->unsignedSmallInteger('year')->comment('年份');
    $table->unsignedTinyInteger('month')->comment('月份');
    $table->decimal('order_total_amount', 15, 2)->default(0)->comment('訂單總金額');
    $table->unsignedInteger('order_count')->default(0)->comment('訂單數量');
    $table->unsignedInteger('order_customer_count')->default(0)->comment('訂單客戶數量');
    $table->unsignedInteger('new_customer_count')->default(0)->comment('新客戶數量');
    $table->decimal('purchase_total_amount', 15, 2)->default(0)->comment('進貨總金額');
    $table->unsignedInteger('supplier_count')->default(0)->comment('廠商數量');
    $table->timestamps();

    $table->unique(['year', 'month']);
    $table->index(['year', 'month']);
});
```

**欄位說明：**
| 欄位 | 型態 | 說明 |
|------|------|------|
| id | BIGINT UNSIGNED | 主鍵 |
| year | SMALLINT UNSIGNED | 年份 |
| month | TINYINT UNSIGNED | 月份 (1-12) |
| order_total_amount | DECIMAL(15,2) | 訂單總金額 |
| order_count | INT UNSIGNED | 訂單數量 |
| order_customer_count | INT UNSIGNED | 訂單客戶數量（當月有下單的不重複客戶數） |
| new_customer_count | INT UNSIGNED | 新客戶數量（當月首次下單的客戶數） |
| purchase_total_amount | DECIMAL(15,2) | 進貨總金額 |
| supplier_count | INT UNSIGNED | 廠商數量（當月有進貨的不重複廠商數） |
| created_at | TIMESTAMP | 建立時間 |
| updated_at | TIMESTAMP | 更新時間 |

#### Table 2: `monthly_product_reports` (商品月報主表)

**Migration: `database/migrations/reports/2025_10_01_000002_create_monthly_product_reports_table.php`**

```php
Schema::connection('sysdata')->create('monthly_product_reports', function (Blueprint $table) {
    $table->id();
    $table->unsignedSmallInteger('year')->comment('年份');
    $table->unsignedTinyInteger('month')->comment('月份');
    $table->string('product_code', 100)->comment('商品代號');
    $table->string('product_name', 255)->comment('商品名稱');
    $table->decimal('quantity', 15, 3)->default(0)->comment('銷售數量');
    $table->decimal('total_amount', 15, 2)->default(0)->comment('銷售金額');
    $table->timestamps();

    $table->unique(['year', 'month', 'product_code']);
    $table->index(['year', 'month', 'total_amount']); // 用於排序取前十大
});
```

**欄位說明：**
| 欄位 | 型態 | 說明 |
|------|------|------|
| id | BIGINT UNSIGNED | 主鍵 |
| year | SMALLINT UNSIGNED | 年份 |
| month | TINYINT UNSIGNED | 月份 (1-12) |
| product_code | VARCHAR(100) | 商品代號 |
| product_name | VARCHAR(255) | 商品名稱 |
| quantity | DECIMAL(15,3) | 銷售數量 |
| total_amount | DECIMAL(15,2) | 銷售金額 |
| created_at | TIMESTAMP | 建立時間 |
| updated_at | TIMESTAMP | 更新時間 |

---

## 四、Model 設計

### 4.1 Trait: UsesSysdataConnection

**app/Domains/Admin/Models/Report/Concerns/UsesSysdataConnection.php**
```php
<?php

namespace App\Domains\Admin\Models\Report\Concerns;

trait UsesSysdataConnection
{
    public function getConnectionName()
    {
        return 'sysdata';
    }
}
```

### 4.2 MonthlyOperationReport

**app/Domains/Admin/Models/Report/MonthlyOperationReport.php**
```php
<?php

namespace App\Domains\Admin\Models\Report;

use Illuminate\Database\Eloquent\Model;
use App\Domains\Admin\Models\Report\Concerns\UsesSysdataConnection;

class MonthlyOperationReport extends Model
{
    use UsesSysdataConnection;

    protected $connection = 'sysdata';
    protected $table = 'monthly_operation_reports';

    protected $fillable = [
        'year',
        'month',
        'order_total_amount',
        'order_count',
        'order_customer_count',
        'new_customer_count',
        'purchase_total_amount',
        'supplier_count',
    ];

    protected $casts = [
        'order_total_amount' => 'decimal:2',
        'purchase_total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 關聯：該月所有商品銷售
    public function productReports()
    {
        return $this->hasMany(MonthlyProductReport::class, ['year', 'month'], ['year', 'month']);
    }

    // 取得該月前十大商品
    public function topProducts($limit = 10)
    {
        return $this->productReports()
            ->orderByDesc('total_amount')
            ->limit($limit);
    }

    // Scope: 查詢特定年月
    public function scopeYearMonth($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    // Scope: 查詢特定年份
    public function scopeYear($query, $year)
    {
        return $query->where('year', $year)->orderBy('month');
    }
}
```

### 4.3 MonthlyProductReport

**app/Domains/Admin/Models/Report/MonthlyProductReport.php**
```php
<?php

namespace App\Domains\Admin\Models\Report;

use Illuminate\Database\Eloquent\Model;
use App\Domains\Admin\Models\Report\Concerns\UsesSysdataConnection;

class MonthlyProductReport extends Model
{
    use UsesSysdataConnection;

    protected $connection = 'sysdata';
    protected $table = 'monthly_product_reports';

    protected $fillable = [
        'year',
        'month',
        'product_code',
        'product_name',
        'quantity',
        'total_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Scope: 查詢特定年月
    public function scopeYearMonth($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    // Scope: 查詢前 N 名
    public function scopeTop($query, $limit = 10)
    {
        return $query->orderByDesc('total_amount')->limit($limit);
    }
}
```

---

## 五、Controller 設計

### 5.1 OperationMonthlyReportController

**app/Domains/Admin/Http/Controllers/Report/OperationMonthlyReportController.php**

**功能清單：**
- `index()` - 列表頁：顯示所有已生成的月報
- `show($year, $month)` - 詳情頁：顯示單月詳細數據
- `export($year, $month)` - 匯出 XLSX 檔案
- `rebuild($year, $month)` - 手動重建特定月份報表

**路由：**
```php
Route::prefix('reports/operation-monthly')->name('reports.operation-monthly.')->group(function () {
    Route::get('/', [OperationMonthlyReportController::class, 'index'])->name('index');
    Route::get('/{year}/{month}', [OperationMonthlyReportController::class, 'show'])->name('show');
    Route::get('/{year}/{month}/export', [OperationMonthlyReportController::class, 'export'])->name('export');
    Route::post('/{year}/{month}/rebuild', [OperationMonthlyReportController::class, 'rebuild'])->name('rebuild');
});
```

### 5.2 AnnualOrderReportController

**app/Domains/Admin/Http/Controllers/Report/AnnualOrderReportController.php**

**功能清單：**
- `index()` - 顯示年度訂單總金額分析表（矩陣式：年份 × 月份）
- `export($year)` - 匯出特定年份 XLSX
- `rebuild($year)` - 重建特定年份數據

**路由：**
```php
Route::prefix('reports/annual-order')->name('reports.annual-order.')->group(function () {
    Route::get('/', [AnnualOrderReportController::class, 'index'])->name('index');
    Route::get('/{year}/export', [AnnualOrderReportController::class, 'export'])->name('export');
    Route::post('/{year}/rebuild', [AnnualOrderReportController::class, 'rebuild'])->name('rebuild');
});
```

---

## 六、Service 設計

### 6.1 OperationReportService

**職責：**
- 從主資料庫查詢訂單、進貨數據
- 計算月度統計並寫入 SQLite
- 計算前十大商品

**主要方法：**
```php
public function updateMonthlyReport(int $year, int $month): MonthlyOperationReport
public function calculateTopProducts(int $year, int $month): Collection
public function rebuildRange(Carbon $startDate, Carbon $endDate): void
```

### 6.2 AnnualOrderReportService

**職責：**
- 從主資料庫按年月統計訂單
- 更新 `annual_order_totals` 表

**主要方法：**
```php
public function updateAnnualReport(int $year): void
public function getYearlyMatrix(array $years): array  // 返回多年矩陣數據
```

### 6.3 ReportExportService

**職責：**
- 使用 `PhpSpreadsheet` 生成 XLSX
- 處理營運月報、年度報表的匯出格式

**主要方法：**
```php
public function exportOperationMonthly(int $year, int $month): string  // 返回檔案路徑
public function exportAnnualOrder(int $year): string
```

---

## 七、報表功能規格

### 7.1 營運月報表

#### 資料內容
**檔名格式：** `營運月報表_YYYY_MM.xlsx`

**Sheet 1: 月度總覽**
| 項目 | 數值 |
|------|------|
| 訂單總金額 | 1,500,000 |
| 訂單數量 | 230 |
| 訂單客戶數量 | 85 |
| 新客戶數量 | 12 |
| 進貨總金額 | 800,000 |
| 廠商數量 | 28 |

**Sheet 2: 前十大商品**
| 排名 | 商品代號 | 商品名稱 | 銷售數量 | 銷售金額 |
|------|----------|----------|----------|----------|
| 1 | P001 | 商品A | 500.000 | 250,000.00 |
| 2 | P002 | 商品B | 450.000 | 220,000.00 |
| ... | ... | ... | ... | ... |

#### 資料來源對應

**月度總覽** → `monthly_operation_reports` 表單筆資料

**前十大商品** → 從 `monthly_product_reports` 按 `total_amount` 排序取前 10 筆

#### 頁面功能
- **列表頁**：下拉選擇年份，顯示該年所有月份報表
- **詳情頁**：顯示數據表格 + 「匯出 XLSX」按鈕
- **重建按鈕**：管理員手動觸發重新計算

### 7.2 年度訂單總金額分析

#### 資料呈現
**表格格式：**
```
年份 | 1月 | 2月 | 3月 | ... | 12月 | 全年總計
-----|-----|-----|-----|-----|------|--------
2022 | 1.2M| 1.5M| 1.3M| ... | 1.8M | 18.5M
2023 | 1.5M| 1.6M| 1.4M| ... | 2.0M | 20.3M
2024 | 1.8M| 1.9M| ... | ... | -    | 15.2M (YTD)
```

#### 資料來源對應

直接查詢 `monthly_operation_reports` 表，按 `year`, `month` 分組展示 `order_total_amount`

#### 頁面功能
- **年份選擇器**：多選查詢不同年份對比
- **視覺化**：可選加入 Chart.js 折線圖
- **匯出 XLSX**：包含選中的所有年份

---

## 八、數據更新機制

### 8.1 自動排程（推薦）

**app/Console/Kernel.php**
```php
protected function schedule(Schedule $schedule)
{
    // 每日凌晨 3 點更新上月營運報表
    $schedule->command('report:update-operation-monthly')
        ->dailyAt('03:00')
        ->onOneServer();

    // 每日凌晨 4 點更新年度報表
    $schedule->command('report:update-annual-order')
        ->dailyAt('04:00')
        ->onOneServer();
}
```

### 8.2 Command 設計

#### UpdateOperationMonthlyReport
```bash
php artisan report:update-operation-monthly {year?} {month?}
```
- 無參數：更新上月數據
- 有參數：更新指定年月

#### UpdateAnnualOrderReport
```bash
php artisan report:update-annual-order {year?}
```
- 無參數：更新當年數據
- 有參數：更新指定年份

---

## 九、初始化流程

### Step 1: 建立資料庫
```bash
php artisan report:init-database
```
執行 `database/reports_schema.sql` 建立所有表格

### Step 2: 歷史數據回填
```bash
# 回填 2022-2024 所有月份
php artisan report:backfill-operation-monthly 2022-01 2024-12

# 回填年度報表
php artisan report:backfill-annual-order 2022 2024
```

### Step 3: 驗證數據
前往後台頁面檢查報表是否正確生成

---

## 十、前端頁面設計（Blade）

### 10.1 報表首頁

**app/Domains/Admin/Views/admin/reports/index.blade.php**

**內容：**
- 卡片式導航：營運月報 / 年度訂單分析
- 顯示最後更新時間
- 快速連結到各報表入口

### 10.2 營運月報列表

**app/Domains/Admin/Views/admin/reports/operation-monthly/index.blade.php**

**元件：**
- 年份選擇器
- 月份卡片（12 個月）
- 每張卡片顯示：訂單金額、進貨金額、狀態
- 點擊進入詳情頁

### 10.3 營運月報詳情

**app/Domains/Admin/Views/admin/reports/operation-monthly/show.blade.php**

**元件：**
- 月度總覽表格
- 前十大商品表格
- 操作按鈕：「匯出 XLSX」、「重建數據」

### 10.4 年度訂單分析

**app/Domains/Admin/Views/admin/reports/annual-order/index.blade.php**

**元件：**
- 年份多選器（勾選框）
- 數據矩陣表格（可排序）
- 匯出按鈕

---

## 十一、技術細節

### 11.1 XLSX 匯出套件

**使用 PhpSpreadsheet**
```bash
composer require phpoffice/phpspreadsheet
```

**匯出範例：**
```php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', '訂單總金額');
// ... 填充數據

$writer = new Xlsx($spreadsheet);
$filePath = storage_path("app/reports/exports/營運月報_{$year}_{$month}.xlsx");
$writer->save($filePath);

return response()->download($filePath)->deleteFileAfterSend(true);
```

### 11.2 資料來源查詢範例

**從主資料庫統計並寫入 sysdata：**

```php
use App\Domains\Admin\Models\Report\MonthlyOperationReport;
use App\Domains\Admin\Models\Report\MonthlyProductReport;
use Illuminate\Support\Facades\DB;

// 假設主資料庫的訂單表、進貨表、客戶表、產品表

// 1. 統計營運月報數據
$orderStats = DB::table('orders')
    ->whereYear('order_date', $year)
    ->whereMonth('order_date', $month)
    ->selectRaw('
        SUM(total_amount) as order_total_amount,
        COUNT(*) as order_count,
        COUNT(DISTINCT customer_id) as order_customer_count
    ')
    ->first();

// 2. 統計新客戶數量（該月首次下單）
$newCustomerCount = DB::table('orders as o1')
    ->whereYear('o1.order_date', $year)
    ->whereMonth('o1.order_date', $month)
    ->whereNotExists(function ($query) use ($year, $month) {
        $query->select(DB::raw(1))
            ->from('orders as o2')
            ->whereColumn('o2.customer_id', 'o1.customer_id')
            ->where(function ($q) use ($year, $month) {
                $q->where(DB::raw('YEAR(o2.order_date)'), '<', $year)
                  ->orWhere(function ($q2) use ($year, $month) {
                      $q2->where(DB::raw('YEAR(o2.order_date)'), '=', $year)
                         ->where(DB::raw('MONTH(o2.order_date)'), '<', $month);
                  });
            });
    })
    ->distinct('customer_id')
    ->count();

// 3. 統計進貨數據
$purchaseStats = DB::table('purchases')
    ->whereYear('purchase_date', $year)
    ->whereMonth('purchase_date', $month)
    ->selectRaw('
        SUM(total_amount) as purchase_total_amount,
        COUNT(DISTINCT supplier_id) as supplier_count
    ')
    ->first();

// 4. 寫入營運月報主表
MonthlyOperationReport::updateOrCreate(
    ['year' => $year, 'month' => $month],
    [
        'order_total_amount' => $orderStats->order_total_amount ?? 0,
        'order_count' => $orderStats->order_count ?? 0,
        'order_customer_count' => $orderStats->order_customer_count ?? 0,
        'new_customer_count' => $newCustomerCount,
        'purchase_total_amount' => $purchaseStats->purchase_total_amount ?? 0,
        'supplier_count' => $purchaseStats->supplier_count ?? 0,
        'updated_at' => now(),
    ]
);

// 5. 統計商品月報數據（所有商品）
$productStats = DB::table('order_items')
    ->join('orders', 'order_items.order_id', '=', 'orders.id')
    ->join('products', 'order_items.product_id', '=', 'products.id')
    ->whereYear('orders.order_date', $year)
    ->whereMonth('orders.order_date', $month)
    ->selectRaw('
        products.code as product_code,
        products.name as product_name,
        SUM(order_items.quantity) as quantity,
        SUM(order_items.subtotal) as total_amount
    ')
    ->groupBy('products.id', 'products.code', 'products.name')
    ->get();

// 6. 寫入商品月報表（先刪除舊數據）
MonthlyProductReport::where('year', $year)->where('month', $month)->delete();

foreach ($productStats as $product) {
    MonthlyProductReport::create([
        'year' => $year,
        'month' => $month,
        'product_code' => $product->product_code,
        'product_name' => $product->product_name,
        'quantity' => $product->quantity,
        'total_amount' => $product->total_amount,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
```

**說明：**
- 統計邏輯需根據實際的訂單表、進貨表、客戶表、產品表結構調整
- 新客戶數量計算：找出該月首次下單的客戶（該客戶在此之前沒有任何訂單記錄）
- 商品月報包含所有有銷售的商品，前十大在查詢時動態取得

---

## 十二、權限與安全

### 12.1 路由中介層
```php
Route::middleware(['auth:admin', 'permission:view-reports'])
    ->prefix('reports')
    ->name('reports.')
    ->group(function () {
        // 所有報表路由
    });
```

### 12.2 操作權限
- **查看報表**：`view-reports`
- **匯出報表**：`export-reports`
- **重建數據**：`rebuild-reports`（限管理員）

---

## 十三、擴展性考量

### 未來可新增報表類型
1. **客戶消費分析**
   - 月度前十大客戶
   - 客戶消費趨勢

2. **區域銷售分析**
   - 按地區統計訂單

3. **利潤分析**
   - 毛利率統計

### 新增報表步驟
1. 在 `reports.sqlite` 新增表格
2. 建立對應 Model
3. 建立 Service 處理業務邏輯
4. 建立 Controller 處理請求
5. 建立 Blade 視圖
6. 新增排程 Command

---

## 十四、測試計劃

### 14.1 單元測試
- Service 層數據計算正確性
- Model 關聯查詢

### 14.2 功能測試
- 報表生成流程
- XLSX 匯出內容驗證
- 手動重建功能

### 14.3 效能測試
- 大量數據（10 年歷史）查詢效能
- MariaDB 索引效能驗證

---

## 十五、實作優先順序

### Phase 1: 基礎建設（第 1-2 天）
- [ ] 建立 Migration 檔案（放在 database/migrations/reports/）
- [ ] 執行 Migration 建立 sysdata 資料庫表
- [ ] 建立所有 Model（MonthlyOperationReport、MonthlyProductReport）
- [ ] 建立 Trait: UsesSysdataConnection

### Phase 2: 營運月報（第 3-5 天）
- [ ] OperationReportService
- [ ] OperationMonthlyReportController
- [ ] Blade 列表與詳情頁
- [ ] XLSX 匯出功能
- [ ] Command: update-operation-monthly

### Phase 3: 年度訂單分析（第 6-7 天）
- [ ] AnnualOrderReportService
- [ ] AnnualOrderReportController
- [ ] Blade 矩陣頁面
- [ ] 匯出功能
- [ ] Command: update-annual-order

### Phase 4: 完善與測試（第 8-9 天）
- [ ] 歷史數據回填
- [ ] 排程設定
- [ ] 權限控制
- [ ] 測試與 Bug 修復

### Phase 5: 優化（第 10 天）
- [ ] 效能優化
- [ ] UI/UX 調整
- [ ] 文件補充

---

## 十六、注意事項

1. **Migration 執行**
   - Migration 檔案放在 `database/migrations/reports/` 子目錄
   - 執行時需指定路徑：`php artisan migrate --path=database/migrations/reports`

2. **數據一致性**
   - 主資料庫訂單若有修改/刪除，需重建對應月份報表
   - 考慮加入「數據版本」欄位追蹤

3. **備份策略**
   - sysdata 資料庫需納入定期備份
   - 匯出重要報表為 XLSX 歸檔

4. **擴展建議**
   - 未來若報表類型增加，可繼續新增表到 sysdata
   - 保持兩張主表結構：操作月報 + 明細數據（如商品月報）

---

## 十七、資料表設計總結

### 兩張核心報表

**1. `monthly_operation_reports` - 營運月報主表**
- 用途：儲存每月營運總覽數據
- 唯一鍵：`(year, month)`
- 8 個統計欄位：訂單總額、訂單數、客戶數、新客戶數、進貨總額、廠商數

**2. `monthly_product_reports` - 商品月報主表**
- 用途：儲存每月每個商品的銷售明細
- 唯一鍵：`(year, month, product_code)`
- 統計欄位：數量、金額

### Excel 匯出對應關係

**營運月報表 XLSX：**
- **Sheet 1（月度總覽）**：從 `monthly_operation_reports` 取單筆資料
- **Sheet 2（前十大商品）**：從 `monthly_product_reports` 按金額排序取前 10 筆

**年度訂單分析 XLSX：**
- 查詢 `monthly_operation_reports` 的 `order_total_amount` 欄位，按年月矩陣呈現

---

## 十八、相關資源

- **PhpSpreadsheet 文件**: https://phpspreadsheet.readthedocs.io/
- **Laravel 多資料庫連線**: https://laravel.com/docs/database#introduction
- **MariaDB 索引優化**: https://mariadb.com/kb/en/optimization-and-indexes/

---

**文件版本**: 2.0
**最後更新**: 2025-10-01
**負責人**: Development Team
