-- Stock replenishment days per product (books use this on product detail Inventory)
ALTER TABLE vp_products
    ADD COLUMN stock_replenishment_days INT UNSIGNED NOT NULL DEFAULT 0 AFTER instock_leadtime;
