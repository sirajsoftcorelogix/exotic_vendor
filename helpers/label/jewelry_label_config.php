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
    /**
     * Max square side for the barcode on the printed label (mm).
     * Clamped to available height inside the label (padding). Lower = smaller symbol vs text.
     */
    'qr_max_side_mm' => 10.0,
    /**
     * Data Matrix module size in pixels (tc-lib-barcode negative width/height factor).
     * Higher = sharper when the printed symbol is scaled in mm.
     */
    'barcode_module_px' => 4,
    /** @deprecated Use barcode_module_px. Kept for backward-compatible overrides. */
    'qr_builder_size_px' => 4,
    'qr_margin' => 0,
    /** Body text (Color / Size / MRP / SKU labels and values) */
    'font_size_mm' => 1.85,
    'font_family' => 'Arial, Helvetica, sans-serif',
    /** Line box multiplier (label/value lines and wrapped SKU). */
    'line_height' => 1.28,
    /** Vertical gap between text block rows (e.g. Color/Size/MRP row vs SKU row), mm */
    'text_block_row_gap_mm' => 0.55,
];
