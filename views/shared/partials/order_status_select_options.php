<?php
/**
 * Order status <option> list grouped dynamically by status master parent groups.
 *
 * @var array<int, array<string, mixed>|string> $order_status_list
 * @var string $selectedStatus
 */
$order_status_list = is_array($order_status_list ?? null) ? $order_status_list : [];
$selectedStatus = (string)($selectedStatus ?? '');

if ($order_status_list === []) {
    return;
}

// Handle associative array fallback e.g. ['pending' => 'Pending']
$isAssociative = false;
$firstKey = array_key_first($order_status_list);
if (is_string($firstKey) && !is_array($order_status_list[$firstKey])) {
    $isAssociative = true;
}

if ($isAssociative) {
    foreach ($order_status_list as $slug => $title) {
        $val = htmlspecialchars((string)$slug, ENT_QUOTES, 'UTF-8');
        $lbl = htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8');
        $sel = ($selectedStatus !== '' && $selectedStatus === (string)$slug) ? ' selected' : '';
        echo "<option value=\"{$val}\"{$sel}>{$lbl}</option>";
    }
    return;
}

// Map parent groups (parent_id === 0) and group child statuses (parent_id !== 0)
$parentGroups = []; // id => title
$childStatuses = []; // parent_id => list of status rows

foreach ($order_status_list as $st) {
    if (!is_array($st)) {
        continue;
    }
    $id = (int)($st['id'] ?? 0);
    $parentId = (int)($st['parent_id'] ?? 0);
    $title = (string)($st['title'] ?? $st['slug'] ?? '');

    if ($parentId === 0) {
        if ($id > 0) {
            $parentGroups[$id] = $title;
        }
    } else {
        $childStatuses[$parentId][] = $st;
    }
}

// Group child statuses under their parent group title
$grouped = [];
foreach ($childStatuses as $pId => $statuses) {
    $groupLabel = $parentGroups[$pId] ?? ($pId === 0 ? 'Order' : 'Other');
    if (!isset($grouped[$groupLabel])) {
        $grouped[$groupLabel] = [];
    }
    foreach ($statuses as $st) {
        $grouped[$groupLabel][] = $st;
    }
}

$renderOption = static function (array $st) use ($selectedStatus): void {
    $value = htmlspecialchars((string)($st['slug'] ?? ''), ENT_QUOTES, 'UTF-8');
    $label = htmlspecialchars((string)($st['title'] ?? $st['slug'] ?? ''), ENT_QUOTES, 'UTF-8');
    $selected = ($selectedStatus !== '' && $selectedStatus === (string)($st['slug'] ?? '')) ? ' selected' : '';
    echo "<option value=\"{$value}\"{$selected}>{$label}</option>";
};

foreach ($grouped as $groupLabel => $statuses) {
    echo '<optgroup label="' . htmlspecialchars((string)$groupLabel, ENT_QUOTES, 'UTF-8') . '">';
    foreach ($statuses as $st) {
        $renderOption($st);
    }
    echo '</optgroup>';
}

