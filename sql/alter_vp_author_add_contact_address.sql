-- Add contact and address fields to vp_author (aligned with vp_publishers)
ALTER TABLE vp_author
    ADD COLUMN contact_name VARCHAR(255) NULL DEFAULT NULL AFTER author,
    ADD COLUMN author_email VARCHAR(255) NULL DEFAULT NULL AFTER contact_name,
    ADD COLUMN country_code VARCHAR(10) NULL DEFAULT NULL AFTER author_email,
    ADD COLUMN author_phone VARCHAR(20) NULL DEFAULT NULL AFTER country_code,
    ADD COLUMN alt_phone VARCHAR(20) NULL DEFAULT NULL AFTER author_phone,
    ADD COLUMN address VARCHAR(500) NULL DEFAULT NULL AFTER alt_phone,
    ADD COLUMN city VARCHAR(100) NULL DEFAULT NULL AFTER address,
    ADD COLUMN state VARCHAR(100) NULL DEFAULT NULL AFTER city,
    ADD COLUMN country VARCHAR(100) NULL DEFAULT NULL AFTER state,
    ADD COLUMN postal_code VARCHAR(20) NULL DEFAULT NULL AFTER country,
    ADD COLUMN webpage TINYINT(1) NOT NULL DEFAULT 0 AFTER postal_code;
