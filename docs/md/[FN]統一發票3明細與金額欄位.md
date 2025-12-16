# 發票項目 orderTotals 處理方案

## 目錄

- [背景說明](#背景說明)
- [台灣電子發票規範](#台灣電子發票規範)
- [方案評估](#方案評估)
- [建議方案](#建議方案)
- [實作範例](#實作範例)
- [特殊情況處理](#特殊情況處理)
- [程式碼修改](#程式碼修改)
- [驗證方法](#驗證方法)
- [替代方案選擇指引](#替代方案選擇指引)
- [相關文件](#相關文件)
- [實作日期](#實作日期)
- [實作內容](#實作內容)
- [發票項目順序](#發票項目順序)
- [API 使用範例](#api-使用範例)
- [orderTotals 處理規則](#ordertotals-處理規則)
- [程式碼位置](#程式碼位置)
- [特點](#特點)
- [測試建議](#測試建議)
- [相關文檔](#相關文檔)
- [後續作業](#後續作業)

---

## 背景說明

`orderTotals` 包含訂單的各種金額項目，需要評估如何在發票中處理。

### orderTotals 項目類型

根據系統分析，`order_totals` 資料表結構：

```php
[
    'order_id' => 訂單ID,
    'code' => 項目代碼（sub_total, discount, coupon, shipping, total等）,
    'title' => 顯示名稱,
    'value' => 金額,
    'sort_order' => 排序,
]
```

### 常見的 orderTotals 項目

1. **sub_total** - 小計（商品總額）
2. **discount** - 折扣（突發情況的指定金額折扣）
3. **coupon** - 優惠券折扣
4. **shipping** - 運費
5. **total** - 總計

---

## 台灣電子發票規範

### 發票項目原則

根據財政部電子發票規範：

1. **商品項目**：發票上應列出實際銷售的商品或服務
2. **折扣處理**：
   - **銷貨折讓**：事後折讓需開立折讓單
   - **現場折扣**：可直接反映在商品單價上
3. **運費**：屬於獨立銷售項目，應單獨列示
4. **稅額**：依照稅率計算

### 發票不應包含

- 總計金額（計算得出，不是項目）
- 小計金額（計算得出，不是項目）

---

## 方案評估

### 方案 A：完整項目化（推薦）

**處理方式：**
1. **商品項目**：主商品 + 加價購（已處理）
2. **運費**：作為獨立發票項目
3. **折扣**：作為負數項目（現場折扣）
4. **優惠券**：作為負數項目（現場折扣）
5. **sub_total, total**：不處理（計算值）

**範例：**
```json
[
  { "name": "便當", "quantity": 3, "price": 100, "subtotal": 300 },
  { "name": "飲料（紅茶）", "quantity": 2, "price": 15, "subtotal": 30 },
  { "name": "運費", "quantity": 1, "price": 60, "subtotal": 60 },
  { "name": "折扣", "quantity": 1, "price": -50, "subtotal": -50 },
  { "name": "優惠券折扣", "quantity": 1, "price": -20, "subtotal": -20 }
]
// 發票總額：320元 (300 + 30 + 60 - 50 - 20)
```

**優點：**
- ✅ 完整反映訂單結構
- ✅ 發票金額與訂單一致
- ✅ 清楚顯示所有費用項目
- ✅ 符合會計需求

**缺點：**
- ❌ 折扣項目較多時，發票項目較長
- ❌ 負數項目可能造成混淆

---

### 方案 B：折讓單處理

**處理方式：**
1. **發票**：只包含商品項目 + 運費
2. **折讓單**：事後開立折讓單處理折扣

**範例：**

**發票項目：**
```json
[
  { "name": "便當", "quantity": 3, "price": 100, "subtotal": 300 },
  { "name": "飲料（紅茶）", "quantity": 2, "price": 15, "subtotal": 30 },
  { "name": "運費", "quantity": 1, "price": 60, "subtotal": 60 }
]
// 發票總額：390元
```

**折讓單：**
```
折讓金額：70元（折扣 50 + 優惠券 20）
實收金額：320元
```

**優點：**
- ✅ 符合正規發票流程
- ✅ 發票項目簡潔
- ✅ 折讓單獨立管理

**缺點：**
- ❌ 需要額外開立折讓單
- ❌ 流程較複雜
- ❌ 發票金額與實收不同（需查看折讓單）

---

### 方案 C：反映實際價格（簡化）

**處理方式：**
1. **調整商品單價**：將折扣平均分攤到商品上
2. **運費**：單獨列示

**範例：**

**原始：**
- 商品總額：330元
- 折扣：-70元
- 運費：60元
- 總計：320元

**調整後：**
```json
[
  {
    "name": "便當（優惠後）",
    "quantity": 3,
    "price": 84.85,  // 100 × (1 - 70/330)
    "subtotal": 254.55
  },
  {
    "name": "飲料（紅茶）",
    "quantity": 2,
    "price": 12.73,  // 15 × (1 - 70/330)
    "subtotal": 25.46
  },
  {
    "name": "運費",
    "quantity": 1,
    "price": 60,
    "subtotal": 60
  }
]
// 發票總額：320元（約）
```

**優點：**
- ✅ 發票金額與實收一致
- ✅ 沒有負數項目

**缺點：**
- ❌ 商品單價不真實
- ❌ 無法看出原價和折扣
- ❌ 計算複雜（需處理分攤邏輯）
- ❌ 可能產生小數位問題

---

## 建議方案

### 🎯 推薦：方案 A（完整項目化）

**理由：**
1. **準確性**：完整反映訂單結構
2. **簡單性**：不需要額外計算或開立折讓單
3. **透明性**：清楚顯示所有費用和折扣
4. **彈性**：可依需要調整項目順序

### 實作細節

#### 1. 項目順序

```
1. 主商品（按訂單順序）
2. 加價購項目
3. 運費（如有）
4. 折扣項目（負數）
5. 優惠券折扣（負數）
```

#### 2. orderTotals 處理規則

| code | 處理方式 | 發票項目名稱 | 數量 | 單價 | 備註 |
|------|---------|------------|------|------|------|
| `sub_total` | **忽略** | - | - | - | 計算值 |
| `discount` | **獨立項目** | title 欄位值 | 1 | -value | 負數 |
| `coupon` | **獨立項目** | title 欄位值 | 1 | -value | 負數 |
| `shipping` | **獨立項目** | title 欄位值 | 1 | value | 正數 |
| `total` | **忽略** | - | - | - | 計算值 |

#### 3. 程式碼實作位置

**檔案：**`app/Domains/ApiPosV2/Http/Controllers/Sale/InvoiceGroupController.php`

**方法：**修改 `splitOrderInvoiceItems()`

---

## 實作範例

### 訂單範例

```php
// order_products
[
  { "name": "便當", "quantity": 3, "price": 100 },
  { "orderProductOptions": [
      { "name": "飲料", "value": "紅茶", "quantity": 2, "price": 15 }
    ]
  }
]

// order_totals
[
  { "code": "sub_total", "title": "小計", "value": 330 },
  { "code": "discount", "title": "折扣", "value": -50 },
  { "code": "coupon", "title": "優惠券", "value": -20 },
  { "code": "shipping", "title": "運費", "value": 60 },
  { "code": "total", "title": "總計", "value": 320 }
]
```

### 建議的發票項目

```json
{
  "suggested_invoice_items": [
    // 1. 主商品
    {
      "name": "便當",
      "quantity": 3,
      "price": 100.00,
      "subtotal": 300.00,
      "is_tax_included": true,
      "item_tax_type": 1,
      "remark": null
    },
    // 2. 加價購
    {
      "name": "飲料（紅茶）",
      "quantity": 2,
      "price": 15.00,
      "subtotal": 30.00,
      "is_tax_included": true,
      "item_tax_type": 1,
      "remark": "加購項目"
    },
    // 3. 運費
    {
      "name": "運費",
      "quantity": 1,
      "price": 60.00,
      "subtotal": 60.00,
      "is_tax_included": true,
      "item_tax_type": 1,
      "remark": null
    },
    // 4. 折扣（負數）
    {
      "name": "折扣",
      "quantity": 1,
      "price": -50.00,
      "subtotal": -50.00,
      "is_tax_included": true,
      "item_tax_type": 1,
      "remark": "折扣優惠"
    },
    // 5. 優惠券（負數）
    {
      "name": "優惠券",
      "quantity": 1,
      "price": -20.00,
      "subtotal": -20.00,
      "is_tax_included": true,
      "item_tax_type": 1,
      "remark": "優惠券折抵"
    }
  ]
}
```

**發票總額驗證：**
```
300 + 30 + 60 - 50 - 20 = 320元 ✅
```

---

## 特殊情況處理

### 情況 1：無折扣和運費

**orderTotals：**
```json
[
  { "code": "sub_total", "value": 330 },
  { "code": "total", "value": 330 }
]
```

**發票項目：**
```json
[
  { "name": "便當", "quantity": 3, "price": 100, "subtotal": 300 },
  { "name": "飲料（紅茶）", "quantity": 2, "price": 15, "subtotal": 30 }
]
```

---

### 情況 2：多個折扣

**orderTotals：**
```json
[
  { "code": "discount", "title": "會員折扣", "value": -30 },
  { "code": "discount", "title": "活動折扣", "value": -20 },
  { "code": "coupon", "title": "優惠券", "value": -20 }
]
```

**發票項目：**（所有折扣分別列示）
```json
[
  { "name": "會員折扣", "price": -30, "subtotal": -30 },
  { "name": "活動折扣", "price": -20, "subtotal": -20 },
  { "name": "優惠券", "price": -20, "subtotal": -20 }
]
```

---

### 情況 3：免運費

**orderTotals：**
```json
[
  { "code": "shipping", "title": "運費", "value": 60 },
  { "code": "discount", "title": "免運優惠", "value": -60 }
]
```

**發票項目：**
```json
[
  { "name": "運費", "price": 60, "subtotal": 60 },
  { "name": "免運優惠", "price": -60, "subtotal": -60 }
]
```

或簡化為：**不列示運費**（前端邏輯：如果運費和折扣抵銷，則不顯示）

---

## 程式碼修改

### 修改 splitOrderInvoiceItems()

**新增參數：**
```php
private function splitOrderInvoiceItems(
    Order $order,
    bool $consolidate = true,
    bool $includeOrderTotals = true  // 新增
): array
```

**新增處理邏輯：**
```php
// 步驟 3：處理 orderTotals（如果啟用）
if ($includeOrderTotals && $order->orderTotals) {
    $items = array_merge($items, $this->processOrderTotals($order->orderTotals));
}
```

### 新增 processOrderTotals() 方法

```php
/**
 * 處理 orderTotals 為發票項目
 *
 * @param Collection $orderTotals
 * @return array
 */
private function processOrderTotals($orderTotals): array
{
    $items = [];

    // 需要處理的項目類型
    $includeCodes = ['shipping', 'discount', 'coupon'];

    foreach ($orderTotals as $total) {
        // 只處理指定類型
        if (!in_array($total->code, $includeCodes)) {
            continue;
        }

        // 折扣項目轉為負數
        $value = $total->value;
        if (in_array($total->code, ['discount', 'coupon']) && $value > 0) {
            $value = -$value;
        }

        $items[] = [
            'name' => $total->title,
            'quantity' => 1,
            'price' => $value,
            'subtotal' => $value,
            'is_tax_included' => true,
            'item_tax_type' => 1,
            'remark' => $this->getRemarkForOrderTotal($total->code),
        ];
    }

    return $items;
}

/**
 * 取得 orderTotal 的備註
 *
 * @param string $code
 * @return string|null
 */
private function getRemarkForOrderTotal(string $code): ?string
{
    $remarks = [
        'shipping' => '運費',
        'discount' => '折扣優惠',
        'coupon' => '優惠券折抵',
    ];

    return $remarks[$code] ?? null;
}
```

---

## 驗證方法

### 1. 發票總額驗證

```php
// 驗證：suggested_invoice_items 總額 = order.payment_total
$suggestTotal = array_sum(array_column($suggestedInvoiceItems, 'subtotal'));
assert($suggestTotal == $order->payment_total);
```

### 2. SQL 驗證

```sql
-- 驗證商品總額 + orderTotals = payment_total
SELECT
    order_id,
    (SELECT SUM(price * quantity) FROM order_products WHERE order_id = o.id) +
    (SELECT SUM(subtotal) FROM order_product_options WHERE order_id = o.id AND price > 0) +
    (SELECT SUM(value) FROM order_totals WHERE order_id = o.id AND code IN ('shipping', 'discount', 'coupon'))
    AS calculated_total,
    payment_total
FROM orders o
WHERE id = :order_id;
```

---

## 替代方案選擇指引

### 何時使用方案 A（完整項目化）

- ✅ 需要完整追蹤所有費用
- ✅ 會計需要明細資訊
- ✅ 客戶要求清楚列示折扣
- ✅ 系統已有負數項目處理能力

### 何時使用方案 B（折讓單）

- ✅ 折扣是事後決定的
- ✅ 需要符合傳統發票流程
- ✅ 有專門的折讓單管理系統

### 何時使用方案 C（調整單價）

- ✅ 不希望顯示折扣明細
- ✅ 簡化發票呈現
- ⚠️ 需要注意：會失去原價資訊

---

## 相關文件

- [發票項目整合實作說明](./發票項目整合實作說明.md)
- [發票項目建議功能說明](./發票項目建議功能說明.md)

---

**文件版本**：1.0
**修改日期**：2025-11-25
**修改者**：Claude Code

---

# 發票項目 orderTotals 實作完成

## 實作日期
2025-11-25

---

## 實作內容

已完成 **orderTotals 處理功能**，在發票項目中自動包含運費、折扣、優惠券。

### ✅ 完成項目

1. **修改 splitOrderInvoiceItems()** (InvoiceGroupController.php:794-843)
   - 新增 `$includeOrderTotals` 參數（預設 true）
   - 在整合商品項目後，自動處理 orderTotals

2. **新增 processOrderTotals()** (InvoiceGroupController.php:890-924)
   - 處理運費（正數項目）
   - 處理折扣（轉為負數）
   - 處理優惠券（轉為負數）
   - 忽略 sub_total 和 total（計算值）

3. **新增 getRemarkForOrderTotal()** (InvoiceGroupController.php:932-941)
   - 為不同類型的 orderTotal 提供適當的備註

4. **更新文檔**
   - 發票項目整合實作說明.md（加入 orderTotals 說明）
   - 發票項目orderTotals處理方案.md（完整方案評估）

---

## 發票項目順序

```
1. 主商品（便當、披薩等）
2. 加價購項目（飲料、沙拉等）
3. 運費（如有）
4. 折扣（負數）
5. 優惠券折扣（負數）
```

---

## API 使用範例

### 請求
```
GET /api/pos/v2/invoice-groups/check-order?order_code=25110282
```

### 回應範例
```json
{
  "success": true,
  "available": true,
  "data": {
    "suggested_invoice_items": [
      {
        "name": "便當",
        "quantity": 3,
        "price": 100.00,
        "subtotal": 300.00,
        "is_tax_included": true,
        "item_tax_type": 1,
        "remark": null
      },
      {
        "name": "飲料（紅茶）",
        "quantity": 2,
        "price": 15.00,
        "subtotal": 30.00,
        "is_tax_included": true,
        "item_tax_type": 1,
        "remark": "加購項目"
      },
      {
        "name": "運費",
        "quantity": 1,
        "price": 60.00,
        "subtotal": 60.00,
        "is_tax_included": true,
        "item_tax_type": 1,
        "remark": "運費"
      },
      {
        "name": "折扣",
        "quantity": 1,
        "price": -50.00,
        "subtotal": -50.00,
        "is_tax_included": true,
        "item_tax_type": 1,
        "remark": "折扣優惠"
      },
      {
        "name": "優惠券",
        "quantity": 1,
        "price": -20.00,
        "subtotal": -20.00,
        "is_tax_included": true,
        "item_tax_type": 1,
        "remark": "優惠券折抵"
      }
    ]
  }
}
```

**總金額驗證：**320元 (300+30+60-50-20) ✅

---

## orderTotals 處理規則

| code | 處理方式 | 發票項目名稱 | 金額 | 備註 |
|------|---------|------------|------|------|
| `sub_total` | **忽略** | - | - | 計算值 |
| `discount` | **獨立項目** | 使用 title | 負數 | 折扣優惠 |
| `coupon` | **獨立項目** | 使用 title | 負數 | 優惠券折抵 |
| `shipping` | **獨立項目** | 使用 title | 正數 | 運費 |
| `total` | **忽略** | - | - | 計算值 |
| **金額 = 0** | **忽略** | - | - | **不列出** |

**重要：**金額為 0 的項目會自動過濾，不會出現在發票項目中。

---

## 程式碼位置

### InvoiceGroupController.php

**檔案路徑：**
```
app/Domains/ApiPosV2/Http/Controllers/Sale/InvoiceGroupController.php
```

**修改的方法：**
1. `splitOrderInvoiceItems()` - 行 794-843
2. `processOrderTotals()` - 行 890-924 (新增)
3. `getRemarkForOrderTotal()` - 行 932-941 (新增)

---

## 特點

✅ **預設啟用**：API 不需要額外參數
✅ **自動整合**：相同商品自動合併（name + price）
✅ **完整追蹤**：包含所有費用和折扣項目
✅ **正確順序**：主商品 → 加價購 → 運費 → 折扣
✅ **金額一致**：suggested_invoice_items 總額 = payment_total
✅ **負數處理**：折扣和優惠券自動轉為負數
✅ **彈性備註**：不同類型有對應的備註說明

---

## 測試建議

### 1. 基本測試
```bash
# 測試包含運費和折扣的訂單
curl "http://localhost/api/pos/v2/invoice-groups/check-order?order_code=25110282"
```

### 2. 驗證項目
- ✅ 商品項目整合正確
- ✅ orderTotals 項目出現
- ✅ 折扣和優惠券為負數
- ✅ 總金額 = payment_total
- ✅ 項目順序正確

### 3. 邊界測試
- 無 orderTotals 的訂單
- 只有折扣沒有運費
- 多個折扣項目
- 免運費（運費 + 折扣抵銷）

---

## 相關文檔

- [發票項目整合實作說明](./發票項目整合實作說明.md) - 完整實作說明
- [發票項目整合方案評估](./發票項目整合方案評估.md) - 整合規則評估
- [發票項目建議功能說明](./發票項目建議功能說明.md) - API 功能說明

---

## 後續作業

- ⏳ 測試實際訂單（含運費、折扣、優惠券）
- ⏳ 前端整合 suggested_invoice_items
- ⏳ 驗證發票開立流程
- ⏳ 確認金額計算正確性

---

**實作完成日期**：2025-11-25
**實作者**：Claude Code
**版本**：1.0
