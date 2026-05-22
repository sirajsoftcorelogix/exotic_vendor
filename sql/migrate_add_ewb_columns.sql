-- Add EWB-specific columns to vp_invoices_international table if they don't exist
-- These columns will store E-Way Bill generation request/response data and errors

ALTER TABLE `vp_invoices_international` ADD `ewb_number` INT NULL AFTER `qrcode_string`, ADD `ewb_date` DATETIME NULL AFTER `ewb_number`, ADD `ewb_valid_till` DATETIME NULL AFTER `ewb_date`;

ALTER TABLE `vp_invoices_international` 
ADD COLUMN IF NOT EXISTS `ewb_request_payload` LONGTEXT NULL COMMENT 'EWB generation request payload (JSON)',
ADD COLUMN IF NOT EXISTS `ewb_response_payload` LONGTEXT NULL COMMENT 'EWB generation response payload (JSON)',
ADD COLUMN IF NOT EXISTS `ewb_error_message` LONGTEXT NULL COMMENT 'EWB generation error details (JSON)';
