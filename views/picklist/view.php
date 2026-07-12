<?php
/** @var array $data */
$picklist = $data['picklist'] ?? [];
$items = $data['items'] ?? [];
$plId = (int) ($picklist['id'] ?? 0);

$picked = 0;
foreach ($items as $it) {
    if (($it['status'] ?? '') === 'picked') {
        $picked++;
    }
}
$total = count($items);
$pct = $total > 0 ? round(($picked / $total) * 100) : 0;
?>
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <a href="?page=picklist&action=list" class="text-sm text-blue-600 hover:underline">&larr; Back to picklists</a>
            <h1 class="text-2xl font-bold mt-2"><?= htmlspecialchars((string) ($picklist['picklist_number'] ?? '')) ?></h1>
            <p class="text-sm text-gray-600 mt-1">
                Picker: <?= htmlspecialchars((string) ($picklist['picker_name'] ?? 'Unassigned')) ?>
                · Status: <?= htmlspecialchars((string) ($picklist['status'] ?? '')) ?>
                · Progress: <?= $picked ?>/<?= $total ?> (<?= $pct ?>%)
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="?page=picklist&action=tablet&id=<?= $plId ?>" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700">
                <i class="fas fa-tablet-alt mr-1"></i> Tablet mode
            </a>
            <a href="?page=picklist&action=view&id=<?= $plId ?>&print=1" target="_blank" class="px-4 py-2 bg-gray-700 text-white rounded-lg text-sm font-semibold hover:bg-gray-800">
                <i class="fas fa-print mr-1"></i> Print
            </a>
        </div>
    </div>

    <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Location</th>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Item</th>
                    <th class="px-4 py-3">SKU / Code</th>
                    <th class="px-4 py-3">Qty</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php foreach ($items as $idx => $item): ?>
                    <?php $isPicked = ($item['status'] ?? '') === 'picked'; ?>
                    <tr class="<?= $isPicked ? 'bg-green-50' : '' ?>">
                        <td class="px-4 py-3"><?= $idx + 1 ?></td>
                        <td class="px-4 py-3 font-semibold text-amber-800"><?= htmlspecialchars((string) ($item['warehouse_location'] ?: '—')) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string) ($item['order_number'] ?? '')) ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?= htmlspecialchars((string) $item['image']) ?>" alt="" class="w-10 h-10 object-contain border rounded">
                                <?php endif; ?>
                                <span class="line-clamp-2"><?= htmlspecialchars((string) ($item['title'] ?? '')) ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <?= htmlspecialchars((string) ($item['sku'] ?: $item['item_code'] ?: '—')) ?>
                            <?php if (!empty($item['size']) || !empty($item['color'])): ?>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars(trim(($item['size'] ?? '') . ' / ' . ($item['color'] ?? ''), ' /')) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3"><?= (int) ($item['quantity'] ?? 1) ?></td>
                        <td class="px-4 py-3">
                            <?php if ($isPicked): ?>
                                <span class="text-green-700 font-medium">Picked</span>
                                <?php if (!empty($item['picked_at'])): ?>
                                    <div class="text-xs text-gray-500"><?= date('d M H:i', strtotime($item['picked_at'])) ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-amber-700 font-medium">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($items === []): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No items on this picklist.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
