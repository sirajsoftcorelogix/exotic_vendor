<?php
/**
 * Rebuild warehouse stock ledger from scratch.
 *
 * Phase baseline:
 *   - Delete all vp_stock_movements rows
 *   - Insert OPENING_STOCK per product at the default warehouse (qty = vp_products.local_stock)
 *   - Sync vp_products.physical_stock from the ledger
 *
 * Phase transfers:
 *   - Replay TRANSFER_OUT from vp_item_stock_transfer (chronological by transfer id)
 *   - Replay TRANSFER_IN from vp_stock_transfer_grns (chronological by grn id)
 *   - Re-sync physical_stock and rebuild vp_stock.current_stock (legacy per-warehouse cache)
 *
 * CLI (from project root):
 *   php scripts/rebuild_stock_movements.php
 *   php scripts/rebuild_stock_movements.php --execute
 *   php scripts/rebuild_stock_movements.php --execute --phase=baseline
 *   php scripts/rebuild_stock_movements.php --execute --phase=transfers
 *   php scripts/rebuild_stock_movements.php --execute --warehouse-id=3 --user-id=1
 *   php scripts/rebuild_stock_movements.php --execute --product-id=42
 *   php scripts/rebuild_stock_movements.php --execute --source=physical_stock
 *
 * Opening qty is taken from vp_products.local_stock by default (--source=local_stock).
 * vp_stock.current_stock is the legacy per-warehouse cache (sometimes called current stock).
 */
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
if (!$isCli) {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

function rebuild_fail(string $msg, int $code = 1): void
{
    fwrite(STDERR, $msg . "\n");
    exit($code);
}

function rebuild_out(string $msg): void
{
    echo $msg . "\n";
}

if (!is_file($configPath)) {
    rebuild_fail('Missing config.php at ' . $configPath);
}

/** @var array $config */
$config = require $configPath;

$argv = $_SERVER['argv'] ?? [];
$execute = in_array('--execute', $argv, true);
$phase = 'all';
$warehouseId = 0;
$userId = 0;
$productId = 0;
$minLocalStock = 0;
$openingSource = 'local_stock';

foreach ($argv as $arg) {
    if (preg_match('/^--phase=(baseline|transfers|all)$/', $arg, $m)) {
        $phase = $m[1];
    } elseif (preg_match('/^--warehouse-id=(\d+)$/', $arg, $m)) {
        $warehouseId = (int) $m[1];
    } elseif (preg_match('/^--user-id=(\d+)$/', $arg, $m)) {
        $userId = (int) $m[1];
    } elseif (preg_match('/^--product-id=(\d+)$/', $arg, $m)) {
        $productId = (int) $m[1];
    } elseif (preg_match('/^--min-local-stock=(\d+)$/', $arg, $m)) {
        $minLocalStock = (int) $m[1];
    } elseif (preg_match('/^--source=(local_stock|physical_stock)$/', $arg, $m)) {
        $openingSource = $m[1];
    }
}

$runBaseline = $phase === 'all' || $phase === 'baseline';
$runTransfers = $phase === 'all' || $phase === 'transfers';

require_once $root . '/models/product/StockMovement.php';

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $dbCfg = $config['db'] ?? null;
    if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
        rebuild_fail("config.php must define ['db'] with host, name, user, pass.");
    }
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
    rebuild_fail('Database connection failed: ' . $e->getMessage());
}

ensureOpeningStockEnum($conn);
ensurePhysicalStockColumn($conn);

if ($warehouseId <= 0) {
    $warehouseId = resolveDefaultWarehouseId($conn);
}
if ($warehouseId <= 0) {
    rebuild_fail('No active warehouse found. Pass --warehouse-id=N.');
}

$warehouseLabel = warehouseLocationLabel($conn, $warehouseId);

rebuild_out($execute ? 'EXECUTE — writing to database.' : 'DRY RUN — no database writes. Add --execute to apply.');
rebuild_out('Phase: ' . $phase . ' | Warehouse: ' . $warehouseId . ' (' . $warehouseLabel . ') | Opening source: ' . $openingSource);
if ($productId > 0) {
    rebuild_out('Scope: product id ' . $productId);
}
rebuild_out('');

if ($runBaseline) {
    runBaselinePhase($conn, $execute, $warehouseId, $warehouseLabel, $userId, $productId, $minLocalStock, $openingSource);
}

