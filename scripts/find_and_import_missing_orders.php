<?php
/**
 * Find and Import Missing Orders Script
 *
 * Fetches order lists from the Exotic India Vendor API for a specified date range (or order IDs),
 * compares them against local database tables (`vp_orders` and `vp_order_info`),
 * identifies missing/incomplete orders, and provides dry-run reporting or full execution import.
 *
 * CLI Usage (from project root):
 *   php scripts/find_and_import_missing_orders.php --start-date=2026-08-01 --end-date=2026-08-25
 *   php scripts/find_and_import_missing_orders.php --days=7
 *   php scripts/find_and_import_missing_orders.php --days=7 --execute
 *   php scripts/find_and_import_missing_orders.php --order=3114463,3114147 --execute
 *
 * Web Usage:
 *   http://seller.exoticindia.com/scripts/find_and_import_missing_orders.php?start_date=2026-08-01&end_date=2026-08-25
 *   http://seller.exoticindia.com/scripts/find_and_import_missing_orders.php?days=7&execute=1
 *   http://seller.exoticindia.com/scripts/find_and_import_missing_orders.php?order=3114463&execute=1
 */

declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';

if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
}

$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

function script_fail(string $msg, int $code = 1): void
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
    script_fail('Missing config.php at ' . $configPath);
}

/** @var array<string, mixed> $config */
$config = require $configPath;

$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    script_fail("config.php must define ['db'] with host, name, user, pass.");
}

// Authentication for Web Access
if (!$isCli) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $webKey = (string) ($config['backfill_logs_web_key'] ?? '');
    $givenKey = (string) ($_GET['key'] ?? '');
    $isLoggedIn = !empty($_SESSION['user_id']) || !empty($_SESSION['user']['id']);

    if (!$isLoggedIn && ($webKey === '' || !hash_equals($webKey, $givenKey))) {
        http_response_code(403);
        echo "Web access denied. Log in to the portal or provide ?key=...\n";
        exit(0);
    }
}

// Connect Database
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
    script_fail('Database connection failed: ' . $e->getMessage());
}

// Initialize Globals & Models
$GLOBALS['conn'] = $conn;
require_once $root . '/models/order/order.php';
require_once $root . '/models/product/product.php';
require_once $root . '/controllers/OrdersController.php';

$ordersModel = new Order($conn);
$productModel = new Product($conn);
$ordersController = new OrdersController($conn);

$GLOBALS['ordersModel'] = $ordersModel;
$GLOBALS['productModel'] = $productModel;

// Parse Parameters
$argv = $_SERVER['argv'] ?? [];
$execute = false;
$startDateStr = '';
$endDateStr = '';
$days = 0;
$orderIds = [];
$verbose = false;

if ($isCli) {
    $execute = in_array('--execute', $argv, true) || in_array('--import', $argv, true);
    $verbose = in_array('--verbose', $argv, true) || in_array('-v', $argv, true);

    foreach ($argv as $arg) {
        if (preg_match('/^--(start-date|from-date|from)=(.+)$/i', $arg, $m)) {
            $startDateStr = trim($m[2]);
        } elseif (preg_match('/^--(end-date|to-date|to)=(.+)$/i', $arg, $m)) {
            $endDateStr = trim($m[2]);
        } elseif (preg_match('/^--days=(\d+)$/i', $arg, $m)) {
            $days = (int) $m[1];
        } elseif (preg_match('/^--(order|orderid|orders)=(.+)$/i', $arg, $m)) {
            foreach (explode(',', (string) $m[2]) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $orderIds[] = $part;
                }
            }
        }
    }
} else {
    $execute = isset($_GET['execute']) && ($_GET['execute'] === '1' || $_GET['execute'] === 'true');
    $verbose = isset($_GET['verbose']) && ($_GET['verbose'] === '1' || $_GET['verbose'] === 'true');
    $startDateStr = trim((string) ($_GET['start_date'] ?? $_GET['from_date'] ?? $_GET['from'] ?? ''));
    $endDateStr = trim((string) ($_GET['end_date'] ?? $_GET['to_date'] ?? $_GET['to'] ?? ''));
    $days = isset($_GET['days']) ? (int) $_GET['days'] : 0;
    $rawOrders = trim((string) ($_GET['order'] ?? $_GET['orderid'] ?? ''));
    if ($rawOrders !== '') {
        foreach (explode(',', $rawOrders) as $part) {
            $part = trim($part);
            if ($part !== '') {
                $orderIds[] = $part;
            }
        }
    }
}

// Calculate Date Range
$fromTimestamp = 0;
$toTimestamp = time();

