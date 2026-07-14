<?php
/** @var array $data */
require_once __DIR__ . '/partials/item_helpers.php';
require_once __DIR__ . '/partials/ui_constants.php';

$picklist = $data['picklist'] ?? [];
$items = $data['items'] ?? [];
$plId = (int) ($picklist['id'] ?? 0);
$staffList = $data['picker_list'] ?? [];

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
<div class="w-full px-2 py-4 sm:px-3 pb-24">
<?php
$mode = 'tablet';
include __DIR__ . '/partials/detail_hero.php';
?>
<?php if (is_array($flash) && trim((string) ($flash['text'] ?? '')) !== ''): ?>
    <?php $ok = ($flash['type'] ?? '') === 'success'; ?>
    <div class="mb-4 rounded-xl border px-4 py-3 text-sm <?= $ok ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-red-200 bg-red-50 text-red-900' ?>">
        <?= htmlspecialchars((string) $flash['text']) ?>
    </div>
<?php endif; ?>

<?php if ($items !== []): ?>
    <div class="mb-4 bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="px-4 py-3.5 border-b border-gray-100 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-gray-600">
                    <span class="font-semibold text-gray-900 tabular-nums"><?= (int) $total ?></span> items
                    <span class="text-gray-400 mx-1">·</span>
                    <span id="picklist-selected-count" class="tabular-nums">0 selected</span>
                </p>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer select-none shrink-0">
                    <input type="checkbox" id="picklist-select-all" class="w-4 h-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500" aria-label="Select all items">
                    <span class="text-xs font-medium">All</span>
                </label>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button"
                        id="picklist-bulk-pick-btn"
                        disabled
                        class="inline-flex flex-1 min-w-[7rem] items-center justify-center gap-1.5 px-3 py-2.5 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold shadow-sm hover:bg-emerald-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    <i class="fas fa-check text-[11px]" aria-hidden="true"></i> Mark picked
                </button>
                <button type="button"
                        id="picklist-bulk-unpick-btn"
                        disabled
                        class="inline-flex flex-1 min-w-[7rem] items-center justify-center gap-1.5 px-3 py-2.5 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-xs font-semibold shadow-sm hover:bg-amber-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    <i class="fas fa-undo text-[11px]" aria-hidden="true"></i> Revert picks
                </button>
            </div>
        </div>
        <?php if (($picklist['status'] ?? '') !== 'completed'): ?>
            <div class="px-4 py-3 bg-gray-50/80 border-b border-gray-100 flex flex-wrap items-center gap-2">
                <label class="text-xs font-medium text-gray-600 shrink-0">Assign picker</label>
                <select id="tablet-picker-select" class="flex-1 min-w-[8rem] border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-amber-500 focus:border-amber-500">
                    <option value="0">Unassigned</option>
                    <?php foreach ($staffList as $sid => $sname): ?>
                        <option value="<?= (int) $sid ?>" <?= (int) ($picklist['picker_id'] ?? 0) === (int) $sid ? 'selected' : '' ?>><?= htmlspecialchars((string) $sname) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="tablet-assign-btn" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 shadow-sm transition">
                    <i class="fas fa-save text-[10px]" aria-hidden="true"></i> Save
                </button>
            </div>
        <?php endif; ?>
        <div class="px-4 py-2.5 text-xs text-gray-500 border-b border-gray-100">
            Tap a card to pick · Use checkboxes for bulk actions
        </div>
    </div>
<?php elseif (($picklist['status'] ?? '') !== 'completed'): ?>
    <div class="mb-4 bg-white rounded-2xl border border-gray-200/80 shadow-sm px-4 py-3 flex flex-wrap items-center gap-2">
        <label class="text-xs font-medium text-gray-600 shrink-0">Assign picker</label>
        <select id="tablet-picker-select" class="flex-1 min-w-[8rem] border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
            <option value="0">Unassigned</option>
            <?php foreach ($staffList as $sid => $sname): ?>
                <option value="<?= (int) $sid ?>" <?= (int) ($picklist['picker_id'] ?? 0) === (int) $sid ? 'selected' : '' ?>><?= htmlspecialchars((string) $sname) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" id="tablet-assign-btn" class="px-3 py-2 rounded-lg bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700">Save</button>
    </div>
<?php endif; ?>

