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
     * Max square side for the QR on the printed label (mm).
     * Spec was 32 mm; on a 12.9 mm tall label this is clamped to the available height automatically.
     */
    'qr_max_side_mm' => 32.0,
    /** Endroid PNG size in pixels (spec: 32; raise e.g. 200–300 if the QR looks soft when printed). */
    'qr_builder_size_px' => 32,
    'qr_margin' => 0,
    /** Body text */
    'font_size_mm' => 2.4,
    'font_family' => 'Arial, Helvetica, sans-serif',
    'line_height' => 1.15,
];
