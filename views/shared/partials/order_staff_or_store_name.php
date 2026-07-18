<?php
$usesStoreName = orderListUsesStoreName($order);
$label = $usesStoreName ? 'Store Name' : 'Staff Name';
$value = htmlspecialchars((string) ($order['staff_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8');
$highlightClass = ' inline-flex items-center rounded-md bg-amber-100 px-1.5 py-0.5 font-semibold text-amber-900 ring-1 ring-amber-200';
$labelClass = 'heading-typography' . ($usesStoreName ? $highlightClass : '');
$valueClass = 'data-typography' . ($usesStoreName ? $highlightClass . ' bg-amber-50 text-amber-950 ring-amber-300' : '');
?>
<span class="<?= $labelClass ?>"><?= $label ?></span>
<p>: <span class="<?= $valueClass ?>"><?= $value ?></span></p>
