<?php
/**
 * Find duplicate vp_vendors rows (same vendor_name after trim + case-insensitive match).
 *
 * Writes only to vp_vendors: --execute deletes duplicate rows and keeps the lowest id per name.
 *
 * Short-code clusters (e.g. AD1, AD4): report only via --prefix-report — not merged unless names match.
 *
 * Default: DRY RUN (report only).
 *
 *   php scripts/dedupe_vp_vendors_by_name.php
 *   php scripts/dedupe_vp_vendors_by_name.php --prefix-report
 *   php scripts/dedupe_vp_vendors_by_name.php --execute
 */
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
}

$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

function dedupe_fail(string $msg, int $code = 1): void
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
    dedupe_fail('Missing config.php at ' . $configPath);
}

/** @var array $config */
$config = require $configPath;

$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    dedupe_fail("config.php must define ['db'] with host, name, user, pass.");
}

$argv = $_SERVER['argv'] ?? [];
$execute = in_array('--execute', $argv, true);
$prefixReport = in_array('--prefix-report', $argv, true);

if (in_array('--merge', $argv, true) || in_array('--table-only', $argv, true)) {
    dedupe_fail('Obsolete flags: this script only DELETEs from vp_vendors. Use: php scripts/dedupe_vp_vendors_by_name.php --execute');
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
    dedupe_fail('Database connection failed: ' . $e->getMessage());
}

$res = $conn->query(
    'SELECT id, vendor_name FROM vp_vendors ORDER BY id ASC'
);
$byKey = [];
while ($row = $res->fetch_assoc()) {
    $name = (string) ($row['vendor_name'] ?? '');
    $key = mb_strtolower(trim($name), 'UTF-8');
    if (!isset($byKey[$key])) {
        $byKey[$key] = [];
    }
    $byKey[$key][] = [
        'id' => (int) $row['id'],
        'vendor_name' => $name,
    ];
}
$res->free();

$dups = [];
foreach ($byKey as $key => $list) {
    if (count($list) < 2) {
        continue;
    }
    if ($key === '') {
        $dups[] = ['key' => $key, 'rows' => $list, 'note' => 'empty vendor_name'];
        continue;
    }
    $dups[] = ['key' => $key, 'rows' => $list];
}

echo 'Total vp_vendors rows scanned: ' . array_sum(array_map('count', $byKey)) . "\n";
echo 'Duplicate name groups (case-insensitive, trimmed): ' . count($dups) . "\n\n";

foreach ($dups as $g) {
    $ids = array_column($g['rows'], 'id');
    echo 'KEY "' . ($g['key'] === '' ? '(empty)' : $g['key']) . "\" — ids: "
        . implode(', ', $ids)
        . (isset($g['note']) ? ' [' . $g['note'] . ']' : '')
        . "\n";
}

if ($prefixReport) {
    echo "\n--- Prefix clusters (letters+digits only, same letter prefix, e.g. AD1 vs AD4) ---\n";
    $pfxMap = [];
    foreach ($byKey as $key => $list) {
        foreach ($list as $entry) {
            $raw = trim((string) ($entry['vendor_name'] ?? ''));
            if ($raw === '') {
                continue;
            }
            if (!preg_match('/^([A-Za-z]+)(\d+)$/', $raw, $m)) {
                continue;
            }
            $pfx = mb_strtolower($m[1], 'UTF-8');
            if (strlen($pfx) < 2) {
                continue;
            }
            if (!isset($pfxMap[$pfx])) {
                $pfxMap[$pfx] = [];
            }
            $pfxMap[$pfx][] = ['id' => $entry['id'], 'vendor_name' => $raw];
        }
    }
    ksort($pfxMap);
    $clusters = 0;
    foreach ($pfxMap as $pfx => $items) {
        $uniqIds = [];
        foreach ($items as $it) {
            $uniqIds[$it['id']] = true;
        }
        if (count($uniqIds) < 2) {
            continue;
        }
        $clusters++;
        echo 'PREFIX "' . $pfx . '": '
            . implode(', ', array_map(static function ($it) {
                return $it['vendor_name'] . '(#' . $it['id'] . ')';
            }, $items))
            . "\n";
    }
    echo "Short-code clusters (informational): $clusters\n";
}

if (!$execute) {
    echo "\nDRY RUN — no writes. Modifies only vp_vendors: --execute\n";
    $conn->close();
    exit(0);
}

$mergedGroups = 0;
$removed = 0;

foreach ($dups as $g) {
    if (($g['key'] ?? '') === '') {
        fwrite(STDERR, "Skipping group for empty vendor_name (unsafe). ids: "
            . implode(', ', array_column($g['rows'], 'id')) . "\n");
        continue;
    }
    $rows = $g['rows'];
    usort($rows, static fn ($a, $b) => $a['id'] <=> $b['id']);
    $keeperId = $rows[0]['id'];
    $dupIds = array_slice(array_column($rows, 'id'), 1);

    $conn->begin_transaction();
    try {
        foreach ($dupIds as $dupId) {
            $dupId = (int) $dupId;
            $conn->query('DELETE FROM vp_vendors WHERE id = ' . $dupId . ' LIMIT 1');
            $removed += $conn->affected_rows;
        }
        $conn->commit();
        $mergedGroups++;
    } catch (Throwable $e) {
        $conn->rollback();
        dedupe_fail(
            'DELETE FROM vp_vendors failed (MySQL may enforce FKs from other tables onto these ids). '
            . 'keeper=' . $keeperId . ': ' . $e->getMessage()
        );
    }
}

echo "\nDONE: {$mergedGroups} duplicate group(s); removed {$removed} extra vp_vendors row(s). "
    . "Canonical id per group = lowest id.\n";

$conn->close();
exit(0);
