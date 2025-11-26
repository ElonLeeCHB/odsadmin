# 訂單儲存 price 和 subtotal 修改摘要

## 修改目的

確保每次訂單儲存時，`order_product_options` 表的 `price` 和 `subtotal` 欄位能正確寫入加購價格資訊，以支援發票拆解功能。

---

## 修改檔案清單

### 1. OrderProductOptionRepository（共用）
**檔案：**`app/Repositories/Eloquent/Sale/OrderProductOptionRepository.php`

**修改內容：**
- 修改 `prepareData()` 方法
- 新增 `price` 和 `subtotal` 欄位，初始值為 0
- 後續由 SQL UPDATE 補正實際價格

**修改行數：**第 79-100 行

```php
public function prepareData(array $data, $order_id, $order_product_id)
{
    $array = [
        // ... 其他欄位 ...
        'price' => 0,  // 初始為 0，後續由 SQL UPDATE 補正
        'subtotal' => 0,  // 初始為 0，後續由 SQL UPDATE 補正
    ];

    return $array;
}
```

---

### 2. OrderService - ApiPosV2
**檔案：**`app/Domains/ApiPosV2/Services/Sale/OrderService.php`

**修改內容：**
- 修改 `save()` 方法中的 SQL UPDATE 語句
- 新增 `price` 和 `subtotal` 的更新邏輯

**修改行數：**第 250-276 行

```php
// 更新 option_id, option_value_id, map_product_id, price, subtotal
// 為了避免前端錯誤，後端另外處理
if(!empty($order->id)){
    $sql = "
        UPDATE order_product_options AS opo
        JOIN product_option_values AS pov ON pov.id=opo.product_option_value_id
        JOIN option_values AS ov ON ov.id=pov.option_value_id
        SET
            opo.option_id = pov.option_id,
            opo.option_value_id = pov.option_value_id,
            opo.map_product_id = IFNULL(ov.product_id, opo.map_product_id),
            opo.price = CASE
                WHEN pov.price_prefix = '+' THEN pov.price
                WHEN pov.price_prefix = '-' THEN -pov.price
                ELSE 0
            END,
            opo.subtotal = ROUND(
                CASE
                    WHEN pov.price_prefix = '+' THEN pov.price
                    WHEN pov.price_prefix = '-' THEN -pov.price
                    ELSE 0
                END * opo.quantity,
                4
            )
        WHERE opo.order_id = " . $order->id;
    DB::statement($sql);
}
```

---

### 3. OrderService - ApiWwwV2
**檔案：**`app/Domains/ApiWwwV2/Services/Sale/OrderService.php`

**修改內容：**
- 修改 `save()` 方法中的 SQL UPDATE 語句
- 新增 `price` 和 `subtotal` 的更新邏輯

**修改行數：**第 264-290 行

**SQL 內容：**與 ApiPosV2 相同

---

### 4. OrderService - Api（舊版）
**檔案：**`app/Domains/Api/Services/Sale/OrderService.php`

**修改內容：**
- 修改 `save()` 方法中的 SQL UPDATE 語句
- 新增 `price` 和 `subtotal` 的更新邏輯

**修改行數：**第 395-420 行

**SQL 內容：**與 ApiPosV2 相同

---

## 更新邏輯說明

### 1. 初始寫入（OrderProductOptionRepository）
```php
'price' => 0,      // 初始為 0
'subtotal' => 0,   // 初始為 0
```

### 2. SQL 補正（OrderService）
```sql
-- price 計算邏輯
opo.price = CASE
    WHEN pov.price_prefix = '+' THEN pov.price    -- 加價購（正數）
    WHEN pov.price_prefix = '-' THEN -pov.price   -- 減價（負數）
    ELSE 0                                         -- 免費選項
END

-- subtotal 計算邏輯
opo.subtotal = ROUND(opo.price * opo.quantity, 4)
```

### 3. 價格來源
- **資料來源**：`product_option_values` 表
- **關聯欄位**：`product_option_value_id`
- **價格欄位**：`price`
- **價格前綴**：`price_prefix`（`+` / `-` / 其他）

---

## 測試重點

### 1. 新增訂單
- ✅ 訂單儲存後，`order_product_options.price` 正確寫入
- ✅ 訂單儲存後，`order_product_options.subtotal` 正確計算

### 2. 修改訂單
- ✅ 訂單修改後，`order_product_options.price` 正確更新
- ✅ 訂單修改後，`order_product_options.subtotal` 正確更新

### 3. 加價購場景
```
範例：便當 100元 × 10個 + 飲料加購 15元 × 7個

order_products:
- name: 便當
- price: 100.0000
- quantity: 10
- total: 1000.0000

order_product_options:
- name: 飲料
- value: 可樂
- quantity: 7.0000
- price: 15.0000         ← 正確寫入
- subtotal: 105.0000     ← 15 × 7 = 105
```

