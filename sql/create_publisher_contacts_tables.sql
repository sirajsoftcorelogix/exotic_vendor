-- Primary contact flags on vp_publishers
ALTER TABLE vp_publishers
    ADD COLUMN publisher_phone_is_whatsapp TINYINT(1) NOT NULL DEFAULT 0 AFTER publisher_phone,
    ADD COLUMN publisher_email_is_billing TINYINT(1) NOT NULL DEFAULT 0 AFTER publisher_email;

-- Alternate phones (WhatsApp flag per number)
CREATE TABLE IF NOT EXISTS publisher_phones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    publisher_id INT UNSIGNED NOT NULL,
    phone VARCHAR(20) NOT NULL,
    is_whatsapp TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_publisher_phones_publisher_id (publisher_id),
    UNIQUE KEY uq_publisher_phone (publisher_id, phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alternate emails (billing flag per address)
CREATE TABLE IF NOT EXISTS publisher_emails (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    publisher_id INT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    is_billing TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_publisher_emails_publisher_id (publisher_id),
    UNIQUE KEY uq_publisher_email (publisher_id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migrate legacy single alternate phone into publisher_phones
INSERT INTO publisher_phones (publisher_id, phone, is_whatsapp, sort_order)
SELECT p.id, TRIM(p.alt_phone), 0, 0
FROM vp_publishers p
WHERE TRIM(COALESCE(p.alt_phone, '')) <> ''
  AND NOT EXISTS (
      SELECT 1 FROM publisher_phones pp
      WHERE pp.publisher_id = p.id AND pp.phone = TRIM(p.alt_phone)
  );
