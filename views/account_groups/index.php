<?php
$searchValue = htmlspecialchars((string)($search ?? ''), ENT_QUOTES, 'UTF-8');
$statusValue = (string)($status_filter ?? '');
$currentPage = max(1, (int)($currentPage ?? 1));
$totalPages = max(1, (int)($totalPages ?? 1));
$limit = (int)($limit ?? 20);
$totalRecords = (int)($totalRecords ?? 0);
$queryBase = [
    'page' => 'account_groups',
    'action' => 'list',
    'search_text' => (string)($search ?? ''),
    'status_filter' => $statusValue,
    'limit' => $limit,
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-layer-group text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Master data · Account groups</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Account Group <span class="text-amber-800">Listing</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Manage account group names used for vendor and product classification. Add, edit, activate, or deactivate groups as needed.
                </p>
            </div>
            <div class="flex shrink-0 lg:pl-4 lg:self-center gap-3 flex-wrap">
                <button id="openAccountGroupModalBtn"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
                    Add Account Group
                </button>
            </div>
        </div>
    </div>

    <div id="accountGroupAlert" class="hidden rounded-xl border px-4 py-3 text-sm font-medium"></div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <div class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 border-b border-amber-100/80">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                    <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900">Search &amp; filters</h2>
                    <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Find account groups by name, id, and active status.</p>
                </div>
            </div>
        </div>
        <form method="get" id="filterForm" class="p-5">
            <input type="hidden" name="page" value="account_groups">
            <input type="hidden" name="action" value="list">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-5 gap-y-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                    <input type="text" name="search_text" placeholder="Search by account group name or id"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition"
                        value="<?php echo $searchValue; ?>" autocomplete="off">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status_filter" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="">All Status</option>
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
                    onclick="window.location='?page=account_groups&action=list';">
                    Reset
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div class="text-sm text-gray-600">
                    Total account groups: <span class="font-semibold text-gray-900"><?php echo number_format($totalRecords); ?></span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th class="px-5 py-3.5 whitespace-nowrap">#</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">ID</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Account Group Name</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Status</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Updated</th>
                        <th class="px-5 py-3.5 whitespace-nowrap text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($account_groups)): ?>
                        <?php $counter = ($currentPage - 1) * $limit; ?>
                        <?php foreach ($account_groups as $group): ?>
                            <?php
                            $id = (int)($group['id'] ?? 0);
                            $name = (string)($group['account_group_name'] ?? '');
                            $active = (int)($group['is_active'] ?? 0) === 1;
                            $updatedRaw = (string)($group['updated_at'] ?? '');
                            $updatedDisplay = $updatedRaw !== '' && ($updatedTs = strtotime($updatedRaw))
                                ? date('jS F Y', $updatedTs)
                                : '';
                            ?>
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="px-5 py-4 text-sm text-gray-700"><?php echo ++$counter; ?></td>
                                <td class="px-5 py-4 text-sm font-medium text-gray-800"><?php echo $id; ?></td>
                                <td class="px-5 py-4 text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo $active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'; ?>">
                                        <?php echo $active ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($updatedDisplay, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm text-right whitespace-nowrap">
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        onclick='openAccountGroupModal(<?php echo json_encode(['id' => $id, 'account_group_name' => $name, 'is_active' => $active ? 1 : 0], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        Edit
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50"
                                        onclick="setAccountGroupStatus(<?php echo $id; ?>, <?php echo $active ? 0 : 1; ?>)">
                                        <?php echo $active ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50"
                                        onclick="deleteAccountGroup(<?php echo $id; ?>)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No account groups found.</td>
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

<div id="accountGroupModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b px-6 py-4">
            <h2 id="accountGroupModalTitle" class="text-lg font-semibold text-gray-900">Add Account Group</h2>
            <button type="button" onclick="closeAccountGroupModal()" class="text-gray-400 hover:text-gray-700">✕</button>
        </div>
        <form id="accountGroupForm" class="space-y-4 px-6 py-5">
            <input type="hidden" name="id" id="account_group_id">
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Account Group Name</label>
                <input type="text" name="account_group_name" id="account_group_name" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                <span id="accountGroupNameMsg" class="text-sm text-red-500"></span>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Status</label>
                <select name="is_active" id="account_group_is_active"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="flex justify-end gap-3 border-t pt-4">
                <button type="button" onclick="closeAccountGroupModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="accountGroupSaveBtn" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Save Account Group</button>
            </div>
        </form>
    </div>
</div>

<script>
let accountGroupNameExists = false;

function bindCreatorNameDuplicateCheck(inputEl, msgEl, page, existsFlagSetter, excludeIdGetter, duplicateMessage) {
    if (!inputEl || !msgEl) return;
    inputEl.addEventListener('keyup', function () {
        const value = inputEl.value.trim();
        if (value.length < 2) {
            existsFlagSetter(false);
            msgEl.textContent = '';
            return;
        }
        const excludeId = excludeIdGetter ? excludeIdGetter() : 0;
        let url = 'index.php?page=' + page + '&action=checkName&name=' + encodeURIComponent(value);
        if (excludeId && parseInt(excludeId, 10) > 0) {
            url += '&excludeId=' + encodeURIComponent(String(excludeId));
        }
        fetch(url, { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.exists) {
                    msgEl.textContent = duplicateMessage;
                    existsFlagSetter(true);
                } else {
                    msgEl.textContent = '';
                    existsFlagSetter(false);
                }
            })
            .catch(function (err) { console.error('Duplicate check error:', err); });
    });
}

function showAccountGroupAlert(message, success) {
    const box = document.getElementById('accountGroupAlert');
    if (!box) return;
    box.textContent = message || '';
    box.classList.remove('hidden', 'border-green-200', 'bg-green-50', 'text-green-700', 'border-red-200', 'bg-red-50', 'text-red-700');
    box.classList.add(success ? 'border-green-200' : 'border-red-200', success ? 'bg-green-50' : 'bg-red-50', success ? 'text-green-700' : 'text-red-700');
}

function openAccountGroupModal(group) {
    group = group || {};
    accountGroupNameExists = false;
    const nameMsg = document.getElementById('accountGroupNameMsg');
    if (nameMsg) nameMsg.textContent = '';
    document.getElementById('accountGroupModalTitle').textContent = group.id ? 'Edit Account Group' : 'Add Account Group';
    document.getElementById('account_group_id').value = group.id || '';
    document.getElementById('account_group_name').value = group.account_group_name || '';
    document.getElementById('account_group_is_active').value = group.is_active != null ? String(group.is_active) : '1';
    document.getElementById('accountGroupModal').classList.remove('hidden');
    document.getElementById('accountGroupModal').classList.add('flex');
    setTimeout(function () { document.getElementById('account_group_name').focus(); }, 50);
}

function closeAccountGroupModal() {
    document.getElementById('accountGroupModal').classList.add('hidden');
    document.getElementById('accountGroupModal').classList.remove('flex');
}

function postAccountGroupAction(action, body) {
    return fetch('index.php?page=account_groups&action=' + action, {
        method: 'POST',
        credentials: 'same-origin',
        body: body
    }).then(function (res) { return res.json(); });
}

document.getElementById('openAccountGroupModalBtn')?.addEventListener('click', function () {
    openAccountGroupModal();
});

bindCreatorNameDuplicateCheck(
    document.getElementById('account_group_name'),
    document.getElementById('accountGroupNameMsg'),
    'account_groups',
    function (v) { accountGroupNameExists = v; },
    function () { return document.getElementById('account_group_id') ? document.getElementById('account_group_id').value : 0; },
    'Account group name already exists'
);

document.getElementById('accountGroupForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    if (accountGroupNameExists) {
        showAccountGroupAlert('Account group name already exists', false);
        return;
    }
    const form = new FormData(this);
    const btn = document.getElementById('accountGroupSaveBtn');
    const oldLabel = btn ? btn.textContent : '';
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Saving...';
    }
    postAccountGroupAction('save', form).then(function (res) {
        showAccountGroupAlert(res.message || (res.success ? 'Account group saved.' : 'Could not save account group.'), !!res.success);
        if (res.success) {
            closeAccountGroupModal();
            setTimeout(function () { window.location.reload(); }, 700);
        }
    }).catch(function () {
        showAccountGroupAlert('Could not save account group.', false);
    }).finally(function () {
        if (btn) {
            btn.disabled = false;
            btn.textContent = oldLabel;
        }
    });
});

function setAccountGroupStatus(id, isActive) {
    const form = new FormData();
    form.append('id', id);
    form.append('is_active', isActive);
    postAccountGroupAction('status', form).then(function (res) {
        showAccountGroupAlert(res.message || 'Status updated.', !!res.success);
        if (res.success) setTimeout(function () { window.location.reload(); }, 700);
    }).catch(function () {
        showAccountGroupAlert('Could not update status.', false);
    });
}

function deleteAccountGroup(id) {
    if (!confirm('Delete this account group? This cannot be undone.')) return;
    const form = new FormData();
    form.append('id', id);
    postAccountGroupAction('delete', form).then(function (res) {
        showAccountGroupAlert(res.message || 'Delete complete.', !!res.success);
        if (res.success) setTimeout(function () { window.location.reload(); }, 700);
    }).catch(function () {
        showAccountGroupAlert('Could not delete account group.', false);
    });
}
</script>
