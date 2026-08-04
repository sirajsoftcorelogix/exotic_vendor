-- Create table for storing E-way Bill and IRN generation records for domestic invoices
CREATE TABLE IF NOT EXISTS `vp_domestic_ewb_irn` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `vp_invoices_id` BIGINT UNSIGNED NOT NULL,
  `irn` VARCHAR(64) COMMENT 'Invoice Registration Number from Alankit API',
  `ewb` VARCHAR(64) COMMENT 'E-way Bill number',
  `ewb_no` VARCHAR(64) COMMENT 'Generated E-way Bill number (explicit field)',
  `ewb_date` DATETIME COMMENT 'E-way Bill generation date/time',
  `ewb_valid_till` DATETIME COMMENT 'E-way Bill validity date/time',
  `gen_gstin` VARCHAR(20) COMMENT 'GSTIN used to generate E-way Bill',
  `info_dtls` LONGTEXT COMMENT 'InfoDtls from Alankit IRN/EWB response',
  `irn_payload` LONGTEXT COMMENT 'JSON payload sent for IRN generation',
  `irn_response` LONGTEXT COMMENT 'JSON response from IRN generation API',
  `ewb_payload` LONGTEXT COMMENT 'JSON payload sent for E-way bill generation',
  `ewb_response` LONGTEXT COMMENT 'JSON response from E-way bill generation API',
  `veh_no` VARCHAR(20) COMMENT 'Vehicle Number for E-way bill',
  `veh_type` VARCHAR(10) COMMENT 'Vehicle Type (R/A/S for Road/Air/Ship)',
  `irn_status` ENUM('pending', 'generated', 'failed', 'cancelled') DEFAULT 'pending' COMMENT 'Status of IRN generation',
  `ewb_status` ENUM('pending', 'generated', 'failed', 'cancelled') DEFAULT 'pending' COMMENT 'Status of E-way bill generation',
  `irn_error` TEXT COMMENT 'Error message if IRN generation failed',
  `ewb_error` TEXT COMMENT 'Error message if E-way bill generation failed',
  `irn_generated_at` DATETIME COMMENT 'Timestamp when IRN was generated',
  `ewb_generated_at` DATETIME COMMENT 'Timestamp when E-way bill was generated',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  UNIQUE KEY `unique_invoice_irn` (`vp_invoices_id`),
  KEY `idx_irn` (`irn`),
  KEY `idx_ewb` (`ewb`),
  KEY `idx_irn_status` (`irn_status`),
  KEY `idx_ewb_status` (`ewb_status`),
  KEY `idx_created_at` (`created_at`),
  
  CONSTRAINT `fk_ewb_irn_invoice` FOREIGN KEY (`vp_invoices_id`) REFERENCES `vp_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores E-way Bill and IRN generation records for domestic invoices';

-- For existing databases where the table already exists, run these safely:
ALTER TABLE `vp_domestic_ewb_irn` ADD COLUMN IF NOT EXISTS `ewb_no` VARCHAR(64) COMMENT 'Generated E-way Bill number (explicit field)' AFTER `ewb`;
ALTER TABLE `vp_domestic_ewb_irn` ADD COLUMN IF NOT EXISTS `ewb_date` DATETIME COMMENT 'E-way Bill generation date/time' AFTER `ewb_no`;
ALTER TABLE `vp_domestic_ewb_irn` ADD COLUMN IF NOT EXISTS `ewb_valid_till` DATETIME COMMENT 'E-way Bill validity date/time' AFTER `ewb_date`;
ALTER TABLE `vp_domestic_ewb_irn` ADD COLUMN IF NOT EXISTS `gen_gstin` VARCHAR(20) COMMENT 'GSTIN used to generate E-way Bill' AFTER `ewb_valid_till`;
ALTER TABLE `vp_domestic_ewb_irn` ADD COLUMN IF NOT EXISTS `info_dtls` LONGTEXT COMMENT 'InfoDtls from Alankit IRN/EWB response' AFTER `gen_gstin`;
ALTER TABLE `vp_domestic_ewb_irn` ADD COLUMN IF NOT EXISTS `trade_name` VARCHAR(100) NULL AFTER `ewb_response`;

