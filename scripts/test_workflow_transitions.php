<?php
/**
 * Sanity-check order status workflow transitions (vp_workflow_transition).
 *
 * CLI: php scripts/test_workflow_transitions.php
 * Optional: php scripts/test_workflow_transitions.php --from=shipped --to=cancelled_returned
 */
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
if (!$isCli) {
    http_response_code(403);
    exit('CLI only.');
}

$projectRoot = dirname(__DIR__);
$configPath = $projectRoot . '/config.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "config.php not found.\n");
    exit(1);
}

$config = require $configPath;
$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    fwrite(STDERR, "Invalid db config.\n");
    exit(1);
}

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

require_once $projectRoot . '/helpers/order_workflow.php';

$customFrom = null;
$customTo = null;
foreach ($argv as $arg) {
    if (preg_match('/^--from=(.+)$/', $arg, $m)) {
        $customFrom = trim($m[1]);
    }
    if (preg_match('/^--to=(.+)$/', $arg, $m)) {
        $customTo = trim($m[1]);
    }
}

$model = order_workflow_transition_model($conn);

echo "=== Workflow transition test ===\n\n";

$countRes = $conn->query(
    'SELECT COUNT(*) AS c FROM vp_workflow_transition WHERE is_active = 1'
)->fetch_assoc();
$activeCount = (int) ($countRes['c'] ?? 0);
echo 'Active transitions: ' . $activeCount . "\n";
echo 'Enforcement active: ' . ($model->isEnforcementActive() ? 'YES' : 'NO (table empty)') . "\n\n";

if ($customFrom !== null && $customTo !== null) {
    $allowed = $model->isTransitionAllowedBySlug($customFrom, $customTo);
    $err = assert_order_status_transition_allowed($conn, $customFrom, $customTo, 0);
    echo "Custom pair: {$customFrom} → {$customTo}\n";
    echo '  Model allows: ' . ($allowed ? 'YES' : 'NO') . "\n";
    echo '  Assert result: ' . ($err === null ? 'ALLOWED' : 'BLOCKED — ' . $err) . "\n";
    $targets = order_workflow_allowed_targets($conn, $customFrom);
    echo '  UI filter_options: ' . (!empty($targets['filter_options']) ? 'YES' : 'NO') . "\n";
    echo '  Allowed targets: ' . implode(', ', $targets['allowed_slugs'] ?? []) . "\n";
    exit(0);
}

$fromRows = $conn->query(
    'SELECT DISTINCT fs.id, fs.slug, fs.title
     FROM vp_workflow_transition wt
     INNER JOIN vp_order_status fs ON fs.id = wt.from_status_id
     WHERE wt.is_active = 1
     ORDER BY fs.title ASC'
)->fetch_all(MYSQLI_ASSOC);

if ($fromRows === []) {
    echo "No active from-status rules configured.\n";
    exit(0);
}

$allSlugs = [];
$slugRes = $conn->query(
    'SELECT slug FROM vp_order_status WHERE is_active = 1 AND parent_id != 0 ORDER BY slug ASC'
);
while ($row = $slugRes->fetch_assoc()) {
    $allSlugs[] = strtolower(trim((string) $row['slug']));
}

$passed = 0;
$failed = 0;

foreach ($fromRows as $fromRow) {
    $fromSlug = strtolower(trim((string) $fromRow['slug']));
    $fromTitle = (string) $fromRow['title'];
    echo "--- From: {$fromTitle} ({$fromSlug}) ---\n";

    $allowedStmt = $conn->prepare(
        'SELECT os.slug, os.title
         FROM vp_workflow_transition wt
         INNER JOIN vp_order_status os ON os.id = wt.to_status_id
         WHERE wt.from_status_id = ? AND wt.is_active = 1
         ORDER BY os.title ASC
         LIMIT 1'
    );
    $fromId = (int) $fromRow['id'];
    $allowedStmt->bind_param('i', $fromId);
    $allowedStmt->execute();
    $allowedRow = $allowedStmt->get_result()->fetch_assoc();
    $allowedStmt->close();

    if ($allowedRow) {
        $toSlug = strtolower(trim((string) $allowedRow['slug']));
        $err = assert_order_status_transition_allowed($conn, $fromSlug, $toSlug, 0);
        if ($err === null) {
            echo "  PASS allowed: {$fromSlug} → {$toSlug}\n";
            $passed++;
        } else {
            echo "  FAIL allowed should pass: {$fromSlug} → {$toSlug} — {$err}\n";
            $failed++;
        }
    }

    $blockedTo = null;
    foreach ($allSlugs as $candidate) {
        if ($candidate === $fromSlug) {
            continue;
        }
        if (!$model->isTransitionAllowedBySlug($fromSlug, $candidate)) {
            $blockedTo = $candidate;
            break;
        }
    }

    if ($blockedTo !== null) {
        $err = assert_order_status_transition_allowed($conn, $fromSlug, $blockedTo, 0);
        if ($err !== null) {
            echo "  PASS blocked: {$fromSlug} → {$blockedTo}\n";
            $passed++;
        } else {
            echo "  FAIL blocked should fail: {$fromSlug} → {$blockedTo}\n";
            $failed++;
        }
    } else {
        echo "  SKIP blocked sample (all targets allowed for this from-status)\n";
    }

    $targets = order_workflow_allowed_targets($conn, $fromSlug);
    echo '  UI filter: ' . (!empty($targets['filter_options']) ? 'ON' : 'OFF')
        . ' | targets: ' . count($targets['allowed_slugs'] ?? []) . "\n\n";
}

echo "=== Summary: {$passed} passed, {$failed} failed ===\n";
if ($failed > 0) {
    exit(1);
}

echo "\nManual UI test:\n";
echo "1. Log in as non-admin user.\n";
echo "2. Orders list → Update Order on a line.\n";
echo "3. Disallowed statuses should be disabled in dropdown.\n";
echo "4. Submitting a blocked change returns JSON error (no status/stock change).\n";
echo "5. Admin (role_id=1) can still change to any status.\n";
