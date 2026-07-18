<?php
$usesStoreName = orderListUsesStoreName($order);
$label = $usesStoreName ? 'Store Name' : 'Staff Name';
$value = htmlspecialchars((string) ($order['staff_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8');
$labelClass = 'heading-typography';
$valueClass = 'data-typography';
if ($usesStoreName) {
    $labelClass .= ' inline-flex items-center rounded-md bg-violet-600 px-2 py-1 text-[11px] font-bold uppercase tracking-wide text-white shadow-md ring-2 ring-violet-400';
    $valueClass .= ' inline-flex items-center rounded-md border-2 border-violet-500 bg-violet-100 px-2 py-1 text-sm font-bold text-violet-950 shadow-sm';
}
?>
<span class="<?= $labelClass ?>"><?= $label ?></span>
<p>: <span class="<?= $valueClass ?>"><?= $value ?></span></p>
