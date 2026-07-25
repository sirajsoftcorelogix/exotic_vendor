-- POS temp orders use order_number values like POS-TMP-WZ-260726021729.
-- Any INT order_number column breaks rename/publish (MySQL: "Truncated incorrect INTEGER value").
-- Run once on production; the app also auto-migrates INT columns on first rename/persist.

ALTER TABLE `vp_order_info`
    MODIFY COLUMN `order_number` VARCHAR(100) NOT NULL;

-- Uncomment/adjust if SHOW COLUMNS reports INT on these tables in your DB:
-- ALTER TABLE `vp_orders` MODIFY COLUMN `order_number` VARCHAR(100) NOT NULL;
-- ALTER TABLE `pos_payments` MODIFY COLUMN `order_number` VARCHAR(100) NULL;
-- ALTER TABLE `vp_invoices` MODIFY COLUMN `order_number` VARCHAR(100) NULL;
-- ALTER TABLE `vp_invoice_items` MODIFY COLUMN `order_number` VARCHAR(100) NULL;
