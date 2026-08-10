-- Add mapped_countries column to currency_master table
-- Stores comma-separated ISO country codes or names mapped to a currency (e.g. EUR -> DE, FR, IT, ES, NL, BE...)

ALTER TABLE `currency_master`
    ADD COLUMN `mapped_countries` TEXT NULL DEFAULT NULL AFTER `display_symbol`;
