CREATE TABLE IF NOT EXISTS vp_inbound_profiler_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    run_id VARCHAR(16) NOT NULL,
    action VARCHAR(80) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'ok',
    total_ms INT UNSIGNED NOT NULL DEFAULT 0,
    memory_peak_mb DECIMAL(8,2) NULL,
    user_id INT UNSIGNED NULL,
    inbound_id INT UNSIGNED NULL,
    request_uri VARCHAR(512) NULL,
    payload_json MEDIUMTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_total_ms (total_ms),
    INDEX idx_inbound_id (inbound_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
