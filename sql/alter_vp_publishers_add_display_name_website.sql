ALTER TABLE vp_publishers
    ADD COLUMN display_name VARCHAR(255) NULL DEFAULT NULL AFTER publishers,
    ADD COLUMN website VARCHAR(500) NULL DEFAULT NULL AFTER display_name;
