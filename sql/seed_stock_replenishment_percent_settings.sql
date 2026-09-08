-- Add min-stock and PO-trigger percentage settings for stock replenishment (safe to re-run)
INSERT IGNORE INTO app_settings (setting_key, setting_value)
VALUES
    ('stock_replenishment_min_stock_percent', '50'),
    ('stock_replenishment_purchase_threshold_percent', '25');
