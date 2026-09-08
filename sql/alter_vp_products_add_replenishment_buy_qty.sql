-- Persist computed Replenishment Buy Qty on each product (books; updated on detail view / import / months change)
ALTER TABLE vp_products
    ADD COLUMN replenishment_buy_qty INT UNSIGNED NOT NULL DEFAULT 0 AFTER stock_replenishment_months;
