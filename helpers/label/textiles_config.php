<?php

declare(strict_types=1);

/**
 * Textiles / "Micro" label sheet layout constants (64×34 mm cell).
 * Mirrors front-end preset in views/products/partials/product_label_print_block.php (micro).
 * COLUMNS/ROWS/MARGINS apply to future batch sheet generators — not used by single-label browser print.
 */
return [
    'CODE_COLUMN' => 0,
    'DATE_COLUMN' => 1,
    'LOCATION_COLUMN' => 2,

    'LABEL_WIDTH_MM' => 64,
    'LABEL_HEIGHT_MM' => 34,
    'SPACING_MM' => 2,

    'COLUMNS' => 3,
    'ROWS' => 8,

    'MARGIN_TOP_MM' => 13,
    'MARGIN_BOTTOM_MM' => 13,
    'MARGIN_LEFT_MM' => 6,
    'MARGIN_RIGHT_MM' => 6,

    'BARCODE_TYPE' => 'CODE128',
    'BARCODE_HEIGHT' => 45,
    'BARCODE_WIDTH' => 1,
    'BARCODE_DISPLAY_VALUE' => false,

    'TEXT_FONT' => 'Arial',
    'CODE_SIZE' => '10pt',
    'DATE_SIZE' => '8pt',
    'LOCATION_SIZE' => '16pt',

    'SHOW_BORDERS' => true,
];
