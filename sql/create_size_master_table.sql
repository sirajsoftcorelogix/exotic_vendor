-- Size master: group-wise size codes used on inbound (dropdown when a group has sizes).
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

-- Clothing / textiles: copied from the inbound clothing size dropdown.
INSERT IGNORE INTO size_master (item_group, size_code, size_label, display_order, is_active) VALUES
('textiles', 'XS', 'Extra Small (XS)(34)', 10, 1),
('textiles', 'S', 'Small (S)(36)', 20, 1),
('textiles', 'M', 'Medium (M)(38)', 30, 1),
('textiles', 'L', 'Large (L)(40)', 40, 1),
('textiles', 'XL', 'Extra Large (XL)(42)', 50, 1),
('textiles', 'XXL', 'Extra Extra Large (XXL)(44)', 60, 1),
('textiles', 'XXXL', 'Extra Extra Extra Large (XXXL)(46)', 70, 1),
('textiles', 'XXXXL', 'Extra Extra Extra Large (4xL)(48)', 80, 1),
('textiles', 'XXXXXL', 'Extra Extra Extra Large (5xL)(50)', 90, 1),
('textiles', 'XXXXXXL', 'Extra Extra Extra Large (6xL)(52)', 100, 1),
('textiles', 'FS', 'Free Size', 110, 1),
('textiles', 'OS', 'One Size', 120, 1),
('clothing', 'XS', 'Extra Small (XS)(34)', 10, 1),
('clothing', 'S', 'Small (S)(36)', 20, 1),
('clothing', 'M', 'Medium (M)(38)', 30, 1),
('clothing', 'L', 'Large (L)(40)', 40, 1),
('clothing', 'XL', 'Extra Large (XL)(42)', 50, 1),
('clothing', 'XXL', 'Extra Extra Large (XXL)(44)', 60, 1),
('clothing', 'XXXL', 'Extra Extra Extra Large (XXXL)(46)', 70, 1),
('clothing', 'XXXXL', 'Extra Extra Extra Large (4xL)(48)', 80, 1),
('clothing', 'XXXXXL', 'Extra Extra Extra Large (5xL)(50)', 90, 1),
('clothing', 'XXXXXXL', 'Extra Extra Extra Large (6xL)(52)', 100, 1),
('clothing', 'FS', 'Free Size', 110, 1),
('clothing', 'OS', 'One Size', 120, 1);

-- Jewelry: common US ring sizes.
INSERT IGNORE INTO size_master (item_group, size_code, size_label, display_order, is_active) VALUES
('jewelry', '4', 'Ring Size 4', 10, 1),
('jewelry', '5', 'Ring Size 5', 20, 1),
('jewelry', '6', 'Ring Size 6', 30, 1),
('jewelry', '7', 'Ring Size 7', 40, 1),
('jewelry', '8', 'Ring Size 8', 50, 1),
('jewelry', '9', 'Ring Size 9', 60, 1),
('jewelry', '10', 'Ring Size 10', 70, 1),
('jewelry', '11', 'Ring Size 11', 80, 1),
('jewelry', '12', 'Ring Size 12', 90, 1),
('jewelry', '13', 'Ring Size 13', 100, 1),
('jewelry', '14', 'Ring Size 14', 110, 1),
('jewelry', '15', 'Ring Size 15', 120, 1);

-- Footwear: UK / India shoe sizes.
INSERT IGNORE INTO size_master (item_group, size_code, size_label, display_order, is_active) VALUES
('footwear', '3', 'UK 3', 10, 1),
('footwear', '4', 'UK 4', 20, 1),
('footwear', '5', 'UK 5', 30, 1),
('footwear', '6', 'UK 6', 40, 1),
('footwear', '7', 'UK 7', 50, 1),
('footwear', '8', 'UK 8', 60, 1),
('footwear', '9', 'UK 9', 70, 1),
('footwear', '10', 'UK 10', 80, 1),
('footwear', '11', 'UK 11', 90, 1),
('footwear', '12', 'UK 12', 100, 1);

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
