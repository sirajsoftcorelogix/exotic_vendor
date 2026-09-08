-- Add new shipping fields to vp_invoices_international table
ALTER TABLE vp_invoices_international
ADD COLUMN shipping_bill_number VARCHAR(255) DEFAULT '',
ADD COLUMN shipping_bill_date DATE DEFAULT NULL,
ADD COLUMN shipping_port VARCHAR(255) DEFAULT '',
ADD COLUMN shipping_ref_clm VARCHAR(255) DEFAULT '',
ADD COLUMN shipping_currency VARCHAR(10) DEFAULT '',
ADD COLUMN shipping_country_code VARCHAR(10) DEFAULT '',
ADD COLUMN shipping_exp_duty DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN transport_selection VARCHAR(10) DEFAULT 'mode',
ADD COLUMN trans_mode VARCHAR(10) DEFAULT '',
ADD COLUMN veh_no VARCHAR(30) DEFAULT '',
ADD COLUMN veh_type VARCHAR(10) DEFAULT '',
ADD COLUMN trans_doc_no VARCHAR(50) DEFAULT '',
ADD COLUMN trans_doc_dt VARCHAR(20) DEFAULT '',
ADD COLUMN trans_id VARCHAR(20) DEFAULT '',
ADD COLUMN trans_name VARCHAR(120) DEFAULT '';