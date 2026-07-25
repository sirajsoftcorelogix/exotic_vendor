CREATE TABLE IF NOT EXISTS publisher_phones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    publisher_id INT UNSIGNED NOT NULL,
    phone VARCHAR(20) NOT NULL,
    is_whatsapp TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_publisher_phones_publisher_id (publisher_id),
    UNIQUE KEY uq_publisher_phone (publisher_id, phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
