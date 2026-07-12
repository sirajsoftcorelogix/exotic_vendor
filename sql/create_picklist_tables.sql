-- Picklist module: warehouse picking workflow
-- Run this migration on your MySQL database before using the module.

CREATE TABLE IF NOT EXISTS `vp_picklists` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `picklist_number` VARCHAR(50) NOT NULL,
  `picker_id` INT UNSIGNED DEFAULT NULL,
  `warehouse_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vp_picklist_number` (`picklist_number`),
  KEY `idx_vp_picklist_status` (`status`),
  KEY `idx_vp_picklist_picker` (`picker_id`),
  KEY `idx_vp_picklist_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vp_picklist_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `picklist_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `order_number` VARCHAR(100) DEFAULT NULL,
  `item_code` VARCHAR(100) DEFAULT NULL,
  `sku` VARCHAR(100) DEFAULT NULL,
  `size` VARCHAR(100) DEFAULT NULL,
  `color` VARCHAR(100) DEFAULT NULL,
  `title` VARCHAR(500) DEFAULT NULL,
  `image` VARCHAR(500) DEFAULT NULL,
  `warehouse_location` VARCHAR(255) DEFAULT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `status` ENUM('pending', 'picked') NOT NULL DEFAULT 'pending',
  `picked_by` INT UNSIGNED DEFAULT NULL,
  `picked_at` DATETIME DEFAULT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vp_pli_picklist` (`picklist_id`),
  KEY `idx_vp_pli_order` (`order_id`),
  KEY `idx_vp_pli_status` (`status`),
  KEY `idx_vp_pli_location` (`warehouse_location`),
  CONSTRAINT `fk_vp_pli_picklist`
    FOREIGN KEY (`picklist_id`) REFERENCES `vp_picklists` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: sidebar menu (adjust parent_id / user_id / sort_order as needed).
-- Assign permissions to roles via Admin → Roles if items do not appear.
/*
INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
VALUES (0, 'Picklist', 'picklist', 'list', '<i class="fas fa-clipboard-list mr-2"></i>', 1, 1, 205);

SET @pl_parent := LAST_INSERT_ID();

INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
VALUES
(@pl_parent, 'Picklists', 'picklist', 'list', '<i class="fas fa-list mr-2"></i>', 1, 1, 1);
*/
