-- One-time reconciliation: align physical_stock, vp_stock.current_stock, and movement ledger.
-- Run during maintenance window; review counts before/after on a staging copy first.

-- 1) vp_products.physical_stock = sum of latest running_stock per warehouse
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
SET p.physical_stock = GREATEST(0, COALESCE(m.total_stock, 0));

-- 2) vp_stock.current_stock = latest movement running_stock per sku + warehouse
UPDATE vp_stock vs
LEFT JOIN (
    SELECT sm.sku, sm.warehouse_id, sm.running_stock
    FROM vp_stock_movements sm
    INNER JOIN (
        SELECT sku, warehouse_id, MAX(id) AS max_id
        FROM vp_stock_movements
        WHERE sku IS NOT NULL AND TRIM(sku) <> '' AND warehouse_id > 0
        GROUP BY sku, warehouse_id
    ) latest ON sm.sku = latest.sku
        AND sm.warehouse_id = latest.warehouse_id
        AND sm.id = latest.max_id
) m ON m.sku = vs.sku AND m.warehouse_id = vs.warehouse_id
SET vs.current_stock = GREATEST(0, COALESCE(m.running_stock, 0));

-- 3) Insert missing vp_stock rows for sku+warehouse pairs that have movements but no vp_stock row
INSERT INTO vp_stock (sku, warehouse_id, current_stock, last_trans_id)
SELECT m.sku, m.warehouse_id, GREATEST(0, m.running_stock), 0
FROM (
    SELECT sm.sku, sm.warehouse_id, sm.running_stock
    FROM vp_stock_movements sm
    INNER JOIN (
        SELECT sku, warehouse_id, MAX(id) AS max_id
        FROM vp_stock_movements
        WHERE sku IS NOT NULL AND TRIM(sku) <> '' AND warehouse_id > 0
        GROUP BY sku, warehouse_id
    ) latest ON sm.sku = latest.sku
        AND sm.warehouse_id = latest.warehouse_id
        AND sm.id = latest.max_id
) m
LEFT JOIN vp_stock vs ON vs.sku = m.sku AND vs.warehouse_id = m.warehouse_id
WHERE vs.id IS NULL;
