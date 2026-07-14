-- Book inbound: compiled / translated / commentary creators (comma-separated author ids).
ALTER TABLE `vp_inbound`
    ADD `compiled_by` VARCHAR(255) NULL COMMENT 'field for books' AFTER `edited_by`,
    ADD `translated_by` VARCHAR(255) NULL COMMENT 'field for books' AFTER `compiled_by`,
    ADD `commentary_by` VARCHAR(255) NULL COMMENT 'field for books' AFTER `translated_by`;
