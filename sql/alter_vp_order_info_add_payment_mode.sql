-- Add payment_mode column to vp_order_info table for auto-filled payment mode (e.g. YES2971 for offline bank_transfer/UPI)
ALTER TABLE `vp_order_info` ADD COLUMN IF NOT EXISTS `payment_mode` VARCHAR(100) NULL DEFAULT NULL AFTER `payment_type`;
