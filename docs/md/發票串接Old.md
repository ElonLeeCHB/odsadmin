資料表：
invoices 發票主表
ininvoice_items 發票項目表
invoice_order_maps 發票訂單對應表
order_groups 發票群組

補充說明：本系統為服務客戶的需求，發票與訂單的關聯如下：
1.標準：一張訂單對一張發票。一對一。
2.拆單：一張訂單開多張發票，一對多。
3.合併：多張訂單合開一張發票，多對一。
4.群組開票：n張訂單開m張發票。建立一個發票群組，包含2張訂單，3張發票。 order_groups: id; orders:id,order_group_id; invoice:id,order_group_id

路由檔 開發票
D:\Codes\PHP\DTSCorp\Chinabing\ods\htdocs\laravel\app\Domains\ApiPosV2\Routes\apipos.php
```
// 訂單群組
Route::apiResource('order-groups', OrderGroupController::class);
Route::post('order-groups/{id}/attach-order', [OrderGroupController::class, 'attachOrder']);
Route::post('order-groups/{id}/detach-order', [OrderGroupController::class, 'detachOrder']);
Route::post('order-groups/{id}/attach-invoice', [OrderGroupController::class, 'attachInvoice']);
Route::post('order-groups/{id}/detach-invoice', [OrderGroupController::class, 'detachInvoice']);

// 發票
Route::apiResource('invoices', InvoiceController::class);
// 批次新增
Route::post('invoices/batch', [InvoiceBatchController::class, 'store']);
```
這只是本系統對應前端的api，還沒有串接廠商的發票平台。

需要實作：串接廠商的api，取得真實發票號碼。


# Giveme 電子發票 API 串接文件

## 文件版本
- **API 版本**: 5.0
- **供應商**: Giveme 電子發票加值中心
- **文檔位置**: `docs/API文檔-Giveme電子發票加值中心.pdf`
- **最後更新**: 2025-10-22

---

