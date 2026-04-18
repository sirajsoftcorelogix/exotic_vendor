<?php
/**
 * Import vendors from catalog API: GET .../vendor-api/products/vendorlist?groupname=...
 * Rows are upserted into vp_vendors with user_id = 1000 (vendor::VENDORLIST_IMPORT_USER_ID).
 *
 * CLI (from project root):
 *   php scripts/import_vendors_from_vendorlist_api.php --groupname="YourGroup"
 *   php scripts/import_vendors_from_vendorlist_api.php --groupname="YourGroup" --execute
 *   php scripts/import_vendors_from_vendorlist_api.php --groupname="YourGroup" --execute --base-url=https://www.exoticindia.com
 */
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
}

$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

function fail_import(string $msg, int $code = 1): void
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
    fail_import('Missing config.php at ' . $configPath);
}

/** @var array $config */
$config = require $configPath;

$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    fail_import("config.php must define ['db'] with host, name, user, pass.");
}

$argv = $_SERVER['argv'] ?? [];
$groupname = '';
$execute = false;
$baseUrl = 'https://www.exoticindia.com';

foreach ($argv as $arg) {
    if (preg_match('/^--groupname=(.+)$/', $arg, $m)) {
        $groupname = trim($m[1], " \t\n\r\0\x0B\"'");
    } elseif (preg_match('/^--base-url=(.+)$/', $arg, $m)) {
        $baseUrl = rtrim(trim($m[1], " \t\n\r\0\x0B\"'"), '/');
    } elseif ($arg === '--execute') {
        $execute = true;
    }
}

if ($groupname === '') {
    fail_import(
        "Usage: php scripts/import_vendors_from_vendorlist_api.php --groupname=\"GroupName\" [--execute] [--base-url=https://www.exoticindia.com]\n" .
        "Without --execute: dry run only (fetches API, reports row count; no DB writes).\n" .
        "With --execute: upserts vp_vendors with user_id = 1000 (vendor::VENDORLIST_IMPORT_USER_ID)."
    );
}

require_once $root . '/models/vendor/vendor.php';

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
    fail_import('Database connection failed: ' . $e->getMessage());
}

$model = new Vendor($conn);
$dryRun = !$execute;

$result = $model->importVendorlistForGroup($groupname, $dryRun, $baseUrl);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

if (!$result['success']) {
    $conn->close();
    exit(1);
}

$conn->close();
exit(0);
