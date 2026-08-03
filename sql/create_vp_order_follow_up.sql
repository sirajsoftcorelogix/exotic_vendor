-- Follow-up orders (reship / replace / copy) linked to a source order.
-- Safe to re-run.

CREATE TABLE IF NOT EXISTS vp_order_follow_up (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_order_number VARCHAR(64) NOT NULL,
    follow_up_order_number VARCHAR(64) NOT NULL,
    follow_up_type ENUM('reship', 'replace', 'copy') NOT NULL DEFAULT 'copy',
    pricing_mode ENUM('waived', 'same_as_original', 'catalog', 'manual') NOT NULL DEFAULT 'catalog',
    scope ENUM('full', 'partial') NOT NULL DEFAULT 'full',
    sales_return_id INT UNSIGNED NULL DEFAULT NULL,
    source_invoice_id INT UNSIGNED NULL DEFAULT NULL,
    follow_up_invoice_id INT UNSIGNED NULL DEFAULT NULL,
    source_payable_total DECIMAL(15,2) NULL DEFAULT NULL,
    follow_up_payable_total DECIMAL(15,2) NULL DEFAULT NULL,
    receipt_number VARCHAR(64) NULL DEFAULT NULL,
    source_pricing_json JSON NULL,
    remarks VARCHAR(500) NULL DEFAULT NULL,
    created_by INT UNSIGNED NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_follow_up_order (follow_up_order_number),
    KEY idx_follow_up_source (source_order_number),
    KEY idx_follow_up_type (follow_up_type),
    KEY idx_follow_up_sales_return (sales_return_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
