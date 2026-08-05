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

        $text = null;

        if (class_exists('Smalot\PdfParser\Parser')) {
            try {
                $parser = new PdfParser();
                $pdf = $parser->parseFile($filePath);
                $text = $pdf->getText();
            } catch (\Throwable $e) {
                $text = null;
            }
        }

        // Fallback to pure-PHP PDF text extractor if Smalot is unavailable or returned empty text
        if (empty(trim((string)$text))) {
            $text = self::extractTextFallback($filePath);
        }

        if (empty(trim((string)$text))) {
            return [
                'success' => false,
                'message' => 'Could not extract text from PDF file. Please ensure it is a valid text-based exchange rate PDF.',
                'rates' => []
            ];
        }

        $res = self::parseTextContent($text);
        if (!$res['success'] && class_exists('Smalot\PdfParser\Parser') && empty($text)) {
            $res['message'] = 'Failed to parse currency rates from PDF: ' . $res['message'];
        }

        return $res;
    }

    /**
     * Parse extracted text content from CBIC notification
     */
    public static function parseTextContent(string $text): array
    {
        // Strip null bytes if present (e.g. UTF-16BE streams)
        if (strpos($text, "\x00") !== false) {
            $text = str_replace("\x00", '', $text);
        }

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

    /**
     * Fallback pure-PHP text extractor for PDFs when Smalot\PdfParser is not installed.
     */
    private static function extractTextFallback(string $filePath): string
    {
        $content = @file_get_contents($filePath);
        if (!$content) {
            return '';
        }

        $text = '';

        // Extract stream objects
        preg_match_all('/stream[\r\n]+(.*?)endstream/s', $content, $matches, PREG_OFFSET_CAPTURE);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                $rawStream = $match[0];
                $streamOffset = $match[1];

                // Check stream dictionary preceding "stream" keyword for /FlateDecode
                $headerChunk = substr($content, max(0, $streamOffset - 1000), 1000);
                
                $data = $rawStream;
                if (stripos($headerChunk, '/FlateDecode') !== false) {
                    // Try gzuncompress or gzinflate or zlib_decode
                    $decompressed = @gzuncompress($rawStream);
                    if ($decompressed === false) {
                        $decompressed = @gzinflate(substr($rawStream, 2));
                    }
                    if ($decompressed === false) {
                        $decompressed = @zlib_decode($rawStream);
                    }
                    if ($decompressed !== false) {
                        $data = $decompressed;
                    }
                }

                // Extract text from text blocks or stream commands
                $extractedStreamText = self::extractTextFromPdfStreamData($data);
                if (!empty($extractedStreamText)) {
                    $text .= $extractedStreamText . "\n";
                }
            }
        }

        // If stream extraction returned empty, try extracting from full raw content
        if (trim($text) === '') {
            $text = self::extractTextFromPdfStreamData($content);
        }

        return $text;
    }

    /**
     * Extract text strings from raw or decompressed PDF stream payload
     */
    private static function extractTextFromPdfStreamData(string $data): string
    {
        $out = '';

        if (preg_match_all('/BT\s+(.*?)\s+ET/s', $data, $btMatches)) {
            $blocks = $btMatches[1];
        } else {
            $blocks = [$data];
        }

        foreach ($blocks as $block) {
            $blockLines = preg_split('/\r\n|\r|\n/', $block);
            foreach ($blockLines as $line) {
                $lineText = '';

                // Handle TJ array: [(...) -10 (...)] TJ
                if (preg_match_all('/\[\s*(.*?)\s*\]\s*TJ/s', $line, $tjMatches)) {
                    foreach ($tjMatches[1] as $arrayContent) {
                        if (preg_match_all('/\((.*?)(?<!\\\\)\)/s', $arrayContent, $strMatches)) {
                            foreach ($strMatches[1] as $s) {
                                $lineText .= self::unescapePdfString($s);
                            }
                        }
                    }
                }
                
                // Handle Tj: (string) Tj or (string) ' or (string) "
                if (preg_match_all('/\((.*?)(?<!\\\\)\)\s*(?:Tj|\'|")/s', $line, $tjMatches)) {
                    foreach ($tjMatches[1] as $s) {
                        $lineText .= self::unescapePdfString($s);
                    }
                }

                // Handle hex strings: <48656c6c6f> Tj
                if (preg_match_all('/<([0-9a-fA-F]+)>\s*(?:Tj|\'|")/s', $line, $hexMatches)) {
                    foreach ($hexMatches[1] as $hex) {
                        $lineText .= @hex2bin($hex);
                    }
                }

                if (trim($lineText) !== '') {
                    $out .= trim($lineText) . "\n";
                }
            }
        }

        return $out;
    }

    /**
     * Unescape standard PDF string escape codes
     */
    private static function unescapePdfString(string $s): string
    {
        $search  = ['\\\\', '\(', '\)', '\n', '\r', '\t'];
        $replace = ['\\',   '(',  ')',  "\n", "\r", "\t"];
        $s = str_replace($search, $replace, $s);

        $s = preg_replace_callback('/\\\\([0-7]{1,3})/', function($m) {
            return chr(octdec($m[1]));
        }, $s);

        return $s;
    }
}
