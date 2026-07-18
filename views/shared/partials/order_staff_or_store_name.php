<?php
$usesStoreName = orderListUsesStoreName($order);
$label = $usesStoreName ? 'Store Name' : 'Staff Name';
$value = htmlspecialchars((string) ($order['staff_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8');
$labelClass = 'heading-typography';
$valueClass = 'data-typography';
if ($usesStoreName) {
    $labelClass .= ' inline-flex items-center rounded-full border border-violet-300 bg-[#5c4d99] px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-200';
    $valueClass .= ' inline-flex items-center rounded-full border border-[#5c4d99] bg-[#f0ebff] px-2.5 py-0.5 text-sm font-normal text-gray-900';
}
?>
<span class="<?= $labelClass ?>"><?= $label ?></span>
<p class="<?= $usesStoreName ? 'flex items-center gap-1.5' : '' ?>">: <span class="<?= $valueClass ?>"><?= $value ?></span></p>
