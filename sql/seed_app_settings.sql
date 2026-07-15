-- Seed developer-defined app settings.
-- Safe to re-run: inserts missing keys only; does not overwrite existing values.
-- Run after sql/create_app_settings_tables.sql

INSERT INTO app_settings (setting_key, setting_value, value_type, group_key, label, description, input_type, sort_order, is_editable)
SELECT 'invoice_prefix', gs.invoice_prefix, 'string', 'invoice', 'Invoice prefix', 'Prefix prepended to auto-generated invoice numbers.', 'text', 10, 1
FROM global_settings gs WHERE gs.id = 1
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    group_key = VALUES(group_key),
    value_type = VALUES(value_type),
    input_type = VALUES(input_type),
    sort_order = VALUES(sort_order);

INSERT INTO app_settings (setting_key, setting_value, value_type, group_key, label, description, input_type, sort_order, is_editable)
SELECT 'invoice_series', CAST(gs.invoice_series AS CHAR), 'int', 'invoice', 'Invoice series', 'Next running number used for invoice generation. Updated automatically when invoices are created.', 'number', 20, 1
FROM global_settings gs WHERE gs.id = 1
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    group_key = VALUES(group_key),
    value_type = VALUES(value_type),
    input_type = VALUES(input_type),
    sort_order = VALUES(sort_order);

INSERT INTO app_settings (setting_key, setting_value, value_type, group_key, label, description, input_type, sort_order, is_editable)
SELECT 'terms_and_conditions', gs.terms_and_conditions, 'text', 'invoice', 'Terms and conditions', 'Default terms printed on invoices.', 'textarea', 30, 1
FROM global_settings gs WHERE gs.id = 1
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    group_key = VALUES(group_key),
    value_type = VALUES(value_type),
    input_type = VALUES(input_type),
    sort_order = VALUES(sort_order);

INSERT INTO app_settings (setting_key, setting_value, value_type, group_key, label, description, input_type, sort_order, is_editable)
SELECT 'high_value_transaction_limit', CAST(COALESCE(gs.high_value_transaction_limit, 200000.00) AS CHAR), 'decimal', 'compliance', 'High value transaction limit', 'Threshold for Section 269ST / Rule 114B POS compliance prompts. Default: ₹2,00,000.', 'number', 10, 1
FROM global_settings gs WHERE gs.id = 1
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    group_key = VALUES(group_key),
    value_type = VALUES(value_type),
    input_type = VALUES(input_type),
    sort_order = VALUES(sort_order);

INSERT INTO app_settings (setting_key, setting_value, value_type, group_key, label, description, input_type, sort_order, is_editable)
SELECT 'firm_name', fd.firm_name, 'string', 'company', 'Firm name', 'Legal name shown on invoices and shipping documents.', 'text', 10, 1
FROM firm_details fd WHERE fd.id = 1
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    group_key = VALUES(group_key),
    value_type = VALUES(value_type),
    input_type = VALUES(input_type),
    sort_order = VALUES(sort_order);

INSERT INTO app_settings (setting_key, setting_value, value_type, group_key, label, description, input_type, sort_order, is_editable)
SELECT 'firm_pan', fd.pan, 'string', 'company', 'PAN', 'Permanent Account Number.', 'text', 20, 1
FROM firm_details fd WHERE fd.id = 1
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    group_key = VALUES(group_key),
    value_type = VALUES(value_type),
    input_type = VALUES(input_type),
    sort_order = VALUES(sort_order);

INSERT INTO app_settings (setting_key, setting_value, value_type, group_key, label, description, input_type, sort_order, is_editable)
SELECT 'firm_gst', fd.gst, 'string', 'company', 'GSTIN', 'GST identification number.', 'text', 30, 1
FROM firm_details fd WHERE fd.id = 1
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    group_key = VALUES(group_key),
    value_type = VALUES(value_type),
    input_type = VALUES(input_type),
    sort_order = VALUES(sort_order);

INSERT INTO app_settings (setting_key, setting_value, value_type, group_key, label, description, input_type, sort_order, is_editable)
SELECT 'firm_address', fd.address, 'text', 'company', 'Address', 'Registered business address.', 'textarea', 40, 1
FROM firm_details fd WHERE fd.id = 1
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    group_key = VALUES(group_key),
    value_type = VALUES(value_type),
    input_type = VALUES(input_type),
    sort_order = VALUES(sort_order);

