<?php

declare(strict_types=1);

/**
 * Picklist order line label — order number, SKU, CODE128 barcode (vp_orders.id).
 */
final class PicklistOrderLabel
{
    private static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * @param array<string, mixed>|null $overrides
     * @return array<string, mixed>
     */
    public static function config(?array $overrides = null): array
    {
        $base = require self::projectRoot() . '/helpers/label/picklist_order_label_config.php';
        if (!is_array($base)) {
            $base = [];
        }
        if ($overrides === null || $overrides === []) {
            return $base;
        }

        return array_replace($base, $overrides);
    }

    /**
     * @param array<string, mixed> $item picklist item row (order_id, order_number, sku/item_code)
     * @return array{order_number: string, sku: string, order_id: int}
     */
    public static function fromPicklistItemRow(array $item): array
    {
        $sku = trim((string) ($item['sku'] ?? ''));
        if ($sku === '') {
            $sku = trim((string) ($item['item_code'] ?? ''));
        }

        return [
            'order_number' => trim((string) ($item['order_number'] ?? '')),
            'sku' => $sku,
            'order_id' => (int) ($item['order_id'] ?? 0),
        ];
    }

    public static function barcodePayloadFromData(array $data): string
    {
        $orderId = (int) ($data['order_id'] ?? 0);

        return $orderId > 0 ? (string) $orderId : '0';
    }

    /**
     * @param array{order_number?: string, sku?: string, order_id?: int} $data
     * @param array<string, mixed>|null $config
     */
    public static function renderInnerHtml(array $data, ?array $config = null): string
    {
        $cfg = self::config($config);
        $w = (float) ($cfg['label_width_mm'] ?? 50);
        $h = (float) ($cfg['label_height_mm'] ?? 30);
        $pad = (float) ($cfg['padding_mm'] ?? 2);
        $ff = (string) ($cfg['font_family'] ?? 'Arial, Helvetica, sans-serif');
        $fsOrder = (float) ($cfg['font_size_order_mm'] ?? 2.8);
        $fsSku = (float) ($cfg['font_size_sku_mm'] ?? 2.5);
        $fsBarcode = (float) ($cfg['font_size_barcode_mm'] ?? 1.5);
        $lh = (float) ($cfg['line_height'] ?? 1.05);
        $gap = (float) ($cfg['row_gap_mm'] ?? 0.15);
        $barH = max(6, (int) ($cfg['barcode_height_px'] ?? 22));
        $barMaxH = (float) ($cfg['barcode_max_height_mm'] ?? 8.0);
        $wFactor = max(1, (int) ($cfg['barcode_width_factor'] ?? 1));

        $e = static function (string $s): string {
            return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };

        $orderNumber = (string) ($data['order_number'] ?? '');
        $sku = (string) ($data['sku'] ?? '');
        $barcode = self::barcodePayloadFromData($data);
        $barCodeEsc = $e($barcode);

        return '<div class="pol-sheet" style="'
            . 'box-sizing:border-box;width:100%;height:100%;max-width:100%;max-height:100%;'
            . 'padding:' . $e((string) $pad) . 'mm;display:grid;grid-template-rows:auto auto auto auto;'
            . 'row-gap:' . $e((string) $gap) . 'mm;align-content:start;justify-items:stretch;'
            . 'font-family:' . $e($ff) . ';font-size:' . $e((string) $fsOrder) . 'mm;line-height:' . $e((string) $lh) . ';'
            . 'color:#000;background:#fff;overflow:hidden;">'
            . '<div class="pol-row pol-row--order" style="width:100%;font-weight:700;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:' . $e((string) $lh) . ';">'
            . 'Ord ' . ($orderNumber !== '' ? $e($orderNumber) : '—')
            . '</div>'
            . '<div class="pol-row pol-row--sku" style="width:100%;font-weight:600;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:' . $e((string) $fsSku) . 'mm;line-height:' . $e((string) $lh) . ';">'
            . 'SKU ' . ($sku !== '' ? $e($sku) : '—')
            . '</div>'
            . '<div class="pol-row pol-row--barcode" style="width:100%;line-height:0;text-align:center;overflow:hidden;">'
            . '<svg class="pol-js-barcode" xmlns="http://www.w3.org/2000/svg"'
            . ' data-barcode="' . $barCodeEsc . '"'
            . ' data-width-factor="' . $e((string) $wFactor) . '"'
            . ' data-height-px="' . $e((string) $barH) . '"'
            . ' data-display-value="0"'
            . ' style="display:block;margin:0 auto;max-width:100%;width:100%;height:auto;max-height:' . $e((string) $barMaxH) . 'mm;vertical-align:top;"></svg>'
            . '</div>'
            . '<div class="pol-row pol-row--id" style="width:100%;text-align:center;font-size:' . $e((string) $fsBarcode) . 'mm;font-weight:600;line-height:' . $e((string) $lh) . ';">'
            . $barCodeEsc
            . '</div>'
            . '</div>';
    }

