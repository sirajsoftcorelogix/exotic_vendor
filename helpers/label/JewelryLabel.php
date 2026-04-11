<?php

declare(strict_types=1);

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * 100 × 12.9 mm jewelry label: left 47% — QR; text block row Color | Size | MRP; then SKU (full width, wraps).
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
     * QR payload is the SKU string (empty SKU uses placeholder so the symbol still renders).
     */
    public static function qrPayloadFromData(array $data): string
    {
        $sku = trim((string)($data['sku'] ?? ''));
        return $sku !== '' ? $sku : '—';
    }

    /**
     * PNG data URI for the QR encoding {@see qrPayloadFromData()}.
     *
     * @param array<string, mixed>|null $config
     */
    public static function qrDataUri(array $data, ?array $config = null): string
    {
        self::loadVendor();
        $cfg = self::config($config);
        $payload = self::qrPayloadFromData($data);
        $size = max(16, (int)($cfg['qr_builder_size_px'] ?? 72));
        $margin = max(0, (int)($cfg['qr_margin'] ?? 0));

        $qrCode = new QrCode(
            data: $payload,
            size: $size,
            margin: $margin
        );
        $writer = new PngWriter();
        $png = $writer->write($qrCode)->getString();

        return 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * Effective QR box side in mm (fits inside label minus padding).
     *
     * @param array<string, mixed> $cfg
     */
    public static function qrDisplaySideMm(array $cfg): float
    {
        $h = (float)($cfg['label_height_mm'] ?? 12.9);
        $pad = (float)($cfg['padding_mm'] ?? 0.6);
        $inner = max(1.0, $h - 2 * $pad);
        $want = (float)($cfg['qr_max_side_mm'] ?? 10.0);
        return min($want, $inner);
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
        $lh = (float)($cfg['line_height'] ?? 1.1);

        $qrMm = self::qrDisplaySideMm($cfg);
        $qrUri = self::qrDataUri($data, $cfg);

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

        $padE = $e((string)$pad);
        $padTopContent = $e((string)($pad + 0.3));

        $skuBlock = ''
            . '<div class="jl-sku" style="width:100%;max-width:100%;box-sizing:border-box;text-align:left;'
            . 'white-space:normal;word-break:break-word;overflow-wrap:anywhere;line-height:' . $lhE . ';">'
            . '<span style="font-weight:700;">SKU: </span>'
            . '<span style="font-weight:600;">' . $skuVal . '</span>'
            . '</div>';

        $detailsRow = ''
            . '<div class="jl-details" style="display:flex;flex-direction:row;align-items:flex-start;justify-content:flex-start;gap:0.8mm;width:100%;min-width:0;">'
            . '<div class="jl-col jl-col--color" style="flex:0 1 auto;min-width:0;display:flex;flex-direction:column;justify-content:flex-start;align-items:flex-start;text-align:left;">'
            . '<span style="font-weight:700;">Color</span>'
            . '<span style="font-weight:400;">' . ($color !== '' ? $e($color) : '—') . '</span>'
            . '</div>'
            . '<div class="jl-col jl-col--size" style="flex:0 1 auto;min-width:0;display:flex;flex-direction:column;justify-content:flex-start;align-items:flex-start;text-align:left;">'
            . '<span style="font-weight:700;">Size</span>'
            . '<span style="font-weight:400;">' . ($size !== '' ? $e($size) : '—') . '</span>'
            . '</div>'
            . '<div class="jl-col jl-col--mrp" style="flex:0 1 auto;min-width:0;display:flex;flex-direction:column;justify-content:flex-start;align-items:flex-start;text-align:left;">'
            . '<span style="font-weight:700;white-space:nowrap;">' . $mrpLine . '</span>'
            . '</div>'
            . '</div>';

        $textCluster = ''
            . '<div class="jl-text-cluster" style="flex:1 1 0;min-width:0;display:flex;flex-direction:column;align-items:stretch;justify-content:flex-start;gap:0.35mm;">'
            . $detailsRow
            . $skuBlock
            . '</div>';

        $innerRow = ''
            . '<div class="jl-col jl-col--qr" style="flex:0 0 auto;display:flex;align-items:flex-start;justify-content:center;align-self:flex-start;">'
            . '<img src="' . $e($qrUri) . '" alt="" style="width:' . $e((string)$qrMm) . 'mm;height:' . $e((string)$qrMm) . 'mm;object-fit:contain;display:block;" />'
            . '</div>'
            . $textCluster;

        return '<div class="jl-sheet" style="'
            . 'box-sizing:border-box;width:' . $e((string)$w) . 'mm;height:' . $e((string)$h) . 'mm;'
            . 'display:flex;flex-direction:row;align-items:stretch;'
            . 'font-family:' . $e($ff) . ';font-size:' . $e((string)$fs) . 'mm;line-height:' . $e((string)$lh) . ';'
            . 'color:#000;background:#fff;border:0.12mm solid #000;'
            . '">'
            . '<div class="jl-zone jl-zone--content" style="box-sizing:border-box;flex:0 0 47%;width:47%;max-width:47%;'
            . 'display:flex;flex-direction:row;align-items:flex-start;justify-content:flex-start;gap:0.8mm;'
            . 'padding-top:' . $padTopContent . 'mm;padding-bottom:' . $padE . 'mm;padding-left:' . $padE . 'mm;padding-right:0.5mm;">'
            . $innerRow
            . '</div>'
            . '<div class="jl-zone jl-zone--blank" style="box-sizing:border-box;flex:0 0 53%;width:53%;max-width:53%;'
            . 'background:#fff;padding-top:' . $padTopContent . 'mm;padding-bottom:' . $padE . 'mm;padding-right:' . $padE . 'mm;"></div>'
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
