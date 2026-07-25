-- Pending Exotic /order/create payload for local POS temp orders (linked to vp_order_info).
CREATE TABLE IF NOT EXISTS `vp_order_exotic_sync` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `vp_order_info_id` INT UNSIGNED NOT NULL,
    `order_number` VARCHAR(100) NOT NULL,
    `sync_payload` JSON NOT NULL,
    `api_error` VARCHAR(500) NULL DEFAULT NULL,
    `sync_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_vp_order_exotic_sync_order_info` (`vp_order_info_id`),
    UNIQUE KEY `uq_vp_order_exotic_sync_order_number` (`order_number`),
    CONSTRAINT `fk_vp_order_exotic_sync_order_info`
        FOREIGN KEY (`vp_order_info_id`) REFERENCES `vp_order_info` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