    private static function printScriptBlock(): string
    {
        return '<script>(function(){'
            . 'function draw(){var els=document.querySelectorAll("svg.pol-js-barcode");'
            . 'for(var i=0;i<els.length;i++){var el=els[i];'
            . 'try{var code=String(el.getAttribute("data-barcode")||"0");var wf=parseInt(el.getAttribute("data-width-factor")||"1",10)||1;'
            . 'var cell=el.closest(".pol-cell");var tw=cell?Math.max(24,cell.clientWidth-4):(el.parentElement?el.parentElement.clientWidth:120);'
            . 'var est=(code.length*11+28)*wf;var barW=wf;'
            . 'if(est>tw&&tw>20){barW=Math.max(1,Math.floor(wf*tw/est));}'
            . 'var showVal=el.getAttribute("data-display-value")==="1";'
            . 'JsBarcode(el,code,{format:"CODE128",displayValue:showVal,width:barW,height:parseInt(el.getAttribute("data-height-px")||"14",10)||14,margin:0,fontSize:8,background:"#ffffff",lineColor:"#000000"});}catch(e){}}}'
            . 'function finish(){window.focus();window.print();}'
            . 'draw();'
            . 'if(window.JsBarcode){finish();return;}'
            . 'var t0=Date.now();'
            . '(function wait(){if(window.JsBarcode){draw();finish();return;}if(Date.now()-t0>2500){finish();return;}setTimeout(wait,80);}());'
            . '}());</script>';
    }

    /**
     * @param array<int, array{order_number?: string, sku?: string, order_id?: int}> $labels
     * @param array<string, mixed>|null $config
     */
    public static function renderPrintSheetDocument(array $labels, ?array $config = null, ?string $sheetTitle = null): string
    {
        $cfg = self::config($config);
        $w = (float) ($cfg['label_width_mm'] ?? 38.1);
        $h = (float) ($cfg['label_height_mm'] ?? 21.2);
        $sheetW = (float) ($cfg['sheet_width_mm'] ?? 210);
        $sheetH = (float) ($cfg['sheet_height_mm'] ?? 297);
        $cols = max(1, (int) ($cfg['sheet_columns'] ?? 5));
        $rows = max(1, (int) ($cfg['sheet_rows'] ?? 13));
        $marginTop = (float) ($cfg['sheet_margin_top_mm'] ?? 0);
        $marginLeft = (float) ($cfg['sheet_margin_left_mm'] ?? 0);
        $perPage = $cols * $rows;
        $title = $sheetTitle !== null && $sheetTitle !== '' ? 'Picklist labels — ' . $sheetTitle : 'Picklist labels';

        $pagesHtml = '';
        if ($labels === []) {
            $pagesHtml = self::renderA4SheetPage([], $cfg);
        } else {
            $chunks = array_chunk($labels, $perPage);
            foreach ($chunks as $chunk) {
                $pagesHtml .= self::renderA4SheetPage($chunk, $cfg);
            }
        }

        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>'
            . '<style>'
            . '@page { size: A4 portrait; margin: 0; }'
            . 'html,body{margin:0;padding:0;}'
            . 'body{-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
            . '.pol-a4-page{width:' . $sheetW . 'mm;height:' . $sheetH . 'mm;margin:0;box-sizing:border-box;'
            . 'padding:' . $marginTop . 'mm ' . ($sheetW - ($cols * $w) - $marginLeft) . 'mm 0 ' . $marginLeft . 'mm;'
            . 'page-break-after:always;page-break-inside:avoid;}'
            . '.pol-a4-page:last-child{page-break-after:auto;}'
            . '.pol-a4-grid{display:grid;grid-template-columns:repeat(' . $cols . ',' . $w . 'mm);'
            . 'grid-template-rows:repeat(' . $rows . ',' . $h . 'mm);gap:0;width:' . ($cols * $w) . 'mm;height:' . ($rows * $h) . 'mm;}'
            . '.pol-cell{width:' . $w . 'mm;height:' . $h . 'mm;max-width:' . $w . 'mm;max-height:' . $h . 'mm;overflow:hidden;box-sizing:border-box;position:relative;}'
            . '.pol-cell .pol-sheet{width:100%;height:100%;}'
            . '.pol-sheet,.pol-row{box-sizing:border-box;max-width:100%;}'
            . '.pol-row--barcode svg.pol-js-barcode{max-width:100%!important;max-height:' . (float) ($cfg['barcode_max_height_mm'] ?? 5) . 'mm!important;height:auto!important;}'
            . '@media screen{.pol-a4-page{margin:12px auto;outline:1px solid #ddd;}}'
            . '.pol-no-print{margin:12px;text-align:center;font-family:Arial,sans-serif;font-size:13px;}'
            . '.pol-no-print button{margin:0 4px;padding:8px 14px;cursor:pointer;}'
            . '.pol-sheet-hint{color:#555;margin-top:6px;font-size:12px;}'
            . '@media print{.pol-no-print{display:none!important;}}'
            . '</style>'
            . '<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>'
            . '</head><body>'
            . '<div class="pol-no-print no-print">'
            . '<button type="button" onclick="window.print()">Print</button>'
            . '<button type="button" onclick="window.close()">Close</button>'
            . '<div class="pol-sheet-hint">Lotus A4 ST-65 · ' . $cols . '×' . $rows . ' · ' . $w . '×' . $h . ' mm per label · '
            . count($labels) . ' label(s)</div>'
            . '<div class="pol-sheet-hint">Print at 100% scale with no margins.</div>'
            . '</div>'
            . $pagesHtml
            . self::printScriptBlock()
            . '</body></html>';
    }

