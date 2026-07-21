-- Map distributors (vp_vendors) to publishers for indirect purchasing
CREATE TABLE IF NOT EXISTS publisher_vendor_mapping (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    publisher_id INT UNSIGNED NOT NULL,
    vendor_id INT UNSIGNED NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_publisher_vendor (publisher_id, vendor_id),
    KEY idx_pvm_publisher_id (publisher_id),
    KEY idx_pvm_vendor_id (vendor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
