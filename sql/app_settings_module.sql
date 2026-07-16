-- =============================================================================
-- App Settings Module — single install / migration script
-- =============================================================================
-- Tables:
--   app_settings        — id, setting_key, setting_value, updated_by, updated_at
--   settings_audit_log  — id, setting_key, old_value, new_value, changed_by, changed_at
--
-- Field metadata (label, input type, sort) lives in config/app_settings_registry.php
--
-- Safe to re-run. Verify after run:
--   SELECT setting_key, setting_value FROM app_settings ORDER BY setting_key;
-- =============================================================================


-- -----------------------------------------------------------------------------
-- 1. Create tables
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS app_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_app_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings_audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_setting_key (setting_key),
    KEY idx_audit_changed_at (changed_at),
    KEY idx_audit_changed_by (changed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 2. Migrate expanded app_settings to slim schema (if old wide table exists)
-- -----------------------------------------------------------------------------

SET @has_fat_app_settings := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'app_settings'
      AND COLUMN_NAME = 'group_key'
);

SET @sql := IF(
    @has_fat_app_settings > 0,
    'CREATE TABLE IF NOT EXISTS app_settings_slim (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL,
        setting_value TEXT NULL,
        updated_by INT NULL,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_app_settings_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    'SELECT ''app_settings already slim — skip slim table create'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_fat_app_settings > 0,
    'INSERT INTO app_settings_slim (id, setting_key, setting_value, updated_by, updated_at)
     SELECT id, setting_key, setting_value, updated_by, updated_at
     FROM app_settings
     ON DUPLICATE KEY UPDATE
        setting_value = VALUES(setting_value),
        updated_by = VALUES(updated_by),
        updated_at = VALUES(updated_at)',
    'SELECT ''app_settings already slim — skip data copy'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_fat_app_settings > 0,
    'RENAME TABLE app_settings TO app_settings_legacy_backup, app_settings_slim TO app_settings',
    'SELECT ''app_settings already slim — skip rename'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- -----------------------------------------------------------------------------
-- 3. Seed default setting(s)
-- -----------------------------------------------------------------------------

UPDATE app_settings
SET setting_key = 'stock_replenishment_months',
    setting_value = IF(setting_value = '30', '1', setting_value)
WHERE setting_key = 'stock_replenishment_lookback_days';

DELETE FROM app_settings
WHERE setting_key NOT IN (
    'stock_replenishment_months',
    'stock_replenishment_book_price_cutoff',
    'invoice_prefix',
    'invoice_series',
    'terms_and_conditions',
    'high_value_transaction_limit'
);

INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
('stock_replenishment_months', '1'),
('stock_replenishment_book_price_cutoff', '0'),
('invoice_prefix', 'inv/2025-26/'),
('invoice_series', '10017'),
('terms_and_conditions', ''),
('high_value_transaction_limit', '200000.00');


-- -----------------------------------------------------------------------------
-- 4. Drop legacy / unused tables
--    Uncomment RENAME block below for backup instead of drop.
-- -----------------------------------------------------------------------------

/*
RENAME TABLE global_settings TO global_settings_backup;
RENAME TABLE firm_details TO firm_details_backup;
*/

DROP TABLE IF EXISTS global_settings;
DROP TABLE IF EXISTS firm_details;
DROP TABLE IF EXISTS app_settings_legacy_backup;
DROP TABLE IF EXISTS app_setting_groups;
