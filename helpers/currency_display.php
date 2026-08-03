<?php

/**
 * Currency display symbols — resolved via DB override, PHP Intl (CLDR), then ISO code fallback.
 */

if (!function_exists('vendor_currency_symbol')) {
    /**
     * @param mixed $code ISO 4217 currency code (e.g. INR, USD)
     */
    function vendor_currency_symbol($code): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $code));
        if ($normalized === '') {
            $normalized = 'INR';
        }

        static $cache = [];
        if (isset($cache[$normalized])) {
            return $cache[$normalized];
        }

        $fromDb = vendor_currency_symbol_from_db($normalized);
        if ($fromDb !== null && $fromDb !== '') {
            return $cache[$normalized] = $fromDb;
        }

        $fromIntl = vendor_currency_symbol_from_intl($normalized);
        if ($fromIntl !== null && $fromIntl !== '') {
            return $cache[$normalized] = $fromIntl;
        }

        return $cache[$normalized] = $normalized . ' ';
    }

    /**
     * @return array<string, string> code => symbol
     */
    function vendor_currency_symbol_map(): array
    {
        static $map = null;
        if (is_array($map)) {
            return $map;
        }

        $map = [];
        global $conn;
        if ($conn instanceof mysqli) {
            $res = $conn->query(
                'SELECT currency_code FROM currency_master WHERE is_active = 1 ORDER BY currency_code ASC'
            );
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $code = strtoupper(trim((string) ($row['currency_code'] ?? '')));
                    if ($code !== '') {
                        $map[$code] = vendor_currency_symbol($code);
                    }
                }
                $res->free();
            }
        }

        if (!isset($map['INR'])) {
            $map['INR'] = vendor_currency_symbol('INR');
        }

        return $map;
    }

    function vendor_currency_symbol_from_intl(string $code): ?string
    {
        if (!class_exists('NumberFormatter')) {
            return null;
        }

        $formatter = new NumberFormatter('en@currency=' . $code, NumberFormatter::CURRENCY);
        if (!$formatter) {
            return null;
        }

        $symbol = $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
        if (!is_string($symbol)) {
            return null;
        }

        $symbol = trim($symbol);
        if ($symbol === '' || strtoupper($symbol) === $code) {
            return null;
        }

        return $symbol;
    }

    function vendor_currency_symbol_from_db(string $code): ?string
    {
        global $conn;
        if (!$conn instanceof mysqli || !vendor_currency_master_has_display_symbol($conn)) {
            return null;
        }

        static $stmt = null;
        if ($stmt === null) {
            $stmt = $conn->prepare(
                'SELECT display_symbol FROM currency_master WHERE currency_code = ? AND is_active = 1 LIMIT 1'
            );
            if (!$stmt) {
                return null;
            }
        }

        $stmt->bind_param('s', $code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            return null;
        }

        $symbol = trim((string) ($row['display_symbol'] ?? ''));
        return $symbol !== '' ? $symbol : null;
    }

    function vendor_currency_master_has_display_symbol(mysqli $conn): bool
    {
        static $hasColumn = null;
        if ($hasColumn !== null) {
            return $hasColumn;
        }

        $res = $conn->query("SHOW COLUMNS FROM currency_master LIKE 'display_symbol'");
        $hasColumn = $res && $res->num_rows > 0;
        if ($res) {
            $res->free();
        }

        return $hasColumn;
    }
}
