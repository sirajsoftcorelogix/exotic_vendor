<?php
/**
 * Picklist order line label — Lotus Label A4 ST-65 sheet (portrait).
 * 5 labels wide × 13 labels high; each label 38.1 × 21.2 mm.
 */
return [
    'label_width_mm' => 38.1,
    'label_height_mm' => 21.2,

    /** A4 portrait sheet layout (Lotus Label A4 ST - 65). */
    'sheet_width_mm' => 210.0,
    'sheet_height_mm' => 297.0,
    'sheet_columns' => 5,
    'sheet_rows' => 13,
    'sheet_orientation' => 'portrait',
    /** Centre 5×13 grid on A4 (210×297 mm). */
    'sheet_margin_top_mm' => 10.7,
    'sheet_margin_left_mm' => 9.75,

    'padding_mm' => 0.6,
    'font_family' => 'Arial, Helvetica, sans-serif',
    'font_size_order_mm' => 1.85,
    'font_size_sku_mm' => 1.7,
    'font_size_barcode_mm' => 1.5,
    'line_height' => 1.05,
    'row_gap_mm' => 0.15,

    'barcode_type' => 'C128',
    'barcode_width_factor' => 1,
    'barcode_height_px' => 22,
    'barcode_max_height_mm' => 8.0,
];
