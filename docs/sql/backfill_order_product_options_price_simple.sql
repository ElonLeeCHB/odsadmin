-- ============================================================
-- 回填 order_product_options 的 price 和 subtotal（簡化版）
-- ============================================================
-- 執行前請先備份！
-- 預計影響：181,373 筆記錄
-- ============================================================

-- 步驟 1：檢查需要更新的記錄數
SELECT COUNT(*) AS need_update_count
FROM order_product_options
WHERE price IS NULL OR price = 0;

-- 步驟 2：更新 price 和 subtotal
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

-- 步驟 3：驗證更新結果
SELECT
    'price > 0 (加價購)' AS type,
    COUNT(*) AS count,
    SUM(subtotal) AS total
FROM order_product_options WHERE price > 0
UNION ALL
SELECT
    'price = 0 (免費選項)' AS type,
    COUNT(*) AS count,
    SUM(subtotal) AS total
FROM order_product_options WHERE price = 0
UNION ALL
SELECT
    'price < 0 (減價)' AS type,
    COUNT(*) AS count,
    SUM(subtotal) AS total
FROM order_product_options WHERE price < 0;
