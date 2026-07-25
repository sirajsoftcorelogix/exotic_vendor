<?php
require_once __DIR__ . '/../../../helpers/order_list_filters.php';

$operatorName = (string) ($operator_name ?? 'status_op');
$labelFor = (string) ($label_for ?? 'status');
$labelText = (string) ($label_text ?? 'Status');
$targetSelectId = (string) ($target_select_id ?? str_replace('_op', '', $operatorName));
$badgeId = (string) ($badge_id ?? ($operatorName . '-badge'));
$selectedOp = normalizeFilterOperator($selected_op ?? ($_GET[$operatorName] ?? 'in'));
$isExclude = $selectedOp === 'not_in';
?>
<div class="flex items-center justify-between gap-2 mb-1">
    <label for="<?php echo htmlspecialchars($labelFor); ?>" class="text-sm font-medium text-gray-600">
        <?php echo htmlspecialchars($labelText); ?>
        <span id="<?php echo htmlspecialchars($badgeId); ?>" class="<?php echo $isExclude ? '' : 'hidden'; ?> text-[10px] font-semibold uppercase text-red-600 ml-1">Exclude</span>
    </label>
    <div class="inline-flex rounded-md border border-gray-300 text-[11px] overflow-hidden shrink-0" data-filter-operator data-target-select="<?php echo htmlspecialchars($targetSelectId); ?>" data-badge-id="<?php echo htmlspecialchars($badgeId); ?>">
        <?php foreach (['in' => 'In', 'not_in' => 'Not In'] as $value => $text): ?>
            <label class="cursor-pointer <?php echo $value === 'not_in' ? 'border-l border-gray-300' : ''; ?>">
                <input type="radio" name="<?php echo htmlspecialchars($operatorName); ?>" value="<?php echo $value; ?>" class="peer sr-only" <?php echo $selectedOp === $value ? 'checked' : ''; ?>>
                <span class="inline-block px-2 py-0.5 text-gray-600 peer-checked:bg-amber-500 peer-checked:text-white"><?php echo $text; ?></span>
            </label>
        <?php endforeach; ?>
    </div>
</div>
