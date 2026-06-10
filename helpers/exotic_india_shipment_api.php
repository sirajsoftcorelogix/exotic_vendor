<?php

require_once __DIR__ . '/api_call_logger.php';
require_once __DIR__ . '/dispatch_courier_identity.php';

function exotic_india_shipment_api_base_url(): string
{
    $base = getenv('EXOTIC_INDIA_API_BASE');
    if ($base !== false && trim((string) $base) !== '') {
        return rtrim((string) $base, '/');
    }

    return 'https://www.exoticindia.com/api';
}

function exotic_india_shipment_api_headers(): array
{
    $apiKey = getenv('EXOTIC_INDIA_API_KEY');
    if ($apiKey === false || trim((string) $apiKey) === '') {
        $apiKey = 'K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9';
    }

    return [
        'Content-Type: application/json',
        'Accept: application/json',
        'x-api-key: ' . trim((string) $apiKey),
        'x-adminapitest: 1',
    ];
}

function exotic_india_format_api_date($value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    $ts = strtotime((string) $value);

    return $ts ? date('Y-m-d', $ts) : '';
}

/**
 * Log only when AWB is newly available (avoid duplicate posts on status-only updates).
 *
 * @param array<string, mixed> $before
 * @param array<string, mixed> $after
 */
function exotic_india_dispatch_should_log_shipment(array $before, array $after): bool
{
    $awb = trim((string) ($after['awb_code'] ?? ''));
    if ($awb === '') {
        return false;
    }

    $prev = trim((string) ($before['awb_code'] ?? ''));

    return $prev === '' || $prev !== $awb;
}

/**
 * @param array<string, mixed> $dispatch vp_dispatch_details row
 * @return array<string, mixed>|null
 */
function exotic_india_build_shipment_payload($conn, array $dispatch): ?array
{
    if (!($conn instanceof mysqli)) {
        return null;
    }

    $shipperId = (int) ($dispatch['shipper_id'] ?? 0);
    if ($shipperId <= 0) {
        $partnerModel = new CourierPartner($conn);
        $shipperId = (int) ($partnerModel->resolveShipperId(
            (string) ($dispatch['courier_name'] ?? ''),
            null
        ) ?? 0);
    }

    $trackingNumber = trim((string) ($dispatch['awb_code'] ?? ''));
    $dateShipped = exotic_india_format_api_date($dispatch['dispatch_date'] ?? '');
    if ($shipperId <= 0 || $trackingNumber === '' || $dateShipped === '') {
        return null;
    }

    $invoiceId = (int) ($dispatch['invoice_id'] ?? 0);
    if ($invoiceId <= 0) {
        return null;
    }

    $invoice = null;
    $stmt = $conn->prepare('SELECT id, invoice_number, invoice_date FROM vp_invoices WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $invoiceId);
        $stmt->execute();
        $invoice = $stmt->get_result()?->fetch_assoc();
        $stmt->close();
    }
    if (!$invoice) {
        return null;
    }

    $saleNo = (int) ($invoice['id'] ?? 0);
    $saleDate = exotic_india_format_api_date($invoice['invoice_date'] ?? '');

    $invoiceItems = [];
    $itemStmt = $conn->prepare('SELECT * FROM vp_invoice_items WHERE invoice_id = ?');
    if ($itemStmt) {
        $itemStmt->bind_param('i', $invoiceId);
        $itemStmt->execute();
        $res = $itemStmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $invoiceItems[] = $row;
        }
        $itemStmt->close();
    }

    $itemsMapById = [];
    $itemsMapByCode = [];
    foreach ($invoiceItems as $invItem) {
        $itemsMapById[(int) ($invItem['id'] ?? 0)] = $invItem;
        $itemsMapById[(string) ($invItem['id'] ?? '')] = $invItem;
        $itemsMapByCode[(string) ($invItem['item_code'] ?? '')] = $invItem;
    }

    $boxItemIds = array_filter(array_map('trim', explode(',', (string) ($dispatch['box_items'] ?? ''))));
    $dispatchOrderId = trim((string) ($dispatch['order_number'] ?? ''));
    $orderedItems = [];

    $appendItem = static function (array $invItem) use (
        &$orderedItems,
        $conn,
        $dispatchOrderId,
        $saleNo,
        $saleDate
    ): void {
        $itemCode = trim((string) ($invItem['item_code'] ?? ''));
        if ($itemCode === '') {
            return;
        }

        $orderId = trim((string) ($invItem['order_number'] ?? $dispatchOrderId));
        if ($orderId === '') {
            return;
        }

        $entry = [
            'itemcode' => $itemCode,
            'orderid' => $orderId,
            'qty' => max(1, (int) round((float) ($invItem['quantity'] ?? 1))),
        ];
        if ($saleNo > 0) {
            $entry['sale_no'] = $saleNo;
        }
        if ($saleDate !== '') {
            $entry['sale_date'] = $saleDate;
        }

        $size = '';
        $color = '';
        $orderStmt = $conn->prepare(
            'SELECT size, color FROM vp_orders WHERE order_number = ? AND item_code = ? LIMIT 1'
        );
        if ($orderStmt) {
            $orderStmt->bind_param('ss', $orderId, $itemCode);
            $orderStmt->execute();
            $orderRow = $orderStmt->get_result()?->fetch_assoc();
            $orderStmt->close();
            $size = trim((string) ($orderRow['size'] ?? ''));
            $color = trim((string) ($orderRow['color'] ?? ''));
        }
        if ($size !== '') {
            $entry['size'] = $size;
        }
        if ($color !== '') {
            $entry['color'] = $color;
        }

        $orderedItems[] = $entry;
    };

    if ($boxItemIds !== []) {
        foreach ($boxItemIds as $itemId) {
            $invItem = $itemsMapById[$itemId] ?? $itemsMapByCode[$itemId] ?? null;
            if (is_array($invItem)) {
                $appendItem($invItem);
            }
        }
    }

    if ($orderedItems === []) {
        $boxNo = (string) ($dispatch['box_no'] ?? '');
        foreach ($invoiceItems as $invItem) {
            if ($boxNo !== '' && (string) ($invItem['box_no'] ?? '') !== $boxNo) {
                continue;
            }
            $appendItem($invItem);
        }
    }

    if ($orderedItems === []) {
        foreach ($invoiceItems as $invItem) {
            $appendItem($invItem);
        }
    }

    if ($orderedItems === []) {
        return null;
    }

    return [
        'shipper_id' => (string) $shipperId,
        'tracking_number' => $trackingNumber,
        'date_shipped' => $dateShipped,
        'ordered_items' => $orderedItems,
    ];
}

