-- Warehouse on-hand stock (movement ledger). local_stock remains online/website stock from API.
ALTER TABLE vp_products
    ADD COLUMN physical_stock INT NOT NULL DEFAULT 0 AFTER local_stock;

-- One-time backfill: latest movement balance per product, else existing local_stock.
UPDATE vp_products p
LEFT JOIN (
    SELECT sm.product_id, sm.running_stock
    FROM vp_stock_movements sm
    INNER JOIN (
        SELECT product_id, MAX(id) AS max_id
        FROM vp_stock_movements
        GROUP BY product_id
    ) latest ON latest.max_id = sm.id
) m ON m.product_id = p.id
SET p.physical_stock = COALESCE(m.running_stock, p.local_stock, 0);
