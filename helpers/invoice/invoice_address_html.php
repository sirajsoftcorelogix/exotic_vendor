<?php

/**
 * Format vp_order_info billing / shipping blocks for invoice PDF templates.
 *
 * @return array{bill: string, ship: string}
 */
function invoice_resolve_bill_ship_html(?array $orderInfo): array
{
    if (!$orderInfo) {
        return ['bill' => '', 'ship' => ''];
    }

    $bill = invoice_format_order_info_address_html($orderInfo, 'billing');
    $ship = invoice_format_order_info_address_html($orderInfo, 'shipping');

    if (!invoice_order_info_has_shipping($orderInfo)) {
        $ship = $bill;
    }

    return ['bill' => $bill, 'ship' => $ship];
}

function invoice_order_info_has_shipping(array $orderInfo): bool
{
    foreach ([
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_zipcode',
    ] as $field) {
        if (trim((string)($orderInfo[$field] ?? '')) !== '') {
            return true;
        }
    }

    return false;
}

function invoice_format_order_info_address_html(array $orderInfo, string $type): string
{
    $isShipping = $type === 'shipping';
    $prefix = $isShipping ? 'shipping_' : '';

    $firstName = trim((string)($orderInfo[$prefix . 'first_name'] ?? ''));
    $lastName = trim((string)($orderInfo[$prefix . 'last_name'] ?? ''));
    if ($firstName === '' && $lastName === '' && !$isShipping) {
        $firstName = trim((string)($orderInfo['first_name'] ?? ''));
        $lastName = trim((string)($orderInfo['last_name'] ?? ''));
    }
    if ($firstName === '' && $lastName === '' && $isShipping) {
        $firstName = trim((string)($orderInfo['first_name'] ?? ''));
        $lastName = trim((string)($orderInfo['last_name'] ?? ''));
    }

    $line1 = trim((string)($orderInfo[$prefix . 'address_line1'] ?? ''));
    $line2 = trim((string)($orderInfo[$prefix . 'address_line2'] ?? ''));
    if ($isShipping && $line1 === '') {
        return '';
    }
    if (!$isShipping && $line1 === '') {
        return '';
    }

    $city = trim((string)($orderInfo[$prefix . 'city'] ?? ''));
    $state = trim((string)($orderInfo[$prefix . 'state'] ?? ''));
    $zip = trim((string)($orderInfo[$prefix . 'zipcode'] ?? ''));
    $phone = trim((string)($orderInfo[$prefix . 'mobile'] ?? ''));
    if ($isShipping && $phone === '') {
        $phone = trim((string)($orderInfo['mobile'] ?? ''));
    }

    $name = trim($firstName . ' ' . $lastName);
    $html = '<strong>' . htmlspecialchars($name !== '' ? $name : 'N/A') . '</strong><br>';
    $html .= htmlspecialchars($line1);
    if ($line2 !== '') {
        $html .= htmlspecialchars($line2);
    }
    $html .= '<br>';
    $html .= htmlspecialchars(trim($city . ' ' . $state . ' ' . $zip)) . '<br>';
    if (!$isShipping) {
        $gstin = trim((string)($orderInfo['gstin'] ?? ''));
        if ($gstin !== '') {
            $html .= 'GSTIN: ' . htmlspecialchars($gstin) . '<br>';
        }
    }
    if ($phone !== '') {
        $html .= 'Tel: ' . htmlspecialchars($phone) . '<br>';
    }

    return $html;
}
