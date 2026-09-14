<?php

use Com\Tecnick\Barcode\Barcode;

/**
 * Resolve generated IRN details (IRN string, Ack No, Ack Date, QR Code string) for an invoice.
 * Checks both vp_domestic_ewb_irn (domestic) and vp_invoices_international (export).
 *
 * @param array<string, mixed> $invoice
 * @param mysqli|null $conn
 * @return array{irn: string, ack_number: string, ack_date: string, qrcode_string: string, qr_data_uri: string}
 */
function invoice_resolve_irn_details(array $invoice, ?mysqli $conn = null): array
{
    $empty = [
        'irn' => '',
        'ack_number' => '',
        'ack_date' => '',
        'qrcode_string' => '',
        'qr_data_uri' => '',
    ];

    if (!$conn) {
        $conn = $GLOBALS['conn'] ?? null;
    }
    if (!$conn instanceof mysqli) {
        return $empty;
    }

    $invoiceId = (int)($invoice['id'] ?? 0);
    if ($invoiceId <= 0) {
        return $empty;
    }

    $irn = trim((string)($invoice['irn'] ?? ''));
    $ackNum = trim((string)($invoice['ack_number'] ?? ''));
    $ackDt = trim((string)($invoice['ack_date'] ?? ''));
    $qrString = trim((string)($invoice['qrcode_string'] ?? ''));

    // 1. Check vp_domestic_ewb_irn (domestic E-Invoicing)
    $stmt = $conn->prepare("SELECT irn, irn_response FROM vp_domestic_ewb_irn WHERE vp_invoices_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $invoiceId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            if ($irn === '' && !empty($row['irn'])) {
                $irn = trim((string)$row['irn']);
            }
            if (!empty($row['irn_response'])) {
                $resp = json_decode($row['irn_response'], true);
                if (is_array($resp)) {
                    if ($irn === '') {
                        $irn = trim((string)($resp['Irn'] ?? $resp['irn'] ?? $resp['InfoDtls'][0]['Desc']['Irn'] ?? ''));
                    }
                    if ($qrString === '') {
                        $qrString = trim((string)($resp['SignedQRCode'] ?? $resp['SignedQrCode'] ?? $resp['signed_qr_code'] ?? $resp['qr_code'] ?? ''));
                    }
                    if ($ackNum === '') {
                        $ackNum = trim((string)($resp['AckNo'] ?? $resp['ack_no'] ?? $resp['ack_number'] ?? ''));
                    }
                    if ($ackDt === '') {
                        $ackDt = trim((string)($resp['AckDt'] ?? $resp['ack_date'] ?? ''));
                    }
                }
            }
        }
        $stmt->close();
    }

    // 2. Check vp_invoices_international (export E-Invoicing)
    if ($irn === '' || $qrString === '') {
        $stmtIntl = $conn->prepare("SELECT irn, ack_number, ack_date, qrcode_string FROM vp_invoices_international WHERE invoice_id = ? LIMIT 1");
        if ($stmtIntl) {
            $stmtIntl->bind_param('i', $invoiceId);
            $stmtIntl->execute();
            $resIntl = $stmtIntl->get_result();
            if ($resIntl && $rowIntl = $resIntl->fetch_assoc()) {
                if ($irn === '' && !empty($rowIntl['irn'])) {
                    $irn = trim((string)$rowIntl['irn']);
                }
                if ($qrString === '' && !empty($rowIntl['qrcode_string'])) {
                    $qrString = trim((string)$rowIntl['qrcode_string']);
                }
                if ($ackNum === '' && !empty($rowIntl['ack_number'])) {
                    $ackNum = trim((string)$rowIntl['ack_number']);
                }
                if ($ackDt === '' && !empty($rowIntl['ack_date'])) {
                    $ackDt = trim((string)$rowIntl['ack_date']);
                }
            }
            $stmtIntl->close();
        }
    }

    if ($irn === '') {
        return $empty;
    }

    // Generate PNG Data URI for QR code if qrcode_string or IRN is present
    $qrDataUri = '';
    $textToEncode = $qrString !== '' ? $qrString : $irn;

    if (class_exists('Com\Tecnick\Barcode\Barcode')) {
        try {
            $barcode = new Barcode();
            $bobj = $barcode->getBarcodeObj(
                'QRCODE,M',
                $textToEncode,
                -4,
                -4,
                'black',
                [0, 0, 0, 0]
            );
            if (function_exists('imagecreate')) {
                $png = $bobj->getPngData(false);
                if ($png !== '') {
                    $qrDataUri = 'data:image/png;base64,' . base64_encode($png);
                }
            }
            if ($qrDataUri === '') {
                $svg = $bobj->getSvgCode();
                if ($svg !== '') {
                    $qrDataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);
                }
            }
        } catch (Throwable $e) {
            error_log('invoice_resolve_irn_details QR generation error: ' . $e->getMessage());
        }
    }

    return [
        'irn' => $irn,
        'ack_number' => $ackNum,
        'ack_date' => $ackDt,
        'qrcode_string' => $qrString,
        'qr_data_uri' => $qrDataUri,
    ];
}
