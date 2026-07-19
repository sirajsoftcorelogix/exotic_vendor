<?php
$usesStoreName = orderListUsesStoreName($order);
$label = $usesStoreName ? 'Store Name' : 'Staff Name';
$value = htmlspecialchars((string) ($order['staff_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8');
$labelClass = 'heading-typography';
$valueClass = 'data-typography';
if ($usesStoreName) {
    $storeHighlightClass = ' inline-flex items-center rounded-full border border-orange-600 bg-orange-50 px-2.5 py-0.5 text-sm font-normal text-gray-900';
    $labelClass .= $storeHighlightClass;
    $valueClass .= $storeHighlightClass;
}
?>
<span class="<?= $labelClass ?>"><?= $label ?></span>
<p class="<?= $usesStoreName ? 'flex items-center gap-1.5' : '' ?>">: <span class="<?= $valueClass ?>"><?= $value ?></span></p>
