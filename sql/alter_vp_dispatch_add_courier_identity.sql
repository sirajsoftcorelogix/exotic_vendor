-- Courier identity on dispatch rows (run once).
-- Step 1: only if courier_name is missing on your table:
-- ALTER TABLE vp_dispatch_details
--   ADD COLUMN courier_name VARCHAR(255) NULL DEFAULT NULL AFTER dispatch_date;

ALTER TABLE vp_dispatch_details
  ADD COLUMN courier_company_id INT UNSIGNED NULL DEFAULT NULL COMMENT 'Shiprocket courier_company_id' AFTER courier_name,
  ADD COLUMN shipper_id INT UNSIGNED NULL DEFAULT NULL COMMENT 'Exotic India shipper_id from courier_partners' AFTER courier_company_id,
  ADD COLUMN courier_partner_id INT UNSIGNED NULL DEFAULT NULL COMMENT 'FK courier_partners.id — booking integration' AFTER shipper_id;

CREATE INDEX idx_vp_dispatch_shipper_id ON vp_dispatch_details (shipper_id);
CREATE INDEX idx_vp_dispatch_courier_company_id ON vp_dispatch_details (courier_company_id);
CREATE INDEX idx_vp_dispatch_courier_partner_id ON vp_dispatch_details (courier_partner_id);