-- App-level settings: keys/metadata are developer-defined; admins may only change values.
-- Run manually against the vendor portal database.

CREATE TABLE IF NOT EXISTS app_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    value_type ENUM('string', 'int', 'decimal', 'bool', 'text', 'json') NOT NULL DEFAULT 'string',
    group_key VARCHAR(50) NOT NULL,
    label VARCHAR(150) NOT NULL,
    description TEXT NULL,
    input_type ENUM('text', 'textarea', 'number', 'toggle', 'select') NOT NULL DEFAULT 'text',
    options_json JSON NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_editable TINYINT(1) NOT NULL DEFAULT 1,
    updated_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_app_settings_key (setting_key),
    KEY idx_app_settings_group (group_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings_audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_setting_key (setting_key),
    KEY idx_audit_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
