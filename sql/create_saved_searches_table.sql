-- Create table for storing saved searches per user
CREATE TABLE IF NOT EXISTS `saved_searches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `page` VARCHAR(100) NOT NULL DEFAULT 'orders',
  `name` VARCHAR(255) NOT NULL,
  `query` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_page` (`user_id`, `page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;