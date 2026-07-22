<?php

/**
 * POS tax-invoice amount summary rows (matches invoice PDF Summary section).
 *
 * @return list<array{label: string, amount: float, note: string, is_grand: bool}>
 */
function pos_invoice_custom_discount_label(array $posMeta): string
{
    $mode = trim((string)($posMeta['custom_discount_mode'] ?? ''));
    $value = round((float)($posMeta['custom_discount_value'] ?? 0), 2);
    if ($mode === 'percent' && $value > 0) {
        $pct = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        return 'Custom Discount (' . $pct . '%)';
    }

    if ($mode === 'fixed' && $value > 0) {
        return 'Custom Discount (fixed amount)';
    }

    return 'Custom Discount';
}

function pos_invoice_coupon_label(array $posMeta): string
{
    $name = trim((string)($posMeta['coupon_display_name'] ?? ''));

    return $name !== '' ? 'Coupon (' . $name . ')' : 'Coupon';
}

/**
 * @return list<array{label: string, amount: float, note: string, is_grand: bool}>
 */
function pos_invoice_build_amount_summary_rows(
    array $posMeta,
    float $grandTotal,
    float $taxAmount
): array {
    $subInclGst = round((float)($posMeta['subtotal_goods'] ?? 0), 2);
    if ($subInclGst <= 0 && $grandTotal > 0) {
        $subInclGst = $grandTotal;
    }
    $coupon = round((float)($posMeta['coupon_discount'] ?? 0), 2);
    $cash = round((float)($posMeta['cash_discount'] ?? 0), 2);
    $gift = round((float)($posMeta['gift_discount'] ?? 0), 2);
    $line = round((float)($posMeta['line_discount'] ?? 0), 2);
    $gst = round((float)($posMeta['gst_total'] ?? 0), 2);
    if ($gst <= 0 && $taxAmount > 0) {
        $gst = round($taxAmount, 2);
    }
    if ($grandTotal <= 0) {
        $grandTotal = $subInclGst;
    }

    $absorbed = !empty($posMeta['discounts_absorbed']);
    $absorbedNote = $absorbed ? '(included in line totals)' : '';
    $hasAnyDiscount = ($coupon + $cash + $gift + $line) > 0.001;
    $showSummary = $subInclGst > 0 || $hasAnyDiscount || $grandTotal > 0;
    if (!$showSummary) {
        return [];
    }

    $rows = [];

    if ($absorbed) {
        $orderLevelDisc = round($coupon + $cash + $gift, 2);
        $lineDisc = round($line, 2);

        if ($subInclGst <= 0 && $grandTotal > 0) {
            $subInclGst = round($grandTotal + $orderLevelDisc, 2);
        }
        if ($grandTotal <= 0) {
            $grandTotal = $subInclGst;
        }
        if ($subInclGst <= 0) {
            $subInclGst = $grandTotal;
        }
        if ($orderLevelDisc > 0.001 && $subInclGst > 0) {
            $computedGrand = max(0.0, round($subInclGst - $orderLevelDisc, 2));
            $grandTotal = $computedGrand;
        }

        $rows[] = [
            'label' => 'Total Before Discount (incl. GST)',
            'amount' => $subInclGst,
            'note' => '',
            'is_grand' => false,
        ];
        if ($lineDisc > 0.001) {
            $rows[] = [
                'label' => 'Line Discount',
                'amount' => $lineDisc,
                'note' => $absorbedNote,
                'is_grand' => false,
            ];
        }
        if ($cash > 0.001) {
            $rows[] = [
                'label' => pos_invoice_custom_discount_label($posMeta),
                'amount' => $cash,
                'note' => '',
                'is_grand' => false,
            ];
        }
        if ($coupon > 0.001) {
            $rows[] = [
                'label' => pos_invoice_coupon_label($posMeta),
                'amount' => $coupon,
                'note' => '',
                'is_grand' => false,
            ];
        }
        if ($gift > 0.001) {
            $rows[] = [
                'label' => 'Gift Voucher',
                'amount' => $gift,
                'note' => '',
                'is_grand' => false,
            ];
        }
        if ($gst > 0.001) {
            $rows[] = [
                'label' => 'Total GST',
                'amount' => $gst,
                'note' => $absorbedNote,
                'is_grand' => false,
            ];
        }
        $rows[] = [
            'label' => 'GRAND Total',
            'amount' => $grandTotal,
            'note' => '',
            'is_grand' => true,
        ];

        return $rows;
    }

    $rows[] = [
        'label' => 'Total Before Discount (incl. GST)',
        'amount' => $subInclGst,
        'note' => '',
        'is_grand' => false,
    ];
    if ($line > 0.001) {
        $rows[] = [
            'label' => 'Line Discount',
            'amount' => $line,
            'note' => '',
            'is_grand' => false,
        ];
    }
    if ($cash > 0) {
        $rows[] = [
            'label' => pos_invoice_custom_discount_label($posMeta),
            'amount' => $cash,
            'note' => $absorbedNote,
            'is_grand' => false,
        ];
    }
    if ($coupon > 0) {
        $rows[] = [
            'label' => pos_invoice_coupon_label($posMeta),
            'amount' => $coupon,
            'note' => $absorbedNote,
            'is_grand' => false,
        ];
    }
    if ($gift > 0) {
        $rows[] = [
            'label' => 'Gift Voucher',
            'amount' => $gift,
            'note' => $absorbedNote,
            'is_grand' => false,
        ];
    }
    $rows[] = [
        'label' => 'Total GST',
        'amount' => $gst,
        'note' => '',
        'is_grand' => false,
    ];
    $rows[] = [
        'label' => 'GRAND Total',
        'amount' => $grandTotal,
        'note' => '',
        'is_grand' => true,
    ];

    return $rows;
}

