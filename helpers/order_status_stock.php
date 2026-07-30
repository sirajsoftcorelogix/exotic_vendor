<?php

require_once __DIR__ . '/order_cancel_invoice.php';

/**
 * Restore warehouse stock when an order line moves to cancelled / cancelled_returned / returned.
 *
 * @return array<string, mixed>
 */
function order_handle_status_change_stock(mysqli $conn, array $orderRow, string $newStatus, string $previousStatus): array
{
    if (!order_status_triggers_stock_restore($newStatus)) {
        return ['attempted' => false];
    }

    if (order_status_triggers_stock_restore($previousStatus)) {
        return [
            'attempted' => false,
            'message' => 'Order line already in a stock-restore status.',
        ];
    }

    $invoiceCancel = null;
    if (is_order_status_cancelled($newStatus)) {
        $invoiceCancel = order_cancel_linked_invoice_for_order_row($conn, $orderRow);
        $invoiceStockApplied = (int) (($invoiceCancel['stock_restore']['applied'] ?? 0));
        if (
            !empty($invoiceCancel['attempted'])
            && !empty($invoiceCancel['success'])
            && $invoiceStockApplied > 0
        ) {
            return [
                'attempted' => true,
                'via' => 'invoice',
                'success' => true,
                'message' => (string) ($invoiceCancel['message'] ?? 'Stock restored via linked invoice cancel.'),
                'invoice_cancel' => $invoiceCancel,
            ];
        }
    }

    require_once __DIR__ . '/../models/order/stock.php';
    $stockModel = new Stock($conn);
    $statusSlug = strtolower(trim($newStatus));
    $refType = in_array($statusSlug, ['returned', 'cancelled_returned'], true)
        ? 'ORDER_RETURN'
        : 'ORDER_CANCEL';
    $orderStockRestore = $stockModel->restoreStockByOrderRow($orderRow, $refType);

    $success = !empty($orderStockRestore['success']);
    $applied = (int) ($orderStockRestore['applied'] ?? 0);

    return [
        'attempted' => true,
        'via' => 'order_row',
        'success' => $success,
        'message' => (string) ($orderStockRestore['message'] ?? ''),
        'applied' => $applied,
        'invoice_cancel' => $invoiceCancel,
        'order_stock_restore' => $orderStockRestore,
    ];
}

/**
 * @param array<string, mixed>|null $stockResult
 */
function order_status_stock_summary_message(?array $stockResult): string
{
    if (!is_array($stockResult) || empty($stockResult['attempted'])) {
        return '';
    }

    if (!empty($stockResult['success'])) {
        if (($stockResult['via'] ?? '') === 'invoice') {
            return ' Stock restored via linked invoice cancel.';
        }
        $applied = (int) ($stockResult['applied'] ?? $stockResult['order_stock_restore']['applied'] ?? 0);
        if ($applied > 0) {
            return ' Stock increased by order quantity (' . $applied . ' movement(s)).';
        }

        return ' Stock restore skipped (already applied or no prior stock OUT).';
    }

    $message = trim((string) ($stockResult['message'] ?? ''));
    if ($message === '') {
        $message = trim((string) ($stockResult['order_stock_restore']['message'] ?? ''));
    }

    return $message !== '' ? ' Stock restore failed: ' . $message : ' Stock restore failed.';
}
