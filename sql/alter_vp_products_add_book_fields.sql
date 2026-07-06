-- Book metadata on published products (fields 1–7 from inbound parity).
-- Run once on your DB before using product detail / Refresh from API book sync.

ALTER TABLE vp_products
    ADD COLUMN edited_by VARCHAR(255) NULL DEFAULT NULL COMMENT 'Book: comma-separated vp_author.author_id values',
    ADD COLUMN isbn VARCHAR(50) NULL DEFAULT NULL,
    ADD COLUMN cover_type VARCHAR(100) NULL DEFAULT NULL,
    ADD COLUMN edition VARCHAR(100) NULL DEFAULT NULL,
    ADD COLUMN publication_date DATE NULL DEFAULT NULL,
    ADD COLUMN language VARCHAR(500) NULL DEFAULT NULL COMMENT 'Book: combined language display string',
    ADD COLUMN pages INT NULL DEFAULT NULL;
