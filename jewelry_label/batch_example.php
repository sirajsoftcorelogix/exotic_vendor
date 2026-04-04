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
        'sku' => 'AB12345',
        'size' => '17',
        'color' => 'Rose',
        'mrp' => 12500,
        'qr_payload' => 'https://www.example.com/product/AB12345',
    ],
    [
        'sku' => 'CD67890',
        'size' => '18',
        'color' => 'Gold',
        'mrp' => 8900,
        'qr_payload' => 'https://www.example.com/product/CD67890',
    ],
    [
        'sku' => 'EF24680',
        'size' => '16',
        'color' => 'Silver',
        'mrp' => 15250,
        'qr_payload' => 'https://www.example.com/product/EF24680',
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

$pages = '';
foreach ($items as $row) {
    $payload = isset($row['qr_payload']) && $row['qr_payload'] !== ''
        ? (string) $row['qr_payload']
        : 'https://www.example.com/p/' . rawurlencode((string) $row['sku']);
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
            padding: 0.25mm 1.2mm 0.25mm 1.2mm;
            gap: 1.5mm;
        }

        .label-left {
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 1 1 auto;
            min-width: 0;
            font-size: <?php echo $leftFs; ?>;
            line-height: 1.12;
        }

        .label-left .sku {
            font-weight: 700;
        }

        .label-left .meta {
            font-weight: 400;
        }

        .label-right {
            display: flex;
            flex-direction: row;
            align-items: center;
            flex: 0 0 auto;
            gap: 1mm;
            font-size: <?php echo $rightFs; ?>;
            line-height: 1.1;
        }

        .label-right .qr {
            width: <?php echo $qrMm; ?>mm;
            height: <?php echo $qrMm; ?>mm;
            object-fit: contain;
            flex-shrink: 0;
            display: block;
        }

        .label-right-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }

        .label-right-text .mrp {
            font-weight: 700;
        }

        .label-right-text .tax {
            font-weight: 400;
            font-size: 92%;
        }
    </style>
</head>
<body>
    <?php echo $pages; ?>
</body>
</html>
