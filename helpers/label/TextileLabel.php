<?php

declare(strict_types=1);

use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

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
        if ($sku === '') {
            return '0';
        }
        if (strlen($sku) > 72) {
            $sku = substr($sku, 0, 72);
        }

        return $sku;
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
        $barH = max(8, (int)($cfg['barcode_height_px'] ?? 45));
        $labelW = (float)($cfg['label_width_mm'] ?? 64);
        $padMm = (float)($cfg['padding_mm'] ?? 1.2);

        $canPng = class_exists(BarcodeGeneratorPNG::class)
            && (extension_loaded('imagick') || function_exists('imagecreate'));

        if ($canPng) {
            try {
                $generator = new BarcodeGeneratorPNG();
                $png = $generator->getBarcode($code, $type, $wFactor, $barH);
                $png = self::scaleBarcodePngToMaxWidth($png, $labelW, $padMm);

                return 'data:image/png;base64,' . base64_encode($png);
            } catch (Throwable $e) {
                // Hosts without a working GD/Imagick stack still hit PngRenderer; fall back to SVG.
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
            try {
                $pages .= '<div class="tl-page">' . self::renderInnerHtml(is_array($row) ? $row : [], $cfg) . '</div>';
            } catch (Throwable $e) {
                error_log('TextileLabel batch row: ' . $e->getMessage());
                $pages .= '<div class="tl-page"><div class="tl-sheet" style="box-sizing:border-box;width:' . $w . 'mm;height:' . $h . 'mm;border:0.12mm solid #000;font-size:2mm;padding:1mm;">Label row skipped.</div></div>';
            }
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
