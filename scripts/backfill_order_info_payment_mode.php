<?php
/**
 * Backfill & Fix vp_order_info.payment_mode records for a given date range or order numbers.
 *
 * Resolves payment_mode based on payment_type, pos_payments, and vp_dispatch_details:
 *   - upi / bank_transfer / cheque -> "YES2971"
 *   - pos_machine / pos            -> "pos"
 *   - cod                          -> Courier Name (from dispatch) or "COD"
 *   - cash / adminorder / etc.     -> "cash" / "adminorder" / etc.
 *
 * CLI Usage (from project root):
 *   php scripts/backfill_order_info_payment_mode.php --start-date=2026-08-01 --end-date=2026-08-19
 *   php scripts/backfill_order_info_payment_mode.php --start-date=2026-08-01 --end-date=2026-08-19 --execute
 *   php scripts/backfill_order_info_payment_mode.php --order=3114463,3114147 --execute
 *
 * Web Usage (accessible via browser when session user is logged in or via backfill_logs_web_key in config.php):
 *   http://seller.exoticindia.com/scripts/backfill_order_info_payment_mode.php?start_date=2026-08-01&end_date=2026-08-19
 *   http://seller.exoticindia.com/scripts/backfill_order_info_payment_mode.php?start_date=2026-08-01&end_date=2026-08-19&execute=1
 */

declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';

if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
}

$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

function backfill_mode_fail(string $msg, int $code = 1): void
{
    global $isCli;
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
    } else {
        http_response_code($code >= 400 && $code < 600 ? $code : 500);
        echo $msg . PHP_EOL;
    }
    exit(1);
}

if (!is_file($configPath)) {
    backfill_mode_fail('Missing config.php at ' . $configPath);
}

/** @var array<string, mixed> $config */
$config = require $configPath;

$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    backfill_mode_fail("config.php must define ['db'] with host, name, user, pass.");
}

// Authentication for Web Access
if (!$isCli) {
    session_start();
    $webKey = (string) ($config['backfill_logs_web_key'] ?? '');
    $givenKey = (string) ($_GET['key'] ?? '');
    $isLoggedIn = !empty($_SESSION['user_id']) || !empty($_SESSION['user']['id']);

    if (!$isLoggedIn && ($webKey === '' || !hash_equals($webKey, $givenKey))) {
        http_response_code(403);
        echo "Web access denied. Either log in to the portal or provide a valid ?key=...\n";
        exit(0);
    }
}

// Parse Parameters
$argv = $_SERVER['argv'] ?? [];
$execute = false;
$startDate = '';
$endDate = '';
$orderNumbers = [];

if ($isCli) {
    $execute = in_array('--execute', $argv, true);
    foreach ($argv as $arg) {
        if (preg_match('/^--start-date=(.+)$/i', $arg, $m)) {
            $startDate = trim($m[1]);
        } elseif (preg_match('/^--end-date=(.+)$/i', $arg, $m)) {
            $endDate = trim($m[1]);
        } elseif (preg_match('/^--order=(.+)$/i', $arg, $m)) {
            foreach (explode(',', (string)$m[1]) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $orderNumbers[] = $part;
                }
            }
        }
    }
} else {
    $execute = isset($_GET['execute']) && $_GET['execute'] !== '' && $_GET['execute'] !== '0';
    $startDate = trim((string)($_GET['start_date'] ?? $_GET['from_date'] ?? ''));
    $endDate = trim((string)($_GET['end_date'] ?? $_GET['to_date'] ?? ''));
    foreach (explode(',', (string)($_GET['order'] ?? $_GET['order_number'] ?? '')) as $part) {
        $part = trim($part);
        if ($part !== '') {
            $orderNumbers[] = $part;
        }
    }
}

// Default date range if not specified
if ($orderNumbers === [] && $startDate === '' && $endDate === '') {
    $startDate = date('Y-m-01'); // Beginning of current month
    $endDate = date('Y-m-d');    // Today
}

// Database Connection
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli(
        (string)$dbCfg['host'],
        (string)$dbCfg['user'],
        (string)$dbCfg['pass'],
        (string)$dbCfg['name'],
        (int)($dbCfg['port'] ?? 3306)
    );
    if (!empty($dbCfg['charset'])) {
        $conn->set_charset((string)$dbCfg['charset']);
    }
} catch (Throwable $e) {
    backfill_mode_fail('Database connection failed: ' . $e->getMessage());
}

require_once $root . '/helpers/pos_payment_receipt.php';
pos_payment_ensure_order_info_payment_mode_column($conn);

