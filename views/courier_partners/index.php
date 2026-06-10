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
$rowCount = is_array($rows) ? count($rows) : 0;
$filtersPanelOpen = trim($search) !== '' || ($statusFilter !== '' && $statusFilter !== null);
$qsParams = $_GET ?? [];
unset($qsParams['page_no']);
$qs = $qsParams ? ('&' . http_build_query($qsParams)) : '';
$pgBase = '?page=courier_partners&action=list' . $qs;
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04] mb-6">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-16 h-48 w-48 rounded-full bg-sky-200/15 blur-2xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-truck text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Courier · Partner master</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">Courier partners</h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Maintain carriers (DHL, FedEx, Blue Dart, etc.) before mapping API accounts and credentials.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row shrink-0 lg:pl-4 lg:self-center gap-2">
                <form method="post" action="?page=courier_partners&amp;action=syncShippers" class="inline">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition whitespace-nowrap">
                        <i class="fas fa-sync-alt text-xs" aria-hidden="true"></i>
                        Sync shippers
                    </button>
                </form>
                <button type="button" id="cpBtnOpenAdd"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap">
                    <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
                    Add partner
                </button>
                <a href="?page=courier_accounts&amp;action=list" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition whitespace-nowrap">
                    <i class="fas fa-id-card-alt text-xs text-amber-600" aria-hidden="true"></i>
                    Courier accounts
                </a>
            </div>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="rounded-xl border px-4 py-3 text-sm mb-6 <?php echo !empty($flash['success']) ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
            <?php echo htmlspecialchars((string)($flash['message'] ?? '')); ?>
        </div>
    <?php endif; ?>

    <style>
        #cp-partner-filters > summary { list-style: none; }
        #cp-partner-filters > summary::-webkit-details-marker { display: none; }
        #cp-partner-filters[open] > summary { border-bottom: 1px solid rgba(251, 243, 219, 0.85); }
        #cp-partner-filters:not([open]) .cpf-label-open { display: none; }
        #cp-partner-filters[open] .cpf-label-closed { display: none; }
        #cp-partner-filters[open] .cpf-chevron { transform: rotate(180deg); }
    </style>

    <details id="cp-partner-filters" class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-6 ring-1 ring-gray-900/[0.03]" <?php echo $filtersPanelOpen ? 'open' : ''; ?>>
        <summary class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 flex items-center justify-between gap-4 cursor-pointer">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                    <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900">Search &amp; filters</h2>
                    <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Filter by partner name, code, or active status.</p>
                </div>
            </div>
            <span class="shrink-0 inline-flex items-center gap-2 text-xs font-semibold text-amber-800">
                <span class="cpf-label-closed">Show</span>
                <span class="cpf-label-open">Hide</span>
                <i class="cpf-chevron fas fa-chevron-down text-[10px] transition-transform duration-200" aria-hidden="true"></i>
            </span>
        </summary>

        <form method="get" action="index.php" class="p-5">
            <input type="hidden" name="page" value="courier_partners">
            <input type="hidden" name="action" value="list">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-5 gap-y-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Keyword</label>
                    <input type="text" name="search_text" value="<?php echo htmlspecialchars($search); ?>" placeholder="Partner name or code"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status_filter" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="">All</option>
                        <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition shadow-sm">
                    <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
                    Apply filters
                </button>
                <a href="?page=courier_partners&action=list" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                    Reset
                </a>
            </div>
        </form>
    </details>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th class="px-5 py-3.5 whitespace-nowrap">Code</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Partner name</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Shipper ID</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Domestic</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">International</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Status</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (!$rows): ?>
                        <tr><td colspan="7" class="px-5 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 text-xl mb-4">
                                    <i class="fas fa-truck" aria-hidden="true"></i>
                                </span>
                                <p class="text-base font-medium text-gray-900">No courier partners found</p>
                                <p class="mt-1 text-sm text-gray-500">Try adjusting filters or add a new partner.</p>
                                <button type="button" class="cp-open-add mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700">Add partner</button>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <?php
                                $shipperId = (int) ($r['shipper_id'] ?? 0);
                                $payload = [
                                    'id' => (int)$r['id'],
                                    'partner_code' => (string)$r['partner_code'],
                                    'partner_name' => (string)$r['partner_name'],
                                    'shipper_id' => $shipperId > 0 ? $shipperId : '',
                                    'supports_domestic' => (int)$r['supports_domestic'],
                                    'supports_international' => (int)$r['supports_international'],
                                    'is_active' => (int)$r['is_active'],
                                    'notes' => (string)($r['notes'] ?? ''),
                                ];
                                /* HEX_* so JSON is safe inside double-quoted HTML attribute for JSON.parse */
                                $payloadJson = json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                            ?>
                            <tr class="odd:bg-white even:bg-gray-50/40 hover:bg-amber-50/50 transition-colors align-top">
                                <td class="px-5 py-4 font-semibold text-gray-800"><?php echo htmlspecialchars((string)$r['partner_code']); ?></td>
                                <td class="px-5 py-4 text-sm text-gray-800"><?php echo htmlspecialchars((string)$r['partner_name']); ?></td>
                                <td class="px-5 py-4 text-sm tabular-nums text-gray-700"><?php echo $shipperId > 0 ? (int) $shipperId : '—'; ?></td>
                                <td class="px-5 py-4">
                                    <?php if ((int)$r['supports_domestic'] === 1): ?>
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 shadow-sm ring-1 ring-inset ring-emerald-600/25">Yes</span>
                                    <?php else: ?>
                                        <span class="text-xs font-medium text-gray-400 tabular-nums">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4">
                                    <?php if ((int)$r['supports_international'] === 1): ?>
                                        <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-900 shadow-sm ring-1 ring-inset ring-sky-600/25">Yes</span>
                                    <?php else: ?>
                                        <span class="text-xs font-medium text-gray-400 tabular-nums">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4">
                                    <?php if ((int)$r['is_active'] === 1): ?>
                                        <span class="inline-flex rounded-full bg-green-100 px-3 py-1.5 text-xs font-semibold text-green-800">Active</span>
                                    <?php else: ?>
                                        <span class="inline-flex rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-600">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4">
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

    <div class="mt-6 rounded-xl border border-gray-200 bg-white px-4 py-4 shadow-sm ring-1 ring-gray-900/[0.03]">
        <p class="text-sm text-gray-600">
            Showing <span class="font-medium text-gray-900 tabular-nums"><?php echo $rowCount; ?></span> partner<?php echo $rowCount === 1 ? '' : 's'; ?> on this page
            (total matching: <span class="font-medium text-gray-900 tabular-nums"><?php echo number_format($totalRecords); ?></span>).
        </p>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border border-gray-200 bg-white px-4 py-4 shadow-sm ring-1 ring-gray-900/[0.03]">
            <p class="text-sm text-gray-600">
                Page <span class="font-medium text-gray-900 tabular-nums"><?php echo $currentPage; ?></span>
                of <span class="font-medium text-gray-900 tabular-nums"><?php echo $totalPages; ?></span>
            </p>
            <nav class="flex flex-wrap items-center gap-2" aria-label="Pagination">
                <a href="<?php echo htmlspecialchars($pgBase . '&page_no=1'); ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?php echo $currentPage <= 1 ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'; ?>">First</a>
                <a href="<?php echo htmlspecialchars($pgBase . '&page_no=' . max(1, $currentPage - 1)); ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?php echo $currentPage <= 1 ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'; ?>">Previous</a>
                <span class="px-3 py-1.5 text-sm text-gray-700 tabular-nums"><?php echo $currentPage; ?> / <?php echo $totalPages; ?></span>
                <a href="<?php echo htmlspecialchars($pgBase . '&page_no=' . min($totalPages, $currentPage + 1)); ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?php echo $currentPage >= $totalPages ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'; ?>">Next</a>
                <a href="<?php echo htmlspecialchars($pgBase . '&page_no=' . $totalPages); ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?php echo $currentPage >= $totalPages ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'; ?>">Last</a>
            </nav>
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
                    <div class="sm:col-span-1">
                        <label for="cp_field_shipper_id" class="block text-xs font-semibold text-gray-600 mb-1.5">Shipper ID</label>
                        <input type="number" name="shipper_id" id="cp_field_shipper_id" min="1" step="1"
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
        document.getElementById('cp_field_shipper_id').value = '';
        syncMarketCardStyles();
    }

    function fillFormEdit(p) {
        document.getElementById('cp_field_id').value = p.id || '';
        document.getElementById('cp_field_code').value = p.partner_code || '';
        document.getElementById('cp_field_name').value = p.partner_name || '';
        document.getElementById('cp_field_shipper_id').value = p.shipper_id ? String(p.shipper_id) : '';
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
