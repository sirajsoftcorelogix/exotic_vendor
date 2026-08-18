<?php
/**
 * Repair script specifically for Order #3044872.
 *
 * Realigns custom_reduce = 101,577.00 across:
 *   1. vp_order_info (custom_reduce = 101577.00, total = 159663.00)
 *   2. vp_orders (custom_reduce = 101577.00 for order lines)
 *   3. vp_invoices (notes JSON pos_discounts & line_items metadata)
 *   4. pos_payments snapshots
 *
 * Usage:
 *   CLI dry-run : php scripts/fix_order_3044872.php
 *   CLI execute : php scripts/fix_order_3044872.php --execute
 *   Browser     : https://seller.exoticindia.com/scripts/fix_order_3044872.php?execute=1
 */

declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
}

$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

function fix_3044872_fail(string $msg, int $code = 500): void
{
    global $isCli;
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
    } else {
        http_response_code($code);
        echo json_encode(['success' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE) ?: $msg;
    }
    exit(1);
}

if (!is_file($configPath)) {
    fix_3044872_fail('Missing config.php at ' . $configPath);
}

/** @var array<string, mixed> $config */
$config = require $configPath;
$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    fix_3044872_fail("config.php must define ['db'] with host, name, user, pass.");
}

$argv = $_SERVER['argv'] ?? [];
$execute = false;
if ($isCli) {
    $execute = in_array('--execute', $argv, true);
} else {
    $execute = isset($_GET['execute']) && $_GET['execute'] !== '' && $_GET['execute'] !== '0';
}

require_once $root . '/helpers/pos_payment_receipt.php';

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
    fix_3044872_fail('Database connection failed: ' . $e->getMessage());
}

$orderNumber = '3044872';
$customDiscount = 101577.00;

// 1. Load current DB rows
$stmt = $conn->prepare('SELECT id, order_number, total, custom_reduce, coupon_reduce, giftvoucher_reduce, credit FROM vp_order_info WHERE order_number = ? LIMIT 1');
$stmt->bind_param('s', $orderNumber);
$stmt->execute();
$orderInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$orderInfo) {
    fix_3044872_fail("Order #{$orderNumber} not found in vp_order_info.");
}

$stmt = $conn->prepare('SELECT id, item_code, title, itemprice, finalprice, quantity, custom_reduce FROM vp_orders WHERE order_number = ?');
$stmt->bind_param('s', $orderNumber);
$stmt->execute();
$res = $stmt->get_result();
$lines = [];
$grossTotal = 0.0;
while ($row = $res->fetch_assoc()) {
    $lines[] = $row;
    $listUnit = (float)($row['itemprice'] ?? 0) > 0 ? (float)$row['itemprice'] : (float)($row['finalprice'] ?? 0);
    $qty = max(1, (int)($row['quantity'] ?? 1));
    $grossTotal += ($listUnit * $qty);
}
$stmt->close();

$grossTotal = round($grossTotal, 2);
$couponReduce = max(0.0, round((float)($orderInfo['coupon_reduce'] ?? 0), 2));
$giftReduce = max(0.0, round((float)($orderInfo['giftvoucher_reduce'] ?? 0), 2));
$credit = max(0.0, round((float)($orderInfo['credit'] ?? 0), 2));

$totalReductions = $customDiscount + $couponReduce + $giftReduce + $credit;
$netTotal = max(0.0, round($grossTotal - $totalReductions, 2));

// Load invoice
$stmt = $conn->prepare('SELECT i.id, i.invoice_number, i.notes, i.subtotal, i.tax_amount, i.total_amount FROM vp_invoices i INNER JOIN vp_order_info o ON o.id = i.vp_order_info_id WHERE o.order_number = ? AND i.pos_flag = 1 ORDER BY i.id DESC LIMIT 1');
$stmt->bind_param('s', $orderNumber);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

$beforeState = [
    'order_number' => $orderNumber,
    'vp_order_info' => [
        'total' => round((float)($orderInfo['total'] ?? 0), 2),
        'custom_reduce' => round((float)($orderInfo['custom_reduce'] ?? 0), 2),
    ],
    'gross_total' => $grossTotal,
    'custom_discount' => $customDiscount,
    'calculated_net_total' => $netTotal,
    'invoice_id' => $invoice ? (int)$invoice['id'] : null,
    'invoice_number' => $invoice['invoice_number'] ?? null,
];

