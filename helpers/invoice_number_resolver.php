<?php

/**
 * Resolve invoice_number from optional user override or global_settings series.
 *
 * @param list<string> $reservedNumbers Custom numbers already used in the same batch submit
 * @return array{success:bool, invoice_number?:string, message?:string}
 */
function resolve_invoice_number(mysqli $conn, string $customInvoiceNumber = '', array $reservedNumbers = []): array
{
    $customInvoiceNumber = trim($customInvoiceNumber);

    if ($customInvoiceNumber !== '') {
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._\/-]{0,49}$/', $customInvoiceNumber)) {
            return [
                'success' => false,
                'message' => 'Custom invoice number can contain letters, numbers, dot, slash, underscore, and hyphen only.',
            ];
        }

        $reserved = array_map(static fn(string $n): string => strtolower(trim($n)), $reservedNumbers);
        if (in_array(strtolower($customInvoiceNumber), $reserved, true)) {
            return [
                'success' => false,
                'message' => 'Custom invoice number already used for another order in this batch.',
            ];
        }

        $stmt = $conn->prepare('SELECT id FROM vp_invoices WHERE invoice_number = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $customInvoiceNumber);
            $stmt->execute();
            $existing = $stmt->get_result()?->fetch_assoc();
            $stmt->close();
            if (!empty($existing['id'])) {
                return ['success' => false, 'message' => 'Custom invoice number already exists.'];
            }
        }

        return ['success' => true, 'invoice_number' => $customInvoiceNumber];
    }

    $stmt = $conn->prepare('SELECT invoice_prefix, invoice_series FROM global_settings WHERE id = 1 LIMIT 1');
    if (!$stmt) {
        return ['success' => false, 'message' => 'Could not read invoice series settings.'];
    }
    $stmt->execute();
    $settings = $stmt->get_result()?->fetch_assoc();
    $stmt->close();

    $invoicePrefix = is_array($settings) ? (string) ($settings['invoice_prefix'] ?? 'INV') : 'INV';
    $invoiceSeries = is_array($settings) ? (int) ($settings['invoice_series'] ?? 0) : 0;
    $invoiceSeries++;

    $update = $conn->prepare('UPDATE global_settings SET invoice_series = ? WHERE id = 1');
    if (!$update) {
        return ['success' => false, 'message' => 'Could not update invoice series.'];
    }
    $update->bind_param('i', $invoiceSeries);
    if (!$update->execute()) {
        $update->close();

        return ['success' => false, 'message' => 'Could not update invoice series.'];
    }
    $update->close();

    return [
        'success' => true,
        'invoice_number' => $invoicePrefix . '-' . str_pad((string) $invoiceSeries, 6, '0', STR_PAD_LEFT),
    ];
}
