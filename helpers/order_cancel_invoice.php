<?php

function is_order_status_cancelled(string $status): bool
{
    return strtolower(trim($status)) === 'cancelled';
}

function order_cancel_resolve_invoice_id_for_row(mysqli $conn, array $orderRow): int
{
    $invoiceId = (int) ($orderRow['invoice_id'] ?? 0);
    if ($invoiceId > 0) {
        return $invoiceId;
    }

    $orderNumber = trim((string) ($orderRow['order_number'] ?? ''));
    if ($orderNumber === '') {
        return 0;
    }

    require_once __DIR__ . '/../models/PosInvoice/invoice.php';
    $invoiceModel = new POSInvoice($conn);
    $invoice = $invoiceModel->getActiveInvoiceForOrderNumber($orderNumber);

    return (int) ($invoice['id'] ?? 0);
}

/**
 * Cancel vp_invoices row: shipments, stock restore, status cancelled (same as dispatch cancel).
 *
 * @return array{success:bool, attempted:bool, invoice_id:int, message:string, stock_restore?:array<string,mixed>}
 */
function order_cancel_vp_invoice_by_id(mysqli $conn, int $invoiceId): array
{
    $invoiceId = (int) $invoiceId;
    if ($invoiceId <= 0) {
        return [
            'success' => true,
            'attempted' => false,
            'invoice_id' => 0,
            'message' => '',
        ];
    }

    require_once __DIR__ . '/../models/PosInvoice/invoice.php';
    require_once __DIR__ . '/../models/dispatch/dispatch.php';
    require_once __DIR__ . '/../models/order/stock.php';
    require_once __DIR__ . '/../models/comman/tables.php';

    $invoiceModel = new POSInvoice($conn);
    $invoice = $invoiceModel->getInvoiceById($invoiceId);
    if (!$invoice) {
        return [
            'success' => false,
            'attempted' => true,
            'invoice_id' => $invoiceId,
            'message' => 'Linked invoice not found.',
        ];
    }

    $invStatus = strtolower(trim((string) ($invoice['status'] ?? '')));
    if ($invStatus === 'cancelled') {
        return [
            'success' => true,
            'attempted' => false,
            'invoice_id' => $invoiceId,
            'message' => 'Invoice already cancelled.',
        ];
    }

    $dispatchModel = new Dispatch($conn);
    $commanModel = new Tables($conn);

    try {
        foreach ($dispatchModel->getDispatchRecordsByInvoiceId($invoiceId) as $record) {
            $shiprocketOrderId = trim((string) ($record['shiprocket_order_id'] ?? ''));
            if ($shiprocketOrderId === '') {
                continue;
            }
            $response = $dispatchModel->cancelShiprocketShipment($shiprocketOrderId);
            if (empty($response['success'])) {
                return [
                    'success' => false,
                    'attempted' => true,
                    'invoice_id' => $invoiceId,
                    'message' => 'Failed to cancel shipment for dispatch ID '
                        . (int) ($record['id'] ?? 0)
                        . ': '
                        . (string) ($response['message'] ?? 'Unknown error'),
                ];
            }
            $commanModel->updateRecord('vp_dispatch_details', ['shipment_status' => 'cancelled'], (int) $record['id']);
        }

        $stockModel = new Stock($conn);
        $stockRestore = $stockModel->restoreStockByInvoiceId($invoiceId);
        if (empty($stockRestore['success'])) {
            return [
                'success' => false,
                'attempted' => true,
                'invoice_id' => $invoiceId,
                'message' => 'Invoice could not be cancelled — stock restore failed: '
                    . (string) ($stockRestore['message'] ?? 'unknown'),
                'stock_restore' => $stockRestore,
            ];
        }

        $invoiceModel->updateInvoiceStatus($invoiceId, 'cancelled');

        $clearStmt = $conn->prepare('UPDATE vp_orders SET invoice_id = NULL WHERE invoice_id = ?');
        if ($clearStmt) {
            $clearStmt->bind_param('i', $invoiceId);
            $clearStmt->execute();
            $clearStmt->close();
        }

        return [
            'success' => true,
            'attempted' => true,
            'invoice_id' => $invoiceId,
            'message' => 'Linked invoice cancelled.',
            'stock_restore' => $stockRestore,
        ];
    } catch (\Throwable $e) {
        error_log('[order cancel invoice] invoice ' . $invoiceId . ': ' . $e->getMessage());

        return [
            'success' => false,
            'attempted' => true,
            'invoice_id' => $invoiceId,
            'message' => 'Error cancelling linked invoice: ' . $e->getMessage(),
        ];
    }
}

/**
 * @return array{success:bool, attempted:bool, invoice_id:int, message:string, stock_restore?:array<string,mixed>}
 */
function order_cancel_linked_invoice_for_order_row(mysqli $conn, array $orderRow): array
{
    return order_cancel_vp_invoice_by_id($conn, order_cancel_resolve_invoice_id_for_row($conn, $orderRow));
}

/**
 * @param list<array<string, mixed>> $orderRows
 * @return list<array{success:bool, attempted:bool, invoice_id:int, message:string}>
 */
function order_cancel_linked_invoices_for_order_rows(mysqli $conn, array $orderRows): array
{
    $seen = [];
    $results = [];

    foreach ($orderRows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $invoiceId = order_cancel_resolve_invoice_id_for_row($conn, $row);
        if ($invoiceId <= 0 || isset($seen[$invoiceId])) {
            continue;
        }
        $seen[$invoiceId] = true;
        $results[] = order_cancel_vp_invoice_by_id($conn, $invoiceId);
    }

    return $results;
}

function order_cancel_invoice_summary_message(array $cancelResults): string
{
    $attempted = 0;
    $failed = 0;
    $firstError = '';

    foreach ($cancelResults as $result) {
        if (empty($result['attempted'])) {
            continue;
        }
        $attempted++;
        if (empty($result['success'])) {
            $failed++;
            if ($firstError === '') {
                $firstError = (string) ($result['message'] ?? 'Invoice cancel failed.');
            }
        }
    }

    if ($attempted === 0) {
        return '';
    }
    if ($failed === 0) {
        return $attempted === 1
            ? ' Linked invoice cancelled.'
            : ' ' . $attempted . ' linked invoices cancelled.';
    }

    return ' Linked invoice cancel failed: ' . $firstError;
}
