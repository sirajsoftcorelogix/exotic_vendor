-- Per-line addon pricing from Exotic vendor-api/order/fetch (cart[].addons).
-- Example: {"Add on Frame": 12995}

ALTER TABLE `vp_orders`
    ADD COLUMN `addons` JSON NULL DEFAULT NULL AFTER `options`;
