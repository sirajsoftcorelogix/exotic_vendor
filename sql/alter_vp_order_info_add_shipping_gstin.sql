-- Add shipping GSTIN to order address info
ALTER TABLE `vp_order_info`
    ADD COLUMN `shipping_gstin` VARCHAR(20) NULL DEFAULT NULL AFTER `shipping_email`;