## 目錄
1. [系統概述](#系統概述)
2. [認證機制](#認證機制)
3. [環境設定](#環境設定)
4. [API 接口列表](#api-接口列表)
5. [B2C 發票開立](#b2c-發票開立)
6. [B2B 發票開立](#b2b-發票開立)
7. [發票作廢](#發票作廢)
8. [發票查詢](#發票查詢)
9. [發票列印](#發票列印)
10. [錯誤處理](#錯誤處理)
11. [測試流程](#測試流程)

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

## 認證機制

### 簽名算法 (sign)

**公式**：
```
sign = MD5(timeStamp + idno + password).toUpperCase()
```

**參數說明**：
- `timeStamp`: 當前時間毫秒數（必須在 5 分鐘內有效）
- `idno`: API 帳號
- `password`: API 登錄密碼

**PHP 範例**：
```php
$timeStamp = round(microtime(true) * 1000); // 毫秒時間戳
$idno = 'your_api_account';
$password = 'your_api_password';
$sign = strtoupper(md5($timeStamp . $idno . $password));
```

**注意事項**：
1. 時間戳必須使用**毫秒**（13位數字）
2. MD5 結果必須轉為**大寫**
3. 簽名有效期為 **5 分鐘**
4. 簽名錯誤會導致 API 調用失敗

---

## 環境設定

### 後台設定步驟

#### 1. 新增 API 帳號
**位置**：系統設定 → 員工設定 → 新增 API 帳號

**要求**：
- API 帳號密碼請**複雜化**
- 建議使用強密碼（大小寫英文 + 數字 + 特殊符號）

#### 2. 設定白名單 IP
**位置**：系統設定 → 白名單設定

**要求**：
- 必須輸入貴司**固定 IP**
- 僅白名單內的 IP 可調用 API

### 環境變數設定

建議在 `.env` 中加入：

```env
# Giveme 電子發票 API
GIVEME_INVOICE_API_URL=https://www.giveme.com.tw/invoice.do
GIVEME_INVOICE_UNCODE=12345678          # 貴公司統一編號
GIVEME_INVOICE_IDNO=api_account         # API 帳號
GIVEME_INVOICE_PASSWORD=api_password    # API 密碼
```

### Config 設定

建議新增 `config/invoice.php`：

```php
<?php

return [
    'giveme' => [
        'api_url' => env('GIVEME_INVOICE_API_URL', 'https://www.giveme.com.tw/invoice.do'),
        'uncode' => env('GIVEME_INVOICE_UNCODE'),
        'idno' => env('GIVEME_INVOICE_IDNO'),
        'password' => env('GIVEME_INVOICE_PASSWORD'),
    ],
];
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

## B2C 發票開立

### API 資訊
- **Action**: `addB2C`
- **Method**: `POST`
- **URL**: `https://www.giveme.com.tw/invoice.do?action=addB2C`
- **Content-Type**: `application/json`

### 請求參數

#### 基本參數（必填）

| 參數名稱 | 類型 | 必填 | 說明 |
|---------|------|------|------|
| `timeStamp` | string | ✓ | 當前時間毫秒數（5分鐘內有效） |
| `uncode` | string | ✓ | 貴公司統一編號 |
| `idno` | string | ✓ | API 帳號 |
| `sign` | string | ✓ | 簽名（MD5 加密） |

#### 發票資訊參數

| 參數名稱 | 類型 | 必填 | 說明 |
|---------|------|------|------|
| `datetime` | string | ✓ | 發票日期 `yyyy-MM-dd` 或電腦時間毫秒數 |
| `totalFee` | string/int | ✓ | 總金額（不可為 0，需大於等於 1） |
| `content` | string | ✓ | 總備註（顯示於網頁及發票上） |
| `customerName` | string |  | 內部註記（顯示於網頁，發票上不顯示） |
| `email` | string |  | 郵件（支援單一 mail） |

#### 捐贈參數

| 參數名稱 | 類型 | 必填 | 說明 |
|---------|------|------|------|
| `state` | string | ✓ | 發票捐贈：`0`-無，`1`-捐贈 |
| `donationCode` | string | ✓* | 捐贈碼（當 state=1 時必填） |

#### 載具參數（擇一）

**重要**：如果**不列印感熱紙**，必須填寫 `phone` 或 `orderCode` 其中一個。

| 參數名稱 | 類型 | 必填 | 說明 |
|---------|------|------|------|
| `phone` | string | ✓* | 財政部載具：手機條碼（範例：`/1234567`） |
| `orderCode` | string | ✓* | 編號載具：訂單編號、會員編號等 |

**手機條碼驗證規則**：
1. 第 1 碼為 `/`
2. 其餘 7 碼由數字 `0-9`、大寫英文 `A-Z`、特殊符號 `(+)(-)(.)` 組成

**編號載具範例**：
- 訂單號碼：蝦皮單號、FB 單號、訂單編號
- 行動電話：消費者手機號碼
- 自訂：會員編號或不重複編號

#### 課稅參數

| 參數名稱 | 類型 | 必填 | 說明 |
|---------|------|------|------|
| `taxType` | int |  | 課稅別類型：`0`-應稅，`1`-零稅率，`2`-免稅，`3`-特種稅，`4`-混合稅<br>（預設跟隨系統設定） |

**混合稅類型額外參數**（當 taxType=4 時）：

| 參數名稱 | 類型 | 必填 | 說明 |
|---------|------|------|------|
| `companyCode` | string |  | 客戶統一編號（選填） |
| `freeAmount` | int | ✓ | 免稅銷售額合計 |
| `zeroAmount` | int | ✓ | 零稅率銷售額合計 |
| `sales` | int | ✓ | 應稅銷售額合計 |
| `amount` | int | ✓ | 稅額 |

#### 零稅率參數

當 `taxType=1` 或混合稅中包含零稅率時：

| 參數名稱 | 類型 | 必填 | 說明 |
|---------|------|------|------|
| `customsMark` | string | ✓ | 通關方式：`0`-非經海關出口，`1`-經海關出口 |
| `zeroRemark` | string | ✓ | 零稅率原因（填寫 71-79） |

**零稅率原因代碼**：
- `71`: 第一款 外銷貨物
- `72`: 第二款 與外銷有關之勞務，或在國內提供而在國外使用之勞務
- `73`: 第三款 依法設立之免稅商店銷售與過境或出境旅客之貨物
- `74`: 第四款 銷售與保稅區營業人供營運之貨物或勞務
- `75`: 第五款 國際間之運輸
- `76`: 第六款 國際運輸用之船舶、航空器及遠洋漁船
- `77`: 第七款 銷售與國際運輸用之船舶、航空器及遠洋漁船所使用之貨物或修繕勞務
- `78`: 第八款 保稅區營業人銷售與課稅區營業人未輸往課稅區而直接出口之貨物
- `79`: 第九款 保稅區營業人銷售與課稅區營業人存入自由港區事業或海關管理之保稅倉庫、物流中心以供外銷之貨物

#### 商品明細（items）

`items` 為商品集合（陣列），每個商品包含：

| 參數名稱 | 類型 | 必填 | 說明 |
|---------|------|------|------|
| `name` | string | ✓ | 商品名稱（請勿有特殊符號） |
| `money` | int | ✓ | 單價（可為 0，至少需有一筆為 1 以上） |
| `number` | int | ✓ | 數量 |
| `taxType` | int | ✓* | 商品課稅別類型：`0`-應稅，`1`-零稅率，`2`-免稅<br>（混合稅類型必填） |
| `remark` | string |  | 單一商品備註（請勿有特殊符號） |

### 請求範例

```json
{
  "timeStamp": "1698123456789",
  "uncode": "12345678",
  "idno": "api_account",
  "sign": "A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6",
  "customerName": "測試客戶",
  "phone": "/ABC1234",
  "datetime": "2025-10-22",
  "email": "customer@example.com",
  "state": "0",
  "taxType": 0,
  "totalFee": "100",
  "content": "感謝惠顧",
  "items": [
    {
      "name": "商品A",
      "money": 50,
      "number": 1,
      "remark": ""
    },
    {
      "name": "商品B",
      "money": 50,
      "number": 1,
      "remark": ""
    }
  ]
}
```

### 回應參數

| 參數名稱 | 類型 | 說明 |
|---------|------|------|
| `success` | string | 成功：`true`，失敗：`false` |
| `code` | string | 發票號碼（success=true 時回傳） |
| `msg` | string | 錯誤描述 |
| `totalFee` | string | 開立發票商品總金額 |
| `orderCode` | string | 編號載具（會員載具），無資料則空值 |
| `phone` | string | 財政部手機條碼載具，無資料則空值 |

### 成功回應範例

```json
{
  "success": "true",
  "code": "AB12345678",
  "totalFee": "100",
  "orderCode": "",
  "phone": "/ABC1234"
}
```

### 失敗回應範例

```json
{
  "success": "false",
  "msg": "簽名驗證失敗"
}
```

---

## B2B 發票開立

### API 資訊
- **Action**: `addB2B`
- **Method**: `POST`
- **URL**: `https://www.giveme.com.tw/invoice.do?action=addB2B`
- **Content-Type**: `application/json`

### 請求參數

#### 基本參數（必填）

與 B2C 相同的基本參數：`timeStamp`, `uncode`, `idno`, `sign`

#### 發票資訊參數

| 參數名稱 | 類型 | 必填 | 說明 |
|---------|------|------|------|
| `phone` | string | ✓ | **買方統一編號**（B2B 必填） |
| `datetime` | string | ✓ | 發票日期 `yyyy-MM-dd` 或電腦時間毫秒數 |
| `taxState` | string | ✓ | 單價是否含稅：`0`-含稅（預設），`1`-未稅 |
| `totalFee` | string | ✓ | 總金額（不可為 0，需大於等於 1） |
| `amount` | string | ✓ | 稅額 |
| `sales` | string | ✓ | 未稅銷售額 |
| `content` | string | ✓ | 總備註（顯示於網頁及發票上） |
| `customerName` | string |  | 買方公司名稱（非必填） |
| `email` | string |  | 郵件（支援單一 mail） |
| `taxType` | int |  | 課稅別類型：`0`-應稅，`1`-零稅率，`2`-免稅<br>（預設跟隨系統設定） |

#### 商品明細（items）

| 參數名稱 | 類型 | 必填 | 說明 |
|---------|------|------|------|
| `name` | string | ✓ | 商品名稱（請勿有特殊符號） |
| `money` | double | ✓ | 單價（最多兩位小數） |
| `number` | int | ✓ | 數量 |
| `remark` | string |  | 單一商品備註（請勿有特殊符號） |

#### 零稅率參數（當 taxType=1 時）

| 參數名稱 | 類型 | 必填 | 說明 |
|---------|------|------|------|
| `customsMark` | string | ✓ | 通關方式：`0`-非經海關出口，`1`-經海關出口 |
| `zeroRemark` | string | ✓ | 零稅率原因（填寫 71-79） |

### 請求範例

```json
{
  "timeStamp": "1698123456789",
  "uncode": "12345678",
  "idno": "api_account",
  "sign": "A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6",
  "customerName": "ABC公司",
  "phone": "87654321",
  "datetime": "2025-10-22",
  "email": "company@example.com",
  "taxState": "0",
  "totalFee": "525",
  "amount": "25",
  "sales": "500",
  "taxType": 0,
  "content": "感謝惠顧",
  "items": [
    {
      "name": "商品A",
      "money": 250.00,
      "number": 2,
      "remark": ""
    }
  ]
}
```

### 回應參數

| 參數名稱 | 類型 | 說明 |
|---------|------|------|
| `success` | string | 成功：`true`，失敗：`false` |
| `code` | string | 發票號碼（success=true 時回傳） |
| `msg` | string | 錯誤描述 |
| `phone` | string | 買方統一編號 |
| `totalFee` | string | 開立發票商品總金額 |

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

### 請求範例

```json
{
  "timeStamp": "1698123456789",
  "uncode": "12345678",
  "idno": "api_account",
  "sign": "A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6",
  "code": "AB12345678",
  "remark": "客戶要求作廢"
}
```

### 回應參數

| 參數名稱 | 類型 | 說明 |
|---------|------|------|
| `success` | string | 成功：`true`，失敗：`false` |
| `code` | string | 發票號碼（success=true 時回傳） |
| `msg` | string | 錯誤描述 |

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

## 發票列印

### 1. 網頁列印（GET）

#### API 資訊
- **Action**: `invoicePrint`
- **Method**: `GET`
- **URL**: `https://www.giveme.com.tw/invoice.do?action=invoicePrint&code={code}&uncode={uncode}`
- **Content-Type**: `application/json`

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

## 錯誤處理

### 常見錯誤

| 錯誤訊息 | 原因 | 解決方案 |
|---------|------|---------|
| 簽名驗證失敗 | sign 計算錯誤 | 檢查 timeStamp、idno、password 組合<br>確認轉為大寫 |
| 時間戳過期 | timeStamp 超過 5 分鐘 | 使用當前時間的毫秒數 |
| IP 不在白名單 | 來源 IP 未授權 | 在後台新增伺服器 IP 到白名單 |
| 發票號碼不存在 | 查詢不存在的發票 | 確認發票號碼正確 |
| 發票已作廢 | 重複作廢 | 先查詢發票狀態 |
| 載具格式錯誤 | phone 手機條碼格式不正確 | 檢查手機條碼格式規則 |
| 總金額不符 | totalFee 與商品總額不符 | 確認計算正確 |

### 錯誤回應格式

```json
{
  "success": "false",
  "msg": "錯誤描述文字"
}
```

---

## 測試流程

### 1. 準備工作

1. **取得測試帳號**
   - 聯繫 Giveme Line 客服 `@giveme`
   - 取得測試環境的 API 帳號密碼
   - 取得測試環境的統一編號

2. **設定白名單**
   - 確認測試伺服器的對外 IP
   - 在 Giveme 後台加入白名單

3. **設定環境變數**
   - 在 `.env` 中設定測試環境參數

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
   - 測試編號載具

5. **B2C 發票開立（捐贈）**
   - 測試發票捐贈功能

6. **B2B 發票開立**
   - 測試公司行號發票

7. **零稅率 / 免稅發票**
   - 測試不同課稅類型

8. **混合稅發票**
   - 測試混合課稅類型

### 3. Postman 測試

參考 PDF 第 1 頁的 Postman 截圖說明：

1. Method 選擇 `POST`
2. URL: `https://www.giveme.com.tw/invoice.do?action=addB2C`
3. Headers: `Content-Type: application/json`
4. Body: 選擇 `raw` → `JSON`
5. 貼上測試 JSON
6. 點擊 `Send`

### 4. Controller 測試

測試 Controller 位置：
```
app\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceTestController.php
```

建議實作以下測試方法：
- `testB2CInvoice()` - 測試 B2C 發票
- `testB2BInvoice()` - 測試 B2B 發票
- `testCancelInvoice()` - 測試作廢
- `testQueryInvoice()` - 測試查詢
- `testSignature()` - 測試簽名算法

---

## 開發建議

### 1. Service 層架構

建議建立以下 Service：

```
app\Domains\ApiPosV2\Services\Invoice\
├── GivemeInvoiceService.php      # 主要服務類
├── InvoiceSignature.php          # 簽名產生器
├── InvoiceValidator.php          # 參數驗證器
└── InvoiceFormatter.php          # 格式轉換器
```

### 2. Model 層

建議建立發票記錄表，保存所有開立的發票：

```php
// app\Domains\ApiPosV2\Models\Invoice.php
class Invoice extends Model
{
    protected $fillable = [
        'invoice_no',      // 發票號碼
        'invoice_type',    // B2C / B2B
        'order_id',        // 關聯訂單
        'total_amount',    // 總金額
        'status',          // 狀態：normal / cancelled
        'request_data',    // 請求資料（JSON）
        'response_data',   // 回應資料（JSON）
        'created_at',
        'updated_at',
    ];
}
```

### 3. 錯誤處理

```php
try {
    $result = $invoiceService->createB2C($data);
} catch (InvoiceException $e) {
    // 記錄錯誤
    Log::error('Invoice API Error: ' . $e->getMessage(), [
        'data' => $data,
        'response' => $e->getResponse(),
    ]);

    // 返回錯誤訊息
    return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
    ], 400);
}
```

### 4. 日誌記錄

建議記錄所有 API 請求/回應：

```php
Log::channel('invoice')->info('Giveme Invoice API Request', [
    'action' => 'addB2C',
    'invoice_no' => $response['code'] ?? null,
    'request' => $requestData,
    'response' => $response,
]);
```

---

## 參考資料

- **API 文檔**: `docs/API文檔-Giveme電子發票加值中心.pdf`
- **Line 客服**: `@giveme`
- **測試 Controller**: `app\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceTestController.php`

---

## 版本記錄

| 版本 | 日期 | 說明 |
|------|------|------|
| 1.0 | 2025-10-22 | 初始版本，根據 Giveme API 5.0 文檔整理 |

---

**整理者**: Claude Code
**最後更新**: 2025-10-22
