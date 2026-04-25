<?php
/**
 * Backfill vp_products.vendor_id by matching vp_products.vendor (vendor name)
 * to vp_vendors.vendor_name (or vp_vendors.vendors_name when present).
 *
 * Default: DRY RUN (no writes).
 *
 * Usage (from project root):
 *   php scripts/backfill_vp_products_vendor_id_from_vendor_name.php
 *   php scripts/backfill_vp_products_vendor_id_from_vendor_name.php --execute
 *   php scripts/backfill_vp_products_vendor_id_from_vendor_name.php --execute --limit=500
 *
 * Notes:
 * - Matching is case-insensitive and trims surrounding spaces.
 * - Existing non-zero vendor_id in vp_products is left unchanged.
 * - If multiple vp_vendors rows share same normalized name, first by id ASC is used.
 */
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
}

$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

function backfill_fail(string $msg, int $code = 1): void
{
    global $isCli;
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
    } else {
        http_response_code($code >= 400 && $code < 600 ? $code : 500);
        echo $msg . PHP_EOL;
    }
    exit(1);
}

function table_has_column(mysqli $conn, string $table, string $column): bool
{
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = $conn->real_escape_string($column);
    // NOTE: MySQL does not reliably support placeholders in SHOW ... LIKE.
    $sql = "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'";
    $res = $conn->query($sql);
    if ($res === false) {
        return false;
    }
    $ok = (bool)$res->fetch_assoc();
    $res->free();
    return $ok;
}

if (!is_file($configPath)) {
    backfill_fail('Missing config.php at ' . $configPath);
}

/** @var array $config */
$config = require $configPath;
$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    backfill_fail("config.php must define ['db'] with host, name, user, pass.");
}

$argv = $_SERVER['argv'] ?? [];
$execute = in_array('--execute', $argv, true);
$limit = 0;
foreach ($argv as $arg) {
    if (preg_match('/^--limit=(\d+)$/', (string)$arg, $m)) {
        $limit = max(0, (int)$m[1]);
    }
}

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli(
        (string)$dbCfg['host'],
        (string)$dbCfg['user'],
        (string)$dbCfg['pass'],
        (string)$dbCfg['name'],
        (int)($dbCfg['port'] ?? 3306)
    );
    if (!empty($dbCfg['charset'])) {
        $conn->set_charset((string)$dbCfg['charset']);
    }
} catch (Throwable $e) {
    backfill_fail('Database connection failed: ' . $e->getMessage());
}

if (!table_has_column($conn, 'vp_products', 'vendor') || !table_has_column($conn, 'vp_products', 'vendor_id')) {
    $conn->close();
    backfill_fail('vp_products must have both columns: vendor, vendor_id');
}

$vendorNameCol = null;
if (table_has_column($conn, 'vp_vendors', 'vendor_name')) {
    $vendorNameCol = 'vendor_name';
} elseif (table_has_column($conn, 'vp_vendors', 'vendors_name')) {
    $vendorNameCol = 'vendors_name';
}
if ($vendorNameCol === null || !table_has_column($conn, 'vp_vendors', 'vendor_id')) {
    $conn->close();
    backfill_fail('vp_vendors must have vendor_id and vendor_name/vendors_name');
}

// 1) Build vendor-name -> vendor_id map (normalized key => smallest vendor_id by id ASC)
$vendorSql = "SELECT id, vendor_id, {$vendorNameCol} AS vendor_name FROM vp_vendors ORDER BY id ASC";
$vendorRes = $conn->query($vendorSql);
$vendorMap = [];
while ($row = $vendorRes->fetch_assoc()) {
    $name = trim((string)($row['vendor_name'] ?? ''));
    $vid = trim((string)($row['vendor_id'] ?? ''));
    if ($name === '' || $vid === '' || $vid === '0') {
        continue;
    }
    $key = mb_strtolower($name, 'UTF-8');
    if (!isset($vendorMap[$key])) {
        $vendorMap[$key] = $vid;
    }
}
$vendorRes->free();

if ($vendorMap === []) {
    $conn->close();
    backfill_fail('No usable vendor_name -> vendor_id mappings found in vp_vendors.');
}

// 2) Read candidate products where vendor name exists and vendor_id is empty/zero
$prodSql = "
    SELECT id, item_code, title, vendor, vendor_id
    FROM vp_products
    WHERE TRIM(IFNULL(vendor, '')) <> ''
      AND (vendor_id IS NULL OR vendor_id = '' OR vendor_id = '0')
    ORDER BY id ASC
";
if ($limit > 0) {
    $prodSql .= ' LIMIT ' . (int)$limit;
}
$prodRes = $conn->query($prodSql);

$matched = [];
$unmatched = [];
while ($row = $prodRes->fetch_assoc()) {
    $id = (int)$row['id'];
    $name = trim((string)($row['vendor'] ?? ''));
    $key = mb_strtolower($name, 'UTF-8');
    if (isset($vendorMap[$key])) {
        $matched[] = [
            'id' => $id,
            'item_code' => (string)($row['item_code'] ?? ''),
            'title' => (string)($row['title'] ?? ''),
            'vendor' => $name,
            'new_vendor_id' => (string)$vendorMap[$key],
        ];
    } else {
        $unmatched[] = [
            'id' => $id,
            'item_code' => (string)($row['item_code'] ?? ''),
            'title' => (string)($row['title'] ?? ''),
            'vendor' => $name,
        ];
    }
}
$prodRes->free();

echo "Vendor map entries: " . count($vendorMap) . PHP_EOL;
echo "Products scanned (empty vendor_id + vendor present): " . (count($matched) + count($unmatched)) . PHP_EOL;
echo "Matched: " . count($matched) . PHP_EOL;
echo "Unmatched: " . count($unmatched) . PHP_EOL . PHP_EOL;

echo "Matched preview (up to 50):" . PHP_EOL;
echo json_encode(array_slice($matched, 0, 50), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL . PHP_EOL;

if (count($unmatched) > 0) {
    echo "Unmatched vendor-name preview (up to 50):" . PHP_EOL;
    echo json_encode(array_slice($unmatched, 0, 50), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL . PHP_EOL;
}

if (!$execute) {
    echo "DRY RUN complete. No changes made." . PHP_EOL;
    echo "Run with --execute to update vp_products.vendor_id." . PHP_EOL;
    $conn->close();
    exit(0);
}

if ($matched === []) {
    echo "No matched rows to update." . PHP_EOL;
    $conn->close();
    exit(0);
}

$upd = $conn->prepare('UPDATE vp_products SET vendor_id = ? WHERE id = ?');
$updated = 0;
foreach ($matched as $m) {
    $newVendorId = (string)$m['new_vendor_id'];
    $pid = (int)$m['id'];
    $upd->bind_param('si', $newVendorId, $pid);
    $upd->execute();
    $updated += $upd->affected_rows;
}
$upd->close();

echo "UPDATE complete. Rows affected: {$updated}" . PHP_EOL;
$conn->close();
exit(0);

