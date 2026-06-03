-- Book inbound: multiple editors stored as comma-separated author ids (same as author field).
ALTER TABLE `vp_inbound`
    ADD `edited_by` VARCHAR(255) NULL COMMENT 'field for books' AFTER `author`;
