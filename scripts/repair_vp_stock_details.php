<?php
/**
 * repair_vp_stock_details.php
 *
 * Frontend-based incremental repair of item_code, size, and color in vp_stock.
 * Uses INSERT ... ON DUPLICATE KEY UPDATE to avoid locking entire table.
 * 
 * Usage: Open this file in browser, or run via CLI: php repair_vp_stock_details.php
 */

$isCli = (php_sapi_name() === 'cli');
$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

if (!is_file($configPath)) {
    die("Missing config.php at {$configPath}\n");
}

$config = require $configPath;

$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    die("config.php must define ['db'] with host, name, user, pass.\n");
}

$host = $dbCfg['host'];
$user = $dbCfg['user'] ?? '';
$pass = $dbCfg['pass'] ?? '';
$name = $dbCfg['name'];
$port = (int)($dbCfg['port'] ?? 3306);

$conn = new mysqli($host, $user, $pass, $name, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
$conn->set_charset('utf8mb4');

// Ensure required indexes exist on vp_stock for fast single-row upserts
function ensureVpStockIndexes(mysqli $conn): void
{
    $indexes = [
        'sku' => "ALTER TABLE vp_stock ADD INDEX idx_sku (sku)",
        'warehouse_id' => "ALTER TABLE vp_stock ADD INDEX idx_warehouse_id (warehouse_id)",
        'id' => "ALTER TABLE vp_stock ADD PRIMARY KEY (id)" // Usually already primary, but safe to attempt
    ];

    foreach ($indexes as $col => $alterSql) {
        $check = $conn->query("SHOW INDEX FROM vp_stock WHERE Column_name = '{$col}'");
        if ($check && $check->num_rows === 0) {
            @$conn->query($alterSql);
        }
        if ($check) $check->free();
    }
}

// Helper to resolve item details for a single vp_stock row
function resolveStockDetails(mysqli $conn, int $stockId, string $sku, int $lastTransId): array
{
    $itemCode = '';
    $size = '';
    $color = '';

    // Resolve from movement row
    if ($lastTransId > 0) {
        $mStmt = $conn->prepare('SELECT item_code, size, color FROM vp_stock_movements WHERE id = ? LIMIT 1');
        if ($mStmt) {
            $mStmt->bind_param('i', $lastTransId);
            $mStmt->execute();
            $mRow = $mStmt->get_result()->fetch_assoc();
            $mStmt->close();
            if ($mRow) {
                $itemCode = trim((string)($mRow['item_code'] ?? ''));
                $size     = trim((string)($mRow['size'] ?? ''));
                $color    = trim((string)($mRow['color'] ?? ''));
            }
        }
    }

    // Resolve from products table if movement details missing
    if ($itemCode === '' && $sku !== '') {
        $pStmt = $conn->prepare('SELECT item_code, size, color FROM vp_products WHERE sku = ? OR item_code = ? LIMIT 1');
        if ($pStmt) {
            $pStmt->bind_param('ss', $sku, $sku);
            $pStmt->execute();
            $pRow = $pStmt->get_result()->fetch_assoc();
            $pStmt->close();
            if ($pRow) {
                $itemCode = trim((string)($pRow['item_code'] ?? ''));
                if ($size === '')  $size  = trim((string)($pRow['size'] ?? ''));
                if ($color === '') $color = trim((string)($pRow['color'] ?? ''));
            }
        }
    }

    return [
        'item_code' => $itemCode,
        'size'      => $size,
        'color'     => $color
    ];
}

// API Endpoint for single row repair (AJAX)
if (isset($_GET['action']) && $_GET['action'] === 'repair_one') {
    header('Content-Type: application/json');

    $stockId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($stockId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid stock ID']);
        exit;
    }

    $stmt = $conn->prepare('SELECT id, sku, warehouse_id, current_stock, last_trans_id FROM vp_stock WHERE id = ? LIMIT 1');
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB error']);
        exit;
    }
    $stmt->bind_param('i', $stockId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Row not found']);
        exit;
    }

    $details = resolveStockDetails($conn, $stockId, (string)$row['sku'], (int)$row['last_trans_id']);

    if ($details['item_code'] === '' && $details['size'] === '' && $details['color'] === '') {
        echo json_encode(['success' => false, 'message' => 'No item details resolved']);
        exit;
    }

    // Use INSERT ... ON DUPLICATE KEY UPDATE to bypass table locks on UPDATE scans
    $sql = "INSERT INTO vp_stock (id, sku, warehouse_id, current_stock, last_trans_id, item_code, size, color)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                item_code = VALUES(item_code),
                size = VALUES(size),
                color = VALUES(color),
                updated_at = NOW()";

    $updStmt = $conn->prepare($sql);
    if (!$updStmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $updStmt->bind_param(
        'isiiisss',
        $stockId,
        $row['sku'],
        $row['warehouse_id'],
        $row['current_stock'],
        $row['last_trans_id'],
        $details['item_code'],
        $details['size'],
        $details['color']
    );

    try {
        $updStmt->execute();
        $updStmt->close();

        echo json_encode([
            'success' => true,
            'id' => $stockId,
            'sku' => $row['sku'],
            'item_code' => $details['item_code'],
            'size' => $details['size'],
            'color' => $details['color']
        ]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// API Endpoint to fetch pending count and next pending IDs
if (isset($_GET['action']) && $_GET['action'] === 'get_pending') {
    header('Content-Type: application/json');

    $countSql = "SELECT COUNT(*) AS cnt FROM vp_stock WHERE item_code IS NULL OR size IS NULL OR color IS NULL";
    $countRes = $conn->query($countSql);
    $count = $countRes ? (int) $countRes->fetch_assoc()['cnt'] : 0;

    $limit = 100;
    $idSql = "SELECT id FROM vp_stock WHERE item_code IS NULL OR size IS NULL OR color IS NULL ORDER BY id ASC LIMIT {$limit}";
    $idRes = $conn->query($idSql);
    $ids = [];
    if ($idRes) {
        while ($r = $idRes->fetch_assoc()) {
            $ids[] = (int) $r['id'];
        }
        $idRes->free();
    }

    echo json_encode([
        'success' => true,
        'total_pending' => $count,
        'ids' => $ids
    ]);
    exit;
}

// CLI Mode
if ($isCli) {
    echo "Starting vp_stock repair in CLI mode...\n";
    $totalUpdated = 0;
    $startTime = microtime(true);

    while (true) {
        $batchSql = "SELECT s.id, s.sku, s.warehouse_id, s.current_stock, s.last_trans_id FROM vp_stock s WHERE (s.item_code IS NULL OR s.size IS NULL OR s.color IS NULL) ORDER BY s.id ASC LIMIT 10";
        $batchRes = $conn->query($batchSql);
        if (!$batchRes || $batchRes->num_rows === 0) {
            break;
        }

        while ($row = $batchRes->fetch_assoc()) {
            $stockId = (int) $row['id'];
            $details = resolveStockDetails($conn, $stockId, (string)$row['sku'], (int)$row['last_trans_id']);

            if ($details['item_code'] !== '' || $details['size'] !== '' || $details['color'] !== '') {
                $sql = "INSERT INTO vp_stock (id, sku, warehouse_id, current_stock, last_trans_id, item_code, size, color)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            item_code = VALUES(item_code),
                            size = VALUES(size),
                            color = VALUES(color),
                            updated_at = NOW()";
                $updStmt = $conn->prepare($sql);
                if ($updStmt) {
                    $updStmt->bind_param(
                        'isiiisss',
                        $stockId,
                        $row['sku'],
                        $row['warehouse_id'],
                        $row['current_stock'],
                        $row['last_trans_id'],
                        $details['item_code'],
                        $details['size'],
                        $details['color']
                    );
                    if ($updStmt->execute()) {
                        $totalUpdated++;
                        echo "Updated ID {$stockId} (SKU: {$row['sku']}) -> ItemCode: {$details['item_code']}\n";
                    } else {
                        echo "Error on ID {$stockId}: " . $updStmt->error . "\n";
                    }
                    $updStmt->close();
                }
            }
        }
        $batchRes->free();
    }

    $elapsed = round(microtime(true) - $startTime, 2);
    echo "Repair complete. Total updated: {$totalUpdated} rows in {$elapsed} seconds.\n";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repair vp_stock Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-6">
    <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-md p-6 border border-slate-200">
        <h1 class="text-2xl font-bold text-slate-800 mb-4">Repair vp_stock Details (Frontend)</h1>
        <p class="text-sm text-slate-600 mb-4">
            This page processes each <code>vp_stock</code> row individually via AJAX to populate <code>item_code</code>, <code>size</code>, and <code>color</code> without lock wait timeouts.
        </p>

        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-4 flex items-center justify-between">
            <div>
                <span class="text-sm font-semibold text-indigo-900">Pending Rows:</span>
                <span id="pendingCount" class="text-lg font-bold text-indigo-700 ml-2">Loading...</span>
            </div>
            <div>
                <span class="text-sm font-semibold text-indigo-900">Processed:</span>
                <span id="processedCount" class="text-lg font-bold text-indigo-700 ml-2">0</span>
            </div>
            <div>
                <span class="text-sm font-semibold text-indigo-900">Errors:</span>
                <span id="errorCount" class="text-lg font-bold text-red-600 ml-2">0</span>
            </div>
        </div>

        <div class="flex items-center gap-3 mb-4">
            <button id="startBtn" onclick="startRepair()" class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition">
                Start Repair
            </button>
            <button id="stopBtn" onclick="stopRepair()" disabled class="px-5 py-2 bg-slate-300 text-slate-600 rounded-lg cursor-not-allowed font-medium">
                Stop
            </button>
        </div>

        <div class="border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-2 font-semibold text-slate-700">ID</th>
                        <th class="px-4 py-2 font-semibold text-slate-700">SKU</th>
                        <th class="px-4 py-2 font-semibold text-slate-700">Item Code</th>
                        <th class="px-4 py-2 font-semibold text-slate-700">Size</th>
                        <th class="px-4 py-2 font-semibold text-slate-700">Color</th>
                        <th class="px-4 py-2 font-semibold text-slate-700">Status</th>
                    </tr>
                </thead>
                <tbody id="logTableBody" class="divide-y divide-slate-100 bg-white">
                    <tr id="placeholderRow">
                        <td colspan="6" class="px-4 py-6 text-center text-slate-400 italic">Click "Start Repair" to begin processing pending rows.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

<script>
let isRunning = false;
let processed = 0;
let errors = 0;
let pendingIds = [];

async function fetchPending() {
    try {
        const res = await fetch('?action=get_pending');
        const data = await res.json();
        if (data.success) {
            document.getElementById('pendingCount').innerText = data.total_pending;
            pendingIds = data.ids;
            return data.total_pending;
        }
    } catch (e) {
        console.error(e);
    }
    return 0;
}

async function processRow(id) {
    const rowHtml = `<tr id="row-${id}" class="hover:bg-slate-50 transition">
        <td class="px-4 py-2 font-mono text-xs">${id}</td>
        <td class="px-4 py-2 font-mono text-xs">...</td>
        <td class="px-4 py-2 font-mono text-xs text-slate-400">...</td>
        <td class="px-4 py-2 font-mono text-xs text-slate-400">...</td>
        <td class="px-4 py-2 font-mono text-xs text-slate-400">...</td>
        <td class="px-4 py-2 text-indigo-600 font-medium">Processing...</td>
    </tr>`;

    const placeholder = document.getElementById('placeholderRow');
    if (placeholder) placeholder.remove();

    document.getElementById('logTableBody').insertAdjacentHTML('afterbegin', rowHtml);

    try {
        const res = await fetch(`?action=repair_one&id=${id}`);
        const data = await res.json();

        const rowEl = document.getElementById(`row-${id}`);
        if (data.success) {
            rowEl.cells[1].innerText = data.sku || '';
            rowEl.cells[2].innerText = data.item_code || '—';
            rowEl.cells[3].innerText = data.size || '—';
            rowEl.cells[4].innerText = data.color || '—';
            rowEl.cells[5].innerHTML = '<span class="text-emerald-600 font-semibold">Success</span>';
            processed++;
        } else {
            rowEl.cells[5].innerHTML = `<span class="text-red-600 font-semibold">Failed: ${data.message}</span>`;
            errors++;
        }
    } catch (e) {
        errors++;
    }

    document.getElementById('processedCount').innerText = processed;
    document.getElementById('errorCount').innerText = errors;
}

async function startRepair() {
    if (isRunning) return;
    isRunning = true;
    document.getElementById('startBtn').disabled = true;
    document.getElementById('startBtn').classList.add('opacity-50', 'cursor-not-allowed');
    document.getElementById('stopBtn').disabled = false;
    document.getElementById('stopBtn').classList.remove('bg-slate-300', 'text-slate-600', 'cursor-not-allowed');
    document.getElementById('stopBtn').classList.add('bg-red-600', 'text-white', 'hover:bg-red-700');

    while (isRunning) {
        const pending = await fetchPending();
        if (pending === 0 || pendingIds.length === 0) {
            break;
        }

        // Process next 10 IDs sequentially to avoid DB locks
        const idsToProcess = pendingIds.splice(0, 10);
        for (const id of idsToProcess) {
            if (!isRunning) break;
            await processRow(id);
        }

        // Small delay between batches
        await new Promise(r => setTimeout(r, 500));
    }

    isRunning = false;
    document.getElementById('startBtn').disabled = false;
    document.getElementById('startBtn').classList.remove('opacity-50', 'cursor-not-allowed');
    document.getElementById('stopBtn').disabled = true;
    document.getElementById('stopBtn').classList.remove('bg-red-600', 'text-white', 'hover:bg-red-700');
    document.getElementById('stopBtn').classList.add('bg-slate-300', 'text-slate-600', 'cursor-not-allowed');
}

function stopRepair() {
    isRunning = false;
}
</script>
</body>
</html>
