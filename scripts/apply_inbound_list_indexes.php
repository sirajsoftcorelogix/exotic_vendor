<?php
/**
 * Add / verify indexes for inbound list performance (getAll batch queries + filters).
 *
 * CLI (from project root):
 *   php scripts/apply_inbound_list_indexes.php              # dry-run: show status
 *   php scripts/apply_inbound_list_indexes.php --execute    # create missing indexes
 *   php scripts/apply_inbound_list_indexes.php --verify     # SHOW INDEX summary only
 */
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
    http_response_code(403);
    echo "CLI only. Run: php scripts/apply_inbound_list_indexes.php --execute\n";
    exit(1);
}

$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

function inbound_idx_fail(string $msg): void
{
    fwrite(STDERR, $msg . "\n");
    exit(1);
}

if (!is_file($configPath)) {
    inbound_idx_fail('Missing config.php at ' . $configPath);
}

/** @var array $config */
$config = require $configPath;

$argv = $_SERVER['argv'] ?? [];
$execute = in_array('--execute', $argv, true);
$verifyOnly = in_array('--verify', $argv, true);

$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    inbound_idx_fail('config.php must define [\'db\'] with host, name, user, pass.');
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
    inbound_idx_fail('Database connection failed: ' . $e->getMessage());
}

/** @var list<array{table: string, name: string, columns: string, reason: string}> */
$indexes = [
  // inbound_logs
  ['table' => 'inbound_logs', 'name' => 'idx_inbound_logs_i_id', 'columns' => 'i_id', 'reason' => 'batch log fetch WHERE i_id IN (...)'],
  ['table' => 'inbound_logs', 'name' => 'idx_inbound_logs_i_id_stat', 'columns' => 'i_id, stat', 'reason' => 'per-inbound stat lookups'],
  ['table' => 'inbound_logs', 'name' => 'idx_inbound_logs_stat_created_at', 'columns' => 'stat, created_at', 'reason' => 'published date range filter'],
  ['table' => 'inbound_logs', 'name' => 'idx_inbound_logs_stat_i_id', 'columns' => 'stat, i_id', 'reason' => 'IN (SELECT i_id WHERE stat = ...) subqueries'],
  // item_images
  ['table' => 'item_images', 'name' => 'idx_item_images_item_display_id', 'columns' => 'item_id, display_order, id', 'reason' => 'list gallery batch + ORDER BY'],
  // vp_inbound
  ['table' => 'vp_inbound', 'name' => 'idx_vp_inbound_vendor_code', 'columns' => 'vendor_code', 'reason' => 'vendor filter + dropdown JOIN'],
  ['table' => 'vp_inbound', 'name' => 'idx_vp_inbound_received_by', 'columns' => 'received_by_user_id', 'reason' => 'agent filter + dropdown JOIN'],
  ['table' => 'vp_inbound', 'name' => 'idx_vp_inbound_updated_by', 'columns' => 'updated_by_user_id', 'reason' => 'feeder filter + dropdown JOIN'],
  ['table' => 'vp_inbound', 'name' => 'idx_vp_inbound_group_name', 'columns' => 'group_name', 'reason' => 'group filter + dropdown JOIN'],
  ['table' => 'vp_inbound', 'name' => 'idx_vp_inbound_created_at', 'columns' => 'created_at', 'reason' => 'created date filter + sort'],
  ['table' => 'vp_inbound', 'name' => 'idx_vp_inbound_modified_at', 'columns' => 'modified_at', 'reason' => 'edited date sort'],
  ['table' => 'vp_inbound', 'name' => 'idx_vp_inbound_assigned_user_at', 'columns' => 'assigned_to_user_id, assigned_at, id', 'reason' => 'my inbound filter + ORDER BY assigned_at'],
  ['table' => 'vp_inbound', 'name' => 'idx_vp_inbound_marketplace', 'columns' => 'Marketplace', 'reason' => 'in-house filter'],
];

function inbound_index_exists(mysqli $conn, string $table, string $indexName): bool
{
    $stmt = $conn->prepare('SHOW INDEX FROM `' . str_replace('`', '', $table) . '` WHERE Key_name = ?');
    $stmt->bind_param('s', $indexName);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = $res && $res->num_rows > 0;
    if ($res) {
        $res->free();
    }
    $stmt->close();
    return $exists;
}

function inbound_table_exists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare('SHOW TABLES LIKE ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = $res && $res->num_rows > 0;
    if ($res) {
        $res->free();
    }
    $stmt->close();
    return $exists;
}

echo "Inbound list performance indexes\n";
echo "Mode: " . ($verifyOnly ? 'verify' : ($execute ? 'execute' : 'dry-run')) . "\n\n";

$missing = 0;
$created = 0;
$skipped = 0;
$errors = 0;

foreach ($indexes as $idx) {
    $table = $idx['table'];
    $name = $idx['name'];
    $columns = $idx['columns'];
    $reason = $idx['reason'];

    if (!inbound_table_exists($conn, $table)) {
        echo "[MISSING TABLE] $table — cannot add $name\n";
        $errors++;
        continue;
    }

    if (inbound_index_exists($conn, $table, $name)) {
        echo "[OK]      $table.$name ($columns)\n";
        $skipped++;
        continue;
    }

    $missing++;
    echo "[MISSING] $table.$name ($columns) — $reason\n";

    if ($execute && !$verifyOnly) {
        $sql = "ALTER TABLE `{$table}` ADD INDEX `{$name}` ({$columns})";
        try {
            $conn->query($sql);
            echo "          -> created\n";
            $created++;
        } catch (Throwable $e) {
            echo "          -> ERROR: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
}

echo "\nSummary: existing/skipped=$skipped, missing=" . ($execute ? "created=$created, still_missing=" . ($missing - $created) : $missing) . ", errors=$errors\n";

if ($verifyOnly || $execute) {
    echo "\n--- SHOW INDEX ---\n";
    foreach (['inbound_logs', 'item_images', 'vp_inbound'] as $table) {
        if (!inbound_table_exists($conn, $table)) {
            continue;
        }
        echo "\n$table:\n";
        $res = $conn->query("SHOW INDEX FROM `{$table}`");
        while ($row = $res->fetch_assoc()) {
            echo "  {$row['Key_name']} ({$row['Column_name']}, seq {$row['Seq_in_index']})\n";
        }
        $res->free();
    }
}

if (!$execute && !$verifyOnly && $missing > 0) {
    echo "\nRun with --execute to create missing indexes.\n";
}

$conn->close();
exit($errors > 0 ? 1 : 0);