INSERT INTO app_settings (setting_key, setting_value, value_type, group_key, label, description, input_type, sort_order, is_editable)
SELECT 'firm_phone', fd.phone, 'string', 'company', 'Phone', 'Primary contact phone number.', 'text', 50, 1
FROM firm_details fd WHERE fd.id = 1
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    group_key = VALUES(group_key),
    value_type = VALUES(value_type),
    input_type = VALUES(input_type),
    sort_order = VALUES(sort_order);

INSERT INTO app_settings (setting_key, setting_value, value_type, group_key, label, description, input_type, sort_order, is_editable)
SELECT 'firm_city', fd.city, 'string', 'company', 'City', NULL, 'text', 60, 1
FROM firm_details fd WHERE fd.id = 1
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    group_key = VALUES(group_key),
    value_type = VALUES(value_type),
    input_type = VALUES(input_type),
    sort_order = VALUES(sort_order);

INSERT INTO app_settings (setting_key, setting_value, value_type, group_key, label, description, input_type, sort_order, is_editable)
SELECT 'firm_state', fd.state, 'string', 'company', 'State', NULL, 'text', 70, 1
FROM firm_details fd WHERE fd.id = 1
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    group_key = VALUES(group_key),
    value_type = VALUES(value_type),
    input_type = VALUES(input_type),
    sort_order = VALUES(sort_order);

INSERT INTO app_settings (setting_key, setting_value, value_type, group_key, label, description, input_type, sort_order, is_editable)
SELECT 'firm_country', fd.country, 'string', 'company', 'Country', NULL, 'text', 80, 1
FROM firm_details fd WHERE fd.id = 1
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    group_key = VALUES(group_key),
    value_type = VALUES(value_type),
    input_type = VALUES(input_type),
    sort_order = VALUES(sort_order);

INSERT INTO app_settings (setting_key, setting_value, value_type, group_key, label, description, input_type, sort_order, is_editable)
SELECT 'firm_pin', fd.pin, 'string', 'company', 'PIN code', 'Postal / ZIP code used as dispatch fallback.', 'text', 90, 1
FROM firm_details fd WHERE fd.id = 1
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    group_key = VALUES(group_key),
    value_type = VALUES(value_type),
    input_type = VALUES(input_type),
    sort_order = VALUES(sort_order);

INSERT INTO app_settings (setting_key, setting_value, value_type, group_key, label, description, input_type, sort_order, is_editable)
SELECT 'firm_state_code', CAST(fd.state_code AS CHAR), 'int', 'company', 'State code', 'Numeric state code for tax / e-invoice integrations.', 'number', 100, 1
FROM firm_details fd WHERE fd.id = 1
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    group_key = VALUES(group_key),
    value_type = VALUES(value_type),
    input_type = VALUES(input_type),
    sort_order = VALUES(sort_order);

INSERT INTO app_settings (setting_key, setting_value, value_type, group_key, label, description, input_type, sort_order, is_editable)
SELECT 'firm_email', fd.email, 'string', 'company', 'Email', 'Company email for documents and notifications.', 'text', 110, 1
FROM firm_details fd WHERE fd.id = 1
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    group_key = VALUES(group_key),
    value_type = VALUES(value_type),
    input_type = VALUES(input_type),
    sort_order = VALUES(sort_order);

-- Optional sidebar menu (uncomment and adjust sort_order / permissions as needed).
/*
INSERT INTO modules (parent_id, module_name, slug, action, font_awesome_icon, active, user_id, sort_order)
VALUES (0, 'Settings', 'globals', 'settings', '<i class="fas fa-cog mr-2"></i>', 1, 1, 900);

SET @settings_parent := LAST_INSERT_ID();

INSERT INTO modules (parent_id, module_name, slug, action, font_awesome_icon, active, user_id, sort_order)
VALUES (@settings_parent, 'Global settings', 'globals', 'settings', '<i class="fas fa-sliders-h mr-2"></i>', 1, 1, 1);
*/
