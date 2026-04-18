<?php
/**
 * Flag vp_vendors rows whose vendor_name is a placeholder: city/state/country name,
 * "0", blank, or digits-only — typical bad imports.
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
 * Review the blocklist in this file before running on production.
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

/**
 * Lowercase exact-match blocklist: states, UTs, common cities wrongly used as vendor names.
 * Extend this array as you find more junk patterns.
 */
$GEO_BLOCKLIST = array_fill_keys(array_map('strtolower', [
    // Country / generic
    'india', 'bharat', 'hindustan',
    // States & UTs (common spellings)
    'andhra pradesh', 'arunachal pradesh', 'assam', 'bihar', 'chhattisgarh', 'goa', 'gujarat',
    'haryana', 'himachal pradesh', 'jharkhand', 'karnataka', 'kerala', 'madhya pradesh',
    'maharashtra', 'manipur', 'meghalaya', 'mizoram', 'nagaland', 'odisha', 'orissa', 'punjab',
    'rajasthan', 'sikkim', 'tamil nadu', 'telangana', 'tripura', 'uttar pradesh', 'uttarakhand',
    'west bengal', 'delhi', 'new delhi', 'chandigarh', 'puducherry', 'pondicherry', 'jammu and kashmir',
    'jammu & kashmir', 'ladakh', 'andaman and nicobar', 'lakshadweep', 'dadra and nagar haveli',
    'daman and diu',
    // User examples & frequent city-as-name mistakes
    'agra', 'allahabad', 'prayagraj', 'bangalore', 'bengaluru', 'bengalore', 'bengaluru',
    'mumbai', 'chennai', 'kolkata', 'calcutta', 'hyderabad', 'pune', 'ahmedabad', 'jaipur',
    'lucknow', 'kanpur', 'nagpur', 'indore', 'thane', 'bhopal', 'visakhapatnam', 'patna',
    'vadodara', 'ghaziabad', 'ludhiana', 'agra', 'nashik', 'faridabad', 'meerut', 'rajkot',
    'varanasi', 'benares', 'kashi', 'srinagar', 'amritsar', 'chandigarh', 'coimbatore',
    'kochi', 'ernakulam', 'mysore', 'mysuru', 'vijayawada', 'gwalior', 'ranchi', 'guwahati',
    'howrah', 'jabalpur', 'vasai', 'navi mumbai', 'solapur', 'hubli', 'hubballi', 'mysuru',
    'janakpuri', 'rohini', 'dwarka', 'karol bagh', 'connaught place',
    'surat', 'vadodara', 'rajkot', 'bhavnagar', 'jamnagar', 'udaipur', 'ajmer', 'jodhpur',
    'kota', 'bikaner', 'alwar', 'bharatpur', 'sikar', 'pali', 'tonk', 'udaipur',
    'mirzapur', 'moradabad', 'bareilly', 'aligarh', 'saharanpur', 'mathura', 'firozabad',
    'jhansi', 'shahjahanpur', 'rampur', 'modinagar', 'hapur', 'etawah', 'bahraich',
    'puri', 'bhubaneswar', 'bhubaneshwar', 'cuttack', 'rourkela', 'berhampur',
    'madurai', 'salem', 'tiruchirappalli', 'coimbatore', 'erode', 'vellore', 'thanjavur',
    'tirunelveli', 'kanyakumari', 'kumbakonam', 'hosur', 'karur', 'nagercoil',
    'dehradun', 'haridwar', 'roorkee', 'haldwani', 'rudrapur',
    'siliguri', 'asansol', 'durgapur', 'bardhaman', 'malda', 'howrah', 'darjeeling',
    'gangtok', 'shillong', 'aizawl', 'kohima', 'itanagar', 'dispur', 'imphal',
    // Typos / region words alone
    'south india', 'north india', 'east india', 'west india', 'central india',
    'odisha', 'orissa',
]), true);

/**
 * @return array{reason: string}|null null = keep row
 */
function classify_placeholder_vendor_name(string $vendorName, array $geoBlocklist): ?array
{
    $t = trim($vendorName);
    if ($t === '') {
        return ['reason' => 'empty_name'];
    }
    if ($t === '0' || strcasecmp($t, 'null') === 0) {
        return ['reason' => 'literal_zero_or_null'];
    }
    if (preg_match('/^[0-9]+$/', $t)) {
        return ['reason' => 'digits_only'];
    }
    $lower = mb_strtolower($t, 'UTF-8');
    if (isset($geoBlocklist[$lower])) {
        return ['reason' => 'geo_blocklist'];
    }
    // Single token that looks like "StateName" without spaces — already covered if in list
    return null;
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
    clean_vp_fail('Database connection failed: ' . $e->getMessage());
}

$sql = 'SELECT id, vendor_name, city, state, is_active, notes FROM vp_vendors ORDER BY id ASC';
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
    $c = classify_placeholder_vendor_name($vn, $GEO_BLOCKLIST);
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
