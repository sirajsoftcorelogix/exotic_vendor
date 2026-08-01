-- Independent broker master (vp_publishers.broker_id → vp_brokers.id; not vp_users)
CREATE TABLE IF NOT EXISTS vp_brokers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    broker_name VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_vp_brokers_broker_name (broker_name),
    INDEX idx_vp_brokers_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vp_broker_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    broker_id INT UNSIGNED NOT NULL,
    state VARCHAR(255) NULL DEFAULT NULL,
    zone VARCHAR(255) NULL DEFAULT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vp_broker_locations_broker_id (broker_id),
    INDEX idx_vp_broker_locations_state (state),
    INDEX idx_vp_broker_locations_zone (zone),
    UNIQUE KEY uk_vp_broker_locations_broker_state_zone (broker_id, state, zone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Legacy publisher broker_id values pointed at vp_users.id; clear them so brokers are reassigned from Broker Master.
UPDATE vp_publishers SET broker_id = NULL WHERE broker_id IS NOT NULL AND broker_id > 0;

-- Optional: sidebar menu (adjust parent_id / user_id / sort_order as needed).
-- Assign permissions to roles via Admin → Roles if items do not appear.
/*
INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
VALUES (0, 'Broker Master', 'brokers', 'list', '<i class="fas fa-user-tie mr-2"></i>', 1, 1, 215);
*/