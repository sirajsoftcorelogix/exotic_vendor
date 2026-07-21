-- Rename email flags if create_publisher_contacts_tables.sql was already applied with billing names
ALTER TABLE vp_publishers
    CHANGE COLUMN publisher_email_is_billing publisher_email_is_primary TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE publisher_emails
    CHANGE COLUMN is_billing is_primary TINYINT(1) NOT NULL DEFAULT 0;
