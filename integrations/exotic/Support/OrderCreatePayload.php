<?php

/**
 * Shared helpers for Exotic retail POST /order/create (form-urlencoded).
 *
 * @see docs/exotic-order-create-api.md
 */

/**
 * @param array<string, mixed> $payload
 */
function pos_order_create_is_shipping_same_as_billing(array $payload): bool
{
    return !empty($payload['confirm_shipping_same_as_billing'])
        || (string)($payload['confirm_shipping_same_as_billing'] ?? '') === '1';
}

/**
 * Copy billing confirm_* fields onto shipping confirm_s* when same-as-billing is checked.
 *
 * @param array<string, mixed> $payload
 *
 * @return array<string, mixed>
 */
function pos_order_create_apply_shipping_same_as_billing(array $payload): array
{
    if (!pos_order_create_is_shipping_same_as_billing($payload)) {
        return $payload;
    }

    $pairs = [
        ['confirm_first_name', 'confirm_sfirst_name'],
        ['confirm_last_name', 'confirm_slast_name'],
        ['confirm_address1', 'confirm_saddress1'],
        ['confirm_address2', 'confirm_saddress2'],
        ['confirm_city', 'confirm_scity'],
        ['confirm_state', 'confirm_sstate'],
        ['confirm_state_iso', 'confirm_sstate_iso'],
        ['confirm_state_code', 'confirm_sstate_code'],
        ['confirm_zip', 'confirm_szip'],
        ['confirm_country', 'confirm_scountry'],
        ['confirm_phone', 'confirm_sphone'],
        ['confirm_phone_code', 'confirm_sphone_code'],
        ['confirm_gstin', 'confirm_sgstin'],
    ];

    foreach ($pairs as [$billingKey, $shippingKey]) {
        $billingVal = trim((string)($payload[$billingKey] ?? ''));
        if ($billingVal !== '') {
            $payload[$shippingKey] = $billingVal;
        }
    }

    $sf = trim((string)($payload['confirm_sfirst_name'] ?? ''));
    $sl = trim((string)($payload['confirm_slast_name'] ?? ''));
    $payload['confirm_sname'] = trim($sf . ' ' . $sl);

    return $payload;
}

/**
 * Resolve shipping first/last/full name with billing fallbacks (partial shipping must not block fallback).
 *
 * @param array<string, mixed> $payload Checkout JSON (after same-as-billing apply)
 * @param array<string, string> $billingOut Built billing block (first_name, last_name, …)
 *
 * @return array{first:string,last:string,full:string}
 */
function pos_order_create_resolve_recipient_name(array $payload, array $billingOut): array
{
    $billingFirst = trim((string)($billingOut['first_name'] ?? $payload['confirm_first_name'] ?? ''));
    $billingLast = trim((string)($billingOut['last_name'] ?? $payload['confirm_last_name'] ?? ''));

    $sf = trim((string)($payload['confirm_sfirst_name'] ?? ''));
    $sl = trim((string)($payload['confirm_slast_name'] ?? ''));

    if ($sf === '' && $billingFirst !== '') {
        $sf = $billingFirst;
    }
    if ($sl === '' && $billingLast !== '') {
        $sl = $billingLast;
    }

    $full = trim((string)($payload['confirm_sname'] ?? ''));
    if ($full === '') {
        $full = trim($sf . ' ' . $sl);
    }
    if ($full === '') {
        $full = trim($billingFirst . ' ' . $billingLast);
    }

    // Exotic parses sname internally; ensure last name is present when billing has one.
    if ($sl === '' && $billingLast !== '' && $full !== '' && !str_contains($full, $billingLast)) {
        $full = trim($full . ' ' . $billingLast);
        $sl = $billingLast;
    }

    return [
        'first' => $sf,
        'last' => $sl,
        'full' => $full,
    ];
}

/**
 * Append required shipping (recipient) fields for POST /order/create.
 *
 * Short checkoutdata tokens do not embed address — all s* fields must be sent explicitly.
 *
 * @param array<string, string> $out Billing fields already set on the POST body
 * @param array<string, mixed>  $payload Checkout JSON (same-as-billing should be applied first)
 */
