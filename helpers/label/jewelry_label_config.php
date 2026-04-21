<?php
/**
 * Jewelry thermal label — physical size and layout defaults.
 * Override keys when calling JewelryLabel::render*($data, $overrides).
 */
return [
    'label_width_mm' => 100.0,
    'label_height_mm' => 12.9,
    /** Inner inset from label edge */
    'padding_mm' => 0.6,
    /** Max printed width for the Code 128 image (mm); scales down long SKUs. */
    'barcode_max_width_mm' => 44.0,
    /** Max printed height for the barcode bars (mm); clamped to label minus padding. */
    'barcode_max_height_mm' => 9.0,
    /** SVG bar height (Picqer user units); scales with label via CSS mm caps. */
    'barcode_svg_bar_height' => 28.0,
    /** SVG bar module width multiplier. */
    'barcode_svg_width_factor' => 2.0,
    /** Body text (Color / Size / MRP / SKU labels and values) */
    'font_size_mm' => 1.85,
    'font_family' => 'Arial, Helvetica, sans-serif',
    /** Line box multiplier (label/value lines and wrapped SKU). */
    'line_height' => 1.28,
    /** Vertical gap between text block rows (e.g. Color/Size/MRP row vs SKU row), mm */
    'text_block_row_gap_mm' => 0.55,
];
