<?php
/**
 * repair_vp_stock_details.php
 *
 * Web / CLI page to rebuild & repair vp_stock (item_code, size, color, current_stock).
 * Runs natively using StockMovement::syncAllVpStockFromMovements() or chunked batches.
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

require_once __DIR__ . '/../models/product/StockMovement.php';

// Action 1: Full native rebuild via StockMovement::syncAllVpStockFromMovements()
if (isset($_GET['action']) && $_GET['action'] === 'full_sync') {
    header('Content-Type: application/json');
    try {
        $affected = StockMovement::syncAllVpStockFromMovements($conn);
        echo json_encode([
            'success' => true,
            'message' => "Successfully synced {$affected} vp_stock row(s) directly from vp_stock_movements ledger."
        ]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Action 2: Process batch of 100 IDs using fast primary key UPSERT
if (isset($_GET['action']) && $_GET['action'] === 'batch_repair') {
    header('Content-Type: application/json');

    $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    $batchSize = 100;

    // Fetch batch of vp_stock rows using primary key > $lastId
    $sql = "SELECT s.id, s.sku, s.warehouse_id, s.current_stock, s.last_trans_id,
                   sm.item_code AS m_item_code, sm.size AS m_size, sm.color AS m_color,
                   p.item_code AS p_item_code, p.size AS p_size, p.color AS p_color
            FROM vp_stock s
            LEFT JOIN vp_stock_movements sm ON s.last_trans_id = sm.id
            LEFT JOIN vp_products p ON (sm.product_id = p.id OR (p.sku IS NOT NULL AND p.sku = s.sku))
            WHERE s.id > {$lastId}
            ORDER BY s.id ASC
            LIMIT {$batchSize}";

    $res = $conn->query($sql);
    if (!$res) {
        echo json_encode(['success' => false, 'message' => $conn->error]);
        exit;
    }

    $updatedCount = 0;
    $maxId = $lastId;

    $updStmt = $conn->prepare("UPDATE vp_stock 
                               SET item_code = NULLIF(?, ''), 
                                   size = NULLIF(?, ''), 
                                   color = NULLIF(?, ''), 
                                   updated_at = NOW() 
                               WHERE id = ?");

    while ($r = $res->fetch_assoc()) {
        $stockId = (int)$r['id'];
        $maxId = $stockId;

        $ic = trim((string)($r['m_item_code'] ?? $r['p_item_code'] ?? ''));
        $sz = trim((string)($r['m_size'] ?? $r['p_size'] ?? ''));
        $cl = trim((string)($r['m_color'] ?? $r['p_color'] ?? ''));

        if ($updStmt && ($ic !== '' || $sz !== '' || $cl !== '')) {
            $updStmt->bind_param('sssi', $ic, $sz, $cl, $stockId);
            $updStmt->execute();
            $updatedCount++;
        }
    }
    if ($updStmt) {
        $updStmt->close();
    }
    $res->free();

    echo json_encode([
        'success' => true,
        'batch_count' => $updatedCount,
        'last_id' => $maxId,
        'has_more' => ($maxId > $lastId)
    ]);
    exit;
}

// CLI Execution Mode
if ($isCli) {
    echo "Running native vp_stock rebuild via StockMovement::syncAllVpStockFromMovements()...\n";
    try {
        $affected = StockMovement::syncAllVpStockFromMovements($conn);
        echo "Done! Successfully updated/inserted {$affected} row(s) in vp_stock.\n";
    } catch (Throwable $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync & Repair vp_stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-6">
    <div class="max-w-3xl mx-auto bg-white rounded-xl shadow-md p-6 border border-slate-200">
        <h1 class="text-2xl font-bold text-slate-800 mb-2">Sync & Repair vp_stock Table</h1>
        <p class="text-sm text-slate-600 mb-6">
            Populate missing <code>item_code</code>, <code>size</code>, and <code>color</code> columns in <code>vp_stock</code> directly from the movement ledger and product catalog.
        </p>

        <!-- Method 1: Instant Full Sync -->
        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-5 mb-6">
            <h2 class="text-lg font-bold text-indigo-900 mb-1">Option 1: Instant Native Ledger Sync (Recommended)</h2>
            <p class="text-xs text-indigo-700 mb-4">
                Executes a single optimized <code>INSERT ... ON DUPLICATE KEY UPDATE</code> to instantly sync all <code>vp_stock</code> rows from <code>vp_stock_movements</code>.
            </p>
            <button id="fullSyncBtn" onclick="runFullSync()" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold text-sm transition">
                ⚡ Run Instant Full Sync
            </button>
            <div id="fullSyncResult" class="mt-3 text-sm font-medium hidden"></div>
        </div>

        <!-- Method 2: Batch AJAX Sync -->
        <div class="bg-slate-100 border border-slate-200 rounded-lg p-5">
            <h2 class="text-lg font-bold text-slate-800 mb-1">Option 2: Chunked Batch Repair</h2>
            <p class="text-xs text-slate-600 mb-4">
                Iterates through <code>vp_stock</code> rows in small chunks of 100 using primary key cursor pagination.
            </p>
            <div class="flex items-center gap-4 mb-4">
                <button id="batchStartBtn" onclick="startBatchRepair()" class="px-5 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-900 font-medium text-sm transition">
                    Start Batch Sync
                </button>
                <button id="batchStopBtn" onclick="stopBatchRepair()" disabled class="px-5 py-2 bg-slate-300 text-slate-500 rounded-lg text-sm cursor-not-allowed font-medium">
                    Stop
                </button>
            </div>
            <div id="batchProgress" class="text-xs font-mono text-slate-600 bg-white p-3 rounded border border-slate-200 h-24 overflow-y-auto hidden"></div>
        </div>
    </div>

<script>
async function runFullSync() {
    const btn = document.getElementById('fullSyncBtn');
    const resDiv = document.getElementById('fullSyncResult');
    
    btn.disabled = true;
    btn.innerText = 'Syncing...';
    resDiv.classList.add('hidden');

    try {
        const res = await fetch('?action=full_sync');
        const data = await res.json();

        resDiv.classList.remove('hidden');
        if (data.success) {
            resDiv.className = 'mt-3 text-sm font-medium text-emerald-700 bg-emerald-50 p-3 rounded border border-emerald-200';
            resDiv.innerText = data.message;
        } else {
            resDiv.className = 'mt-3 text-sm font-medium text-red-700 bg-red-50 p-3 rounded border border-red-200';
            resDiv.innerText = 'Error: ' + data.message;
        }
    } catch (e) {
        resDiv.classList.remove('hidden');
        resDiv.className = 'mt-3 text-sm font-medium text-red-700 bg-red-50 p-3 rounded border border-red-200';
        resDiv.innerText = 'Request failed: ' + e.message;
    }

    btn.disabled = false;
    btn.innerText = '⚡ Run Instant Full Sync';
}

let batchRunning = false;
let lastId = 0;
let totalBatchUpdated = 0;

async function startBatchRepair() {
    if (batchRunning) return;
    batchRunning = true;
    
    document.getElementById('batchStartBtn').disabled = true;
    document.getElementById('batchStopBtn').disabled = false;
    document.getElementById('batchStopBtn').className = 'px-5 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 font-medium transition';
    
    const progressDiv = document.getElementById('batchProgress');
    progressDiv.classList.remove('hidden');

    while (batchRunning) {
        try {
            const res = await fetch(`?action=batch_repair&last_id=${lastId}`);
            const data = await res.json();

            if (data.success) {
                totalBatchUpdated += data.batch_count;
                lastId = data.last_id;
                progressDiv.innerText += `Processed up to ID ${lastId} (Updated: ${data.batch_count}, Total: ${totalBatchUpdated})\n`;
                progressDiv.scrollTop = progressDiv.scrollHeight;

                if (!data.has_more) {
                    progressDiv.innerText += `\nCompleted! Total updated: ${totalBatchUpdated} rows.\n`;
                    break;
                }
            } else {
                progressDiv.innerText += `Error at ID ${lastId}: ${data.message}\n`;
                break;
            }
        } catch (e) {
            progressDiv.innerText += `Network error: ${e.message}\n`;
            break;
        }

        await new Promise(r => setTimeout(r, 200));
    }

    batchRunning = false;
    document.getElementById('batchStartBtn').disabled = false;
    document.getElementById('batchStopBtn').disabled = true;
    document.getElementById('batchStopBtn').className = 'px-5 py-2 bg-slate-300 text-slate-500 rounded-lg text-sm cursor-not-allowed font-medium';
}

function stopBatchRepair() {
    batchRunning = false;
}
</script>
</body>
</html>
