-- Add contact, address, and tax fields to vp_publishers (aligned with vp_vendors)
ALTER TABLE vp_publishers
    ADD COLUMN contact_name VARCHAR(255) NULL DEFAULT NULL AFTER publishers,
    ADD COLUMN publisher_email VARCHAR(255) NULL DEFAULT NULL AFTER contact_name,
    ADD COLUMN country_code VARCHAR(10) NULL DEFAULT NULL AFTER publisher_email,
    ADD COLUMN publisher_phone VARCHAR(20) NULL DEFAULT NULL AFTER country_code,
    ADD COLUMN alt_phone VARCHAR(20) NULL DEFAULT NULL AFTER publisher_phone,
    ADD COLUMN gst_number VARCHAR(30) NULL DEFAULT NULL AFTER alt_phone,
    ADD COLUMN pan_number VARCHAR(20) NULL DEFAULT NULL AFTER gst_number,
    ADD COLUMN address VARCHAR(500) NULL DEFAULT NULL AFTER pan_number,
    ADD COLUMN city VARCHAR(100) NULL DEFAULT NULL AFTER address,
    ADD COLUMN state VARCHAR(100) NULL DEFAULT NULL AFTER city,
    ADD COLUMN country VARCHAR(100) NULL DEFAULT NULL AFTER state,
    ADD COLUMN postal_code VARCHAR(20) NULL DEFAULT NULL AFTER country,
    ADD COLUMN webpage TINYINT(1) NOT NULL DEFAULT 0 AFTER postal_code,
    ADD COLUMN stock_replenishment_months INT UNSIGNED NOT NULL DEFAULT 0 AFTER webpage;
