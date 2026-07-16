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
];
