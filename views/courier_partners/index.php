<?php
$flash = $_SESSION['courier_partner_flash'] ?? null;
if ($flash) {
    unset($_SESSION['courier_partner_flash']);
}
$rows = $rows ?? [];
$search = $search ?? '';
$statusFilter = $status_filter ?? '';
$currentPage = (int)($currentPage ?? 1);
$totalPages = (int)($totalPages ?? 1);
$limit = (int)($limit ?? 20);
$totalRecords = (int)($totalRecords ?? 0);
?>

<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-5">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Courier Partner Master</h2>
            <p class="text-sm text-gray-500 mt-1">Create courier partners (DHL, FedEx, Blue Dart, etc.) before mapping accounts.</p>
        </div>
        <button type="button" id="cpBtnOpenAdd"
            class="inline-flex shrink-0 items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-md shadow-amber-900/15 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition">
            <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
            Add partner
        </button>
    </div>

    <?php if ($flash): ?>
        <div class="rounded-lg border px-4 py-3 text-sm <?php echo !empty($flash['success']) ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
            <?php echo htmlspecialchars((string)($flash['message'] ?? '')); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <form method="get" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="page" value="courier_partners">
            <input type="hidden" name="action" value="list">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Search</label>
                <input type="text" name="search_text" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name or code" class="h-10 rounded-lg border border-gray-300 px-3 text-sm w-64">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
                <select name="status_filter" class="h-10 rounded-lg border border-gray-300 px-3 text-sm w-44">
                    <option value="">All</option>
                    <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" class="h-10 px-4 rounded-lg bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold">Search</button>
            <a href="?page=courier_partners&action=list" class="h-10 px-4 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm font-semibold inline-flex items-center">Clear</a>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left">Code</th>
                        <th class="px-4 py-3 text-left">Partner Name</th>
                        <th class="px-4 py-3 text-left">Domestic</th>
                        <th class="px-4 py-3 text-left">International</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="6" class="px-4 py-12 text-center">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 mb-3"><i class="fas fa-truck"></i></span>
                            <p class="text-gray-500">No courier partners found.</p>
                            <button type="button" class="cp-open-add mt-3 text-sm font-semibold text-amber-800 hover:underline">Add your first partner</button>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <?php
                                $payload = [
                                    'id' => (int)$r['id'],
                                    'partner_code' => (string)$r['partner_code'],
                                    'partner_name' => (string)$r['partner_name'],
                                    'supports_domestic' => (int)$r['supports_domestic'],
                                    'supports_international' => (int)$r['supports_international'],
                                    'is_active' => (int)$r['is_active'],
                                    'notes' => (string)($r['notes'] ?? ''),
                                ];
                                /* HEX_* so JSON is safe inside double-quoted HTML attribute for JSON.parse */
                                $payloadJson = json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                            ?>
                            <tr class="border-b border-gray-100 align-top hover:bg-amber-50/30 transition-colors">
                                <td class="px-4 py-3 font-semibold text-gray-800"><?php echo htmlspecialchars((string)$r['partner_code']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars((string)$r['partner_name']); ?></td>
                                <td class="px-4 py-3">
                                    <?php if ((int)$r['supports_domestic'] === 1): ?>
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 shadow-sm ring-1 ring-inset ring-emerald-600/25">Yes</span>
                                    <?php else: ?>
                                        <span class="text-xs font-medium text-gray-400 tabular-nums">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ((int)$r['supports_international'] === 1): ?>
                                        <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-900 shadow-sm ring-1 ring-inset ring-sky-600/25">Yes</span>
                                    <?php else: ?>
                                        <span class="text-xs font-medium text-gray-400 tabular-nums">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3"><?php echo (int)$r['is_active'] === 1 ? 'Active' : 'Inactive'; ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="?page=courier_accounts&amp;action=list&amp;partner_id=<?php echo (int) $r['id']; ?>"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50/80 px-2.5 py-1.5 text-xs font-semibold text-amber-900 hover:bg-amber-100 transition">
                                            <i class="fas fa-id-card-alt text-[10px]" aria-hidden="true"></i>
                                            Accounts
                                        </a>
                                        <button type="button" class="cp-btn-edit inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-800 hover:bg-gray-50 transition"
                                            data-partner="<?php echo $payloadJson; ?>">
                                            <i class="fas fa-pen text-[10px] text-indigo-600" aria-hidden="true"></i>
                                            Edit
                                        </button>
                                        <form method="post" action="?page=courier_partners&action=deleteRecord" class="inline" onsubmit="return confirm('Delete this courier partner?');">
                                            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 transition">
                                                <i class="fas fa-trash-alt text-[10px]" aria-hidden="true"></i>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-sm text-gray-600">Total records: <?php echo (int)$totalRecords; ?></div>
    <?php if ($totalPages > 1): ?>
        <div class="flex items-center gap-2 flex-wrap">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?page=courier_partners&action=list&page_no=<?php echo $p; ?>&limit=<?php echo $limit; ?>&search_text=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($statusFilter); ?>"
                   class="h-8 min-w-8 px-2 rounded border text-sm inline-flex items-center justify-center <?php echo $p === $currentPage ? 'bg-gray-900 text-white border-gray-900' : 'bg-white border-gray-300 text-gray-700'; ?>">
                    <?php echo $p; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Add / Edit Courier Partner -->
<div id="courierPartnerModal" class="fixed inset-0 z-[100] hidden opacity-0 transition-opacity duration-200" aria-hidden="true" role="dialog" aria-labelledby="cpModalTitle">
    <div class="absolute inset-0 bg-gray-900/55 backdrop-blur-[2px] cp-modal-backdrop" aria-hidden="true"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none overflow-y-auto">
        <div class="pointer-events-auto w-full max-w-lg rounded-2xl border border-gray-200/90 bg-white shadow-2xl shadow-gray-900/20 ring-1 ring-black/5 my-8 overflow-hidden transform transition-all scale-95 opacity-0 cp-modal-panel">
            <div class="relative px-6 pt-6 pb-4 border-b border-amber-100 bg-gradient-to-br from-amber-50/90 via-white to-slate-50/40">
                <div class="absolute top-0 right-0 w-40 h-40 rounded-full bg-amber-200/20 blur-3xl pointer-events-none -translate-y-1/2 translate-x-1/3" aria-hidden="true"></div>
                <div class="relative flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-amber-800/80 mb-1">Courier partner</p>
                        <h3 id="cpModalTitle" class="text-xl font-bold text-gray-900 tracking-tight">Add partner</h3>
                        <p id="cpModalSubtitle" class="text-sm text-gray-500 mt-1">Define code, markets served, and status.</p>
                    </div>
                    <button type="button" class="cp-modal-close rounded-xl p-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition -mr-1 -mt-1" aria-label="Close">
                        <i class="fas fa-times text-lg" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <form method="post" action="?page=courier_partners&action=addRecord" id="cpPartnerForm" class="px-6 py-5 space-y-5">
                <input type="hidden" name="id" id="cp_field_id" value="">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-1">
                        <label for="cp_field_code" class="block text-xs font-semibold text-gray-600 mb-1.5">Partner code <span class="text-red-500">*</span></label>
                        <input type="text" name="partner_code" id="cp_field_code" maxlength="50" required autocomplete="off"
                            placeholder="e.g. DHL"
                            class="h-11 w-full rounded-xl border border-gray-300 px-3.5 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/25 transition uppercase">
                    </div>
                    <div class="sm:col-span-1">
                        <label for="cp_field_status" class="block text-xs font-semibold text-gray-600 mb-1.5">Status</label>
                        <select name="is_active" id="cp_field_status" class="h-11 w-full rounded-xl border border-gray-300 px-3.5 text-sm bg-white shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/25 transition">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="cp_field_name" class="block text-xs font-semibold text-gray-600 mb-1.5">Partner name <span class="text-red-500">*</span></label>
                        <input type="text" name="partner_name" id="cp_field_name" maxlength="120" required autocomplete="organization"
                            placeholder="e.g. DHL Express India"
                            class="h-11 w-full rounded-xl border border-gray-300 px-3.5 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/25 transition">
                    </div>
                </div>

                <div>
                    <p class="block text-xs font-semibold text-gray-600 mb-2">Markets</p>
                    <div class="flex flex-wrap gap-3">
                        <label class="cp-market-card inline-flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50/80 px-4 py-3 cursor-pointer hover:border-emerald-300/80 hover:bg-emerald-50/50 transition flex-1 min-w-[140px]">
                            <input type="checkbox" name="supports_domestic" id="cp_field_domestic" value="1" class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm font-medium text-gray-800">Domestic</span>
                        </label>
                        <label class="cp-market-card inline-flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50/80 px-4 py-3 cursor-pointer hover:border-sky-300/80 hover:bg-sky-50/50 transition flex-1 min-w-[140px]">
                            <input type="checkbox" name="supports_international" id="cp_field_intl" value="1" class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                            <span class="text-sm font-medium text-gray-800">International</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="cp_field_notes" class="block text-xs font-semibold text-gray-600 mb-1.5">Notes</label>
                    <textarea name="notes" id="cp_field_notes" rows="3" placeholder="Optional internal notes…"
                        class="w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/25 transition resize-y min-h-[5rem]"></textarea>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 pt-2 border-t border-gray-100">
                    <button type="button" class="cp-modal-close h-11 px-5 rounded-xl border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" id="cpSubmitBtn" class="h-11 px-6 rounded-xl bg-gradient-to-b from-indigo-600 to-indigo-700 text-white text-sm font-semibold shadow-md hover:from-indigo-500 hover:to-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition">
                        Save partner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('courierPartnerModal');
    var panel = modal ? modal.querySelector('.cp-modal-panel') : null;
    var form = document.getElementById('cpPartnerForm');
    var titleEl = document.getElementById('cpModalTitle');
    var subtitleEl = document.getElementById('cpModalSubtitle');
    var submitBtn = document.getElementById('cpSubmitBtn');

    function openModal() {
        if (!modal || !panel) return;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        requestAnimationFrame(function () {
            modal.classList.remove('opacity-0');
            modal.classList.add('opacity-100');
            panel.classList.remove('scale-95', 'opacity-0');
            panel.classList.add('scale-100', 'opacity-100');
        });
        modal.setAttribute('aria-hidden', 'false');
        var first = form.querySelector('#cp_field_code');
        if (first) first.focus();
    }

    function closeModal() {
        if (!modal || !panel) return;
        modal.classList.add('opacity-0');
        modal.classList.remove('opacity-100');
        panel.classList.add('scale-95', 'opacity-0');
        panel.classList.remove('scale-100', 'opacity-100');
        document.body.classList.remove('overflow-hidden');
        setTimeout(function () {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }, 200);
    }

    function syncMarketCardStyles() {
        var d = document.getElementById('cp_field_domestic');
        var i = document.getElementById('cp_field_intl');
        var labels = document.querySelectorAll('.cp-market-card');
        if (labels[0] && d) {
            labels[0].classList.toggle('border-emerald-400', d.checked);
            labels[0].classList.toggle('bg-emerald-50/70', d.checked);
        }
        if (labels[1] && i) {
            labels[1].classList.toggle('border-sky-400', i.checked);
            labels[1].classList.toggle('bg-sky-50/70', i.checked);
        }
    }

    function resetFormAdd() {
        form.reset();
        document.getElementById('cp_field_id').value = '';
        titleEl.textContent = 'Add partner';
        subtitleEl.textContent = 'Define code, markets served, and status.';
        submitBtn.textContent = 'Save partner';
        document.getElementById('cp_field_domestic').checked = true;
        document.getElementById('cp_field_intl').checked = true;
        document.getElementById('cp_field_status').value = '1';
        syncMarketCardStyles();
    }

    function fillFormEdit(p) {
        document.getElementById('cp_field_id').value = p.id || '';
        document.getElementById('cp_field_code').value = p.partner_code || '';
        document.getElementById('cp_field_name').value = p.partner_name || '';
        document.getElementById('cp_field_domestic').checked = !!p.supports_domestic;
        document.getElementById('cp_field_intl').checked = !!p.supports_international;
        document.getElementById('cp_field_status').value = String(p.is_active === 1 ? 1 : 0);
        document.getElementById('cp_field_notes').value = p.notes || '';
        titleEl.textContent = 'Edit partner';
        subtitleEl.textContent = 'Update details for ' + (p.partner_name || p.partner_code || 'partner') + '.';
        submitBtn.textContent = 'Update partner';
        syncMarketCardStyles();
    }

    document.getElementById('cpBtnOpenAdd').addEventListener('click', function () {
        resetFormAdd();
        openModal();
    });

    document.querySelectorAll('.cp-open-add').forEach(function (btn) {
        btn.addEventListener('click', function () {
            resetFormAdd();
            openModal();
        });
    });

    document.querySelectorAll('.cp-btn-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var raw = btn.getAttribute('data-partner');
            if (!raw) return;
            try {
                var p = JSON.parse(raw);
                fillFormEdit(p);
                openModal();
            } catch (e) {}
        });
    });

    modal.querySelectorAll('.cp-modal-close').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });
    modal.querySelector('.cp-modal-backdrop').addEventListener('click', closeModal);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

    document.getElementById('cp_field_domestic').addEventListener('change', syncMarketCardStyles);
    document.getElementById('cp_field_intl').addEventListener('change', syncMarketCardStyles);
})();
</script>
