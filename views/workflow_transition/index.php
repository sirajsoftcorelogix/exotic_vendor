<?php
function workflow_transition_nav_query($search, $status_filter, $from_filter, $to_filter, array $extra = [])
{
    $params = array_merge([
        'page' => 'workflow_transition',
        'action' => 'list',
    ], $extra);
    if (($search ?? '') !== '') {
        $params['search_text'] = $search;
    }
    if (($status_filter ?? '') !== '') {
        $params['status_filter'] = $status_filter;
    }
    if ((int) ($from_filter ?? 0) > 0) {
        $params['from_filter'] = (int) $from_filter;
    }
    if ((int) ($to_filter ?? 0) > 0) {
        $params['to_filter'] = (int) $to_filter;
    }

    return http_build_query($params);
}

$page = isset($page_no) ? (int) $page_no : 1;
$page = $page < 1 ? 1 : $page;
$limit = isset($limit) ? (int) $limit : 20;
$limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;
$total_records = isset($totalRecords) ? (int) $totalRecords : 0;
$total_pages = $limit > 0 ? (int) ceil($total_records / $limit) : 1;
$slot_size = 10;
$start = max(1, $page - (int) floor($slot_size / 2));
$end = min($total_pages, $start + $slot_size - 1);
if ($end - $start < $slot_size - 1) {
    $start = max(1, $end - $slot_size + 1);
}
$status_options = $status_options ?? [];
$transition_rows = $transition_rows ?? [];
$enforcement_active = !empty($enforcement_active);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04]">
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-project-diagram text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Orders · Workflow safeguards</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Status <span class="text-amber-800">transitions</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Define allowed order status changes in <code class="text-xs bg-gray-100 px-1 rounded">vp_workflow_transition</code>.
                    When at least one active transition exists, unmapped changes are blocked (Administrators bypass).
                    Configure outgoing transitions per status to lock down accidental stock-affecting moves.
                </p>
                <?php if ($enforcement_active): ?>
                    <p class="mt-2 text-sm font-medium text-emerald-700">Enforcement is active.</p>
                <?php else: ?>
                    <p class="mt-2 text-sm font-medium text-amber-700">No active transitions yet — all status changes are currently allowed.</p>
                <?php endif; ?>
            </div>
            <div class="flex shrink-0 gap-3 flex-wrap">
                <button type="button" id="open-transition-popup-btn"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] transition">
                    <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
                    Add transition
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <form method="get" id="filterForm" class="p-5 border-b border-gray-100">
            <input type="hidden" name="page" value="workflow_transition">
            <input type="hidden" name="action" value="list">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                    <input type="text" name="search_text" value="<?= htmlspecialchars((string) ($search ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Status title or slug">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">From status</label>
                    <select name="from_filter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All</option>
                        <?php foreach ($status_options as $st): ?>
                            <option value="<?= (int) $st['id'] ?>" <?= ((int) ($from_filter ?? 0) === (int) $st['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($st['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">To status</label>
                    <select name="to_filter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All</option>
                        <?php foreach ($status_options as $st): ?>
                            <option value="<?= (int) $st['id'] ?>" <?= ((int) ($to_filter ?? 0) === (int) $st['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($st['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Active</label>
                    <select name="status_filter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option value="1" <?= (($status_filter ?? '') === '1') ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= (($status_filter ?? '') === '0') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex gap-3">
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">Apply</button>
                <a href="?page=workflow_transition&action=list" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">From</th>
                        <th class="px-4 py-3 text-left">To</th>
                        <th class="px-4 py-3 text-left">Active</th>
                        <th class="px-4 py-3 text-left">Created</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($transition_rows === []): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No transitions configured yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transition_rows as $row): ?>
                            <tr class="hover:bg-gray-50/80">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars((string) ($row['from_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars((string) ($row['from_slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars((string) ($row['to_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars((string) ($row['to_slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if (!empty($row['is_active'])): ?>
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Active</span>
                                    <?php else: ?>
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <?= htmlspecialchars(date('d M Y', strtotime((string) ($row['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button type="button" class="text-amber-700 hover:underline mr-3 edit-transition-btn"
                                        data-id="<?= (int) ($row['id'] ?? 0) ?>">Edit</button>
                                    <button type="button" class="text-gray-700 hover:underline mr-3 toggle-transition-btn"
                                        data-id="<?= (int) ($row['id'] ?? 0) ?>"
                                        data-active="<?= !empty($row['is_active']) ? '1' : '0' ?>">
                                        <?= !empty($row['is_active']) ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                    <button type="button" class="text-red-600 hover:underline delete-transition-btn"
                                        data-id="<?= (int) ($row['id'] ?? 0) ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($transition_rows !== []): ?>
            <div class="px-5 py-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-600"><?= (int) $total_records ?> transition(s)</p>
                <div class="flex items-center gap-2">
                    <?php if ($page > 1): ?>
                        <a class="px-3 py-1 rounded border text-sm"
                            href="?<?= htmlspecialchars(workflow_transition_nav_query($search ?? '', $status_filter ?? '', $from_filter ?? 0, $to_filter ?? 0, ['page_no' => $page - 1, 'limit' => $limit]), ENT_QUOTES, 'UTF-8') ?>">Previous</a>
                    <?php endif; ?>
                    <span class="text-sm text-gray-600">Page <?= $page ?> of <?= max(1, $total_pages) ?></span>
                    <?php if ($page < $total_pages): ?>
                        <a class="px-3 py-1 rounded border text-sm"
                            href="?<?= htmlspecialchars(workflow_transition_nav_query($search ?? '', $status_filter ?? '', $from_filter ?? 0, $to_filter ?? 0, ['page_no' => $page + 1, 'limit' => $limit]), ENT_QUOTES, 'UTF-8') ?>">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="transitionModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 relative">
        <button type="button" id="close-transition-modal" class="absolute top-3 right-3 text-gray-500 hover:text-gray-800">✕</button>
        <h2 id="transitionModalTitle" class="text-xl font-bold mb-4">Add transition</h2>
        <div id="transitionFormMsg" class="text-sm mb-3"></div>
        <form id="transitionForm">
            <input type="hidden" name="id" id="transitionId" value="0">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From status</label>
                    <select name="from_status_id" id="fromStatusId" required class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">Select status</option>
                        <?php foreach ($status_options as $st): ?>
                            <option value="<?= (int) $st['id'] ?>"><?= htmlspecialchars((string) ($st['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To status</label>
                    <select name="to_status_id" id="toStatusId" required class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">Select status</option>
                        <?php foreach ($status_options as $st): ?>
                            <option value="<?= (int) $st['id'] ?>"><?= htmlspecialchars((string) ($st['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_active" id="transitionActive" value="1" checked>
                        Active
                    </label>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" id="cancel-transition-modal" class="px-4 py-2 border rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm hover:bg-orange-700">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const modal = document.getElementById('transitionModal');
    const form = document.getElementById('transitionForm');
    const msg = document.getElementById('transitionFormMsg');
    const title = document.getElementById('transitionModalTitle');

    function openModal(mode) {
        msg.textContent = '';
        title.textContent = mode === 'edit' ? 'Edit transition' : 'Add transition';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('open-transition-popup-btn')?.addEventListener('click', function() {
        form.reset();
        document.getElementById('transitionId').value = '0';
        document.getElementById('transitionActive').checked = true;
        openModal('add');
    });

    document.getElementById('close-transition-modal')?.addEventListener('click', closeModal);
    document.getElementById('cancel-transition-modal')?.addEventListener('click', closeModal);

    form?.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(form);
        if (!document.getElementById('transitionActive').checked) {
            fd.set('is_active', '0');
        }
        fetch('?page=workflow_transition&action=addRecord', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                msg.textContent = data.message || '';
                msg.className = 'text-sm mb-3 ' + (data.success ? 'text-green-700' : 'text-red-600');
                if (data.success) {
                    setTimeout(function() { window.location.reload(); }, 700);
                }
            });
    });

    document.querySelectorAll('.edit-transition-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = btn.getAttribute('data-id');
            fetch('?page=workflow_transition&action=getDetails&id=' + encodeURIComponent(id))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success || !data.record) {
                        alert(data.message || 'Could not load transition.');
                        return;
                    }
                    const rec = data.record;
                    document.getElementById('transitionId').value = rec.id;
                    document.getElementById('fromStatusId').value = rec.from_status_id;
                    document.getElementById('toStatusId').value = rec.to_status_id;
                    document.getElementById('transitionActive').checked = String(rec.is_active) === '1';
                    openModal('edit');
                });
        });
    });

    document.querySelectorAll('.toggle-transition-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = btn.getAttribute('data-id');
            const active = btn.getAttribute('data-active') !== '1';
            fetch('?page=workflow_transition&action=toggleActive', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, is_active: active ? 1 : 0 })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Update failed.');
                }
            });
        });
    });

    document.querySelectorAll('.delete-transition-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Delete this transition?')) {
                return;
            }
            const id = btn.getAttribute('data-id');
            fetch('?page=workflow_transition&action=deleteRecord', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Delete failed.');
                }
            });
        });
    });
})();
</script>
