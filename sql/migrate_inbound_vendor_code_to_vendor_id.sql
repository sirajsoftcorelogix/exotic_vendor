-- Map vp_inbound.vendor_code from local vp_vendors.id to Exotic vendor_id.
-- Run on staging first, then production.

-- Preview rows that would change
SELECT i.id AS inbound_id,
       i.vendor_code AS current_value,
       v.id AS local_vendor_pk,
       v.vendor_id AS exotic_vendor_id
FROM vp_inbound i
INNER JOIN vp_vendors v ON CAST(i.vendor_code AS UNSIGNED) = v.id
WHERE v.vendor_id IS NOT NULL
  AND TRIM(v.vendor_id) <> ''
  AND NOT EXISTS (
    SELECT 1 FROM vp_vendors vx
    WHERE TRIM(vx.vendor_id) = TRIM(i.vendor_code)
  );

-- Apply migration
UPDATE vp_inbound i
INNER JOIN vp_vendors v ON CAST(i.vendor_code AS UNSIGNED) = v.id
SET i.vendor_code = v.vendor_id
WHERE v.vendor_id IS NOT NULL
  AND TRIM(v.vendor_id) <> ''
  AND NOT EXISTS (
    SELECT 1 FROM vp_vendors vx
    WHERE TRIM(vx.vendor_id) = TRIM(i.vendor_code)
  );
