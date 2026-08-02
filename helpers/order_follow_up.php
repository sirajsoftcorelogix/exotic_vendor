<?php

require_once __DIR__ . '/html_helpers.php';
require_once __DIR__ . '/order_follow_up_pricing.php';
require_once __DIR__ . '/../models/order_follow_up/OrderFollowUp.php';

const POS_FOLLOW_UP_SESSION_KEY = 'pos_follow_up';

/**
 * @return array<string, mixed>|null
 */
function order_follow_up_get_session(): ?array
{
    $data = $_SESSION[POS_FOLLOW_UP_SESSION_KEY] ?? null;

    return is_array($data) ? $data : null;
}

function order_follow_up_clear_session(): void
{
    unset($_SESSION[POS_FOLLOW_UP_SESSION_KEY]);
}

/**
 * @param list<int> $lineIds
 * @return array{success:bool, message:string}
 */
function order_follow_up_start_session(
    mysqli $conn,
    string $sourceOrderNumber,
    string $followUpType,
    string $pricingMode,
    array $lineIds = [],
    int $salesReturnId = 0
): array {
    require_once __DIR__ . '/../models/posorder/order.php';

    if (!canSrEmpAccess()) {
        return ['success' => false, 'message' => 'Access denied.'];
    }

    $model = new OrderFollowUp($conn);
    $eligibility = $model->resolveStartEligibility($sourceOrderNumber, $followUpType);
    if (empty($eligibility['can_start'])) {
        return ['success' => false, 'message' => (string) ($eligibility['disabled_reason'] ?? 'Follow-up not available.')];
    }

    $followUpType = strtolower(trim($followUpType));
    if (!in_array($followUpType, OrderFollowUp::allowedFollowUpTypes(), true)) {
        return ['success' => false, 'message' => 'Invalid follow-up type.'];
    }

    $pricingMode = strtolower(trim($pricingMode));
    if (!in_array($pricingMode, OrderFollowUp::allowedPricingModes(), true)) {
        $pricingMode = OrderFollowUp::defaultPricingModeForType($followUpType);
    }

    if ($pricingMode === 'waived' && !in_array($followUpType, ['reship', 'replace'], true)) {
        $pricingMode = OrderFollowUp::defaultPricingModeForType($followUpType);
    }

    $orderLines = is_array($eligibility['order_lines']) ? $eligibility['order_lines'] : [];

    if (in_array($followUpType, ['reship', 'replace'], true)) {
        $returnedLineIds = order_follow_up_get_returned_line_ids($conn, $sourceOrderNumber, $orderLines);
        if ($returnedLineIds === []) {
            return [
                'success' => false,
                'message' => 'Reship and Replacement follow-up orders require at least one returned line in the order.',
            ];
        }

        if ($lineIds !== []) {
            $invalidLines = array_diff($lineIds, $returnedLineIds);
            if ($invalidLines !== []) {
                return [
                    'success' => false,
                    'message' => 'Only returned items can be included in a Reship or Replacement order.',
                ];
            }
        } else {
            $lineIds = $returnedLineIds;
        }
    }

    $filteredLines = order_follow_up_filter_order_lines($orderLines, $lineIds);
    if ($filteredLines === []) {
        return ['success' => false, 'message' => 'Select at least one order line.'];
    }

    $selectedLineIds = array_values(array_filter(array_map(static function ($row): int {
        return is_array($row) ? (int) ($row['id'] ?? 0) : 0;
    }, $filteredLines), static fn (int $id): bool => $id > 0));

    $snapshot = order_follow_up_build_source_pricing_snapshot($conn, $sourceOrderNumber, $selectedLineIds);
    $scope = count($selectedLineIds) >= count($orderLines) ? 'full' : 'partial';

    if ($salesReturnId <= 0) {
        $salesReturnId = (int) ($eligibility['latest_sales_return_id'] ?? 0);
    }

    $orderModel = new POSOrder($conn);
    $orderInfo = $orderModel->getAddressInfoByOrderNumber($sourceOrderNumber);
    $customerId = is_array($orderInfo) ? (int) ($orderInfo['customer_id'] ?? 0) : 0;
    if ($customerId <= 0 && !empty($orderLines[0]['customer_id'])) {
        $customerId = (int) $orderLines[0]['customer_id'];
    }

    if ($customerId > 0) {
        $_SESSION['pos_customer_id'] = $customerId;
    }

    $posLinePrices = [];
    if (in_array($pricingMode, ['same_as_original', 'waived'], true)) {
        $posLinePrices = order_follow_up_build_pos_line_prices_from_snapshot($snapshot);
        if ($pricingMode === 'waived') {
            foreach ($posLinePrices as &$lp) {
                $lp['price'] = 0.0;
            }
            unset($lp);
        }
    }

    $_SESSION[POS_FOLLOW_UP_SESSION_KEY] = [
        'source_order_number' => trim($sourceOrderNumber),
        'follow_up_type' => $followUpType,
        'pricing_mode' => $pricingMode,
        'scope' => $scope,
        'line_ids' => $selectedLineIds,
        'sales_return_id' => $salesReturnId > 0 ? $salesReturnId : null,
        'source_pricing_snapshot' => $snapshot,
        'pos_line_prices' => $posLinePrices,
        'seed_pending' => true,
    ];

    return ['success' => true, 'message' => 'Follow-up session started.'];
}

