<?php

/**
 * Warehouse address from exotic_address (DB only).
 * Rendered in the invoice header below the company name.
 *
 * @param array<string, mixed> $invoice
 * @param list<array<string, mixed>> $items
 */
function invoice_resolve_exclusive_stores_footer_html(
    array $invoice,
    array $items,
    Tables $commanModel,
    ?Payment $paymentModel = null
): string {
    $warehouseId = (int)($invoice['warehouse_id'] ?? 0);

    if (!empty($invoice['pos_flag']) && $paymentModel instanceof Payment) {
        $orderNumber = trim((string)($items[0]['order_number'] ?? ''));
        if ($orderNumber !== '') {
            $paymentWarehouseId = $paymentModel->getWarehouseIdForOrder($orderNumber);
            if ($paymentWarehouseId > 0) {
                $warehouseId = $paymentWarehouseId;
            }
        }
    }

    $warehouse = $commanModel->get_exotic_address_for_footer($warehouseId);
    $body = '';
    if (is_array($warehouse)) {
        $body = trim((string)($warehouse['address'] ?? ''));
    }

    if ($body === '') {
        return '';
    }

    $body = preg_replace('/^Exclusive Stores\s*\R?/i', '', $body);
    $body = trim($body);
    if ($body === '') {
        return '';
    }

    return nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
}