### 4. 免費選項場景
```
範例：便當口味選擇（免費）

order_product_options:
- name: 便當口味
- value: 雞腿
- quantity: 1.0000
- price: 0.0000          ← 免費選項
- subtotal: 0.0000       ← 0 × 1 = 0
```

---

## 驗證 SQL

### 檢查最近建立的訂單
```sql
SELECT
    opo.id,
    opo.order_id,
    opo.name,
    opo.value,
    opo.quantity,
    opo.price,
    opo.subtotal,
    pov.price AS pov_price,
    pov.price_prefix
FROM order_product_options opo
LEFT JOIN product_option_values pov ON opo.product_option_value_id = pov.id
WHERE opo.order_id = [訂單ID]
ORDER BY opo.id;
```

### 檢查有加價的選項
```sql
SELECT
    opo.id,
    opo.order_id,
    opo.name,
    opo.value,
    opo.quantity,
    opo.price,
    opo.subtotal
FROM order_product_options opo
WHERE opo.price > 0
    AND opo.created_at >= '2025-11-25'
ORDER BY opo.created_at DESC
LIMIT 20;
```

### 驗證計算正確性
```sql
SELECT
    id,
    order_id,
    name,
    quantity,
    price,
    subtotal,
    ROUND(price * quantity, 4) AS calculated_subtotal,
    CASE
        WHEN ABS(subtotal - ROUND(price * quantity, 4)) < 0.0001 THEN 'OK'
        ELSE 'ERROR'
    END AS validation
FROM order_product_options
WHERE created_at >= '2025-11-25'
LIMIT 50;
```

---

## 影響範圍

### ✅ 已修改的 Domain
1. **ApiPosV2**（POS 前端 API）
2. **ApiWwwV2**（官網前端 API）
3. **Api**（舊版 API）

### ℹ️ 未修改的 Domain
- **Admin**（後台管理）- 未發現相同的 SQL UPDATE 語句
- **ApiV2**（V2 版本）- 未發現相同的 SQL UPDATE 語句
- **app/Services/Sale/OrderService.php**（共用 Service）- 未發現相同的 SQL UPDATE 語句

---

## 相關文件

- [發票項目加價購拆解計劃](./發票項目加價購.md)
- [回填 order_product_options 價格說明](./回填order_product_options價格說明.md)
- [SQL 腳本（完整版）](../sql/backfill_order_product_options_price.sql)
- [SQL 腳本（簡化版）](../sql/backfill_order_product_options_price_simple.sql)

---

## 歷史資料回填

### 回填所有歷史資料的 SQL

**說明：**此 SQL 會更新所有 `price` 為 NULL 或 0 的歷史記錄，根據 `product_option_values` 表補正價格和小計。

**執行前請先備份！**

```sql
-- 回填歷史資料（所有 price = 0 或 NULL 的記錄）
UPDATE order_product_options opo
INNER JOIN product_option_values pov ON opo.product_option_value_id = pov.id
SET
    opo.price = CASE
        WHEN pov.price_prefix = '+' THEN pov.price
        WHEN pov.price_prefix = '-' THEN -pov.price
        ELSE 0
    END,
    opo.subtotal = ROUND(
        CASE
            WHEN pov.price_prefix = '+' THEN pov.price
            WHEN pov.price_prefix = '-' THEN -pov.price
            ELSE 0
        END * opo.quantity,
        4
    )
WHERE (opo.price IS NULL OR opo.price = 0);
```

### 執行步驟

1. **備份資料表**
   ```sql
   CREATE TABLE order_product_options_backup_20251125
   AS SELECT * FROM order_product_options;
   ```

2. **執行回填 SQL**
   - 直接執行上方的 UPDATE 語句
   - 預計影響約 181,373 筆記錄

3. **驗證結果**
   ```sql
   -- 檢查有加價的記錄數
   SELECT COUNT(*) FROM order_product_options WHERE price > 0;

   -- 檢查免費選項的記錄數
   SELECT COUNT(*) FROM order_product_options WHERE price = 0;
   ```

### 相關 SQL 腳本

詳細的回填 SQL 腳本（包含檢查、預覽、驗證等步驟）請參考：
- [SQL 腳本（完整版）](../sql/backfill_order_product_options_price.sql)
- [SQL 腳本（簡化版）](../sql/backfill_order_product_options_price_simple.sql)
- [回填說明文件](./回填order_product_options價格說明.md)

---

## 後續作業

1. ✅ 回填歷史資料（使用上方 SQL 腳本）
2. ✅ 修改訂單儲存流程（本次修改）
3. ⏳ 實作發票拆解邏輯（InvoiceGroupController）
4. ⏳ 測試發票開立功能

---

**文件版本**：1.0
**修改日期**：2025-11-25
**修改者**：Claude Code
