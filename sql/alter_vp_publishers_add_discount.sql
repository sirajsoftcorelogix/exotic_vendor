-- Add vendor/publisher discount percentage for book replenishment pricing
ALTER TABLE vp_publishers
    ADD COLUMN discount DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER stock_replenishment_months;
