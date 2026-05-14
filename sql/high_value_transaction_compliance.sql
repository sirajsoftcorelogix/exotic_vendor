-- High Value Transaction compliance support for POS billing.
-- Runtime code also checks/creates these columns defensively.

ALTER TABLE global_settings
  ADD COLUMN IF NOT EXISTS high_value_transaction_limit DECIMAL(15,2) NOT NULL DEFAULT 200000.00;

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
