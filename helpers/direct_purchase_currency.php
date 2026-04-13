<?php

/**
 * Currency symbols and labels for direct purchase screens.
 */
if (!function_exists('dp_currency_symbol_map')) {
    function dp_currency_symbol_map(): array
    {
        return [
            'INR' => "\u{20B9}",
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AED' => 'د.إ',
            'SGD' => 'S$',
            'HKD' => 'HK$',
            'JPY' => '¥',
            'CNY' => '¥',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'CHF' => 'Fr.',
            'NZD' => 'NZ$',
            'SAR' => "\u{FDFC}",
            'THB' => "\u{0E3F}",
        ];
    }

    function dp_currency_form_options(): array
    {
        $sym = dp_currency_symbol_map();

        return [
            'INR' => $sym['INR'] . ' INR — Indian Rupee',
            'USD' => $sym['USD'] . ' USD — US Dollar',
            'EUR' => $sym['EUR'] . ' EUR — Euro',
            'GBP' => $sym['GBP'] . ' GBP — British Pound',
            'AED' => $sym['AED'] . ' AED — UAE Dirham',
            'SGD' => $sym['SGD'] . ' SGD — Singapore Dollar',
            'HKD' => $sym['HKD'] . ' HKD — Hong Kong Dollar',
            'JPY' => $sym['JPY'] . ' JPY — Japanese Yen',
            'CNY' => $sym['CNY'] . ' CNY — Chinese Yuan',
            'AUD' => $sym['AUD'] . ' AUD — Australian Dollar',
            'CAD' => $sym['CAD'] . ' CAD — Canadian Dollar',
            'CHF' => $sym['CHF'] . ' CHF — Swiss Franc',
            'NZD' => $sym['NZD'] . ' NZD — New Zealand Dollar',
            'SAR' => $sym['SAR'] . ' SAR — Saudi Riyal',
            'THB' => $sym['THB'] . ' THB — Thai Baht',
        ];
    }

    /**
     * @param mixed $code ISO-like currency code from DB or request
     */
    function dp_currency_symbol($code): string
    {
        $c = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $code));
        if ($c === '') {
            $c = 'INR';
        }
        $map = dp_currency_symbol_map();

        return $map[$c] ?? '¤';
    }

    /** @param mixed $code */
    function dp_currency_decimals($code): int
    {
        $c = strtoupper(trim((string) $code));

        return $c === 'JPY' ? 0 : 2;
    }
}
