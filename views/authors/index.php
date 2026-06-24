<?php
$searchValue = htmlspecialchars((string)($search ?? ''), ENT_QUOTES, 'UTF-8');
$statusValue = (string)($status_filter ?? '');
$currentPage = max(1, (int)($currentPage ?? 1));
$totalPages = max(1, (int)($totalPages ?? 1));
$limit = (int)($limit ?? 20);
$totalRecords = (int)($totalRecords ?? 0);
$queryBase = [
    'page' => 'authors',
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
                        <i class="fas fa-pen-nib text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Books · Creator management</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Author <span class="text-amber-800">Listing</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Manage author names for inbounding book metadata. Add, edit, and delete sync with Exotic India.
                </p>
            </div>
            <div class="flex shrink-0 lg:pl-4 lg:self-center gap-3 flex-wrap">
                <?php if (isset($_SESSION['user']['role_id']) && (int)$_SESSION['user']['role_id'] === 1): ?>
                    <button id="syncAuthorsBtn"
                        class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-amber-300 bg-white text-amber-800 text-sm font-semibold shadow-sm hover:bg-amber-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 transition whitespace-nowrap">
                        <i class="fas fa-sync-alt text-xs opacity-95" aria-hidden="true"></i>
                        Sync from admin
                    </button>
                <?php endif; ?>
                <button id="openAuthorModalBtn"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
                    Add Author
                </button>
            </div>
        </div>
    </div>

    <div id="authorAlert" class="hidden rounded-xl border px-4 py-3 text-sm font-medium"></div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <div class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 border-b border-amber-100/80">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                    <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900">Search &amp; filters</h2>
                    <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Find authors by name, remote author id, and active status.</p>
                </div>
            </div>
        </div>
        <form method="get" id="filterForm" class="p-5">
            <input type="hidden" name="page" value="authors">
            <input type="hidden" name="action" value="list">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-5 gap-y-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                    <input type="text" name="search_text" placeholder="Search by author name or id"
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
                    onclick="window.location='?page=authors&action=list';">
                    Reset
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div class="text-sm text-gray-600">
                    Total authors: <span class="font-semibold text-gray-900"><?php echo number_format($totalRecords); ?></span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th class="px-5 py-3.5 whitespace-nowrap">#</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Author ID</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Author Name</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Status</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Updated</th>
                        <th class="px-5 py-3.5 whitespace-nowrap text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($authors)): ?>
                        <?php $counter = ($currentPage - 1) * $limit; ?>
                        <?php foreach ($authors as $author): ?>
                            <?php
                            $id = (int)($author['author_id'] ?? 0);
                            $name = (string)($author['author'] ?? '');
                            $active = (int)($author['is_active'] ?? 0) === 1;
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
                                <td class="px-5 py-4 text-sm text-gray-600"><?php echo htmlspecialchars((string)($author['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm text-right whitespace-nowrap">
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        onclick='openAuthorModal(<?php echo json_encode(['author_id' => $id, 'author' => $name, 'is_active' => $active ? 1 : 0], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        Edit
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50"
                                        onclick="setAuthorStatus(<?php echo $id; ?>, <?php echo $active ? 0 : 1; ?>)">
                                        <?php echo $active ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50"
                                        onclick="deleteAuthor(<?php echo $id; ?>)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No authors found.</td>
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

<div id="authorModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b px-6 py-4">
            <h2 id="authorModalTitle" class="text-lg font-semibold text-gray-900">Add Author</h2>
            <button type="button" onclick="closeAuthorModal()" class="text-gray-400 hover:text-gray-700">✕</button>
        </div>
        <form id="authorForm" class="space-y-4 px-6 py-5">
            <input type="hidden" name="author_id" id="author_id">
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Author Name</label>
                <input type="text" name="author" id="author_name" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                <span id="authorNameMsg" class="text-sm text-red-500"></span>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Status</label>
                <select name="is_active" id="author_is_active"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="webpage" id="author_webpage" value="1"
                        class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                    <span>Allow webpage on Exotic India</span>
                </label>
                <p class="mt-1 text-xs text-gray-500">Maps to the <code>webpage</code> parameter (0 or 1) on the vendor API.</p>
            </div>
            <div class="flex justify-end gap-3 border-t pt-4">
                <button type="button" onclick="closeAuthorModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="authorSaveBtn" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Save Author</button>
            </div>
        </form>
    </div>
</div>

<script>
let authorNameExists = false;

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

function showAuthorAlert(message, success) {
    const box = document.getElementById('authorAlert');
    if (!box) return;
    box.textContent = message || '';
    box.classList.remove('hidden', 'border-green-200', 'bg-green-50', 'text-green-700', 'border-red-200', 'bg-red-50', 'text-red-700');
    box.classList.add(success ? 'border-green-200' : 'border-red-200', success ? 'bg-green-50' : 'bg-red-50', success ? 'text-green-700' : 'text-red-700');
}

function openAuthorModal(author) {
    author = author || {};
    authorNameExists = false;
    const authorNameMsg = document.getElementById('authorNameMsg');
    if (authorNameMsg) authorNameMsg.textContent = '';
    document.getElementById('authorModalTitle').textContent = author.author_id ? 'Edit Author' : 'Add Author';
    document.getElementById('author_id').value = author.author_id || '';
    document.getElementById('author_name').value = author.author || '';
    document.getElementById('author_is_active').value = author.is_active != null ? String(author.is_active) : '1';
    document.getElementById('author_webpage').checked = author.webpage === 1 || author.webpage === '1';
    document.getElementById('authorModal').classList.remove('hidden');
    document.getElementById('authorModal').classList.add('flex');
    setTimeout(function () { document.getElementById('author_name').focus(); }, 50);
}

function closeAuthorModal() {
    document.getElementById('authorModal').classList.add('hidden');
    document.getElementById('authorModal').classList.remove('flex');
}

function postAuthorAction(action, body) {
    return fetch('index.php?page=authors&action=' + action, {
        method: 'POST',
        credentials: 'same-origin',
        body: body
    }).then(function (res) { return res.json(); });
}

document.getElementById('openAuthorModalBtn')?.addEventListener('click', function () {
    openAuthorModal();
});

bindCreatorNameDuplicateCheck(
    document.getElementById('author_name'),
    document.getElementById('authorNameMsg'),
    'authors',
    function (v) { authorNameExists = v; },
    function () { return document.getElementById('author_id') ? document.getElementById('author_id').value : 0; },
    'Author name already exists'
);

document.getElementById('authorForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    if (authorNameExists) {
        showAuthorAlert('Author name already exists', false);
        return;
    }
    const form = new FormData(this);
    if (!document.getElementById('author_webpage').checked) {
        form.set('webpage', '0');
    }
    const btn = document.getElementById('authorSaveBtn');
    const oldLabel = btn ? btn.textContent : '';
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Saving...';
    }
    postAuthorAction('save', form).then(function (res) {
        showAuthorAlert(res.message || (res.success ? 'Author saved.' : 'Could not save author.'), !!res.success);
        if (res.success) {
            closeAuthorModal();
            setTimeout(function () { window.location.reload(); }, 700);
        }
    }).catch(function () {
        showAuthorAlert('Could not save author.', false);
    }).finally(function () {
        if (btn) {
            btn.disabled = false;
            btn.textContent = oldLabel;
        }
    });
});

function setAuthorStatus(id, isActive) {
    const form = new FormData();
    form.append('author_id', id);
    form.append('is_active', isActive);
    postAuthorAction('status', form).then(function (res) {
        showAuthorAlert(res.message || 'Status updated.', !!res.success);
        if (res.success) setTimeout(function () { window.location.reload(); }, 700);
    }).catch(function () {
        showAuthorAlert('Could not update status.', false);
    });
}

function deleteAuthor(id) {
    if (!confirm('Delete this author on Exotic India and locally? This cannot be undone.')) return;
    const form = new FormData();
    form.append('author_id', id);
    postAuthorAction('delete', form).then(function (res) {
        showAuthorAlert(res.message || 'Delete complete.', !!res.success);
        if (res.success) setTimeout(function () { window.location.reload(); }, 700);
    }).catch(function () {
        showAuthorAlert('Could not delete author.', false);
    });
}

document.getElementById('syncAuthorsBtn')?.addEventListener('click', function () {
    if (!confirm('Sync authors from Admin now? Existing author names will be updated by author ID.')) return;
    const btn = this;
    const oldHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Syncing...';
    postAuthorAction('syncFromAdmin', new FormData()).then(function (res) {
        const msg = res.success
            ? ((res.message || 'Author sync completed.') + ' Imported/updated: ' + (res.imported || 0) + '. Skipped: ' + (res.skipped || 0) + '.')
            : (res.message || 'Author sync failed.');
        showAuthorAlert(msg, !!res.success);
        if (res.success) setTimeout(function () { window.location.reload(); }, 1200);
    }).catch(function () {
        showAuthorAlert('Author sync failed.', false);
    }).finally(function () {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
    });
});
</script>
