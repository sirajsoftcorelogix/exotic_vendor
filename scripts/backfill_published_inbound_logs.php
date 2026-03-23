<?php
/**
 * Backfill inbound_logs: add stat = 'Published' for vp_inbound rows that appear in
 * vp_products (live catalog) but have no Published/published log yet.
 *
 * Run from project root:
 *   php scripts/backfill_published_inbound_logs.php
 *   php scripts/backfill_published_inbound_logs.php --execute
 *
 * Options:
 *   --execute              Perform INSERTs (default is dry-run only).
 *   --user-id=N            userid_log when inbound.updated_by_user_id is 0/null (default 0).
 *                          Use a real vp_users.id if your DB forbids 0.
 */
declare(strict_types=1);

$argv = $_SERVER['argv'] ?? [];

$execute = in_array('--execute', $argv, true);
$fallbackUserId = 0;
foreach ($argv as $arg) {
    if (preg_match('/^--user-id=(\d+)$/', $arg, $m)) {
        $fallbackUserId = (int) $m[1];
    }
}

$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "Missing config.php at {$configPath}\n");
    exit(1);
}

/** @var array $config */
$config = require $configPath;
$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    fwrite(STDERR, "config.php must define ['db'] with host, name, user, pass.\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli(
    (string) $dbCfg['host'],
    (string) $dbCfg['user'],
    (string) $dbCfg['pass'],
    (string) $dbCfg['name'],
    (int) ($dbCfg['port'] ?? 3306)
);
if (!empty($dbCfg['charset'])) {
    $conn->set_charset((string) $dbCfg['charset']);
}

// Match catalog products to inbound by item code; skip if any Published log already exists.
$fallbackSql = (int) $fallbackUserId;
$sql = "
SELECT DISTINCT vi.id AS i_id,
       COALESCE(NULLIF(vi.updated_by_user_id, 0), {$fallbackSql}) AS userid_log
FROM vp_inbound vi
INNER JOIN vp_products p ON TRIM(p.item_code) = TRIM(vi.Item_code)
WHERE TRIM(COALESCE(vi.Item_code, '')) <> ''
  AND NOT EXISTS (
    SELECT 1 FROM inbound_logs il
    WHERE il.i_id = vi.id
      AND LOWER(TRIM(il.stat)) = 'published'
  )
ORDER BY vi.id
";

$result = $conn->query($sql);
$rows = $result->fetch_all(MYSQLI_ASSOC);
$result->free();

$n = count($rows);

if (!$execute) {
    echo "DRY RUN — no database writes. Add --execute to insert.\n";
} else {
    echo "EXECUTE — inserting into inbound_logs …\n";
}

echo "Candidate rows (missing Published log, item_code in vp_products): {$n}\n";

if ($n === 0) {
    $conn->close();
    exit(0);
}

if (!$execute) {
    $preview = array_slice($rows, 0, 30);
    echo "Sample (up to 30) i_id → userid_log:\n";
    foreach ($preview as $r) {
        echo '  ' . (int) $r['i_id'] . ' → ' . (int) $r['userid_log'] . "\n";
    }
    $conn->close();
    exit(0);
}

$stmt = $conn->prepare(
    'INSERT INTO inbound_logs (`i_id`, `stat`, `userid_log`, `created_at`, `modified_at`) VALUES (?, ?, ?, NOW(), NULL)'
);
$stat = 'Published';
$inserted = 0;
foreach ($rows as $r) {
    $iId = (int) $r['i_id'];
    $uid = (int) $r['userid_log'];
    $stmt->bind_param('isi', $iId, $stat, $uid);
    $stmt->execute();
    $inserted += $stmt->affected_rows;
}
$stmt->close();
$conn->close();

echo "Done. Inserted {$inserted} row(s). stat=Published\n";