    /**
     * @param array<int, array{order_number?: string, sku?: string, order_id?: int}> $labels
     * @param array<string, mixed> $cfg
     */
    private static function renderA4SheetPage(array $labels, array $cfg): string
    {
        $cols = max(1, (int) ($cfg['sheet_columns'] ?? 5));
        $rows = max(1, (int) ($cfg['sheet_rows'] ?? 13));
        $perPage = $cols * $rows;
        $cells = '';

        for ($i = 0; $i < $perPage; $i++) {
            $inner = '';
            if (isset($labels[$i])) {
                $inner = self::renderInnerHtml($labels[$i], $cfg);
            }
            $cells .= '<div class="pol-cell">' . $inner . '</div>';
        }

        return '<div class="pol-a4-page"><div class="pol-a4-grid">' . $cells . '</div></div>';
    }

    /**
     * @param array{order_number?: string, sku?: string, order_id?: int} $data
     * @param array<string, mixed>|null $config
     */
    public static function renderPrintDocument(array $data, ?array $config = null): string
    {
        $cfg = self::config($config);
        $w = (float) ($cfg['label_width_mm'] ?? 38.1);
        $h = (float) ($cfg['label_height_mm'] ?? 21.2);
        $sheetW = (float) ($cfg['sheet_width_mm'] ?? 210);
        $sheetH = (float) ($cfg['sheet_height_mm'] ?? 297);
        $cols = max(1, (int) ($cfg['sheet_columns'] ?? 5));
        $rows = max(1, (int) ($cfg['sheet_rows'] ?? 13));
        $inner = self::renderInnerHtml($data, $cfg);
        $title = 'Picklist label';

        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>'
            . '<style>'
            . '@page { size: ' . $w . 'mm ' . $h . 'mm; margin: 0; }'
            . 'html,body{margin:0;padding:0;}'
            . 'body{-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
            . '.pol-page{width:' . $w . 'mm;height:' . $h . 'mm;margin:0;page-break-after:always;page-break-inside:avoid;}'
            . '.pol-page:last-child{page-break-after:auto;}'
            . '.pol-sheet,.pol-row{box-sizing:border-box;}'
            . '.pol-row--barcode svg.pol-js-barcode{max-width:100%!important;height:auto!important;}'
            . '@media screen{.pol-page{margin:12px auto;outline:1px dashed #ccc;}}'
            . '.pol-no-print{margin:12px;text-align:center;font-family:Arial,sans-serif;font-size:13px;}'
            . '.pol-no-print button{margin:0 4px;padding:8px 14px;cursor:pointer;}'
            . '.pol-sheet-hint{color:#555;margin-top:6px;font-size:12px;}'
            . '@media print{.pol-no-print{display:none!important;}}'
            . '</style>'
            . '<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>'
            . '</head><body>'
            . '<div class="pol-no-print no-print">'
            . '<button type="button" onclick="window.print()">Print</button>'
            . '<button type="button" onclick="window.close()">Close</button>'
            . '<div class="pol-sheet-hint">Lotus A4 ST-65 · ' . $cols . '×' . $rows . ' labels · '
            . $w . '×' . $h . ' mm each · A4 portrait ' . $sheetW . '×' . $sheetH . ' mm</div>'
            . '<div class="pol-sheet-hint">Print at 100% scale with no margins. Place on top-left label cell.</div>'
            . '</div>'
            . '<div class="pol-page">' . $inner . '</div>'
            . self::printScriptBlock()
            . '</body></html>';
    }
}
