<?php
/** @var array<string,mixed>|null $dispatch */
/** @var array<string,mixed> $labelPackage */
/** @var string $awb */

$val = static function (array $row, string $key, string $default = ''): string {
    $v = $row[$key] ?? $default;
    return htmlspecialchars(trim((string)$v), ENT_QUOTES, 'UTF-8');
};

$name = $val($labelPackage, 'name', $val($labelPackage, 'consignee_name'));
$address = $val($labelPackage, 'address', $val($labelPackage, 'add'));
$city = $val($labelPackage, 'city');
$state = $val($labelPackage, 'state');
$pin = $val($labelPackage, 'pin', $val($labelPackage, 'pincode'));
$phone = $val($labelPackage, 'phone');
$orderId = $val($labelPackage, 'oid', $val($labelPackage, 'order'));
$paymentMode = $val($labelPackage, 'pt', $val($labelPackage, 'payment_mode'));
$weight = $val($labelPackage, 'weight', $val($labelPackage, 'cgm'));
$sortCode = $val($labelPackage, 'sort_code', $val($labelPackage, 'sortcode'));
$destination = $val($labelPackage, 'destination', $val($labelPackage, 'dst'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delhivery Label — <?= htmlspecialchars($awb) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 16px; background: #f3f4f6; color: #111; }
        .toolbar { max-width: 420px; margin: 0 auto 12px; display: flex; gap: 8px; }
        .toolbar button { padding: 8px 14px; border: 1px solid #d1d5db; background: #fff; border-radius: 6px; cursor: pointer; font-size: 13px; }
        .label {
            max-width: 420px; margin: 0 auto; background: #fff; border: 2px solid #111;
            padding: 14px; font-size: 12px; line-height: 1.35;
        }
        .label h1 { margin: 0 0 8px; font-size: 18px; letter-spacing: 0.5px; }
        .awb { font-size: 22px; font-weight: bold; letter-spacing: 1px; margin: 8px 0 12px; font-family: monospace; }
        .row { margin-bottom: 6px; }
        .muted { color: #4b5563; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; }
        .section { border-top: 1px dashed #9ca3af; margin-top: 10px; padding-top: 10px; }
        .barcode-box {
            margin-top: 12px; padding: 10px; border: 1px solid #111; text-align: center;
            font-family: "Libre Barcode 128 Text", monospace; font-size: 42px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .label { border: 1px solid #000; margin: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print label</button>
        <button type="button" onclick="window.close()">Close</button>
    </div>

    <div class="label">
        <h1>Delhivery</h1>
        <div class="muted">Waybill / AWB</div>
        <div class="awb"><?= htmlspecialchars($awb) ?></div>

        <?php if ($sortCode !== ''): ?>
            <div class="row"><span class="muted">Sort code:</span> <?= $sortCode ?></div>
        <?php endif; ?>
        <?php if ($destination !== ''): ?>
            <div class="row"><span class="muted">Destination:</span> <?= $destination ?></div>
        <?php endif; ?>

        <div class="section">
            <div class="muted">Ship To</div>
            <div class="row"><strong><?= $name ?></strong></div>
            <div class="row"><?= $address ?></div>
            <div class="row"><?= trim($city . ($state !== '' ? ', ' . $state : '') . ($pin !== '' ? ' - ' . $pin : '')) ?></div>
            <?php if ($phone !== ''): ?>
                <div class="row">Phone: <?= $phone ?></div>
            <?php endif; ?>
        </div>

        <div class="section">
            <?php if ($orderId !== ''): ?>
                <div class="row"><span class="muted">Order:</span> <?= $orderId ?></div>
            <?php endif; ?>
            <?php if ($paymentMode !== ''): ?>
                <div class="row"><span class="muted">Payment:</span> <?= $paymentMode ?></div>
            <?php endif; ?>
            <?php if ($weight !== ''): ?>
                <div class="row"><span class="muted">Weight:</span> <?= $weight ?></div>
            <?php endif; ?>
        </div>

        <div class="barcode-box" aria-hidden="true"><?= htmlspecialchars($awb) ?></div>
    </div>
</body>
</html>
