<?php
require_once __DIR__ . '/../../../helpers/order_list_filters.php';

$statusOptions = $status_list ?? [];
$selectedStatuses = normalizeFilterValues($_GET['status'] ?? null);
$statusOp = normalizeFilterOperator($_GET['status_op'] ?? 'in');
$borderClass = $statusOp === 'not_in' ? 'border-red-300' : 'border-gray-300';
?>
<div>
    <?php renderPartial('views/shared/partials/filter_operator_header.php', [
        'operator_name' => 'status_op',
        'label_for' => 'status',
        'label_text' => 'Status',
        'target_select_id' => 'status',
        'selected_op' => $statusOp,
    ]); ?>
    <select
        id="status"
        name="status[]"
        multiple="multiple"
        class="max-w-48 px-2 py-1.5 border <?php echo $borderClass; ?> rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white"
        data-order-filter-select
        data-filter-op="<?php echo htmlspecialchars($statusOp); ?>"
        data-placeholder-in="Select Status"
        data-placeholder-not-in="Exclude statuses…"
        data-selected-values="<?php echo htmlspecialchars(json_encode(array_values($selectedStatuses)), ENT_QUOTES, 'UTF-8'); ?>">
        <?php foreach ($statusOptions as $key => $value): ?>
            <option value="<?php echo htmlspecialchars((string) $key); ?>" <?php echo in_array((string) $key, $selectedStatuses, true) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars((string) $value); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
