-- Add stock replenishment months to vp_vendors (mirrors vp_publishers)
ALTER TABLE vp_vendors
    ADD COLUMN stock_replenishment_months INT UNSIGNED NOT NULL DEFAULT 0 AFTER groupname;
