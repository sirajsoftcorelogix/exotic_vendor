-- Cached vendor catalog reference data (colormaps, optionals) synced from Exotic vendor API.
CREATE TABLE IF NOT EXISTS `vendor_reference_cache` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(64) NOT NULL,
  `payload_json` longtext NOT NULL,
  `synced_at` datetime NOT NULL,
  `sync_status` enum('ok','error') NOT NULL DEFAULT 'ok',
  `http_code` smallint DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `updated_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cache_key` (`cache_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
