<?php
/**
 * repair_vp_stock_details.php
 *
 * Frontend page to incrementally repair item_code, size, and color in vp_stock.
 * Runs strictly in batches of 5 rows via AJAX with a visual progress bar.
 */

// Disable HTML error output for clean JSON API responses
@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$isCli = (php_sapi_name() === 'cli');
$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

if (!is_file($configPath)) {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => "Missing config.php at {$configPath}"]);
        exit;
    }
    die("Missing config.php at {$configPath}\n");
}

$config = require $configPath;

$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => "config.php must define ['db'] with host, name, user, pass."]);
        exit;
    }
    die("config.php must define ['db'] with host, name, user, pass.\n");
}

$host = $dbCfg['host'];
$user = $dbCfg['user'] ?? '';
$pass = $dbCfg['pass'] ?? '';
$name = $dbCfg['name'];
$port = (int)($dbCfg['port'] ?? 3306);

$conn = new mysqli($host, $user, $pass, $name, $port);
if ($conn->connect_error) {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]);
        exit;
    }
    die("Connection failed: " . $conn->connect_error . "\n");
}
$conn->set_charset('utf8mb4');

// Action 1: Get Initial Totals & Bounds
if (isset($_GET['action']) && $_GET['action'] === 'get_meta') {
    header('Content-Type: application/json');

    $maxRes = $conn->query("SELECT MAX(id) AS max_id, MIN(id) AS min_id FROM vp_stock");
    $maxRow = $maxRes ? $maxRes->fetch_assoc() : ['max_id' => 0, 'min_id' => 0];
    
    echo json_encode([
        'success' => true,
        'min_id' => (int)($maxRow['min_id'] ?? 0),
        'max_id' => (int)($maxRow['max_id'] ?? 0)
    ]);
    exit;
}

// Action 2: Process batch of 5 IDs using fast primary key UPSERT
if (isset($_GET['action']) && $_GET['action'] === 'batch_repair') {
    header('Content-Type: application/json');

    $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    $batchSize = 5; // Fixed batch size of 5 as requested

    // Fetch batch of 5 vp_stock rows using primary key > $lastId
    // Join using primary keys / indexed column sm.id = s.last_trans_id
    $sql = "SELECT s.id, s.sku, s.warehouse_id, s.current_stock, s.last_trans_id,
                   sm.item_code AS m_item_code, sm.size AS m_size, sm.color AS m_color,
                   p.item_code AS p_item_code, p.size AS p_size, p.color AS p_color
            FROM vp_stock s
            LEFT JOIN vp_stock_movements sm ON s.last_trans_id = sm.id
            LEFT JOIN vp_products p ON sm.product_id = p.id
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
    $processedRows = [];

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

        $processedRows[] = [
            'id' => $stockId,
            'sku' => $r['sku'],
            'item_code' => $ic,
            'size' => $sz,
            'color' => $cl
        ];
    }
    if ($updStmt) {
        $updStmt->close();
    }
    $res->free();

    echo json_encode([
        'success' => true,
        'batch_count' => count($processedRows),
        'updated_count' => $updatedCount,
        'last_id' => $maxId,
        'has_more' => ($maxId > $lastId),
        'rows' => $processedRows
    ]);
    exit;
}

