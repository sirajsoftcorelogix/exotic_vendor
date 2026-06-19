<?php
/**
 * CLI: summarize recent inbound profiler logs (slowest runs and steps).
 *
 * Usage:
 *   php scripts/inbound_profiler_report.php [limit]
 *   php scripts/inbound_profiler_report.php --diagnose
 */

$root = dirname(__DIR__);
require_once $root . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'inbound_profiler.php';

$dbBootstrap = $root . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.php';
if (is_file($dbBootstrap) && !isset($GLOBALS['conn'])) {
    require_once $dbBootstrap;
    if (class_exists('Database')) {
        $GLOBALS['conn'] = Database::getConnection();
    }
}

$diagnose = in_array('--diagnose', $argv ?? [], true);
$limit = 30;
foreach (array_slice($argv ?? [], 1) as $arg) {
    if ($arg === '--diagnose') {
        continue;
    }
    if (is_numeric($arg)) {
        $limit = max(1, (int) $arg);
    }
}

/**
 * @return list<string>
 */
function inbound_profiler_report_dirs(): array
{
    $dirs = inbound_profiler_log_dirs();
    $seen = [];
    $out = [];
    foreach ($dirs as $dir) {
        $real = realpath($dir);
        $key = $real !== false ? $real : $dir;
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $out[] = $dir;
    }
    return $out;
}

/**
 * @param list<string> $dirs
 * @return list<array<string, mixed>>
 */
function inbound_profiler_collect_file_rows(array $dirs, int $limit): array
{
    $files = [];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        $matches = glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'inbound_*.json') ?: [];
        foreach ($matches as $file) {
            $files[$file] = $file;
        }
    }

    $files = array_values($files);
    usort($files, static fn($a, $b) => (filemtime($b) ?: 0) <=> (filemtime($a) ?: 0));
    $files = array_slice($files, 0, $limit);

    $rows = [];
    foreach ($files as $file) {
        $raw = @file_get_contents($file);
        if ($raw === false) {
            continue;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            continue;
        }
        $rows[] = [
            'source' => 'file',
            'file' => basename($file),
            'action' => $data['action'] ?? '?',
            'total_ms' => (int) ($data['total_ms'] ?? 0),
            'memory_mb' => $data['memory_peak_mb'] ?? '?',
            'status' => $data['status'] ?? '?',
            'meta' => is_array($data['meta'] ?? null) ? $data['meta'] : [],
            'steps' => is_array($data['steps'] ?? null) ? $data['steps'] : [],
        ];
    }

    return $rows;
}

/**
 * @param list<array<string, mixed>> $rows
 * @return list<array<string, mixed>>
 */
function inbound_profiler_merge_rows(array $rows, int $limit): array
{
    usort($rows, static fn($a, $b) => ($b['total_ms'] ?? 0) <=> ($a['total_ms'] ?? 0));
    return array_slice($rows, 0, $limit);
}

