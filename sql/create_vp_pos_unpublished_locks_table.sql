-- Create table for multi-store lease locks on unpublished virtual products during POS checkout
CREATE TABLE IF NOT EXISTS `vp_pos_unpublished_locks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `user_id` INT DEFAULT 0,
    `store_id` INT DEFAULT 0,
    `was_unpublished` TINYINT(1) DEFAULT 1,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_product_expires` (`product_id`, `expires_at`),
    INDEX `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
