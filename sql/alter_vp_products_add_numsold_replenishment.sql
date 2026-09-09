-- Period sales used by daily book replenishment (Exotic API later; local lookback for now).
ALTER TABLE vp_products
    ADD COLUMN numsold_replenishment INT UNSIGNED NOT NULL DEFAULT 0 AFTER replenishment_buy_qty;
