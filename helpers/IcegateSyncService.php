<?php

class IcegateSyncService
{
    /**
     * Parse raw text / table pasted directly from ICEGATE portal or Excel
     *
     * @param string $rawText
     * @return array
     */
    public static function parseRawTableText(string $rawText): array
    {
        $rawText = trim($rawText);
        if (empty($rawText)) {
            return [
                'success' => false,
                'message' => 'Pasted text is empty.',
                'rates'   => []
            ];
        }

        $rates = [];
        $lines = explode("\n", $rawText);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Replace tabs or multiple spaces/pipes with single space or delimiter
            $cleanLine = preg_replace('/[\t|]+/u', ' ', $line);
            $cleanLine = preg_replace('/\s+/', ' ', $cleanLine);

            // Ignore header rows
            if (preg_match('/currency\s+code/i', $cleanLine) || preg_match('/import\s+rate/i', $cleanLine)) {
                continue;
            }

            // Pattern A: CODE (3 caps) NAME (string) UNIT (num) IMPORT (num) EXPORT (num)
            // e.g. USD US Dollar 1.0 97.2 95.45
            if (preg_match('/^([A-Z]{3})\s+(.+?)\s+([0-9]+(?:\.[0-9]+)?)\s+([0-9]+(?:\.[0-9]+)?)\s+([0-9]+(?:\.[0-9]+)?)$/i', $cleanLine, $m)) {
                $code = strtoupper(trim($m[1]));
                $name = trim($m[2]);
                $unitVal = floatval($m[3]);
                $importRate = floatval($m[4]);
                $exportRate = floatval($m[5]);

                if ($code !== 'CUR' && !strpos(strtolower($name), 'currency')) {
                    $unitText = ($unitVal > 1) ? ((int)$unitVal . ' ' . $code) : ('1 ' . $code);
                    $rates[] = [
                        'currency_code' => $code,
                        'currency_name' => $name,
                        'currency_unit' => $unitText,
                        'unit_value'    => $unitVal,
                        'rate_import'   => $importRate,
                        'rate_export'   => $exportRate
                    ];
                    continue;
                }
            }

            // Pattern B: CODE (3 caps) IMPORT (num) EXPORT (num)
            // e.g. USD 97.2 95.45
            if (preg_match('/^([A-Z]{3})\s+([0-9]+(?:\.[0-9]+)?)\s+([0-9]+(?:\.[0-9]+)?)$/i', $cleanLine, $m)) {
                $code = strtoupper(trim($m[1]));
                $importRate = floatval($m[2]);
                $exportRate = floatval($m[3]);

                $unitVal = in_array($code, ['JPY', 'KRW']) ? 100 : 1;
                $unitText = ($unitVal > 1) ? ($unitVal . ' ' . $code) : ('1 ' . $code);

                $rates[] = [
                    'currency_code' => $code,
                    'currency_name' => $code,
                    'currency_unit' => $unitText,
                    'unit_value'    => $unitVal,
                    'rate_import'   => $importRate,
                    'rate_export'   => $exportRate
                ];
            }
        }

        return [
            'success'         => count($rates) > 0,
            'message'         => count($rates) > 0 ? ('Successfully parsed ' . count($rates) . ' currency rates from pasted text.') : 'No valid currency rate lines recognized in pasted text.',
            'notification_no' => 'ICEGATE Web Paste',
            'effective_date'  => date('Y-m-d'),
            'rates'           => $rates
        ];
    }
}
