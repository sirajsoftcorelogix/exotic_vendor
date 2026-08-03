-- Optional display symbol override per currency (used when PHP Intl is unavailable or admin customizes).
-- When NULL, the app resolves symbols via PHP Intl NumberFormatter (CLDR).

ALTER TABLE `currency_master`
    ADD COLUMN `display_symbol` VARCHAR(16) NULL DEFAULT NULL AFTER `currency_unit`;

-- Ensure INR exists for domestic POS / orders (symbol can be edited in Currency admin later).
INSERT INTO `currency_master`
    (`currency_code`, `currency_name`, `currency_unit`, `display_symbol`, `rate_import`, `rate_export`, `is_active`)
SELECT 'INR', 'Indian Rupee', '1 INR', '₹', 1.000000, 1.000000, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `currency_master` WHERE `currency_code` = 'INR'
);

UPDATE `currency_master` SET `display_symbol` = '₹' WHERE `currency_code` = 'INR' AND (`display_symbol` IS NULL OR `display_symbol` = '');
