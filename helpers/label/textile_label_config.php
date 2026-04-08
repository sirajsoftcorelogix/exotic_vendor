<?php
/**
 * Textile strip label — 64 × 34 mm (location + date, CODE128 SKU, SKU line).
 */
return [
    'label_width_mm' => 64.0,
    'label_height_mm' => 34.0,
    'padding_mm' => 1.2,
    'font_family' => 'Arial, Helvetica, sans-serif',
    'font_size_top_mm' => 2.35,
    'font_size_barcode_label_mm' => 1.85,
    'font_size_sku_mm' => 2.45,
    'line_height' => 1.12,

    'barcode_type' => 'C128',
    'barcode_width_factor' => 1,
    'barcode_height_px' => 45,
    /** Human-readable SKU under bars (JsBarcode) / repeated under PNG. */
    'barcode_display_value' => true,
    'barcode_text_font_px' => 11,

    /** PHP date() format for row 1. */
    'print_date_format' => 'd-m-Y',
];
