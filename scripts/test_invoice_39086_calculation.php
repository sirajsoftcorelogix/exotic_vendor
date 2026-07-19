<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/invoice/pos_invoice_line_calculation.php';
require_once __DIR__ . '/../helpers/invoice/invoice_gst.php';

$listPrices = [6405.0, 28950.85, 5623.55, 12600.91, 8435.32, 18224.46, 79458.65, 32491.62];
$orderDiscount = 38725.0;
$listSum = array_sum($listPrices);

$lines = [];
foreach ($listPrices as $list) {
    $lines[] = ['list_incl_unit' => $list, 'disc_incl_unit' => $list, 'qty' => 1];
}

$adjusted = pos_invoice_apply_list_price_order_discount($lines, $orderDiscount);
$expectedNet = [5114.44, 23117.46, 4490.44, 10061.92, 6735.66, 14552.36, 63448.29, 25944.79];

$netSum = 0.0;
$failures = 0;
foreach ($adjusted as $i => $row) {
    $net = round((float)$row['disc_incl_unit'], 2);
    $netSum += $net;
    $expected = $expectedNet[$i];
    if (abs($net - $expected) > 0.02) {
        echo 'Line ' . ($i + 1) . " mismatch: got {$net}, expected {$expected}\n";
        $failures++;
    }
}

$expectedGrand = round($listSum - $orderDiscount, 2);
if (abs($netSum - $expectedGrand) > 0.02) {
    echo "Grand total mismatch: got {$netSum}, expected {$expectedGrand}\n";
    $failures++;
}

if ($failures === 0) {
    echo "OK invoice 39086 excel calculation (grand {$expectedGrand})\n";
    exit(0);
}

exit(1);
