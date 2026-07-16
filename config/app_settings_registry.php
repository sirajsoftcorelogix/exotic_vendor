<?php

/**
 * Developer-defined app settings metadata.
 * DB tables:
 *   app_settings       — setting_key, setting_value
 *   settings_audit_log — change history
 *
 * To add a setting:
 * 1. Add an entry here (label, input, type, sort, etc.)
 * 2. INSERT the row into app_settings (setting_key, setting_value)
 */
return [
    'stock_replenishment_months' => [
        'label' => 'Stock replenishment lookback (months)',
        'description' => 'Number of months of sales history used to calculate average demand and recommended stock levels.',
        'input' => 'number',
        'type' => 'int',
        'default' => 1,
        'editable' => true,
        'active' => true,
        'sort' => 10,
    ],
    'stock_replenishment_book_price_cutoff' => [
        'label' => 'Book price cutoff for replenishment',
        'description' => 'Minimum book selling price (₹) included in stock replenishment calculations. Books below this price are excluded. Use 0 for no cutoff.',
        'input' => 'number',
        'type' => 'int',
        'default' => 0,
        'editable' => true,
        'active' => true,
        'sort' => 20,
    ],
    'invoice_prefix' => [
        'label' => 'Invoice number prefix',
        'description' => 'Prefix for auto-generated invoice numbers (e.g. inv/2025-26/ or INV).',
        'input' => 'text',
        'type' => 'string',
        'default' => 'INV',
        'editable' => true,
        'active' => true,
        'sort' => 100,
    ],
    'invoice_series' => [
        'label' => 'Invoice series counter',
        'description' => 'Last used invoice series number; incremented automatically when a new invoice is created.',
        'input' => 'number',
        'type' => 'int',
        'default' => 0,
        'editable' => true,
        'active' => true,
        'sort' => 110,
    ],
    'terms_and_conditions' => [
        'label' => 'Invoice terms and conditions',
        'description' => 'Default terms printed on invoices.',
        'input' => 'textarea',
        'type' => 'string',
        'default' => '',
        'editable' => true,
        'active' => true,
        'sort' => 120,
    ],
    'high_value_transaction_limit' => [
        'label' => 'High value transaction limit (₹)',
        'description' => 'Orders above this amount may require additional approval.',
        'input' => 'number',
        'type' => 'decimal',
        'default' => 200000.00,
        'editable' => true,
        'active' => true,
        'sort' => 130,
    ],
];
