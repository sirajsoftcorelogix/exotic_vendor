CREATE TABLE IF NOT EXISTS publisher_emails (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    publisher_id INT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_publisher_emails_publisher_id (publisher_id),
    UNIQUE KEY uq_publisher_email (publisher_id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
