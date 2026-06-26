-- Warehouse on-hand stock (movement ledger). local_stock remains online/website stock from API.
ALTER TABLE vp_products
    ADD COLUMN physical_stock INT NOT NULL DEFAULT 0 AFTER local_stock;

-- One-time backfill: sum of latest running_stock per warehouse per product.
UPDATE vp_products p
LEFT JOIN (
    SELECT sm.product_id, SUM(sm.running_stock) AS total_stock
    FROM vp_stock_movements sm
    INNER JOIN (
        SELECT warehouse_id, product_id, MAX(id) AS max_id
        FROM vp_stock_movements
        GROUP BY warehouse_id, product_id
    ) latest ON sm.warehouse_id = latest.warehouse_id
        AND sm.product_id = latest.product_id
        AND sm.id = latest.max_id
    GROUP BY sm.product_id
) m ON m.product_id = p.id
SET p.physical_stock = COALESCE(m.total_stock, 0);
