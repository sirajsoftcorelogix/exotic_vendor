<?php
/**
 * MG store large label — 75 × 50 mm.
 * Override via MgStoreLabel::render*($data, $overrides).
 */
return [
    'label_width_mm' => 75.0,
    'label_height_mm' => 50.0,
    'padding_mm' => 2.0,
    'font_family' => 'Arial, Helvetica, sans-serif',
    'font_size_title_mm' => 2.75,
    'font_size_body_mm' => 2.15,
    'line_height' => 1.2,

    /** Picqer: BarcodeGenerator::TYPE_CODE_128 */
    'barcode_type' => 'C128',
    /** Single bar width in pixels (BARCODE_WIDTH). */
    'barcode_width_factor' => 1,
    /** Bar height in pixels (BARCODE_HEIGHT). */
    'barcode_height_px' => 35,
    /** Show human-readable SKU under bars in row 1. */
    'barcode_display_value' => true,
    'barcode_caption_font_mm' => 2.0,
];
