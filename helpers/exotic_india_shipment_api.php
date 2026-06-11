<?php

require_once __DIR__ . '/api_call_logger.php';
require_once __DIR__ . '/dispatch_courier_identity.php';
require_once __DIR__ . '/exotic_india_api.php';

/**
 * Map vp_invoices.invoice_number to Exotic India sale_no (numeric series, not internal id).
 */
function exotic_india_resolve_sale_no(string $invoiceNumber): int
{
    $invoiceNumber = trim($invoiceNumber);
    if ($invoiceNumber === '') {
        return 0;
    }

    $parts = explode('-', $invoiceNumber);
    $seriesPart = trim((string) end($parts));
    if ($seriesPart !== '' && ctype_digit($seriesPart)) {
        return (int) $seriesPart;
    }

    if (ctype_digit($invoiceNumber)) {
        return (int) $invoiceNumber;
    }

    return 0;
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
    if (trim((string) ($after['exotic_shipment_id'] ?? '')) !== '') {
        return false;
    }

    $awb = trim((string) ($after['awb_code'] ?? ''));
    if ($awb === '') {
        return false;
    }

    $prev = trim((string) ($before['awb_code'] ?? ''));

    return $prev === '' || $prev !== $awb;
}

/**
 * @param array<string, mixed> $data Decoded API response body
 */
