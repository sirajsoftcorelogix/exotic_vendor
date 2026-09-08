-- Size master: group-wise size codes (dropdown per item group).
CREATE TABLE IF NOT EXISTS size_master (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_group VARCHAR(120) NOT NULL,
    size_code VARCHAR(80) NOT NULL,
    size_label VARCHAR(255) NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    user_id INT UNSIGNED NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_size_master_group_code (item_group, size_code),
    INDEX idx_size_master_group_active (item_group, is_active),
    INDEX idx_size_master_active (is_active),
    INDEX idx_size_master_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional sidebar menu. Assign permissions via Admin → Roles if the item does not appear.
INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
SELECT
    COALESCE(
        (SELECT parent_id FROM modules WHERE slug IN ('materials', 'account_groups', 'languages') AND parent_id > 0 ORDER BY id ASC LIMIT 1),
        (SELECT id FROM modules WHERE parent_id = 0 AND slug IN ('materials', 'account_groups') LIMIT 1),
        0
    ),
    'Sizes',
    'sizes',
    'list',
    '<i class="fas fa-ruler-combined mr-2"></i>',
    1,
    1,
    220
WHERE NOT EXISTS (SELECT 1 FROM modules WHERE slug = 'sizes' LIMIT 1);
