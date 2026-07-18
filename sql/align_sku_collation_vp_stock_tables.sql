-- DEFERRED: optional one-time schema cleanup when you are ready to align collations.
-- For now the app uses BINARY sku comparisons in PHP (no ALTER required).
--
-- Align sku collation across core stock tables (fixes MySQL 1267 on sku = sku joins).
--
-- Run on the SAME database the app uses (e.g. exotic_vendor_portal).
-- 1) Inspect current definitions
-- 2) Generate ALTER statements (optional)
-- 3) Apply ALTERs (edit lengths/nullability if step 1 differs)
--
-- Target collation: utf8mb4_general_ci (matches legacy vp_products / stock refresh code).
-- To use utf8mb4_unicode_ci instead, replace utf8mb4_general_ci in all ALTERs below.

-- ---------------------------------------------------------------------------
-- Step 1 — Inspect (run first; pick database in phpMyAdmin left sidebar)
-- ---------------------------------------------------------------------------
SELECT DATABASE() AS current_database;

SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLLATION_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('vp_products', 'vp_stock_movements', 'vp_stock')
  AND COLUMN_NAME = 'sku'
ORDER BY TABLE_NAME;

-- If DATABASE() is NULL or empty result, use explicit schema:
-- SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLLATION_NAME
-- FROM information_schema.COLUMNS
-- WHERE TABLE_SCHEMA = 'exotic_vendor_portal'
--   AND TABLE_NAME IN ('vp_products', 'vp_stock_movements', 'vp_stock')
--   AND COLUMN_NAME = 'sku'
-- ORDER BY TABLE_NAME;

-- Or per table:
-- SHOW FULL COLUMNS FROM vp_products LIKE 'sku';
-- SHOW FULL COLUMNS FROM vp_stock_movements LIKE 'sku';
-- SHOW FULL COLUMNS FROM vp_stock LIKE 'sku';

-- ---------------------------------------------------------------------------
-- Step 2 — Optional: generate MODIFY statements from current metadata
-- (Review output before executing; skip rows already on target collation.)
-- ---------------------------------------------------------------------------
SELECT CONCAT(
    'ALTER TABLE `', TABLE_NAME, '` MODIFY `', COLUMN_NAME, '` ',
    COLUMN_TYPE, ' CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci',
    IF(IS_NULLABLE = 'YES', ' NULL', ' NOT NULL'),
    IF(COLUMN_DEFAULT IS NOT NULL,
        CONCAT(' DEFAULT ', QUOTE(COLUMN_DEFAULT)),
        IF(IS_NULLABLE = 'YES', ' DEFAULT NULL', '')
    ),
    ';'
) AS generated_alter_sql
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('vp_products', 'vp_stock_movements', 'vp_stock')
  AND COLUMN_NAME = 'sku'
  AND (COLLATION_NAME IS NULL OR COLLATION_NAME <> 'utf8mb4_general_ci');

-- ---------------------------------------------------------------------------
-- Step 3 — Manual ALTERs (adjust VARCHAR length / NULL to match Step 1)
-- Typical layouts in this project: vp_products.sku VARCHAR(100) NULL,
-- vp_stock_movements.sku VARCHAR(100) NULL, vp_stock.sku VARCHAR(100) NOT NULL.
-- If your SHOW FULL COLUMNS differs, change the definitions below to match.
-- ---------------------------------------------------------------------------

-- Backup recommended before running on production.

ALTER TABLE `vp_products`
    MODIFY `sku` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;

ALTER TABLE `vp_stock_movements`
    MODIFY `sku` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;

ALTER TABLE `vp_stock`
    MODIFY `sku` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

-- Optional: temp scope table used by legacy stock rebuild (if it exists)
-- ALTER TABLE `_stock_rebuild_scope`
--     MODIFY `sku` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

-- ---------------------------------------------------------------------------
-- Step 4 — Verify all three match
-- ---------------------------------------------------------------------------
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, COLLATION_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('vp_products', 'vp_stock_movements', 'vp_stock')
  AND COLUMN_NAME = 'sku'
ORDER BY TABLE_NAME;