function exotic_india_extract_shipment_id(array $data): string
{
    foreach (['shipment_id', 'shipmentId'] as $key) {
        $value = trim((string) ($data[$key] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function exotic_india_save_dispatch_shipment_id($conn, int $dispatchId, string $shipmentId): bool
{
    $shipmentId = trim($shipmentId);
    if ($dispatchId <= 0 || $shipmentId === '' || !($conn instanceof mysqli)) {
        return false;
    }

    $stmt = $conn->prepare(
        'UPDATE vp_dispatch_details SET exotic_shipment_id = ? WHERE id = ? LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('si', $shipmentId, $dispatchId);
    $ok = $stmt->execute();
    $stmt->close();

    return (bool) $ok;
}

/**
 * @param array<string, mixed> $apiResult Result from exotic_india_post_shipment_add()
 * @return array<string, mixed>
 */
function exotic_india_persist_shipment_add_result($conn, int $dispatchId, array $apiResult): array
{
    if ($dispatchId <= 0 || empty($apiResult['success'])) {
        return $apiResult;
    }

    $data = is_array($apiResult['data'] ?? null) ? $apiResult['data'] : [];
    $shipmentId = exotic_india_extract_shipment_id($data);
    if ($shipmentId === '') {
        return $apiResult;
    }

    if (exotic_india_save_dispatch_shipment_id($conn, $dispatchId, $shipmentId)) {
        $apiResult['shipment_id'] = $shipmentId;
    }

    return $apiResult;
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

    $saleNo = exotic_india_resolve_sale_no((string) ($invoice['invoice_number'] ?? ''));
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

    $appendItem = static function (array $invItem) use (&$orderedItems, $dispatchOrderId): void {
        $itemCode = trim((string) ($invItem['item_code'] ?? ''));
        if ($itemCode === '') {
            return;
        }

        $orderId = trim((string) ($invItem['order_number'] ?? $dispatchOrderId));
        if ($orderId === '') {
            return;
        }

        $orderedItems[] = [
            'itemcode' => $itemCode,
            'orderid' => $orderId,
            'qty' => max(1, (int) round((float) ($invItem['quantity'] ?? 1))),
        ];
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

    if ($orderedItems === [] || $saleNo <= 0 || $saleDate === '') {
        return null;
    }

    return [
        'shipper_id' => (string) $shipperId,
        'tracking_number' => $trackingNumber,
        'date_shipped' => $dateShipped,
        'sale_no' => $saleNo,
        'sale_date' => $saleDate,
        'ordered_items' => $orderedItems,
    ];
}

/**
 * @param array<string, mixed> $payload
 * @return array{success:bool,message:string,http_code:int,data?:array,raw?:string,shipment_id?:string}
 */
function exotic_india_post_shipment_add(array $payload): array
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return ['success' => false, 'message' => 'Could not encode shipment payload.', 'http_code' => 0];
    }

    $result = exotic_india_api_post('/order/shipment-add', $json, [
        'Content-Type: application/json',
        'Accept: application/json',
    ]);

    if (defined('API_CALL_LOG_ENABLED') && API_CALL_LOG_ENABLED) {
        api_call_log_write([
            'kind' => 'exotic_shipment_add',
            'endpoint' => '/order/shipment-add',
            'method' => 'POST',
            'request_url' => $result['request_url'] ?? '',
            'request_headers' => api_call_log_sanitize_header_lines($result['request_headers'] ?? []),
            'request_post_body' => $json,
            'curl_error' => $result['curl_error'] ?? null,
            'response_http_code' => (int) ($result['http_code'] ?? 0),
            'response_raw' => (string) ($result['raw'] ?? ''),
            'response_decoded' => $result['data'] ?? [],
        ]);
    }

    if (empty($result['success'])) {
        return [
            'success' => false,
            'message' => (string) ($result['message'] ?? 'Shipment API call failed.'),
            'http_code' => (int) ($result['http_code'] ?? 0),
            'data' => $result['data'] ?? [],
            'raw' => (string) ($result['raw'] ?? ''),
        ];
    }

    $data = is_array($result['data'] ?? null) ? $result['data'] : [];
    $shipmentId = exotic_india_extract_shipment_id($data);
    $response = [
        'success' => true,
        'message' => trim((string) ($result['message'] ?? 'Shipment logged successfully.')),
        'http_code' => (int) ($result['http_code'] ?? 0),
        'data' => $data,
        'raw' => (string) ($result['raw'] ?? ''),
    ];
    if ($shipmentId !== '') {
        $response['shipment_id'] = $shipmentId;
    }

    return $response;
}

/**
 * @param array<string, mixed> $before prior dispatch row (empty on create)
 * @return array{success:bool,message:string,skipped?:bool,http_code?:int,data?:array,shipment_id?:string}
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

    $result = exotic_india_post_shipment_add($payload);

    return exotic_india_persist_shipment_add_result($conn, $dispatchId, $result);
}

/**
 * @param array<string, mixed> $dispatch
 * @return array{ready:bool,issues:list<string>,payload:?array,api_url:string}
 */
function exotic_india_shipment_add_preview($conn, array $dispatch): array
{
    $issues = [];

    if (trim((string) ($dispatch['awb_code'] ?? '')) === '') {
        $issues[] = 'AWB / tracking number (awb_code) is missing on this dispatch.';
    }

    $shipperId = (int) ($dispatch['shipper_id'] ?? 0);
    if ($shipperId <= 0 && $conn instanceof mysqli) {
        $partnerModel = new CourierPartner($conn);
        $shipperId = (int) ($partnerModel->resolveShipperId(
            (string) ($dispatch['courier_name'] ?? ''),
            null
        ) ?? 0);
    }
    if ($shipperId <= 0) {
        $issues[] = 'shipper_id is missing. Sync courier_partners or complete dispatch courier identity.';
    }

    if (exotic_india_format_api_date($dispatch['dispatch_date'] ?? '') === '') {
        $issues[] = 'dispatch_date is missing or invalid.';
    }

    if ((int) ($dispatch['invoice_id'] ?? 0) <= 0) {
        $issues[] = 'invoice_id is missing on dispatch.';
    }

    $payload = $conn instanceof mysqli ? exotic_india_build_shipment_payload($conn, $dispatch) : null;
    if ($payload === null && $issues === []) {
        $issues[] = 'Could not build shipment payload (check invoice sale_no/sale_date, box_items, or line items).';
    }

    return [
        'ready' => $payload !== null && $issues === [],
        'issues' => $issues,
        'payload' => $payload,
        'api_url' => exotic_india_api_base_url() . '/order/shipment-add',
    ];
}
