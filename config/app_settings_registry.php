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
    'stock_replenishment_lookback_days' => [
        'label' => 'Stock replenishment lookback (days)',
        'description' => 'Number of days of sales history used to calculate average demand and recommended stock levels.',
        'input' => 'number',
        'type' => 'int',
        'default' => 30,
        'editable' => true,
        'active' => true,
        'sort' => 10,
    ],
];
