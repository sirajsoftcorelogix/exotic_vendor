<?php

/**
 * Step-by-step timing logs for inbound module requests (502 / overload diagnosis).
 *
 * Logs go to logs/inbound_profiler/ (or EXOTIC_INBOUND_PROFILER_DIR outside web root).
 * Prunes oldest JSON files when count exceeds INBOUND_PROFILER_MAX_FILES.
 */

if (!defined('INBOUND_PROFILER_ENABLED')) {
    $env = getenv('EXOTIC_INBOUND_PROFILER');
    define('INBOUND_PROFILER_ENABLED', $env === false || $env === '' || $env === '1' || strtolower((string) $env) === 'true');
}

if (!defined('INBOUND_PROFILER_DIR')) {
    $envDir = getenv('EXOTIC_INBOUND_PROFILER_DIR');
    if ($envDir !== false && trim((string) $envDir) !== '') {
        define('INBOUND_PROFILER_DIR', rtrim((string) $envDir, DIRECTORY_SEPARATOR));
    } else {
        define('INBOUND_PROFILER_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'inbound_profiler');
    }
}

if (!defined('INBOUND_PROFILER_MAX_FILES')) {
    define('INBOUND_PROFILER_MAX_FILES', 200);
}

function inbound_profiler_ensure_dir(string $dir): bool
{
    if (is_dir($dir)) {
        return true;
    }

    return @mkdir($dir, 0755, true);
}

function inbound_profiler_prune(string $dir, int $maxFiles = INBOUND_PROFILER_MAX_FILES): void
{
    if ($maxFiles < 1 || !is_dir($dir)) {
        return;
    }
    $pattern = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'inbound_*.json';
    $files = glob($pattern) ?: [];
    if (count($files) <= $maxFiles) {
        return;
    }
    usort($files, static function ($a, $b) {
        return (filemtime($a) ?: 0) <=> (filemtime($b) ?: 0);
    });
    foreach (array_slice($files, 0, count($files) - $maxFiles) as $file) {
        @unlink($file);
    }
}

/**
 * @param array<string, mixed> $meta
 */
function inbound_profiler_start(string $action, array $meta = []): string
{
    $runId = bin2hex(random_bytes(8));
    if (!isset($GLOBALS['_inbound_profiler_runs']) || !is_array($GLOBALS['_inbound_profiler_runs'])) {
        $GLOBALS['_inbound_profiler_runs'] = [];
    }
    $GLOBALS['_inbound_profiler_runs'][$runId] = [
        'action' => $action,
        'started_at' => microtime(true),
        'last_mark' => microtime(true),
        'meta' => $meta,
        'steps' => [],
        'memory_peak_mb' => 0,
    ];

    return $runId;
}

/**
 * @param array<string, mixed> $extra
 */
function inbound_profiler_step(string $runId, string $label, array $extra = []): void
{
    if ($runId === '' || empty($GLOBALS['_inbound_profiler_runs'][$runId])) {
        return;
    }
    $run = &$GLOBALS['_inbound_profiler_runs'][$runId];
    $now = microtime(true);
    $run['steps'][] = [
        'step' => $label,
        'ms_since_start' => (int) round(($now - $run['started_at']) * 1000),
        'ms_since_prev' => (int) round(($now - $run['last_mark']) * 1000),
        'extra' => $extra,
    ];
    $run['last_mark'] = $now;
    $run['memory_peak_mb'] = round(memory_get_peak_usage(true) / 1048576, 2);
}

/**
 * @param array<string, mixed> $extra
 */
function inbound_profiler_finish(string $runId, string $status = 'ok', array $extra = []): void
{
    if (!INBOUND_PROFILER_ENABLED || $runId === '' || empty($GLOBALS['_inbound_profiler_runs'][$runId])) {
        unset($GLOBALS['_inbound_profiler_runs'][$runId]);
        return;
    }

    $run = $GLOBALS['_inbound_profiler_runs'][$runId];
    unset($GLOBALS['_inbound_profiler_runs'][$runId]);

    $totalMs = (int) round((microtime(true) - $run['started_at']) * 1000);
    $action = (string) ($run['action'] ?? 'unknown');

    $alwaysLog = in_array($action, [
        'inbound_product_publish',
        'updatedesktopform',
        'syncVendorReferenceCache',
        'download_photos',
    ], true);
    $slowThresholdMs = ($action === 'list') ? 3000 : 1500;
    if (!$alwaysLog && $totalMs < $slowThresholdMs) {
        return;
    }

    $dir = INBOUND_PROFILER_DIR;
    if (!inbound_profiler_ensure_dir($dir)) {
        return;
    }

    $payload = [
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => $status,
        'action' => $action,
        'total_ms' => $totalMs,
        'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
        'user_id' => $_SESSION['user']['id'] ?? null,
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        'meta' => $run['meta'] ?? [],
        'steps' => $run['steps'] ?? [],
        'extra' => $extra,
    ];

    $slug = preg_replace('/[^a-z0-9_]+/i', '_', $action) ?: 'inbound';
    $file = $dir . DIRECTORY_SEPARATOR . sprintf(
        'inbound_%s_%s_%s.json',
        date('Ymd_His'),
        substr($runId, 0, 8),
        substr($slug, 0, 40)
    );

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json !== false) {
        @file_put_contents($file, $json . "\n", LOCK_EX);
        inbound_profiler_prune($dir);
    }
}
