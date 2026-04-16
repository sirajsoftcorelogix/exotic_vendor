-- Remove payment fields from direct purchases (run once on existing databases).
ALTER TABLE `vp_direct_purchases`
  DROP COLUMN `payment_mode`,
  DROP COLUMN `payment_reference`,
  DROP COLUMN `payment_date`,
  DROP COLUMN `payment_notes`;