function inbound_profiler_print_diagnostics(array $dirs): void
{
    $db = inbound_profiler_db_diagnostics();

    echo "Inbound profiler diagnostics\n";
    echo str_repeat('-', 72) . "\n";
    echo 'Enabled: ' . (INBOUND_PROFILER_ENABLED ? 'yes' : 'no') . "\n";
    echo 'Storage mode: ' . INBOUND_PROFILER_STORAGE . "\n";
    echo 'EXOTIC_INBOUND_PROFILER env: ' . (getenv('EXOTIC_INBOUND_PROFILER') !== false ? var_export(getenv('EXOTIC_INBOUND_PROFILER'), true) : '(not set)') . "\n";
    echo 'EXOTIC_INBOUND_PROFILER_DIR env: ' . (getenv('EXOTIC_INBOUND_PROFILER_DIR') !== false ? getenv('EXOTIC_INBOUND_PROFILER_DIR') : '(not set)') . "\n";
    echo 'EXOTIC_INBOUND_PROFILER_STORAGE env: ' . (getenv('EXOTIC_INBOUND_PROFILER_STORAGE') !== false ? getenv('EXOTIC_INBOUND_PROFILER_STORAGE') : '(not set, default both)') . "\n";
    echo 'PHP SAPI: ' . PHP_SAPI . "\n";
    echo 'Project root: ' . dirname(__DIR__) . "\n";
    echo str_repeat('-', 72) . "\n";

    echo "Database:\n";
    echo '  available: ' . (!empty($db['available']) ? 'yes' : 'no') . "\n";
    echo '  table: ' . ($db['table'] ?? INBOUND_PROFILER_DB_TABLE) . "\n";
    echo '  table ready: ' . (!empty($db['table_ready']) ? 'yes' : 'no') . "\n";
    echo '  rows: ' . (int) ($db['row_count'] ?? 0) . "\n";
    if (!empty($db['error'])) {
        echo '  error: ' . $db['error'] . "\n";
    }
    echo "\n";

    foreach ($dirs as $dir) {
        $exists = is_dir($dir);
        $writable = $exists && is_writable($dir);
        $jsonCount = 0;
        $errorFile = $dir . DIRECTORY_SEPARATOR . '_profiler_write_error.json';
        if ($exists) {
            $jsonCount = count(glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'inbound_*.json') ?: []);
        }

        echo "Dir: {$dir}\n";
        echo '  exists: ' . ($exists ? 'yes' : 'no') . "\n";
        echo '  writable: ' . ($writable ? 'yes' : 'no') . "\n";
        echo "  inbound_*.json files: {$jsonCount}\n";

        if (is_file($errorFile)) {
            echo "  last write error:\n";
            $raw = trim((string) @file_get_contents($errorFile));
            foreach (explode("\n", $raw) as $line) {
                echo '    ' . $line . "\n";
            }
        }
        echo "\n";
    }

    echo "Notes:\n";
    echo "  - Default storage is both: database table + JSON files when writable.\n";
    echo "  - Set EXOTIC_INBOUND_PROFILER_STORAGE=db to use database only.\n";
    echo "  - Logs are written for desktop form save, publish, preview JSON, and slow list loads.\n";
    echo "  - Opening the desktop form alone does not create a profiler entry.\n";
    echo str_repeat('-', 72) . "\n";
}

$dirs = inbound_profiler_report_dirs();
$fileRows = inbound_profiler_collect_file_rows($dirs, $limit);
$dbRows = inbound_profiler_fetch_db_rows($limit);
$rows = inbound_profiler_merge_rows(array_merge($fileRows, $dbRows), $limit);

if ($diagnose) {
    inbound_profiler_print_diagnostics($dirs);
    exit(0);
}

echo 'Inbound profiler — top ' . count($rows) . " runs by duration\n";
echo str_repeat('-', 72) . "\n";

if ($rows === []) {
    echo "No profiler runs found.\n\n";
    inbound_profiler_print_diagnostics($dirs);
    exit(0);
}

foreach ($rows as $i => $row) {
    $meta = $row['meta'];
    $metaStr = '';
    if (!empty($meta['inbound_id'])) {
        $metaStr .= ' id=' . $meta['inbound_id'];
    }
    if (!empty($meta['item_code'])) {
        $metaStr .= ' sku=' . $meta['item_code'];
    }
    $source = $row['source'] ?? 'file';
    printf(
        "%2d. %-28s %6d ms  mem=%s  %s%s  [%s]\n",
        $i + 1,
        $row['action'],
        $row['total_ms'],
        $row['memory_mb'],
        $row['status'],
        $metaStr,
        $source
    );
    foreach ($row['steps'] as $step) {
        if ((int) ($step['ms_since_prev'] ?? 0) >= 200) {
            printf(
                "      + %-40s %5d ms (+%d ms)\n",
                $step['step'] ?? '?',
                (int) ($step['ms_since_start'] ?? 0),
                (int) ($step['ms_since_prev'] ?? 0)
            );
        }
    }
}

$dbInfo = inbound_profiler_db_diagnostics();
echo str_repeat('-', 72) . "\n";
echo 'Storage mode: ' . INBOUND_PROFILER_STORAGE . "\n";
echo 'Database rows: ' . (int) ($dbInfo['row_count'] ?? 0) . ' in ' . INBOUND_PROFILER_DB_TABLE . "\n";
echo 'Scanned dirs:' . "\n";
foreach ($dirs as $dir) {
    $count = count(glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'inbound_*.json') ?: []);
    echo "  {$dir} ({$count} files)\n";
}
echo "Tip: run with --diagnose for env/permission details.\n";