if ($startDateStr !== '') {
    $parsedFrom = is_numeric($startDateStr) ? (int) $startDateStr : strtotime($startDateStr);
    if ($parsedFrom && $parsedFrom > 0) {
        $fromTimestamp = $parsedFrom;
    }
}

if ($endDateStr !== '') {
    $parsedTo = is_numeric($endDateStr) ? (int) $endDateStr : strtotime($endDateStr . ' 23:59:59');
    if ($parsedTo && $parsedTo > 0) {
        $toTimestamp = $parsedTo;
    }
}

if ($fromTimestamp === 0) {
    if ($days > 0) {
        $fromTimestamp = strtotime("-{$days} days");
    } else {
        // Default to last 7 days
        $fromTimestamp = strtotime('-7 days');
    }
}

$formattedFrom = date('Y-m-d H:i:s', $fromTimestamp);
$formattedTo = date('Y-m-d H:i:s', $toTimestamp);

echo "=========================================================================\n";
echo "           EXOTIC INDIA - MISSING ORDER AUDIT & IMPORT SCRIPT            \n";
echo "=========================================================================\n";
echo "Mode           : " . ($execute ? "EXECUTE (Importing missing orders)" : "DRY RUN (Scan & Report only)") . "\n";
if ($orderIds !== []) {
    echo "Target Orders  : " . implode(', ', $orderIds) . "\n";
} else {
    echo "Date Range     : " . $formattedFrom . " to " . $formattedTo . "\n";
}
echo "Database Host  : " . $dbCfg['host'] . " (" . $dbCfg['name'] . ")\n";
echo "-------------------------------------------------------------------------\n\n";

// Helper to fetch orders from Exotic India Vendor API
function fetchVendorApiOrders(array $postData): array
{
    $url = 'https://www.exoticindia.com/vendor-api/order/fetch';
    $headers = [
        'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
        'x-adminapitest: 1',
        'Content-Type: application/x-www-form-urlencoded',
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 120,
    ]);

    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $errstr = curl_error($ch);
    curl_close($ch);

    if ($response === false || $errno !== 0) {
        return ['error' => "cURL Error ({$errno}): {$errstr}"];
    }

    $json = json_decode((string) $response, true);
    if (!is_array($json)) {
        return ['error' => 'Invalid JSON response from Exotic Vendor API.'];
    }

    return $json;
}

// Step 1: Fetch Orders from Vendor API
echo "Step 1: Querying Exotic India Vendor API...\n";
$allApiOrders = [];

if ($orderIds !== []) {
    foreach ($orderIds as $id) {
        echo "  - Fetching order ID #{$id}... ";
        $resp = fetchVendorApiOrders([
            'makeRequestOf' => 'vendors-orderjson',
            'orderid' => $id,
        ]);
        if (!empty($resp['orders']) && is_array($resp['orders'])) {
            echo "Found (" . count($resp['orders']) . " record(s))\n";
            foreach ($resp['orders'] as $ord) {
                $allApiOrders[(string) ($ord['orderid'] ?? $id)] = $ord;
            }
        } else {
            echo "Not found in API or error (" . ($resp['error'] ?? 'No data') . ")\n";
        }
    }
} else {
    $firstPageResp = fetchVendorApiOrders([
        'makeRequestOf' => 'vendors-orderjson',
        'from_date' => $fromTimestamp,
        'to_date' => $toTimestamp,
    ]);

    if (!empty($firstPageResp['error'])) {
        script_fail("API Error: " . $firstPageResp['error']);
    }

    $totalPages = (int) ($firstPageResp['total_pages'] ?? 1);
    $firstPageOrders = (array) ($firstPageResp['orders'] ?? []);
    echo "  - Page 1 of {$totalPages}: Received " . count($firstPageOrders) . " order(s)\n";

    foreach ($firstPageOrders as $ord) {
        $id = (string) ($ord['orderid'] ?? '');
        if ($id !== '') {
            $allApiOrders[$id] = $ord;
        }
    }

    if ($totalPages > 1) {
        for ($p = 2; $p <= $totalPages; $p++) {
            echo "  - Page {$p} of {$totalPages}... ";
            $pageResp = fetchVendorApiOrders([
                'makeRequestOf' => 'vendors-orderjson',
                'from_date' => $fromTimestamp,
                'to_date' => $toTimestamp,
                'page' => $p,
            ]);
            $pOrders = (array) ($pageResp['orders'] ?? []);
            echo "Received " . count($pOrders) . " order(s)\n";
            foreach ($pOrders as $ord) {
                $id = (string) ($ord['orderid'] ?? '');
                if ($id !== '') {
                    $allApiOrders[$id] = $ord;
                }
            }
        }
    }
}

