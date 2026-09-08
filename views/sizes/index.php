<?php
$searchValue = htmlspecialchars((string) ($search ?? ''), ENT_QUOTES, 'UTF-8');
$statusValue = (string) ($status_filter ?? '');
$itemGroupFilterValue = (string) ($item_group_filter ?? '');
$currentPage = max(1, (int) ($currentPage ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$limit = (int) ($limit ?? 20);
$totalRecords = (int) ($totalRecords ?? 0);
$nextDisplayOrder = (int) ($next_display_order ?? 10);
$queryBase = [
    'page' => 'sizes',
    'action' => 'list',
    'search_text' => (string) ($search ?? ''),
    'status_filter' => $statusValue,
    'item_group_filter' => $itemGroupFilterValue,
    'limit' => $limit,
];
$itemGroupLabels = is_array($item_group_labels ?? null) ? $item_group_labels : [];
$itemGroups = is_array($item_groups ?? null) ? $item_groups : [];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-ruler-combined text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Master data · Sizes</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Size <span class="text-amber-800">listing</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Manage inbound size dropdowns by item group. Groups with sizes show a dropdown; others stay as a text box.
                </p>
            </div>
            <div class="flex shrink-0 lg:pl-4 lg:self-center gap-3 flex-wrap">
                <button type="button" id="openSizeModalBtn"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
                    Add size
                </button>
            </div>
        </div>
    </div>

    <div id="sizeAlert" class="hidden rounded-xl border px-4 py-3 text-sm font-medium"></div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <div class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 border-b border-amber-100/80">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                    <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900">Search &amp; filters</h2>
                    <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Find sizes by code, label, item group, or status.</p>
                </div>
            </div>
        </div>
        <form method="get" id="filterForm" class="p-5">
            <input type="hidden" name="page" value="sizes">
            <input type="hidden" name="action" value="list">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-x-5 gap-y-4">
                <div class="sm:col-span-2 lg:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                    <input type="text" name="search_text" placeholder="Search by size code, label, or group"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition"
                        value="<?php echo $searchValue; ?>" autocomplete="off">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Item Group</label>
                    <select name="item_group_filter" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="">All Item Groups</option>
                        <?php foreach (($item_groups ?? []) as $itemGroupOption): ?>
                            <?php
                            $optionValue = (string) ($itemGroupOption['name'] ?? '');
                            $optionLabel = (string) ($itemGroupOption['display_name'] ?? $optionValue);
                            ?>
                            <option value="<?php echo htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $itemGroupFilterValue === $optionValue ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status_filter" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="">All status</option>
                        <option value="active" <?php echo $statusValue === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusValue === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Per page</label>
                    <select name="limit" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <?php foreach ([10, 20, 50, 100] as $opt): ?>
                            <option value="<?php echo $opt; ?>" <?php echo $limit === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 transition shadow-sm">
                    <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
                    Apply filters
                </button>
                <button type="button" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition"
                    onclick="window.location='?page=sizes&action=list';">
                    Reset
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div class="text-sm text-gray-600">
                    Total sizes: <span class="font-semibold text-gray-900"><?php echo number_format($totalRecords); ?></span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th class="px-5 py-3.5 whitespace-nowrap">#</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Item group</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Size code</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Label</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Order</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Status</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Updated</th>
                        <th class="px-5 py-3.5 whitespace-nowrap text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($sizes)): ?>
                        <?php $counter = ($currentPage - 1) * $limit; ?>
                        <?php foreach ($sizes as $row): ?>
                            <?php
                            $id = (int) ($row['id'] ?? 0);
                            $itemGroup = trim((string) ($row['item_group'] ?? ''));
                            $itemGroupDisplay = trim((string) ($row['item_group_display'] ?? ''));
                            if ($itemGroupDisplay === '' && $itemGroup !== '') {
                                $itemGroupDisplay = (string) ($itemGroupLabels[$itemGroup] ?? $itemGroup);
                            }
                            $sizeCode = (string) ($row['size_code'] ?? '');
                            $sizeLabel = (string) ($row['size_label'] ?? '');
                            $displayOrder = (int) ($row['display_order'] ?? 0);
                            $active = (int) ($row['is_active'] ?? 0) === 1;
                            $updatedRaw = (string) ($row['updated_at'] ?? '');
                            $updatedDisplay = $updatedRaw !== '' && ($updatedTs = strtotime($updatedRaw))
                                ? date('jS F Y', $updatedTs)
                                : '';
                            ?>
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="px-5 py-4 text-sm text-gray-700"><?php echo ++$counter; ?></td>
                                <td class="px-5 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($itemGroupDisplay !== '' ? $itemGroupDisplay : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm font-mono font-semibold text-gray-900"><?php echo htmlspecialchars($sizeCode, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm text-gray-800"><?php echo htmlspecialchars($sizeLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm text-gray-700"><?php echo $displayOrder; ?></td>
                                <td class="px-5 py-4 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo $active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'; ?>">
                                        <?php echo $active ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($updatedDisplay, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm text-right whitespace-nowrap">
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        onclick='openSizeModal(<?php echo json_encode([
                                            'id' => $id,
                                            'item_group' => $itemGroup,
                                            'size_code' => $sizeCode,
                                            'size_label' => $sizeLabel,
                                            'display_order' => $displayOrder,
                                            'is_active' => $active ? 1 : 0,
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        Edit
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50"
                                        onclick="setSizeStatus(<?php echo $id; ?>, <?php echo $active ? 0 : 1; ?>)">
                                        <?php echo $active ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50"
                                        onclick="askDeleteSize(<?php echo $id; ?>, <?php echo json_encode($sizeCode, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-sm text-gray-500">No sizes found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
                    <div class="text-sm text-gray-500">Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?></div>
                    <div class="flex flex-wrap gap-2">
                        <?php
                        $start = max(1, $currentPage - 3);
                        $end = min($totalPages, $currentPage + 3);
                        for ($p = $start; $p <= $end; $p++):
                            $queryBase['page_no'] = $p;
                            $url = '?' . http_build_query($queryBase);
                        ?>
                            <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
                               class="rounded-lg border px-3 py-1.5 text-sm <?php echo $p === $currentPage ? 'border-amber-500 bg-amber-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $p; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="sizeModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b px-6 py-4">
            <h2 id="sizeModalTitle" class="text-lg font-semibold text-gray-900">Add size</h2>
            <button type="button" onclick="closeSizeModal()" class="text-gray-400 hover:text-gray-700">✕</button>
        </div>
        <form id="sizeForm" class="space-y-4 px-6 py-5">
            <input type="hidden" name="id" id="size_id">
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Item Group</label>
                <select name="item_group" id="size_item_group" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                    <option value="">Select item group</option>
                    <?php foreach (($item_groups ?? []) as $itemGroupOption): ?>
                        <?php
                        $optionValue = (string) ($itemGroupOption['name'] ?? '');
                        $optionLabel = (string) ($itemGroupOption['display_name'] ?? $optionValue);
                        ?>
                        <option value="<?php echo htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Size code</label>
                <input type="text" name="size_code" id="size_code" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"
                    placeholder="e.g. XL">
                <span id="sizeCodeMsg" class="text-sm text-red-500"></span>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Size label</label>
                <input type="text" name="size_label" id="size_label" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"
                    placeholder="e.g. Extra Large (XL)(42)">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Display order</label>
                    <input type="number" name="display_order" id="size_display_order" min="1"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"
                        value="<?php echo $nextDisplayOrder; ?>">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Status</label>
                    <select name="is_active" id="size_is_active"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t pt-4">
                <button type="button" onclick="closeSizeModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="sizeSaveBtn" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Save size</button>
            </div>
        </form>
    </div>
</div>

<div id="sizeConfirmModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-xl p-6 text-center">
        <h3 id="sizeConfirmTitle" class="text-lg font-semibold text-gray-900 mb-2">Confirm</h3>
        <p id="sizeConfirmMessage" class="text-sm text-gray-600 mb-6">Are you sure?</p>
        <div class="flex justify-center gap-3">
            <button type="button" id="sizeConfirmCancel" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
            <button type="button" id="sizeConfirmOk" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Delete</button>
        </div>
    </div>
</div>

<script>
let sizeCodeExists = false;
let pendingDeleteId = 0;

function showSizeAlert(message, success) {
    const box = document.getElementById('sizeAlert');
    if (!box) return;
    box.textContent = message || '';
    box.classList.remove('hidden', 'border-green-200', 'bg-green-50', 'text-green-700', 'border-red-200', 'bg-red-50', 'text-red-700');
    box.classList.add(success ? 'border-green-200' : 'border-red-200', success ? 'bg-green-50' : 'bg-red-50', success ? 'text-green-700' : 'text-red-700');
}

function postSizeAction(action, body) {
    return fetch('index.php?page=sizes&action=' + action, {
        method: 'POST',
        credentials: 'same-origin',
        body: body
    }).then(function (res) { return res.json(); });
}

function openSizeModal(size) {
    size = size || {};
    sizeCodeExists = false;
    const nameMsg = document.getElementById('sizeCodeMsg');
    if (nameMsg) nameMsg.textContent = '';
    document.getElementById('sizeModalTitle').textContent = size.id ? 'Edit size' : 'Add size';
    document.getElementById('size_id').value = size.id || '';
    document.getElementById('size_item_group').value = size.item_group != null && size.item_group !== '' ? String(size.item_group) : '';
    document.getElementById('size_code').value = size.size_code || '';
    document.getElementById('size_label').value = size.size_label || '';
    document.getElementById('size_display_order').value = size.display_order != null ? String(size.display_order) : '<?php echo $nextDisplayOrder; ?>';
    document.getElementById('size_is_active').value = size.is_active != null ? String(size.is_active) : '1';
    document.getElementById('sizeModal').classList.remove('hidden');
    document.getElementById('sizeModal').classList.add('flex');
    setTimeout(function () { document.getElementById('size_code').focus(); }, 50);
    if (!size.id) {
        refreshNextOrder();
    }
}

function closeSizeModal() {
    document.getElementById('sizeModal').classList.add('hidden');
    document.getElementById('sizeModal').classList.remove('flex');
}

function refreshNextOrder() {
    const group = document.getElementById('size_item_group').value;
    const id = document.getElementById('size_id').value;
    if (id) return;
    fetch('index.php?page=sizes&action=nextOrder&item_group=' + encodeURIComponent(group), { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data && data.next) {
                document.getElementById('size_display_order').value = data.next;
            }
        })
        .catch(function () {});
}

function checkSizeCodeDuplicate() {
    const code = document.getElementById('size_code').value.trim();
    const group = document.getElementById('size_item_group').value.trim();
    const msgEl = document.getElementById('sizeCodeMsg');
    if (code.length < 1 || group === '') {
        sizeCodeExists = false;
        if (msgEl) msgEl.textContent = '';
        return;
    }
    const excludeId = document.getElementById('size_id').value || 0;
    let url = 'index.php?page=sizes&action=checkCode&item_group=' + encodeURIComponent(group) + '&size_code=' + encodeURIComponent(code);
    if (excludeId && parseInt(excludeId, 10) > 0) {
        url += '&excludeId=' + encodeURIComponent(String(excludeId));
    }
    fetch(url, { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.exists) {
                sizeCodeExists = true;
                if (msgEl) msgEl.textContent = 'This size code already exists for the selected group.';
            } else {
                sizeCodeExists = false;
                if (msgEl) msgEl.textContent = '';
            }
        })
        .catch(function () {});
}

document.getElementById('openSizeModalBtn')?.addEventListener('click', function () {
    openSizeModal();
});

document.getElementById('size_item_group')?.addEventListener('change', function () {
    refreshNextOrder();
    checkSizeCodeDuplicate();
});
document.getElementById('size_code')?.addEventListener('keyup', checkSizeCodeDuplicate);

document.getElementById('sizeForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    if (sizeCodeExists) {
        showSizeAlert('This size code already exists for the selected group.', false);
        return;
    }
    const form = new FormData(this);
    const btn = document.getElementById('sizeSaveBtn');
    const oldLabel = btn ? btn.textContent : '';
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Saving...';
    }
    postSizeAction('save', form).then(function (res) {
        showSizeAlert(res.message || (res.success ? 'Size saved.' : 'Could not save size.'), !!res.success);
        if (res.success) {
            closeSizeModal();
            setTimeout(function () { window.location.reload(); }, 700);
        }
    }).catch(function () {
        showSizeAlert('Could not save size.', false);
    }).finally(function () {
        if (btn) {
            btn.disabled = false;
            btn.textContent = oldLabel;
        }
    });
});