function pos_order_create_append_shipping_fields(array &$out, array $payload): void
{
    $payload = pos_order_create_apply_shipping_same_as_billing($payload);

    $names = pos_order_create_resolve_recipient_name($payload, $out);
    $sf = $names['first'];
    $sl = $names['last'];
    $sname = $names['full'];

    $saddress1 = trim((string)($payload['confirm_saddress1'] ?? ''));
    $saddress2 = trim((string)($payload['confirm_saddress2'] ?? ''));
    $scity = trim((string)($payload['confirm_scity'] ?? ''));
    $sstate = trim((string)($payload['confirm_sstate'] ?? ''));
    $szip = trim((string)($payload['confirm_szip'] ?? ''));
    $sphone = trim((string)($payload['confirm_sphone'] ?? ''));
    $sgstin = strtoupper(trim((string)($payload['confirm_sgstin'] ?? '')));

    if ($saddress1 === '') {
        $saddress1 = (string)($out['address1'] ?? '');
    }
    if ($saddress2 === '') {
        $saddress2 = (string)($out['address2'] ?? '');
    }
    if ($scity === '') {
        $scity = (string)($out['city'] ?? '');
    }
    if ($sstate === '') {
        $sstate = (string)($out['state'] ?? '');
    }
    if ($szip === '') {
        $szip = (string)($out['zip'] ?? '');
    }
    if ($sphone === '') {
        $sphone = (string)($out['phone'] ?? '');
    }
    if ($sgstin === '') {
        $sgstin = strtoupper(trim((string)($out['gstin'] ?? '')));
    }

    $scountry = strtoupper(substr(trim((string)($payload['confirm_scountry'] ?? '')), 0, 2));
    if ($scountry === '') {
        $scountry = (string)($out['country'] ?? 'IN');
    }
    if ($scountry === '') {
        $scountry = 'IN';
    }

    $out['sname'] = $sname;
    $out['saddress1'] = $saddress1;
    $out['saddress2'] = $saddress2;
    $out['scity'] = $scity;
    $out['sstate'] = $sstate;
    $out['szip'] = $szip;
    $out['scountry'] = $scountry;
    $out['sphone'] = $sphone;
}

/**
 * Apply billing fallbacks when rebuilding publish payload from vp_order_info (no same-as-billing flag stored).
 *
 * @param array<string, mixed> $orderInfo
 *
 * @return array<string, mixed> confirm_* shaped payload fragment
 */
