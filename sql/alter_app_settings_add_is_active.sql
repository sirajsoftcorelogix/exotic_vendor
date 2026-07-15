-- Add activate/deactivate support for developer-defined app settings.
-- MySQL-compatible (no ADD COLUMN IF NOT EXISTS). Safe to re-run.
-- Run on databases that already have app_settings without is_active.

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'app_settings'
      AND COLUMN_NAME = 'is_active'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE app_settings ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER is_editable',
    'SELECT ''Column is_active already exists on app_settings'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'app_settings'
      AND INDEX_NAME = 'idx_app_settings_active'
);

SET @sql := IF(
    @index_exists = 0,
    'ALTER TABLE app_settings ADD KEY idx_app_settings_active (is_active)',
    'SELECT ''Index idx_app_settings_active already exists on app_settings'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE app_settings SET is_active = 1 WHERE is_active IS NULL;
