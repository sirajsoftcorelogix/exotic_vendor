-- Rename stock_replenishment_days -> stock_replenishment_months (run if days column already exists)
ALTER TABLE vp_products
    CHANGE COLUMN stock_replenishment_days stock_replenishment_months INT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE vp_publishers
    CHANGE COLUMN stock_replenishment_days stock_replenishment_months INT UNSIGNED NOT NULL DEFAULT 0;
