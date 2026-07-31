<?php

/**
 * Currency symbols and labels for direct purchase screens.
 */
require_once __DIR__ . '/currency_display.php';

if (!function_exists('dp_currency_symbol_map')) {
    function dp_currency_symbol_map(): array
    {
        return vendor_currency_symbol_map();
    }

    function dp_currency_form_options(): array
    {
        $map = dp_currency_symbol_map();

        return [
            'INR' => ($map['INR'] ?? 'INR') . ' INR — Indian Rupee',
            'USD' => ($map['USD'] ?? 'USD') . ' USD — US Dollar',
            'EUR' => ($map['EUR'] ?? 'EUR') . ' EUR — Euro',
            'GBP' => ($map['GBP'] ?? 'GBP') . ' GBP — British Pound',
            'AED' => ($map['AED'] ?? 'AED') . ' AED — UAE Dirham',
            'SGD' => ($map['SGD'] ?? 'SGD') . ' SGD — Singapore Dollar',
            'HKD' => ($map['HKD'] ?? 'HKD') . ' HKD — Hong Kong Dollar',
            'JPY' => ($map['JPY'] ?? 'JPY') . ' JPY — Japanese Yen',
            'CNY' => ($map['CNY'] ?? 'CNY') . ' CNY — Chinese Yuan',
            'AUD' => ($map['AUD'] ?? 'AUD') . ' AUD — Australian Dollar',
            'CAD' => ($map['CAD'] ?? 'CAD') . ' CAD — Canadian Dollar',
            'CHF' => ($map['CHF'] ?? 'CHF') . ' CHF — Swiss Franc',
            'NZD' => ($map['NZD'] ?? 'NZD') . ' NZD — New Zealand Dollar',
            'SAR' => ($map['SAR'] ?? 'SAR') . ' SAR — Saudi Riyal',
            'THB' => ($map['THB'] ?? 'THB') . ' THB — Thai Baht',
        ];
    }

    /**
     * @param mixed $code ISO-like currency code from DB or request
     */
    function dp_currency_symbol($code): string
    {
        return vendor_currency_symbol($code);
    }

    /** @param mixed $code */
    function dp_currency_decimals($code): int
    {
        $c = strtoupper(trim((string) $code));

        return $c === 'JPY' ? 0 : 2;
    }
}
