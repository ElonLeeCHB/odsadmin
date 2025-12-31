# 日誌系統設計文件

## 📋 目錄

- [系統概述](#系統概述)
- [檔案結構](#檔案結構)
- [日誌格式](#日誌格式)
- [壓縮策略](#壓縮策略)
- [後台查看方案](#後台查看方案)
- [API 設計](#api-設計)
- [實現細節](#實現細節)
- [使用說明](#使用說明)
- [維護指南](#維護指南)

---

## 系統概述

### 設計目標

1. ✅ **易於備份**：使用檔案系統而非資料庫
2. ✅ **節省空間**：自動壓縮舊日誌
3. ✅ **快速查詢**：後台可直接讀取壓縮檔
4. ✅ **結構化**：JSON Lines 格式，便於解析
5. ✅ **完整追蹤**：記錄所有登入、登出、例外

### 核心元件

| 元件 | 檔案 | 功能 |
|------|------|------|
| Repository | `app/Repositories/LogFileRepository.php` | 日誌讀寫核心 |
| Command | `app/Console/Commands/CompressLogsCommand.php` | 壓縮舊日誌 |
| Schedule | `app/Console/Kernel.php` | 自動排程壓縮 |
| Handler | `app/Exceptions/Handler.php` | 例外統一記錄 |
| Controllers | `LoginController.php`, `OAuthController.php` | 登入登出記錄 |

---

## 檔案結構

```
storage/logs/app/
├── logs_2025-11-06.txt          # 今天（未壓縮，快速查詢）
├── logs_2025-11-05.txt          # 昨天（未壓縮）
├── logs_2025-11-04.txt          # 前天（未壓縮）
├── logs_2025-11-03.txt          # 3天前（未壓縮）
├── logs_2025-11-02.txt          # 4天前（未壓縮）
├── logs_2025-11-01.txt          # 5天前（未壓縮）
├── logs_2025-10-31.txt          # 6天前（未壓縮）
├── logs_2025-10.zip             # 10月份壓縮檔（包含 1-30 日）
├── logs_2025-09.zip             # 9月份壓縮檔
└── logs_2025-08.zip             # 8月份壓縮檔
```

### 命名規則

- **每日日誌**：`logs_YYYY-MM-DD.txt`
- **月度壓縮**：`logs_YYYY-MM.zip`

---

## 日誌格式

### JSON Lines 格式

每行一個 JSON 物件，方便逐行解析和搜尋：

```json
{"timestamp":"2025-11-06T10:30:45+08:00","request_trace_id":"1730860245-673abc123","area":"production","url":"https://pos.huabing.tw/api/v1/oauth/login","method":"POST","data":{"account":"user001","password":"***FILTERED***"},"status":"","note":"OAuth 登入成功：user_id=123, username=user001, code=EMP001","client_ip":"192.168.1.100","api_ip":"192.168.1.1"}
{"timestamp":"2025-11-06T10:31:20+08:00","request_trace_id":"1730860280-673abc456","area":"production","url":"https://pos.huabing.tw/api/v1/punchday-stats/month/2025-10","method":"GET","data":{},"status":"","note":"Unauthenticated","client_ip":"192.168.1.100","api_ip":"192.168.1.1"}
```

### 欄位說明

| 欄位 | 類型 | 說明 | 範例 |
|------|------|------|------|
| `timestamp` | string | ISO 8601 時間戳記 | `2025-11-06T10:30:45+08:00` |
| `request_trace_id` | string | 唯一請求 ID（用於追蹤） | `1730860245-673abc123` |
| `area` | string | 環境（production/staging/local） | `production` |
| `url` | string | 完整請求 URL | `https://pos.huabing.tw/api/v1/oauth/login` |
| `method` | string | HTTP 方法 | `POST`, `GET`, `PUT`, `DELETE` |
| `data` | object | 請求資料（已過濾敏感資訊） | `{"account":"user001"}` |
| `status` | string | 狀態（可選） | `success`, `error`, `""` |
| `note` | string | 備註訊息 | `OAuth 登入成功：user_id=123` |
| `client_ip` | string | 客戶端 IP（X-CLIENT-IPV4 Header） | `192.168.1.100` |
| `api_ip` | string | API 伺服器 IP | `192.168.1.1` |

### 敏感資料過濾

自動過濾以下欄位（顯示為 `***FILTERED***`）：
- `password`
- `password_confirmation`
- `token`
- `secret`
- `api_key`

---

## 壓縮策略

### 策略選擇

#### **方案 1：混合模式（推薦）✅**

| 時間範圍 | 狀態 | 說明 |
|---------|------|------|
| 最近 7 天 | 未壓縮 `.txt` | 快速查詢，無需解壓 |
| 8-90 天 | 壓縮 `.zip` | 節省空間，後台仍可讀取 |
| 90 天以上 | 移至備份或刪除 | 長期保存需求 |

**優點**：
- ✅ 最近 7 天快速查詢
- ✅ 節省 80-90% 空間
- ✅ 後台可無縫讀取所有日誌

#### **方案 2：每月壓縮（目前實現）**

| 時間範圍 | 狀態 | 說明 |
|---------|------|------|
| 當月 | 未壓縮 `.txt` | 快速查詢 |
| 歷史月份 | 壓縮 `.zip` | 按月壓縮 |

**排程**：每月 1 日凌晨 2:00 自動壓縮上個月

### 壓縮效果

假設每天產生 100MB 日誌：

| 項目 | 未壓縮 | 壓縮後 | 節省 |
|------|--------|--------|------|
| 單日 | 100 MB | 10-20 MB | 80-90% |
| 單月 | 3 GB | 300-600 MB | 80-90% |
| 單年 | 36 GB | 3.6-7.2 GB | 80-90% |

---

## 後台查看方案

### 技術可行性

#### ✅ **直接讀取 ZIP 內容（不解壓）**

PHP 的 `ZipArchive` 支援直接讀取壓縮檔內容：

```php
$zip = new ZipArchive();
if ($zip->open('storage/logs/app/logs_2025-10.zip') === true) {
    // 列出 ZIP 內所有檔案
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);  // logs_2025-10-15.txt
    }

    // 直接讀取特定檔案（不解壓到硬碟）
    $content = $zip->getFromName('logs_2025-10-15.txt');

    $zip->close();
}
```

#### 效能特性

- ✅ **速度快**：讀取 ZIP 內單一檔案幾乎無延遲
- ✅ **記憶體省**：只載入需要的檔案
- ✅ **使用者無感**：後台體驗與讀取 `.txt` 相同

### 後台功能設計

#### 1. **日誌列表頁**

顯示所有可用的日誌檔案：

| 日期 | 類型 | 大小 | 操作 |
|------|------|------|------|
| 2025-11-06 | 📝 TXT | 25 MB | 查看 |
| 2025-11-05 | 📝 TXT | 98 MB | 查看 |
| 2025-10-31 | 📦 ZIP | 15 MB | 查看（共 31 個檔案） |
| 2025-09-30 | 📦 ZIP | 12 MB | 查看（共 30 個檔案） |

#### 2. **日誌內容頁**

功能：
- ✅ 選擇日期自動判斷讀取來源（`.txt` 或 `.zip`）
- ✅ 分頁顯示（避免載入過多資料）
- ✅ 關鍵字搜尋（例如：搜尋特定用戶）
- ✅ 篩選條件：
  - HTTP Method（GET, POST, PUT, DELETE）
  - 狀態（success, error）
  - 時間範圍
  - IP 地址

#### 3. **日誌詳情頁**

點擊單筆日誌顯示完整 JSON 內容：

```json
{
  "timestamp": "2025-11-06T10:30:45+08:00",
  "request_trace_id": "1730860245-673abc123",
  "area": "production",
  "url": "https://pos.huabing.tw/api/v1/oauth/login",
  "method": "POST",
  "data": {
    "account": "user001",
    "password": "***FILTERED***"
  },
  "status": "",
  "note": "OAuth 登入成功：user_id=123, username=user001",
  "client_ip": "192.168.1.100",
  "api_ip": "192.168.1.1"
}
```

---

## API 設計

### 端點規劃

#### 1. **列出所有日誌檔案**

```http
GET /api/admin/logs
```

**回應範例**：
```json
{
  "success": true,
  "data": [
    {
      "date": "2025-11-06",
      "type": "txt",
      "size": "25 MB",
      "size_bytes": 26214400,
      "modified": "2025-11-06 15:30:00",
      "readable": true
    },
    {
      "date": "2025-10",
      "type": "zip",
      "size": "350 MB",
      "size_bytes": 367001600,
      "modified": "2025-11-01 02:00:00",
      "files_count": 31,
      "readable": true
    }
  ]
}
```

#### 2. **讀取特定日期的日誌**

```http
GET /api/admin/logs/{date}?page=1&limit=100&search=user001
```

**參數**：
- `date`: `YYYY-MM-DD` 或 `YYYY-MM`（自動判斷 txt/zip）
- `page`: 頁碼（預設 1）
- `limit`: 每頁筆數（預設 100，最大 1000）
- `search`: 搜尋關鍵字（可選）
- `method`: 篩選 HTTP Method（可選）
- `status`: 篩選狀態（可選）

**回應範例**：
```json
{
  "success": true,
  "data": {
    "logs": [
      {
        "timestamp": "2025-11-06T10:30:45+08:00",
        "request_trace_id": "1730860245-673abc123",
        "method": "POST",
        "url": "/api/v1/oauth/login",
        "note": "OAuth 登入成功：user_id=123",
        "client_ip": "192.168.1.100"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "total_records": 500,
      "per_page": 100
    }
  }
}
```

#### 3. **搜尋日誌**

```http
POST /api/admin/logs/search
```

**請求 Body**：
```json
{
  "keyword": "user001",
  "date_from": "2025-10-01",
  "date_to": "2025-11-06",
  "method": "POST",
  "limit": 100
}
```

#### 4. **下載日誌**

```http
GET /api/admin/logs/{date}/download
```

回應：直接下載檔案（`.txt` 或 `.zip`）

---

## 實現細節

### LogFileRepository 方法擴充

需要新增以下方法：

```php
// 1. 讀取日誌（自動判斷 txt/zip）
public function readLogs(string $date, int $page = 1, int $limit = 100, ?string $search = null): array

// 2. 從 ZIP 中讀取特定日期
public function readLogsFromZip(string $zipPath, string $date, int $page = 1, int $limit = 100): array

// 3. 列出 ZIP 內的所有檔案
public function listFilesInZip(string $zipPath): array

// 4. 搜尋日誌（支援多日期、多條件）
public function searchLogs(array $criteria): array

// 5. 取得日誌統計
public function getLogStats(string $date): array
```

### 壓縮指令優化

新增壓縮選項：

```bash
# 壓縮指定日期範圍
php artisan logs:compress --from=2025-10-01 --to=2025-10-07

# 壓縮 7 天前的日誌（推薦）
php artisan logs:compress --days-ago=7

# 只壓縮不刪除原始檔
php artisan logs:compress --keep-original
```

### 排程建議

```php
// app/Console/Kernel.php

// 每週日凌晨 2:00 壓縮 7 天前的日誌
$schedule->command('logs:compress --days-ago=7')
    ->weekly()
    ->sundays()
    ->at('02:00');

// 每月 1 日凌晨 3:00 壓縮上個月
$schedule->command('logs:compress --auto')
    ->monthlyOn(1, '03:00');
```

---

## 使用說明

### 手動操作

#### 壓縮日誌

```bash
# 壓縮上個月
php artisan logs:compress

# 壓縮指定月份
php artisan logs:compress 2025-10

# 列出所有日誌檔案
php artisan logs:compress --list

# 自動壓縮（用於排程）
php artisan logs:compress --auto
```

#### 查看日誌檔案

```bash
# 查看今天的日誌
cat storage/logs/app/logs_2025-11-06.txt

# 搜尋特定內容
grep "user001" storage/logs/app/logs_2025-11-06.txt

# 使用 jq 解析 JSON
cat storage/logs/app/logs_2025-11-06.txt | jq 'select(.note | contains("登入成功"))'

# 查看壓縮檔內容（不解壓）
unzip -l storage/logs/app/logs_2025-10.zip

# 讀取壓縮檔內特定檔案
unzip -p storage/logs/app/logs_2025-10.zip logs_2025-10-15.txt | less
```

### 程式碼範例

#### 記錄日誌

```php
use App\Repositories\LogFileRepository;

// 記錄請求日誌
(new LogFileRepository)->logRequest(note: 'OAuth 登入成功：user_id=123');

// 記錄自訂日誌
(new LogFileRepository)->log([
    'url' => '/custom/action',
    'method' => 'POST',
    'data' => ['key' => 'value'],
    'status' => 'success',
    'note' => '自訂操作成功',
]);
```

#### 讀取日誌

```php
use App\Repositories\LogFileRepository;

$logRepo = new LogFileRepository();

// 讀取特定日期的日誌
$result = $logRepo->readLogsByDate('2025-11-06', limit: 100);

if ($result['success']) {
    foreach ($result['logs'] as $log) {
        echo $log['timestamp'] . ' - ' . $log['note'] . PHP_EOL;
    }
}

// 列出所有日誌檔案
$files = $logRepo->listLogFiles();
```

---

## 維護指南

### 定期檢查

#### 1. **磁碟空間監控**

```bash
# 檢查日誌目錄大小
du -sh storage/logs/app/

# 列出最大的檔案
ls -lh storage/logs/app/ | sort -k5 -h -r | head -10
```

#### 2. **清理舊日誌**

建議保留：
- 未壓縮：最近 7 天
- 壓縮檔：最近 3-12 個月
- 超過 12 個月：移至備份伺服器或刪除

```bash
# 刪除 12 個月前的壓縮檔
find storage/logs/app/ -name "logs_*.zip" -mtime +365 -delete
```

#### 3. **效能優化**

- 如果單日日誌超過 1GB，考慮按小時分割
- 使用 Redis 快取熱門查詢結果
- 考慮使用 ELK Stack（Elasticsearch + Logstash + Kibana）處理大量日誌

### 故障排除

#### 日誌寫入失敗

檢查目錄權限：
```bash
chmod -R 755 storage/logs/app/
chown -R www-data:www-data storage/logs/app/
```

#### ZIP 無法開啟

檢查 PHP ZipArchive 擴充：
```bash
php -m | grep zip
```

如果沒有安裝：
```bash
# Ubuntu/Debian
sudo apt-get install php-zip

# CentOS/RHEL
sudo yum install php-zip
```

---

## 參考資源

### Plesk 日誌管理

Plesk 的日誌壓縮機制（gzip）：
- 位置：`/var/www/vhosts/system/{domain}/logs/`
- 格式：`access_log`, `access_log.1.gz`, `access_log.2.gz`
- 查看：`zcat access_log.1.gz | less`
- 搜尋：`zgrep "keyword" access_log.*.gz`

### 相關工具

- **jq**: JSON 命令列處理工具 - https://jqlang.github.io/jq/
- **ZipArchive**: PHP ZIP 擴充文件 - https://www.php.net/manual/en/class.ziparchive.php
- **Laravel Logging**: https://laravel.com/docs/logging

---

## 版本歷史

| 版本 | 日期 | 變更內容 |
|------|------|---------|
| 1.0 | 2025-11-06 | 初版文件，定義日誌系統架構與壓縮策略 |

---

## 附錄

### A. JSON Lines 格式優勢

相比 JSON Array 格式：

**JSON Lines** ✅ (推薦)
```json
{"id":1,"name":"A"}
{"id":2,"name":"B"}
```

**JSON Array** ❌ (不推薦)
```json
[
  {"id":1,"name":"A"},
  {"id":2,"name":"B"}
]
```

| 特性 | JSON Lines | JSON Array |
|------|-----------|-----------|
| 逐行解析 | ✅ 可以 | ❌ 必須完整載入 |
| 追加寫入 | ✅ 直接 append | ❌ 需要重寫整個檔案 |
| 部分損壞 | ✅ 只影響該行 | ❌ 整個檔案無法解析 |
| 串流處理 | ✅ 支援 | ❌ 不支援 |

### B. 容量規劃

假設條件：
- 平均每個請求產生 500 bytes 日誌
- 每日 100,000 次請求

計算：
- 每日：100,000 × 500 bytes = 50 MB
- 每月：50 MB × 30 = 1.5 GB
- 每年：1.5 GB × 12 = 18 GB

壓縮後（90% 壓縮率）：
- 每月：1.5 GB → 150 MB
- 每年：18 GB → 1.8 GB

---

**文件維護者**：Claude
**最後更新**：2025-11-06
