<?php

declare(strict_types=1);

use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * Textile label — 64 × 34 mm: location — print date, CODE128 (SKU), SKU row.
 */
final class TextileLabel
{
    private static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function loadVendor(): bool
    {
        static $done = false;
        if ($done) {
            return true;
        }
        $autoload = self::projectRoot() . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            return false;
        }
        require_once $autoload;
        $done = true;
        return true;
    }

    /**
     * @param array<string, mixed>|null $overrides
     * @return array<string, mixed>
     */
    public static function config(?array $overrides = null): array
    {
        $base = require self::projectRoot() . '/helpers/label/textile_label_config.php';
        if (!is_array($base)) {
            $base = [];
        }
        if ($overrides === null || $overrides === []) {
            return $base;
        }
        return array_replace($base, $overrides);
    }

    /**
     * @param array<string, mixed> $product
     * @return array{location: string, sku: string, print_date: string}
     */
    public static function fromProductRow(array $product): array
    {
        $cfg = self::config();
        $fmt = (string)($cfg['print_date_format'] ?? 'd-m-Y');

        return [
            'location' => trim((string)($product['location'] ?? '')),
            'sku' => trim((string)($product['sku'] ?? '')),
            'print_date' => date($fmt),
        ];
    }

    public static function barcodePayloadFromData(array $data): string
    {
        $sku = trim((string)($data['sku'] ?? ''));
        return $sku !== '' ? $sku : '0';
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public static function barcodeDataUri(array $data, ?array $config = null): string
    {
        $vendorOk = self::loadVendor();
        $cfg = self::config($config);
        $code = self::barcodePayloadFromData($data);
        $type = (string)($cfg['barcode_type'] ?? 'C128');
        $wFactor = max(1, (int)($cfg['barcode_width_factor'] ?? 1));
        $barH = max(8, (int)($cfg['barcode_height_px'] ?? 45));

        if (!$vendorOk || !class_exists(BarcodeGeneratorPNG::class)) {
            return '';
        }

        $generator = new BarcodeGeneratorPNG();
        try {
            $png = $generator->getBarcode($code, $type, $wFactor, $barH);
        } catch (Throwable $e) {
            $png = $generator->getBarcode('0', $type, $wFactor, $barH);
        }

        return 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * @param array{location?: string, sku?: string, print_date?: string} $data
     * @param array<string, mixed>|null $config
     */
    public static function renderInnerHtml(array $data, ?array $config = null): string
    {
        $cfg = self::config($config);
        $w = (float)($cfg['label_width_mm'] ?? 64);
        $h = (float)($cfg['label_height_mm'] ?? 34);
        $pad = (float)($cfg['padding_mm'] ?? 1.2);
        $ff = (string)($cfg['font_family'] ?? 'Arial, Helvetica, sans-serif');
        $fsTop = (float)($cfg['font_size_top_mm'] ?? 2.35);
        $fsBarLbl = (float)($cfg['font_size_barcode_label_mm'] ?? 1.85);
        $fsSku = (float)($cfg['font_size_sku_mm'] ?? 2.45);
        $lh = (float)($cfg['line_height'] ?? 1.12);
        $showBarText = !empty($cfg['barcode_display_value']);
        $barFontPx = max(8, (int)($cfg['barcode_text_font_px'] ?? 11));

        $e = static function (string $s): string {
            return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };

        $loc = (string)($data['location'] ?? '');
        $sku = (string)($data['sku'] ?? '');
        $pdate = (string)($data['print_date'] ?? date('d-m-Y'));

        $row1 = ($loc !== '' ? $e($loc) : '—') . ' — ' . $e($pdate);

        $barUri = self::barcodeDataUri($data, $cfg);
        $wFactor = max(1, (int)($cfg['barcode_width_factor'] ?? 1));
        $barH = max(8, (int)($cfg['barcode_height_px'] ?? 45));
        $barCodeEsc = $e(self::barcodePayloadFromData($data));

        $barBlock = '';
        if ($barUri !== '') {
            $cap = '';
            if ($showBarText) {
                $cap = '<div style="margin-top:0.2mm;text-align:center;font-size:' . $e((string)$fsBarLbl) . 'mm;font-weight:600;">' . ($sku !== '' ? $e($sku) : '—') . '</div>';
            }
            $barBlock = '<img src="' . $e($barUri) . '" alt="" style="max-width:100%;height:auto;max-height:16mm;object-fit:contain;display:block;margin:0 auto;" />' . $cap;
        } else {
            $barBlock = '<svg class="tl-js-barcode" xmlns="http://www.w3.org/2000/svg"'
                . ' data-barcode="' . $barCodeEsc . '"'
                . ' data-width-factor="' . $e((string)$wFactor) . '"'
                . ' data-height-px="' . $e((string)$barH) . '"'
                . ' data-show-text="' . ($showBarText ? '1' : '0') . '"'
                . ' data-font-px="' . $e((string)$barFontPx) . '"'
                . ' style="max-width:100%;height:auto;max-height:18mm;display:block;margin:0 auto;"></svg>';
        }

        return '<div class="tl-sheet" style="'
            . 'box-sizing:border-box;width:' . $e((string)$w) . 'mm;height:' . $e((string)$h) . 'mm;'
            . 'padding:' . $e((string)$pad) . 'mm;display:flex;flex-direction:column;align-items:stretch;'
            . 'justify-content:flex-start;gap:0.35mm;font-family:' . $e($ff) . ';'
            . 'font-size:' . $e((string)$fsTop) . 'mm;line-height:' . $e((string)$lh) . ';color:#000;background:#fff;'
            . 'border:0.12mm solid #000;'
            . '">'
            . '<div class="tl-row tl-row--meta" style="flex:0 0 auto;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' . $row1 . '</div>'
            . '<div class="tl-row tl-row--barcode" style="flex:0 0 auto;">'
            . '<div style="font-size:' . $e((string)$fsBarLbl) . 'mm;font-weight:700;margin-bottom:0.15mm;">Barcode:</div>'
            . $barBlock
            . '</div>'
            . '<div class="tl-row tl-row--sku" style="flex:1 1 auto;display:flex;align-items:center;justify-content:center;text-align:center;font-weight:900;font-size:' . $e((string)$fsSku) . 'mm;">'
            . ($sku !== '' ? $e($sku) : '—')
            . '</div>'
            . '</div>';
    }

    private static function printScriptBlock(): string
    {
        return '<script>(function(){'
            . 'function draw(){var els=document.querySelectorAll("svg.tl-js-barcode");'
            . 'for(var i=0;i<els.length;i++){var el=els[i];'
            . 'var show=el.getAttribute("data-show-text")==="1";'
            . 'var fs=parseInt(el.getAttribute("data-font-px")||"11",10)||11;'
            . 'try{JsBarcode(el,el.getAttribute("data-barcode")||"0",{format:"CODE128",displayValue:show,width:parseInt(el.getAttribute("data-width-factor")||"1",10)||1,height:parseInt(el.getAttribute("data-height-px")||"45",10)||45,margin:2,background:"#ffffff",lineColor:"#000000",fontSize:fs,textMargin:2});}catch(e){}}}'
            . 'function finish(){window.focus();window.print();}'
            . 'draw();'
            . 'if(window.JsBarcode){finish();return;}'
            . 'var t0=Date.now();'
            . '(function wait(){if(window.JsBarcode){draw();finish();return;}if(Date.now()-t0>2500){finish();return;}setTimeout(wait,80);}());'
            . '}());</script>';
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public static function renderPrintDocument(array $data, ?array $config = null): string
    {
        $cfg = self::config($config);
        $w = (float)($cfg['label_width_mm'] ?? 64);
        $h = (float)($cfg['label_height_mm'] ?? 34);
        $inner = self::renderInnerHtml($data, $cfg);

        $title = 'Textile label';
        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>'
            . '<style>'
            . '@page { size: ' . $w . 'mm ' . $h . 'mm; margin: 0; }'
            . 'html,body{margin:0;padding:0;}'
            . 'body{-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
            . '.tl-page{width:' . $w . 'mm;height:' . $h . 'mm;margin:0;page-break-after:always;page-break-inside:avoid;}'
            . '.tl-page:last-child{page-break-after:auto;}'
            . '</style>'
            . '<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>'
            . '</head><body>'
            . '<div class="tl-page">' . $inner . '</div>'
            . self::printScriptBlock()
            . '</body></html>';
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param array<string, mixed>|null $config
     */
    public static function renderPrintDocumentBatch(array $rows, ?array $config = null): string
    {
        $cfg = self::config($config);
        $w = (float)($cfg['label_width_mm'] ?? 64);
        $h = (float)($cfg['label_height_mm'] ?? 34);
        $pages = '';
        foreach ($rows as $row) {
            $pages .= '<div class="tl-page">' . self::renderInnerHtml(is_array($row) ? $row : [], $cfg) . '</div>';
        }
        $title = 'Textile labels';
        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<title>' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>'
            . '<style>'
            . '@page { size: ' . $w . 'mm ' . $h . 'mm; margin: 0; }'
            . 'html,body{margin:0;padding:0;}'
            . 'body{-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
            . '.tl-page{width:' . $w . 'mm;height:' . $h . 'mm;margin:0;page-break-after:always;page-break-inside:avoid;}'
            . '.tl-page:last-child{page-break-after:auto;}'
            . '</style>'
            . '<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>'
            . '</head><body>' . $pages
            . self::printScriptBlock()
            . '</body></html>';
    }
}
