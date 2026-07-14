<?php
require_once __DIR__ . '/../../../helpers/payment_type_groups.php';

$paymentTypeGroups = $payment_type_groups ?? groupPaymentTypes($payment_types ?? []);
$selectedPaymentTypes = $selected_payment_types ?? normalizeSelectedPaymentTypes($_GET['payment_type'] ?? null);
$selectId = $select_id ?? 'payment_type';
$selectClass = $select_class ?? 'advanced-multiselect max-w-48 px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white';
$groupsJson = htmlspecialchars(json_encode($paymentTypeGroups), ENT_QUOTES, 'UTF-8');
$selectedJson = htmlspecialchars(json_encode(array_values($selectedPaymentTypes)), ENT_QUOTES, 'UTF-8');
?>
<div>
    <label for="<?php echo htmlspecialchars($selectId); ?>" class="block text-sm font-medium text-gray-600 mb-1">Payment Type</label>
    <select
        id="<?php echo htmlspecialchars($selectId); ?>"
        name="payment_type[]"
        multiple="multiple"
        class="<?php echo htmlspecialchars($selectClass); ?>"
        data-payment-type-groups="<?php echo $groupsJson; ?>"
        data-selected-payment-types="<?php echo $selectedJson; ?>">
        <?php foreach ($paymentTypeGroups as $groupLabel => $groupItems): ?>
            <?php if ($groupItems === []) {
                continue;
            } ?>
            <optgroup label="<?php echo htmlspecialchars($groupLabel); ?>">
                <option value="<?php echo htmlspecialchars(paymentTypeGroupToken($groupLabel)); ?>" data-is-group="1">
                    Select all <?php echo htmlspecialchars($groupLabel); ?>
                </option>
                <?php foreach ($groupItems as $key => $value): ?>
                    <option
                        value="<?php echo htmlspecialchars($key); ?>"
                        data-group="<?php echo htmlspecialchars($groupLabel); ?>"
                        <?php echo in_array($key, $selectedPaymentTypes, true) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($value); ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>
        <?php endforeach; ?>
    </select>
</div>