$totalApiOrdersCount = count($allApiOrders);
echo "Total distinct API orders fetched: {$totalApiOrdersCount}\n\n";

if ($totalApiOrdersCount === 0) {
    echo "No orders found in Vendor API for the specified search criteria.\n";
    exit(0);
}

// Step 2: Check Local Database
echo "Step 2: Checking local database (vp_orders and vp_order_info)...\n";

$apiOrderIdsList = array_keys($allApiOrders);
$existingInOrders = [];
$existingInOrderInfo = [];

// Chunk DB lookup queries in batches of 200 IDs
foreach (array_chunk($apiOrderIdsList, 200) as $chunk) {
    $placeholders = implode(',', array_fill(0, count($chunk), '?'));

    // Check vp_orders
    $stmt = $conn->prepare("SELECT DISTINCT order_number FROM vp_orders WHERE order_number IN ({$placeholders})");
    $types = str_repeat('s', count($chunk));
    $stmt->bind_param($types, ...$chunk);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $existingInOrders[(string) $row['order_number']] = true;
    }
    $stmt->close();

    // Check vp_order_info
    $stmtInfo = $conn->prepare("SELECT DISTINCT order_number FROM vp_order_info WHERE order_number IN ({$placeholders})");
    $stmtInfo->bind_param($types, ...$chunk);
    $stmtInfo->execute();
    $resInfo = $stmtInfo->get_result();
    while ($row = $resInfo->fetch_assoc()) {
        $existingInOrderInfo[(string) $row['order_number']] = true;
    }
    $stmtInfo->close();
}

$completelyMissingOrders = [];
$missingInfoOrders = [];
$fullyPresentOrders = [];

foreach ($allApiOrders as $id => $ord) {
    $hasVpOrders = isset($existingInOrders[$id]);
    $hasVpOrderInfo = isset($existingInOrderInfo[$id]);

    if (!$hasVpOrders) {
        $completelyMissingOrders[$id] = $ord;
    } elseif (!$hasVpOrderInfo) {
        $missingInfoOrders[$id] = $ord;
    } else {
        $fullyPresentOrders[$id] = $ord;
    }
}

// Step 3: Display Audit Summary
echo "Step 3: Audit Summary\n";
echo sprintf("  - Total API Orders Scanned  : %d\n", $totalApiOrdersCount);
echo sprintf("  - Fully Present in Local DB : %d\n", count($fullyPresentOrders));
echo sprintf("  - Completely Missing Orders : %d\n", count($completelyMissingOrders));
echo sprintf("  - Missing Address/Info Rows : %d\n\n", count($missingInfoOrders));

if (count($completelyMissingOrders) > 0) {
    echo "=========================================================================\n";
    echo "COMPLETELY MISSING ORDERS (Not found in vp_orders)\n";
    echo "=========================================================================\n";
    echo sprintf(
        "%-12s | %-19s | %-22s | %-8s | %-5s | %-12s | %s\n",
        'Order ID',
        'Processed Date',
        'Customer Name',
        'Country',
        'Items',
        'Payment',
        'Total Amount'
    );
    echo "-------------------------------------------------------------------------\n";

    foreach ($completelyMissingOrders as $id => $ord) {
        $pTime = !empty($ord['processed_time']) ? date('Y-m-d H:i:s', (int) $ord['processed_time']) : 'N/A';
        $fname = (string) ($ord['address_info']['first_name'] ?? '');
        $lname = (string) ($ord['address_info']['last_name'] ?? '');
        $custName = trim($fname . ' ' . $lname);
        if ($custName === '') {
            $custName = 'N/A';
        }
        if (strlen($custName) > 22) {
            $custName = substr($custName, 0, 19) . '...';
        }

        $country = (string) ($ord['shipping_country'] ?? $ord['address_info']['country'] ?? 'N/A');
        $cart = (array) ($ord['cart'] ?? []);
        $itemCount = count($cart);
        $payType = (string) ($ord['payment_type'] ?? 'N/A');

        $totalAmt = 0.0;
        foreach ($cart as $item) {
            $qty = (float) ($item['qty'] ?? 1);
            $price = (float) ($item['finalprice'] ?? $item['itemprice'] ?? 0);
            $totalAmt += ($qty * $price);
        }

        echo sprintf(
            "%-12s | %-19s | %-22s | %-8s | %-5d | %-12s | %.2f\n",
            $id,
            $pTime,
            $custName,
            substr($country, 0, 8),
            $itemCount,
            substr($payType, 0, 12),
            $totalAmt
        );

        if ($verbose && $cart !== []) {
            foreach ($cart as $idx => $item) {
                $code = (string) ($item['itemcode'] ?? $item['sku'] ?? 'N/A');
                $title = (string) ($item['title'] ?? '');
                if (strlen($title) > 40) {
                    $title = substr($title, 0, 37) . '...';
                }
                $qty = (float) ($item['qty'] ?? 1);
                echo sprintf("     Line %d: [%s] x%.0f - %s\n", $idx + 1, $code, $qty, $title);
            }
        }
    }
    echo "-------------------------------------------------------------------------\n\n";
}

