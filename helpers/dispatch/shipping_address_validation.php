<?php

/**
 * Validate order shipping address before bulk dispatch / courier rating.
 * Falls back to billing fields when shipping columns are empty (matches courier adapter).
 *
 * @param array<string, mixed> $orderInfo Row from vp_order_info (getRemarksByOrderNumber)
 * @return array{valid:bool,message:string,address:string,pincode:string}
 */
function validateShippingAddressForDispatch(array $orderInfo): array
{
    $line1 = trim((string) ($orderInfo['shipping_address_line1'] ?? ''));
    if ($line1 === '') {
        $line1 = trim((string) ($orderInfo['address_line1'] ?? ''));
    }

    $line2 = trim((string) ($orderInfo['shipping_address_line2'] ?? ''));
    if ($line2 === '') {
        $line2 = trim((string) ($orderInfo['address_line2'] ?? ''));
    }

    $city = trim((string) ($orderInfo['shipping_city'] ?? ''));
    if ($city === '') {
        $city = trim((string) ($orderInfo['city'] ?? ''));
    }

    $state = trim((string) ($orderInfo['shipping_state'] ?? ''));
    if ($state === '') {
        $state = trim((string) ($orderInfo['state'] ?? ''));
    }

    $pinRaw = trim((string) ($orderInfo['shipping_zipcode'] ?? ''));
    if ($pinRaw === '') {
        $pinRaw = trim((string) ($orderInfo['zipcode'] ?? ''));
    }
    $pin = preg_replace('/\s+/', '', $pinRaw) ?? '';

    $country = strtoupper(trim((string) ($orderInfo['shipping_country'] ?? '')));
    if ($country === '') {
        $country = strtoupper(trim((string) ($orderInfo['country'] ?? '')));
    }
    $isDomestic = $country === '' || in_array($country, ['IN', 'IND', 'INDIA'], true);

    $orderNo = (string) ($orderInfo['order_number'] ?? 'N/A');
    $displayCountry = $country !== '' ? $country : 'Unspecified';

    if ($line1 === '') {
        return [
            'valid' => false,
            'message' => "Order #{$orderNo} has no shipping address line 1. Please edit the order shipping address and add street details before dispatching.",
            'address' => '',
            'pincode' => '',
        ];
    }

    if ($pin === '') {
        return [
            'valid' => false,
            'message' => "Order #{$orderNo} has no shipping pincode / postal code (Country: {$displayCountry}). Please edit the order shipping address and enter a valid postal code.",
            'address' => '',
            'pincode' => '',
        ];
    }

    if ($isDomestic && !preg_match('/^\d{6}$/', $pin)) {
        $foundPin = $pinRaw !== '' ? $pinRaw : '(empty)';
        return [
            'valid' => false,
            'message' => "Order #{$orderNo} has an invalid India pincode ('{$foundPin}'). A valid 6-digit numeric pincode is required for domestic dispatch.",
            'address' => '',
            'pincode' => $pin,
        ];
    }

    if (!$isDomestic && strlen($pin) < 3) {
        $foundPin = $pinRaw !== '' ? $pinRaw : '(empty)';
        return [
            'valid' => false,
            'message' => "Order #{$orderNo} has an invalid international postal code ('{$foundPin}' for Country: {$displayCountry}). A valid postal code (min 3 characters) is required for international dispatch.",
            'address' => '',
            'pincode' => $pin,
        ];
    }

    $address = htmlspecialchars($line1, ENT_QUOTES, 'UTF-8');
    if ($line2 !== '') {
        $address .= ', ' . htmlspecialchars($line2, ENT_QUOTES, 'UTF-8');
    }
    if ($city !== '') {
        $address .= ', ' . htmlspecialchars($city, ENT_QUOTES, 'UTF-8');
    }
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
