-- Move broker mapping from publishers to vendors (vp_vendors.broker_id → vp_brokers.id)

ALTER TABLE vp_vendors
    ADD COLUMN broker_id INT UNSIGNED NULL DEFAULT NULL AFTER discount,
    ADD KEY idx_vp_vendors_broker_id (broker_id);

-- Clear legacy publisher broker assignments (brokers are assigned on vendor records now).
UPDATE vp_publishers SET broker_id = NULL WHERE broker_id IS NOT NULL AND broker_id > 0;
