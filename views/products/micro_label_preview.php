<?php

declare(strict_types=1);

/**
 * Micro strip preview — same builder as product detail.
 * Open: /micro_label/label.php
 */

$root = dirname(__DIR__, 2);
$tx = require $root . '/helpers/label/textiles_config.php';
$preset = $tx['PRINT_JS_PRESET'];
$sample = [
    'sku' => 'M-SKU-001', 'itemCode' => 'ITEM-DEMO-01', 'labelDate' => date('d-m-Y'),
];
$w = (float) $preset['wMm'];
$h = (float) $preset['hMm'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Micro label — <?php echo htmlspecialchars($sample['sku'], ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 16px; font-family: Arial, sans-serif; background: #ddd; }
        .hint { margin-bottom: 12px; font-size: 14px; }
        #host { display: inline-block; background: #fff; padding: 12px; border-radius: 8px; }
    </style>
</head>
<body>
    <p class="hint">Micro <?php echo $w; ?>×<?php echo $h; ?> mm — tune <code>helpers/label/textiles_config.php</code>. Print from product page for live data.</p>
    <div id="host"></div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
    <script><?php readfile(__DIR__ . '/partials/micro_label_build.js'); ?></script>
    <script>
    (function () {
        var preset = <?php echo json_encode($preset, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        var data = <?php echo json_encode($sample, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        var el = buildMicroLabelElement(preset, data);
        var cv = el.querySelector('canvas.pl-barcode');
        var t = String(data.sku || data.itemCode || '0').trim() || '0';
        if (cv) {
            try {
                JsBarcode(cv, t, {
                    format: 'CODE128', width: preset.barUnit || 1, height: preset.barHeight,
                    displayValue: preset.barDisplayValue === true,
                    font: preset.fontFamily || 'Arial, Helvetica, sans-serif',
                    fontSize: preset.barFont || 8, margin: 0,
                    background: '#fff', lineColor: '#000'
                });
            } catch (e) {}
            var pad = preset.pad || 0, m = preset.barHorizontalMarginPx || 8;
            var maxW = Math.max(40, preset.cw - 2 * pad - 2 * m);
            if (maxW > 40 && cv.width > 0 && Math.abs(cv.width - maxW) > 1) {
                var nw = Math.floor(maxW), nh = Math.max(1, Math.floor(cv.height * (nw / cv.width)));
                var s = document.createElement('canvas');
                s.width = cv.width; s.height = cv.height;
                s.getContext('2d').drawImage(cv, 0, 0);
                cv.width = nw; cv.height = nh;
                cv.getContext('2d').drawImage(s, 0, 0, s.width, s.height, 0, 0, nw, nh);
            }
        }
        document.getElementById('host').appendChild(el);
    })();
    </script>
</body>
</html>
