<?php

declare(strict_types=1);

/**
 * Multiple labels for batch printing — one physical label per printed page.
 * Open: /jewelry_label/batch_example.php
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/LabelRenderer.php';

$config = require __DIR__ . '/config.php';

$items = [
    [
        'item_code' => 'ITEM-AB12345',
        'sku' => 'V-SKU-001',
        'size' => '17',
        'color' => 'Rose',
        'mrp' => 12500,
        'product_name' => 'Sterling Silver Rose Gold Ring',
        'qr_payload' => 'https://www.example.com/product/ITEM-AB12345',
    ],
    [
        'item_code' => 'ITEM-CD67890',
        'sku' => 'V-SKU-002',
        'size' => '18',
        'color' => 'Gold',
        'mrp' => 8900,
        'product_name' => '22K Gold Temple Necklace',
        'qr_payload' => 'https://www.example.com/product/ITEM-CD67890',
    ],
    [
        'item_code' => 'ITEM-EF24680',
        'sku' => 'V-SKU-003',
        'size' => '16',
        'color' => 'Silver',
        'mrp' => 15250,
        'product_name' => 'Oxidized Silver Bangle Set',
        'qr_payload' => 'https://www.example.com/product/ITEM-EF24680',
    ],
];

$font = htmlspecialchars((string) $config['TEXT_FONT'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$w = (float) $config['LABEL_WIDTH_MM'];
$h = (float) $config['LABEL_HEIGHT_MM'];
$ox = (float) $config['OFFSET_X_MM'];
$oy = (float) $config['OFFSET_Y_MM'];
$leftFs = htmlspecialchars((string) $config['LEFT_SIDE_SIZE'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$rightFs = htmlspecialchars((string) $config['RIGHT_SIDE_SIZE'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$qrMm = LabelRenderer::qrDisplayMm($config);
$batchRightGapMm = 0.15;
$leftMaxMm = LabelRenderer::leftColumnMaxMm($config, $qrMm, $batchRightGapMm);
$innerMm = $w - 2 * LabelRenderer::INNER_PAD_LR_MM;
$qrTrackMm = 10 + $qrMm + $batchRightGapMm + 10;
$productNamePrefMm = (float) ($config['PRODUCT_NAME_WIDTH_MM'] ?? 36);
$productNameWmm = min(
    $productNamePrefMm,
    max(6.0, $innerMm - 1.0 - 16.0 - $qrTrackMm)
);

$pages = '';
foreach ($items as $row) {
    $payload = isset($row['qr_payload']) && $row['qr_payload'] !== ''
        ? (string) $row['qr_payload']
        : 'https://www.example.com/p/' . rawurlencode((string) ($row['item_code'] ?? $row['sku'] ?? ''));
    $qrUri = LabelRenderer::qrDataUri($config, $payload);
    $pages .= '<div class="label-print-page">' . LabelRenderer::renderLabelInnerHtml($config, $row, $qrUri) . '</div>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jewelry labels — batch <?php echo count($items); ?></title>
    <style>
        @page {
            size: <?php echo $w; ?>mm <?php echo $h; ?>mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: <?php echo $font; ?>, Helvetica, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        @media screen {
            body {
                background: #ccc;
                padding: 10mm;
            }

            .label-print-page {
                margin-bottom: 10mm;
                outline: 1px dashed #666;
            }
        }

        .label-print-page {
            page-break-after: always;
            break-after: page;
        }

        .label-print-page:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        .label-sheet {
            width: <?php echo $w; ?>mm;
            height: <?php echo $h; ?>mm;
            margin-left: <?php echo $ox; ?>mm;
            margin-top: <?php echo $oy; ?>mm;
            background: #fff;
            border: 0.12mm solid #000;
            overflow: hidden;
        }

        .label-inner {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            height: 100%;
            padding: 0.05mm 2.2mm 0.05mm 2.2mm;
            gap: 1mm;
            overflow: hidden;
            min-width: 0;
        }

        .label-left {
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 0 1 auto;
            min-width: 0;
            max-width: <?php echo $leftMaxMm; ?>mm;
            font-size: <?php echo $leftFs; ?>;
            line-height: 1.28;
            overflow: hidden;
        }

        .label-left .sku {
            font-weight: 700;
            display: block;
            line-height: 1.3;
            padding: 0;
            margin: 0;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .label-left .meta {
            font-weight: 400;
            display: block;
            line-height: 1.28;
            padding: 0;
            margin: 0.05mm 0 0 0;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .label-right {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            flex: 1 1 0;
            min-width: 0;
            gap: 0.15mm;
            font-size: <?php echo $rightFs; ?>;
            line-height: 1.22;
            overflow: hidden;
        }

        .label-right .qr {
            width: <?php echo $qrMm; ?>mm;
            height: <?php echo $qrMm; ?>mm;
            object-fit: contain;
            flex-shrink: 0;
            display: block;
            margin-left: 10mm;
        }

        .label-right-text {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: center;
            gap: 0;
            flex: 1 1 0;
            width: 0;
            min-width: 0;
            overflow: hidden;
            line-height: 1.18;
            margin-left: -10mm;
        }

        .label-right-text .mrp-line {
            display: block;
            line-height: 1.15;
            white-space: nowrap;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .label-right-text .mrp {
            font-weight: 700;
        }

        .label-right-text .tax {
            font-weight: 400;
            font-size: 92%;
        }

        .label-right-text .product-name {
            box-sizing: border-box;
            width: 100%;
            max-width: min(<?php echo $productNameWmm; ?>mm, 100%);
            min-width: 0;
            font-size: <?php echo $leftFs; ?>;
            font-weight: 400;
            line-height: 1.22;
            margin: 0;
            padding: 0;
            white-space: normal;
            overflow-wrap: anywhere;
            word-wrap: break-word;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <?php echo $pages; ?>
</body>
</html>
