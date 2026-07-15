-- Stock replenishment months per product (books use this on product detail Inventory)
ALTER TABLE vp_products
    ADD COLUMN stock_replenishment_months INT UNSIGNED NOT NULL DEFAULT 0 AFTER instock_leadtime;
