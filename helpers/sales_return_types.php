<?php

function sales_return_type_options(): array
{
    return [
        'customer_request' => 'Customer request',
        'defective' => 'Defective / damaged',
        'wrong_item' => 'Wrong item',
        'exchange' => 'Exchange (stock only)',
        'other' => 'Other',
    ];
}

function sales_return_normalize_type(?string $raw): string
{
    $key = strtolower(trim((string) $raw));
    $options = sales_return_type_options();

    return array_key_exists($key, $options) ? $key : 'customer_request';
}