// CLI Execution Mode
if ($isCli) {
    echo "Starting vp_stock repair in CLI mode...\n";
    $lastId = 0;
    $totalUpdated = 0;

    while (true) {
        $sql = "SELECT s.id, s.sku, s.last_trans_id,
                       sm.item_code AS m_item_code, sm.size AS m_size, sm.color AS m_color,
                       p.item_code AS p_item_code, p.size AS p_size, p.color AS p_color
                FROM vp_stock s
                LEFT JOIN vp_stock_movements sm ON s.last_trans_id = sm.id
                LEFT JOIN vp_products p ON sm.product_id = p.id
                WHERE s.id > {$lastId}
                ORDER BY s.id ASC
                LIMIT 5";

        $res = $conn->query($sql);
        if (!$res || $res->num_rows === 0) break;

        $maxId = $lastId;
        $upd = $conn->prepare("UPDATE vp_stock SET item_code = NULLIF(?, ''), size = NULLIF(?, ''), color = NULLIF(?, ''), updated_at = NOW() WHERE id = ?");

        while ($r = $res->fetch_assoc()) {
            $stockId = (int)$r['id'];
            $maxId = $stockId;
            $ic = trim((string)($r['m_item_code'] ?? $r['p_item_code'] ?? ''));
            $sz = trim((string)($r['m_size'] ?? $r['p_size'] ?? ''));
            $cl = trim((string)($r['m_color'] ?? $r['p_color'] ?? ''));

            if ($upd && ($ic !== '' || $sz !== '' || $cl !== '')) {
                $upd->bind_param('sssi', $ic, $sz, $cl, $stockId);
                $upd->execute();
                $totalUpdated++;
            }
        }
        if ($upd) $upd->close();
        $res->free();

        if ($maxId <= $lastId) break;
        $lastId = $maxId;
        echo "Processed up to ID {$lastId} (Total updated: {$totalUpdated})\n";
    }

    echo "CLI Repair complete. Total updated: {$totalUpdated} rows.\n";
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
        <h1 class="text-2xl font-bold text-slate-800 mb-2">Repair vp_stock Details</h1>
        <p class="text-sm text-slate-600 mb-6">
            Populate missing <code>item_code</code>, <code>size</code>, and <code>color</code> in <code>vp_stock</code> in batches of 5 rows with progress tracking.
        </p>

        <!-- Progress Card -->
        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-5 mb-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-indigo-900">Overall Progress</span>
                <span id="percentText" class="text-sm font-bold text-indigo-700">0%</span>
            </div>
            <!-- Progress Bar -->
            <div class="w-full bg-indigo-200 rounded-full h-3.5 mb-4 overflow-hidden">
                <div id="progressBar" class="bg-indigo-600 h-3.5 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
            
            <div class="grid grid-cols-3 gap-4 text-center text-sm font-medium">
                <div class="bg-white p-3 rounded border border-indigo-100 shadow-sm">
                    <div class="text-slate-500 text-xs uppercase font-semibold">Processed Rows</div>
                    <div id="processedCount" class="text-lg font-bold text-slate-800 mt-0.5">0</div>
                </div>
                <div class="bg-white p-3 rounded border border-indigo-100 shadow-sm">
                    <div class="text-slate-500 text-xs uppercase font-semibold">Current ID</div>
                    <div id="currentId" class="text-lg font-bold text-slate-800 mt-0.5">0</div>
                </div>
                <div class="bg-white p-3 rounded border border-indigo-100 shadow-sm">
                    <div class="text-slate-500 text-xs uppercase font-semibold">Max ID</div>
                    <div id="maxIdText" class="text-lg font-bold text-slate-800 mt-0.5">0</div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 mb-6">
            <button id="startBtn" onclick="startRepair()" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold text-sm transition shadow-sm">
                ▶ Start Batch Repair
            </button>
            <button id="stopBtn" onclick="stopRepair()" disabled class="px-6 py-2.5 bg-slate-200 text-slate-500 rounded-lg text-sm cursor-not-allowed font-semibold">
                ⏸ Pause
            </button>
        </div>

        <!-- Live Activity Log -->
        <div class="border border-slate-200 rounded-lg overflow-hidden shadow-sm">
            <div class="bg-slate-100 px-4 py-2.5 border-b border-slate-200 font-semibold text-slate-700 text-xs uppercase tracking-wider">
                Live Processing Feed (Batch size: 5)
            </div>
            <div id="logList" class="divide-y divide-slate-100 bg-white max-h-80 overflow-y-auto font-mono text-xs p-2">
                <div id="placeholderLog" class="p-4 text-center text-slate-400 italic font-sans">
                    Click "Start Batch Repair" to begin processing in batches of 5.
                </div>
            </div>
        </div>
    </div>

<script>
let isRunning = false;
let lastId = 0;
let minId = 0;
let maxId = 0;
let processedCount = 0;

async function initMeta() {
    try {
        const res = await fetch('?action=get_meta');
        const data = await res.json();
        if (data.success) {
            minId = data.min_id;
            maxId = data.max_id;
            document.getElementById('maxIdText').innerText = maxId;
            document.getElementById('currentId').innerText = minId;
        }
    } catch (e) {
        console.error('Failed to init meta:', e);
    }
}

async function startRepair() {
    if (isRunning) return;
    isRunning = true;

    document.getElementById('startBtn').disabled = true;
    document.getElementById('startBtn').classList.add('opacity-50', 'cursor-not-allowed');
    document.getElementById('stopBtn').disabled = false;
    document.getElementById('stopBtn').className = 'px-6 py-2.5 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 font-semibold shadow-sm transition';

    if (maxId === 0) {
        await initMeta();
    }

    const placeholder = document.getElementById('placeholderLog');
    if (placeholder) placeholder.remove();

    const logList = document.getElementById('logList');

    while (isRunning) {
        try {
            const res = await fetch(`?action=batch_repair&last_id=${lastId}`);
            
            // Validate response is JSON
            const contentType = res.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await res.text();
                logList.insertAdjacentHTML('afterbegin', `<div class="p-2 text-red-600 bg-red-50 rounded mb-1">JSON Error: ${text.substring(0, 100)}...</div>`);
                break;
            }

            const data = await res.json();

            if (data.success) {
                if (data.batch_count === 0 || !data.has_more) {
                    logList.insertAdjacentHTML('afterbegin', `<div class="p-2 text-emerald-700 bg-emerald-50 rounded mb-1 font-semibold">🎉 Repair complete! All rows processed.</div>`);
                    document.getElementById('progressBar').style.width = '100%';
                    document.getElementById('percentText').innerText = '100%';
                    break;
                }

                lastId = data.last_id;
                processedCount += data.batch_count;

                document.getElementById('processedCount').innerText = processedCount;
                document.getElementById('currentId').innerText = lastId;

                // Update progress bar
                if (maxId > 0) {
                    const pct = Math.min(100, Math.round((lastId / maxId) * 100));
                    document.getElementById('progressBar').style.width = pct + '%';
                    document.getElementById('percentText').innerText = pct + '%';
                }

                // Log batch details
                let rowSummary = data.rows.map(r => `ID ${r.id} (${r.sku || 'no-sku'}) -> ${r.item_code || 'N/A'}`).join(', ');
                logList.insertAdjacentHTML('afterbegin', `<div class="p-2 border-b border-slate-100 flex items-center justify-between"><span class="text-slate-700">Batch [ID ${data.rows[0]?.id} .. ${lastId}]: ${rowSummary}</span><span class="text-emerald-600 font-semibold text-[10px]">OK</span></div>`);

            } else {
                logList.insertAdjacentHTML('afterbegin', `<div class="p-2 text-red-600 bg-red-50 rounded mb-1">Batch Error: ${data.message}</div>`);
                break;
            }
        } catch (e) {
            logList.insertAdjacentHTML('afterbegin', `<div class="p-2 text-red-600 bg-red-50 rounded mb-1">Network Error: ${e.message}</div>`);
            break;
        }

        // Small delay between batches
        await new Promise(r => setTimeout(r, 150));
    }

    isRunning = false;
    document.getElementById('startBtn').disabled = false;
    document.getElementById('startBtn').classList.remove('opacity-50', 'cursor-not-allowed');
    document.getElementById('stopBtn').disabled = true;
    document.getElementById('stopBtn').className = 'px-6 py-2.5 bg-slate-200 text-slate-500 rounded-lg text-sm cursor-not-allowed font-semibold';
}

function stopRepair() {
    isRunning = false;
}

// Initialize bounds on page load
initMeta();
</script>
</body>
</html>
