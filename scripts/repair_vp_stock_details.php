<?php
/**
 * repair_vp_stock_details.php
 *
 * Incrementally populate item_code, size, and color in vp_stock in small batches.
 * Prevents lock wait timeouts and long transactions by updating in chunks.
 * 
 * Usage: php repair_vp_stock_details.php
 */

$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

if (!is_file($configPath)) {
    fwrite(STDERR, "Missing config.php at {$configPath}\n");
    exit(1);
}

$config = require $configPath;

$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    fwrite(STDERR, "config.php must define ['db'] with host, name, user, pass.\n");
    exit(1);
}

$host = $dbCfg['host'];
$user = $dbCfg['user'] ?? '';
$pass = $dbCfg['pass'] ?? '';
$name = $dbCfg['name'];
$port = (int)($dbCfg['port'] ?? 3306);

$conn = new mysqli($host, $user, $pass, $name, $port);
if ($conn->connect_error) {
    fwrite(STDERR, "Connection failed: " . $conn->connect_error . "\n");
    exit(1);
}
$conn->set_charset('utf8mb4');

$batchSize = 50; // Reduced batch size to prevent lock contention on large tables
$totalUpdated = 0;
$startTime = microtime(true);

echo "Starting vp_stock repair in batches of {$batchSize}...\n";

while (true) {
    // Select batch of vp_stock rows needing repair
    $batchSql = "SELECT s.id, s.sku, s.last_trans_id
                 FROM vp_stock s
                 WHERE (s.item_code IS NULL OR s.size IS NULL OR s.color IS NULL)
                 ORDER BY s.id ASC
                 LIMIT {$batchSize}";

    $batchRes = $conn->query($batchSql);
    if (!$batchRes || $batchRes->num_rows === 0) {
        break; // No more rows to process
    }

    $stockRows = [];
    while ($row = $batchRes->fetch_assoc()) {
        $stockRows[] = $row;
    }
    $batchRes->free();

    $updates = [];
    foreach ($stockRows as $row) {
        $stockId = (int) $row['id'];
        $sku = (string) $row['sku'];
        $lastTransId = (int) ($row['last_trans_id'] ?? 0);

        // Resolve item details from movement or product table
        $detSql = "SELECT sm.item_code AS m_item_code, sm.size AS m_size, sm.color AS m_color,
                          p.item_code AS p_item_code, p.size AS p_size, p.color AS p_color
                   FROM vp_stock_movements sm
                   LEFT JOIN vp_products p ON sm.product_id = p.id
                   WHERE sm.id = ?
                   UNION
                   SELECT NULL, NULL, NULL, item_code, size, color
                   FROM vp_products p
                   WHERE p.sku = ? OR p.item_code = ?
                   LIMIT 1";

        $stmt = $conn->prepare($detSql);
        $itemCode = '';
        $size = '';
        $color = '';

        if ($stmt) {
            $stmt->bind_param('iss', $lastTransId, $sku, $sku);
            $stmt->execute();
            $det = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($det) {
                $itemCode = trim((string)($det['m_item_code'] ?? $det['p_item_code'] ?? ''));
                $size     = trim((string)($det['m_size'] ?? $det['p_size'] ?? ''));
                $color    = trim((string)($det['m_color'] ?? $det['p_color'] ?? ''));
            }
        }

        if ($itemCode !== '' || $size !== '' || $color !== '') {
            $updates[] = [
                'id' => $stockId,
                'item_code' => $itemCode,
                'size' => $size,
                'color' => $color
            ];
        }
    }

    if (!empty($updates)) {
        $conn->begin_transaction();
        try {
            $updStmt = $conn->prepare('UPDATE vp_stock SET item_code = NULLIF(?, ""), size = NULLIF(?, ""), color = NULLIF(?, ""), updated_at = NOW() WHERE id = ?');
            if ($updStmt) {
                $maxRetries = 3;
                $retryCount = 0;
                $done = false;
                while (!$done && $retryCount <= $maxRetries) {
                    try {
                        foreach ($updates as $upd) {
                            $updStmt->bind_param('sssi', $upd['item_code'], $upd['size'], $upd['color'], $upd['id']);
                            $updStmt->execute();
                        }
                        $conn->commit();
                        $done = true;
                        $totalUpdated += count($updates);
                        echo "Updated " . count($updates) . " rows (Total: {$totalUpdated})\n";
                    } catch (mysqli_sql_exception $e) {
                        $retryCount++;
                        if (strpos($e->getMessage(), 'Lock wait timeout') !== false && $retryCount <= $maxRetries) {
                            $conn->rollback();
                            echo "Lock wait timeout, retrying batch (Attempt {$retryCount}/{$maxRetries}) after 5 seconds...\n";
                            sleep(5);
                            $conn->begin_transaction(); // Restart transaction
                        } else {
                            throw $e;
                        }
                    }
                }
                $updStmt->close();
            }
        } catch (Throwable $e) {
            if ($conn->errno) {
                @$conn->rollback();
            }
            echo "Error in batch: " . $e->getMessage() . "\n";
        }
    } else {
        // If no updates were resolvable in this batch, skip ahead so we don't loop forever
        $maxId = max(array_column($stockRows, 'id'));
        $skipStmt = $conn->query("SELECT COUNT(*) AS cnt FROM vp_stock WHERE id > {$maxId} AND (item_code IS NULL OR size IS NULL OR color IS NULL)");
        if ($skipStmt && $skipStmt->fetch_assoc()['cnt'] == 0) {
            break;
        }
    }
}

$elapsed = round(microtime(true) - $startTime, 2);
echo "Repair complete. Total updated: {$totalUpdated} rows in {$elapsed} seconds.\n";
