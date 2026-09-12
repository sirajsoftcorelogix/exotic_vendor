-- Shipping port master: sea, air, inland, and dry ports for shipping.
-- If the old table already exists, rename it first (phpMyAdmin: run this line alone):
-- RENAME TABLE port_master TO shipping_port_master;

CREATE TABLE IF NOT EXISTS shipping_port_master (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    port_name VARCHAR(255) NOT NULL,
    port_code VARCHAR(20) NOT NULL,
    port_type VARCHAR(20) NOT NULL,
    city VARCHAR(120) NOT NULL,
    country_id INT UNSIGNED NOT NULL,
    pincode VARCHAR(20) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    user_id INT UNSIGNED NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_shipping_port_master_code (port_code),
    INDEX idx_shipping_port_master_type (port_type),
    INDEX idx_shipping_port_master_country (country_id),
    INDEX idx_shipping_port_master_city (city),
    INDEX idx_shipping_port_master_active (is_active),
    INDEX idx_shipping_port_master_name (port_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional sidebar menu. Assign permissions via Admin → Roles if the item does not appear.
INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
SELECT
    COALESCE(
        (SELECT parent_id FROM modules WHERE slug IN ('materials', 'account_groups', 'languages', 'sizes') AND parent_id > 0 ORDER BY id ASC LIMIT 1),
        (SELECT id FROM modules WHERE parent_id = 0 AND slug IN ('materials', 'account_groups') LIMIT 1),
        0
    ),
    'Port Master',
    'ports',
    'list',
    '<i class="fas fa-plane-departure mr-2"></i>',
    1,
    1,
    225
WHERE NOT EXISTS (SELECT 1 FROM modules WHERE slug = 'ports' LIMIT 1);
