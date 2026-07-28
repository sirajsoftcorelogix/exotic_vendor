<?php

/**
 * Live vendor-api/order/fetch for investigation UI and checkout import.
 */

function order_json_fetch_client(): OrderClient
{
    static $client = null;
    if ($client === null) {
        require_once __DIR__ . '/../integrations/exotic/Clients/OrderClient.php';
        $client = new OrderClient();
    }

    return $client;
}

function order_json_fetch_assert_access(): void
{
    is_login();
    require_once __DIR__ . '/html_helpers.php';
    $userId = (int) ($_SESSION['user']['id'] ?? 0);
    if (!hasTieredAccess($userId, 'Sr Emp Access', ['Orders', 'POS Orders'])) {
        order_json_fetch_send_json(['success' => false, 'message' => 'Access denied.'], 403);
    }
}

/**
 * @param array<string, mixed>|null $payload
 */
function order_json_fetch_resolve_order_number(?array $payload = null): string
{
    $candidates = [
        $_GET['order_number'] ?? null,
        $_POST['order_number'] ?? null,
        $_GET['orderid'] ?? null,
        $_POST['orderid'] ?? null,
    ];

    if (is_array($payload)) {
        $candidates[] = $payload['order_number'] ?? null;
        $candidates[] = $payload['orderid'] ?? null;
    }

    foreach ($candidates as $candidate) {
        $n = trim((string) $candidate);
        if ($n !== '') {
            return $n;
        }
    }

    if ($payload !== null) {
        return '';
    }

    $raw = (string) file_get_contents('php://input');
    if ($raw === '') {
        return !empty($_POST) && is_array($_POST)
            ? order_json_fetch_resolve_order_number($_POST)
            : '';
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? order_json_fetch_resolve_order_number($decoded) : '';
}

/**
 * @param array<string, mixed> $payload
 */
function order_json_fetch_send_json(array $payload, int $httpCode = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($httpCode);
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

/**
 * Checkout / refresh import shape used by OrdersController.
 *
 * @return array{ok: bool, orders: list<array<string, mixed>>, error: string}
 */
function order_json_fetch_checkout_payload(string $orderNumber): array
{
    $result = order_json_fetch_client()->fetchOrderByNumber($orderNumber);
    if (empty($result['success'])) {
        return [
            'ok' => false,
            'orders' => [],
            'error' => (string) ($result['message'] ?? 'No order data from vendor API'),
        ];
    }

    return [
        'ok' => true,
        'orders' => $result['orders'],
        'error' => '',
    ];
}

/**
 * On-demand live JSON (investigation only; does not import).
 */
function order_json_fetch_handle_ajax(): void
{
    order_json_fetch_assert_access();

    $orderNumber = order_json_fetch_resolve_order_number();
    if ($orderNumber === '') {
        order_json_fetch_send_json([
            'success' => false,
            'message' => 'Order number is required.',
        ], 400);
    }

    try {
        $result = order_json_fetch_client()->fetchOrderByNumber($orderNumber);
        if (empty($result['success'])) {
            order_json_fetch_send_json([
                'success' => false,
                'message' => (string) ($result['message'] ?? 'No order data from vendor API'),
                'order_number' => $orderNumber,
                'response' => $result['data'] ?? null,
                'response_raw' => !empty($result['raw']) ? $result['raw'] : null,
            ], 502);
        }

        require_once __DIR__ . '/../integrations/exotic/Support/VendorOrderFetchParser.php';
        $decoded = is_array($result['data'] ?? null) ? $result['data'] : [];
        order_json_fetch_send_json([
            'success' => true,
            'order_number' => $orderNumber,
            'fetched_at' => date('c'),
            'order' => VendorOrderFetchParser::findOrder($decoded, $orderNumber),
            'response' => $decoded,
        ]);
    } catch (\Throwable $e) {
        error_log('[fetch_order_json] ' . $e->getMessage());
        order_json_fetch_send_json([
            'success' => false,
            'message' => 'Fetch failed: ' . $e->getMessage(),
        ], 500);
    }
}
