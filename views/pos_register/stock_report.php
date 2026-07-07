<?php
$filtersPanelOpen =
  trim((string)($filters['search'] ?? '')) !== ''
  || (($filters['category'] ?? 'allProducts') !== 'allProducts')
  || (($filters['stock_status'] ?? 'all') !== 'all')
  || (!empty($can_change_warehouse) && (int)($filters['warehouse_id'] ?? 0) > 0);
$rowCount = is_array($rows ?? null) ? count($rows) : 0;
$pageNo = max(1, (int)($page_no ?? ($filters['page_no'] ?? 1)));
$limit = max(1, (int)($limit ?? ($filters['limit'] ?? 200)));
$totalRows = max(0, (int)($total_rows ?? 0));
$totalPages = max(1, (int)($total_pages ?? 1));
$qsParams = $_GET ?? [];
unset($qsParams['page_no']);
$qs = $qsParams ? ('&' . http_build_query($qsParams)) : '';
$pgBase = '?page=pos_register&action=stock-report' . $qs;
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
  <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04] mb-6">
    <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-20 -left-16 h-48 w-48 rounded-full bg-sky-200/15 blur-2xl" aria-hidden="true"></div>
    <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
      <div class="min-w-0 max-w-3xl">
        <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
          <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
            <i class="fas fa-warehouse text-[11px]" aria-hidden="true"></i>
          </span>
          <span>POS Register · Stock report</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">Stock report</h1>
        <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
          Monitor warehouse stock by SKU, category, and status using the same list workspace style as direct purchase.
        </p>
      </div>
      <div class="flex shrink-0 lg:pl-4 lg:self-center gap-2">
        <span class="inline-flex items-center gap-2 px-4 py-3 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700">
          <i class="fas fa-store-alt text-amber-600 text-xs" aria-hidden="true"></i>
          <?= htmlspecialchars($warehouse_name ?? 'No Warehouse') ?>
        </span>
        <a href="?page=pos_register&action=list" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
          Back to POS
        </a>
      </div>
    </div>
  </div>

  <style>
    #pos-stock-filters > summary { list-style: none; }
    #pos-stock-filters > summary::-webkit-details-marker { display: none; }
    #pos-stock-filters[open] > summary { border-bottom: 1px solid rgba(251, 243, 219, 0.85); }
    #pos-stock-filters:not([open]) .psf-label-open { display: none; }
    #pos-stock-filters[open] .psf-label-closed { display: none; }
    #pos-stock-filters[open] .psf-chevron { transform: rotate(180deg); }
  </style>

  <details id="pos-stock-filters" class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-6 ring-1 ring-gray-900/[0.03]" <?= $filtersPanelOpen ? 'open' : '' ?>>
    <summary class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 flex items-center justify-between gap-4 cursor-pointer">
      <div class="flex items-center gap-3 min-w-0">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
          <i class="fas fa-filter text-sm" aria-hidden="true"></i>
        </span>
        <div class="min-w-0">
          <h2 class="text-sm font-semibold text-gray-900">Search &amp; filters</h2>
          <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Warehouse, keyword, category, stock status, and rows limit.</p>
        </div>
      </div>
      <span class="shrink-0 inline-flex items-center gap-2 text-xs font-semibold text-amber-800">
        <span class="psf-label-closed">Show</span>
        <span class="psf-label-open">Hide</span>
        <i class="psf-chevron fas fa-chevron-down text-[10px] transition-transform duration-200" aria-hidden="true"></i>
      </span>
    </summary>

    <form method="get" action="index.php" class="p-5">
      <input type="hidden" name="page" value="pos_register">
      <input type="hidden" name="action" value="stock-report">

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-5 gap-y-4">
        <?php if (!empty($can_change_warehouse)): ?>
          <div>
            <label for="stock_report_warehouse" class="block text-xs font-semibold text-gray-600 mb-1">Warehouse</label>
            <select id="stock_report_warehouse" name="warehouse_id" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
              <?php foreach ($warehouses ?? [] as $wh): ?>
                <?php $wid = (int)($wh['id'] ?? 0); ?>
                <option value="<?= $wid ?>" <?= ((int)($filters['warehouse_id'] ?? 0) === $wid) ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string)($wh['address_title'] ?? ('#' . $wid))) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <div class="<?= !empty($can_change_warehouse) ? 'sm:col-span-2' : 'sm:col-span-2 lg:col-span-2' ?>">
          <label class="block text-xs font-semibold text-gray-600 mb-1">Keyword</label>
          <input
            type="text"
            name="search"
            value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
            placeholder="Item code, SKU, title"
            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Category</label>
          <select name="category" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
            <?php foreach (($categories ?? []) as $slug => $label): ?>
              <option value="<?= htmlspecialchars($slug) ?>" <?= (($filters['category'] ?? 'allProducts') === $slug) ? 'selected' : '' ?>>
                <?= htmlspecialchars($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Stock status</label>
          <select name="stock_status" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
            <option value="all" <?= (($filters['stock_status'] ?? 'all') === 'all') ? 'selected' : '' ?>>All stock</option>
            <option value="out" <?= (($filters['stock_status'] ?? 'all') === 'out') ? 'selected' : '' ?>>Out of stock</option>
            <option value="low" <?= (($filters['stock_status'] ?? 'all') === 'low') ? 'selected' : '' ?>>Low stock (1-5)</option>
            <option value="in" <?= (($filters['stock_status'] ?? 'all') === 'in') ? 'selected' : '' ?>>In stock</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Rows</label>
          <select name="limit" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
            <?php foreach ([50, 100, 200, 500] as $l): ?>
              <option value="<?= $l ?>" <?= ((int)($filters['limit'] ?? 200) === $l) ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="mt-5 flex flex-wrap items-center gap-3">
        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition shadow-sm">
          <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
          Apply filters
        </button>
        <a href="?page=pos_register&action=stock-report" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
          Reset
        </a>
      </div>
    </form>
  </details>

  <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
    <?php if (!empty($rows)): ?>
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 bg-gray-50/80 px-5 py-3">
        <p class="text-sm text-gray-600">
          <span id="stockReportSelectedCount" class="font-semibold text-gray-900 tabular-nums">0</span>
          <span> selected on this page</span>
        </p>
        <button
          type="button"
          id="stockReportBulkRefreshBtn"
          class="inline-flex items-center gap-2 rounded-lg border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-800 hover:bg-orange-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 transition disabled:opacity-50 disabled:pointer-events-none"
          disabled>
          <i class="fas fa-sync-alt text-xs" aria-hidden="true"></i>
          <span>Refresh selected</span>
        </button>
      </div>
    <?php endif; ?>
    <div class="overflow-x-auto">
      <table class="min-w-full text-left">
        <thead class="sticky top-0 z-10">
          <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
            <th class="px-5 py-3.5 whitespace-nowrap w-12">
              <?php if (!empty($rows)): ?>
                <input
                  type="checkbox"
                  id="stockReportSelectAll"
                  class="h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                  aria-label="Select all rows on this page"
                  title="Select all on this page">
              <?php endif; ?>
            </th>
            <th class="px-5 py-3.5 whitespace-nowrap">Image</th>
            <th class="px-5 py-3.5 whitespace-nowrap">SKU</th>
            <th class="px-5 py-3.5 whitespace-nowrap">Category</th>
            <th class="px-5 py-3.5 whitespace-nowrap">Location</th>
            <th class="px-5 py-3.5 whitespace-nowrap">Stock</th>
            <th class="px-5 py-3.5 whitespace-nowrap text-right">Sell price</th>
            <th class="px-5 py-3.5 min-w-[15rem]">Title</th>
            <th class="px-5 py-3.5 whitespace-nowrap text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="9" class="px-5 py-16 text-center">
                <div class="mx-auto flex max-w-sm flex-col items-center">
                  <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 text-xl mb-4">
                    <i class="fas fa-inbox" aria-hidden="true"></i>
                  </span>
                  <p class="text-base font-medium text-gray-900">No stock records found</p>
                  <p class="mt-1 text-sm text-gray-500">Try adjusting filters to broaden results.</p>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <?php
                $qty = (int)($r['stock_qty'] ?? 0);
                $imgUrl = $r['image'] ?: 'https://dummyimage.com/256x256/e5e7eb/6b7280&text=No+Image';
                $categoryKey = (string)($r['groupname'] ?? '');
                $rawCategory = (string)($r['category_display'] ?? $categoryKey);
                $fallbackCategory = ucwords(strtolower(str_replace(['_', '-'], ' ', $rawCategory)));
                $categoryLabel = (string)($categories[$categoryKey] ?? $fallbackCategory);
              ?>
              <?php
                $productId = (int)($r['id'] ?? 0);
                $skuLabel = trim((string)($r['sku'] ?? $r['item_code'] ?? ''));
              ?>
              <tr class="odd:bg-white even:bg-gray-50/40 hover:bg-amber-50/50 transition-colors" data-product-id="<?= $productId ?>">
                <td class="px-5 py-4 align-top">
                  <input
                    type="checkbox"
                    class="stock-report-select-row h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                    value="<?= $productId ?>"
                    data-sku-label="<?= htmlspecialchars($skuLabel, ENT_QUOTES, 'UTF-8') ?>"
                    aria-label="Select <?= htmlspecialchars($skuLabel, ENT_QUOTES, 'UTF-8') ?>">
                </td>
                <td class="px-5 py-4 align-top">
                  <img
                    src="<?= htmlspecialchars($imgUrl) ?>"
                    data-full-img="<?= htmlspecialchars($imgUrl) ?>"
                    class="h-10 w-10 rounded-lg object-cover bg-slate-100 cursor-pointer hover:opacity-90 ring-1 ring-gray-200 transition"
                    alt="Product image"
                    loading="lazy"
                    onclick="openStockReportImage(this)">
                </td>
                <td class="px-5 py-4 align-top text-sm">
                  <a
                    href="<?= htmlspecialchars('?page=products&action=detail&id=' . (int)($r['id'] ?? 0)) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-amber-700 hover:text-amber-800 hover:underline font-medium"
                  >
                    <?= htmlspecialchars((string)($r['sku'] ?? $r['item_code'] ?? '')) ?>
                  </a>
                  
                </td>
                <td class="px-5 py-4 align-top text-sm text-gray-700"><?= htmlspecialchars($categoryLabel) ?></td>
                <td class="px-5 py-4 align-top text-sm text-gray-700">
                  <?php if (trim((string)($r['location'] ?? '')) !== ''): ?>
                    <?= htmlspecialchars((string)($r['location'] ?? '')) ?>
                  <?php else: ?>
                    <span class="inline-flex rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-500">N/A</span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-4 align-top">
                  <?php if ($qty <= 0): ?>
                    <span class="inline-flex rounded-full bg-red-100 px-3 py-1.5 text-xs font-semibold text-red-700">Out (0)</span>
                  <?php elseif ($qty <= 5): ?>
                    <span class="inline-flex rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-700">Low (<?= $qty ?>)</span>
                  <?php else: ?>
                    <span class="inline-flex rounded-full bg-green-100 px-3 py-1.5 text-xs font-semibold text-green-700">In (<?= $qty ?>)</span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-4 align-top text-sm text-right font-semibold text-gray-900 tabular-nums">₹<?= number_format((float)($r['sell_price'] ?? 0), 2) ?></td>
                <td class="px-5 py-4 align-top text-sm text-gray-800 max-w-[15rem] break-words leading-snug"><?= htmlspecialchars($r['title'] ?? '') ?></td>
                <td class="px-5 py-4 align-top text-right">
                  <button
                    type="button"
                    class="stock-report-refresh-btn inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition disabled:opacity-50 disabled:pointer-events-none"
                    data-product-id="<?= $productId ?>"
                    data-sku-label="<?= htmlspecialchars($skuLabel, ENT_QUOTES, 'UTF-8') ?>"
                    title="Clear ledger, reset physical stock, fetch local stock, and reseed default warehouse">
                    <i class="fas fa-sync-alt text-[10px]" aria-hidden="true"></i>
                    <span>Refresh</span>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-6 rounded-xl border border-gray-200 bg-white px-4 py-4 shadow-sm">
    <p class="text-sm text-gray-600">
      Showing <span class="font-medium text-gray-900 tabular-nums"><?= $rowCount ?></span> of
      <span class="font-medium text-gray-900 tabular-nums"><?= number_format($totalRows) ?></span> stock rows
      for <span class="font-medium text-gray-900"><?= htmlspecialchars($warehouse_name ?? 'No Warehouse') ?></span>.
    </p>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border border-gray-200 bg-white px-4 py-4 shadow-sm">
      <p class="text-sm text-gray-600">
        Showing
        <span class="font-medium text-gray-900 tabular-nums"><?= ($pageNo - 1) * $limit + 1 ?></span>
        –
        <span class="font-medium text-gray-900 tabular-nums"><?= min($pageNo * $limit, $totalRows) ?></span>
        of <span class="font-medium text-gray-900 tabular-nums"><?= number_format($totalRows) ?></span>
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

<!-- Bulk refresh progress modal -->
<div
  id="stockReportBulkProgressModal"
  class="fixed inset-0 hidden z-[60] items-center justify-center bg-black/50 p-4"
  role="dialog"
  aria-modal="true"
  aria-labelledby="stockReportBulkProgressTitle">
  <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl">
    <div class="flex items-start gap-3">
      <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-700">
        <i id="stockReportBulkProgressIcon" class="fas fa-sync-alt fa-spin text-sm" aria-hidden="true"></i>
      </span>
      <div class="min-w-0 flex-1">
        <h3 id="stockReportBulkProgressTitle" class="text-base font-semibold text-gray-900">Refreshing selected stock</h3>
        <p id="stockReportBulkProgressText" class="mt-1 text-sm text-gray-600">Preparing…</p>
      </div>
    </div>
    <div class="mt-5">
      <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
        <div id="stockReportBulkProgressBar" class="h-full rounded-full bg-orange-500 transition-all duration-300" style="width: 0%"></div>
      </div>
      <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
        <span id="stockReportBulkProgressBatch">Batch 0 of 0</span>
        <span id="stockReportBulkProgressStats">Succeeded: 0 · Failed: 0</span>
      </div>
      <p id="stockReportBulkProgressHint" class="mt-3 text-xs text-gray-500">
        Processing in small batches to avoid timeouts. Please keep this page open.
      </p>
    </div>
  </div>
</div>

<!-- Image Expand Modal -->
<div
  id="stockReportImgModal"
  class="fixed inset-0 hidden z-50 items-center justify-center bg-black/60 p-4"
  role="dialog"
  aria-modal="true">
  <div class="relative bg-white rounded-xl shadow-2xl max-w-[95vw]">
    <button
      type="button"
      class="absolute -top-3 -right-3 bg-white rounded-full border border-slate-200 w-9 h-9 flex items-center justify-center text-slate-700 hover:bg-slate-50"
      onclick="closeStockReportImage()"
      aria-label="Close">
      <span class="text-xl leading-none">&times;</span>
    </button>
    <img
      id="stockReportImgModalImg"
      src=""
      alt="Expanded product image"
      class="max-h-[80vh] max-w-[95vw] object-contain rounded-xl">
  </div>
</div>

<script>
  const STOCK_REPORT_REFRESH_CONFIRM =
    'This will delete vp_stock_movements and vp_stock rows, reset physical_stock to 0, '
    + 'fetch the latest local stock from the API, then reseed opening stock in the default warehouse.';
  const STOCK_REPORT_BATCH_SIZE = 5;

  function chunkStockReportIds(ids, size) {
    const chunks = [];
    for (let i = 0; i < ids.length; i += size) {
      chunks.push(ids.slice(i, i + size));
    }
    return chunks;
  }

  function setStockReportBulkUiLocked(locked) {
    const bulkBtn = document.getElementById('stockReportBulkRefreshBtn');
    const selectAll = document.getElementById('stockReportSelectAll');
    document.querySelectorAll('.stock-report-select-row').forEach((box) => {
      box.disabled = locked;
    });
    document.querySelectorAll('.stock-report-refresh-btn').forEach((btn) => {
      btn.disabled = locked;
    });
    if (selectAll) selectAll.disabled = locked;
    if (bulkBtn) bulkBtn.disabled = locked || getSelectedStockReportRows().length === 0;
  }

  function showStockReportBulkProgressModal() {
    const modal = document.getElementById('stockReportBulkProgressModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }

  function hideStockReportBulkProgressModal() {
    const modal = document.getElementById('stockReportBulkProgressModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  function updateStockReportBulkProgress(state) {
    const total = Math.max(0, Number(state.total || 0));
    const completed = Math.max(0, Number(state.completed || 0));
    const succeeded = Math.max(0, Number(state.succeeded || 0));
    const failed = Math.max(0, Number(state.failed || 0));
    const batchNo = Math.max(0, Number(state.batchNo || 0));
    const batchTotal = Math.max(0, Number(state.batchTotal || 0));
    const percent = total > 0 ? Math.min(100, Math.round((completed / total) * 100)) : 0;

    const textEl = document.getElementById('stockReportBulkProgressText');
    const barEl = document.getElementById('stockReportBulkProgressBar');
    const batchEl = document.getElementById('stockReportBulkProgressBatch');
    const statsEl = document.getElementById('stockReportBulkProgressStats');
    const hintEl = document.getElementById('stockReportBulkProgressHint');
    const iconEl = document.getElementById('stockReportBulkProgressIcon');

    if (textEl) {
      textEl.textContent = 'Processed ' + completed + ' of ' + total + ' selected item(s)';
    }
    if (barEl) barEl.style.width = percent + '%';
    if (batchEl) {
      batchEl.textContent = batchTotal > 0
        ? ('Batch ' + batchNo + ' of ' + batchTotal + ' · ' + STOCK_REPORT_BATCH_SIZE + ' items per batch')
        : 'Preparing batches…';
    }
    if (statsEl) statsEl.textContent = 'Succeeded: ' + succeeded + ' · Failed: ' + failed;
    if (hintEl && state.hint) hintEl.textContent = state.hint;
    if (iconEl) {
      iconEl.classList.toggle('fa-spin', !!state.spinning);
      iconEl.classList.toggle('fa-check', !state.spinning && state.done);
      iconEl.classList.toggle('fa-sync-alt', !!state.spinning || !state.done);
    }
  }

  async function fetchStockReportBulkBatch(productIds) {
    const res = await fetch('index.php?page=pos_register&action=stock-report-refresh-bulk', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ product_ids: productIds }),
    });

    let data = null;
    const rawText = await res.text();
    try {
      data = rawText ? JSON.parse(rawText) : null;
    } catch (parseErr) {
      data = null;
    }

    if (!res.ok) {
      throw new Error(
        (data && data.message)
          ? data.message
          : ('Batch request failed with HTTP ' + res.status + (rawText ? ': ' + rawText.slice(0, 180) : ''))
      );
    }
    if (!data) {
      throw new Error('Invalid response from batch refresh.');
    }
    if (!Array.isArray(data.results)) {
      throw new Error(data.message || 'Batch refresh failed.');
    }

    return data;
  }

  async function runStockReportBulkRefreshBatched(productIds) {
    const total = productIds.length;
    const batches = chunkStockReportIds(productIds, STOCK_REPORT_BATCH_SIZE);
    const batchTotal = batches.length;
    let completed = 0;
    let succeeded = 0;
    let failed = 0;
    const allResults = [];

    showStockReportBulkProgressModal();
    setStockReportBulkUiLocked(true);
    updateStockReportBulkProgress({
      total,
      completed,
      succeeded,
      failed,
      batchNo: 0,
      batchTotal,
      spinning: true,
      done: false,
      hint: 'Processing in small batches to avoid timeouts. Please keep this page open.',
    });

    for (let i = 0; i < batches.length; i++) {
      const batchIds = batches[i];
      updateStockReportBulkProgress({
        total,
        completed,
        succeeded,
        failed,
        batchNo: i + 1,
        batchTotal,
        spinning: true,
        done: false,
        hint: 'Running batch ' + (i + 1) + ' of ' + batchTotal + ' (' + batchIds.length + ' item(s))…',
      });

      const data = await fetchStockReportBulkBatch(batchIds);
      const batchResults = Array.isArray(data.results) ? data.results : [];
      allResults.push.apply(allResults, batchResults);

      batchResults.forEach((row) => {
        if (row && row.success) {
          succeeded++;
        } else {
          failed++;
        }
      });
      completed += batchIds.length;

      updateStockReportBulkProgress({
        total,
        completed,
        succeeded,
        failed,
        batchNo: i + 1,
        batchTotal,
        spinning: i < batches.length - 1,
        done: i === batches.length - 1,
        hint: i < batches.length - 1
          ? 'Batch ' + (i + 1) + ' complete. Starting next batch…'
          : 'All batches complete.',
      });
    }

    return {
      total,
      succeeded,
      failed,
      results: allResults,
      message: 'Refreshed ' + succeeded + ' of ' + total + ' selected item(s).'
        + (failed > 0 ? (' ' + failed + ' failed.') : ''),
    };
  }

  function getSelectedStockReportRows() {
    return Array.from(document.querySelectorAll('.stock-report-select-row:checked'));
  }

  function updateStockReportSelectionUi() {
    const selected = getSelectedStockReportRows();
    const count = selected.length;
    const countEl = document.getElementById('stockReportSelectedCount');
    const bulkBtn = document.getElementById('stockReportBulkRefreshBtn');
    const selectAll = document.getElementById('stockReportSelectAll');
    const rowBoxes = document.querySelectorAll('.stock-report-select-row');

    if (countEl) countEl.textContent = String(count);
    if (bulkBtn) bulkBtn.disabled = count === 0;

    if (selectAll && rowBoxes.length > 0) {
      selectAll.checked = count > 0 && count === rowBoxes.length;
      selectAll.indeterminate = count > 0 && count < rowBoxes.length;
    }
  }

  async function refreshStockReportProduct(productId, skuLabel) {
    const confirmed = window.confirm(
      'Refresh stock for ' + skuLabel + '?\n\n' + STOCK_REPORT_REFRESH_CONFIRM
    );
    if (!confirmed) return { cancelled: true };

    const res = await fetch('index.php?page=pos_register&action=stock-report-refresh', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ product_id: productId }),
    });
    const data = await res.json();
    if (!data || !data.success) {
      throw new Error((data && data.message) ? data.message : 'Refresh failed.');
    }
    return data;
  }

  function openStockReportImage(imgEl) {
    const modal = document.getElementById('stockReportImgModal');
    const modalImg = document.getElementById('stockReportImgModalImg');
    const full = imgEl.getAttribute('data-full-img') || imgEl.src;
    modalImg.src = full;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }

  function closeStockReportImage() {
    const modal = document.getElementById('stockReportImgModal');
    const modalImg = document.getElementById('stockReportImgModalImg');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modalImg.src = '';
  }

  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('stockReportImgModal');
    if (modal) {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) closeStockReportImage();
      });
    }

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeStockReportImage();
    });

    const selectAll = document.getElementById('stockReportSelectAll');
    if (selectAll) {
      selectAll.addEventListener('change', () => {
        const checked = selectAll.checked;
        document.querySelectorAll('.stock-report-select-row').forEach((box) => {
          box.checked = checked;
        });
        updateStockReportSelectionUi();
      });
    }

    document.querySelectorAll('.stock-report-select-row').forEach((box) => {
      box.addEventListener('change', updateStockReportSelectionUi);
    });

    const bulkBtn = document.getElementById('stockReportBulkRefreshBtn');
    if (bulkBtn) {
      bulkBtn.addEventListener('click', async () => {
        const selected = getSelectedStockReportRows();
        if (selected.length === 0) return;

        const labels = selected
          .map((box) => box.getAttribute('data-sku-label') || box.value)
          .slice(0, 5);
        let preview = labels.join(', ');
        if (selected.length > 5) {
          preview += ' … +' + (selected.length - 5) + ' more';
        }

        const confirmed = window.confirm(
          'Refresh stock for ' + selected.length + ' selected item(s)?\n\n'
          + preview + '\n\n' + STOCK_REPORT_REFRESH_CONFIRM
        );
        if (!confirmed) return;

        const productIds = selected.map((box) => parseInt(box.value || '0', 10)).filter((id) => id > 0);
        const btnLabel = bulkBtn.querySelector('span');
        const btnIcon = bulkBtn.querySelector('i');
        bulkBtn.disabled = true;
        if (btnIcon) btnIcon.classList.add('fa-spin');
        if (btnLabel) btnLabel.textContent = 'Refreshing…';

        try {
          const summary = await runStockReportBulkRefreshBatched(productIds);

          updateStockReportBulkProgress({
            total: summary.total,
            completed: summary.total,
            succeeded: summary.succeeded,
            failed: summary.failed,
            batchNo: Math.ceil(summary.total / STOCK_REPORT_BATCH_SIZE),
            batchTotal: Math.ceil(summary.total / STOCK_REPORT_BATCH_SIZE),
            spinning: false,
            done: true,
            hint: summary.failed > 0 ? 'Completed with some failures. Review details below.' : 'Completed successfully. Reloading…',
          });

          if (summary.failed > 0 && Array.isArray(summary.results)) {
            const failedLines = summary.results
              .filter((row) => row && !row.success)
              .slice(0, 8)
              .map((row) => (row.label || row.sku || ('#' + row.product_id)) + ': ' + (row.message || 'Failed'))
              .join('\n');
            const extra = summary.failed > 8 ? '\n…and ' + (summary.failed - 8) + ' more.' : '';
            window.alert((summary.message || 'Bulk refresh completed with errors.') + '\n\n' + failedLines + extra);
          }

          window.setTimeout(() => window.location.reload(), summary.failed > 0 ? 1200 : 600);
        } catch (err) {
          hideStockReportBulkProgressModal();
          window.alert(err && err.message ? err.message : 'Bulk refresh failed.');
          setStockReportBulkUiLocked(false);
          bulkBtn.disabled = false;
          if (btnIcon) btnIcon.classList.remove('fa-spin');
          if (btnLabel) btnLabel.textContent = 'Refresh selected';
          updateStockReportSelectionUi();
        }
      });
    }

    updateStockReportSelectionUi();

    document.querySelectorAll('.stock-report-refresh-btn').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const productId = parseInt(btn.getAttribute('data-product-id') || '0', 10);
        const skuLabel = btn.getAttribute('data-sku-label') || ('#' + productId);
        if (productId <= 0) return;

        const icon = btn.querySelector('i');
        const label = btn.querySelector('span');
        btn.disabled = true;
        if (icon) icon.classList.add('fa-spin');
        if (label) label.textContent = 'Refreshing…';

        try {
          const result = await refreshStockReportProduct(productId, skuLabel);
          if (result && result.cancelled) {
            btn.disabled = false;
            if (icon) icon.classList.remove('fa-spin');
            if (label) label.textContent = 'Refresh';
            return;
          }
          window.location.reload();
        } catch (err) {
          window.alert(err && err.message ? err.message : 'Refresh failed.');
          btn.disabled = false;
          if (icon) icon.classList.remove('fa-spin');
          if (label) label.textContent = 'Refresh';
        }
      });
    });
  });
</script>
