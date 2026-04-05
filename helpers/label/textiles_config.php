<?php

declare(strict_types=1);

/**
 * Textiles / Micro thermal strip (25×15 mm). Batch grid keys optional.
 * `PRINT_JS_PRESET` is embedded on the product page for JsBarcode/html2canvas.
 */
$TC = [
    'LABEL_WIDTH_MM' => 25,
    'LABEL_HEIGHT_MM' => 15,
    'PX_PER_MM' => 20,
    'OFFSET_X_MM' => 0,
    'OFFSET_Y_MM' => 0,
    'LABEL_PAD_PX' => 12,
    'SKU_FONT_PX' => 23,
    /** Unitless line-height for SKU row. */
    'SKU_LINE_HEIGHT' => 1.62,
    'DATE_PT' => '9pt',
    'BAR_HEIGHT' => 30,
    'BAR_WIDTH' => 1,
    'BAR_FONT' => 8,
    'BAR_H_MARGIN_PX' => 4,
    /** Space between SKU row and barcode. */
    'SKU_TO_BAR_PX' => 8,
    'BAR_TO_DATE_PX' => 5,
    'BORDER' => '1px solid #000000',
    /** Stack for label + JsBarcode caption (matches jewelry preset). */
    'FONT_FAMILY' => 'Arial, Helvetica, sans-serif',
    'SHOW_BARCODE_TEXT' => false,
    'SHOW_BORDER' => true,
    'CODE_COLUMN' => 0,
    'DATE_COLUMN' => 1,
    'LOCATION_COLUMN' => 2,
    'SPACING_MM' => 2,
    'COLUMNS' => 7,
    'ROWS' => 8,
    'MARGIN_TOP_MM' => 13,
    'MARGIN_BOTTOM_MM' => 13,
    'MARGIN_LEFT_MM' => 6,
    'MARGIN_RIGHT_MM' => 6,
];

$ppm = max(1, (int) $TC['PX_PER_MM']);
$w = (float) $TC['LABEL_WIDTH_MM'];
$h = (float) $TC['LABEL_HEIGHT_MM'];

$TC['PRINT_JS_PRESET'] = [
    'name' => 'Micro (textiles)',
    'layout' => 'micro',
    'wMm' => $w,
    'hMm' => $h,
    'offsetXMm' => (float) $TC['OFFSET_X_MM'],
    'offsetYMm' => (float) $TC['OFFSET_Y_MM'],
    'orient' => 'landscape',
    'cw' => (int) round($w * $ppm),
    'ch' => (int) round($h * $ppm),
    'pad' => (int) $TC['LABEL_PAD_PX'],
    'border' => (string) $TC['BORDER'],
    'fontFamily' => (string) $TC['FONT_FAMILY'],
    'dateSize' => (string) $TC['DATE_PT'],
    'skuFontPx' => (int) $TC['SKU_FONT_PX'],
    'skuLineHeight' => (float) ($TC['SKU_LINE_HEIGHT'] ?? 1.62),
    'showBorders' => (bool) $TC['SHOW_BORDER'],
    'barUnit' => (float) $TC['BAR_WIDTH'],
    'barHeight' => (int) $TC['BAR_HEIGHT'],
    'barDisplayValue' => (bool) ($TC['SHOW_BARCODE_TEXT'] ?? false),
    'barFont' => (int) $TC['BAR_FONT'],
    'barHorizontalMarginPx' => (int) $TC['BAR_H_MARGIN_PX'],
    'skuBarcodeGapPx' => (int) $TC['SKU_TO_BAR_PX'],
    'barcodeDateGapPx' => (int) $TC['BAR_TO_DATE_PX'],
];

return $TC;
