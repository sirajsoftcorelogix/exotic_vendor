CREATE TABLE IF NOT EXISTS courier_partners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_code VARCHAR(50) NOT NULL UNIQUE,
    partner_name VARCHAR(120) NOT NULL,
    supports_domestic TINYINT(1) NOT NULL DEFAULT 1,
    supports_international TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_partner_name (partner_name),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

