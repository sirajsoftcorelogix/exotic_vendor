<?php

declare(strict_types=1);

/**
 * Large thermal label (MG store style): 75 × 50 mm, barcode top, text block below.
 * Embeds `PRINT_JS_PRESET` for product detail JsBarcode/html2canvas (same px/mm as other labels).
 */
$MG = [
    'LABEL_WIDTH_MM' => 75,
    'LABEL_HEIGHT_MM' => 50,
    'PX_PER_MM' => 20,
    'OFFSET_X_MM' => 0,
    'OFFSET_Y_MM' => 0,
    'LABEL_PAD_PX' => 28,
    /** Gap between barcode block and text (px). */
    'TEXT_AFTER_BARCODE_PX' => 10,
    'SKU_LINE_PX' => 68,
    'TITLE_LINE_PX' => 60,
    'BODY_LINE_PX' => 52,
    /** Bar geometry: height = bar module stack; width = narrow-bar width (CODE128). */
    'BAR_HEIGHT' => 18,
    'BAR_WIDTH' => 0.5,
    'BAR_FONT' => 5,
    'BAR_H_MARGIN_PX' => 8,
    'BORDER' => '1px solid #000000',
    'FONT_FAMILY' => 'Arial, Helvetica, sans-serif',
    'SHOW_BARCODE_TEXT' => false,
    'SHOW_BORDER' => true,
    'COLUMNS' => 1,
    'ROWS' => 1,
    'SPACING_MM' => 0,
    'MARGIN_TOP_MM' => 0,
    'MARGIN_BOTTOM_MM' => 0,
    'MARGIN_LEFT_MM' => 0,
    'MARGIN_RIGHT_MM' => 0,
];

$ppm = max(1, (int) $MG['PX_PER_MM']);
$w = (float) $MG['LABEL_WIDTH_MM'];
$h = (float) $MG['LABEL_HEIGHT_MM'];

$MG['PRINT_JS_PRESET'] = [
    'name' => 'Large (MG store)',
    'layout' => 'mg_store',
    'wMm' => $w,
    'hMm' => $h,
    'offsetXMm' => (float) $MG['OFFSET_X_MM'],
    'offsetYMm' => (float) $MG['OFFSET_Y_MM'],
    'orient' => 'portrait',
    'cw' => (int) round($w * $ppm),
    'ch' => (int) round($h * $ppm),
    'pad' => (int) $MG['LABEL_PAD_PX'],
    'textAfterBarcodePx' => (int) $MG['TEXT_AFTER_BARCODE_PX'],
    'border' => (string) $MG['BORDER'],
    'fontFamily' => (string) $MG['FONT_FAMILY'],
    'skuLinePx' => (int) $MG['SKU_LINE_PX'],
    'titleLinePx' => (int) $MG['TITLE_LINE_PX'],
    'bodyLinePx' => (int) $MG['BODY_LINE_PX'],
    'showBorders' => (bool) $MG['SHOW_BORDER'],
    'barUnit' => (float) $MG['BAR_WIDTH'],
    'barHeight' => (int) $MG['BAR_HEIGHT'],
    'barDisplayValue' => (bool) ($MG['SHOW_BARCODE_TEXT'] ?? false),
    'barFont' => (int) $MG['BAR_FONT'],
    'barHorizontalMarginPx' => (int) $MG['BAR_H_MARGIN_PX'],
];

return $MG;
