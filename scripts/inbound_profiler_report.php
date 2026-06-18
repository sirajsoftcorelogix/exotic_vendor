<?php
/**
 * CLI: summarize recent inbound profiler logs (slowest runs and steps).
 *
 * Usage: php scripts/inbound_profiler_report.php [limit]
 */

$limit = isset($argv[1]) ? max(1, (int) $argv[1]) : 30;
$dir = getenv('EXOTIC_INBOUND_PROFILER_DIR');
if ($dir === false || trim((string) $dir) === '') {
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'inbound_profiler';
}

if (!is_dir($dir)) {
    fwrite(STDERR, "No profiler directory: {$dir}\n");
    exit(1);
}

$files = glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'inbound_*.json') ?: [];
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
        'file' => basename($file),
        'action' => $data['action'] ?? '?',
        'total_ms' => (int) ($data['total_ms'] ?? 0),
        'memory_mb' => $data['memory_peak_mb'] ?? '?',
        'status' => $data['status'] ?? '?',
        'meta' => $data['meta'] ?? [],
        'steps' => $data['steps'] ?? [],
    ];
}

usort($rows, static fn($a, $b) => $b['total_ms'] <=> $a['total_ms']);

echo "Inbound profiler — top " . count($rows) . " runs by duration\n";
echo str_repeat('-', 72) . "\n";

foreach ($rows as $i => $row) {
    $meta = $row['meta'];
    $metaStr = '';
    if (!empty($meta['inbound_id'])) {
        $metaStr .= ' id=' . $meta['inbound_id'];
    }
    if (!empty($meta['item_code'])) {
        $metaStr .= ' sku=' . $meta['item_code'];
    }
    printf(
        "%2d. %-28s %6d ms  mem=%s  %s%s\n",
        $i + 1,
        $row['action'],
        $row['total_ms'],
        $row['memory_mb'],
        $row['status'],
        $metaStr
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

echo str_repeat('-', 72) . "\n";
echo "Log dir: {$dir}\n";