if (count($missingInfoOrders) > 0) {
    echo "=========================================================================\n";
    echo "INCOMPLETE ORDERS (Found in vp_orders, but missing vp_order_info)\n";
    echo "=========================================================================\n";
    echo sprintf("%-12s | %-19s | %s\n", 'Order ID', 'Processed Date', 'Customer Name');
    echo "-------------------------------------------------------------------------\n";

    foreach ($missingInfoOrders as $id => $ord) {
        $pTime = !empty($ord['processed_time']) ? date('Y-m-d H:i:s', (int) $ord['processed_time']) : 'N/A';
        $fname = (string) ($ord['address_info']['first_name'] ?? '');
        $lname = (string) ($ord['address_info']['last_name'] ?? '');
        $custName = trim($fname . ' ' . $lname);
        echo sprintf("%-12s | %-19s | %s\n", $id, $pTime, $custName ?: 'N/A');
    }
    echo "-------------------------------------------------------------------------\n\n";
}

// Step 4: Import Phase
if (!$execute) {
    if (count($completelyMissingOrders) > 0 || count($missingInfoOrders) > 0) {
        echo "[DRY-RUN MODE COMPLETE]\n";
        echo "Found " . (count($completelyMissingOrders) + count($missingInfoOrders)) . " order(s) requiring import or repair.\n";
        echo "To import these orders into the database, re-run with:\n";
        if ($isCli) {
            echo "  php scripts/find_and_import_missing_orders.php --start-date={$startDateStr} --end-date={$endDateStr} --execute\n";
        } else {
            echo "  Add &execute=1 to the URL.\n";
        }
    } else {
        echo "[DRY-RUN MODE COMPLETE] All orders returned by the Vendor API are fully present in the local database.\n";
    }
    exit(0);
}

echo "Step 4: Executing Import for Missing Orders...\n";
echo "-------------------------------------------------------------------------\n";

$importedCount = 0;
$failedCount = 0;

// 1. Import completely missing orders
if (count($completelyMissingOrders) > 0) {
    echo "Importing " . count($completelyMissingOrders) . " completely missing order(s)...\n";

    foreach ($completelyMissingOrders as $id => $ord) {
        echo "  - Importing Order #{$id}... ";
        try {
            $batchResult = $ordersController->importVendorOrdersFromApiPayload([$ord]);
            $numImported = (int) ($batchResult['imported'] ?? 0);

            if ($numImported > 0) {
                $importedCount++;
                echo "SUCCESS (Imported {$numImported} line(s))\n";
            } else {
                // Check if result had a specific failure message
                $errMsg = 'No lines inserted';
                if (!empty($batchResult['result'][0]['message'])) {
                    $errMsg = $batchResult['result'][0]['message'];
                }
                echo "SKIPPED/FAILED ({$errMsg})\n";
            }
        } catch (Throwable $e) {
            $failedCount++;
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
}

// 2. Repair incomplete orders (missing vp_order_info)
if (count($missingInfoOrders) > 0) {
    echo "Backfilling address info for " . count($missingInfoOrders) . " incomplete order(s)...\n";

    foreach ($missingInfoOrders as $id => $ord) {
        echo "  - Repairing Order Info for #{$id}... ";
        try {
            $custRes = $ordersModel->addCustomerIfNotExists($ord);
            $cid = (int) ($custRes['customer_id'] ?? 0);
            $addrRes = $ordersModel->insertAddressInfo($ord, $cid);

            if (!empty($addrRes['success'])) {
                $importedCount++;
                echo "SUCCESS (Address info backfilled)\n";
            } else {
                echo "SKIPPED (" . ($addrRes['message'] ?? 'Already exists or invalid') . ")\n";
            }
        } catch (Throwable $e) {
            $failedCount++;
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
}

echo "=========================================================================\n";
echo "EXECUTION SUMMARY\n";
echo "=========================================================================\n";
echo sprintf("Successfully Processed / Imported : %d order(s)\n", $importedCount);
echo sprintf("Errors / Failures                 : %d order(s)\n", $failedCount);
echo "=========================================================================\n";

exit(0);
