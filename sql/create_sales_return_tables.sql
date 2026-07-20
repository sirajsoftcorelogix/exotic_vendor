-- =============================================================================
-- Sales return documents (partial returns against any order).
-- Run on your MySQL vendor database before or alongside deploying the
-- sales return module (?page=sales_returns).
--
-- phpMyAdmin / MySQL: paste ONLY the SQL below (no diff markers).
-- Safe to re-run: uses CREATE TABLE IF NOT EXISTS.
--
-- Related code:
--   models/sales_return/SalesReturnSchema.php  (auto-creates same schema)
--   controllers/SalesReturnController.php
--
-- Stock: no change to vp_stock_movements structure. The app writes rows with:
--   movement_type = IN,  ref_type = SALES_RETURN         (restore stock on return)
--   movement_type = OUT, ref_type = SALES_RETURN_CANCEL  (reverse on cancel, Sr Emp+)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `vp_sales_returns` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `return_number` VARCHAR(32) NOT NULL,
  `order_number` VARCHAR(64) NOT NULL,
  `invoice_id` INT UNSIGNED NULL DEFAULT NULL,
  `warehouse_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `return_date` DATE NOT NULL,
  `return_type` VARCHAR(32) NOT NULL DEFAULT 'customer_request',
  `remarks` VARCHAR(500) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'finalized',
  `stock_applied` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vp_sr_return_number` (`return_number`),
  KEY `idx_vp_sr_order_number` (`order_number`),
  KEY `idx_vp_sr_invoice_id` (`invoice_id`),
  KEY `idx_vp_sr_return_date` (`return_date`),
  KEY `idx_vp_sr_warehouse_id` (`warehouse_id`),
  KEY `idx_vp_sr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vp_sales_return_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sales_return_id` INT UNSIGNED NOT NULL,
  `invoice_item_id` INT UNSIGNED NULL DEFAULT NULL,
  `order_row_id` INT UNSIGNED NULL DEFAULT NULL,
  `product_id` INT UNSIGNED NULL DEFAULT NULL,
  `item_code` VARCHAR(64) DEFAULT NULL,
  `size` VARCHAR(64) DEFAULT NULL,
  `color` VARCHAR(64) DEFAULT NULL,
  `return_qty` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
  `stock_applied_qty` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_vp_sri_return` (`sales_return_id`),
  KEY `idx_vp_sri_invoice_item` (`invoice_item_id`),
  KEY `idx_vp_sri_order_row` (`order_row_id`),
  CONSTRAINT `fk_vp_sri_return`
    FOREIGN KEY (`sales_return_id`) REFERENCES `vp_sales_returns` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- return_type values (enforced in app, not a DB ENUM):
--   customer_request | defective | wrong_item | exchange | other
--
-- status values:
--   finalized | cancelled
-- =============================================================================

-- Verify (optional):
-- SHOW TABLES LIKE 'vp_sales_return%';
-- SHOW CREATE TABLE vp_sales_returns\G
-- SHOW CREATE TABLE vp_sales_return_items\G
