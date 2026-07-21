-- Link publisher to an active portal user (broker)
ALTER TABLE vp_publishers
    ADD COLUMN broker_id INT UNSIGNED NULL DEFAULT NULL AFTER discount,
    ADD KEY idx_vp_publishers_broker_id (broker_id);
