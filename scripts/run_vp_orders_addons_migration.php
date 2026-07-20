<?php

declare(strict_types=1);

/**
 * Apply sql/alter_vp_orders_add_addons.sql
 * CLI (project root): php scripts/run_vp_orders_addons_migration.php
 */

chdir(dirname(__DIR__));

$config = require 'config.php';
$db = $config['db'] ?? [];
$conn = new mysqli(
    (string) ($db['host'] ?? '127.0.0.1'),
    (string) ($db['user'] ?? ''),
    (string) ($db['pass'] ?? ''),
    (string) ($db['name'] ?? ''),
    (int) ($db['port'] ?? 3306)
);

if ($conn->connect_error) {
    fwrite(STDERR, 'DB connect failed: ' . $conn->connect_error . PHP_EOL);
    exit(1);
}

$conn->set_charset((string) ($db['charset'] ?? 'utf8mb4'));

require_once 'models/order/order.php';

$res = $conn->query("SHOW COLUMNS FROM vp_orders LIKE 'addons'");
$exists = $res && $res->num_rows > 0;
if ($res) {
    $res->free();
}

if (!$exists) {
    $sql = trim(preg_replace('/^--.*$/m', '', (string) file_get_contents(__DIR__ . '/../sql/alter_vp_orders_add_addons.sql')));
    if ($sql === '' || !$conn->query($sql)) {
        fwrite(STDERR, 'Migration failed: ' . $conn->error . PHP_EOL);
        exit(1);
    }
    echo "Migration applied: vp_orders.addons column added.\n";
} else {
    echo "Column vp_orders.addons already exists.\n";
}

$sample = Order::normalizeVendorOrderLineAddons(['Add on Frame' => 12995]);
echo 'Normalize test: ' . ($sample ?? 'null') . PHP_EOL;

$res2 = $conn->query('DESCRIBE vp_orders addons');
if ($res2 && ($row = $res2->fetch_assoc())) {
    echo 'Column type: ' . $row['Type'] . ', Null: ' . $row['Null'] . PHP_EOL;
}
