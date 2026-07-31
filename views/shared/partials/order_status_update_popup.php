<?php
$order_status_list = is_array($order_status_list ?? null) ? $order_status_list : [];
$staff_list = is_array($staff_list ?? null) ? $staff_list : [];
$showOrderVendorName = (bool)($showOrderVendorName ?? false);

$orderPage = (string)($orderPage ?? 'orders');
if (!in_array($orderPage, ['orders', 'posorders'], true)) {
    $orderPage = 'orders';
}

$updateStatusUrl = (string)($updateStatusUrl ?? base_url('index.php?page=' . $orderPage . '&action=update_status'));
$retryStatusApiUrl = (string)($retryStatusApiUrl ?? '');
if ($retryStatusApiUrl === '' && $orderPage === 'posorders') {
    $retryStatusApiUrl = base_url('index.php?page=posorders&action=retry_status_api');
}
$showExoticApiSyncModal = (bool)($showExoticApiSyncModal ?? ($orderPage === 'posorders'));
?>
<div id="statusPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-[250] p-4" onclick="closeStatusPopup(event)">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto relative" onclick="event.stopPropagation();">
        <button type="button" onclick="closeStatusPopup()" class="absolute top-3 right-3 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <div class="grid grid-cols-1 md:grid-cols-[38%_62%] gap-0">
            <div class="p-6 border-b md:border-b-0 md:border-r border-gray-200">
                <img src="https://placehold.co/100x80/e2e8f0/4a5568?text=Item" alt="Product Image" class="rounded-md border h-36 w-full max-w-[220px] object-cover mb-4">
                <p class="text-sm text-gray-600 space-y-1">
                    <strong>Order Number:</strong> <span id="status_order_number"></span><br>
                    <strong>Item Code:</strong> <span id="status_item_code"></span><br>
                    <?php if ($showOrderVendorName): ?>
                    <strong>Vendor Name:</strong> <span id="status_vendor_name"></span><br>
                    <?php endif; ?>
                    <span id="status_category"></span> / <span id="status_sub_category"></span><br>
                    <span id="status_item" class="font-bold"></span>
                </p>
            </div>
            <div class="p-6">
                <h2 class="text-2xl font-bold mb-4">Update Order</h2>
                <form id="statusForm" enctype="multipart/form-data" method="post" action="<?= htmlspecialchars($updateStatusUrl) ?>">
                    <input type="hidden" name="status_order_id" id="status_order_id">
                    <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="orderStatus" class="block text-gray-700 font-bold mb-2">Order Status</label>
                            <select id="orderStatus" name="orderStatus" class="border border-gray-300 rounded px-3 py-2 w-full">
                                <option value="">-- Order Status --</option>
                                <?php renderPartial('views/shared/partials/order_status_select_options.php', [
                                    'order_status_list' => $order_status_list,
                                ]); ?>
                            </select>
                            <input type="hidden" id="previousStatus" name="previousStatus" value="">
                        </div>
                        <div>
                            <label for="statusESD" class="block text-gray-700 font-bold mb-2">Ship By Date</label>
                            <input type="date" id="statusESD" name="esd" class="border border-gray-300 rounded px-2 py-1.5 w-full">
                            <input type="hidden" id="previousESD" name="previous_esd" value="">
                        </div>
                    </div>
                    <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="agentId" class="block text-gray-700 font-bold mb-2">Assign agent</label>
                            <select name="agent_id" id="agentId" class="border border-gray-300 rounded px-3 py-2 w-full">
                                <option value="">Select User</option>
                                <?php foreach ($staff_list as $id => $name): ?>
                                    <option value="<?= (int)$id ?>"><?= htmlspecialchars((string)$name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="agentName" name="agent_name" value="">
                            <input type="hidden" id="previousAgent" name="previous_agent" value="">
                        </div>
                        <div>
                            <label for="orderPriority" class="block text-gray-700 font-bold mb-2">Priority</label>
                            <select id="orderPriority" name="orderPriority" class="border border-gray-300 rounded px-3 py-2 w-full">
                                <option value="">-Select-</option>
                                <option value="critical">Critical</option>
                                <option value="urgent">Urgent</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                            <input type="hidden" id="previousPriority" name="previous_priority" value="">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="orderRemarks" class="block text-gray-700 font-bold mb-2">Notes</label>
                        <textarea id="orderRemarks" name="orderRemarks" class="border border-gray-300 rounded px-3 py-2 w-full" rows="4"></textarea>
                        <input type="hidden" id="previousRemarks" name="previous_remarks" value="">
                    </div>
                    <p class="text-xs text-gray-500 mb-3">Saving updates the local status and syncs to Exotic India when supported for this status.</p>
                    <div id="orderStatusError" class="text-red-500 text-sm mt-1 hidden">Please select a status.</div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeStatusPopup()" class="px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-600">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($showExoticApiSyncModal): ?>
<div id="exoticApiSyncModal" class="fixed inset-0 z-[10000] hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeExoticApiSyncModal()"></div>
    <div class="relative mx-auto mt-24 w-[92%] max-w-lg rounded-xl bg-white shadow-xl">
        <div class="border-b border-amber-200 bg-amber-50 px-5 py-4 rounded-t-xl">
            <h3 class="text-lg font-semibold text-amber-900">Exotic India sync failed</h3>
            <p class="text-sm text-amber-800 mt-1">Local order status was saved, but the vendor portal could not be updated.</p>
        </div>
        <div class="px-5 py-4 space-y-3 text-sm text-gray-700">
            <p id="exoticApiSyncSummary" class="font-medium text-gray-900"></p>
            <div id="exoticApiSyncDetails" class="max-h-48 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 p-3 font-mono text-xs whitespace-pre-wrap text-gray-800"></div>
        </div>
        <div class="flex flex-wrap justify-end gap-2 border-t border-gray-200 px-5 py-4">
            <button type="button" onclick="closeExoticApiSyncModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Close</button>
            <button type="button" id="exoticApiSyncRetryBtn" onclick="retryExoticApiSync()" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Retry sync</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="assets/js/order_workflow_status_filter.js"></script>
<script>
window.OrderStatusPopupConfig = <?= json_encode([
    'updateStatusUrl' => $updateStatusUrl,
    'retryStatusApiUrl' => $retryStatusApiUrl,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="assets/js/order_status_update_popup.js"></script>
