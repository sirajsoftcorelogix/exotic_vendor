<?php
/**
 * Sync colormaps + optionals from Exotic vendor API into vendor_reference_cache.
 *
 * CLI (from project root):
 *   php scripts/sync_vendor_reference_cache.php
 *   php scripts/sync_vendor_reference_cache.php --key=colormaps
 *   php scripts/sync_vendor_reference_cache.php --key=optionals
 */
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
}

$root = dirname(__DIR__);

require_once $root . '/bootstrap/init/init.php';
require_once $root . '/models/vendor/VendorReferenceCache.php';

function fail_sync(string $msg, int $code = 1): void
{
    global $isCli;
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
    } else {
        http_response_code($code >= 400 && $code < 600 ? $code : 500);
        echo json_encode(['ok' => false, 'message' => $msg], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit(1);
}

$keyFilter = '';
$argv = $_SERVER['argv'] ?? [];
foreach ($argv as $arg) {
    if (preg_match('/^--key=(.+)$/', $arg, $m)) {
        $keyFilter = trim($m[1], " \t\n\r\0\x0B\"'");
    }
}

$cache = new VendorReferenceCache($conn);

if ($keyFilter !== '') {
    if (!in_array($keyFilter, [VendorReferenceCache::KEY_COLORMAPS, VendorReferenceCache::KEY_OPTIONALS], true)) {
        fail_sync('Invalid --key. Use colormaps or optionals.');
    }
    $results = $cache->syncKeys([$keyFilter]);
} else {
    $results = $cache->syncAll();
}

$output = [
    'ok' => true,
    'results' => $results,
    'meta' => $cache->getSyncMeta(),
];

if ($isCli) {
    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $allOk = !in_array(false, array_column($results, 'ok'), true);
    exit($allOk ? 0 : 2);
}

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
