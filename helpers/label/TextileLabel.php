<?php

declare(strict_types=1);

use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * Textile label — 64 × 34 mm: location — print date, CODE128 (bars only), centered SKU row.
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
     * Shrink wide barcodes so long CODE128 payloads still fit inside the printable strip.
     *
     * @param non-empty-string $pngBinary
     * @return non-empty-string
     */
    private static function scaleBarcodePngToMaxWidth(string $pngBinary, float $labelWidthMm, float $paddingMm): string
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagescale')) {
            return $pngBinary;
        }
        $im = @imagecreatefromstring($pngBinary);
        if ($im === false) {
            return $pngBinary;
        }
        $iw = imagesx($im);
        $ih = imagesy($im);
        if ($iw <= 1 || $ih <= 1) {
            imagedestroy($im);
            return $pngBinary;
        }
        $innerMm = max(8.0, $labelWidthMm - 2 * $paddingMm);
        // ~11 px/mm ≈ 280 dpi across inner width — enough for thermal strips without clipping in print
        $maxPx = (int) max(100, min(960, (int) round($innerMm * 11.0)));
        if ($iw <= $maxPx) {
            imagedestroy($im);
            return $pngBinary;
        }
        $newW = $maxPx;
        $newH = (int) max(8, (int) round($ih * ($newW / $iw)));
        $scaled = imagescale($im, $newW, $newH, IMG_BILINEAR_FIXED);
        imagedestroy($im);
        if ($scaled === false) {
            return $pngBinary;
        }
        ob_start();
        imagepng($scaled);
        $out = ob_get_clean();
        imagedestroy($scaled);

        return ($out !== false && $out !== '') ? $out : $pngBinary;
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

        $labelW = (float)($cfg['label_width_mm'] ?? 64);
        $padMm = (float)($cfg['padding_mm'] ?? 1.2);
        $png = self::scaleBarcodePngToMaxWidth($png, $labelW, $padMm);

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
        $fsSku = (float)($cfg['font_size_sku_mm'] ?? 2.45);
        $lh = (float)($cfg['line_height'] ?? 1.12);

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
            $barBlock = '<img src="' . $e($barUri) . '" alt=""'
                . ' style="max-width:100%;width:auto;height:auto;max-height:15mm;object-fit:contain;object-position:center;display:block;margin:0 auto;vertical-align:middle;" />';
        } else {
            $barBlock = '<svg class="tl-js-barcode" xmlns="http://www.w3.org/2000/svg"'
                . ' data-barcode="' . $barCodeEsc . '"'
                . ' data-width-factor="' . $e((string)$wFactor) . '"'
                . ' data-height-px="' . $e((string)$barH) . '"'
                . ' style="max-width:100%;width:auto;height:auto;max-height:15mm;display:block;margin:0 auto;box-sizing:border-box;"></svg>';
        }

        $blankRows = max(0, min(10, (int)($cfg['blank_top_rows'] ?? 1)));
        $blankRowH = max(0.0, (float)($cfg['blank_top_row_height_mm'] ?? 2.2));
        $blankRowsHtml = '';
        for ($i = 0; $i < $blankRows; $i++) {
            $blankRowsHtml .= '<div class="tl-row tl-row--blank" style="flex:0 0 auto;width:100%;min-height:' . $e((string)$blankRowH) . 'mm;height:' . $e((string)$blankRowH) . 'mm;line-height:' . $e((string)$blankRowH) . 'mm;font-size:0;">&#8203;</div>';
        }

        return '<div class="tl-sheet" style="'
            . 'box-sizing:border-box;width:' . $e((string)$w) . 'mm;height:' . $e((string)$h) . 'mm;max-width:' . $e((string)$w) . 'mm;'
            . 'padding:' . $e((string)$pad) . 'mm;display:flex;flex-direction:column;align-items:stretch;'
            . 'justify-content:flex-start;gap:0.35mm;font-family:' . $e($ff) . ';'
            . 'font-size:' . $e((string)$fsTop) . 'mm;line-height:' . $e((string)$lh) . ';color:#000;background:#fff;'
            . 'border:0.12mm solid #000;overflow:hidden;'
            . '">'
            . $blankRowsHtml
            . '<div class="tl-row tl-row--meta" style="flex:0 0 auto;width:100%;min-width:0;box-sizing:border-box;font-weight:600;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' . $row1 . '</div>'
            . '<div class="tl-row tl-row--barcode" style="flex:0 1 auto;width:100%;min-width:0;max-width:100%;box-sizing:border-box;overflow:hidden;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;">'
            . '<div class="tl-barcode-wrap" style="width:100%;min-width:0;max-width:100%;overflow:hidden;display:flex;justify-content:center;align-items:center;box-sizing:border-box;">'
            . $barBlock
            . '</div></div>'
            . '<div class="tl-row tl-row--sku" style="flex:1 1 auto;width:100%;min-width:0;box-sizing:border-box;display:flex;align-items:center;justify-content:center;text-align:center;font-weight:900;font-size:' . $e((string)$fsSku) . 'mm;overflow:hidden;word-break:break-word;line-height:1.05;">'
            . ($sku !== '' ? $e($sku) : '—')
            . '</div>'
            . '</div>';
    }

    private static function printScriptBlock(): string
    {
        return '<script>(function(){'
            . 'function draw(){var els=document.querySelectorAll("svg.tl-js-barcode");'
            . 'for(var i=0;i<els.length;i++){var el=els[i];'
            . 'try{var code=String(el.getAttribute("data-barcode")||"0");var wf=parseInt(el.getAttribute("data-width-factor")||"1",10)||1;'
            . 'var wrap=el.parentElement;var tw=(wrap&&wrap.clientWidth)?wrap.clientWidth:420;var est=(code.length*11+28)*wf;var barW=wf;'
            . 'if(est>tw&&tw>24){barW=Math.max(1,Math.floor(wf*tw/est));}'
            . 'JsBarcode(el,code,{format:"CODE128",displayValue:false,width:barW,height:parseInt(el.getAttribute("data-height-px")||"45",10)||45,margin:2,background:"#ffffff",lineColor:"#000000"});}catch(e){}}}'
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
            . '.tl-sheet,.tl-row,.tl-barcode-wrap{box-sizing:border-box;}'
            . '.tl-row--barcode svg.tl-js-barcode{max-width:100%!important;height:auto!important;}'
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
            . '.tl-sheet,.tl-row,.tl-barcode-wrap{box-sizing:border-box;}'
            . '.tl-row--barcode svg.tl-js-barcode{max-width:100%!important;height:auto!important;}'
            . '</style>'
            . '<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>'
            . '</head><body>' . $pages
            . self::printScriptBlock()
            . '</body></html>';
    }
}
