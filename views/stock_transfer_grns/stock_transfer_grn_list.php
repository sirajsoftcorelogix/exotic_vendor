<div class="container mx-auto p-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Stock Transfer GRN List</h1>
        <?php if (!empty($data['transfer'])): ?>
            <a href="?page=stock_transfer_grns&action=create&transfer_id=<?php echo (int)$data['transfer']['id']; ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition">
                <i class="fas fa-file-invoice"></i>
                Add GRN to <?php echo htmlspecialchars($data['transfer']['transfer_order_no'] ?? ''); ?>
            </a>
        <?php else: ?>
            <a href="?page=products&action=stock_transfer" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">
                <i class="fas fa-arrow-left"></i>
                Back to Transfer list
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($data['grns'])): ?>
        <div class="bg-white border border-gray-200 p-4 rounded-lg text-gray-600">No GRN records found.</div>
    <?php else: ?>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr class="text-xs font-bold text-gray-700">
                        <th class="px-4 py-2">GRN ID</th>
                        <th class="px-4 py-2">Transfer Order</th>
                        <th class="px-4 py-2">SKU</th>
                        <th class="px-4 py-2">Item Code</th>
                        <th class="px-4 py-2">Received Date</th>
                        <th class="px-4 py-2">Received By</th>
                        <th class="px-4 py-2">Location</th>
                        <th class="px-4 py-2">Qty Received</th>
                        <th class="px-4 py-2">Qty Acceptable</th>
                        <th class="px-4 py-2">Remarks</th>
                        <th class="px-4 py-2">Created At</th>
                        <th class="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($data['grns'] as $grn): ?>
                        <tr>
                            <td class="px-4 py-2"><?php echo (int)$grn['id']; ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($grn['transfer_order_no'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($grn['sku'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($grn['item_code'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo !empty($grn['received_date']) ? date('j M Y', strtotime($grn['received_date'])) : '-'; ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($grn['received_by_name'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($grn['location_name'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo (int)$grn['qty_received']; ?></td>
                            <td class="px-4 py-2"><?php echo (int)$grn['qty_acceptable']; ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($grn['remarks'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo !empty($grn['created_at']) ? date('j M Y H:i', strtotime($grn['created_at'])) : '-'; ?></td>
                            <td class="px-4 py-2">
                                <a href="javascript:if(confirm('Delete this GRN?')) window.location='?page=stock_transfer_grns&action=delete&grn_id=<?php echo (int)$grn['id']; ?>&transfer_id=<?php echo urlencode($data['transferId'] ?? ''); ?>'" class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-red-600 text-white hover:bg-red-700 transition duration-200 shadow-sm">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>