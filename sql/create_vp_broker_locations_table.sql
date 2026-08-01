-- Broker locations: multiple state + zone rows per broker
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

-- Migrate legacy single state/zone from vp_brokers (run once if columns exist)
INSERT INTO vp_broker_locations (broker_id, state, zone, sort_order, created_at, updated_at)
SELECT b.id, b.state, b.zone, 1, NOW(), NOW()
FROM vp_brokers b
WHERE (b.state IS NOT NULL AND TRIM(b.state) <> '')
   OR (b.zone IS NOT NULL AND TRIM(b.zone) <> '')
ON DUPLICATE KEY UPDATE
    updated_at = NOW();

-- Optional cleanup after migration:
-- ALTER TABLE vp_brokers DROP INDEX idx_vp_brokers_state;
-- ALTER TABLE vp_brokers DROP COLUMN state, DROP COLUMN zone;
