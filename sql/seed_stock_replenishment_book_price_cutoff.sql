-- Add book price cutoff setting for stock replenishment (safe to re-run)
INSERT IGNORE INTO app_settings (setting_key, setting_value)
VALUES ('stock_replenishment_book_price_cutoff', '0');
