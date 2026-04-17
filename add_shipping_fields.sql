-- Add new shipping fields to vp_invoices_international table
ALTER TABLE vp_invoices_international
ADD COLUMN shipping_bill_number VARCHAR(255) DEFAULT '',
ADD COLUMN shipping_bill_date DATE DEFAULT NULL,
ADD COLUMN shipping_port VARCHAR(255) DEFAULT '',
ADD COLUMN shipping_ref_clm VARCHAR(255) DEFAULT '',
ADD COLUMN shipping_currency VARCHAR(10) DEFAULT '',
ADD COLUMN shipping_country_code VARCHAR(10) DEFAULT '',
ADD COLUMN shipping_exp_duty DECIMAL(10,2) DEFAULT 0.00;