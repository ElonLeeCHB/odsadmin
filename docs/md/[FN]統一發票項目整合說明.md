# 發票項目整合說明

> 為應用多種開票需求，系統採用發票群組概念，相關 API 由 `InvoiceGroupController` 處理。詳細開票方式請參考 [統一發票概述](./統一發票概述.md)。

## 目錄

- [功能概述](#功能概述)
- [背景說明](#背景說明)
- [加價購資料結構](#加價購資料結構)
- [整合方案評估](#整合方案評估)
- [建議方案](#建議方案)
- [拆解邏輯](#拆解邏輯)
- [整合邏輯](#整合邏輯)
- [orderTotals 處理](#ordertotals-處理)
- [範例場景](#範例場景)
- [特殊場景處理](#特殊場景處理)
- [API 使用方式](#api-使用方式)
- [驗證方法](#驗證方法)
- [測試重點](#測試重點)
- [後續作業](#後續作業)
- [相關文件](#相關文件)

---

## 功能概述

在 `InvoiceGroupController` 的 `checkOrder` API 中，新增 `suggest_items` 欄位，自動拆解訂單為建議的發票項目，正確處理加價購邏輯，並整合相同項目。

**實作方案：**`name` + `price` 相同即合併

**特點：**
- ✅ 符合發票規定（同品項同單價）
- ✅ 保留完整資訊（品名清楚）
- ✅ 預設啟用（API 不需帶參數）
- ✅ 參考麥當勞實務標準
- ✅ 處理運費、折扣、優惠券（orderTotals）
- ✅ 正確的項目順序（主商品 → 加價購 → 運費 → 折扣）

**項目順序：**
1. 主商品
2. 加價購項目
3. 運費（如有）
4. 折扣（負數）
5. 優惠券折扣（負數）

---

## 背景說明

當訂單包含多個商品，且每個商品都有相同的加購項目時，發票項目會出現重複。例如：

**訂單範例：**
- 商品A：便當 + 飲料（紅茶）
- 商品B：便當 + 飲料（紅茶）
- 商品C：便當 + 飲料（奶茶）

**未整合的拆解結果：**
```
1. 便當 × 1 = 100元
2. 飲料（紅茶）× 1 = 15元
3. 便當 × 1 = 100元
4. 飲料（紅茶）× 1 = 15元  ← 重複
5. 便當 × 1 = 100元
6. 飲料（奶茶）× 1 = 15元
```

**問題：**飲料（紅茶）出現 2 次，發票項目過多。

---

## 加價購資料結構

### 核心問題

訂單中有主商品（如便當）和加購商品（如飲料），開立發票時需要正確拆解項目。

**原本 `order_product_options` 資料表沒有 `price` 欄位**，無法記錄加購價，導致發票拆解時無法判斷：
- 哪些是加價購（price > 0）
- 哪些是免費選項（price = 0）
- 加購的單價是多少

### 解決方案

在 `order_product_options` 表新增 `price` 和 `subtotal` 欄位：

| 欄位 | 型別 | 說明 |
|------|------|------|
| `price` | decimal(13,4) | 選項單價（加購價，0 表示免費選項） |
| `subtotal` | decimal(13,4) | 選項小計（price × quantity） |

### 資料範例

**訂單：**10 個便當，其中 7 個加購飲料

**order_products：**
| name | price | quantity | total | options_total |
|------|-------|----------|-------|---------------|
| 便當 | 100.00 | 10 | 1000.00 | 105.00 |

**order_product_options：**
| name | value | quantity | price | subtotal |
|------|-------|----------|-------|----------|
| 飲料 | 可樂 | 7 | 15.00 | 105.00 |

### 識別規則

發票項目使用 `name` + `value` + `price` 做識別：
- **name**：選項名稱（如「飲料」）
- **value**：選項值（如「可樂」）
- **price**：加購單價

**發票項目名稱格式：**`選項名稱（選項值）`，例如：`飲料（可樂）`

### 歷史資料處理

- 歷史訂單保持 price = 0
- 新訂單才有 price
- 發票拆解時：如果 price = 0，則視為免費選項，不獨立列示

---

## 整合方案評估

### 方案 1：相同 value 加總

**條件：**`order_product_options.value` 相同即合併

**結果：**
```
1. 便當 × 3 = 300元
2. 飲料（紅茶）× 2 = 30元  ← 合併
3. 飲料（奶茶）× 1 = 15元
```

#### ✅ 優點
- 保留完整的口味資訊
- 發票項目簡潔

#### ❌ 缺點
- **無法處理價格不同的情況**
  - 例如：A 商品加購紅茶 15 元，B 商品加購紅茶 20 元
  - 如果只看 `value`，會錯誤合併成「紅茶 × 2 = 35 元」
  - **這違反發票規定！**（同一品項的單價必須一致）

---

### 方案 1A：相同 name + price 才合併（推薦）

**條件：**`name` + `price` 都相同

**結果：**
```
1. 便當 × 3 = 300元
2. 飲料（紅茶）15元 × 2 = 30元  ← 合併（價格相同）
3. 飲料（奶茶）15元 × 1 = 15元
```

**優點：**
- ✅ 符合發票規定（同品項同單價）
- ✅ 保留完整口味資訊
- ✅ 處理價格差異

---

### 方案 2：相同 option_id 且價格相同

**條件：**`option_id` 相同且 `price` 相同即合併

**結果：**
```
1. 便當 × 3 = 300元
2. 飲料 × 3 = 45元  ← 紅茶、奶茶合併
```

#### ❌ 缺點
- **失去口味資訊**
- **不符合實務需求**

---

### 方案 3：相同價格加總

**條件：**`price` 相同即合併

**結果：**
```
1. 便當 × 3 = 300元
2. 加購項目（15元）× 3 = 45元  ← 所有 15 元的合併
```

#### ❌ 缺點
- **完全失去商品資訊**
- **不符合餐飲業實務**

---

### 麥當勞發票實務參考

麥當勞的整合邏輯：
- **品名 + 規格 + 單價 相同** → 合併
- **品名相同但規格不同** → 分開列示

**範例：**
```
✅ 合併
可樂（中）× 3 = 63元  （品名、規格、單價都相同）

❌ 不合併
可樂（中）× 1 = 21元
可樂（大）× 1 = 25元  （規格不同，單價不同）
```

---

## 建議方案

### 🎯 推薦：方案 1A

**合併條件：**`name` + `price` 都相同

**優點：**
1. ✅ **符合發票規定**（同品項同單價）
2. ✅ **保留完整資訊**（品名、口味、規格）
3. ✅ **簡化項目**（相同的合併）
4. ✅ **符合實務**（參考麥當勞做法）

### 對照表

| 方案 | 合併條件 | 結果範例 | 優點 | 缺點 | 推薦度 |
|------|---------|---------|------|------|--------|
| **方案 1** | `value` 相同 | 飲料（紅茶）× 2 | 保留口味 | 無法處理價差 | ⭐⭐ |
| **方案 1A** | `name + price` | 飲料（紅茶）× 2 | 符合規定、保留資訊 | - | ⭐⭐⭐⭐⭐ |
| **方案 2** | `option_id + price` | 飲料 × 3 | 極簡潔 | 失去口味資訊 | ⭐⭐ |
| **方案 3** | `price` 相同 | 加購項目（15元）× 3 | 最簡潔 | 失去所有資訊 | ⭐ |

---

## 拆解邏輯

### splitOrderInvoiceItems() 方法

**邏輯說明：**

#### 1. 主商品
- 每個 `order_products` 產生一個發票項目
- 欄位：`name`, `quantity`, `price`, `subtotal`

#### 2. 加價購選項
- 篩選條件：`order_product_options.price > 0`
- 項目名稱：`選項名稱（選項值）`，例如：`飲料（可樂）`
- 欄位：`name`, `quantity`, `price`, `subtotal`
- 備註：`加購項目`

#### 3. 免費選項
- 篩選條件：`order_product_options.price = 0`
- **不拆分**（不產生獨立發票項目）

---

## 整合邏輯

### consolidateInvoiceItems() 方法

**整合規則：**
- 分組鍵：`name` + `price`
- 相同鍵的項目合併 `quantity` 和 `subtotal`

**整合流程圖：**

```
原始項目
┌──────────────────────────────────────┐
│ 1. 便當 × 1 = 100元                  │
│ 2. 飲料（紅茶）× 1 = 15元            │
│ 3. 便當 × 1 = 100元                  │
│ 4. 飲料（紅茶）× 1 = 15元            │
│ 5. 便當 × 1 = 100元                  │
│ 6. 飲料（奶茶）× 1 = 15元            │
└──────────────────────────────────────┘
              ↓
         分組（name + price）
              ↓
┌──────────────────────────────────────┐
│ "便當|100"                            │
│   → 項目 1, 3, 5                      │
│   → quantity: 1+1+1 = 3               │
│   → subtotal: 100+100+100 = 300       │
│                                        │
│ "飲料（紅茶）|15"                     │
│   → 項目 2, 4                          │
│   → quantity: 1+1 = 2                 │
│   → subtotal: 15+15 = 30              │
│                                        │
│ "飲料（奶茶）|15"                     │
│   → 項目 6                             │
│   → quantity: 1                       │
│   → subtotal: 15                      │
└──────────────────────────────────────┘
              ↓
         整合結果
              ↓
┌──────────────────────────────────────┐
│ 1. 便當 × 3 = 300元                   │
│ 2. 飲料（紅茶）× 2 = 30元             │
│ 3. 飲料（奶茶）× 1 = 15元             │
└──────────────────────────────────────┘
```

---

## orderTotals 處理

### processOrderTotals() 方法

**處理規則：**

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

## 範例場景

### 場景 1：便當 + 飲料加購

**訂單內容：**
- 10個便當（100元/個）
- 7個加購飲料（15元/個）

**原始資料：**
```json
{
  "order_products": [
    {
      "id": 1,
      "name": "便當",
      "price": 100.00,
      "quantity": 10,
      "orderProductOptions": [
        {
          "id": 1,
          "name": "飲料",
          "value": "可樂",
          "quantity": 7,
          "price": 15.00,
          "subtotal": 105.00
        }
      ]
    }
  ]
}
```

**拆解後的 suggest_items：**
```json
[
  {
    "name": "便當",
    "quantity": 10,
    "price": 100.00,
    "subtotal": 1000.00,
    "is_tax_included": true,
    "item_tax_type": 1,
    "remark": null
  },
  {
    "name": "飲料（可樂）",
    "quantity": 7,
    "price": 15.00,
    "subtotal": 105.00,
    "is_tax_included": true,
    "item_tax_type": 1,
    "remark": "加購項目"
  }
]
```

**發票總金額：**1,105元

---

### 場景 2：便當 + 免費口味選項

**訂單內容：**
- 10個便當（100元/個）
- 10個免費口味選擇（雞腿）

**拆解後的 suggest_items：**
```json
[
  {
    "name": "便當",
    "quantity": 10,
    "price": 100.00,
    "subtotal": 1000.00,
    "is_tax_included": true,
    "item_tax_type": 1,
    "remark": null
  }
]
```

**發票總金額：**1,000元（免費口味選項不拆分）

---

### 場景 3：多個商品 + 各自加購 + 整合

**訂單內容：**
- 商品 A：便當 100元 + 飲料（紅茶）15元
- 商品 B：便當 100元 + 飲料（紅茶）15元
- 商品 C：便當 100元 + 飲料（奶茶）15元

**整合結果：**
```json
[
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
    "name": "飲料（奶茶）",
    "quantity": 1,
    "price": 15.00,
    "subtotal": 15.00,
    "is_tax_included": true,
    "item_tax_type": 1,
    "remark": "加購項目"
  }
]
```

**發票總金額：**345 元（300 + 30 + 15）

---

### 場景 4：完整範例（包含 orderTotals）

**訂單資料：**
- 商品：便當 × 3 = 300元
- 加購：飲料（紅茶）× 2 = 30元
- orderTotals:
  - 運費：60元
  - 折扣：-50元
  - 優惠券：-20元

**整合結果：**
```json
[
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
```

**總金額：**320 元（300 + 30 + 60 - 50 - 20）✅

---

## 特殊場景處理

### 場景：價格不同的相同商品

**訂單：**
- 商品 A 加購紅茶 15 元
- 商品 B 加購紅茶 20 元（促銷價）

**整合結果：**
```json
[
  { "name": "飲料（紅茶）", "quantity": 1, "price": 15.00, "subtotal": 15.00 },
  { "name": "飲料（紅茶）", "quantity": 1, "price": 20.00, "subtotal": 20.00 }
]
```

**說明：**不合併（因為 price 不同），符合發票規定。

---

## API 使用方式

### 端點
`GET /api/pos/v2/invoice-groups/check-order`

### 參數
- `order_id`：訂單 ID
- `order_code`：訂單編號

**不需要額外參數**（預設整合已啟用）

### 回傳資料

```json
{
  "success": true,
  "available": true,
  "data": {
    "order_code": "ORD20250001",
    "order_id": 123,
    "payment_total": 320.00,
    "suggest_items": [
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
        "name": "折扣",
        "quantity": 1,
        "price": -10.00,
        "subtotal": -10.00,
        "is_tax_included": true,
        "item_tax_type": 1,
        "remark": "折扣優惠"
      }
    ]
  }
}
```

### 前端使用範例

```javascript
const response = await fetch('/api/pos/v2/invoice-groups/check-order?order_code=25110282');
const result = await response.json();

if (result.success && result.available) {
  const suggestItems = result.data.suggest_items;

  // suggest_items 已整合，可直接使用
  console.log('發票項目數：', suggestItems.length);
  console.log('發票總金額：', suggestItems.reduce((sum, item) => sum + item.subtotal, 0));
}
```

---

## 驗證方法

### 1. 發票總額驗證

```php
// 驗證：suggest_items 總額 = order.payment_total
$suggestTotal = array_sum(array_column($suggestItems, 'subtotal'));
assert($suggestTotal == $order->payment_total);
```

### 2. SQL 驗證

```sql
-- 手動驗證整合結果
SELECT
    CONCAT(op.name, ' (主商品)') AS name,
    op.price,
    COUNT(*) AS count,
    SUM(op.quantity) AS total_quantity,
    SUM(op.price * op.quantity) AS total_subtotal
FROM order_products op
WHERE op.order_id = :order_id
GROUP BY op.name, op.price

UNION ALL

SELECT
    CONCAT(opo.name, '（', opo.value, '）') AS name,
    opo.price,
    COUNT(*) AS count,
    SUM(opo.quantity) AS total_quantity,
    SUM(opo.subtotal) AS total_subtotal
FROM order_product_options opo
WHERE opo.order_id = :order_id
    AND opo.price > 0
GROUP BY opo.name, opo.value, opo.price;
```

---

## 測試重點

### 1. 整合邏輯測試
- ✅ 相同 name + price → 合併
- ✅ 不同 name 或 price → 不合併
- ✅ 數量和小計正確累加

### 2. 拆解邏輯驗證
- ✅ 主商品：price = order_products.price
- ✅ 加價購：price = order_product_options.price（price > 0）
- ✅ 免費選項不出現在 suggest_items

### 3. 金額驗證
- ✅ 整合前後總金額一致
- ✅ suggest_items 總額 = payment_total

### 4. 邊界測試
- ✅ 單一商品不整合（保持原樣）
- ✅ 全部相同整合為 1 項
- ✅ 空訂單處理
- ✅ 無 orderTotals 的訂單
- ✅ 只有折扣沒有運費
- ✅ 多個折扣項目

---

## 後續作業

- ⏳ 前端整合 suggest_items
- ⏳ 驗證發票開立流程
- ⏳ 測試實際訂單（含運費、折扣、優惠券）
- ⏳ 確認金額計算正確性

---

## 相關文件

- [發票項目 orderTotals 處理方案](./發票項目orderTotals處理方案.md)
- [訂單儲存 price 和 subtotal 修改摘要](./訂單儲存price和subtotal修改摘要.md)
- [回填 order_product_options 價格說明](./回填order_product_options價格說明.md)

---

**文件版本**：1.0
**修改日期**：2025-11-25
**修改者**：Claude Code
