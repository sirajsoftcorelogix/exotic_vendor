-- SQL migration for Export Document Generation Module

CREATE TABLE IF NOT EXISTS `vp_export_document_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_code` VARCHAR(64) NOT NULL,
  `invoice_id` INT UNSIGNED NULL DEFAULT NULL,
  `invoice_number` VARCHAR(64) NULL DEFAULT NULL,
  `order_number` VARCHAR(64) NULL DEFAULT NULL,
  `shipment_type` VARCHAR(32) NOT NULL DEFAULT 'csb5',
  `category` VARCHAR(64) NOT NULL DEFAULT 'sculpture_painting_home',
  `courier_partner` VARCHAR(32) NOT NULL DEFAULT 'ups',
  `is_drawback` TINYINT(1) NOT NULL DEFAULT 0,
  `has_rodtep` TINYINT(1) NOT NULL DEFAULT 0,
  `has_lacey` TINYINT(1) NOT NULL DEFAULT 0,
  `common_data_json` LONGTEXT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vp_exp_doc_session_code` (`session_code`),
  KEY `idx_vp_exp_doc_invoice_num` (`invoice_number`),
  KEY `idx_vp_exp_doc_order_num` (`order_number`),
  KEY `idx_vp_exp_doc_shipment_type` (`shipment_type`),
  KEY `idx_vp_exp_doc_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vp_export_document_forms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` INT UNSIGNED NOT NULL,
  `document_code` VARCHAR(64) NOT NULL,
  `document_title` VARCHAR(128) NOT NULL,
  `form_data_json` LONGTEXT NULL,
  `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vp_exp_doc_session_doc` (`session_id`, `document_code`),
  KEY `idx_vp_exp_doc_session_id` (`session_id`),
  KEY `idx_vp_exp_doc_code` (`document_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