/**
 * @param array<string, mixed> $payload
 * @return array{success:bool,message:string,http_code:int,data?:array,raw?:string}
 */
function exotic_india_post_shipment_add(array $payload): array
{
    $url = exotic_india_shipment_api_base_url() . '/order/shipment-add';
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return ['success' => false, 'message' => 'Could not encode shipment payload.', 'http_code' => 0];
    }

    $headers = exotic_india_shipment_api_headers();
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        if (defined('API_CALL_LOG_ENABLED') && API_CALL_LOG_ENABLED) {
            api_call_log_write([
                'kind' => 'exotic_shipment_add',
                'endpoint' => '/order/shipment-add',
                'method' => 'POST',
                'request_url' => $url,
                'request_headers' => api_call_log_sanitize_header_lines($headers),
                'request_post_body' => $json,
                'curl_error' => $curlError,
                'response_http_code' => $httpCode,
            ]);
        }

        return [
            'success' => false,
            'message' => 'Shipment API call failed: ' . $curlError,
            'http_code' => $httpCode,
        ];
    }

    $decoded = json_decode((string) $raw, true);
    $data = is_array($decoded) ? $decoded : [];

    if (defined('API_CALL_LOG_ENABLED') && API_CALL_LOG_ENABLED) {
        api_call_log_write([
            'kind' => 'exotic_shipment_add',
            'endpoint' => '/order/shipment-add',
            'method' => 'POST',
            'request_url' => $url,
            'request_headers' => api_call_log_sanitize_header_lines($headers),
            'request_post_body' => $json,
            'response_http_code' => $httpCode,
            'response_raw' => (string) $raw,
            'response_decoded' => $data,
        ]);
    }

    $failed = $httpCode >= 400
        || (isset($data['success']) && $data['success'] === false)
        || (isset($data['status']) && in_array(strtolower((string) $data['status']), ['error', 'failed'], true));

    if ($failed) {
        $message = trim((string) ($data['message'] ?? $data['error'] ?? ''));
        if ($message === '') {
            $message = 'HTTP ' . $httpCode;
        }

        return [
            'success' => false,
            'message' => $message,
            'http_code' => $httpCode,
            'data' => $data,
            'raw' => (string) $raw,
        ];
    }

    return [
        'success' => true,
        'message' => trim((string) ($data['message'] ?? 'Shipment logged successfully.')),
        'http_code' => $httpCode,
        'data' => $data,
        'raw' => (string) $raw,
    ];
}

/**
 * @param array<string, mixed> $before prior dispatch row (empty on create)
 * @return array{success:bool,message:string,skipped?:bool,http_code?:int,data?:array}
 */
function exotic_india_log_dispatch_shipment($conn, int $dispatchId, array $before = []): array
{
    if ($dispatchId <= 0 || !($conn instanceof mysqli)) {
        return ['success' => false, 'message' => 'Invalid dispatch id.'];
    }

    $stmt = $conn->prepare('SELECT * FROM vp_dispatch_details WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return ['success' => false, 'message' => 'Could not load dispatch record.'];
    }
    $stmt->bind_param('i', $dispatchId);
    $stmt->execute();
    $dispatch = $stmt->get_result()?->fetch_assoc();
    $stmt->close();

    if (!$dispatch) {
        return ['success' => false, 'message' => 'Dispatch record not found.'];
    }

    if (!exotic_india_dispatch_should_log_shipment($before, $dispatch)) {
        return ['success' => true, 'message' => 'Shipment already logged or AWB not ready.', 'skipped' => true];
    }

    $payload = exotic_india_build_shipment_payload($conn, $dispatch);
    if ($payload === null) {
        return ['success' => false, 'message' => 'Missing shipper, AWB, invoice, or line items for shipment-add.'];
    }

    return exotic_india_post_shipment_add($payload);
}
