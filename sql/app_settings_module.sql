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
-- 3. Migrate data from legacy global_settings + firm_details (if they exist)
--    Does not overwrite existing app_settings values (ON DUPLICATE KEY no-op).
-- -----------------------------------------------------------------------------

SET @has_global_settings := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'global_settings'
);

SET @has_firm_details := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'firm_details'
);

SET @sql := IF(
    @has_global_settings > 0,
    'INSERT INTO app_settings (setting_key, setting_value)
     SELECT ''invoice_prefix'', gs.invoice_prefix FROM global_settings gs WHERE gs.id = 1
     ON DUPLICATE KEY UPDATE setting_key = setting_key',
    'SELECT ''global_settings not found — skip invoice_prefix'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_global_settings > 0,
    'INSERT INTO app_settings (setting_key, setting_value)
     SELECT ''invoice_series'', CAST(gs.invoice_series AS CHAR) FROM global_settings gs WHERE gs.id = 1
     ON DUPLICATE KEY UPDATE setting_key = setting_key',
    'SELECT ''global_settings not found — skip invoice_series'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_global_settings > 0,
    'INSERT INTO app_settings (setting_key, setting_value)
     SELECT ''terms_and_conditions'', gs.terms_and_conditions FROM global_settings gs WHERE gs.id = 1
     ON DUPLICATE KEY UPDATE setting_key = setting_key',
    'SELECT ''global_settings not found — skip terms_and_conditions'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_global_settings > 0,
    'INSERT INTO app_settings (setting_key, setting_value)
     SELECT ''high_value_transaction_limit'', CAST(COALESCE(gs.high_value_transaction_limit, 200000.00) AS CHAR)
     FROM global_settings gs WHERE gs.id = 1
     ON DUPLICATE KEY UPDATE setting_key = setting_key',
    'SELECT ''global_settings not found — skip high_value_transaction_limit'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_firm_details > 0,
    'INSERT INTO app_settings (setting_key, setting_value)
     SELECT ''firm_name'', fd.firm_name FROM firm_details fd WHERE fd.id = 1
     ON DUPLICATE KEY UPDATE setting_key = setting_key',
    'SELECT ''firm_details not found — skip firm_name'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_firm_details > 0,
    'INSERT INTO app_settings (setting_key, setting_value)
     SELECT ''firm_pan'', fd.pan FROM firm_details fd WHERE fd.id = 1
     ON DUPLICATE KEY UPDATE setting_key = setting_key',
    'SELECT ''firm_details not found — skip firm_pan'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_firm_details > 0,
    'INSERT INTO app_settings (setting_key, setting_value)
     SELECT ''firm_gst'', fd.gst FROM firm_details fd WHERE fd.id = 1
     ON DUPLICATE KEY UPDATE setting_key = setting_key',
    'SELECT ''firm_details not found — skip firm_gst'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_firm_details > 0,
    'INSERT INTO app_settings (setting_key, setting_value)
     SELECT ''firm_address'', fd.address FROM firm_details fd WHERE fd.id = 1
     ON DUPLICATE KEY UPDATE setting_key = setting_key',
    'SELECT ''firm_details not found — skip firm_address'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_firm_details > 0,
    'INSERT INTO app_settings (setting_key, setting_value)
     SELECT ''firm_phone'', fd.phone FROM firm_details fd WHERE fd.id = 1
     ON DUPLICATE KEY UPDATE setting_key = setting_key',
    'SELECT ''firm_details not found — skip firm_phone'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_firm_details > 0,
    'INSERT INTO app_settings (setting_key, setting_value)
     SELECT ''firm_city'', fd.city FROM firm_details fd WHERE fd.id = 1
     ON DUPLICATE KEY UPDATE setting_key = setting_key',
    'SELECT ''firm_details not found — skip firm_city'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_firm_details > 0,
    'INSERT INTO app_settings (setting_key, setting_value)
     SELECT ''firm_state'', fd.state FROM firm_details fd WHERE fd.id = 1
     ON DUPLICATE KEY UPDATE setting_key = setting_key',
    'SELECT ''firm_details not found — skip firm_state'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_firm_details > 0,
    'INSERT INTO app_settings (setting_key, setting_value)
     SELECT ''firm_country'', fd.country FROM firm_details fd WHERE fd.id = 1
     ON DUPLICATE KEY UPDATE setting_key = setting_key',
    'SELECT ''firm_details not found — skip firm_country'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_firm_details > 0,
    'INSERT INTO app_settings (setting_key, setting_value)
     SELECT ''firm_pin'', fd.pin FROM firm_details fd WHERE fd.id = 1
     ON DUPLICATE KEY UPDATE setting_key = setting_key',
    'SELECT ''firm_details not found — skip firm_pin'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_firm_details > 0,
    'INSERT INTO app_settings (setting_key, setting_value)
     SELECT ''firm_state_code'', CAST(fd.state_code AS CHAR) FROM firm_details fd WHERE fd.id = 1
     ON DUPLICATE KEY UPDATE setting_key = setting_key',
    'SELECT ''firm_details not found — skip firm_state_code'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_firm_details > 0,
    'INSERT INTO app_settings (setting_key, setting_value)
     SELECT ''firm_email'', fd.email FROM firm_details fd WHERE fd.id = 1
     ON DUPLICATE KEY UPDATE setting_key = setting_key',
    'SELECT ''firm_details not found — skip firm_email'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- -----------------------------------------------------------------------------
-- 4. Fallback defaults (when legacy tables are empty or missing)
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
('invoice_prefix', 'INV'),
('invoice_series', '1'),
('terms_and_conditions', ''),
('high_value_transaction_limit', '200000.00');


-- -----------------------------------------------------------------------------
-- 5. Drop legacy tables
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
