CREATE TABLE IF NOT EXISTS book_languages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    iso VARCHAR(10) NOT NULL,
    language_name VARCHAR(255) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_iso (iso),
    UNIQUE KEY uk_language_name (language_name),
    INDEX idx_active (active),
    INDEX idx_language_name (language_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: sidebar menu (adjust parent_id / user_id / sort_order as needed).
-- Assign permissions to roles via Admin → Roles if items do not appear.
/*
INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
VALUES (0, 'Book Languages', 'languages', 'list', '<i class="fas fa-language mr-2"></i>', 1, 1, 210);
*/
