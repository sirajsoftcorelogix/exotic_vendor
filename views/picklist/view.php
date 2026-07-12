<?php
/** @var array $data */
require_once __DIR__ . '/partials/item_helpers.php';

$picklist = $data['picklist'] ?? [];
$items = $data['items'] ?? [];
$plId = (int) ($picklist['id'] ?? 0);
$showBookColumns = picklist_any_book_items($items);

$picked = 0;
foreach ($items as $it) {
    if (($it['status'] ?? '') === 'picked') {
        $picked++;
    }
}
$total = count($items);
$pct = $total > 0 ? round(($picked / $total) * 100) : 0;
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
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

    <div class="bg-white border rounded-xl shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Location</th>
                    <th class="px-4 py-3">Order Number</th>
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">Item Title</th>
                    <th class="px-4 py-3">Item Image</th>
                    <th class="px-4 py-3">Physical Qty</th>
                    <?php if ($showBookColumns): ?>
                        <th class="px-4 py-3">Publisher</th>
                        <th class="px-4 py-3">Cover Type</th>
                    <?php endif; ?>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php foreach ($items as $idx => $item): ?>
                    <?php
                    $isPicked = ($item['status'] ?? '') === 'picked';
                    $isBook = picklist_item_is_book($item);
                    $imageUrl = picklist_item_image_url($item);
                    ?>
                    <tr class="<?= $isPicked ? 'bg-green-50' : '' ?>">
                        <td class="px-4 py-3"><?= $idx + 1 ?></td>
                        <td class="px-4 py-3 font-semibold text-amber-800 whitespace-nowrap"><?= htmlspecialchars((string) ($item['warehouse_location'] ?: '—')) ?></td>
                        <td class="px-4 py-3 whitespace-nowrap"><?= htmlspecialchars((string) ($item['order_number'] ?? '')) ?></td>
                        <td class="px-4 py-3 whitespace-nowrap"><?= htmlspecialchars(picklist_item_sku($item) ?: '—') ?></td>
                        <td class="px-4 py-3 max-w-xs">
                            <span class="line-clamp-3"><?= htmlspecialchars((string) ($item['title'] ?? '')) ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($imageUrl !== ''): ?>
                                <img src="<?= htmlspecialchars($imageUrl) ?>" alt="" class="w-16 h-16 object-contain border rounded bg-white">
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 font-semibold"><?= (int) ($item['physical_qty'] ?? 0) ?></td>
                        <?php if ($showBookColumns): ?>
                            <td class="px-4 py-3"><?= $isBook ? htmlspecialchars((string) ($item['publisher'] ?? '—')) : '—' ?></td>
                            <td class="px-4 py-3"><?= $isBook ? htmlspecialchars((string) ($item['cover_type'] ?? '—')) : '—' ?></td>
                        <?php endif; ?>
                        <td class="px-4 py-3 whitespace-nowrap">
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
                    <?php $colspan = $showBookColumns ? 10 : 8; ?>
                    <tr><td colspan="<?= $colspan ?>" class="px-4 py-8 text-center text-gray-500">No items on this picklist.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
