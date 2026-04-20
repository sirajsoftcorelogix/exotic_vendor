<?php
/**
 * Flag vp_vendors rows whose vendor_name is a placeholder: city/state/country name,
 * "0", blank, or digits-only — typical bad imports. Geo-like names are skipped when the row has
 * vendor_email, vendor_phone, or alt_phone set.
 *
 * Default: DRY RUN (lists matches, no writes).
 *
 * CLI (project root):
 *   php scripts/clean_vp_vendors_placeholder_names.php
 *   php scripts/clean_vp_vendors_placeholder_names.php --execute --soft
 *
 * --execute --soft   Set is_active = 'inactive' and append a short note (safest).
 * --execute --delete DELETE rows with no blocking FK references only (optional; see script output).
 *
 * Extend city/state list in scripts/inc/vp_vendor_placeholder_rules.php before production runs.
 */
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
}

$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

function clean_vp_fail(string $msg, int $code = 1): void
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
    clean_vp_fail('Missing config.php at ' . $configPath);
}

/** @var array $config */
$config = require $configPath;

$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    clean_vp_fail("config.php must define ['db'] with host, name, user, pass.");
}

$argv = $_SERVER['argv'] ?? [];
$execute = in_array('--execute', $argv, true);
$soft = in_array('--soft', $argv, true);
$hardDelete = in_array('--delete', $argv, true);
$limit = 0;
foreach ($argv as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $limit = max(0, (int) $m[1]);
    }
}

if ($execute && !$soft && !$hardDelete) {
    clean_vp_fail('With --execute, add either --soft (recommended) or --delete.');
}

require_once __DIR__ . '/inc/vp_vendor_placeholder_rules.php';

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
    clean_vp_fail('Database connection failed: ' . $e->getMessage());
}

$sql = 'SELECT id, vendor_name, vendor_email, vendor_phone, alt_phone, city, state, is_active, notes FROM vp_vendors ORDER BY id ASC';
if ($limit > 0) {
    $sql .= ' LIMIT ' . (int) $limit;
}

$res = $conn->query($sql);
$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}
$res->free();

$matches = [];
foreach ($rows as $row) {
    $vn = (string) ($row['vendor_name'] ?? '');
    $c = classify_placeholder_vendor_name_respecting_contact(
        $vn,
        isset($row['vendor_email']) ? (string) $row['vendor_email'] : null,
        isset($row['vendor_phone']) ? (string) $row['vendor_phone'] : null,
        isset($row['alt_phone']) ? (string) $row['alt_phone'] : null
    );
    if ($c !== null) {
        $matches[] = [
            'id' => (int) $row['id'],
            'vendor_name' => $vn,
            'city' => $row['city'] ?? '',
            'state' => $row['state'] ?? '',
            'reason' => $c['reason'],
            'is_active' => $row['is_active'] ?? '',
        ];
    }
}

$n = count($matches);
echo "Scanned " . count($rows) . " vendors; " . $n . " matched placeholder/geo rules.\n\n";

if ($n === 0) {
    $conn->close();
    exit(0);
}

$preview = array_slice($matches, 0, 80);
echo "Preview (up to 80):\n";
echo json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if (!$execute) {
    echo "DRY RUN — no changes. Run with: php scripts/clean_vp_vendors_placeholder_names.php --execute --soft\n";
    $conn->close();
    exit(0);
}

$ids = array_column($matches, 'id');
$noteLine = "\n\n[auto-flagged " . date('Y-m-d') . "] vendor_name matched geo/placeholder cleanup rules; set inactive. Review or merge duplicate.";

if ($soft) {
    $upd = $conn->prepare(
        'UPDATE vp_vendors SET is_active = ?, notes = TRIM(CONCAT(IFNULL(notes, \'\'), ?)) WHERE id = ?'
    );
    $inactive = 'inactive';
    $affected = 0;
    foreach ($ids as $vid) {
        $upd->bind_param('ssi', $inactive, $noteLine, $vid);
        $upd->execute();
        $affected += $upd->affected_rows;
    }
    $upd->close();
    echo "SOFT: set is_active=inactive for $affected row(s) (notes appended).\n";
}

if ($hardDelete) {
    $deleted = 0;
    $blocked = [];
    foreach ($ids as $vid) {
        $vid = (int) $vid;
        $refSql = '(SELECT COUNT(*) FROM purchase_orders WHERE vendor_id = ' . $vid . ')
            + (SELECT COUNT(*) FROM vp_inbound WHERE vendor_code = ' . $vid . ')
            + (SELECT COUNT(*) FROM product_vendor_map WHERE vendor_id = ' . $vid . ')';
        $refCount = (int) ($conn->query('SELECT ' . $refSql . ' AS c')->fetch_assoc()['c'] ?? 0);
        if ($refCount > 0) {
            $blocked[] = $vid;
            continue;
        }
        $del = $conn->prepare('DELETE FROM vp_vendors WHERE id = ? LIMIT 1');
        $del->bind_param('i', $vid);
        $del->execute();
        $deleted += $del->affected_rows;
        $del->close();
    }
    echo "DELETE: removed $deleted row(s). Skipped (references exist): " . count($blocked) . " ids "
        . json_encode(array_slice($blocked, 0, 30)) . (count($blocked) > 30 ? '…' : '') . "\n";
}

$conn->close();
exit(0);
