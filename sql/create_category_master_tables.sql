-- SQL script for Category Master module
-- Tables: category, category_sync_logs
-- Modules menu: Masters -> Categories

CREATE TABLE IF NOT EXISTS `category` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `parent_id` INT NOT NULL DEFAULT 0,
  `name` VARCHAR(100) NULL,
  `display_name` VARCHAR(100) NULL,
  `category` INT NULL,
  `parent` VARCHAR(255) NULL,
  `initial` VARCHAR(5) NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  UNIQUE KEY `uk_category_api` (`category`),
  INDEX `idx_category_name` (`name`),
  INDEX `idx_category_display_name` (`display_name`),
  INDEX `idx_category_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `category_sync_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `categories_received` INT NOT NULL DEFAULT 0,
  `categories_added` INT NOT NULL DEFAULT 0,
  `categories_existing` INT NOT NULL DEFAULT 0,
  `categories_failed` INT NOT NULL DEFAULT 0,
  `execution_time` FLOAT NOT NULL DEFAULT 0.0,
  `ip_address` VARCHAR(45) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'success',
  `message` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sync_user` (`user_id`),
  INDEX `idx_sync_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Menu setup under Masters -> Categories
INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
SELECT 0, 'Masters', 'masters', 'list', '<i class="fas fa-layer-group mr-2"></i>', 1, 1, 200
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `modules` WHERE `parent_id` = 0 AND (`module_name` = 'Masters' OR `slug` = 'masters')
);

INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
SELECT id, 'Categories', 'category', 'list', '<i class="fas fa-sitemap mr-2"></i>', 1, 1, 10
FROM `modules`
WHERE `parent_id` = 0 AND (`module_name` = 'Masters' OR `slug` = 'masters')
AND NOT EXISTS (
    SELECT 1 FROM `modules` WHERE `slug` = 'category' AND `action` = 'list'
)
LIMIT 1;
