-- Order status workflow transitions (allowed from → to mappings).
-- Safe to re-run.

CREATE TABLE IF NOT EXISTS vp_workflow_transition (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_status_id INT NOT NULL,
    to_status_id INT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_transition (from_status_id, to_status_id),
    KEY idx_from_active (from_status_id, is_active),
    KEY idx_to_status (to_status_id),
    CONSTRAINT fk_wf_from FOREIGN KEY (from_status_id) REFERENCES vp_order_status (id),
    CONSTRAINT fk_wf_to FOREIGN KEY (to_status_id) REFERENCES vp_order_status (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
