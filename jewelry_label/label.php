<?php

declare(strict_types=1);

/**
 * Single jewelry label — browser preview + print (Ctrl+P).
 * Open: /jewelry_label/label.php
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/LabelRenderer.php';

$config = require __DIR__ . '/config.php';

$sampleRow = [
    'sku' => 'AB12345',
    'size' => '17',
    'color' => 'Rose',
    'mrp' => 12500,
    'product_name' => 'Sterling Silver Rose Gold Ring',
    'qr_payload' => 'https://www.example.com/product/AB12345',
];

$qrPayload = isset($sampleRow['qr_payload']) && $sampleRow['qr_payload'] !== ''
    ? (string) $sampleRow['qr_payload']
    : 'https://www.example.com/p/' . rawurlencode((string) $sampleRow['sku']);

$qrUri = LabelRenderer::qrDataUri($config, $qrPayload);
$qrMm = LabelRenderer::qrDisplayMm($config);
$labelHtml = LabelRenderer::renderLabelInnerHtml($config, $sampleRow, $qrUri);

$font = htmlspecialchars((string) $config['TEXT_FONT'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$w = (float) $config['LABEL_WIDTH_MM'];
$h = (float) $config['LABEL_HEIGHT_MM'];
$ox = (float) $config['OFFSET_X_MM'];
$oy = (float) $config['OFFSET_Y_MM'];
$leftFs = htmlspecialchars((string) $config['LEFT_SIDE_SIZE'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$rightFs = htmlspecialchars((string) $config['RIGHT_SIDE_SIZE'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jewelry label — <?php echo htmlspecialchars($sampleRow['sku'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></title>
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
                background: #ddd;
                padding: 12mm;
            }

            .preview-hint {
                font-size: 14px;
                font-family: sans-serif;
                margin-bottom: 8px;
                color: #333;
            }
        }

        @media print {
            .preview-hint {
                display: none !important;
            }
        }

        .label-sheet {
            width: <?php echo $w; ?>mm;
            height: <?php echo $h; ?>mm;
            margin-left: <?php echo $ox; ?>mm;
            margin-top: <?php echo $oy; ?>mm;
            background: #fff;
            border: 0.12mm solid #000;
            overflow: visible;
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
            overflow: visible;
        }

        .label-left {
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 0 1 auto;
            min-width: 0;
            font-size: <?php echo $leftFs; ?>;
            line-height: 1.28;
            overflow: visible;
        }

        .label-left .sku {
            font-weight: 700;
            display: block;
            line-height: 1.3;
            padding: 0;
            margin: 0;
        }

        .label-left .meta {
            font-weight: 400;
            display: block;
            line-height: 1.28;
            padding: 0;
            margin: 0.05mm 0 0 0;
        }

        .label-right {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            flex: 1 1 0;
            min-width: 0;
            gap: 0.50mm;
            font-size: <?php echo $rightFs; ?>;
            line-height: 1.22;
            overflow: visible;
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
            min-width: 0;
            overflow: visible;
            line-height: 1.18;
            margin-left: 10mm;
        }

        .label-right-text .mrp-line {
            display: block;
            line-height: 1.15;
            white-space: nowrap;
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
            min-width: 0;
            font-size: <?php echo $leftFs; ?>;
            font-weight: 400;
            line-height: 1.22;
            margin: 0;
            padding: 0;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <p class="preview-hint">Preview — use <strong>Print</strong> (Ctrl+P) for the thermal printer. Stock: <?php echo $w; ?>×<?php echo $h; ?> mm.</p>
    <?php echo $labelHtml; ?>

<?php
/*
 * === BONUS: batch loop pattern ===
 *
 * $config = require __DIR__ . '/config.php';
 * $items = [
 *     ['sku'=>'AB12345','size'=>'17','color'=>'Rose','mrp'=>12500,'product_name'=>'…','qr_payload'=>'https://example.com/p/AB12345'],
 *     ['sku'=>'CD67890','size'=>'18','color'=>'Gold','mrp'=>8900,'product_name'=>'…','qr_payload'=>'https://example.com/p/CD67890'],
 * ];
 * foreach ($items as $row) {
 *     $qrUri = LabelRenderer::qrDataUri($config, (string)($row['qr_payload'] ?? 'https://example.com/p/'.$row['sku']));
 *     echo '<div class="label-print-page">' . LabelRenderer::renderLabelInnerHtml($config, $row, $qrUri) . '</div>';
 * }
 *
 * See also: batch_example.php
 */
?>
</body>
</html>
