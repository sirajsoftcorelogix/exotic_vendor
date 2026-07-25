-- POS temp orders use alphanumeric order numbers (e.g. POS-TMP-WZ-260726010134).
-- Legacy schema had vp_order_info.order_number as INT UNSIGNED.
ALTER TABLE `vp_order_info`
    MODIFY COLUMN `order_number` VARCHAR(100) NOT NULL;
