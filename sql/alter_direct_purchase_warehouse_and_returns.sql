-- =============================================================================
-- Stock warehouse on direct purchase + purchase return documents.
-- Run after create_direct_purchase_tables.sql (or on an existing database).
--
-- phpMyAdmin / MySQL: paste ONLY the lines from "ALTER TABLE" downward.
-- Do NOT paste Git or Cursor diff lines (e.g. lines starting with "@" or
-- containing "@@") — they are not SQL and will cause error #1064.
-- =============================================================================

ALTER TABLE `vp_direct_purchases`
  ADD COLUMN `warehouse_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `vendor_id`;

CREATE TABLE IF NOT EXISTS `vp_direct_purchase_returns` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `direct_purchase_id` INT UNSIGNED NOT NULL,
  `warehouse_id` INT UNSIGNED NOT NULL,
  `return_date` DATE NOT NULL,
  `remarks` VARCHAR(500) DEFAULT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `igst_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `sgst_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `cgst_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `round_off` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vp_dpr_purchase` (`direct_purchase_id`),
  KEY `idx_vp_dpr_return_date` (`return_date`),
  CONSTRAINT `fk_vp_dpr_purchase`
    FOREIGN KEY (`direct_purchase_id`) REFERENCES `vp_direct_purchases` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vp_direct_purchase_return_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `direct_purchase_return_id` INT UNSIGNED NOT NULL,
  `direct_purchase_item_id` INT UNSIGNED NOT NULL,
  `return_qty` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
  `gst_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `line_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_vp_dpri_return` (`direct_purchase_return_id`),
  KEY `idx_vp_dpri_dp_item` (`direct_purchase_item_id`),
  CONSTRAINT `fk_vp_dpri_return`
    FOREIGN KEY (`direct_purchase_return_id`) REFERENCES `vp_direct_purchase_returns` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_vp_dpri_dp_item`
    FOREIGN KEY (`direct_purchase_item_id`) REFERENCES `vp_direct_purchase_items` (`id`)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
