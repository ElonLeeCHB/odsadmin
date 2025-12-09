# Giveme 電子發票 API 串接文件

## 目錄

1. [文件版本](#文件版本)
2. [系統概述](#系統概述)
3. [環境設定](#環境設定)
4. [API 接口列表](#api-接口列表)
5. [認證機制](#認證機制)
6. [兩階段開票流程](#兩階段開票流程)
7. [欄位對應表](#欄位對應表)
8. [Controller 架構](#controller-架構)
9. [Service 層架構](#service-層架構)
10. [發票作廢](#發票作廢)
11. [發票查詢](#發票查詢)
12. [錯誤處理](#錯誤處理)
13. [發票列印](#發票列印)
14. [測試流程](#測試流程)
15. [總結](#總結)

---

## 文件版本

- **API 版本**: 5.0
- **供應商**: Giveme 電子發票加值中心
- **文檔位置**: `docs/API文檔-Giveme電子發票加值中心.pdf`
- **最後更新**: 2025-10-30

---

## 系統概述

### 供應商資訊
- **名稱**: Giveme 電子發票加值中心
- **Line 客服**: @giveme
- **API 基礎網址**: `https://www.giveme.com.tw/invoice.do`

### 功能特點
- 支援 B2C（一般消費者）發票開立
- 支援 B2B（公司行號）發票開立
- 支援發票作廢
- 支援電子載具（手機條碼、會員載具）
- 支援發票捐贈
- 支援雲列印（需選購雲發票機）
- 支援多種課稅類型（應稅、零稅率、免稅、混合稅）

---

## 環境設定

### .env 設定

**測試環境**：
```env
# Giveme 電子發票 API - 測試環境
GIVEME_INVOICE_API_URL=https://www.giveme.com.tw/invoice.do
GIVEME_INVOICE_TEST_TAX_ID=53418005     # 測試統編
GIVEME_INVOICE_TEST_ACCOUNT=Giveme09    # 測試帳號
GIVEME_INVOICE_TEST_PASSWORD=9VHGCq     # 測試密碼
```

**正式環境**：
```env
# Giveme 電子發票 API - 正式環境
GIVEME_INVOICE_API_URL=https://www.giveme.com.tw/invoice.do
GIVEME_INVOICE_TAX_ID=your_company_tax_id      # 貴公司統一編號
GIVEME_INVOICE_ACCOUNT=your_api_account        # API 帳號
GIVEME_INVOICE_PASSWORD=your_api_password      # API 密碼
```

**注意**：
- 使用前請確認伺服器 IP 已加入白名單
- 正式環境請聯繫 Giveme Line 客服 `@giveme` 取得正式帳號

### Config 設定

建立 `config/invoice.php`：

```php
<?php

return [
    'giveme' => [
        'api_url' => env('GIVEME_INVOICE_API_URL', 'https://www.giveme.com.tw/invoice.do'),
        'tax_id' => env('GIVEME_INVOICE_TAX_ID'),
        'account' => env('GIVEME_INVOICE_ACCOUNT'),
        'password' => env('GIVEME_INVOICE_PASSWORD'),
    ],

    'test' => [
        'tax_id' => env('GIVEME_INVOICE_TEST_TAX_ID', '53418005'),
        'account' => env('GIVEME_INVOICE_TEST_ACCOUNT', 'Giveme09'),
        'password' => env('GIVEME_INVOICE_TEST_PASSWORD', '9VHGCq'),
    ],
];
```

**使用方式**：
```php
// 正式環境
$config = config('invoice.giveme');

// 測試環境
$config = config('invoice.test');
```

---

## API 接口列表

| 功能 | Action | Method | 說明 |
|------|--------|--------|------|
| B2C 發票新增 | `addB2C` | POST | 開立一般消費者發票 |
| B2B 發票新增 | `addB2B` | POST | 開立公司行號發票 |
| 發票作廢 | `cancelInvoice` | POST | 作廢已開立發票 |
| 發票查詢 | `query` | POST | 查詢發票資訊 |
| 發票列印 | `invoicePrint` | GET | 列印發票 |
| 發票圖片列印 | `picture` | POST | 取得發票圖片 |

**完整 URL 格式**：
```
https://www.giveme.com.tw/invoice.do?action={action}
```

---

## 認證機制

### 簽名（Sign）

**公式**：
```
sign = MD5(timeStamp + idno + password).toUpperCase()
```

**PHP 範例**：
```php
$timeStamp = round(microtime(true) * 1000); // 毫秒時間戳
$idno = 'Giveme09';  // 測試證號
$password = '9VHGCq';  // 測試密碼
$sign = strtoupper(md5($timeStamp . $idno . $password));
```

**注意事項**：
1. 時間戳必須使用**毫秒**（13位數字）
2. MD5 結果必須轉為**大寫**
3. 簽名有效期為 **5 分鐘**

---

## 兩階段開票流程

本系統的發票作業分為**兩個獨立階段**：

### 階段一：發票內容建立（本地資料庫操作）

**目的**：在本系統資料庫中準備發票資料

**操作內容**：
- 建立 `invoices` 記錄（買方、賣方、金額、載具等）
- 建立 `invoice_items` 記錄（商品明細、可自訂品項名稱）
- 關聯訂單（透過 `invoice_group_orders`）

**此階段與 Giveme API 無關，純本地操作**

### 階段二：發票開立（串接 Giveme API）

**目的**：將本地發票資料送至 Giveme 開立正式發票

**操作流程**：
```
前端傳送 → { invoice_id, order_id, order_code }
     ↓
後端處理：
  1. 從資料庫查詢 invoices (id = invoice_id)
  2. 從資料庫查詢 invoice_items (invoice_id = invoice_id)
  3. 組裝成 Giveme API 格式
  4. 呼叫 Giveme API (addB2C/addB2B)
  5. 取得正式發票號碼
  6. 更新本地資料庫（invoice_number, api_response_data）
```

**重要原則**：
- ✅ 前端**只傳 ID**：`invoice_id`, `order_id`, `order_code`
- ✅ 後端從資料庫讀取所有發票內容（金額、商品、買方等）
- ❌ 前端**不傳**金額、商品、買方等詳細資料
- 📌 **原因**：避免前端與資料庫資料不一致，確保單一資料來源

---

## 欄位對應表

### invoices 欄位對應 Giveme API

| 本系統欄位 | Giveme API 參數 | B2C | B2B | 說明 |
|-----------|----------------|-----|-----|------|
| `invoice_number` | `code` | ✓ | ✓ | 發票號碼（API回傳） |
| `invoice_date` | `datetime` | ✓ | ✓ | 發票日期 |
| `random_code` | `randomCode` | ✓ | - | 4位隨機碼（API回傳） |
| `content` | `content` | ✓ | ✓ | 總備註 |
| `buyer_name` | `customerName` | ✓ | ✓ | 買方名稱（個人/公司） |
| `email` | `email` | ✓ | ✓ | 客戶 Email |
| `tax_id_number` | `phone` (B2B) | - | ✓ | 買方統編 |
| `carrier_type` + `carrier_number` | `phone` / `orderCode` | ✓ | - | 載具（依類型判斷） |
| `donation_code` | `donationCode` | ✓ | - | 捐贈碼 |
| `tax_state` | `taxState` | - | ✓ | 單價是否含稅 |
| `net_amount` | `sales` | - | ✓ | 未稅金額 |
| `tax_amount` | `amount` | ✓ | ✓ | 稅額 |
| `total_amount` | `totalFee` | ✓ | ✓ | 總金額 |
| `tax_type` | `taxType` | ✓ | ✓ | 課稅類型 |

### 載具參數對應邏輯

```php
// Service 層處理
switch ($invoice->carrier_type) {
    case 'donation':
        $data['state'] = '1';
        $data['donationCode'] = $invoice->donation_code;
        break;

    case 'phone_barcode':
        $data['state'] = '0';
        $data['phone'] = $invoice->carrier_number; // 手機條碼 → phone
        break;

    case 'citizen_cert':
    case 'member_card':
    case 'credit_card':
    case 'icash':
    case 'easycard':
    case 'ipass':
    case 'email':
        $data['state'] = '0';
        $data['orderCode'] = $invoice->carrier_number; // 其他 → orderCode
        break;

    case 'none':
    default:
        $data['state'] = '0';
        // 不傳 phone 和 orderCode（列印紙本）
        break;
}
```

---

## Controller 架構

系統將發票相關 Controller 分為**三種類型**：

### 類型一：發票內容管理（階段一）

| Controller | 說明 | 操作 |
|-----------|------|------|
| `InvoiceController` | 發票 CRUD | 建立、查詢、修改、刪除發票 |
| `InvoiceItemController` | 發票項目 CRUD | 管理發票商品明細 |
| `InvoiceBatchController` | 批次開票 | 處理群組開票情境 |

**前端傳送資料**：完整的發票內容（金額、商品、買方等）
**資料庫操作**：INSERT/UPDATE/DELETE

### 類型二：發票開立測試（階段二 - 測試用）

| Controller | 說明 | 使用場景 | 資料來源 |
|-----------|------|---------|---------|
| `GivemeDataTestController` | 直接測試 API | 開發測試 | 前端直接傳入完整資料 |
| `GivemeTestController` | 模擬正式流程 | 功能測試 | 從資料庫讀取 (by invoice_id) |

**差異說明**：
- **GivemeDataTestController**：測試 API 連線是否正常（不寫入資料庫）
- **GivemeTestController**：測試完整正式流程（SELECT + UPDATE）

### 類型三：發票開立正式（階段二 - 正式環境）

| Controller | 說明 | 使用帳號 |
|-----------|------|---------|
| `GivemeController` | 正式發票開立 | 正式環境帳號 |

**流程與 GivemeTestController 相同**，差異在於使用正式環境帳號。

---

## Service 層架構

```
app\Domains\ApiPosV2\Services\Invoice\
├── GivemeInvoiceService.php      # 主要服務類
├── InvoiceSignature.php          # 簽名產生器
├── InvoiceValidator.php          # 參數驗證器
└── InvoiceFormatter.php          # 格式轉換器
```

---

## 發票作廢

### API 資訊
- **Action**: `cancelInvoice`
- **Method**: `POST`
- **URL**: `https://www.giveme.com.tw/invoice.do?action=cancelInvoice`
- **Content-Type**: `application/json`

### 請求參數

| 參數名稱 | 類型 | 必填 | 說明 |
|---------|------|------|------|
| `timeStamp` | string | ✓ | 當前時間毫秒數（5分鐘內有效） |
| `uncode` | string | ✓ | 貴公司統一編號 |
| `idno` | string | ✓ | API 帳號 |
| `sign` | string | ✓ | 簽名（MD5 加密） |
| `code` | string | ✓ | 發票號碼 |
| `remark` | string | ✓ | 作廢原因 |

### 請求範例（使用測試帳號）

```json
{
  "timeStamp": "1729696800000",
  "uncode": "53418005",
  "idno": "Giveme09",
  "sign": "CALCULATED_MD5_SIGN_IN_UPPERCASE",
  "code": "AB12345678",
  "remark": "客戶要求作廢"
}
```

**注意**：`sign` 欄位需使用當前時間戳計算：
```php
$sign = strtoupper(md5($timeStamp . 'Giveme09' . '9VHGCq'));
```

### 回應參數

| 參數名稱 | 類型 | 說明 |
|---------|------|------|
| `success` | string | 成功：`true`，失敗：`false` |
| `code` | string | 發票號碼（success=true 時回傳） |
| `msg` | string | 錯誤描述 |

### 成功回應範例

```json
{
  "success": "true",
  "code": "AB12345678"
}
```

### 失敗回應範例

```json
{
  "success": "false",
  "msg": "發票不存在或已作廢"
}
```

---

## 發票查詢

### API 資訊
- **Action**: `query`
- **Method**: `POST`
- **URL**: `https://www.giveme.com.tw/invoice.do?action=query`
- **Content-Type**: `application/json`

### 請求參數

| 參數名稱 | 類型 | 必填 | 說明 |
|---------|------|------|------|
| `timeStamp` | string | ✓ | 當前時間毫秒數（5分鐘內有效） |
| `uncode` | string | ✓ | 貴公司統一編號 |
| `idno` | string | ✓ | API 帳號 |
| `sign` | string | ✓ | 簽名（MD5 加密） |
| `code` | string | ✓ | 發票號碼 |

### 請求範例（使用測試帳號）

```json
{
  "timeStamp": "1729696800000",
  "uncode": "53418005",
  "idno": "Giveme09",
  "sign": "CALCULATED_MD5_SIGN_IN_UPPERCASE",
  "code": "AB12345678"
}
```

### 回應參數

| 參數名稱 | 類型 | 說明 |
|---------|------|------|
| `success` | string | 成功：`true`，失敗：`false` |
| `code` | string | 發票號碼 |
| `msg` | string | 錯誤描述 |
| `type` | string | 發票類型：`0`-B2C，`1`-B2B |
| `tranno` | string | 載具（開立時有輸入載具者）/ 買方統編 |
| `email` | string | 郵件 |
| `totalFee` | string | 總金額 |
| `randomCode` | string | 4 位隨機碼 |
| `datetime` | string | 發票日期 |
| `status` | string | 狀態：`0`-正常，`1`-作廢 |
| `delRemark` | string | 作廢說明（status=1 時回傳） |
| `delTime` | string | 作廢時間（status=1 時回傳）<br>範例：`2023-02-02 10:00:00` |
| `details` | array | 商品明細集合 |
| `details.name` | string | 商品名稱 |
| `details.number` | string | 數量 |
| `details.money` | string | 金額 |

### 回應範例

```json
{
  "success": "true",
  "code": "AB12345678",
  "type": "0",
  "tranno": "/ABC1234",
  "email": "customer@example.com",
  "totalFee": "100",
  "randomCode": "5678",
  "datetime": "2025-10-22",
  "status": "0",
  "details": [
    {
      "name": "商品A",
      "number": "1",
      "money": "50"
    },
    {
      "name": "商品B",
      "number": "1",
      "money": "50"
    }
  ]
}
```

---

## 錯誤處理

### 常見錯誤

| 錯誤訊息 | 原因 | 解決方案 |
|---------|------|---------|
| 簽名驗證失敗 | sign 計算錯誤 | 檢查 timeStamp、idno、password 組合<br>確認轉為大寫 |
| 時間戳過期 | timeStamp 超過 5 分鐘 | 使用當前時間的毫秒數 |
| IP 不在白名單 | 來源 IP 未授權 | 在後台新增伺服器 IP 到白名單 |
| 發票號碼不存在 | 查詢不存在的發票 | 確認發票號碼正確 |
| 發票已作廢 | 重複作廢 | 先查詢發票狀態 |

### 錯誤回應格式

```json
{
  "success": "false",
  "msg": "錯誤描述文字"
}
```

---

## 發票列印

### 1. 網頁列印（GET）

#### API 資訊
- **Action**: `invoicePrint`
- **Method**: `GET`
- **URL**: `https://www.giveme.com.tw/invoice.do?action=invoicePrint&code={code}&uncode={uncode}`

#### 請求參數

| 參數名稱 | 類型 | 必填 | 說明 |
|---------|------|------|------|
| `code` | string | ✓ | 發票號碼 |
| `uncode` | string | ✓ | 貴公司統一編號 |

#### 範例

```
GET https://www.giveme.com.tw/invoice.do?action=invoicePrint&code=AB12345678&uncode=12345678
```

### 2. 圖片列印（POST）

#### API 資訊
- **Action**: `picture`
- **Method**: `POST`
- **URL**: `https://www.giveme.com.tw/invoice.do?action=picture`
- **Content-Type**: `application/json`

#### 請求參數

| 參數名稱 | 類型 | 必填 | 說明 |
|---------|------|------|------|
| `timeStamp` | string | ✓ | 當前時間毫秒數（5分鐘內有效） |
| `uncode` | string | ✓ | 貴公司統一編號 |
| `idno` | string | ✓ | API 帳號 |
| `sign` | string | ✓ | 簽名（MD5 加密） |
| `code` | string | ✓ | 發票號碼 |
| `type` | string | ✓ | 選擇需要類型，獲取圖片<br>`1`- 發票證明聯 + 交易明細<br>`2`- 發票證明聯<br>`3`- 交易明細 |

#### 回應

**成功**：返回文件流（Stream），可直接保存為圖片

**失敗**：
```json
{
  "success": "false",
  "code": "AB12345678",
  "msg": "錯誤描述"
}
```

### 3. 雲列印（選配）

**說明**：
1. 需選購雲發票機
2. 需啟用雲列印加值服務
3. 透過 WiFi 或手機分享網路，直接列印感熱紙發票
4. 支援手機 / 平板 / 電腦 / 筆電列印
5. 可多台多人員同時使用

**詳情請洽**：Line 客服 ID `@giveme`

---

## 測試流程

### 1. 準備工作

1. **✅ 已取得測試帳號**
   - 統編: `53418005`
   - 證號: `Giveme09`
   - 密碼: `9VHGCq`

2. **設定白名單**
   - 確認測試伺服器的對外 IP
   - 聯繫 Giveme Line 客服 `@giveme` 在後台加入白名單
   - ⚠️ **重要**：未加入白名單將無法調用 API

3. **設定環境變數**
   - 在 `.env` 中設定測試環境參數：
   ```env
   GIVEME_INVOICE_API_URL=https://www.giveme.com.tw/invoice.do
   GIVEME_INVOICE_TEST_TAX_ID=53418005
   GIVEME_INVOICE_TEST_ACCOUNT=Giveme09
   GIVEME_INVOICE_TEST_PASSWORD=9VHGCq
   ```

### 2. 測試順序

建議按以下順序測試：

1. **B2C 發票開立（基本）**
   - 測試最簡單的應稅發票
   - 確認簽名機制正確
   - 確認基本參數無誤

2. **B2C 發票查詢**
   - 使用步驟 1 的發票號碼查詢
   - 確認發票資訊正確

3. **B2C 發票作廢**
   - 作廢步驟 1 的發票
   - 再次查詢確認狀態為作廢

4. **B2C 發票開立（載具）**
   - 測試手機條碼載具
   - 測試其他編號載具

5. **B2C 發票開立（捐贈）**
   - 測試發票捐贈功能

6. **B2B 發票開立**
   - 測試公司行號發票

7. **零稅率 / 免稅發票**
   - 測試不同課稅類型

8. **混合稅發票**
   - 測試混合課稅類型

### 3. Postman 測試

測試步驟：

1. Method 選擇 `POST`
2. URL: `https://www.giveme.com.tw/invoice.do?action=addB2C`
3. Headers: `Content-Type: application/json`
4. Body: 選擇 `raw` → `JSON`
5. 貼上測試 JSON
6. 點擊 `Send`

### 4. Controller 測試

系統提供兩種測試 Controller：

#### GivemeDataTestController（API 直接測試）

**位置**：`app\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceIssue\GivemeDataTestController.php`

**測試方法**：
- `testSignature()` - 測試簽名算法
- `testB2C()` - 測試 B2C 發票（前端傳完整資料）
- `testB2B()` - 測試 B2B 發票（前端傳完整資料）
- `testQuery()` - 測試發票查詢
- `testCancel()` - 測試發票作廢
- `showConfig()` - 查看當前環境設定

**使用時機**：開發階段測試 API 連線

#### GivemeTestController（完整流程測試）

**位置**：`app\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceIssue\GivemeTestController.php`

**測試方法**：
- `issueB2C()` - 開立 B2C 發票（從資料庫讀取）
- `issueB2B()` - 開立 B2B 發票（從資料庫讀取）
- `cancel()` - 作廢發票
- `query()` - 查詢發票

**前端傳送**：`invoice_id`, `order_id`, `order_code`

**使用時機**：功能測試、UAT 測試

---

## 總結

### 串接重點

1. **兩階段開票**
   - 階段一：本地資料庫建立發票內容
   - 階段二：呼叫 Giveme API 開立正式發票

2. **資料來源**
   - 前端只傳 ID（invoice_id, order_id, order_code）
   - 後端從資料庫讀取所有發票資料
   - 確保單一資料來源（Single Source of Truth）

3. **簽名機制**
   - 使用毫秒時間戳
   - MD5 加密後轉大寫
   - 有效期 5 分鐘

4. **載具對應**
   - 手機條碼 → `phone`
   - 其他載具 → `orderCode`
   - 捐贈 → `donationCode`

5. **測試環境**
   - 使用測試帳號（53418005 / Giveme09 / 9VHGCq）
   - 確認 IP 已加入白名單
   - 按順序測試各種發票類型

### Controller 分類

| 類型 | Controller | 用途 | 資料來源 |
|------|-----------|------|---------|
| 階段一 | InvoiceController | 發票內容管理 | 前端傳完整資料 |
| 階段二（測試） | GivemeDataTestController | API 連線測試 | 前端傳完整資料 |
| 階段二（測試） | GivemeTestController | 完整流程測試 | 資料庫（by ID） |
| 階段二（正式） | GivemeController | 正式發票開立 | 資料庫（by ID） |

### 相關資源

- **API 文檔**: `docs/API文檔-Giveme電子發票加值中心.pdf`
- **Line 客服**: @giveme
- **業務設計文件**: `統一發票概述.md`
- **系統說明**: `CLAUDE.md`

---

**文件版本**: 1.0
**最後更新**: 2025-10-30
**相關文件**: `統一發票概述.md`, `CLAUDE.md`

**文件說明**:
本文件為 Giveme 電子發票 API 串接技術文件，包含：
1. **環境設定**：測試環境與正式環境配置
2. **API 接口**：發票開立、查詢、作廢、列印
3. **認證機制**：簽名算法與安全性
4. **Controller 架構**：測試與正式環境的 Controller 分類
5. **測試指南**：完整的測試流程與 Postman 使用

如需了解發票系統的業務邏輯與資料表設計，請參考 `統一發票概述.md`。
