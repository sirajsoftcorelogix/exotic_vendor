CREATE TABLE IF NOT EXISTS courier_partner_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id INT UNSIGNED NOT NULL,
    account_code VARCHAR(80) NOT NULL,
    account_name VARCHAR(140) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    priority INT NOT NULL DEFAULT 100,
    tags_json TEXT NULL,
    notes TEXT NULL,
    credentials_json TEXT NULL COMMENT 'Partner credentials as one JSON object',
    environment VARCHAR(20) NOT NULL DEFAULT 'sandbox' COMMENT 'Active environment: sandbox or production',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_partner_account_code (partner_id, account_code),
    INDEX idx_partner (partner_id),
    INDEX idx_active (is_active),
    CONSTRAINT fk_courier_partner_accounts_partner
        FOREIGN KEY (partner_id) REFERENCES courier_partners(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS courier_partner_account_credentials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id INT UNSIGNED NOT NULL,
    cred_key VARCHAR(120) NOT NULL,
    cred_value TEXT NULL,
    is_secret TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_account_cred_key (account_id, cred_key),
    INDEX idx_account (account_id),
    CONSTRAINT fk_courier_account_creds_account
        FOREIGN KEY (account_id) REFERENCES courier_partner_accounts(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;