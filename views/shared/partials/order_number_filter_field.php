<?php
$orderNumberValue = (string) ($value ?? ($_GET['order_number'] ?? ''));
?>
<div>
    <label for="order-number" class="block text-sm font-medium text-gray-600 mb-1">Order No</label>
    <textarea
        name="order_number"
        id="order-number"
        rows="2"
        placeholder="One or more order numbers (comma, space, or new line)"
        class="w-full px-2 py-2 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 resize-y min-h-[2.5rem]"><?php echo htmlspecialchars($orderNumberValue); ?></textarea>
</div>
