-- Restore invoice numbering settings after app_settings slim migration.
-- Safe to re-run (INSERT IGNORE + conditional restore from backup).

-- Prefer legacy global_settings if it still exists
INSERT IGNORE INTO app_settings (setting_key, setting_value)
SELECT 'invoice_prefix', invoice_prefix
FROM global_settings
WHERE id = 1
  AND invoice_prefix IS NOT NULL
  AND invoice_prefix != '';

INSERT IGNORE INTO app_settings (setting_key, setting_value)
SELECT 'invoice_series', CAST(invoice_series AS CHAR)
FROM global_settings
WHERE id = 1
  AND invoice_series IS NOT NULL;

INSERT IGNORE INTO app_settings (setting_key, setting_value)
SELECT 'terms_and_conditions', terms_and_conditions
FROM global_settings
WHERE id = 1
  AND terms_and_conditions IS NOT NULL
  AND terms_and_conditions != '';

-- Fallback: global_settings_backup (table renamed during migration)
INSERT IGNORE INTO app_settings (setting_key, setting_value)
SELECT 'invoice_prefix', invoice_prefix
FROM global_settings_backup
WHERE id = 1
  AND invoice_prefix IS NOT NULL
  AND invoice_prefix != '';

INSERT IGNORE INTO app_settings (setting_key, setting_value)
SELECT 'invoice_series', CAST(invoice_series AS CHAR)
FROM global_settings_backup
WHERE id = 1
  AND invoice_series IS NOT NULL;

INSERT IGNORE INTO app_settings (setting_key, setting_value)
SELECT 'terms_and_conditions', terms_and_conditions
FROM global_settings_backup
WHERE id = 1
  AND terms_and_conditions IS NOT NULL
  AND terms_and_conditions != '';

-- Defaults when no legacy row exists
INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
('invoice_prefix', 'inv/2025-26/'),
('invoice_series', '10017'),
('terms_and_conditions', ''),
('high_value_transaction_limit', '200000.00');

-- If series is still zero, seed from the highest existing invoice number for the current prefix
SET @prefix := COALESCE(
    (SELECT setting_value FROM app_settings WHERE setting_key = 'invoice_prefix' LIMIT 1),
    'inv/2025-26/'
);

SET @current_series := COALESCE(
    (SELECT CAST(setting_value AS UNSIGNED) FROM app_settings WHERE setting_key = 'invoice_series' LIMIT 1),
    0
);

SET @max_from_invoices := (
    SELECT COALESCE(MAX(
        CASE
            WHEN @prefix != '' AND invoice_number LIKE CONCAT(@prefix, '%')
                THEN CAST(SUBSTRING(invoice_number, CHAR_LENGTH(@prefix) + 1) AS UNSIGNED)
            WHEN invoice_number REGEXP '^INV-[0-9]+$'
                THEN CAST(SUBSTRING(invoice_number, 5) AS UNSIGNED)
            ELSE 0
        END
    ), 0)
    FROM vp_invoices
);

UPDATE app_settings
SET setting_value = CAST(GREATEST(@current_series, @max_from_invoices) AS CHAR)
WHERE setting_key = 'invoice_series'
  AND @max_from_invoices > @current_series;
