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
            <a href="?page=picklist&action=tablet&id=<?= $plId ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 shadow-sm">
                <i class="fas fa-tablet-alt" aria-hidden="true"></i> Tablet mode
            </a>
            <a href="?page=picklist&action=view&id=<?= $plId ?>&print=1" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-700 text-white text-sm font-semibold hover:bg-gray-800 shadow-sm">
                <i class="fas fa-print" aria-hidden="true"></i> Print
            </a>
            <?php
            $deleteConfirm = 'Delete picklist ' . (string) ($picklist['picklist_number'] ?? '') . '? Orders on this list will be set back to Item Received where applicable.';
            ?>
            <a href="?page=picklist&action=delete&id=<?= $plId ?>"
               class="js-picklist-confirm-action inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm font-semibold hover:bg-red-100 shadow-sm"
               data-confirm="<?= htmlspecialchars($deleteConfirm, ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-trash-alt" aria-hidden="true"></i> Delete
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
                    <th class="px-4 py-3">Order Qty</th>
                    <?php if ($showBookColumns): ?>
                        <th class="px-4 py-3">Publisher</th>
                        <th class="px-4 py-3">Cover Type</th>
                    <?php endif; ?>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
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
                                <button type="button"
                                        class="js-picklist-expand-image block p-0 border-0 bg-transparent cursor-zoom-in rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                                        data-full-src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                                        data-image-alt="<?= htmlspecialchars((string) ($item['title'] ?? 'Product image'), ENT_QUOTES, 'UTF-8') ?>"
                                        title="Click to enlarge">
                                    <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars((string) ($item['title'] ?? '')) ?>" class="w-16 h-16 object-contain border rounded bg-white pointer-events-none">
                                </button>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 font-semibold"><?= (int) ($item['physical_qty'] ?? 0) ?></td>
                        <td class="px-4 py-3 font-semibold"><?= (int) ($item['quantity'] ?? 1) ?></td>
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
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="inline-flex flex-wrap items-center justify-end gap-1.5">
                            <?php if ($isPicked): ?>
                                <button type="button"
                                        class="js-picklist-unpick-item inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-amber-200 bg-amber-50 text-amber-800 text-xs font-semibold hover:bg-amber-100"
                                        data-item-id="<?= (int) ($item['id'] ?? 0) ?>"
                                        title="Revert pick">
                                    <i class="fas fa-undo" aria-hidden="true"></i> Revert
                                </button>
                            <?php endif; ?>
                            <?php
                            $removeConfirm = 'Remove this item from the picklist? The order will be set back to Item Received if applicable.';
                            ?>
                            <a href="#"
                               class="js-picklist-confirm-action inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-red-200 bg-red-50 text-red-700 text-xs font-semibold hover:bg-red-100"
                               data-confirm="<?= htmlspecialchars($removeConfirm, ENT_QUOTES, 'UTF-8') ?>"
                               data-item-id="<?= (int) ($item['id'] ?? 0) ?>"
                               title="Remove from picklist">
                                <i class="fas fa-times" aria-hidden="true"></i> Remove
                            </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($items === []): ?>
                    <?php $colspan = $showBookColumns ? 12 : 10; ?>
                    <tr><td colspan="<?= $colspan ?>" class="px-4 py-8 text-center text-gray-500">No items on this picklist.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/partials/confirm_modal.php'; ?>
<?php require_once __DIR__ . '/partials/confirm_delete_script.php'; ?>
<?php require_once __DIR__ . '/partials/unpick_script.php'; ?>
<?php require_once __DIR__ . '/partials/image_lightbox.php'; ?>
