<?php
/**
 * Backfill inbound_logs: verify and add stat = 'Published (Live)' for vp_inbound rows
 * that exist in vp_products (local catalog), publish JSON log files, or Exotic India Live API,
 * but have no Published/published log in inbound_logs yet.
 *
 * CLI (from project root):
 *   php scripts/backfill_published_inbound_logs.php
 *   php scripts/backfill_published_inbound_logs.php --execute
 *   php scripts/backfill_published_inbound_logs.php --execute --user-id=1
 *   php scripts/backfill_published_inbound_logs.php --execute --skip-api
 *
 * Web (only if config backfill_logs_web_key is set or session active):
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

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$webKey = (string) ($config['backfill_logs_web_key'] ?? '');
$isWebSessionUser = !empty($_SESSION['user']['id']);

if (!$isCli) {
    if ($webKey === '' && !$isWebSessionUser) {
        http_response_code(403);
        echo "Web access disabled.\n\n";
        echo "Either log in as an authorized user or set 'backfill_logs_web_key' in config.php.\n";
        exit(0);
    }
    if ($webKey !== '') {
        $given = (string) ($_GET['key'] ?? '');
        if (!hash_equals($webKey, $given) && !$isWebSessionUser) {
            http_response_code(403);
            echo "Invalid or missing key.\n";
            exit(0);
        }
    }
}

$argv = $_SERVER['argv'] ?? [];
$execute = false;
$skipApi = false;
$limit = 0;
$fallbackUserId = (int) ($_SESSION['user']['id'] ?? 1);

if ($isCli) {
    $execute = in_array('--execute', $argv, true);
    $skipApi = in_array('--skip-api', $argv, true);
    foreach ($argv as $arg) {
        if (preg_match('/^--user-id=(\d+)$/', $arg, $m)) {
            $fallbackUserId = (int) $m[1];
        }
        if (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
            $limit = (int) $m[1];
        }
    }
} else {
    $execute = isset($_GET['execute']) && $_GET['execute'] !== '' && $_GET['execute'] !== '0';
    $skipApi = isset($_GET['skip_api']) && $_GET['skip_api'] !== '' && $_GET['skip_api'] !== '0';
    if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
        $fallbackUserId = (int) $_GET['user_id'];
    }
    if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
        $limit = (int) $_GET['limit'];
    }
}

require_once $root . '/models/inbounding/Inbounding.php';
global $conn, $inboundingModel;

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

$inboundingModel = new Inbounding();

echo "=====================================================\n";
echo "INBOUND PUBLISHED STATUS VERIFICATION & BACKFILL TOOL\n";
echo "=====================================================\n";
echo "Mode: " . ($execute ? "EXECUTE (writing to inbound_logs)" : "DRY RUN (no database writes)") . "\n";
echo "Fallback User ID: {$fallbackUserId}\n";
echo "Check Live Exotic API: " . ($skipApi ? "NO (--skip-api set)" : "YES") . "\n";
if ($limit > 0) {
    echo "Limit: {$limit}\n";
}
echo "-----------------------------------------------------\n\n";

$result = $inboundingModel->verifyAndBackfillPublishedInboundLogs(
    $execute,
    $fallbackUserId,
    !$skipApi,
    $limit
);

if (empty($result['success'])) {
    $conn->close();
    backfill_fail("Backfill failed: " . ($result['error'] ?? 'Unknown error'));
}

echo "Total Un-logged Candidates Found: " . ($result['total_candidates'] ?? 0) . "\n";
echo "  - Verified via Local Catalog (vp_products): " . ($result['verified_local_db'] ?? 0) . "\n";
echo "  - Verified via Publish JSON Logs:           " . ($result['verified_json_logs'] ?? 0) . "\n";
echo "  - Verified via Exotic Live Website API:     " . ($result['verified_live_api'] ?? 0) . "\n";
echo "-----------------------------------------------------\n";
echo "Total Verified Products: " . ($result['total_verified'] ?? 0) . "\n";

if ($execute) {
    echo "Inserted / Backfilled Rows in inbound_logs: " . ($result['backfilled_count'] ?? 0) . "\n";
} else {
    echo "\nDRY RUN complete. Re-run with --execute (or &execute=1) to insert log records.\n";
}

$conn->close();