if (!$execute) {
    echo json_encode([
        'success' => true,
        'execute' => false,
        'message' => 'Dry run completed. Run with --execute or ?execute=1 to apply changes.',
        'before' => $beforeState,
        'proposed_changes' => [
            'vp_order_info.custom_reduce' => $customDiscount,
            'vp_order_info.total' => $netTotal,
            'vp_orders.custom_reduce' => $customDiscount,
            'vp_invoices.notes.pos_discounts' => [
                'cash_discount' => $customDiscount,
                'custom_discount_mode' => 'fixed',
                'custom_discount_value' => $customDiscount,
                'subtotal_goods' => $grossTotal,
                'grand_total' => $netTotal,
            ],
            'pos_payments_recomputed' => true,
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}

$conn->begin_transaction();

try {
    // 1. Update vp_order_info
    $stmt = $conn->prepare('UPDATE vp_order_info SET custom_reduce = ?, total = ? WHERE order_number = ? LIMIT 1');
    $stmt->bind_param('dds', $customDiscount, $netTotal, $orderNumber);
    $stmt->execute();
    $stmt->close();

    // 2. Update vp_orders custom_reduce
    $stmt = $conn->prepare('UPDATE vp_orders SET custom_reduce = ? WHERE order_number = ?');
    $stmt->bind_param('ds', $customDiscount, $orderNumber);
    $stmt->execute();
    $stmt->close();

    // 3. Update vp_invoices notes JSON if invoice exists
    if ($invoice) {
        $invoiceId = (int)$invoice['id'];
        $notesRaw = (string)($invoice['notes'] ?? '');
        $decoded = json_decode($notesRaw, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }

        if (!isset($decoded['pos_discounts']) || !is_array($decoded['pos_discounts'])) {
            $decoded['pos_discounts'] = [];
        }

        $decoded['pos_discounts']['cash_discount'] = $customDiscount;
        $decoded['pos_discounts']['custom_discount_mode'] = 'fixed';
        $decoded['pos_discounts']['custom_discount_value'] = $customDiscount;
        $decoded['pos_discounts']['subtotal_goods'] = $grossTotal;
        $decoded['pos_discounts']['grand_total'] = $netTotal;

        // Build corrected line items array matching list prices
        $lineItemsMeta = [];
        foreach ($lines as $line) {
            $itemCode = (string)($line['item_code'] ?? '');
            $listUnit = (float)($line['itemprice'] ?? 0) > 0 ? (float)$line['itemprice'] : (float)($line['finalprice'] ?? 0);
            if ($itemCode !== '') {
                $lineItemsMeta[] = [
                    'item_code' => $itemCode,
                    'list_unit_incl' => round($listUnit, 2),
                    'discounted_unit_incl' => round($listUnit, 2),
                ];
            }
        }
        $decoded['line_items'] = $lineItemsMeta;

        $patchedJson = json_encode($decoded, JSON_UNESCAPED_UNICODE);
        if ($patchedJson !== false) {
            $stmt = $conn->prepare('UPDATE vp_invoices SET notes = ? WHERE id = ? LIMIT 1');
            $stmt->bind_param('si', $patchedJson, $invoiceId);
            $stmt->execute();
            $stmt->close();
        }
    }

    // 4. Recompute pos_payments snapshots
    pos_payment_refresh_order_snapshots($conn, $orderNumber);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'execute' => true,
        'message' => "Order #{$orderNumber} database repair completed successfully.",
        'before' => $beforeState,
        'after' => [
            'vp_order_info' => [
                'total' => $netTotal,
                'custom_reduce' => $customDiscount,
            ],
            'gross_total' => $grossTotal,
            'custom_discount' => $customDiscount,
            'net_chargeable' => $netTotal,
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

} catch (Throwable $e) {
    $conn->rollback();
    fix_3044872_fail('Database repair transaction failed: ' . $e->getMessage());
}
