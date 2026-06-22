-- Book-only fees for inbound desktop form / product publish API
ALTER TABLE vp_inbound
    ADD COLUMN sourcingfee DECIMAL(12,2) NULL DEFAULT NULL AFTER pages,
    ADD COLUMN shippingfee DECIMAL(12,2) NULL DEFAULT NULL AFTER sourcingfee;
