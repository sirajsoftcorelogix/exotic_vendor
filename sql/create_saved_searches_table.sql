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

CREATE TABLE vp_address_info (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED DEFAULT NULL,

    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    company VARCHAR(150) DEFAULT NULL,

    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) DEFAULT NULL,
    state_iso VARCHAR(10) DEFAULT NULL,
    state_code VARCHAR(10) DEFAULT NULL,
    country CHAR(2) NOT NULL,
    zipcode VARCHAR(20) NOT NULL,

    mobile VARCHAR(20) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    gstin VARCHAR(20) DEFAULT NULL,

    shipping_first_name VARCHAR(100) DEFAULT NULL,
    shipping_last_name VARCHAR(100) DEFAULT NULL,
    shipping_company VARCHAR(150) DEFAULT NULL,
    shipping_address_line1 VARCHAR(255) DEFAULT NULL,
    shipping_address_line2 VARCHAR(255) DEFAULT NULL,
    shipping_city VARCHAR(100) DEFAULT NULL,
    shipping_state VARCHAR(100) DEFAULT NULL,
    shipping_state_iso VARCHAR(10) DEFAULT NULL,
    shipping_state_code VARCHAR(10) DEFAULT NULL,
    shipping_country CHAR(2) DEFAULT NULL,
    shipping_zipcode VARCHAR(20) DEFAULT NULL,
    shipping_mobile VARCHAR(20) DEFAULT NULL,
    shipping_email VARCHAR(150) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
  

CREATE TABLE IF NOT EXISTS `vp_customers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_email_phone` (`email`, `phone`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
