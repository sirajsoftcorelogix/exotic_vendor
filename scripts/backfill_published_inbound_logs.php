<?php
/**
 * Backfill inbound_logs: add stat = 'Published' for vp_inbound rows that appear in
 * vp_products (live catalog) but have no Published/published log yet.
 *
 * CLI (from project root):
 *   php scripts/backfill_published_inbound_logs.php
 *   php scripts/backfill_published_inbound_logs.php --execute
 *   php scripts/backfill_published_inbound_logs.php --execute --user-id=1
 *
 * Web (only if config backfill_logs_web_key is set):
 *   /scripts/backfill_published_inbound_logs.php?key=YOUR_KEY
 *   /scripts/backfill_published_inbound_logs.php?key=YOUR_KEY&execute=1&user_id=1
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
        fwrite(STDERR, $msg . "\n");
    } else {
        http_response_code($code >= 400 && $code < 600 ? $code : 500);
        echo $msg . "\n";
    }
    exit(1);
}

if (!is_file($configPath)) {
    backfill_fail('Missing config.php at ' . $configPath);
}

/** @var array $config */
$config = require $configPath;

$webKey = (string) ($config['backfill_logs_web_key'] ?? '');
if (!$isCli) {
    if ($webKey === '') {
        http_response_code(403);
        echo "Web access is disabled.\n\n";
        echo "Either:\n";
        echo "  1. Set 'backfill_logs_web_key' in config.php to a long random secret, then open this URL with ?key=...\n";
        echo "  2. Run from SSH: php scripts/backfill_published_inbound_logs.php --execute\n";
        exit(0);
    }
    $given = (string) ($_GET['key'] ?? '');
    if (!hash_equals($webKey, $given)) {
        http_response_code(403);
        echo "Invalid or missing key.\n";
        exit(0);
    }
}

$argv = $_SERVER['argv'] ?? [];
$execute = false;
$fallbackUserId = 0;

if ($isCli) {
    $execute = in_array('--execute', $argv, true);
    foreach ($argv as $arg) {
        if (preg_match('/^--user-id=(\d+)$/', $arg, $m)) {
            $fallbackUserId = (int) $m[1];
        }
    }
} else {
    $execute = isset($_GET['execute']) && $_GET['execute'] !== '' && $_GET['execute'] !== '0';
    if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
        $fallbackUserId = (int) $_GET['user_id'];
    }
}

$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    backfill_fail("config.php must define ['db'] with host, name, user, pass.");
}

try {
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
} catch (Throwable $e) {
    backfill_fail('Database connection failed: ' . $e->getMessage());
}

$fallbackSql = (int) $fallbackUserId;
$sql = "
SELECT DISTINCT vi.id AS i_id,
       COALESCE(NULLIF(vi.updated_by_user_id, 0), {$fallbackSql}) AS userid_log
FROM vp_inbound vi
INNER JOIN vp_products p
  ON TRIM(p.item_code) COLLATE utf8mb4_unicode_ci = TRIM(vi.Item_code) COLLATE utf8mb4_unicode_ci
WHERE TRIM(COALESCE(vi.Item_code, '')) <> ''
  AND NOT EXISTS (
    SELECT 1 FROM inbound_logs il
    WHERE il.i_id = vi.id
      AND LOWER(TRIM(il.stat)) = 'published'
  )
ORDER BY vi.id
";

try {
    $result = $conn->query($sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} catch (Throwable $e) {
    $conn->close();
    backfill_fail('Query failed: ' . $e->getMessage());
}

$n = count($rows);

if (!$execute) {
    echo "DRY RUN — no database writes.";
    if ($isCli) {
        echo " Add --execute to insert.\n";
    } else {
        echo " Add &execute=1 to the URL to insert.\n";
    }
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

try {
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
} catch (Throwable $e) {
    $conn->close();
    backfill_fail('Insert failed: ' . $e->getMessage());
}

$conn->close();

echo "Done. Inserted {$inserted} row(s). stat=Published\n";
