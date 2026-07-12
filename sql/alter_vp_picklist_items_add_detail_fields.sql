-- Snapshot columns for picklist line items (publisher, cover type, physical qty, book flag).
-- Safe to run once; ignore errors if columns already exist.

ALTER TABLE `vp_picklist_items`
  ADD COLUMN `publisher` VARCHAR(255) NULL DEFAULT NULL AFTER `image`,
  ADD COLUMN `cover_type` VARCHAR(100) NULL DEFAULT NULL AFTER `publisher`,
  ADD COLUMN `physical_qty` INT NOT NULL DEFAULT 0 AFTER `cover_type`,
  ADD COLUMN `is_book` TINYINT(1) NOT NULL DEFAULT 0 AFTER `physical_qty`;
