<?php

use Com\Tecnick\Barcode\Barcode;

/**
 * Extract QR code string from response / payload JSON.
 *
 * @param mixed $rawJson
 * @return string
 */
function invoice_extract_qr_string_from_json($rawJson): string
{
    if (empty($rawJson)) {
        return '';
    }
    $data = is_array($rawJson) ? $rawJson : json_decode((string)$rawJson, true);
    if (!is_array($data)) {
        return '';
    }

    foreach (['SignedQRCode', 'SignedQrCode', 'signed_qr_code', 'qr_code', 'qrcode_string', 'SignedInvoice'] as $key) {
        if (!empty($data[$key]) && is_string($data[$key])) {
            return trim($data[$key]);
        }
    }

    if (!empty($data['InfoDtls']) && is_array($data['InfoDtls'])) {
        foreach ($data['InfoDtls'] as $info) {
            $desc = $info['Desc'] ?? null;
            if (is_string($desc)) {
                $decodedDesc = json_decode($desc, true);
                if (is_array($decodedDesc)) {
                    $desc = $decodedDesc;
                }
            }
            if (is_array($desc)) {
                foreach (['SignedQRCode', 'SignedQrCode', 'signed_qr_code', 'qr_code', 'qrcode_string'] as $key) {
                    if (!empty($desc[$key]) && is_string($desc[$key])) {
                        return trim($desc[$key]);
                    }
                }
            }
        }
    }

    return '';
}

/**
 * Extract IRN hex string from response / payload JSON.
 *
 * @param mixed $rawJson
 * @return string
 */
function invoice_extract_irn_from_json($rawJson): string
{
    if (empty($rawJson)) {
        return '';
    }
    $data = is_array($rawJson) ? $rawJson : json_decode((string)$rawJson, true);
    if (!is_array($data)) {
        return '';
    }

    foreach (['Irn', 'irn', 'IRN'] as $key) {
        if (!empty($data[$key]) && is_string($data[$key])) {
            return trim($data[$key]);
        }
    }

    if (!empty($data['InfoDtls']) && is_array($data['InfoDtls'])) {
        foreach ($data['InfoDtls'] as $info) {
            $desc = $info['Desc'] ?? null;
            if (is_string($desc)) {
                $decodedDesc = json_decode($desc, true);
                if (is_array($decodedDesc)) {
                    $desc = $decodedDesc;
                }
            }
            if (is_array($desc)) {
                foreach (['Irn', 'irn', 'IRN'] as $key) {
                    if (!empty($desc[$key]) && is_string($desc[$key])) {
                        return trim($desc[$key]);
                    }
                }
            }
        }
    }

    return '';
}

/**
 * Extract Ack Number and Ack Date from response / payload JSON.
 *
 * @param mixed $rawJson
 * @return array{ack_number: string, ack_date: string}
 */
function invoice_extract_ack_from_json($rawJson): array
{
    $ackNum = '';
    $ackDt = '';
    if (empty($rawJson)) {
        return ['ack_number' => '', 'ack_date' => ''];
    }
    $data = is_array($rawJson) ? $rawJson : json_decode((string)$rawJson, true);
    if (!is_array($data)) {
        return ['ack_number' => '', 'ack_date' => ''];
    }

    foreach (['AckNo', 'ack_no', 'ack_number'] as $k) {
        if (!empty($data[$k])) {
            $ackNum = (string)$data[$k];
            break;
        }
    }
    foreach (['AckDt', 'ack_dt', 'ack_date'] as $k) {
        if (!empty($data[$k])) {
            $ackDt = (string)$data[$k];
            break;
        }
    }

    if (!empty($data['InfoDtls']) && is_array($data['InfoDtls'])) {
        foreach ($data['InfoDtls'] as $info) {
            $desc = $info['Desc'] ?? null;
            if (is_string($desc)) {
                $decodedDesc = json_decode($desc, true);
                if (is_array($decodedDesc)) {
                    $desc = $decodedDesc;
                }
            }
            if (is_array($desc)) {
                if ($ackNum === '') {
                    foreach (['AckNo', 'ack_no', 'ack_number'] as $k) {
                        if (!empty($desc[$k])) {
                            $ackNum = (string)$desc[$k];
                            break;
                        }
                    }
                }
                if ($ackDt === '') {
                    foreach (['AckDt', 'ack_dt', 'ack_date'] as $k) {
                        if (!empty($desc[$k])) {
                            $ackDt = (string)$desc[$k];
                            break;
                        }
                    }
                }
            }
        }
    }

    return ['ack_number' => $ackNum, 'ack_date' => $ackDt];
}

/**
 * Resolve generated IRN details (IRN string, Ack No, Ack Date, QR Code string) for an invoice.
 * Checks both vp_domestic_ewb_irn (domestic) and vp_invoices_international (export).
 *
 * @param array<string, mixed> $invoice
 * @param mysqli|null $conn
 * @return array{irn: string, ack_number: string, ack_date: string, qrcode_string: string, qr_data_uri: string, qr_svg: string, qr_api_url: string}
 */