/**
 * Advance received and COD pending rows for POS invoices.
 *
 * @return list<array{label: string, amount: float, note: string, is_grand: bool, is_pending?: bool}>
 */
function pos_invoice_build_payment_collection_rows(mysqli $conn, string $orderNumber): array
{
    require_once __DIR__ . '/../pos_payment_receipt.php';

    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return [];
    }

    $advance = pos_payment_sum_paid($conn, $orderNumber);
    $codRecorded = pos_payment_sum_cod_pending($conn, $orderNumber);
    if ($advance <= 0.001 && $codRecorded <= 0.001) {
        return [];
    }

    $orderTotal = pos_payment_resolve_order_total($conn, $orderNumber);
    $codPending = $codRecorded > 0.001
        ? max(0.0, round($orderTotal - $advance, 2))
        : 0.0;
    if ($advance <= 0.001 && $codPending <= 0.001) {
        return [];
    }

    $rows = [];
    if ($advance > 0.001) {
        $rows[] = [
            'label' => 'Advance Received',
            'amount' => $advance,
            'note' => '',
            'is_grand' => false,
        ];
    }
    if ($codPending > 0.001) {
        $rows[] = [
            'label' => 'COD Pending',
            'amount' => $codPending,
            'note' => 'Collect on delivery',
            'is_grand' => false,
            'is_pending' => true,
        ];
    }

    return $rows;
}

/**
 * Resolve POS discount meta + grand/tax totals the same way invoice PDF generation does.
 *
 * @param array<string, mixed> $invoice
 * @return array{
 *   pos_meta: array<string, mixed>,
 *   grand_total: float,
 *   tax_amount: float
 * }
 */