// Build Query to Fetch Target vp_order_info Records
$whereClauses = [];
$params = [];
$types = '';

if ($orderNumbers !== []) {
    $placeholders = implode(',', array_fill(0, count($orderNumbers), '?'));
    $whereClauses[] = "oi.order_number IN ($placeholders)";
    foreach ($orderNumbers as $ord) {
        $params[] = $ord;
        $types .= 's';
    }
} else {
    if ($startDate !== '') {
        $whereClauses[] = "DATE(oi.created_at) >= ?";
        $params[] = $startDate;
        $types .= 's';
    }
    if ($endDate !== '') {
        $whereClauses[] = "DATE(oi.created_at) <= ?";
        $params[] = $endDate;
        $types .= 's';
    }
}

$whereSql = $whereClauses !== [] ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
$sql = "SELECT oi.id, oi.order_number, oi.payment_type, oi.payment_mode, DATE(oi.created_at) AS created_date
        FROM vp_order_info oi
        {$whereSql}
        ORDER BY oi.id ASC";

$stmt = $conn->prepare($sql);
if ($types !== '' && $params !== []) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$totalEvaluated = 0;
$alreadyCorrect = 0;
$needsUpdate = 0;
$updatedCount = 0;
$errorCount = 0;
$resolvedBreakdown = [];

echo "=========================================================================\n";
echo " vp_order_info.payment_mode Backfill Script\n";
echo "=========================================================================\n";
echo " Mode        : " . ($execute ? "EXECUTE (Applying changes to DB)" : "DRY-RUN (Simulating changes)") . "\n";
if ($orderNumbers !== []) {
    echo " Target Orders: " . implode(', ', $orderNumbers) . "\n";
} else {
    echo " Date Range  : " . ($startDate !== '' ? $startDate : 'Any') . " to " . ($endDate !== '' ? $endDate : 'Any') . "\n";
}
echo "=========================================================================\n\n";

$updateStmt = $conn->prepare("UPDATE vp_order_info SET payment_mode = ? WHERE id = ?");

while ($row = $res->fetch_assoc()) {
    $totalEvaluated++;
    $id = (int)$row['id'];
    $orderNumber = (string)($row['order_number'] ?? '');
    $payType = (string)($row['payment_type'] ?? '');
    $currentMode = $row['payment_mode'] !== null ? (string)$row['payment_mode'] : null;

    $resolvedMode = pos_payment_resolve_order_payment_mode($conn, $orderNumber, $payType, $currentMode ?? '');

    $resolvedBreakdown[$resolvedMode] = ($resolvedBreakdown[$resolvedMode] ?? 0) + 1;

    if ($currentMode === $resolvedMode) {
        $alreadyCorrect++;
        continue;
    }

    $needsUpdate++;
    $oldDisplay = $currentMode !== null ? "'{$currentMode}'" : 'NULL';
    echo sprintf(
        " Order #%-10s | Type: %-12s | Old Mode: %-12s => New Mode: %-12s | %s\n",
        $orderNumber,
        $payType !== '' ? $payType : 'N/A',
        $oldDisplay,
        "'{$resolvedMode}'",
        $execute ? "[UPDATING...]" : "[DRY-RUN MATCH]"
    );

    if ($execute) {
        try {
            $updateStmt->bind_param('si', $resolvedMode, $id);
            if ($updateStmt->execute()) {
                $updatedCount++;
            } else {
                $errorCount++;
                echo "   ERROR updating id {$id}: " . $updateStmt->error . "\n";
            }
        } catch (Throwable $e) {
            $errorCount++;
            echo "   EXCEPTION updating id {$id}: " . $e->getMessage() . "\n";
        }
    }
}

$stmt->close();
$updateStmt->close();

echo "\n-------------------------------------------------------------------------\n";
echo " SUMMARY & BREAKDOWN\n";
echo "-------------------------------------------------------------------------\n";
echo " Total Evaluated  : {$totalEvaluated}\n";
echo " Already Correct  : {$alreadyCorrect}\n";
echo " Pending Updates  : {$needsUpdate}\n";
if ($execute) {
    echo " Successfully Updated: {$updatedCount}\n";
    if ($errorCount > 0) {
        echo " Update Errors    : {$errorCount}\n";
    }
} else {
    echo " (Run with --execute or &execute=1 to apply updates to database)\n";
}

echo "\n Resolved Value Breakdown:\n";
foreach ($resolvedBreakdown as $val => $cnt) {
    echo sprintf("   - %-20s : %d\n", "'{$val}'", $cnt);
}
echo "=========================================================================\n";