function pos_order_create_confirm_payload_from_order_info(array $orderInfo, array $payments = []): array
{
    $sf = trim((string)($orderInfo['shipping_first_name'] ?? ''));
    $sl = trim((string)($orderInfo['shipping_last_name'] ?? ''));
    $bf = trim((string)($orderInfo['first_name'] ?? ''));
    $bl = trim((string)($orderInfo['last_name'] ?? ''));
    if ($sf === '' && $bf !== '') {
        $sf = $bf;
    }
    if ($sl === '' && $bl !== '') {
        $sl = $bl;
    }

    $splits = [];
    $codAmount = 0.0;
    $primaryMode = 'cash';
    $primaryTxn = '';
    foreach ($payments as $pay) {
        if (!is_array($pay)) {
            continue;
        }
        $mode = strtolower(trim((string)($pay['payment_mode'] ?? '')));
        $amount = round((float)($pay['payment_amount'] ?? 0), 2);
        $txn = trim((string)($pay['transaction_id'] ?? ''));
        $splits[] = [
            'mode' => $mode !== '' ? $mode : 'cash',
            'amount' => $amount,
            'transaction_id' => $txn,
        ];
        if ($mode === 'cod') {
            $codAmount += $amount;
        } elseif ($primaryTxn === '' && $txn !== '') {
            $primaryTxn = $txn;
        }
        if ($mode !== 'cod' && $primaryMode === 'cash' && $mode !== '') {
            $primaryMode = $mode;
        }
    }
    if ($codAmount > 0.001) {
        $primaryMode = 'cod';
    }

    $payload = [
        'customer_id' => (int)($orderInfo['customer_id'] ?? 0),
        'payment_mode' => $primaryMode,
        'transaction_id' => $primaryTxn,
        'cod_amount' => round($codAmount, 2),
        'payment_splits' => $splits,
        'confirm_first_name' => $bf,
        'confirm_last_name' => $bl,
        'confirm_company' => trim((string)($orderInfo['company'] ?? '')),
        'confirm_address1' => trim((string)($orderInfo['address_line1'] ?? '')),
        'confirm_address2' => trim((string)($orderInfo['address_line2'] ?? '')),
        'confirm_city' => trim((string)($orderInfo['city'] ?? '')),
        'confirm_state' => trim((string)($orderInfo['state'] ?? '')),
        'confirm_state_iso' => trim((string)($orderInfo['state_iso'] ?? '')),
        'confirm_state_code' => trim((string)($orderInfo['state_code'] ?? '')),
        'confirm_country' => trim((string)($orderInfo['country'] ?? 'IN')),
        'confirm_zip' => trim((string)($orderInfo['zipcode'] ?? '')),
        'confirm_phone' => trim((string)($orderInfo['mobile'] ?? '')),
        'confirm_email' => trim((string)($orderInfo['email'] ?? '')),
        'confirm_gstin' => trim((string)($orderInfo['gstin'] ?? '')),
        'confirm_sfirst_name' => $sf,
        'confirm_slast_name' => $sl,
        'confirm_sname' => trim($sf . ' ' . $sl),
        'confirm_scompany' => trim((string)($orderInfo['shipping_company'] ?? '')),
        'confirm_saddress1' => trim((string)($orderInfo['shipping_address_line1'] ?? '')),
        'confirm_saddress2' => trim((string)($orderInfo['shipping_address_line2'] ?? '')),
        'confirm_scity' => trim((string)($orderInfo['shipping_city'] ?? '')),
        'confirm_sstate' => trim((string)($orderInfo['shipping_state'] ?? '')),
        'confirm_sstate_iso' => trim((string)($orderInfo['shipping_state_iso'] ?? '')),
        'confirm_sstate_code' => trim((string)($orderInfo['shipping_state_code'] ?? '')),
        'confirm_scountry' => trim((string)($orderInfo['shipping_country'] ?? '')),
        'confirm_szip' => trim((string)($orderInfo['shipping_zipcode'] ?? '')),
        'confirm_sphone' => trim((string)($orderInfo['shipping_mobile'] ?? '')),
        'confirm_sgstin' => trim((string)($orderInfo['shipping_gstin'] ?? '')),
    ];

    return pos_order_create_apply_shipping_same_as_billing(
        pos_order_create_apply_billing_fallbacks_to_shipping($payload)
    );
}

/**
 * When shipping address fields are empty, copy from billing (vp_order_info publish rebuild).
 *
 * @param array<string, mixed> $payload
 *
 * @return array<string, mixed>
 */
function pos_order_create_apply_billing_fallbacks_to_shipping(array $payload): array
{
    $pairs = [
        ['confirm_sfirst_name', 'confirm_first_name'],
        ['confirm_slast_name', 'confirm_last_name'],
        ['confirm_saddress1', 'confirm_address1'],
        ['confirm_saddress2', 'confirm_address2'],
        ['confirm_scity', 'confirm_city'],
        ['confirm_sstate', 'confirm_state'],
        ['confirm_szip', 'confirm_zip'],
        ['confirm_scountry', 'confirm_country'],
        ['confirm_sphone', 'confirm_phone'],
        ['confirm_sgstin', 'confirm_gstin'],
    ];

    foreach ($pairs as [$shippingKey, $billingKey]) {
        if (trim((string)($payload[$shippingKey] ?? '')) === '') {
            $billingVal = trim((string)($payload[$billingKey] ?? ''));
            if ($billingVal !== '') {
                $payload[$shippingKey] = $billingVal;
            }
        }
    }

    $sf = trim((string)($payload['confirm_sfirst_name'] ?? ''));
    $sl = trim((string)($payload['confirm_slast_name'] ?? ''));
    if (trim((string)($payload['confirm_sname'] ?? '')) === '') {
        $payload['confirm_sname'] = trim($sf . ' ' . $sl);
    }

    return $payload;
}
