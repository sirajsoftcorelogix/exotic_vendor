-- Add stock replenishment months to vp_publishers (if contact/address migration was already applied)
ALTER TABLE vp_publishers
    ADD COLUMN stock_replenishment_months INT UNSIGNED NOT NULL DEFAULT 0 AFTER webpage;
