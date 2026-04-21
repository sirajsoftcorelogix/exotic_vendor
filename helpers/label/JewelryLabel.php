<?php

declare(strict_types=1);

use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * 100 × 12.9 mm jewelry label: left 47% — Code 128 barcode (SKU); text block row Color | Size | MRP; then SKU (full width, wraps).
 * Remainder blank for margin / peel. Use from controllers, models, or CLI.
 */
final class JewelryLabel
{
    private static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function loadVendor(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $autoload = self::projectRoot() . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('Composer autoload missing; run composer install.');
        }
        require_once $autoload;
        $done = true;
    }

    /**
     * Merge defaults from jewelry_label_config.php with optional overrides.
     *
     * @param array<string, mixed>|null $overrides
     * @return array<string, mixed>
     */
    public static function config(?array $overrides = null): array
    {
        $base = require self::projectRoot() . '/helpers/label/jewelry_label_config.php';
        if (!is_array($base)) {
            $base = [];
        }
        if ($overrides === null || $overrides === []) {
            return $base;
        }
        return array_replace($base, $overrides);
    }

    /**
     * Map a product row (e.g. getProduct()) to label fields.
     *
     * @param array<string, mixed> $product
     * @return array{sku: string, color: string, size: string, mrp: string}
     */
    public static function fromProductRow(array $product): array
    {
        $sku = trim((string)($product['sku'] ?? ''));
        $mrpRaw = $product['price_india'] ?? '';
        $mrp = $mrpRaw;
        if ($mrpRaw !== '' && $mrpRaw !== null && is_numeric($mrpRaw)) {
            $mrp = number_format((float)$mrpRaw, 0, '.', ',');
        }
        return [
            'sku' => $sku,
            'color' => trim((string)($product['color'] ?? '')),
            'size' => trim((string)($product['size'] ?? '')),
            'mrp' => trim((string)$mrp),
        ];
    }

    /**
     * Barcode payload is the SKU (Code 128). Empty SKU uses a minimal placeholder so bars still render.
     */
    public static function barcodePayloadFromData(array $data): string
    {
        $sku = trim((string)($data['sku'] ?? ''));
        return $sku !== '' ? $sku : '-';
    }

    /**
     * PNG data URI for Code 128 encoding {@see barcodePayloadFromData()}.
     *
     * @param array<string, mixed>|null $config
     */
    public static function barcodeDataUri(array $data, ?array $config = null): string
    {
        self::loadVendor();
        $cfg = self::config($config);
        $payload = self::barcodePayloadFromData($data);
        $hPx = max(20, (int)($cfg['barcode_height_px'] ?? 50));
        $wFactor = max(1, (int)($cfg['barcode_width_factor'] ?? 2));

        $generator = new BarcodeGeneratorPNG();
        $png = $generator->getBarcode($payload, $generator::TYPE_CODE_128, $wFactor, $hPx);

        return 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * @param array<string, mixed> $cfg
     * @return array{max_width_mm: float, max_height_mm: float}
     */
    public static function barcodeDisplayBoundsMm(array $cfg): array
    {
        $h = (float)($cfg['label_height_mm'] ?? 12.9);
        $pad = (float)($cfg['padding_mm'] ?? 0.6);
        $inner = max(1.0, $h - 2 * $pad);
        $maxH = (float)($cfg['barcode_max_height_mm'] ?? 9.0);
        $maxH = min($maxH, $inner);
        $maxW = (float)($cfg['barcode_max_width_mm'] ?? 44.0);

        return ['max_width_mm' => max(8.0, $maxW), 'max_height_mm' => max(4.0, $maxH)];
    }

    /**
     * Single label inner HTML (no html/body). For embedding or batch wrappers.
     *
     * @param array{sku?: string, color?: string, size?: string, mrp?: string} $data
     * @param array<string, mixed>|null $config
     */
    public static function renderInnerHtml(array $data, ?array $config = null): string
    {
        $cfg = self::config($config);
        $w = (float)($cfg['label_width_mm'] ?? 100);
        $h = (float)($cfg['label_height_mm'] ?? 12.9);
        $pad = (float)($cfg['padding_mm'] ?? 0.6);
        $fs = (float)($cfg['font_size_mm'] ?? 1.85);
        $ff = (string)($cfg['font_family'] ?? 'Arial, Helvetica, sans-serif');
        $lh = (float)($cfg['line_height'] ?? 1.28);
        $textRowGapMm = (float)($cfg['text_block_row_gap_mm'] ?? 0.55);

        $bounds = self::barcodeDisplayBoundsMm($cfg);
        $barW = $bounds['max_width_mm'];
        $barH = $bounds['max_height_mm'];
        $barUri = self::barcodeDataUri($data, $cfg);

        $sku = (string)($data['sku'] ?? '');
        $color = (string)($data['color'] ?? '');
        $size = (string)($data['size'] ?? '');
        $mrp = (string)($data['mrp'] ?? '');

        $e = static function (string $s): string {
            return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };

        $mrpLine = $mrp !== '' ? ('MRP: ₹' . $e($mrp)) : 'MRP: —';
        $skuVal = $sku !== '' ? $e($sku) : '—';
        $lhE = $e((string)$lh);
        $rowGapE = $e((string)$textRowGapMm);

        $padE = $e((string)$pad);
        // Top inset includes base padding + one line-height (font_size_mm × line_height) as extra headroom
        $lineHeightMm = $fs * $lh;
        $padTopContent = $e((string)($pad + 0.3 + $lineHeightMm));

        $skuBlock = ''
            . '<div class="jl-sku" style="width:100%;max-width:100%;box-sizing:border-box;text-align:left;'
            . 'white-space:normal;word-break:break-word;overflow-wrap:anywhere;line-height:' . $lhE . ';">'
            . '<span style="font-weight:700;">SKU: </span>'
            . '<span style="font-weight:600;">' . $skuVal . '</span>'
            . '</div>';

        $detailsRow = ''
            . '<div class="jl-details" style="display:flex;flex-direction:row;align-items:flex-start;justify-content:flex-start;gap:1.2mm;width:100%;min-width:0;">'
            . '<div class="jl-col jl-col--color" style="flex:0 1 auto;min-width:0;display:flex;flex-direction:column;justify-content:flex-start;align-items:flex-start;text-align:left;line-height:' . $lhE . ';">'
            . '<span style="font-weight:700;">Color</span>'
            . '<span style="font-weight:400;">' . ($color !== '' ? $e($color) : '—') . '</span>'
            . '</div>'
            . '<div class="jl-col jl-col--size" style="flex:0 1 auto;min-width:0;display:flex;flex-direction:column;justify-content:flex-start;align-items:flex-start;text-align:left;line-height:' . $lhE . ';">'
            . '<span style="font-weight:700;">Size</span>'
            . '<span style="font-weight:400;">' . ($size !== '' ? $e($size) : '—') . '</span>'
            . '</div>'
            . '<div class="jl-col jl-col--mrp" style="flex:0 1 auto;min-width:0;display:flex;flex-direction:column;justify-content:flex-start;align-items:flex-start;text-align:left;line-height:' . $lhE . ';">'
            . '<span style="font-weight:700;white-space:nowrap;">' . $mrpLine . '</span>'
            . '</div>'
            . '</div>';

        $textCluster = ''
            . '<div class="jl-text-cluster" style="flex:1 1 0;min-width:0;display:flex;flex-direction:column;align-items:stretch;justify-content:flex-start;gap:' . $rowGapE . 'mm;">'
            . $detailsRow
            . $skuBlock
            . '</div>';

        $innerRow = ''
            . '<div class="jl-inner-row" style="display:flex;flex-direction:row;align-items:center;justify-content:flex-start;gap:1.2mm;width:100%;min-width:0;box-sizing:border-box;">'
            . '<div class="jl-col jl-col--barcode" style="flex:0 0 auto;min-width:0;display:flex;align-items:center;justify-content:center;">'
            . '<img src="' . $e($barUri) . '" alt="" style="max-width:' . $e((string)$barW) . 'mm;max-height:' . $e((string)$barH) . 'mm;width:auto;height:auto;object-fit:contain;object-position:left center;display:block;flex-shrink:0;" />'
            . '</div>'
            . $textCluster
            . '</div>';

        return '<div class="jl-sheet" style="'
            . 'box-sizing:border-box;width:' . $e((string)$w) . 'mm;height:' . $e((string)$h) . 'mm;'
            . 'display:flex;flex-direction:row;align-items:stretch;'
            . 'font-family:' . $e($ff) . ';font-size:' . $e((string)$fs) . 'mm;line-height:' . $e((string)$lh) . ';'
            . 'color:#000;background:#fff;border:0.12mm solid #000;'
            . '">'
            . '<div class="jl-zone jl-zone--content" style="box-sizing:border-box;flex:0 0 47%;width:47%;max-width:47%;min-height:0;align-self:stretch;'
            . 'display:flex;flex-direction:column;justify-content:center;'
            . 'padding-top:' . $padTopContent . 'mm;padding-bottom:' . $padTopContent . 'mm;padding-left:' . $padE . 'mm;padding-right:0.5mm;">'
            . $innerRow
            . '</div>'
            . '<div class="jl-zone jl-zone--blank" style="box-sizing:border-box;flex:0 0 53%;width:53%;max-width:53%;'
            . 'background:#fff;padding-top:' . $padTopContent . 'mm;padding-bottom:' . $padTopContent . 'mm;padding-right:' . $padE . 'mm;"></div>'
            . '</div>';
    }

    /**
     * Full HTML document for browser print (@page size matches label).
     *
     * @param array{sku?: string, color?: string, size?: string, mrp?: string} $data
     * @param array<string, mixed>|null $config
     */
    public static function renderPrintDocument(array $data, ?array $config = null): string
    {
        $cfg = self::config($config);
        $w = (float)($cfg['label_width_mm'] ?? 100);
        $h = (float)($cfg['label_height_mm'] ?? 12.9);
        $inner = self::renderInnerHtml($data, $cfg);

        $title = 'Jewelry label';
        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>'
            . '<style>'
            . '@page { size: ' . $w . 'mm ' . $h . 'mm; margin: 0; }'
            . 'html,body{margin:0;padding:0;}'
            . 'body{-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
            . '.jl-page{width:' . $w . 'mm;height:' . $h . 'mm;margin:0;page-break-after:always;page-break-inside:avoid;}'
            . '.jl-page:last-child{page-break-after:auto;}'
            . '</style></head><body>'
            . '<div class="jl-page">' . $inner . '</div>'
            . '<script>window.onload=function(){window.focus();window.print();};</script>'
            . '</body></html>';
    }

    /**
     * Multiple labels in one print job (e.g. from a model returning many rows).
     *
     * @param list<array{sku?: string, color?: string, size?: string, mrp?: string}> $rows
     * @param array<string, mixed>|null $config
     */
    public static function renderPrintDocumentBatch(array $rows, ?array $config = null): string
    {
        $cfg = self::config($config);
        $w = (float)($cfg['label_width_mm'] ?? 100);
        $h = (float)($cfg['label_height_mm'] ?? 12.9);
        $pages = '';
        foreach ($rows as $row) {
            $pages .= '<div class="jl-page">' . self::renderInnerHtml(is_array($row) ? $row : [], $cfg) . '</div>';
        }
        $title = 'Jewelry labels';
        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<title>' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>'
            . '<style>'
            . '@page { size: ' . $w . 'mm ' . $h . 'mm; margin: 0; }'
            . 'html,body{margin:0;padding:0;}'
            . 'body{-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
            . '.jl-page{width:' . $w . 'mm;height:' . $h . 'mm;margin:0;page-break-after:always;page-break-inside:avoid;}'
            . '.jl-page:last-child{page-break-after:auto;}'
            . '</style></head><body>' . $pages
            . '<script>window.onload=function(){window.focus();window.print();};</script>'
            . '</body></html>';
    }
}
