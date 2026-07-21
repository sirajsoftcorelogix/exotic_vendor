INSERT INTO publisher_phones (publisher_id, phone, is_whatsapp, sort_order)
SELECT p.id, TRIM(p.alt_phone), 0, 0
FROM vp_publishers p
WHERE TRIM(COALESCE(p.alt_phone, '')) <> ''
  AND NOT EXISTS (
      SELECT 1 FROM publisher_phones pp
      WHERE pp.publisher_id = p.id
        AND BINARY pp.phone = BINARY TRIM(p.alt_phone)
  );
