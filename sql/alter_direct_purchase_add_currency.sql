-- Add invoice currency to direct purchases (default INR). Run once if the table already exists.
ALTER TABLE `vp_direct_purchases`
  ADD COLUMN `currency` VARCHAR(10) NOT NULL DEFAULT 'INR' AFTER `invoice_file`;