function pos_invoice_resolve_pdf_summary_inputs(array $invoice, float $lineTotalSum = 0.0): array
{
    require_once __DIR__ . '/pos_order_pricing.php';
    require_once __DIR__ . '/pos_invoice_line_calculation.php';

    $posMeta = pos_invoice_parse_discount_meta($invoice['notes'] ?? null);
    if ($posMeta === [] && !empty($invoice['pos_flag'])) {
        $posMeta = [
            'subtotal_goods' => round((float)($invoice['total_amount'] ?? 0), 2),
            'gst_total' => round((float)($invoice['tax_amount'] ?? 0), 2),
            'grand_total' => round((float)($invoice['total_amount'] ?? 0), 2),
            'discounts_absorbed' => true,
        ];
    }

    $summaryGrandTotal = round((float)($posMeta['grand_total'] ?? 0), 2);
    $summarySubtotal = round((float)($posMeta['subtotal_goods'] ?? 0), 2);
    $invoiceInclusive = round((float)($invoice['subtotal'] ?? 0) + (float)($invoice['tax_amount'] ?? 0), 2);
    $orderLevelDisc = pos_invoice_order_level_discount_total($posMeta);
    $excelGrandTotal = pos_invoice_expected_grand_total($posMeta, $lineTotalSum);
    $summaryBase = $summarySubtotal > 0 ? $summarySubtotal : $invoiceInclusive;
    if ($orderLevelDisc > 0.001 && $summaryBase > 0) {
        $summaryGrandTotal = $excelGrandTotal;
    } elseif ($summaryGrandTotal <= 0 || abs($summaryGrandTotal - $summaryBase) < 0.02) {
        $computedGrand = max(0.0, round($summaryBase - $orderLevelDisc, 2));
        if ($summaryGrandTotal <= 0 || abs($summaryGrandTotal - $summaryBase) < 0.02) {
            $summaryGrandTotal = $computedGrand;
        }
    }
    if ($summaryGrandTotal <= 0) {
        $summaryGrandTotal = round((float)($invoice['total_amount'] ?? 0), 2);
    }
    if ($lineTotalSum > 0.001 && abs($lineTotalSum - $excelGrandTotal) <= 0.05) {
        $summaryGrandTotal = round($lineTotalSum, 2);
    } elseif ($excelGrandTotal > 0 && $orderLevelDisc > 0.001) {
        $summaryGrandTotal = $excelGrandTotal;
    }

    $summaryTaxAmount = round((float)($posMeta['gst_total'] ?? 0), 2);
    if ($summaryTaxAmount <= 0) {
        $summaryTaxAmount = round((float)($invoice['tax_amount'] ?? 0), 2);
    }

    return [
        'pos_meta' => $posMeta,
        'grand_total' => $summaryGrandTotal,
        'tax_amount' => $summaryTaxAmount,
    ];
}

/**
 * @param list<array{label: string, amount: float, note: string, is_grand: bool}> $rows
 */
function pos_invoice_render_amount_summary_html(array $rows, int $colCount = 13, ?string $sectionTitle = 'Summary'): string
{
    if ($rows === []) {
        return '';
    }

    require_once __DIR__ . '/invoice_address_html.php';

    $colCount = max(3, $colCount);
    $labelSpan = $colCount - 2;
    $html = '';
    if ($sectionTitle !== null && trim($sectionTitle) !== '') {
        $html .= '
                    <tr style="background:#ffffff;">
                        <td colspan="' . $colCount . '" style="text-align:left;padding:14px 8px 6px;border:none;border-top:2px solid #000;">
                            <span style="font-size:13px;font-weight:bold;letter-spacing:0.08em;text-transform:uppercase;color:#333;">' . htmlspecialchars($sectionTitle) . '</span>
                        </td>
                    </tr>';
    }

    foreach ($rows as $row) {
        $label = (string)($row['label'] ?? '');
        $amount = round((float)($row['amount'] ?? 0), 2);
        $note = trim((string)($row['note'] ?? ''));
        $isGrand = !empty($row['is_grand']);
        $isPending = !empty($row['is_pending']);
        $noteHtml = $note !== ''
            ? '<br><span class="invoice-summary-note" style="' . invoice_pdf_body_text_inline_style() . ' font-weight:normal;color:#555;">' . htmlspecialchars($note) . '</span>'
            : '';
        $bg = $isGrand ? '#f0f0f0' : ($isPending ? '#fff7ed' : '#f9f9f9');
        $rowClass = $isGrand ? 'invoice-summary-grand' : ($isPending ? 'invoice-summary-pending' : '');
        $borderTop = $isGrand ? 'border-top:2px solid #000;' : '';
        $cellStyle = 'text-align:right;padding:8px 10px;border:1px solid #ddd;' . invoice_pdf_body_text_inline_style();
        $amountStyle = $isPending ? ' color:#b45309;font-weight:bold;' : '';

        $html .= '
                    <tr class="' . $rowClass . '" style="background:' . $bg . ';' . $borderTop . '">
                        <td colspan="' . $labelSpan . '" class="right invoice-text" style="' . $cellStyle . '">'
                            . htmlspecialchars($label) . $noteHtml .
                        '</td>
                        <td colspan="2" class="right invoice-text" style="' . $cellStyle . $amountStyle . '">'
                            . number_format($amount, 2) .
                        '</td>
                    </tr>';
    }

    return $html;
}