/**
 * @return array{success:bool, message:string, cart_data?:array<string,mixed>}
 */
function order_follow_up_seed_exotic_cart(mysqli $conn, RetailApiClient $client): array
{
    $session = order_follow_up_get_session();
    if ($session === null || empty($session['seed_pending'])) {
        return ['success' => false, 'message' => 'No follow-up cart to seed.'];
    }

    require_once __DIR__ . '/../models/posorder/order.php';
    require_once __DIR__ . '/pos_local_checkout_order.php';

    $sourceOrderNumber = trim((string) ($session['source_order_number'] ?? ''));
    $lineIds = is_array($session['line_ids'] ?? null) ? $session['line_ids'] : [];
    $orderModel = new POSOrder($conn);
    $orderLines = $orderModel->getOrderByOrderNumber($sourceOrderNumber);
    if (!is_array($orderLines)) {
        return ['success' => false, 'message' => 'Source order lines not found.'];
    }

    $filtered = order_follow_up_filter_order_lines($orderLines, $lineIds);
    if ($filtered === []) {
        return ['success' => false, 'message' => 'No lines to add to cart.'];
    }

    $customReduce = 0.0;
    $pricingMode = strtolower(trim((string) ($session['pricing_mode'] ?? 'catalog')));
    $snapshot = is_array($session['source_pricing_snapshot'] ?? null) ? $session['source_pricing_snapshot'] : [];
    $targetPayable = (float) ($snapshot['payable_total'] ?? 0);

    $rebuild = pos_local_checkout_rebuild_exotic_cart_from_order_lines($client, $filtered, $customReduce, $conn);
    if (empty($rebuild['success'])) {
        return [
            'success' => false,
            'message' => (string) ($rebuild['message'] ?? 'Could not rebuild cart from order lines.'),
        ];
    }

    $cartData = is_array($rebuild['cart_data'] ?? null) ? $rebuild['cart_data'] : [];

    if ($pricingMode === 'same_as_original' && $targetPayable > 0) {
        $cartTotal = order_follow_up_extract_cart_payable_total($cartData);
        if ($cartTotal > $targetPayable + 0.02) {
            $customReduce = round($cartTotal - $targetPayable, 2);
            require_once __DIR__ . '/../integrations/exotic/Support/CartResponseParser.php';
            $client->call('/cart/addcustomdiscount', 'GET', [
                'custom_reduce' => number_format($customReduce, 2, '.', ''),
            ]);
            $_SESSION['pos_exotic_cart_custom_reduce'] = number_format($customReduce, 2, '.', '');
            $retrieve = $client->call('/cart/retrieve', 'GET', []);
            if (CartResponseParser::isSuccess($retrieve) && is_array($retrieve['data'] ?? null)) {
                $cartData = $retrieve['data'];
            }
        }
        $_SESSION[POS_FOLLOW_UP_SESSION_KEY]['target_payable'] = $targetPayable;
        $_SESSION[POS_FOLLOW_UP_SESSION_KEY]['custom_reduce'] = $customReduce;
    }

    $_SESSION[POS_FOLLOW_UP_SESSION_KEY]['seed_pending'] = false;

    $updatedSession = order_follow_up_get_session() ?? $session;

    return [
        'success' => true,
        'message' => 'Cart loaded from source order.',
        'cart_data' => $cartData,
        'follow_up' => order_follow_up_public_session_view($updatedSession),
    ];
}

/**
 * @param array<string, mixed> $session
 * @return array<string, mixed>
 */
function order_follow_up_public_session_view(array $session): array
{
    return [
        'source_order_number' => (string) ($session['source_order_number'] ?? ''),
        'follow_up_type' => (string) ($session['follow_up_type'] ?? ''),
        'follow_up_type_label' => order_follow_up_type_label((string) ($session['follow_up_type'] ?? '')),
        'pricing_mode' => (string) ($session['pricing_mode'] ?? ''),
        'pricing_mode_label' => order_follow_up_pricing_mode_label((string) ($session['pricing_mode'] ?? '')),
        'scope' => (string) ($session['scope'] ?? 'full'),
        'pos_line_prices' => is_array($session['pos_line_prices'] ?? null) ? $session['pos_line_prices'] : [],
        'target_payable' => (float) ($session['target_payable'] ?? 0),
        'source_payable_total' => (float) (($session['source_pricing_snapshot']['payable_total'] ?? 0)),
    ];
}

