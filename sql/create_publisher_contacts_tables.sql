-- Publisher contacts + broker migration
-- Applies recent publisher master changes:
--   - broker_id on vp_publishers (links to vp_users.id)
--   - primary contact flags: publisher_phone_is_whatsapp, publisher_email_is_primary
--   - alternate phones/emails in publisher_phones / publisher_emails
--   - legacy alt_phone copied into publisher_phones
--
-- Prerequisites (run first if missing):
--   sql/alter_vp_publishers_add_contact_address_tax.sql
--   sql/alter_vp_publishers_add_discount.sql
--
-- If CREATE TABLE failed partway, run:
--   sql/fix_publisher_phones_table.sql
--   sql/fix_publisher_emails_table.sql
-- Or run one table at a time:
--   sql/create_publisher_phones_table.sql
--   sql/create_publisher_emails_table.sql
-- If collation errors occur on migrate, run: sql/alter_publisher_contacts_collation.sql
-- If you used old billing column names, run: sql/rename_publisher_email_billing_to_primary.sql

-- ---------------------------------------------------------------------------
-- 1. broker_id on vp_publishers
-- ---------------------------------------------------------------------------
ALTER TABLE vp_publishers
    ADD COLUMN broker_id INT UNSIGNED NULL DEFAULT NULL AFTER discount;

ALTER TABLE vp_publishers
    ADD KEY idx_vp_publishers_broker_id (broker_id);

-- ---------------------------------------------------------------------------
-- 2. Primary contact flags on vp_publishers
-- ---------------------------------------------------------------------------
ALTER TABLE vp_publishers
    ADD COLUMN publisher_phone_is_whatsapp TINYINT(1) NOT NULL DEFAULT 0 AFTER publisher_phone;

ALTER TABLE vp_publishers
    ADD COLUMN publisher_email_is_primary TINYINT(1) NOT NULL DEFAULT 0 AFTER publisher_email;

-- ---------------------------------------------------------------------------
-- 3. Alternate phones
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS publisher_phones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    publisher_id INT UNSIGNED NOT NULL,
    phone VARCHAR(20) NOT NULL,
    is_whatsapp TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_publisher_phones_publisher_id (publisher_id),
    UNIQUE KEY uq_publisher_phone (publisher_id, phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 4. Alternate emails (is_primary only — do NOT add is_billing)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS publisher_emails (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    publisher_id INT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_publisher_emails_publisher_id (publisher_id),
    UNIQUE KEY uq_publisher_email (publisher_id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 5. Migrate legacy alt_phone into publisher_phones
-- Run separately if needed: sql/migrate_publisher_alt_phone.sql
-- ---------------------------------------------------------------------------
INSERT INTO publisher_phones (publisher_id, phone, is_whatsapp, sort_order)
SELECT p.id, TRIM(p.alt_phone), 0, 0
FROM vp_publishers p
WHERE TRIM(COALESCE(p.alt_phone, '')) <> ''
  AND NOT EXISTS (
      SELECT 1 FROM publisher_phones pp
      WHERE pp.publisher_id = p.id
        AND BINARY pp.phone = BINARY TRIM(p.alt_phone)
  );
