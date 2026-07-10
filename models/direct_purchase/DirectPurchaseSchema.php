<?php

/**
 * Ensures direct-purchase tables/columns exist (safe for older production DBs).
 */
final class DirectPurchaseSchema
{
    public static function ensureAll(\mysqli $conn): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        self::ensureColumn(
            $conn,
            'vp_direct_purchases',
            'warehouse_id',
            'ALTER TABLE `vp_direct_purchases` ADD COLUMN `warehouse_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `vendor_id`'
        );
        self::ensureColumn(
            $conn,
            'vp_direct_purchases',
            'currency',
            'ALTER TABLE `vp_direct_purchases` ADD COLUMN `currency` VARCHAR(10) NOT NULL DEFAULT \'INR\' AFTER `invoice_file`'
        );
        self::ensureColumn(
            $conn,
            'vp_direct_purchases',
            'vendor_type',
            'ALTER TABLE `vp_direct_purchases` ADD COLUMN `vendor_type` VARCHAR(20) NOT NULL DEFAULT \'vendor\' AFTER `vendor_id`'
        );
        self::ensureInvoiceDateNullable($conn);

        self::ensureColumn(
            $conn,
            'vp_direct_purchase_items',
            'vendor_qty_synced',
            'ALTER TABLE `vp_direct_purchase_items` ADD COLUMN `vendor_qty_synced` TINYINT(1) NOT NULL DEFAULT 0 AFTER `sort_order`'
        );
        self::ensureColumn(
            $conn,
            'vp_direct_purchase_items',
            'vendor_qty_synced_qty',
            'ALTER TABLE `vp_direct_purchase_items` ADD COLUMN `vendor_qty_synced_qty` DECIMAL(15,3) DEFAULT NULL AFTER `vendor_qty_synced`'
        );

        self::ensureTable(
            $conn,
            'vp_direct_purchase_returns',
            'CREATE TABLE IF NOT EXISTS `vp_direct_purchase_returns` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `direct_purchase_id` INT UNSIGNED NOT NULL,
              `warehouse_id` INT UNSIGNED NOT NULL,
              `return_date` DATE NOT NULL,
              `remarks` VARCHAR(500) DEFAULT NULL,
              `currency` VARCHAR(10) NOT NULL DEFAULT \'INR\',
              `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
              `discount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
              `igst_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
              `sgst_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
              `cgst_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
              `round_off` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
              `grand_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
              `created_by` INT UNSIGNED DEFAULT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_vp_dpr_purchase` (`direct_purchase_id`),
              KEY `idx_vp_dpr_return_date` (`return_date`),
              CONSTRAINT `fk_vp_dpr_purchase`
                FOREIGN KEY (`direct_purchase_id`) REFERENCES `vp_direct_purchases` (`id`)
                ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::ensureTable(
            $conn,
            'vp_direct_purchase_return_items',
            'CREATE TABLE IF NOT EXISTS `vp_direct_purchase_return_items` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `direct_purchase_return_id` INT UNSIGNED NOT NULL,
              `direct_purchase_item_id` INT UNSIGNED NOT NULL,
              `return_qty` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
              `gst_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
              `line_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
              `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              KEY `idx_vp_dpri_return` (`direct_purchase_return_id`),
              KEY `idx_vp_dpri_dp_item` (`direct_purchase_item_id`),
              CONSTRAINT `fk_vp_dpri_return`
                FOREIGN KEY (`direct_purchase_return_id`) REFERENCES `vp_direct_purchase_returns` (`id`)
                ON DELETE CASCADE,
              CONSTRAINT `fk_vp_dpri_dp_item`
                FOREIGN KEY (`direct_purchase_item_id`) REFERENCES `vp_direct_purchase_items` (`id`)
                ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function ensureInvoiceDateNullable(\mysqli $conn): void
    {
        if (!self::tableExists($conn, 'vp_direct_purchases') || !self::columnExists($conn, 'vp_direct_purchases', 'invoice_date')) {
            return;
        }

        $res = @$conn->query("SHOW COLUMNS FROM `vp_direct_purchases` LIKE 'invoice_date'");
        if (!$res || !($row = $res->fetch_assoc())) {
            return;
        }
        $res->free();

        if (strtoupper((string) ($row['Null'] ?? '')) === 'YES') {
            return;
        }

        @$conn->query('ALTER TABLE `vp_direct_purchases` MODIFY `invoice_date` DATE NULL DEFAULT NULL');
    }

    private static function ensureColumn(\mysqli $conn, string $table, string $column, string $alterSql): void
    {
        if (self::columnExists($conn, $table, $column)) {
            return;
        }

        if (!@$conn->query($alterSql)) {
            $err = (string) $conn->error;
            if (stripos($err, 'Duplicate column') === false) {
                throw new \RuntimeException("Database schema update failed for {$table}.{$column}: {$err}");
            }
        }
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
        $safeTable = self::safeIdentifier($table);
        if ($safeTable === '') {
            return false;
        }

        $res = @$conn->query("SHOW TABLES LIKE '{$safeTable}'");
        if ($res && $res->num_rows > 0) {
            $res->free();
            return true;
        }

        return false;
    }

    private static function columnExists(\mysqli $conn, string $table, string $column): bool
    {
        $safeTable = self::safeIdentifier($table);
        $safeColumn = self::safeIdentifier($column);
        if ($safeTable === '' || $safeColumn === '') {
            return false;
        }

        $res = @$conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
        if ($res && $res->num_rows > 0) {
            $res->free();
            return true;
        }

        return false;
    }

    private static function safeIdentifier(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $name) ?? '';
    }
}
