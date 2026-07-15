-- Add stock replenishment days to vp_publishers (if contact/address migration was already applied)
ALTER TABLE vp_publishers
    ADD COLUMN stock_replenishment_days INT UNSIGNED NOT NULL DEFAULT 0 AFTER webpage;
