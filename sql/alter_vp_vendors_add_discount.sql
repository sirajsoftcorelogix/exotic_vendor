-- Add vendor discount percentage (book group vendors)
ALTER TABLE vp_vendors
    ADD COLUMN discount DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER stock_replenishment_months;
