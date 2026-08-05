<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use Smalot\PdfParser\Parser as PdfParser;

class IcegatePdfParser
{
    /**
     * Parse CBIC/ICEGATE Exchange Rate Notification PDF file
     *
     * @param string $filePath Absolute path to PDF
     * @return array Result array with metadata and extracted rates
     */
    public static function parsePdf(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'PDF file not found at path: ' . $filePath,
                'rates' => []
            ];
        }

        if (!class_exists('Smalot\PdfParser\Parser')) {
            return [
                'success' => false,
                'message' => 'Smalot\\PdfParser library is missing. Please run composer install.',
                'rates' => []
            ];
        }

        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            return self::parseTextContent($text);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to parse PDF: ' . $e->getMessage(),
                'rates' => []
            ];
        }
    }

    /**
     * Parse extracted text content from CBIC notification
     */
    public static function parseTextContent(string $text): array
    {
        $notificationNo = null;
        $effectiveDate = null;

        // Notification No: e.g., "Exchange Rate Notification No.: 22/2026" or "No. 22/2026-Customs"
        if (preg_match('/Notification\s+(?:No\.?|Number):\s*([0-9]+\/[0-9]{4}(?:-[A-Za-z]+)?)/i', $text, $m)) {
            $notificationNo = trim($m[1]);
        } elseif (preg_match('/No\.?\s*([0-9]+\/[0-9]{4})/i', $text, $m)) {
            $notificationNo = trim($m[1]);
        }

        // Effective date: e.g., "w.e.f 17-07-2026" or "w.e.f. 17/07/2026" or "17th July, 2026"
        if (preg_match('/w\.?e\.?f\.?\s*([0-9]{1,2}[-.\/][0-9]{1,2}[-.\/][0-9]{2,4})/i', $text, $m)) {
            $effectiveDate = self::formatDate($m[1]);
        } elseif (preg_match('/w\.?e\.?f\.?\s*([0-9]{1,2}(?:st|nd|rd|th)?\s+[A-Za-z]+\,?\s+[0-9]{4})/i', $text, $m)) {
            $effectiveDate = date('Y-m-d', strtotime(str_replace(['st', 'nd', 'rd', 'th'], '', $m[1])));
        }

        if (!$effectiveDate) {
            $effectiveDate = date('Y-m-d');
        }

        $rates = [];
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Pattern: AED UAE Dirham 1.0 27 25.45 OR USD US Dollar 1.0 97.2 95.45
            // CODE (3 caps) NAME (string) UNIT (num) IMPORT_RATE (num) EXPORT_RATE (num)
            if (preg_match('/^([A-Z]{3})\s+(.+?)\s+([0-9]+(?:\.[0-9]+)?)\s+([0-9]+(?:\.[0-9]+)?)\s+([0-9]+(?:\.[0-9]+)?)$/i', $line, $matches)) {
                $code = strtoupper(trim($matches[1]));
                $name = trim($matches[2]);
                $unitVal = floatval($matches[3]);
                $importRate = floatval($matches[4]);
                $exportRate = floatval($matches[5]);

                // Avoid header row matches
                if ($code === 'CUR' || strpos(strtolower($name), 'currency') !== false) {
                    continue;
                }

                $unitText = ($unitVal > 1) ? ((int)$unitVal . ' ' . $code) : ('1 ' . $code);

                $rates[] = [
                    'currency_code' => $code,
                    'currency_name' => $name,
                    'currency_unit' => $unitText,
                    'unit_value'    => $unitVal,
                    'rate_import'   => $importRate,
                    'rate_export'   => $exportRate,
                ];
            }
        }

        return [
            'success' => count($rates) > 0,
            'message' => count($rates) > 0 ? ('Successfully parsed ' . count($rates) . ' currency rates.') : 'No currency rate entries found in text.',
            'notification_no' => $notificationNo,
            'effective_date'  => $effectiveDate,
            'rates' => $rates
        ];
    }

    private static function formatDate(string $dateStr): string
    {
        $dateStr = str_replace('.', '-', $dateStr);
        $parts = preg_split('/[-.\/]/', $dateStr);
        if (count($parts) === 3) {
            $day = (int)$parts[0];
            $month = (int)$parts[1];
            $year = (int)$parts[2];
            if ($year < 100) $year += 2000;
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
        $ts = strtotime($dateStr);
        return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
    }
}
