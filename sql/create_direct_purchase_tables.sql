-- Direct purchase (vendor invoice capture without PO)
-- Run this migration on your MySQL database before using the module.

CREATE TABLE IF NOT EXISTS `vp_direct_purchases` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_id` INT UNSIGNED NOT NULL,
  `warehouse_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `invoice_number` VARCHAR(100) NOT NULL,
  `invoice_date` DATE NOT NULL,
  `invoice_file` VARCHAR(500) DEFAULT NULL,
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
  KEY `idx_vp_dp_vendor` (`vendor_id`),
  KEY `idx_vp_dp_invoice_date` (`invoice_date`),
  KEY `idx_vp_dp_invoice_number` (`invoice_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vp_direct_purchase_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `direct_purchase_id` INT UNSIGNED NOT NULL,
  `item_code` VARCHAR(100) DEFAULT NULL,
  `sku` VARCHAR(100) DEFAULT NULL,
  `color` VARCHAR(100) DEFAULT NULL,
  `size` VARCHAR(100) DEFAULT NULL,
  `cost_per_item` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `qty` DECIMAL(15,3) NOT NULL DEFAULT 1.000,
  `hsn` VARCHAR(50) DEFAULT NULL,
  `gst_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `unit` VARCHAR(50) DEFAULT NULL,
  `gst_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `line_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_vp_dpi_purchase` (`direct_purchase_id`),
  CONSTRAINT `fk_vp_dpi_purchase`
    FOREIGN KEY (`direct_purchase_id`) REFERENCES `vp_direct_purchases` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: sidebar menu (adjust parent_id / user_id / sort_order as needed).
-- Assign permissions to roles via Admin → Roles if items do not appear.
/*
INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
VALUES (0, 'Direct Purchase', 'direct_purchase', 'list', '<i class="fas fa-file-invoice-dollar mr-2"></i>', 1, 1, 200);

SET @dp_parent := LAST_INSERT_ID();

INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
VALUES
(@dp_parent, 'List & manage purchases', 'direct_purchase', 'list', '<i class="fas fa-list mr-2"></i>', 1, 1, 1),
(@dp_parent, 'Add purchase', 'direct_purchase', 'add', '<i class="fas fa-plus mr-2"></i>', 1, 1, 2);
*/