function setSizeStatus(id, isActive) {
    const form = new FormData();
    form.append('id', id);
    form.append('is_active', isActive);
    postSizeAction('status', form).then(function (res) {
        showSizeAlert(res.message || 'Status updated.', !!res.success);
        if (res.success) setTimeout(function () { window.location.reload(); }, 700);
    }).catch(function () {
        showSizeAlert('Could not update status.', false);
    });
}

function askDeleteSize(id, code) {
    pendingDeleteId = id;
    document.getElementById('sizeConfirmTitle').textContent = 'Delete size';
    document.getElementById('sizeConfirmMessage').textContent = 'Delete size "' + (code || '') + '"? Existing inbound records keep the saved size text.';
    document.getElementById('sizeConfirmModal').classList.remove('hidden');
    document.getElementById('sizeConfirmModal').classList.add('flex');
}

function closeConfirmModal() {
    pendingDeleteId = 0;
    document.getElementById('sizeConfirmModal').classList.add('hidden');
    document.getElementById('sizeConfirmModal').classList.remove('flex');
}

document.getElementById('sizeConfirmCancel')?.addEventListener('click', closeConfirmModal);
document.getElementById('sizeConfirmOk')?.addEventListener('click', function () {
    const id = pendingDeleteId;
    closeConfirmModal();
    if (!id) return;
    const form = new FormData();
    form.append('id', id);
    postSizeAction('delete', form).then(function (res) {
        showSizeAlert(res.message || 'Delete complete.', !!res.success);
        if (res.success) setTimeout(function () { window.location.reload(); }, 700);
    }).catch(function () {
        showSizeAlert('Could not delete size.', false);
    });
});
</script>