function invoice_resolve_irn_details(array $invoice, ?mysqli $conn = null): array
{
    $empty = [
        'irn' => '',
        'ack_number' => '',
        'ack_date' => '',
        'qrcode_string' => '',
        'qr_data_uri' => '',
        'qr_svg' => '',
        'qr_api_url' => '',
    ];

    if (!$conn) {
        $conn = $GLOBALS['conn'] ?? null;
    }
    if (!$conn instanceof mysqli) {
        return $empty;
    }

    $invoiceId = (int)($invoice['id'] ?? 0);
    $vpOrderInfoId = (int)($invoice['vp_order_info_id'] ?? 0);
    $orderNumber = trim((string)($invoice['order_number'] ?? ''));
    $invoiceNumber = trim((string)($invoice['invoice_number'] ?? ''));

    $irn = trim((string)($invoice['irn'] ?? ''));
    $ackNum = trim((string)($invoice['ack_number'] ?? ''));
    $ackDt = trim((string)($invoice['ack_date'] ?? ''));
    $qrString = trim((string)($invoice['qrcode_string'] ?? ''));

    // 1. Search vp_domestic_ewb_irn by invoiceId, vpOrderInfoId, orderNumber, invoiceNumber
    if ($invoiceId > 0 || $vpOrderInfoId > 0 || $orderNumber !== '' || $invoiceNumber !== '') {
        $sql = "SELECT d.irn, d.irn_response, d.info_dtls, d.irn_payload
                FROM vp_domestic_ewb_irn d
                LEFT JOIN vp_invoices i ON i.id = d.vp_invoices_id
                LEFT JOIN vp_order_info oi ON oi.id = i.vp_order_info_id
                LEFT JOIN vp_invoice_items ii ON ii.invoice_id = i.id
                WHERE d.vp_invoices_id = ?
                   OR (i.vp_order_info_id > 0 AND i.vp_order_info_id = ?)
                   OR (? != '' AND oi.order_number = ?)
                   OR (? != '' AND i.invoice_number = ?)
                   OR (? != '' AND ii.order_number = ?)
                ORDER BY d.id DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param(
                'iissssss',
                $invoiceId,
                $vpOrderInfoId,
                $orderNumber,
                $orderNumber,
                $invoiceNumber,
                $invoiceNumber,
                $orderNumber,
                $orderNumber
            );
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                if ($irn === '' && !empty($row['irn'])) {
                    $irn = trim((string)$row['irn']);
                }
                if ($qrString === '') {
                    $qrString = invoice_extract_qr_string_from_json($row['irn_response'] ?? null);
                    if ($qrString === '') {
                        $qrString = invoice_extract_qr_string_from_json($row['info_dtls'] ?? null);
                    }
                    if ($qrString === '') {
                        $qrString = invoice_extract_qr_string_from_json($row['irn_payload'] ?? null);
                    }
                }
                if ($irn === '') {
                    $irn = invoice_extract_irn_from_json($row['irn_response'] ?? null);
                }
                $acks = invoice_extract_ack_from_json($row['irn_response'] ?? null);
                if ($ackNum === '' && !empty($acks['ack_number'])) {
                    $ackNum = $acks['ack_number'];
                }
                if ($ackDt === '' && !empty($acks['ack_date'])) {
                    $ackDt = $acks['ack_date'];
                }
            }
            $stmt->close();
        }
    }

    // 2. Search vp_invoices_international
    if ($irn === '' || $qrString === '') {
        if ($invoiceId > 0) {
            $stmtIntl = $conn->prepare("SELECT irn, ack_number, ack_date, qrcode_string, response_payload FROM vp_invoices_international WHERE invoice_id = ? LIMIT 1");
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
                    if ($qrString === '' && !empty($rowIntl['response_payload'])) {
                        $qrString = invoice_extract_qr_string_from_json($rowIntl['response_payload']);
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
    }

    if ($irn === '') {
        return $empty;
    }

    $qrDataUri = '';
    $qrSvg = '';
    $textToEncode = $qrString !== '' ? $qrString : $irn;

    if (class_exists('Com\Tecnick\Barcode\Barcode')) {
        try {
            $barcode = new Barcode();
            $bobj = $barcode->getBarcodeObj(
                'QRCODE,M',
                $textToEncode,
                -6,
                -6,
                'black',
                [0, 0, 0, 0]
            );
            $rawSvg = $bobj->getSvgCode();
            if ($rawSvg !== '') {
                $cleanSvg = preg_replace('/^<\?xml[^>]*\?>\s*/i', '', $rawSvg);
                $qrSvg = $cleanSvg;
                $qrDataUri = 'data:image/svg+xml;base64,' . base64_encode($rawSvg);
            }
        } catch (Throwable $e) {
            error_log('invoice_resolve_irn_details QR generation error: ' . $e->getMessage());
        }
    }

    $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($textToEncode);

    return [
        'irn' => $irn,
        'ack_number' => $ackNum,
        'ack_date' => $ackDt,
        'qrcode_string' => $qrString,
        'qr_data_uri' => $qrDataUri,
        'qr_svg' => $qrSvg,
        'qr_api_url' => $qrApiUrl,
    ];
}
