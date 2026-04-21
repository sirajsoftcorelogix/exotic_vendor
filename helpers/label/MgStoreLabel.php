<?php

declare(strict_types=1);

use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

/**
 * MG Road large label — 75 × 50 mm, CODE128 (SKU), title, material, price (Incl. GST), H×W×D.
 * Callable from controllers/models like {@see JewelryLabel}.
 */
final class MgStoreLabel
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
        $base = require self::projectRoot() . '/helpers/label/mg_store_label_config.php';
        if (!is_array($base)) {
            $base = [];
        }
        if ($overrides === null || $overrides === []) {
            return $base;
        }
        return array_replace($base, $overrides);
    }

    /**
     * @param array<string, mixed> $product from getProduct() / similar
     * @return array{
     *   sku: string,
     *   title: string,
     *   material: string,
     *   price: string,
     *   height: string,
     *   width: string,
     *   depth: string,
     *   measure_unit: string
     * }
     */
    public static function fromProductRow(array $product): array
    {
        $sku = trim((string)($product['sku'] ?? ''));
        $mrpRaw = $product['price_india'] ?? '';
        $price = trim((string)$mrpRaw);
        if ($mrpRaw !== '' && $mrpRaw !== null && is_numeric($mrpRaw)) {
            $price = number_format((float)$mrpRaw, 0, '.', ',');
        }

        $unit = trim((string)($product['length_unit'] ?? 'inch'));
        if ($unit === '') {
            $unit = 'inch';
        }

        return [
            'sku' => $sku,
            'title' => trim((string)($product['title'] ?? '')),
            'material' => trim((string)($product['material'] ?? '')),
            'price' => $price,
            'height' => trim((string)($product['prod_height'] ?? '')),
            'width' => trim((string)($product['prod_width'] ?? '')),
            'depth' => trim((string)($product['prod_length'] ?? '')),
            'measure_unit' => $unit,
        ];
    }

    public static function barcodePayloadFromData(array $data): string
    {
        $sku = trim((string)($data['sku'] ?? ''));
        if ($sku !== '') {
            return $sku;
        }
        return '0';
    }

    /**
     * ASCII-safe payload for Picqer CODE128 (avoids InvalidCharacterException).
     */
    private static function normalizeSkuForCode128(string $sku): string
    {
        if ($sku === '') {
            return '0';
        }
        $conv = @iconv('UTF-8', 'ASCII//TRANSLIT', $sku);
        if ($conv !== false && $conv !== '') {
            $sku = $conv;
        }
        $sku = preg_replace('/[^\x20-\x7E]/', '', $sku);
        $sku = trim((string)$sku);

        return $sku !== '' ? $sku : '0';
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public static function barcodeDataUri(array $data, ?array $config = null): string
    {
        if (!self::loadVendor() || !class_exists(BarcodeGeneratorSVG::class)) {
            return '';
        }
        $cfg = self::config($config);
        $code = self::normalizeSkuForCode128(self::barcodePayloadFromData($data));
        $type = (string)($cfg['barcode_type'] ?? 'C128');
        $wFactor = max(1, (int)($cfg['barcode_width_factor'] ?? 1));
        $barH = max(8, (int)($cfg['barcode_height_px'] ?? 35));

        $canPng = class_exists(BarcodeGeneratorPNG::class)
            && (extension_loaded('imagick') || function_exists('imagecreate'));

        if ($canPng) {
            try {
                $generator = new BarcodeGeneratorPNG();
                $png = $generator->getBarcode($code, $type, $wFactor, $barH);

                return 'data:image/png;base64,' . base64_encode($png);
            } catch (Throwable $e) {
                // Fall back to SVG when PNG/GD/Imagick is unavailable or encoding fails.
            }
        }

        try {
            $svgGen = new BarcodeGeneratorSVG();
            $svg = $svgGen->getBarcode($code, $type, (float)$wFactor, (float)$barH);
        } catch (Throwable $e) {
            $svgGen = new BarcodeGeneratorSVG();
            $svg = $svgGen->getBarcode('0', $svgGen::TYPE_CODE_128, 2.0, (float)$barH);
        }

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * @param array{
     *   sku?: string,
     *   title?: string,
     *   material?: string,
     *   price?: string,
     *   height?: string,
     *   width?: string,
     *   depth?: string,
     *   measure_unit?: string
     * } $data
     * @param array<string, mixed>|null $config
     */
    public static function renderInnerHtml(array $data, ?array $config = null): string
    {
        $cfg = self::config($config);
        $w = (float)($cfg['label_width_mm'] ?? 75);
        $h = (float)($cfg['label_height_mm'] ?? 50);
        $pad = (float)($cfg['padding_mm'] ?? 2);
        $ff = (string)($cfg['font_family'] ?? 'Arial, Helvetica, sans-serif');
        $fsTitle = (float)($cfg['font_size_title_mm'] ?? 2.75);
        $fsBody = (float)($cfg['font_size_body_mm'] ?? 2.15);
        $lh = (float)($cfg['line_height'] ?? 1.2);
        $fsCap = (float)($cfg['barcode_caption_font_mm'] ?? 2.0);
        $showBarText = !empty($cfg['barcode_display_value']);

        $e = static function (string $s): string {
            return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };

        $sku = (string)($data['sku'] ?? '');
        $title = (string)($data['title'] ?? '');
        $material = (string)($data['material'] ?? '');
        $price = (string)($data['price'] ?? '');
        $height = (string)($data['height'] ?? '');
        $width = (string)($data['width'] ?? '');
        $depth = (string)($data['depth'] ?? '');
        $mUnit = (string)($data['measure_unit'] ?? 'inch');

        $barUri = self::barcodeDataUri($data, $cfg);
        $barcodeWidthFactor = max(1, (int)($cfg['barcode_width_factor'] ?? 1));
        $barcodeHeightPx = max(8, (int)($cfg['barcode_height_px'] ?? 35));

        $dimParts = [];
        if ($height !== '') {
            $dimParts[] = 'Height: ' . $e($height) . ' ' . $e($mUnit);
        }
        if ($width !== '') {
            $dimParts[] = 'Width: ' . $e($width) . ' ' . $e($mUnit);
        }
        if ($depth !== '') {
            $dimParts[] = 'Depth: ' . $e($depth) . ' ' . $e($mUnit);
        }
        $dimLine = $dimParts !== [] ? implode(', ', $dimParts) : 'Height: —, Width: —, Depth: —';

        $priceLine = $price !== '' ? ('Price: ₹' . $e($price) . ' (Incl. GST)') : 'Price: — (Incl. GST)';
        $matLine = $material !== '' ? ('Material: ' . $e($material)) : 'Material: —';

        $row1Extra = '';
        if ($showBarText) {
            $cap = $sku !== '' ? $e($sku) : '—';
            $row1Extra = '<div style="margin-top:0.35mm;text-align:center;font-size:' . $e((string)$fsCap) . 'mm;font-weight:600;">' . $cap . '</div>';
        }

        $barCodeEsc = $e(self::barcodePayloadFromData($data));
        $barEl = $barUri !== ''
            ? '<img src="' . $e($barUri) . '" alt="" style="max-width:100%;height:auto;max-height:14mm;object-fit:contain;display:inline-block;vertical-align:top;" />'
            : '<svg class="mgs-js-barcode"'
                . ' data-barcode="' . $barCodeEsc . '"'
                . ' data-width-factor="' . $e((string)$barcodeWidthFactor) . '"'
                . ' data-height-px="' . $e((string)$barcodeHeightPx) . '"'
                . ' style="max-width:100%;height:auto;max-height:14mm;display:inline-block;vertical-align:top;"></svg>';

        $blankRows = max(0, min(10, (int)($cfg['blank_top_rows'] ?? 2)));
        $blankRowH = max(0.0, (float)($cfg['blank_top_row_height_mm'] ?? 2.6));
        $blankRowsHtml = '';
        for ($i = 0; $i < $blankRows; $i++) {
            $blankRowsHtml .= '<div class="mgs-row mgs-row--blank" style="flex:0 0 auto;min-height:' . $e((string)$blankRowH) . 'mm;height:' . $e((string)$blankRowH) . 'mm;line-height:' . $e((string)$blankRowH) . 'mm;font-size:0;">&#8203;</div>';
        }

        return '<div class="mgs-sheet" style="'
            . 'box-sizing:border-box;width:' . $e((string)$w) . 'mm;height:' . $e((string)$h) . 'mm;'
            . 'padding:' . $e((string)$pad) . 'mm;display:flex;flex-direction:column;align-items:stretch;'
            . 'justify-content:flex-start;gap:0.5mm;font-family:' . $e($ff) . ';'
            . 'font-size:' . $e((string)$fsBody) . 'mm;line-height:' . $e((string)$lh) . ';color:#000;background:#fff;'
            . 'border:0.15mm solid #000;'
            . '">'
            . $blankRowsHtml
            . '<div class="mgs-row mgs-row--barcode" style="flex:0 0 auto;text-align:center;">'
            . $barEl
            . $row1Extra
            . '</div>'
            . '<div class="mgs-row" style="font-weight:800;font-size:' . $e((string)$fsBody) . 'mm;">SKU: <span style="font-weight:900;">' . ($sku !== '' ? $e($sku) : '—') . '</span></div>'
            . '<div class="mgs-row" style="font-weight:600;font-size:' . $e((string)$fsTitle) . 'mm;line-height:1.15;max-height:7.5mm;overflow:hidden;">'
            . ($title !== '' ? $e($title) : '—')
            . '</div>'
            . '<div class="mgs-row">' . $e($matLine) . '</div>'
            . '<div class="mgs-row" style="font-weight:700;">' . $priceLine . '</div>'
            . '<div class="mgs-row" style="font-size:' . $e((string)$fsBody) . 'mm;">' . $dimLine . '</div>'
            . '</div>';
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public static function renderPrintDocument(array $data, ?array $config = null): string
    {
        $cfg = self::config($config);
        $w = (float)($cfg['label_width_mm'] ?? 75);
        $h = (float)($cfg['label_height_mm'] ?? 50);
        $inner = self::renderInnerHtml($data, $cfg);

        $title = 'MG Road label';
        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>'
            . '<style>'
            . '@page { size: ' . $w . 'mm ' . $h . 'mm; margin: 0; }'
            . 'html,body{margin:0;padding:0;}'
            . 'body{-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
            . '.mgs-page{width:' . $w . 'mm;height:' . $h . 'mm;margin:0;page-break-after:always;page-break-inside:avoid;}'
            . '.mgs-page:last-child{page-break-after:auto;}'
            . '</style>'
            . '<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>'
            . '</head><body>'
            . '<div class="mgs-page">' . $inner . '</div>'
            . '<script>(function(){'
            . 'function draw(){var els=document.querySelectorAll("svg.mgs-js-barcode");'
            . 'for(var i=0;i<els.length;i++){var el=els[i];'
            . 'try{JsBarcode(el,el.getAttribute("data-barcode")||"0",{format:"CODE128",displayValue:false,width:parseInt(el.getAttribute("data-width-factor")||"1",10)||1,height:parseInt(el.getAttribute("data-height-px")||"35",10)||35,margin:0,background:"#ffffff",lineColor:"#000000"});}catch(e){}}}'
            . 'if(window.JsBarcode){draw();window.focus();window.print();return;}'
            . 'var t0=Date.now();'
            . '(function waitJs(){if(window.JsBarcode){draw();window.focus();window.print();return;}'
            . 'if(Date.now()-t0>2500){window.focus();window.print();return;}setTimeout(waitJs,80);}());'
            . '}());</script>'
            . '</body></html>';
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param array<string, mixed>|null $config
     */
    public static function renderPrintDocumentBatch(array $rows, ?array $config = null): string
    {
        $cfg = self::config($config);
        $w = (float)($cfg['label_width_mm'] ?? 75);
        $h = (float)($cfg['label_height_mm'] ?? 50);
        $pages = '';
        foreach ($rows as $row) {
            $pages .= '<div class="mgs-page">' . self::renderInnerHtml(is_array($row) ? $row : [], $cfg) . '</div>';
        }
        $title = 'MG Road labels';
        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<title>' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>'
            . '<style>'
            . '@page { size: ' . $w . 'mm ' . $h . 'mm; margin: 0; }'
            . 'html,body{margin:0;padding:0;}'
            . 'body{-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
            . '.mgs-page{width:' . $w . 'mm;height:' . $h . 'mm;margin:0;page-break-after:always;page-break-inside:avoid;}'
            . '.mgs-page:last-child{page-break-after:auto;}'
            . '</style>'
            . '<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>'
            . '</head><body>' . $pages
            . '<script>(function(){'
            . 'function draw(){var els=document.querySelectorAll("svg.mgs-js-barcode");'
            . 'for(var i=0;i<els.length;i++){var el=els[i];'
            . 'try{JsBarcode(el,el.getAttribute("data-barcode")||"0",{format:"CODE128",displayValue:false,width:parseInt(el.getAttribute("data-width-factor")||"1",10)||1,height:parseInt(el.getAttribute("data-height-px")||"35",10)||35,margin:0,background:"#ffffff",lineColor:"#000000"});}catch(e){}}}'
            . 'if(window.JsBarcode){draw();window.focus();window.print();return;}'
            . 'var t0=Date.now();'
            . '(function waitJs(){if(window.JsBarcode){draw();window.focus();window.print();return;}'
            . 'if(Date.now()-t0>2500){window.focus();window.print();return;}setTimeout(waitJs,80);}());'
            . '}());</script>'
            . '</body></html>';
    }
}
