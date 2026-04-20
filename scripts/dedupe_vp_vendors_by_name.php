<?php
/**
 * Find duplicate vp_vendors rows (same vendor_name after trim + case-insensitive match).
 * Also lists "placeholder" vendor_name values: city / state / country / empty / digits (see
 * scripts/inc/vp_vendor_placeholder_rules.php). Geo-like names with email or phone are skipped.
 *
 * Writes only to vp_vendors:
 *   --execute                      delete duplicate name rows (keep lowest id).
 *   --execute --remove-placeholders also DELETE rows whose vendor_name matches placeholder rules.
 *
 * Short-code clusters (e.g. AD1, AD4): report only via --prefix-report — not merged unless names match.
 *
 * Default: DRY RUN (report only).
 *
 *   php scripts/dedupe_vp_vendors_by_name.php
 *   php scripts/dedupe_vp_vendors_by_name.php --prefix-report
 *   php scripts/dedupe_vp_vendors_by_name.php --execute
 *   php scripts/dedupe_vp_vendors_by_name.php --execute --remove-placeholders
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

require_once __DIR__ . '/inc/vp_vendor_placeholder_rules.php';

/** @var array $config */
$config = require $configPath;

$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    dedupe_fail("config.php must define ['db'] with host, name, user, pass.");
}

$argv = $_SERVER['argv'] ?? [];
$execute = in_array('--execute', $argv, true);
$removePlaceholders = in_array('--remove-placeholders', $argv, true);
$prefixReport = in_array('--prefix-report', $argv, true);

if ($removePlaceholders && !$execute) {
    dedupe_fail('--remove-placeholders requires --execute');
}

if (in_array('--merge', $argv, true) || in_array('--table-only', $argv, true)) {
    dedupe_fail('Obsolete flags: use --execute and optionally --remove-placeholders.');
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
    'SELECT id, vendor_name, vendor_email, vendor_phone, alt_phone, city, state FROM vp_vendors ORDER BY id ASC'
);
$byKey = [];
$allRows = [];
while ($row = $res->fetch_assoc()) {
    $name = (string) ($row['vendor_name'] ?? '');
    $key = mb_strtolower(trim($name), 'UTF-8');
    $entry = [
        'id' => (int) $row['id'],
        'vendor_name' => $name,
        'vendor_email' => (string) ($row['vendor_email'] ?? ''),
        'vendor_phone' => (string) ($row['vendor_phone'] ?? ''),
        'alt_phone' => (string) ($row['alt_phone'] ?? ''),
        'city' => (string) ($row['city'] ?? ''),
        'state' => (string) ($row['state'] ?? ''),
    ];
    $allRows[] = $entry;
    if (!isset($byKey[$key])) {
        $byKey[$key] = [];
    }
    $byKey[$key][] = $entry;
}
$res->free();

$placeholders = [];
$geoSkippedContact = 0;
foreach ($allRows as $entry) {
    $base = classify_placeholder_vendor_name($entry['vendor_name']);
    if ($base !== null && ($base['reason'] ?? '') === 'geo_blocklist'
        && vp_vendor_row_has_meaningful_contact(
            $entry['vendor_email'],
            $entry['vendor_phone'],
            $entry['alt_phone']
        )) {
        $geoSkippedContact++;
    }
    $c = classify_placeholder_vendor_name_respecting_contact(
        $entry['vendor_name'],
        $entry['vendor_email'],
        $entry['vendor_phone'],
        $entry['alt_phone']
    );
    if ($c !== null) {
        $placeholders[] = [
            'id' => $entry['id'],
            'vendor_name' => $entry['vendor_name'],
            'city' => $entry['city'],
            'state' => $entry['state'],
            'reason' => $c['reason'],
        ];
    }
}

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

$nPh = count($placeholders);
echo "\n--- Placeholder / geo-like vendor_name (city, state, country, empty, digits, etc.) ---\n";
echo "Matched {$nPh} row(s) (rules in scripts/inc/vp_vendor_placeholder_rules.php).\n";
if ($geoSkippedContact > 0) {
    echo "Skipped {$geoSkippedContact} geo/city/state name row(s) that have email or phone.\n";
}
if ($nPh > 0) {
    $showPh = array_slice($placeholders, 0, 120);
    foreach ($showPh as $p) {
        echo '  id=' . $p['id'] . ' name="' . $p['vendor_name'] . '" reason=' . $p['reason']
            . ' city="' . $p['city'] . '" state="' . $p['state'] . "\"\n";
    }
    if ($nPh > 120) {
        echo '  … plus ' . ($nPh - 120) . " more\n";
    }
}
echo "To flag these only (inactive + note), use: php scripts/clean_vp_vendors_placeholder_names.php --execute --soft\n";

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
    echo "\nDRY RUN — no writes. vp_vendors only:\n";
    echo "  --execute                          remove duplicate names (keep lowest id)\n";
    echo "  --execute --remove-placeholders    also DELETE rows matching placeholder rules above\n";
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

echo "\nDONE (dedupe): {$mergedGroups} duplicate group(s); removed {$removed} extra vp_vendors row(s). "
    . "Canonical id per group = lowest id.\n";

if ($removePlaceholders) {
    $conn->begin_transaction();
    try {
        $phRes = $conn->query(
            'SELECT id, vendor_name, vendor_email, vendor_phone, alt_phone FROM vp_vendors ORDER BY id ASC'
        );
        $delPh = 0;
        while ($pr = $phRes->fetch_assoc()) {
            $vid = (int) $pr['id'];
            if (classify_placeholder_vendor_name_respecting_contact(
                (string) ($pr['vendor_name'] ?? ''),
                isset($pr['vendor_email']) ? (string) $pr['vendor_email'] : null,
                isset($pr['vendor_phone']) ? (string) $pr['vendor_phone'] : null,
                isset($pr['alt_phone']) ? (string) $pr['alt_phone'] : null
            ) === null) {
                continue;
            }
            $conn->query('DELETE FROM vp_vendors WHERE id = ' . $vid . ' LIMIT 1');
            $delPh += $conn->affected_rows;
        }
        $phRes->free();
        $conn->commit();
        echo "DONE (placeholders): removed {$delPh} vp_vendors row(s) matching placeholder/geo rules.\n";
    } catch (Throwable $e) {
        $conn->rollback();
        dedupe_fail(
            'Placeholder DELETE failed (MySQL may enforce FKs from other tables onto these ids). '
            . $e->getMessage()
        );
    }
}

$conn->close();
exit(0);
