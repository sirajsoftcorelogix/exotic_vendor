-- Vendor contact parity with publisher master (website, primary flags, persisted webpage)

ALTER TABLE vp_vendors
    ADD COLUMN website VARCHAR(500) NULL DEFAULT NULL AFTER vendor_name,
    ADD COLUMN vendor_email_is_primary TINYINT(1) NOT NULL DEFAULT 0 AFTER vendor_email,
    ADD COLUMN vendor_phone_is_whatsapp TINYINT(1) NOT NULL DEFAULT 0 AFTER vendor_phone,
    ADD COLUMN webpage TINYINT(1) NOT NULL DEFAULT 1 AFTER postal_code;

CREATE TABLE IF NOT EXISTS vendor_phones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT UNSIGNED NOT NULL,
    phone VARCHAR(20) NOT NULL,
    is_whatsapp TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_vendor_phones_vendor_id (vendor_id),
    UNIQUE KEY uq_vendor_phone (vendor_id, phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS vendor_emails (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_vendor_emails_vendor_id (vendor_id),
    UNIQUE KEY uq_vendor_email (vendor_id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Legacy single alt_phone → vendor_phones
INSERT INTO vendor_phones (vendor_id, phone, is_whatsapp, sort_order)
SELECT v.id, TRIM(v.alt_phone), 0, 0
FROM vp_vendors v
WHERE TRIM(COALESCE(v.alt_phone, '')) <> ''
  AND NOT EXISTS (
      SELECT 1 FROM vendor_phones vp
      WHERE vp.vendor_id = v.id
        AND BINARY vp.phone = BINARY TRIM(v.alt_phone)
  );