function order_follow_up_is_waived_checkout(?array $session = null): bool
{
    $session = $session ?? order_follow_up_get_session();
    if (!is_array($session)) {
        return false;
    }

    $type = strtolower(trim((string) ($session['follow_up_type'] ?? '')));
    $pricingMode = strtolower(trim((string) ($session['pricing_mode'] ?? '')));

    if (!in_array($type, ['reship', 'replace'], true)) {
        return false;
    }

    return $pricingMode === 'waived';
}

/**
 * @return array{success:bool, message:string, id?:int}
 */
function order_follow_up_finalize_link(
    mysqli $conn,
    string $followUpOrderNumber,
    float $followUpPayableTotal,
    string $receiptNumber = '',
    int $followUpInvoiceId = 0
): array {
    $session = order_follow_up_get_session();
    if ($session === null) {
        return ['success' => false, 'message' => 'Follow-up session expired.'];
    }

    $model = new OrderFollowUp($conn);
    $snapshot = is_array($session['source_pricing_snapshot'] ?? null) ? $session['source_pricing_snapshot'] : [];

    $result = $model->insertLink([
        'source_order_number' => (string) ($session['source_order_number'] ?? ''),
        'follow_up_order_number' => trim($followUpOrderNumber),
        'follow_up_type' => (string) ($session['follow_up_type'] ?? 'copy'),
        'pricing_mode' => (string) ($session['pricing_mode'] ?? 'catalog'),
        'scope' => (string) ($session['scope'] ?? 'full'),
        'sales_return_id' => (int) ($session['sales_return_id'] ?? 0),
        'source_invoice_id' => (int) ($snapshot['invoice_id'] ?? 0),
        'follow_up_invoice_id' => $followUpInvoiceId,
        'source_payable_total' => (float) ($snapshot['payable_total'] ?? 0),
        'follow_up_payable_total' => round($followUpPayableTotal, 2),
        'receipt_number' => $receiptNumber,
        'source_pricing_json' => $snapshot,
        'remarks' => order_follow_up_type_label((string) ($session['follow_up_type'] ?? ''))
            . ' from #' . (string) ($session['source_order_number'] ?? ''),
        'created_by' => (int) ($_SESSION['user']['id'] ?? 0),
    ]);

    order_follow_up_clear_session();

    return $result;
}

/**
 * @return array{outbound:list<array<string,mixed>>, inbound:array<string,mixed>|null}
 */
function order_follow_up_links_for_order(mysqli $conn, string $orderNumber): array
{
    $orderNumber = trim($orderNumber);
    $model = new OrderFollowUp($conn);

    return [
        'outbound' => $model->getFollowUpsForSource($orderNumber),
        'inbound' => $model->getLinkForFollowUpOrder($orderNumber),
    ];
}

function order_follow_up_order_details_url(string $orderNumber, string $page = 'posorders'): string
{
    $page = in_array($page, ['orders', 'posorders'], true) ? $page : 'posorders';

    return base_url(
        'index.php?page=' . $page . '&action=get_order_details_html&type=outer&order_number=' . rawurlencode(trim($orderNumber))
    );
}

/**
 * @param list<array<string, mixed>> $orderLines
 * @return list<int>
 */
function order_follow_up_get_returned_line_ids(?mysqli $conn, string $sourceOrderNumber, array $orderLines = []): array
{
    $sourceOrderNumber = trim($sourceOrderNumber);
    $returnedIds = [];

    foreach ($orderLines as $line) {
        if (!is_array($line)) {
            continue;
        }
        $lineId = (int) ($line['id'] ?? 0);
        if ($lineId <= 0) {
            continue;
        }
        $status = strtolower(trim((string) ($line['status'] ?? '')));
        if ($status === 'returned') {
            $returnedIds[$lineId] = true;
        }
    }

    if ($conn instanceof mysqli && $sourceOrderNumber !== '') {
        $stmt = $conn->prepare(
            "SELECT DISTINCT ri.order_row_id
             FROM vp_sales_return_items ri
             INNER JOIN vp_sales_returns r ON r.id = ri.sales_return_id
             WHERE r.order_number = ? AND r.status = 'finalized' AND ri.order_row_id IS NOT NULL AND ri.order_row_id > 0"
        );
        if ($stmt) {
            $stmt->bind_param('s', $sourceOrderNumber);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && ($row = $res->fetch_assoc())) {
                $rowId = (int) ($row['order_row_id'] ?? 0);
                if ($rowId > 0) {
                    $returnedIds[$rowId] = true;
                }
            }
            $stmt->close();
        }
    }

    $out = array_map('intval', array_keys($returnedIds));
    sort($out);

    return $out;
}
