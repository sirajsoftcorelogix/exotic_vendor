<?php

/**
 * Validate order shipping address before bulk dispatch / courier rating.
 *
 * @param array<string, mixed> $orderInfo Row from vp_order_info (getRemarksByOrderNumber)
 * @return array{valid:bool,message:string,address:string,pincode:string}
 */
function validateShippingAddressForDispatch(array $orderInfo): array
{
    $line1 = trim((string) ($orderInfo['shipping_address_line1'] ?? ''));
    $pinRaw = trim((string) ($orderInfo['shipping_zipcode'] ?? ''));
    $pin = preg_replace('/\s+/', '', $pinRaw) ?? '';
    $country = strtoupper(trim((string) ($orderInfo['shipping_country'] ?? '')));
    $isDomestic = $country === '' || in_array($country, ['IN', 'IND', 'INDIA'], true);

    if ($line1 === '') {
        return [
            'valid' => false,
            'message' => 'Order #' . ($orderInfo['order_number'] ?? '')
                . ' has no shipping address. Update the order shipping address before dispatch.',
            'address' => '',
            'pincode' => '',
        ];
    }

    if ($pin === '') {
        return [
            'valid' => false,
            'message' => 'Order #' . ($orderInfo['order_number'] ?? '')
                . ' has no shipping pincode / postal code. Update the order before adding to dispatch.',
            'address' => '',
            'pincode' => '',
        ];
    }

    if ($isDomestic && !preg_match('/^\d{6}$/', $pin)) {
        return [
            'valid' => false,
            'message' => 'Order #' . ($orderInfo['order_number'] ?? '')
                . ' has an invalid India pincode. A valid 6-digit pincode is required for dispatch.',
            'address' => '',
            'pincode' => $pin,
        ];
    }

    if (!$isDomestic && strlen($pin) < 3) {
        return [
            'valid' => false,
            'message' => 'Order #' . ($orderInfo['order_number'] ?? '')
                . ' has an invalid international postal code for dispatch.',
            'address' => '',
            'pincode' => $pin,
        ];
    }

    $address = htmlspecialchars($line1, ENT_QUOTES, 'UTF-8');
    $line2 = trim((string) ($orderInfo['shipping_address_line2'] ?? ''));
    if ($line2 !== '') {
        $address .= ', ' . htmlspecialchars($line2, ENT_QUOTES, 'UTF-8');
    }
    $city = trim((string) ($orderInfo['shipping_city'] ?? ''));
    if ($city !== '') {
        $address .= ', ' . htmlspecialchars($city, ENT_QUOTES, 'UTF-8');
    }
    $state = trim((string) ($orderInfo['shipping_state'] ?? ''));
    if ($state !== '') {
        $address .= ', ' . htmlspecialchars($state, ENT_QUOTES, 'UTF-8');
    }
    $address .= ' - ' . htmlspecialchars($pinRaw !== '' ? $pinRaw : $pin, ENT_QUOTES, 'UTF-8');

    return [
        'valid' => true,
        'message' => '',
        'address' => $address,
        'pincode' => $pin,
    ];
}
