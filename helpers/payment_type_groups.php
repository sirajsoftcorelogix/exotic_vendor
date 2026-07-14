<?php

const PAYMENT_TYPE_GROUP_PREFIX = 'group:';

/**
 * Ordered keyword rules. First match wins, so broader groups (e.g. Payment Gateway) come first.
 *
 * @return array<string, string[]>
 */
function paymentTypeGroupRules(): array
{
    static $rules = null;

    if ($rules !== null) {
        return $rules;
    }

    $rules = [
        'Payment Gateway' => [
            'razorpay', 'payu', 'paypal', 'stripe', 'phonepe', 'phone_pe', 'gpay', 'googlepay', 'google_pay',
            'paytm', 'ccavenue', 'cc_avenue', 'instamojo', 'billdesk', 'mobikwik', 'freecharge', 'amazonpay',
            'amazon_pay', 'bhim', 'cashfree', 'easebuzz', 'pinelabs', 'worldline', 'payone', 'netbanking',
            'net_banking', 'debitcard', 'debit_card', 'creditcard', 'credit_card', 'payment_gateway',
            'online_payment', 'upi',
        ],
        'Amazon' => ['amazon', 'fba'],
        'Myntra' => ['myntra'],
        'Flipkart' => ['flipkart'],
        'Meesho' => ['meesho'],
        'Snapdeal' => ['snapdeal'],
        'Shopify' => ['shopify'],
        'eBay' => ['ebay'],
        'Etsy' => ['etsy'],
        'Nykaa' => ['nykaa'],
        'Ajio' => ['ajio'],
        'COD' => ['cod', 'cash_on_delivery', 'cash on delivery'],
        'Offline' => ['offline', 'counter'],
    ];

    return $rules;
}

function paymentTypeMatchesRule(string $value, array $keywords): bool
{
    $lower = strtolower($value);

    foreach ($keywords as $keyword) {
        $keyword = strtolower(trim((string) $keyword));
        if ($keyword !== '' && str_contains($lower, $keyword)) {
            return true;
        }
    }

    return false;
}

function resolvePaymentTypeGroup(string $paymentType): string
{
    $raw = trim($paymentType);
    if ($raw === '') {
        return 'Other';
    }

    $lower = strtolower($raw);
    $rules = paymentTypeGroupRules();

    foreach ($rules as $groupLabel => $keywords) {
        if (paymentTypeMatchesRule($lower, $keywords)) {
            return $groupLabel;
        }
    }

    if (str_contains($raw, '_')) {
        $prefix = explode('_', $lower, 2)[0];
        if ($prefix !== '') {
            foreach ($rules as $groupLabel => $keywords) {
                if (paymentTypeMatchesRule($prefix, $keywords)) {
                    return $groupLabel;
                }
            }

            return ucwords(str_replace('_', ' ', $prefix));
        }
    }

    return ucwords(str_replace('_', ' ', $lower));
}

/**
 * @param array<string, string> $paymentTypes
 * @return array<string, array<string, string>>
 */
function groupPaymentTypes(array $paymentTypes): array
{
    $grouped = [];

    foreach ($paymentTypes as $key => $label) {
        $grouped[resolvePaymentTypeGroup((string) $key)][(string) $key] = (string) $label;
    }

    if ($grouped === []) {
        return [];
    }

    foreach ($grouped as &$items) {
        asort($items, SORT_NATURAL | SORT_FLAG_CASE);
    }
    unset($items);

    $ordered = [];
    foreach (array_keys(paymentTypeGroupRules()) as $groupLabel) {
        if (!empty($grouped[$groupLabel])) {
            $ordered[$groupLabel] = $grouped[$groupLabel];
            unset($grouped[$groupLabel]);
        }
    }

    if ($grouped !== []) {
        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($grouped as $groupLabel => $items) {
            $ordered[$groupLabel] = $items;
        }
    }

    return $ordered;
}

function normalizeSelectedPaymentTypes($selected): array
{
    if ($selected === null || $selected === '' || $selected === 'all') {
        return [];
    }

    $values = is_array($selected) ? $selected : [$selected];

    return array_values(array_filter($values, static function ($value) {
        return $value !== '' && $value !== 'all';
    }));
}

/**
 * @param array<string, string> $paymentTypes
 * @return array{payment_types: array<string, string>, payment_type_groups: array<string, array<string, string>>, selected_payment_types: string[]}
 */
function preparePaymentTypeFilterData(array $paymentTypes, $selected = null): array
{
    return [
        'payment_types' => $paymentTypes,
        'payment_type_groups' => groupPaymentTypes($paymentTypes),
        'selected_payment_types' => normalizeSelectedPaymentTypes($selected),
    ];
}

function paymentTypeGroupToken(string $groupLabel): string
{
    return PAYMENT_TYPE_GROUP_PREFIX . $groupLabel;
}
