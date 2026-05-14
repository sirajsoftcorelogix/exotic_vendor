-- High Value Transaction compliance support for POS billing.
-- Runtime code also checks/creates these columns defensively.

ALTER TABLE global_settings
  ADD COLUMN IF NOT EXISTS high_value_transaction_limit DECIMAL(15,2) NOT NULL DEFAULT 200000.00 AFTER terms_and_conditions;

UPDATE global_settings
SET high_value_transaction_limit = 200000.00
WHERE id = 1 AND (high_value_transaction_limit IS NULL OR high_value_transaction_limit <= 0);

ALTER TABLE vp_customers
  ADD COLUMN IF NOT EXISTS customer_residency_status ENUM('INDIAN_RESIDENT','NRI','FOREIGN_NATIONAL') NOT NULL DEFAULT 'INDIAN_RESIDENT' AFTER phone,
  ADD COLUMN IF NOT EXISTS customer_pan VARCHAR(10) NOT NULL DEFAULT '' AFTER customer_residency_status,
  ADD COLUMN IF NOT EXISTS passport_number VARCHAR(32) NOT NULL DEFAULT '' AFTER customer_pan,
  ADD COLUMN IF NOT EXISTS country_of_residence VARCHAR(128) NOT NULL DEFAULT '' AFTER passport_number;

ALTER TABLE vp_invoices
  ADD COLUMN IF NOT EXISTS is_high_value_transaction TINYINT(1) NOT NULL DEFAULT 0 AFTER total_amount,
  ADD COLUMN IF NOT EXISTS high_value_transaction_limit DECIMAL(15,2) NULL AFTER is_high_value_transaction,
  ADD COLUMN IF NOT EXISTS high_value_compliance_status ENUM('NOT_REQUIRED','PENDING','COMPLETED') NOT NULL DEFAULT 'NOT_REQUIRED' AFTER high_value_transaction_limit;

CREATE TABLE IF NOT EXISTS pos_high_value_compliance_audit (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT NULL,
  order_number VARCHAR(100) NOT NULL DEFAULT '',
  payment_id INT UNSIGNED NULL,
  invoice_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  high_value_limit DECIMAL(15,2) NOT NULL DEFAULT 200000.00,
  payment_modes VARCHAR(255) NOT NULL DEFAULT '',
  residency_status ENUM('INDIAN_RESIDENT','NRI','FOREIGN_NATIONAL') NOT NULL DEFAULT 'INDIAN_RESIDENT',
  pan_captured ENUM('Y','N') NOT NULL DEFAULT 'N',
  passport_captured ENUM('Y','N') NOT NULL DEFAULT 'N',
  gstin_present ENUM('Y','N') NOT NULL DEFAULT 'N',
  compliance_completed_timestamp DATETIME NULL,
  cashier_user_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_order_number (order_number),
  KEY idx_invoice_id (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
