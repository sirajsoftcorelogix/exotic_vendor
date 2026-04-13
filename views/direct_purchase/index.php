<?php
require_once dirname(dirname(__DIR__)) . '/helpers/direct_purchase_currency.php';

/** @var array $data */
$filters = $data['filters'] ?? [];
$pageNo = (int) ($data['page_no'] ?? 1);
$totalPages = (int) ($data['total_pages'] ?? 1);
$limit = (int) ($data['limit'] ?? 20);
$totalRecords = (int) ($data['total_records'] ?? 0);
$purchases = $data['purchases'] ?? [];

$filtersPanelOpen =
    trim((string) ($filters['search_text'] ?? '')) !== ''
    || trim((string) ($filters['invoice_date_from'] ?? '')) !== ''
    || trim((string) ($filters['invoice_date_to'] ?? '')) !== ''
    || (isset($filters['vendor_id']) && (int) $filters['vendor_id'] > 0);

$flash = $_SESSION['direct_purchase_flash'] ?? null;
if ($flash) {
    unset($_SESSION['direct_purchase_flash']);
}

$queryString = '';
if (!empty($_GET)) {
    $params = $_GET;
    unset($params['page_no'], $params['limit']);
    if ($params !== []) {
        $queryString = '&' . http_build_query($params);
    }
}
$pgBase = '?page=direct_purchase&action=list&limit=' . $limit . $queryString;
$dpFilterDateMax = (new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d');
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <!-- Page header (matches stock transfer history) -->
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04] mb-6">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-16 h-48 w-48 rounded-full bg-sky-200/15 blur-2xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-file-invoice-dollar text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Purchasing · Direct purchase</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Direct purchase
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Record vendor invoices without a PO, attach files, and capture GST line items—aligned with your stock transfer workspace styling.
                </p>
            </div>
            <div class="flex shrink-0 lg:pl-4 lg:self-center">
                <a href="?page=direct_purchase&action=add"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 focus-visible:ring-offset-amber-50/50 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
                    Add direct purchase
                </a>
            </div>
        </div>
    </div>

    <?php if (is_array($flash) && trim((string) ($flash['text'] ?? '')) !== ''): ?>
        <?php $flashType = ($flash['type'] ?? '') === 'success' ? 'success' : 'error';
        $flashRing = $flashType === 'success'
            ? 'border-emerald-200/80 bg-emerald-50/90 text-emerald-900'
            : 'border-red-200/80 bg-red-50/90 text-red-900';
        ?>
        <div class="mb-6 rounded-xl border px-4 py-3 text-sm font-medium shadow-sm <?= $flashRing ?>" role="status">
            <?= htmlspecialchars((string) $flash['text']) ?>
        </div>
    <?php endif; ?>

    <style>
        #dp-purchase-filters > summary { list-style: none; }
        #dp-purchase-filters > summary::-webkit-details-marker { display: none; }
        #dp-purchase-filters[open] > summary { border-bottom: 1px solid rgba(251, 243, 219, 0.85); }
        #dp-purchase-filters:not([open]) .dpf-label-open { display: none; }
        #dp-purchase-filters[open] .dpf-label-closed { display: none; }
        #dp-purchase-filters[open] .dpf-chevron { transform: rotate(180deg); }
    </style>

    <details id="dp-purchase-filters" class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-6 ring-1 ring-gray-900/[0.03]" <?= $filtersPanelOpen ? 'open' : '' ?>>
        <summary class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 flex items-center justify-between gap-4 cursor-pointer">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                    <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900">Search &amp; filters</h2>
                    <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Keyword, vendor, invoice dates, and page size.</p>
                </div>
            </div>
            <span class="shrink-0 inline-flex items-center gap-2 text-xs font-semibold text-amber-800">
                <span class="dpf-label-closed">Show</span>
                <span class="dpf-label-open">Hide</span>
                <i class="dpf-chevron fas fa-chevron-down text-[10px] transition-transform duration-200" aria-hidden="true"></i>
            </span>
        </summary>
        <form method="GET" action="" class="p-5">
            <input type="hidden" name="page" value="direct_purchase">
            <input type="hidden" name="action" value="list">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-5 gap-y-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Keyword</label>
                    <input type="text" name="search_text" value="<?= htmlspecialchars($filters['search_text'] ?? '') ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition"
                        placeholder="Invoice no., vendor, SKU…"
                        autocomplete="off">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Vendor</label>
                    <select name="vendor_id" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="">All vendors</option>
                        <?php foreach ($data['vendors'] ?? [] as $v): ?>
                            <option value="<?= (int) $v['id'] ?>" <?= (!empty($filters['vendor_id']) && (int) $filters['vendor_id'] === (int) $v['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['vendor_name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Rows per page</label>
                    <select name="limit" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <?php foreach ([10, 20, 50, 100] as $l): ?>
                            <option value="<?= $l ?>" <?= $limit === $l ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Invoice from</label>
                    <input type="date" name="invoice_date_from" value="<?= htmlspecialchars($filters['invoice_date_from'] ?? '') ?>"
                        max="<?= htmlspecialchars($dpFilterDateMax) ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Invoice to</label>
                    <input type="date" name="invoice_date_to" value="<?= htmlspecialchars($filters['invoice_date_to'] ?? '') ?>"
                        max="<?= htmlspecialchars($dpFilterDateMax) ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                </div>
            </div>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition shadow-sm">
                    <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
                    Apply filters
                </button>
                <a href="?page=direct_purchase&action=list" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                    Reset
                </a>
            </div>
        </form>
    </details>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">#</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Invoice</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Date</th>
                        <th scope="col" class="px-5 py-3.5 min-w-[10rem]">Vendor</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap text-right">Grand total</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap text-center">File</th>
                        <th scope="col" class="w-0 px-2 py-3.5 text-center"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($purchases)): ?>
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 text-xl mb-4">
                                        <i class="fas fa-inbox" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-base font-medium text-gray-900">No purchases match</p>
                                    <p class="mt-1 text-sm text-gray-500">Try adjusting filters or add a direct purchase.</p>
                                    <a href="?page=direct_purchase&action=add" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-amber-700 hover:text-amber-800">
                                        <i class="fas fa-plus-circle" aria-hidden="true"></i>
                                        New purchase
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php
                        $rowNum = ($pageNo - 1) * $limit;
                        foreach ($purchases as $p):
                            $rowNum++;
                            ?>
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="px-5 py-4 align-top text-sm text-gray-700 tabular-nums"><?= $rowNum ?></td>
                                <td class="px-5 py-4 align-top">
                                    <span class="font-mono text-sm font-semibold text-gray-900"><?= htmlspecialchars($p['invoice_number'] ?? '') ?></span>
                                </td>
                                <td class="px-5 py-4 align-top text-sm text-gray-700 whitespace-nowrap">
                                    <?= !empty($p['invoice_date']) ? htmlspecialchars(date('j M Y', strtotime($p['invoice_date']))) : '—'; ?>
                                </td>
                                <td class="px-5 py-4 align-top text-sm text-gray-800"><?= htmlspecialchars($p['vendor_name'] ?? '') ?></td>
                                <td class="px-5 py-4 align-top text-sm text-right font-medium text-gray-900 tabular-nums">
                                    <?php
                                    $cur = strtoupper(trim((string) ($p['currency'] ?? 'INR')));
                                    if ($cur === '') {
                                        $cur = 'INR';
                                    }
                                    $curSym = dp_currency_symbol($cur);
                                    $curDec = dp_currency_decimals($cur);
                                    ?>
                                    <span class="inline-flex items-baseline justify-end gap-1.5 flex-wrap">
                                        <span class="text-gray-700" title="<?= htmlspecialchars($cur) ?>"><?= htmlspecialchars($curSym) ?></span>
                                        <span><?= number_format((float) ($p['grand_total'] ?? 0), $curDec) ?></span>
                                        <span class="text-gray-500 font-normal text-xs"><?= htmlspecialchars($cur) ?></span>
                                    </span>
                                </td>
                                <td class="px-5 py-4 align-top text-center text-sm">
                                    <?php if (!empty($p['invoice_file'])): ?>
                                        <a href="<?= htmlspecialchars($p['invoice_file']) ?>" target="_blank" rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 text-xs font-medium text-amber-800/90 hover:text-amber-950 hover:underline underline-offset-2 decoration-amber-800/30">
                                            <i class="fas fa-paperclip text-[10px]" aria-hidden="true"></i>
                                            View
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="w-0 px-2 py-4 align-middle text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="?page=direct_purchase&action=edit&id=<?= (int) $p['id'] ?>"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded border border-gray-200 bg-white text-blue-600 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1"
                                            title="Edit purchase"
                                            aria-label="Edit purchase">
                                            <i class="fas fa-edit text-xs" aria-hidden="true"></i>
                                        </a>
                                        <a href="?page=direct_purchase&action=delete&id=<?= (int) $p['id'] ?>"
                                            onclick="return confirm('Delete this purchase and all line items?');"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded border border-red-200 bg-white text-red-600 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-1"
                                            title="Delete purchase"
                                            aria-label="Delete purchase">
                                            <i class="fas fa-trash-alt text-xs" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border border-gray-200 bg-white px-4 py-4 shadow-sm">
            <p class="text-sm text-gray-600">
                Showing <span class="font-medium text-gray-900 tabular-nums"><?= ($pageNo - 1) * $limit + 1 ?></span>
                –
                <span class="font-medium text-gray-900 tabular-nums"><?= min($pageNo * $limit, $totalRecords) ?></span>
                of <span class="font-medium text-gray-900 tabular-nums"><?= number_format($totalRecords) ?></span>
            </p>
            <nav class="flex flex-wrap items-center gap-2" aria-label="Pagination">
                <a href="<?= htmlspecialchars($pgBase . '&page_no=1') ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?= $pageNo <= 1 ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' ?>">First</a>
                <a href="<?= htmlspecialchars($pgBase . '&page_no=' . max(1, $pageNo - 1)) ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?= $pageNo <= 1 ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' ?>">Previous</a>
                <span class="px-3 py-1.5 text-sm text-gray-700 tabular-nums">Page <?= $pageNo ?> / <?= $totalPages ?></span>
                <a href="<?= htmlspecialchars($pgBase . '&page_no=' . min($totalPages, $pageNo + 1)) ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?= $pageNo >= $totalPages ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' ?>">Next</a>
                <a href="<?= htmlspecialchars($pgBase . '&page_no=' . $totalPages) ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?= $pageNo >= $totalPages ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' ?>">Last</a>
            </nav>
        </div>
    <?php endif; ?>
</div>
