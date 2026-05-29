<?php
/**
 * TEMPORARY — map vp_inbound.vendor_code from local vp_vendors.id to Exotic vendor_id.
 *
 * DELETE this file after migration is verified on all environments.
 *
 * CLI:
 *   php scripts/migrate_inbound_vendor_code_to_vendor_id.php
 *   php scripts/migrate_inbound_vendor_code_to_vendor_id.php --execute
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

if (!is_file($configPath)) {
    fwrite(STDERR, "Missing config.php at {$configPath}\n");
    exit(1);
}

/** @var array $config */
$config = require $configPath;
$execute = in_array('--execute', $_SERVER['argv'] ?? [], true);

$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    fwrite(STDERR, "config.php must define ['db'] with host, name, user, pass.\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli(
    (string) $dbCfg['host'],
    (string) ($dbCfg['user'] ?? ''),
    (string) ($dbCfg['pass'] ?? ''),
    (string) $dbCfg['name']
);
$conn->set_charset('utf8mb4');

$previewSql = <<<'SQL'
SELECT i.id AS inbound_id,
       i.vendor_code AS current_value,
       v.id AS local_vendor_pk,
       v.vendor_id AS exotic_vendor_id
FROM vp_inbound i
INNER JOIN vp_vendors v ON CAST(i.vendor_code AS UNSIGNED) = v.id
WHERE v.vendor_id IS NOT NULL
  AND TRIM(v.vendor_id) <> ''
  AND NOT EXISTS (
    SELECT 1 FROM vp_vendors vx
    WHERE TRIM(vx.vendor_id) = TRIM(i.vendor_code)
  )
ORDER BY i.id ASC
SQL;

$rows = $conn->query($previewSql)->fetch_all(MYSQLI_ASSOC);
$count = count($rows);

echo ($execute ? 'EXECUTE' : 'DRY RUN') . ": {$count} inbound row(s) would be updated.\n\n";

if ($count === 0) {
    exit(0);
}

foreach ($rows as $row) {
    echo sprintf(
        "inbound #%s: vendor_code %s -> %s (local pk %s)\n",
        $row['inbound_id'],
        $row['current_value'],
        $row['exotic_vendor_id'],
        $row['local_vendor_pk']
    );
}

if (!$execute) {
    echo "\nRe-run with --execute to apply.\n";
    exit(0);
}

$updateSql = <<<'SQL'
UPDATE vp_inbound i
INNER JOIN vp_vendors v ON CAST(i.vendor_code AS UNSIGNED) = v.id
SET i.vendor_code = v.vendor_id
WHERE v.vendor_id IS NOT NULL
  AND TRIM(v.vendor_id) <> ''
  AND NOT EXISTS (
    SELECT 1 FROM vp_vendors vx
    WHERE TRIM(vx.vendor_id) = TRIM(i.vendor_code)
  )
SQL;

$conn->query($updateSql);
echo "\nUpdated {$conn->affected_rows} row(s).\n";
