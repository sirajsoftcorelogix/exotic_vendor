<?php

declare(strict_types=1);

/**
 * Textiles / "Micro" thermal label — matches Seznik-style jewellery strip stock
 * (Amazon.in ASIN B0FK2X6VYF: 25 mm × 15 mm, 2500 labels roll).
 *
 * Mirrors front-end preset `micro` in views/products/partials/product_label_print_block.php.
 * COLUMNS/ROWS/MARGINS are for future sheet generators; re-tune when laying out full pages.
 */
return [
    'CODE_COLUMN' => 0,
    'DATE_COLUMN' => 1,
    'LOCATION_COLUMN' => 2,

    'LABEL_WIDTH_MM' => 25,
    'LABEL_HEIGHT_MM' => 15,
    'SPACING_MM' => 2,

    /** Example grid for ~208 mm printable width: (208 − L/R margins) / (25 + gap). */
    'COLUMNS' => 7,
    'ROWS' => 8,

    'MARGIN_TOP_MM' => 13,
    'MARGIN_BOTTOM_MM' => 13,
    'MARGIN_LEFT_MM' => 6,
    'MARGIN_RIGHT_MM' => 6,

    'BARCODE_TYPE' => 'CODE128',
    'BARCODE_HEIGHT' => 34,
    'BARCODE_WIDTH' => 1,
    'BARCODE_DISPLAY_VALUE' => false,

    'TEXT_FONT' => 'Arial',
    /** Mirrors preset skuLocationFontPx (22px; SKU and location share the same font). */
    'CODE_SIZE' => '16.5pt',
    'DATE_SIZE' => '8pt',
    'LOCATION_SIZE' => '16.5pt',

    'SHOW_BORDERS' => true,
];
