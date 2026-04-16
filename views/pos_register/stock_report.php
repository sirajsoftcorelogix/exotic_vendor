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
    <div class="overflow-x-auto">
      <table class="min-w-full text-left">
        <thead>
          <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
            <th class="px-5 py-3.5 whitespace-nowrap">Image</th>
            <th class="px-5 py-3.5 whitespace-nowrap">SKU</th>
            <th class="px-5 py-3.5 whitespace-nowrap">Category</th>
            <th class="px-5 py-3.5 whitespace-nowrap">Location</th>
            <th class="px-5 py-3.5 whitespace-nowrap">Stock</th>
            <th class="px-5 py-3.5 whitespace-nowrap text-right">Sell price</th>
            <th class="px-5 py-3.5 min-w-[10rem]">Title</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="7" class="px-5 py-16 text-center">
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
              <tr class="hover:bg-amber-50/40 transition-colors">
                <td class="px-5 py-4 align-top">
                  <img
                    src="<?= htmlspecialchars($imgUrl) ?>"
                    data-full-img="<?= htmlspecialchars($imgUrl) ?>"
                    class="h-10 w-10 rounded object-cover bg-slate-100 cursor-pointer hover:opacity-90 transition"
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
                <td class="px-5 py-4 align-top text-sm text-gray-700"><?= htmlspecialchars((string)($r['location'] ?? '')) ?></td>
                <td class="px-5 py-4 align-top">
                  <?php if ($qty <= 0): ?>
                    <span class="inline-flex rounded-full bg-red-100 px-3 py-1.5 text-xs font-semibold text-red-700">Out (0)</span>
                  <?php elseif ($qty <= 5): ?>
                    <span class="inline-flex rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-700">Low (<?= $qty ?>)</span>
                  <?php else: ?>
                    <span class="inline-flex rounded-full bg-green-100 px-3 py-1.5 text-xs font-semibold text-green-700">In (<?= $qty ?>)</span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-4 align-top text-sm text-right font-medium text-gray-900 tabular-nums"><?= number_format((float)($r['sell_price'] ?? 0), 2) ?></td>
                <td class="px-5 py-4 align-top text-sm text-gray-800 max-w-[10rem] break-words"><?= htmlspecialchars($r['title'] ?? '') ?></td>
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
    if (!modal) return;

    modal.addEventListener('click', (e) => {
      // Backdrop click closes (but clicks inside the image box should not)
      if (e.target === modal) closeStockReportImage();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeStockReportImage();
    });
  });
</script>
