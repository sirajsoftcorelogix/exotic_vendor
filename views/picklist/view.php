<?php
/** @var array $data */
require_once __DIR__ . '/partials/item_helpers.php';
require_once __DIR__ . '/partials/ui_constants.php';

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

$flash = $_SESSION['picklist_flash'] ?? null;
if ($flash) {
    unset($_SESSION['picklist_flash']);
}
?>
<div class="w-full px-2 py-4 sm:px-3">
<?php
$mode = 'desktop';
include __DIR__ . '/partials/detail_hero.php';
?>
<?php if (is_array($flash) && trim((string) ($flash['text'] ?? '')) !== ''): ?>
    <?php $ok = ($flash['type'] ?? '') === 'success'; ?>
    <div class="mb-4 rounded-xl border px-4 py-3 text-sm <?= $ok ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-red-200 bg-red-50 text-red-900' ?>">
        <?= htmlspecialchars((string) $flash['text']) ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
    <?php if ($items !== []): ?>
        <div class="px-3 py-3 border-b border-gray-100 flex flex-col sm:flex-row sm:flex-wrap sm:items-center sm:justify-between gap-3">
            <p class="text-sm text-gray-600">
                <span class="font-semibold text-gray-900 tabular-nums"><?= (int) $total ?></span> item<?= (int) $total === 1 ? '' : 's' ?>
                <span class="text-gray-400 mx-1">·</span>
                <span id="picklist-selected-count" class="tabular-nums">0 selected</span>
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer select-none">
                    <input type="checkbox" id="picklist-select-all" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500" aria-label="Select all items">
                    <span class="text-xs font-medium">Select all</span>
                </label>
                <button type="button"
                        id="picklist-bulk-pick-btn"
                        disabled
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold shadow-sm hover:bg-emerald-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    <i class="fas fa-check text-[11px]" aria-hidden="true"></i> Mark picked
                </button>
                <button type="button"
                        id="picklist-bulk-unpick-btn"
                        disabled
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-xs font-semibold shadow-sm hover:bg-amber-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    <i class="fas fa-undo text-[11px]" aria-hidden="true"></i> Revert picks
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="px-3 py-3 border-b border-gray-100">
            <p class="text-sm text-gray-600"><span class="font-semibold text-gray-900">0</span> items</p>
        </div>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                    <th class="px-3 py-3 w-10"></th>
                    <th class="px-3 py-3 whitespace-nowrap">#</th>
                    <th class="px-3 py-3 whitespace-nowrap">Location</th>
                    <th class="px-3 py-3 whitespace-nowrap">Order #</th>
                    <th class="px-3 py-3 whitespace-nowrap">SKU</th>
                    <th class="px-3 py-3 min-w-[12rem]">Item Title</th>
                    <th class="px-3 py-3 whitespace-nowrap">Image</th>
                    <th class="px-3 py-3 whitespace-nowrap">Phys Qty</th>
                    <th class="px-3 py-3 whitespace-nowrap">Order Qty</th>
                    <?php if ($showBookColumns): ?>
                        <th class="px-3 py-3 whitespace-nowrap">Publisher</th>
                        <th class="px-3 py-3 whitespace-nowrap">Cover</th>
                    <?php endif; ?>
                    <th class="px-3 py-3 whitespace-nowrap">Status</th>
                    <th class="px-3 py-3 whitespace-nowrap text-center min-w-[9rem]">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($items as $idx => $item): ?>
                    <?php
                    $isPicked = ($item['status'] ?? '') === 'picked';
                    $isBook = picklist_item_is_book($item);
                    $imageUrl = picklist_item_image_url($item);
                    $itemStatusClass = $itemStatusStyles[$isPicked ? 'picked' : 'pending'];
                    ?>
                    <tr class="picklist-select-row cursor-pointer hover:bg-amber-50/40 transition-colors <?= $isPicked ? 'bg-emerald-50/25' : '' ?>">
                        <td class="px-3 py-3 align-middle">
                            <input type="checkbox"
                                   class="picklist-item-cb w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500 pointer-events-none"
                                   value="<?= (int) ($item['id'] ?? 0) ?>"
                                   data-status="<?= $isPicked ? 'picked' : 'pending' ?>"
                                   aria-label="Select item">
                        </td>
                        <td class="px-3 py-3 align-middle tabular-nums text-gray-500"><?= $idx + 1 ?></td>
                        <td class="px-3 py-3 align-middle font-semibold text-amber-800 whitespace-nowrap"><?= htmlspecialchars((string) ($item['warehouse_location'] ?: '—')) ?></td>
                        <td class="px-3 py-3 align-middle whitespace-nowrap font-mono text-xs text-gray-800"><?= htmlspecialchars((string) ($item['order_number'] ?? '')) ?></td>
                        <td class="px-3 py-3 align-middle whitespace-nowrap text-gray-700"><?= htmlspecialchars(picklist_item_sku($item) ?: '—') ?></td>
                        <td class="px-3 py-3 align-middle">
                            <span class="line-clamp-2 text-gray-900"><?= htmlspecialchars((string) ($item['title'] ?? '')) ?></span>
                        </td>
                        <td class="px-3 py-3 align-middle">
                            <?php if ($imageUrl !== ''): ?>
                                <button type="button"
                                        class="js-picklist-expand-image block p-0 border-0 bg-transparent cursor-zoom-in rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                                        data-full-src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                                        data-image-alt="<?= htmlspecialchars((string) ($item['title'] ?? 'Product image'), ENT_QUOTES, 'UTF-8') ?>"
                                        title="Click to enlarge">
                                    <img src="<?= htmlspecialchars($imageUrl) ?>" alt="" class="w-14 h-14 object-contain border border-gray-200 rounded-lg bg-white pointer-events-none">
                                </button>
                            <?php else: ?>
                                <span class="inline-flex h-14 w-14 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-xs text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-3 align-middle font-semibold tabular-nums"><?= (int) ($item['physical_qty'] ?? 0) ?></td>
                        <td class="px-3 py-3 align-middle font-semibold tabular-nums"><?= (int) ($item['quantity'] ?? 1) ?></td>
                        <?php if ($showBookColumns): ?>
                            <td class="px-3 py-3 align-middle text-gray-700"><?= $isBook ? htmlspecialchars((string) ($item['publisher'] ?? '—')) : '—' ?></td>
                            <td class="px-3 py-3 align-middle text-gray-700"><?= $isBook ? htmlspecialchars((string) ($item['cover_type'] ?? '—')) : '—' ?></td>
                        <?php endif; ?>
                        <td class="px-3 py-3 align-middle whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border <?= $itemStatusClass ?>">
                                <?= $isPicked ? 'Picked' : 'Pending' ?>
                            </span>
                            <?php if ($isPicked && !empty($item['picked_at'])): ?>
                                <div class="text-[11px] text-gray-500 mt-1 tabular-nums"><?= date('d M, H:i', strtotime($item['picked_at'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="picklist-row-actions px-3 py-3 align-middle">
                            <div class="flex flex-wrap items-center justify-center gap-1.5">
                                <?php if ($isPicked): ?>
                                    <button type="button"
                                            class="js-picklist-unpick-item inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-xs font-semibold shadow-sm hover:bg-amber-100 transition"
                                            data-item-id="<?= (int) ($item['id'] ?? 0) ?>"
                                            title="Revert pick">
                                        <i class="fas fa-undo text-[10px]" aria-hidden="true"></i> Revert
                                    </button>
                                <?php endif; ?>
                                <?php $removeConfirm = 'Remove this item from the picklist? The order will be set back to Item Received where applicable.'; ?>
                                <a href="#"
                                   class="js-picklist-confirm-action inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-red-200 bg-red-50 text-red-700 text-xs font-semibold shadow-sm hover:bg-red-100 transition"
                                   data-confirm="<?= htmlspecialchars($removeConfirm, ENT_QUOTES, 'UTF-8') ?>"
                                   data-item-id="<?= (int) ($item['id'] ?? 0) ?>"
                                   title="Remove from picklist">
                                    <i class="fas fa-times text-[10px]" aria-hidden="true"></i> Remove
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($items === []): ?>
                    <tr>
                        <td colspan="<?= $showBookColumns ? 13 : 11 ?>" class="px-3 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 text-xl mb-4">
                                    <i class="fas fa-box-open" aria-hidden="true"></i>
                                </span>
                                <p class="text-base font-medium text-gray-900">No items on this picklist</p>
                                <p class="mt-1 text-sm text-gray-500">Add orders from the orders list using Add to Picklist.</p>
                                <a href="?page=orders&action=list" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-amber-700 hover:text-amber-800">
                                    <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                                    Go to orders
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<?php require_once __DIR__ . '/partials/confirm_modal.php'; ?>
<?php require_once __DIR__ . '/partials/confirm_delete_script.php'; ?>
<?php require_once __DIR__ . '/partials/unpick_script.php'; ?>
<?php require_once __DIR__ . '/partials/bulk_actions_script.php'; ?>
<?php require_once __DIR__ . '/partials/image_lightbox.php'; ?>
