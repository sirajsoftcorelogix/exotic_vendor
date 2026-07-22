<?php
/**
 * Order status <option> list grouped by Order / Procurement.
 *
 * @var array<int, array<string, mixed>> $order_status_list
 * @var string $selectedStatus
 */
$order_status_list = is_array($order_status_list ?? null) ? $order_status_list : [];
$selectedStatus = (string)($selectedStatus ?? '');

$procurement_id = null;
$sorder_id = null;
foreach ($order_status_list as $s) {
    if ((isset($s['slug']) && strtolower((string)$s['slug']) === 'procurement')
        || (isset($s['title']) && strtolower((string)$s['title']) === 'procurement')
    ) {
        $procurement_id = $s['id'] ?? null;
    }
    if (($s['parent_id'] ?? null) === 0 && strtolower((string)($s['slug'] ?? '')) === 'order') {
        $sorder_id = $s['id'] ?? null;
    }
}

$procurement_children = [];
$other_statuses = [];
foreach ($order_status_list as $status) {
    if ($procurement_id !== null && isset($status['id']) && (int)$status['id'] === (int)$procurement_id) {
        continue;
    }
    if ($sorder_id !== null && (int)($status['id'] ?? 0) === (int)$sorder_id) {
        continue;
    }
    if ($procurement_id !== null && isset($status['parent_id']) && (int)$status['parent_id'] === (int)$procurement_id) {
        $procurement_children[] = $status;
    } else {
        $other_statuses[] = $status;
    }
}

$renderOption = static function (array $st) use ($selectedStatus): void {
    $value = htmlspecialchars((string)($st['slug'] ?? ''), ENT_QUOTES, 'UTF-8');
    $label = htmlspecialchars((string)($st['title'] ?? $st['slug'] ?? ''), ENT_QUOTES, 'UTF-8');
    $selected = ($selectedStatus !== '' && $selectedStatus === (string)($st['slug'] ?? '')) ? ' selected' : '';
    echo "<option value=\"{$value}\"{$selected}>{$label}</option>";
};

if ($other_statuses !== []) {
    echo '<optgroup label="Order">';
    foreach ($other_statuses as $st) {
        $renderOption($st);
    }
    echo '</optgroup>';
}
if ($procurement_children !== []) {
    echo '<optgroup label="Procurement">';
    foreach ($procurement_children as $st) {
        $renderOption($st);
    }
    echo '</optgroup>';
}
