<?php
/**
 * One-off analysis of exported vp_inbound_profiler_logs JSON.
 * Usage: php scripts/analyze_profiler_json.php path/to/export.json
 */

$jsonPath = $argv[1] ?? '';
if ($jsonPath === '' || !is_file($jsonPath)) {
    fwrite(STDERR, "Usage: php scripts/analyze_profiler_json.php <json-file>\n");
    exit(1);
}

$raw = file_get_contents($jsonPath);
$decoded = json_decode($raw, true);
if (!is_array($decoded)) {
    fwrite(STDERR, 'JSON parse error: ' . json_last_error_msg() . "\n");
    exit(1);
}

$rows = [];
foreach ($decoded as $entry) {
    if (!is_array($entry)) {
        continue;
    }
    if (isset($entry['type']) && isset($entry['data']) && is_array($entry['data'])) {
        foreach ($entry['data'] as $dbRow) {
            if (is_array($dbRow)) {
                $rows[] = $dbRow;
            }
        }
        continue;
    }
    if (isset($entry['action']) || isset($entry['payload_json'])) {
        $rows[] = $entry;
    }
}
if ($rows === [] && isset($decoded[0])) {
    $rows = $decoded;
} elseif ($rows === [] && isset($decoded['data'])) {
    $rows = is_array($decoded['data']) ? $decoded['data'] : [];
}

$data = $rows;

echo '=== Profiler export analysis ===' . "\n";
echo 'Rows: ' . count($rows) . "\n\n";

$byAction = [];
$stepAgg = [];
$statusCounts = [];
$slowRuns = [];

foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }

    // DB row may nest payload in steps_json / meta_json
    $action = $row['action'] ?? $row['request_action'] ?? '?';
    $totalMs = (int) ($row['total_ms'] ?? 0);
    $status = (string) ($row['status'] ?? 'unknown');
    $steps = $row['steps'] ?? null;
    $meta = [];

    if (isset($row['payload_json']) && is_string($row['payload_json'])) {
        $payload = json_decode($row['payload_json'], true);
        if (is_array($payload)) {
            $totalMs = (int) ($payload['total_ms'] ?? $totalMs);
            $action = (string) ($payload['action'] ?? $action);
            $status = (string) ($payload['status'] ?? $status);
            $steps = is_array($payload['steps'] ?? null) ? $payload['steps'] : $steps;
            $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        }
    }

    $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

    if (!isset($byAction[$action])) {
        $byAction[$action] = ['count' => 0, 'total_ms_sum' => 0, 'max_ms' => 0, 'over_5s' => 0, 'over_30s' => 0, 'over_60s' => 0];
    }
    $byAction[$action]['count']++;
    $byAction[$action]['total_ms_sum'] += $totalMs;
    $byAction[$action]['max_ms'] = max($byAction[$action]['max_ms'], $totalMs);
    if ($totalMs >= 5000) {
        $byAction[$action]['over_5s']++;
    }
    if ($totalMs >= 30000) {
        $byAction[$action]['over_30s']++;
    }
    if ($totalMs >= 60000) {
        $byAction[$action]['over_60s']++;
    }

    $slowRuns[] = ['action' => $action, 'total_ms' => $totalMs, 'status' => $status, 'steps' => $steps, 'meta' => $row['meta'] ?? []];

    foreach ($steps as $step) {
        if (!is_array($step)) {
            continue;
        }
        $name = (string) ($step['step'] ?? '?');
        $prev = (int) ($step['ms_since_prev'] ?? 0);
        if (!isset($stepAgg[$name])) {
            $stepAgg[$name] = ['count' => 0, 'sum_ms' => 0, 'max_ms' => 0, 'over_1s' => 0, 'over_5s' => 0];
        }
        $stepAgg[$name]['count']++;
        $stepAgg[$name]['sum_ms'] += $prev;
        $stepAgg[$name]['max_ms'] = max($stepAgg[$name]['max_ms'], $prev);
        if ($prev >= 1000) {
            $stepAgg[$name]['over_1s']++;
        }
        if ($prev >= 5000) {
            $stepAgg[$name]['over_5s']++;
        }
    }
}

echo "--- By action (avg / max ms, slow counts) ---\n";
uasort($byAction, static fn($a, $b) => ($b['total_ms_sum'] / max(1, $b['count'])) <=> ($a['total_ms_sum'] / max(1, $a['count'])));
foreach ($byAction as $action => $stats) {
    $avg = round($stats['total_ms_sum'] / max(1, $stats['count']));
    printf(
        "%-32s n=%4d  avg=%6d ms  max=%6d ms  >=5s=%d  >=30s=%d  >=60s=%d\n",
        $action,
        $stats['count'],
        $avg,
        $stats['max_ms'],
        $stats['over_5s'],
        $stats['over_30s'],
        $stats['over_60s']
    );
}

echo "\n--- Status counts ---\n";
foreach ($statusCounts as $st => $n) {
    echo "  {$st}: {$n}\n";
}

echo "\n--- Slowest steps (by max single-step ms_since_prev) ---\n";
uasort($stepAgg, static fn($a, $b) => $b['max_ms'] <=> $a['max_ms']);
$i = 0;
foreach ($stepAgg as $name => $s) {
    if ($i++ >= 25) {
        break;
    }
    $avg = round($s['sum_ms'] / max(1, $s['count']));
    printf(
        "%-42s n=%4d avg_step=%5d ms max_step=%6d ms  >=1s=%d  >=5s=%d\n",
        $name,
        $s['count'],
        $avg,
        $s['max_ms'],
        $s['over_1s'],
        $s['over_5s']
    );
}

echo "\n--- Top 15 slowest runs ---\n";
usort($slowRuns, static fn($a, $b) => $b['total_ms'] <=> $a['total_ms']);
foreach (array_slice($slowRuns, 0, 15) as $i => $run) {
    $meta = is_array($run['meta']) ? $run['meta'] : [];
    $metaStr = '';
    if (!empty($meta['inbound_id'])) {
        $metaStr .= ' id=' . $meta['inbound_id'];
    }
    if (!empty($meta['item_code'])) {
        $metaStr .= ' sku=' . $meta['item_code'];
    }
    printf("%2d. %-28s %6d ms  %s%s\n", $i + 1, $run['action'], $run['total_ms'], $run['status'], $metaStr);
    foreach ($run['steps'] as $step) {
        if (!is_array($step)) {
            continue;
        }
        $prev = (int) ($step['ms_since_prev'] ?? 0);
        if ($prev >= 500) {
            printf("      + %-40s +%d ms\n", $step['step'] ?? '?', $prev);
        }
    }
}
