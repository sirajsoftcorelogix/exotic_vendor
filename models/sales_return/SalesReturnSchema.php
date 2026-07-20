<?php

/**
 * Ensures sales return tables exist (safe for older production DBs).
 */
final class SalesReturnSchema
{
    public static function ensureAll(\mysqli $conn): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        self::ensureTable(
            $conn,
            'vp_sales_returns',
            'CREATE TABLE IF NOT EXISTS `vp_sales_returns` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `return_number` VARCHAR(32) NOT NULL,
              `order_number` VARCHAR(64) NOT NULL,
              `invoice_id` INT UNSIGNED NULL DEFAULT NULL,
              `warehouse_id` INT UNSIGNED NOT NULL DEFAULT 0,
              `return_date` DATE NOT NULL,
              `return_type` VARCHAR(32) NOT NULL DEFAULT \'customer_request\',
              `remarks` VARCHAR(500) DEFAULT NULL,
              `status` VARCHAR(20) NOT NULL DEFAULT \'finalized\',
              `stock_applied` TINYINT(1) NOT NULL DEFAULT 0,
              `created_by` INT UNSIGNED NULL DEFAULT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_vp_sr_return_number` (`return_number`),
              KEY `idx_vp_sr_order_number` (`order_number`),
              KEY `idx_vp_sr_invoice_id` (`invoice_id`),
              KEY `idx_vp_sr_return_date` (`return_date`),
              KEY `idx_vp_sr_warehouse_id` (`warehouse_id`),
              KEY `idx_vp_sr_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::ensureTable(
            $conn,
            'vp_sales_return_items',
            'CREATE TABLE IF NOT EXISTS `vp_sales_return_items` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `sales_return_id` INT UNSIGNED NOT NULL,
              `invoice_item_id` INT UNSIGNED NULL DEFAULT NULL,
              `order_row_id` INT UNSIGNED NULL DEFAULT NULL,
              `product_id` INT UNSIGNED NULL DEFAULT NULL,
              `item_code` VARCHAR(64) DEFAULT NULL,
              `size` VARCHAR(64) DEFAULT NULL,
              `color` VARCHAR(64) DEFAULT NULL,
              `return_qty` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
              `stock_applied_qty` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
              `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              KEY `idx_vp_sri_return` (`sales_return_id`),
              KEY `idx_vp_sri_invoice_item` (`invoice_item_id`),
              KEY `idx_vp_sri_order_row` (`order_row_id`),
              CONSTRAINT `fk_vp_sri_return`
                FOREIGN KEY (`sales_return_id`) REFERENCES `vp_sales_returns` (`id`)
                ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function ensureTable(\mysqli $conn, string $table, string $createSql): void
    {
        if (self::tableExists($conn, $table)) {
            return;
        }

        if (!@$conn->query($createSql)) {
            throw new \RuntimeException("Database schema update failed creating {$table}: " . $conn->error);
        }
    }

    private static function tableExists(\mysqli $conn, string $table): bool
    {
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        if ($safeTable === '') {
            return false;
        }

        $res = @$conn->query("SHOW TABLES LIKE '{$safeTable}'");

        return $res && $res->num_rows > 0;
    }
}
