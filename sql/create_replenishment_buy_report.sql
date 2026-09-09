-- Daily book replenishment buy list (purchased yes/no).
CREATE TABLE IF NOT EXISTS vp_replenishment_buy_report (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    run_date DATE NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    sku VARCHAR(120) NOT NULL DEFAULT '',
    item_code VARCHAR(120) NOT NULL DEFAULT '',
    title VARCHAR(500) NOT NULL DEFAULT '',
    yesterday_sold_qty INT UNSIGNED NOT NULL DEFAULT 0,
    numsold_replenishment INT UNSIGNED NOT NULL DEFAULT 0,
    lookback_months INT UNSIGNED NOT NULL DEFAULT 0,
    lookback_source VARCHAR(32) NOT NULL DEFAULT '',
    numsold_source VARCHAR(32) NOT NULL DEFAULT '',
    physical_stock INT NOT NULL DEFAULT 0,
    pending_po_qty INT NOT NULL DEFAULT 0,
    available_stock INT NOT NULL DEFAULT 0,
    purchase_threshold_percent INT UNSIGNED NOT NULL DEFAULT 0,
    purchase_threshold_qty INT UNSIGNED NOT NULL DEFAULT 0,
    min_stock_percent INT UNSIGNED NOT NULL DEFAULT 0,
    replenishment_buy_qty INT UNSIGNED NOT NULL DEFAULT 0,
    purchased TINYINT(1) NOT NULL DEFAULT 0,
    purchased_at DATETIME NULL DEFAULT NULL,
    purchased_by INT NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_replenish_buy_run_product (run_date, product_id),
    KEY idx_replenish_buy_purchased (purchased, run_date),
    KEY idx_replenish_buy_sku (sku),
    KEY idx_replenish_buy_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
SELECT id, 'Replenishment Buy Report', 'replenishment_buy_report', 'list', '<i class="fas fa-clipboard-list mr-2"></i>', 1, 1, 55
FROM `modules`
WHERE `parent_id` = 0 AND (`slug` = 'products' OR `module_name` LIKE '%Product%')
  AND NOT EXISTS (SELECT 1 FROM modules WHERE slug = 'replenishment_buy_report' LIMIT 1)
LIMIT 1;