<div id="tablet-items" class="space-y-3">
    <?php foreach ($items as $item): ?>
        <?php
        $itemId = (int) ($item['id'] ?? 0);
        $isPicked = ($item['status'] ?? '') === 'picked';
        $isBook = picklist_item_is_book($item);
        $imageUrl = picklist_item_image_url($item);
        $itemStatusClass = $itemStatusStyles[$isPicked ? 'picked' : 'pending'];
        ?>
        <div class="pick-item rounded-2xl border shadow-sm transition-all <?= $isPicked ? 'border-emerald-200/80 bg-emerald-50/40' : 'border-gray-200/80 bg-white cursor-pointer hover:border-amber-200 hover:shadow-md active:scale-[0.995]' ?>"
             data-item-id="<?= $itemId ?>"
             data-picked="<?= $isPicked ? '1' : '0' ?>">
            <div class="p-4">
                <div class="flex items-start gap-3">
                    <label class="flex-shrink-0 pt-1 cursor-pointer" onclick="event.stopPropagation();">
                        <input type="checkbox"
                               class="picklist-item-cb w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                               value="<?= $itemId ?>"
                               data-status="<?= $isPicked ? 'picked' : 'pending' ?>"
                               aria-label="Select item">
                    </label>
                    <?php if ($imageUrl !== ''): ?>
                        <button type="button"
                                class="js-picklist-expand-image flex-shrink-0 p-0 border-0 bg-transparent cursor-zoom-in rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                                data-full-src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                                data-image-alt="<?= htmlspecialchars((string) ($item['title'] ?? 'Product image'), ENT_QUOTES, 'UTF-8') ?>"
                                title="Tap to enlarge">
                            <img src="<?= htmlspecialchars($imageUrl) ?>" alt="" class="w-20 h-20 sm:w-24 sm:h-24 object-contain border border-gray-200 rounded-xl bg-white pointer-events-none">
                        </button>
                    <?php else: ?>
                        <div class="w-20 h-20 sm:w-24 sm:h-24 border border-gray-200 rounded-xl flex-shrink-0 bg-gray-50 flex items-center justify-center text-xs text-gray-400">No image</div>
                    <?php endif; ?>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="text-base font-bold text-amber-800"><?= htmlspecialchars((string) ($item['warehouse_location'] ?: 'No location')) ?></span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border <?= $itemStatusClass ?>">
                                <?= $isPicked ? 'Picked' : 'Pending' ?>
                            </span>
                        </div>
                        <div class="text-sm font-semibold text-gray-900">Order: <span class="font-mono text-xs"><?= htmlspecialchars((string) ($item['order_number'] ?? '')) ?></span></div>
                        <div class="text-xs text-gray-500 mt-0.5">SKU: <?= htmlspecialchars(picklist_item_sku($item) ?: '—') ?></div>
                        <div class="text-sm text-gray-800 mt-1.5 line-clamp-2 leading-snug"><?= htmlspecialchars((string) ($item['title'] ?? '')) ?></div>
                        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-600">
                            <span>Phys: <strong class="text-gray-900 tabular-nums"><?= (int) ($item['physical_qty'] ?? 0) ?></strong></span>
                            <span>Order: <strong class="text-gray-900 tabular-nums"><?= (int) ($item['quantity'] ?? 1) ?></strong></span>
                        </div>
                        <?php if ($isBook): ?>
                            <div class="text-xs text-gray-500 mt-1 line-clamp-1">
                                <?= htmlspecialchars((string) ($item['publisher'] ?? '—')) ?> · <?= htmlspecialchars((string) ($item['cover_type'] ?? '—')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-shrink-0 flex flex-col items-center gap-2">
                        <?php if ($isPicked): ?>
                            <span class="inline-flex items-center justify-center w-11 h-11 rounded-full bg-emerald-600 text-white shadow-sm"><i class="fas fa-check"></i></span>
                            <button type="button"
                                    class="js-picklist-unpick-item inline-flex items-center justify-center w-9 h-9 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100 transition"
                                    data-item-id="<?= $itemId ?>"
                                    title="Revert pick">
                                <i class="fas fa-undo text-xs" aria-hidden="true"></i>
                            </button>
                        <?php else: ?>
                            <span class="inline-flex items-center justify-center w-11 h-11 rounded-full border-2 border-amber-500 bg-amber-50 text-amber-700 text-[10px] font-bold tracking-wide">PICK</span>
                        <?php endif; ?>
                        <button type="button"
                                class="remove-item-btn inline-flex items-center justify-center w-9 h-9 rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 transition"
                                data-item-id="<?= $itemId ?>"
                                title="Remove from picklist">
                            <i class="fas fa-times text-xs" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if ($items === []): ?>
        <div class="rounded-2xl border border-gray-200/80 bg-white shadow-sm px-6 py-16 text-center">
            <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 text-xl mb-4">
                <i class="fas fa-box-open" aria-hidden="true"></i>
            </span>
            <p class="text-base font-medium text-gray-900">No items on this picklist</p>
            <p class="mt-1 text-sm text-gray-500">Add orders from the orders list.</p>
            <a href="?page=orders&action=list" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-amber-700 hover:text-amber-800">
                <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                Go to orders
            </a>
        </div>
    <?php endif; ?>
</div>
</div>

<?php require_once __DIR__ . '/partials/confirm_modal.php'; ?>
<?php require_once __DIR__ . '/partials/unpick_script.php'; ?>
<?php require_once __DIR__ . '/partials/bulk_actions_script.php'; ?>

<script>
(function() {
    const picklistId = <?= (int) $plId ?>;

    document.querySelectorAll('.pick-item[data-picked="0"]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item-btn')) return;
            if (e.target.closest('.js-picklist-unpick-item')) return;
            if (e.target.closest('.js-picklist-expand-image')) return;
            if (e.target.closest('.picklist-item-cb')) return;
            if (e.target.closest('label')) return;
            const itemId = el.getAttribute('data-item-id');
            if (!itemId || el.getAttribute('data-picked') === '1') return;

            openPicklistConfirmModal({
                title: 'Mark as picked?',
                message: 'Mark this item as picked?',
                confirmText: 'Yes, mark picked',
                cancelText: 'Cancel'
            }).then(function(confirmed) {
                if (!confirmed) return;

                el.style.pointerEvents = 'none';
                const fd = new FormData();
                fd.append('item_id', itemId);

                fetch('index.php?page=picklist&action=pick_item', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            showAlert(data.message || 'Failed to pick item.', 'error');
                            el.style.pointerEvents = '';
                        }
                    })
                    .catch(function() {
                        showAlert('Network error.', 'error');
                        el.style.pointerEvents = '';
                    });
            });
        });
    });

    document.querySelectorAll('.remove-item-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const itemId = btn.getAttribute('data-item-id');
            if (!itemId) return;
            if (!window.confirm('Remove this item from the picklist? The order will be set back to Item Received if applicable.')) return;

            btn.disabled = true;
            const fd = new FormData();
            fd.append('item_id', itemId);

            fetch('index.php?page=picklist&action=delete_item', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const card = btn.closest('.pick-item');
                        if (card) card.remove();
                        if (data.picklist_deleted) {
                            showAlert('Last item removed — picklist deleted.', 'success');
                            setTimeout(function() {
                                window.location.href = data.redirect || 'index.php?page=picklist&action=list';
                            }, 800);
                        } else if (!document.querySelector('.pick-item')) {
                            showAlert('No items left on this picklist.', 'success');
                            setTimeout(function() { location.reload(); }, 800);
                        } else {
                            showAlert(data.message || 'Item removed.', 'success');
                        }
                    } else {
                        showAlert(data.message || 'Failed to remove item.', 'error');
                        btn.disabled = false;
                    }
                })
                .catch(function() {
                    showAlert('Network error.', 'error');
                    btn.disabled = false;
                });
        });
    });

    const assignBtn = document.getElementById('tablet-assign-btn');
    if (assignBtn) {
        assignBtn.addEventListener('click', function() {
            const pickerId = document.getElementById('tablet-picker-select').value;
            const fd = new FormData();
            fd.append('picklist_id', picklistId);
            fd.append('picker_id', pickerId);
            fetch('index.php?page=picklist&action=assign_picker', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => showAlert(data.message || (data.success ? 'Saved' : 'Failed'), data.success ? 'success' : 'error'));
        });
    }
})();
</script>
<?php require_once __DIR__ . '/partials/image_lightbox.php'; ?>
