-- SQL migration for Bulk Background Invoice Jobs
CREATE TABLE IF NOT EXISTS `vp_invoice_bulk_batches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `batch_no` VARCHAR(64) NOT NULL UNIQUE,
  `total_customers` INT NOT NULL DEFAULT 0,
  `total_orders` INT NOT NULL DEFAULT 0,
  `total_items` INT NOT NULL DEFAULT 0,
  `processed_customers` INT NOT NULL DEFAULT 0,
  `success_count` INT NOT NULL DEFAULT 0,
  `error_count` INT NOT NULL DEFAULT 0,
  `status` ENUM('pending', 'processing', 'completed', 'partially_completed', 'failed') DEFAULT 'pending',
  `created_by` INT NOT NULL DEFAULT 0,
  `started_at` DATETIME NULL,
  `completed_at` DATETIME NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`status`),
  INDEX (`batch_no`),
  INDEX (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `vp_invoice_bulk_batch_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `batch_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `customer_name` VARCHAR(255) NULL,
  `order_item_ids` TEXT NOT NULL,
  `order_numbers` TEXT NULL,
  `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
  `invoice_id` INT NULL,
  `invoice_number` VARCHAR(100) NULL,
  `invoice_amount` DECIMAL(12,2) DEFAULT 0.00,
  `error_message` TEXT NULL,
  `processed_at` DATETIME NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`batch_id`) REFERENCES `vp_invoice_bulk_batches`(`id`) ON DELETE CASCADE,
  INDEX (`batch_id`, `status`),
  INDEX (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