if ($runTransfers) {
    runTransfersPhase($conn, $execute, $userId, $productId);
}

if ($execute && ($runBaseline || $runTransfers)) {
    rebuildVpStockFromMovements($conn, $productId);
    syncPhysicalStockForProducts($conn, $productId);
}

$conn->close();
rebuild_out('');
rebuild_out('Done.');

// ---------------------------------------------------------------------------
// Phase: baseline
// ---------------------------------------------------------------------------

function runBaselinePhase(
    mysqli $conn,
    bool $execute,
    int $warehouseId,
    string $warehouseLabel,
    int $userId,
    int $productId,
    int $minLocalStock,
    string $openingSource
): void {
    if (!in_array($openingSource, ['local_stock', 'physical_stock'], true)) {
        $openingSource = 'local_stock';
    }
    $where = 'WHERE TRIM(COALESCE(sku, \'\')) <> \'\'';
    $types = '';
    $params = [];

    if ($productId > 0) {
        $where .= ' AND id = ?';
        $types .= 'i';
        $params[] = $productId;
    }
    if ($minLocalStock > 0) {
        $where .= ' AND IFNULL(local_stock, 0) >= ?';
        $types .= 'i';
        $params[] = $minLocalStock;
    }

    $sql = "SELECT id, sku, item_code, IFNULL(size, '') AS size, IFNULL(color, '') AS color,
                   IFNULL(local_stock, 0) AS local_stock, IFNULL(physical_stock, 0) AS physical_stock
            FROM vp_products
            {$where}
            ORDER BY id ASC";

    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $bind = [$types];
        foreach ($params as $k => $v) {
            $bind[] = &$params[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);
    }
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $openingCandidates = 0;
    foreach ($products as $p) {
        if ((int) ($p[$openingSource] ?? 0) > 0) {
            $openingCandidates++;
        }
    }

    rebuild_out('Baseline: ' . count($products) . ' product(s), ' . $openingCandidates . ' with ' . $openingSource . ' > 0 for OPENING_STOCK.');

    if (!$execute) {
        $preview = array_slice($products, 0, 15);
        foreach ($preview as $p) {
            rebuild_out(sprintf(
                '  [dry] product %d sku=%s %s=%d → OPENING_STOCK @ wh %d',
                (int) $p['id'],
                (string) $p['sku'],
                $openingSource,
                (int) ($p[$openingSource] ?? 0),
                $warehouseId
            ));
        }
        if (count($products) > 15) {
            rebuild_out('  … and ' . (count($products) - 15) . ' more');
        }
        rebuild_out('Baseline dry run: would DELETE vp_stock_movements' . ($productId > 0 ? ' for scoped product(s)' : ' (all rows)'));
        return;
    }

    $conn->begin_transaction();
    try {
        if ($productId > 0) {
            $del = $conn->prepare('DELETE FROM vp_stock_movements WHERE product_id = ?');
            $del->bind_param('i', $productId);
            $del->execute();
            $deleted = $del->affected_rows;
            $del->close();
        } else {
            $conn->query('DELETE FROM vp_stock_movements');
            $deleted = $conn->affected_rows;
        }
        rebuild_out('Deleted ' . $deleted . ' movement row(s).');

        if ($productId <= 0) {
            $conn->query('DELETE FROM vp_stock');
            rebuild_out('Cleared vp_stock (current_stock cache).');
        }

        $inserted = 0;
        $skipped = 0;
        foreach ($products as $p) {
            $pid = (int) $p['id'];
            $qty = max(0, (int) ($p[$openingSource] ?? 0));
            if ($qty <= 0) {
                StockMovement::syncProductPhysicalStock($conn, $pid);
                $skipped++;
                continue;
            }

            $sku = trim((string) ($p['sku'] ?? ''));
            $itemCode = trim((string) ($p['item_code'] ?? ''));
            $refId = 'rebuild:' . $pid;

            StockMovement::insert($conn, [
                'product_id' => $pid,
                'sku' => $sku,
                'item_code' => $itemCode,
                'size' => (string) ($p['size'] ?? ''),
                'color' => (string) ($p['color'] ?? ''),
                'warehouse_id' => $warehouseId,
                'location' => $warehouseLabel,
                'movement_type' => 'OPENING_STOCK',
                'quantity' => $qty,
                'ref_type' => 'STOCK_REBUILD',
                'ref_id' => $refId,
                'reason' => 'Opening stock from ' . $openingSource . ' (stock rebuild)',
                'update_by_user' => $userId,
                'strict_stock_check' => false,
                'sync_physical_stock' => true,
            ]);
            $inserted++;

            $updLocal = $conn->prepare('UPDATE vp_products SET local_stock = ? WHERE id = ?');
            if ($updLocal) {
                $updLocal->bind_param('ii', $qty, $pid);
                $updLocal->execute();
                $updLocal->close();
            }
        }

        $conn->commit();
        rebuild_out("Baseline complete: {$inserted} OPENING_STOCK row(s), {$skipped} product(s) with zero {$openingSource}.");
    } catch (Throwable $e) {
        $conn->rollback();
        rebuild_fail('Baseline failed: ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------------------
// Phase: replay stock transfers
// ---------------------------------------------------------------------------

function runTransfersPhase(mysqli $conn, bool $execute, int $userId, int $productId): void
{
    $transferSql = 'SELECT id, transfer_order_no, from_warehouse, to_warehouse, created_by
                    FROM vp_stock_transfer
                    ORDER BY id ASC';
    $transfers = $conn->query($transferSql)->fetch_all(MYSQLI_ASSOC);

    $transferOutCount = 0;
    $transferInCount = 0;

    foreach ($transfers as $t) {
        $transferId = (int) $t['id'];
        $orderNo = (string) $t['transfer_order_no'];
        $fromWh = (int) $t['from_warehouse'];
        $createdBy = (int) ($t['created_by'] ?? 0);
        if ($createdBy <= 0) {
            $createdBy = $userId;
        }

        $itemStmt = $conn->prepare(
            'SELECT product_id, item_code, sku, transfer_qty
             FROM vp_item_stock_transfer
             WHERE transfer_order_no = ?
             ORDER BY id ASC'
        );
        $itemStmt->bind_param('s', $orderNo);
        $itemStmt->execute();
        $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $itemStmt->close();

        foreach ($items as $item) {
            $pid = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['transfer_qty'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            if ($productId > 0 && $pid !== $productId) {
                continue;
            }
            $transferOutCount++;
            if (!$execute) {
                continue;
            }

            $meta = loadProductMeta($conn, $pid, (string) ($item['sku'] ?? ''));
            insertReplayMovement($conn, [
                'product_id' => $pid,
                'sku' => (string) ($item['sku'] ?? $meta['sku']),
                'item_code' => (string) ($item['item_code'] ?? $meta['item_code']),
                'size' => $meta['size'],
                'color' => $meta['color'],
                'warehouse_id' => $fromWh,
                'location' => $meta['location'],
                'movement_type' => 'TRANSFER_OUT',
                'quantity' => $qty,
                'ref_type' => 'TRANSFER_ORDER',
                'ref_id' => $orderNo,
                'reason' => 'Replay transfer out (stock rebuild)',
                'update_by_user' => $createdBy,
            ]);
        }

        $grnStmt = $conn->prepare(
            'SELECT id, sku, item_code, size, color, qty_received, location, created_by
             FROM vp_stock_transfer_grns
             WHERE transfer_id = ?
             ORDER BY id ASC'
        );
        $grnStmt->bind_param('i', $transferId);
        $grnStmt->execute();
        $grns = $grnStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $grnStmt->close();

        foreach ($grns as $grn) {
            $received = (int) ($grn['qty_received'] ?? 0);
            if ($received <= 0) {
                continue;
            }
            $sku = trim((string) ($grn['sku'] ?? ''));
            $itemCode = trim((string) ($grn['item_code'] ?? ''));
            $pid = resolveProductId($conn, $sku, $itemCode);
            if ($productId > 0 && $pid !== $productId) {
                continue;
            }
            $transferInCount++;
            if (!$execute) {
                continue;
            }

            $toWh = (int) ($grn['location'] ?? 0);
            if ($toWh <= 0) {
                $toWh = (int) ($t['to_warehouse'] ?? 0);
            }
            $grnUser = (int) ($grn['created_by'] ?? 0);
            if ($grnUser <= 0) {
                $grnUser = $createdBy > 0 ? $createdBy : $userId;
            }
            $meta = loadProductMeta($conn, $pid, $sku);
            if ($sku === '' && $meta['sku'] !== '') {
                $sku = $meta['sku'];
            }

            insertReplayMovement($conn, [
                'product_id' => $pid,
                'sku' => $sku,
                'item_code' => $itemCode !== '' ? $itemCode : $meta['item_code'],
                'size' => (string) ($grn['size'] ?? $meta['size']),
                'color' => (string) ($grn['color'] ?? $meta['color']),
                'warehouse_id' => $toWh,
                'location' => warehouseLocationLabel($conn, $toWh),
                'movement_type' => 'TRANSFER_IN',
                'quantity' => $received,
                'ref_type' => 'GRN',
                'ref_id' => (string) ((int) ($grn['id'] ?? 0)),
                'reason' => 'Replay transfer in (stock rebuild) ' . $orderNo,
                'update_by_user' => $grnUser,
            ]);
        }
    }

    rebuild_out('Transfers: ' . count($transfers) . ' transfer header(s), '
        . $transferOutCount . ' TRANSFER_OUT line(s), ' . $transferInCount . ' TRANSFER_IN GRN line(s).');

    if (!$execute) {
        rebuild_out('Transfers dry run: no movements inserted.');
    } else {
        rebuild_out('Transfers replay complete.');
    }
}

// ---------------------------------------------------------------------------
// Sync helpers
// ---------------------------------------------------------------------------

function rebuildVpStockFromMovements(mysqli $conn, int $productId): void
{
    $productFilter = '';
    if ($productId > 0) {
        $productFilter = ' AND sm.product_id = ' . (int) $productId;
    }

    $sql = "
        SELECT sm.sku, sm.warehouse_id, sm.running_stock, sm.id AS movement_id
        FROM vp_stock_movements sm
        INNER JOIN (
            SELECT sku, warehouse_id, MAX(id) AS max_id
            FROM vp_stock_movements
            WHERE warehouse_id > 0 AND TRIM(COALESCE(sku, '')) <> ''
            GROUP BY sku, warehouse_id
        ) latest ON latest.max_id = sm.id
        WHERE sm.warehouse_id > 0 AND TRIM(COALESCE(sm.sku, '')) <> ''
        {$productFilter}";

    $res = $conn->query($sql);
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    if ($res) {
        $res->free();
    }

    $select = $conn->prepare('SELECT id FROM vp_stock WHERE sku = ? AND warehouse_id = ? LIMIT 1');
    $update = $conn->prepare('UPDATE vp_stock SET current_stock = ?, last_trans_id = ? WHERE id = ?');
    $insert = $conn->prepare(
        'INSERT INTO vp_stock (sku, warehouse_id, current_stock, last_trans_id) VALUES (?, ?, ?, ?)'
    );

    $upserted = 0;
    foreach ($rows as $row) {
        $sku = (string) ($row['sku'] ?? '');
        $wh = (int) ($row['warehouse_id'] ?? 0);
        $qty = (float) ($row['running_stock'] ?? 0);
        $movId = (int) ($row['movement_id'] ?? 0);
        if ($sku === '' || $wh <= 0) {
            continue;
        }

        $select->bind_param('si', $sku, $wh);
        $select->execute();
        $existing = $select->get_result()->fetch_assoc();

        if ($existing) {
            $stockId = (int) $existing['id'];
            $update->bind_param('dii', $qty, $movId, $stockId);
            $update->execute();
        } else {
            $insert->bind_param('sidi', $sku, $wh, $qty, $movId);
            $insert->execute();
        }
        $upserted++;
    }

    $select->close();
    $update->close();
    $insert->close();

    rebuild_out('vp_stock.current_stock rebuilt for ' . $upserted . ' sku/warehouse pair(s).');
}

function syncPhysicalStockForProducts(mysqli $conn, int $productId): void
{
    if ($productId > 0) {
        StockMovement::syncProductPhysicalStock($conn, $productId);
        rebuild_out('physical_stock synced for product ' . $productId . '.');
        return;
    }

    $res = $conn->query('SELECT DISTINCT product_id FROM vp_stock_movements WHERE product_id > 0');
    $count = 0;
    while ($row = $res->fetch_assoc()) {
        StockMovement::syncProductPhysicalStock($conn, (int) $row['product_id']);
        $count++;
    }
    $res->free();
    rebuild_out('physical_stock synced for ' . $count . ' product(s).');
}

function insertReplayMovement(mysqli $conn, array $data): void
{
    StockMovement::insert($conn, array_merge($data, [
        'strict_stock_check' => false,
        'sync_physical_stock' => false,
    ]));
}

function loadProductMeta(mysqli $conn, int $productId, string $sku): array
{
    $defaults = ['sku' => $sku, 'item_code' => '', 'size' => '', 'color' => '', 'location' => ''];
    if ($productId > 0) {
        $stmt = $conn->prepare(
            "SELECT sku, item_code, IFNULL(size, '') AS size, IFNULL(color, '') AS color, IFNULL(location, '') AS location
             FROM vp_products WHERE id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            return [
                'sku' => trim((string) ($row['sku'] ?? $sku)),
                'item_code' => trim((string) ($row['item_code'] ?? '')),
                'size' => (string) ($row['size'] ?? ''),
                'color' => (string) ($row['color'] ?? ''),
                'location' => (string) ($row['location'] ?? ''),
            ];
        }
    }
    if ($sku !== '') {
        $stmt = $conn->prepare(
            "SELECT id, sku, item_code, IFNULL(size, '') AS size, IFNULL(color, '') AS color, IFNULL(location, '') AS location
             FROM vp_products WHERE sku = ? LIMIT 1"
        );
        $stmt->bind_param('s', $sku);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            return [
                'sku' => trim((string) ($row['sku'] ?? $sku)),
                'item_code' => trim((string) ($row['item_code'] ?? '')),
                'size' => (string) ($row['size'] ?? ''),
                'color' => (string) ($row['color'] ?? ''),
                'location' => (string) ($row['location'] ?? ''),
            ];
        }
    }

    return $defaults;
}

function resolveProductId(mysqli $conn, string $sku, string $itemCode): int
{
    if ($sku !== '') {
        $stmt = $conn->prepare('SELECT id FROM vp_products WHERE sku = ? LIMIT 1');
        $stmt->bind_param('s', $sku);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            return (int) $row['id'];
        }
    }
    if ($itemCode !== '') {
        $stmt = $conn->prepare('SELECT id FROM vp_products WHERE item_code = ? LIMIT 1');
        $stmt->bind_param('s', $itemCode);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            return (int) $row['id'];
        }
    }

    return 0;
}

function resolveDefaultWarehouseId(mysqli $conn): int
{
    $r = $conn->query('SELECT id FROM exotic_address WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
    if ($r && ($row = $r->fetch_assoc())) {
        return (int) $row['id'];
    }

    return 0;
}

function warehouseLocationLabel(mysqli $conn, int $warehouseId): string
{
    if ($warehouseId <= 0) {
        return '-';
    }
    $stmt = $conn->prepare('SELECT address_title FROM exotic_address WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $warehouseId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $t = trim((string) ($row['address_title'] ?? ''));

    return $t !== '' ? $t : '-';
}

function ensureOpeningStockEnum(mysqli $conn): void
{
    $res = @$conn->query("SHOW COLUMNS FROM vp_stock_movements LIKE 'movement_type'");
    if (!$res) {
        return;
    }
    $row = $res->fetch_assoc();
    $res->free();
    if (!$row) {
        return;
    }
    $type = strtolower((string) ($row['Type'] ?? ''));
    if (strpos($type, 'opening_stock') !== false) {
        return;
    }
    @$conn->query(
        "ALTER TABLE vp_stock_movements MODIFY COLUMN movement_type ENUM('IN','OUT','TRANSFER_IN','TRANSFER_OUT','OPENING_STOCK') NOT NULL"
    );
}

function ensurePhysicalStockColumn(mysqli $conn): void
{
    $res = @$conn->query("SHOW COLUMNS FROM vp_products LIKE 'physical_stock'");
    if ($res && $res->num_rows > 0) {
        $res->free();
        return;
    }
    if ($res) {
        $res->free();
    }
    @$conn->query(
        'ALTER TABLE vp_products ADD COLUMN physical_stock INT NOT NULL DEFAULT 0 AFTER local_stock'
    );
}
