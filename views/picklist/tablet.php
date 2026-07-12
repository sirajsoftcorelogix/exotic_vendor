<?php
/** @var array $data */
require_once __DIR__ . '/partials/item_helpers.php';

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
?>
<div class="max-w-3xl mx-auto px-3 sm:px-4 py-4 pb-24">
    <div class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b pb-3 mb-4 -mx-3 px-3 sm:-mx-4 sm:px-4">
        <a href="?page=picklist&action=view&id=<?= $plId ?>" class="text-sm text-blue-600">&larr; Detail view</a>
        <h1 class="text-xl font-bold mt-1"><?= htmlspecialchars((string) ($picklist['picklist_number'] ?? '')) ?></h1>
        <p class="text-sm text-gray-600"><?= $picked ?> of <?= $total ?> picked · Tap an item when collected</p>
        <?php if (($picklist['status'] ?? '') !== 'completed'): ?>
            <div class="mt-2 flex gap-2 items-center">
                <label class="text-xs text-gray-600">Assign picker:</label>
                <select id="tablet-picker-select" class="border rounded px-2 py-1 text-sm flex-1">
                    <option value="0">Unassigned</option>
                    <?php foreach ($staffList as $sid => $sname): ?>
                        <option value="<?= (int) $sid ?>" <?= (int) ($picklist['picker_id'] ?? 0) === (int) $sid ? 'selected' : '' ?>><?= htmlspecialchars((string) $sname) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="tablet-assign-btn" class="text-sm px-3 py-1 bg-gray-200 rounded">Save</button>
            </div>
        <?php endif; ?>
    </div>

    <div id="tablet-items" class="space-y-3">
        <?php foreach ($items as $item): ?>
            <?php
            $itemId = (int) ($item['id'] ?? 0);
            $isPicked = ($item['status'] ?? '') === 'picked';
            $isBook = picklist_item_is_book($item);
            $imageUrl = picklist_item_image_url($item);
            ?>
            <div class="pick-item border rounded-xl p-4 shadow-sm <?= $isPicked ? 'bg-green-50 border-green-300 opacity-75' : 'bg-white cursor-pointer active:scale-[0.99]' ?>"
                 data-item-id="<?= $itemId ?>"
                 data-picked="<?= $isPicked ? '1' : '0' ?>">
                <div class="flex gap-3">
                    <?php if ($imageUrl !== ''): ?>
                        <img src="<?= htmlspecialchars($imageUrl) ?>" alt="" class="w-24 h-24 object-contain border rounded flex-shrink-0 bg-white">
                    <?php else: ?>
                        <div class="w-24 h-24 border rounded flex-shrink-0 bg-gray-50 flex items-center justify-center text-xs text-gray-400">No image</div>
                    <?php endif; ?>
                    <div class="min-w-0 flex-1">
                        <div class="text-lg font-bold text-amber-800"><?= htmlspecialchars((string) ($item['warehouse_location'] ?: 'No location')) ?></div>
                        <div class="text-sm font-semibold text-gray-900 mt-1">Order: <?= htmlspecialchars((string) ($item['order_number'] ?? '')) ?></div>
                        <div class="text-xs text-gray-600 mt-0.5">SKU: <?= htmlspecialchars(picklist_item_sku($item) ?: '—') ?></div>
                        <div class="text-sm text-gray-800 mt-1 line-clamp-3"><?= htmlspecialchars((string) ($item['title'] ?? '')) ?></div>
                        <div class="text-xs text-gray-600 mt-2">
                            Physical Qty: <span class="font-semibold"><?= (int) ($item['physical_qty'] ?? 0) ?></span>
                            · Order Qty: <?= (int) ($item['quantity'] ?? 1) ?>
                        </div>
                        <?php if ($isBook): ?>
                            <div class="text-xs text-gray-600 mt-1">
                                Publisher: <?= htmlspecialchars((string) ($item['publisher'] ?? '—')) ?>
                                · Cover: <?= htmlspecialchars((string) ($item['cover_type'] ?? '—')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-shrink-0 self-center flex flex-col items-center gap-2">
                        <?php if ($isPicked): ?>
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-green-600 text-white"><i class="fas fa-check"></i></span>
                        <?php else: ?>
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full border-2 border-amber-500 text-amber-600 text-xs font-bold">PICK</span>
                        <?php endif; ?>
                        <button type="button"
                                class="remove-item-btn text-xs px-2 py-1 rounded border border-red-200 text-red-700 bg-red-50 hover:bg-red-100"
                                data-item-id="<?= $itemId ?>"
                                title="Remove from picklist">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($items === []): ?>
            <p class="text-center text-gray-500 py-8">No items on this picklist.</p>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    const picklistId = <?= (int) $plId ?>;

    document.querySelectorAll('.pick-item[data-picked="0"]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item-btn')) return;
            const itemId = el.getAttribute('data-item-id');
            if (!itemId || el.getAttribute('data-picked') === '1') return;
            if (!confirm('Mark this item as picked?')) return;

            el.style.pointerEvents = 'none';
            const fd = new FormData();
            fd.append('item_id', itemId);

            fetch('index.php?page=picklist&action=pick_item', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        el.setAttribute('data-picked', '1');
                        el.classList.add('bg-green-50', 'border-green-300', 'opacity-75');
                        el.classList.remove('cursor-pointer', 'bg-white');
                        const badge = el.querySelector('.flex-shrink-0 span');
                        if (badge) {
                            badge.className = 'inline-flex items-center justify-center w-10 h-10 rounded-full bg-green-600 text-white';
                            badge.innerHTML = '<i class="fas fa-check"></i>';
                        }
                        if (data.picklist_completed) {
                            showAlert('All items picked — picklist complete!', 'success');
                        }
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

    document.querySelectorAll('.remove-item-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const itemId = btn.getAttribute('data-item-id');
            if (!itemId) return;
            if (!confirm('Remove this item from the picklist? The order will be set back to Item Received if applicable.')) return;

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
