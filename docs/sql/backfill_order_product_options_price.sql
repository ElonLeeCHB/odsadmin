-- ============================================================
-- 回填 order_product_options 的 price 和 subtotal 欄位
-- ============================================================
-- 說明：
-- 1. 從 product_option_values 表取得加購價格
-- 2. 根據 price_prefix 判斷加價/減價/免費
--    - '+' → 加價（正數）
--    - '-' → 減價（負數）
--    - 其他 → 免費（0）
-- 3. 計算 subtotal = price × quantity
--
-- 執行前請先備份！
-- 目標：181,373 筆記錄
-- ============================================================

-- ============================================================
-- 步驟 0：檢查目前狀態
-- ============================================================
SELECT
    '需要更新的記錄數' AS description,
    COUNT(*) AS count
FROM order_product_options
WHERE price IS NULL OR price = 0;

SELECT
    '有加價的記錄數（price > 0）' AS description,
    COUNT(*) AS count
FROM order_product_options opo
INNER JOIN product_option_values pov ON opo.product_option_value_id = pov.id
WHERE (opo.price IS NULL OR opo.price = 0)
    AND pov.price > 0
    AND pov.price_prefix = '+';

-- ============================================================
-- 步驟 1：預覽更新結果（前10筆）
-- ============================================================
-- 預覽更新後的數值，確認邏輯正確
SELECT
    opo.id,
    opo.product_option_value_id,
    opo.quantity,
    opo.price AS old_price,
    opo.subtotal AS old_subtotal,
    pov.price AS pov_price,
    pov.price_prefix,
    -- 計算新的 price
    CASE
        WHEN pov.price_prefix = '+' THEN pov.price
        WHEN pov.price_prefix = '-' THEN -pov.price
        ELSE 0
    END AS new_price,
    -- 計算新的 subtotal
    ROUND(
        CASE
            WHEN pov.price_prefix = '+' THEN pov.price
            WHEN pov.price_prefix = '-' THEN -pov.price
            ELSE 0
        END * opo.quantity,
        4
    ) AS new_subtotal
FROM order_product_options opo
LEFT JOIN product_option_values pov ON opo.product_option_value_id = pov.id
WHERE (opo.price IS NULL OR opo.price = 0)
LIMIT 10;

-- ============================================================
-- 步驟 2：開始交易（建議）
-- ============================================================
-- 在正式環境執行時，建議使用交易
-- START TRANSACTION;

-- ============================================================
-- 步驟 3：執行更新（主要語句）
-- ============================================================
-- 更新 price 和 subtotal 欄位
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

-- ============================================================
-- 步驟 4：驗證更新結果
-- ============================================================
-- 檢查更新後的狀態
SELECT
    '更新後還有多少 price = 0 的記錄' AS description,
    COUNT(*) AS count
FROM order_product_options
WHERE price = 0;

SELECT
    '更新後有多少 price > 0 的記錄' AS description,
    COUNT(*) AS count
FROM order_product_options
WHERE price > 0;

SELECT
    '更新後有多少 price < 0 的記錄（減價）' AS description,
    COUNT(*) AS count
FROM order_product_options
WHERE price < 0;

-- 檢查 subtotal 計算是否正確（抽樣10筆）
SELECT
    id,
    quantity,
    price,
    subtotal,
    ROUND(price * quantity, 4) AS calculated_subtotal,
    CASE
        WHEN ABS(subtotal - ROUND(price * quantity, 4)) < 0.0001 THEN 'OK'
        ELSE 'ERROR'
    END AS validation
FROM order_product_options
WHERE price != 0
LIMIT 10;

-- ============================================================
-- 步驟 5：提交或回滾（如果使用交易）
-- ============================================================
-- 確認無誤後提交：
-- COMMIT;

-- 如果有問題，回滾：
-- ROLLBACK;

-- ============================================================
-- 步驟 6：查看加價購的實際案例
-- ============================================================
-- 查看有加價的訂單（便當 + 飲料範例）
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
ORDER BY opo.order_id, opo.id
LIMIT 20;

-- ============================================================
-- 統計報表
-- ============================================================
SELECT
    '總記錄數' AS type,
    COUNT(*) AS count,
    SUM(subtotal) AS total_amount
FROM order_product_options
UNION ALL
SELECT
    '免費選項（price = 0）' AS type,
    COUNT(*) AS count,
    SUM(subtotal) AS total_amount
FROM order_product_options
WHERE price = 0
UNION ALL
SELECT
    '加價購（price > 0）' AS type,
    COUNT(*) AS count,
    SUM(subtotal) AS total_amount
FROM order_product_options
WHERE price > 0
UNION ALL
SELECT
    '減價（price < 0）' AS type,
    COUNT(*) AS count,
    SUM(subtotal) AS total_amount
FROM order_product_options
WHERE price < 0;

-- ============================================================
-- 完成！
-- ============================================================
-- 更新完成後，請確認：
-- 1. 記錄數正確（181,373 筆）
-- 2. 有加價的選項 price > 0
-- 3. subtotal = price × quantity
-- 4. 沒有異常的 NULL 值
-- ============================================================
