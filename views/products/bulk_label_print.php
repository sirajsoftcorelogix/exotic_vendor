<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-4">
  <div class="bg-white border rounded-xl shadow-sm p-4 sm:p-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Bulk Label Print</h2>
        <p class="text-sm text-gray-600 mt-1">UI-only screen for fast search, select, and queue before bulk printing.</p>
      </div>
      <div class="flex items-center gap-2">
        <a href="?page=products&action=list" class="px-3 py-2 text-sm rounded border border-gray-300 hover:bg-gray-50">Back to Products</a>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="xl:col-span-2 bg-white border rounded-xl shadow-sm p-4">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
        <div class="md:col-span-7">
          <label for="bulkLabelSearchInput" class="block text-sm font-medium text-gray-700 mb-1">Search product (SKU / item code / title)</label>
          <input id="bulkLabelSearchInput" type="text" placeholder="Type SKU and press Enter to add top result"
            class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
        </div>
        <div class="md:col-span-3">
          <label for="bulkLabelWarehouse" class="block text-sm font-medium text-gray-700 mb-1">Warehouse</label>
          <select id="bulkLabelWarehouse" class="w-full border rounded-lg px-3 py-2 text-sm" <?= empty($isAdminUser) ? 'disabled' : '' ?>>
            <?php if (!empty($isAdminUser)): ?>
              <option value="">All</option>
            <?php endif; ?>
            <?php foreach (($warehouses ?? []) as $wh): ?>
              <?php
                $wid = (int)($wh['id'] ?? 0);
                $wname = (string)($wh['name'] ?? $wh['address_title'] ?? $wh['address'] ?? ('Warehouse #' . $wid));
              ?>
              <option value="<?= $wid ?>" <?= ((int)($selectedWarehouseId ?? 0) === $wid) ? 'selected' : '' ?>>
                <?= htmlspecialchars($wname, ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (empty($isAdminUser)): ?>
            <p class="text-[11px] text-gray-500 mt-1">Warehouse is fixed to your login warehouse.</p>
          <?php endif; ?>
        </div>
        <div class="md:col-span-2">
          <div class="flex items-center gap-1.5">
            <button id="bulkLabelSearchBtn" type="button"
              class="flex-1 px-3 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold">Search</button>
            <button id="bulkLabelSearchClearBtn" type="button" title="Clear search"
              class="h-8 px-2 rounded border border-gray-300 text-[11px] text-gray-600 hover:bg-gray-50">Clear</button>
          </div>
        </div>
      </div>

      <div class="mt-3 text-xs text-gray-500 flex flex-wrap items-center gap-x-3 gap-y-1">
        <span>Tip: <kbd class="px-1 border rounded bg-gray-50">Enter</kbd> adds highlighted result.</span>
        <span>Use <kbd class="px-1 border rounded bg-gray-50">↑</kbd> / <kbd class="px-1 border rounded bg-gray-50">↓</kbd> to navigate search results.</span>
        <span id="bulkLabelSearchMeta">No search yet.</span>
      </div>

      <div class="mt-4 border rounded-lg overflow-hidden">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-600">
            <tr>
              <th class="px-3 py-2 text-left">Thumb</th>
              <th class="px-3 py-2 text-left">SKU</th>
              <th class="px-3 py-2 text-left">Item Code</th>
              <th class="px-3 py-2 text-left">Title</th>
              <th class="px-3 py-2 text-left">Stock</th>
              <th class="px-3 py-2 text-left">Location</th>
              <th class="px-3 py-2 text-right">Action</th>
            </tr>
          </thead>
          <tbody id="bulkLabelResultsTbody">
            <tr><td colspan="7" class="px-3 py-8 text-center text-gray-400">Search products to start selection.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <aside class="h-fit xl:sticky xl:top-4 rounded-2xl border border-amber-200/70 bg-gradient-to-br from-amber-50/90 via-white to-stone-50/80 p-5 sm:p-6 shadow-md shadow-amber-900/5 ring-1 ring-stone-900/5 space-y-5">
      <section class="rounded-xl border border-stone-200/90 bg-white/95 p-4 shadow-sm backdrop-blur-sm">
        <div class="flex items-start gap-2.5">
          <span class="mt-0.5 h-8 w-1 shrink-0 rounded-full bg-amber-500" aria-hidden="true"></span>
          <div class="min-w-0 flex-1">
            <h3 class="text-sm font-semibold tracking-tight text-stone-900">Import file</h3>
            <p class="mt-1 text-[11px] leading-relaxed text-stone-500">Item Code required. Size and/or Color optional (all variant shapes). Optional Qty. Row 1 = headers.</p>
            <div class="mt-3 rounded-lg border border-dashed border-stone-300/90 bg-stone-50/80 px-3 py-2.5">
              <div class="flex min-w-0 flex-wrap items-center gap-2">
                <label for="bulkLabelImportFile" class="sr-only">Choose file</label>
                <input id="bulkLabelImportFile" type="file" accept=".csv,.xlsx,.xls,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                  class="min-w-0 flex-1 text-[11px] text-stone-600 file:mr-2 file:rounded-md file:border-0 file:bg-amber-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-amber-950 hover:file:bg-amber-200" />
                <button id="bulkLabelImportBtn" type="button"
                  class="shrink-0 rounded-lg bg-stone-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-stone-800">
                  Upload
                </button>
              </div>
            </div>
            <a href="?page=products&action=bulk_label_print_sample_csv" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-amber-800 hover:text-amber-950 underline decoration-amber-300 underline-offset-2">Sample CSV</a>
            <p id="bulkLabelImportMsg" class="mt-2 hidden whitespace-pre-wrap rounded-md border px-3 py-2 text-xs leading-relaxed" role="status"></p>
          </div>
        </div>
      </section>

      <div class="h-px bg-gradient-to-r from-transparent via-stone-200 to-transparent" aria-hidden="true"></div>

      <section class="rounded-xl border border-stone-200/90 bg-white/95 p-4 shadow-sm backdrop-blur-sm">
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div class="flex items-start gap-2.5 min-w-0">
            <span class="mt-0.5 h-8 w-1 shrink-0 rounded-full bg-amber-400" aria-hidden="true"></span>
            <div>
              <h3 class="text-sm font-semibold tracking-tight text-stone-900">Selection queue</h3>
              <p class="mt-0.5 text-[11px] text-stone-500">Adjust quantity per line before printing.</p>
            </div>
          </div>
          <div class="flex shrink-0 flex-wrap items-center justify-end gap-1.5">
            <button id="bulkLabelSetQtyAll" type="button"
              class="rounded-full border border-stone-200 bg-stone-50 px-3 py-1 text-[11px] font-medium text-stone-700 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-950">
              Set qty for all
            </button>
            <button id="bulkLabelClearAll" type="button"
              class="rounded-full border border-stone-200 bg-white px-3 py-1 text-[11px] font-medium text-stone-600 transition hover:border-red-200 hover:bg-red-50 hover:text-red-800">
              Clear all
            </button>
          </div>
        </div>

        <div class="mt-3 max-h-[280px] min-h-[132px] overflow-auto rounded-xl border border-stone-200/80 bg-stone-50/90 shadow-inner">
          <div id="bulkLabelQueueEmpty" class="flex min-h-[120px] flex-col items-center justify-center gap-1 px-4 py-8 text-center">
            <span class="text-sm font-medium text-stone-400">Queue is empty</span>
            <span class="text-xs text-stone-400">Search or import to add products</span>
          </div>
          <div id="bulkLabelQueueList" class="hidden space-y-2 p-2"></div>
        </div>
      </section>

      <section class="rounded-xl border border-amber-200/80 bg-gradient-to-b from-amber-50/90 to-white p-4 shadow-sm">
        <label for="bulkLabelTemplate" class="block text-xs font-semibold uppercase tracking-wide text-amber-950/80">Label template</label>
        <select id="bulkLabelTemplate" class="mt-2 w-full cursor-pointer rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-800 shadow-sm transition focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
          <option value="jewelry">Jewelry — 100 × 12.9 mm</option>
          <option value="textile">Textile — 64 × 34 mm</option>
          <option value="mg_store">MG Road — 75 × 50 mm</option>
        </select>

        <div class="mt-4 grid grid-cols-2 gap-2">
          <div class="rounded-lg border border-amber-200/60 bg-white/80 px-3 py-2.5 text-center shadow-sm">
            <div class="text-[10px] font-medium uppercase tracking-wider text-stone-500">Products</div>
            <div id="bulkLabelCountProducts" class="mt-0.5 text-lg font-bold tabular-nums text-stone-900">0</div>
          </div>
          <div class="rounded-lg border border-amber-200/60 bg-white/80 px-3 py-2.5 text-center shadow-sm">
            <div class="text-[10px] font-medium uppercase tracking-wider text-stone-500">Labels</div>
            <div id="bulkLabelCountLabels" class="mt-0.5 text-lg font-bold tabular-nums text-amber-800">0</div>
          </div>
        </div>

        <button id="bulkLabelGenerateBtn" type="button"
          class="mt-4 w-full rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-amber-600/25 transition hover:from-amber-500 hover:to-amber-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">
          Print labels
        </button>
        <p class="mt-2 text-center text-[11px] leading-relaxed text-stone-500">Opens print preview using the template above.</p>
      </section>
    </aside>
  </div>

  <div id="bulkLabelQtyAllModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/45" role="dialog" aria-modal="true" aria-labelledby="bulkLabelQtyAllModalTitle">
    <div class="bg-white rounded-xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden" role="document">
      <div class="px-5 pt-5 pb-3 border-b border-gray-100">
        <h3 id="bulkLabelQtyAllModalTitle" class="text-lg font-semibold text-gray-900">Set quantity for all</h3>
        <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">Choose how many labels to print for <strong>every</strong> product currently in the queue. Allowed range: 1–99.</p>
      </div>
      <div class="px-5 py-4">
        <label for="bulkLabelQtyAllInput" class="block text-sm font-medium text-gray-700 mb-2">Labels per product</label>
        <div class="flex items-center gap-3">
          <button type="button" id="bulkLabelQtyAllDec" class="h-11 w-11 shrink-0 rounded-lg border border-gray-300 text-lg font-semibold text-gray-700 hover:bg-gray-50" aria-label="Decrease quantity">−</button>
          <input type="number" id="bulkLabelQtyAllInput" min="1" max="99" value="1"
            class="flex-1 min-w-0 border rounded-lg px-3 py-2.5 text-center text-lg font-semibold text-gray-900 tabular-nums focus:ring-2 focus:ring-amber-500 focus:border-amber-500" />
          <button type="button" id="bulkLabelQtyAllInc" class="h-11 w-11 shrink-0 rounded-lg border border-gray-300 text-lg font-semibold text-gray-700 hover:bg-gray-50" aria-label="Increase quantity">+</button>
        </div>
        <p id="bulkLabelQtyAllHint" class="text-xs text-gray-400 mt-2"></p>
      </div>
      <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
        <button type="button" id="bulkLabelQtyAllCancel" class="w-full sm:w-auto px-4 py-2.5 text-sm rounded-lg border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-medium">Cancel</button>
        <button type="button" id="bulkLabelQtyAllApply" class="w-full sm:w-auto px-4 py-2.5 text-sm rounded-lg bg-amber-600 text-white hover:bg-amber-700 font-semibold shadow-sm">Apply to all</button>
      </div>
    </div>
  </div>

  <div id="bulkLabelInfoModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/45" role="dialog" aria-modal="true" aria-labelledby="bulkLabelInfoModalTitle">
    <div class="bg-white rounded-xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden" role="document">
      <div class="px-5 pt-5 pb-3 border-b border-gray-100">
        <h3 id="bulkLabelInfoModalTitle" class="text-lg font-semibold text-gray-900">Notice</h3>
      </div>
      <div class="px-5 py-4">
        <p id="bulkLabelInfoModalMessage" class="text-sm text-gray-600 leading-relaxed"></p>
      </div>
      <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
        <button type="button" id="bulkLabelInfoModalOk" class="px-5 py-2.5 text-sm rounded-lg bg-amber-600 text-white hover:bg-amber-700 font-semibold shadow-sm min-w-[5rem]">OK</button>
      </div>
    </div>
  </div>

  <div id="bulkLabelQueueImageModal" class="hidden fixed inset-0 z-[102] flex items-center justify-center p-4 sm:p-6 bg-black/75" role="dialog" aria-modal="true" aria-labelledby="bulkLabelQueueImageModalTitle">
    <div class="relative z-10 w-full max-w-3xl flex flex-col items-center gap-3" role="document">
      <h2 id="bulkLabelQueueImageModalTitle" class="sr-only">Product image preview</h2>
      <button type="button" id="bulkLabelQueueImageModalClose" class="self-end rounded-lg bg-white/95 px-3 py-1.5 text-sm font-semibold text-gray-800 shadow hover:bg-white border border-gray-200">Close</button>
      <div class="w-full rounded-xl bg-white p-2 sm:p-3 shadow-2xl ring-1 ring-black/10">
        <img id="bulkLabelQueueImageModalImg" src="" alt="" class="mx-auto max-h-[min(75vh,560px)] w-auto max-w-full object-contain" />
      </div>
      <p id="bulkLabelQueueImageModalCaption" class="max-w-full text-center text-sm text-white/95 drop-shadow px-2"></p>
    </div>
  </div>
</div>

<script>
(function () {
  var appBaseUrl = <?php echo json_encode(base_url(''), JSON_UNESCAPED_SLASHES); ?>;
  var loginWarehouseId = <?php echo (int)($selectedWarehouseId ?? 0); ?>;
  var searchInput = document.getElementById('bulkLabelSearchInput');
  var searchBtn = document.getElementById('bulkLabelSearchBtn');
  var clearSearchBtn = document.getElementById('bulkLabelSearchClearBtn');
  var warehouseSel = document.getElementById('bulkLabelWarehouse');
  var resultsTbody = document.getElementById('bulkLabelResultsTbody');
  var searchMeta = document.getElementById('bulkLabelSearchMeta');
  var queueEmpty = document.getElementById('bulkLabelQueueEmpty');
  var queueList = document.getElementById('bulkLabelQueueList');
  var countProducts = document.getElementById('bulkLabelCountProducts');
  var countLabels = document.getElementById('bulkLabelCountLabels');
  var clearAllBtn = document.getElementById('bulkLabelClearAll');
  var setQtyAllBtn = document.getElementById('bulkLabelSetQtyAll');
  var generateBtn = document.getElementById('bulkLabelGenerateBtn');
  var templateSel = document.getElementById('bulkLabelTemplate');
  var qtyAllModal = document.getElementById('bulkLabelQtyAllModal');
  var qtyAllInput = document.getElementById('bulkLabelQtyAllInput');
  var qtyAllApply = document.getElementById('bulkLabelQtyAllApply');
  var qtyAllCancel = document.getElementById('bulkLabelQtyAllCancel');
  var qtyAllInc = document.getElementById('bulkLabelQtyAllInc');
  var qtyAllDec = document.getElementById('bulkLabelQtyAllDec');
  var qtyAllHint = document.getElementById('bulkLabelQtyAllHint');
  var importFile = document.getElementById('bulkLabelImportFile');
  var importBtn = document.getElementById('bulkLabelImportBtn');
  var importMsg = document.getElementById('bulkLabelImportMsg');
  var infoModal = document.getElementById('bulkLabelInfoModal');
  var infoModalTitle = document.getElementById('bulkLabelInfoModalTitle');
  var infoModalMessage = document.getElementById('bulkLabelInfoModalMessage');
  var infoModalOk = document.getElementById('bulkLabelInfoModalOk');
  var queueImageModal = document.getElementById('bulkLabelQueueImageModal');
  var queueImageModalImg = document.getElementById('bulkLabelQueueImageModalImg');
  var queueImageModalCaption = document.getElementById('bulkLabelQueueImageModalCaption');
  var queueImageModalClose = document.getElementById('bulkLabelQueueImageModalClose');

  var queueMap = {}; // key: product id -> {product, qty}
  var qtyAllLastApplied = 1;
  var searchReqId = 0;
  var searchDebounce = null;
  var activeResultIndex = -1;

  // Keep default warehouse aligned with login session.
  if (warehouseSel && loginWarehouseId > 0) {
    warehouseSel.value = String(loginWarehouseId);
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function imageUrlForProduct(p) {
    var src = (p && p.image != null) ? String(p.image).trim() : '';
    if (!src) return '';
    if (/^https?:\/\//i.test(src)) return src;
    if (src.charAt(0) === '/') return src;
    return String(appBaseUrl || '').replace(/\/+$/, '') + '/' + src.replace(/^\/+/, '');
  }

  function totalLabels() {
    var sum = 0;
    Object.keys(queueMap).forEach(function (k) {
      sum += Math.max(1, parseInt(queueMap[k].qty, 10) || 1);
    });
    return sum;
  }

  function updateCounters() {
    var pc = Object.keys(queueMap).length;
    countProducts.textContent = String(pc);
    countLabels.textContent = String(totalLabels());
    if (pc === 0) {
      queueEmpty.classList.remove('hidden');
      queueList.classList.add('hidden');
    } else {
      queueEmpty.classList.add('hidden');
      queueList.classList.remove('hidden');
    }
  }

  function openQueueImageModal(src, caption) {
    if (!queueImageModal || !queueImageModalImg) return;
    queueImageModalImg.src = src || '';
    queueImageModalImg.alt = caption || 'Product';
    if (queueImageModalCaption) {
      queueImageModalCaption.textContent = caption || '';
      queueImageModalCaption.classList.toggle('hidden', !caption);
    }
    queueImageModal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    try { queueImageModalClose.focus(); } catch (err) {}
  }

  function closeQueueImageModal() {
    if (!queueImageModal || !queueImageModalImg) return;
    queueImageModal.classList.add('hidden');
    queueImageModalImg.src = '';
    document.body.classList.remove('overflow-hidden');
  }

  function renderQueue() {
    var keys = Object.keys(queueMap);
    queueList.innerHTML = '';
    keys.forEach(function (k) {
      var row = queueMap[k];
      var p = row.product || {};
      var img = imageUrlForProduct(p);
      var cap = (p.sku || '—') + ' · ' + (p.item_code || '') + (p.title ? (' — ' + String(p.title)) : '');
      var thumbInner = img
        ? '<img src="' + esc(img) + '" alt="" class="w-full h-full object-cover pointer-events-none" loading="lazy" onerror="this.style.display=\'none\';var sp=this.parentNode&&this.parentNode.querySelector(\'.jl-thumb-fallback\');if(sp)sp.style.display=\'flex\';" /><span class="jl-thumb-fallback hidden items-center justify-center w-full h-full text-[10px] text-gray-400 pointer-events-none">No image</span>'
        : '<span class="flex items-center justify-center w-full h-full text-[10px] text-gray-400">No image</span>';
      var thumbWrap = img
        ? '<button type="button" class="bulk-label-queue-thumb h-11 w-11 shrink-0 overflow-hidden rounded-lg border border-stone-200 bg-white p-0 shadow-sm cursor-pointer ring-amber-400/0 transition hover:ring-2 hover:ring-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500" data-bulk-queue-thumb="1" data-img-href="' + esc(img) + '" data-img-caption="' + esc(cap) + '" title="View larger" aria-label="View larger product image">' + thumbInner + '</button>'
        : '<div class="h-11 w-11 shrink-0 overflow-hidden rounded-lg border border-stone-200 bg-stone-100">' + thumbInner + '</div>';
      var wrap = document.createElement('div');
      wrap.className = 'rounded-lg border border-stone-200/90 bg-white p-3 shadow-sm transition-colors hover:border-amber-200/80';
      wrap.innerHTML =
        '<div class="flex items-start justify-between gap-2">' +
          '<div class="min-w-0 flex items-start gap-2.5">' +
            thumbWrap +
            '<div class="min-w-0">' +
            '<div class="text-sm font-semibold text-stone-900 truncate">' + esc(p.sku || '—') + '</div>' +
            '<div class="text-xs text-stone-500 truncate">' + esc(p.item_code || '') + ' · ' + esc(p.title || '') + '</div>' +
            '</div>' +
          '</div>' +
          '<button type="button" data-rm="' + esc(k) + '" class="shrink-0 rounded-md px-2 py-1 text-[11px] font-medium text-red-700 transition hover:bg-red-50">Remove</button>' +
        '</div>' +
        '<div class="mt-2.5 flex items-center gap-2">' +
          '<span class="text-[11px] font-medium text-stone-500">Qty</span>' +
          '<button type="button" data-dec="' + esc(k) + '" class="flex h-8 w-8 items-center justify-center rounded-lg border border-stone-200 bg-stone-50 text-sm font-medium text-stone-700 transition hover:bg-stone-100">−</button>' +
          '<input data-qty="' + esc(k) + '" value="' + esc(String(row.qty || 1)) + '" class="w-14 rounded-lg border border-stone-200 bg-white px-2 py-1.5 text-center text-sm font-semibold tabular-nums text-stone-900 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500" />' +
          '<button type="button" data-inc="' + esc(k) + '" class="flex h-8 w-8 items-center justify-center rounded-lg border border-stone-200 bg-stone-50 text-sm font-medium text-stone-700 transition hover:bg-stone-100">+</button>' +
        '</div>';
      queueList.appendChild(wrap);
    });
    updateCounters();
  }

  function addProductToQueue(p) {
    var id = String(parseInt(p.id, 10) || 0);
    if (!id || id === '0') return;
    if (queueMap[id]) {
      queueMap[id].qty = Math.min(99, (parseInt(queueMap[id].qty, 10) || 1) + 1);
    } else {
      queueMap[id] = { product: p, qty: 1 };
    }
    renderQueue();
  }

  function renderResults(products) {
    activeResultIndex = -1;
    resultsTbody.innerHTML = '';
    if (!products || !products.length) {
      resultsTbody.innerHTML = '<tr><td colspan="7" class="px-3 py-8 text-center text-gray-400">No products found.</td></tr>';
      return;
    }
    products.forEach(function (p, idx) {
      var img = imageUrlForProduct(p);
      var thumbHtml = img
        ? '<img src="' + esc(img) + '" alt="" class="w-full h-full object-cover" loading="lazy" onerror="this.style.display=\'none\';this.parentNode.querySelector(\'span\').style.display=\'flex\';" /><span class="hidden items-center justify-center w-full h-full text-[10px] text-gray-400">No image</span>'
        : '<span class="flex items-center justify-center w-full h-full text-[10px] text-gray-400">No image</span>';
      var tr = document.createElement('tr');
      tr.className = 'border-t hover:bg-gray-50';
      tr.dataset.resultIndex = String(idx);
      tr.innerHTML =
        '<td class="px-3 py-2"><div class="w-10 h-10 rounded border border-gray-200 overflow-hidden bg-gray-50">' + thumbHtml + '</div></td>' +
        '<td class="px-3 py-2 font-semibold">' + esc(p.sku || '') + '</td>' +
        '<td class="px-3 py-2">' + esc(p.item_code || '') + '</td>' +
        '<td class="px-3 py-2">' + esc(p.title || '') + '</td>' +
        '<td class="px-3 py-2">' + esc(String(p.local_stock != null ? p.local_stock : '')) + '</td>' +
        '<td class="px-3 py-2">' + esc(p.location || '') + '</td>' +
        '<td class="px-3 py-2 text-right"><button type="button" class="px-2 py-1 rounded bg-amber-600 text-white text-xs hover:bg-amber-700" data-add="' + esc(String(p.id || '')) + '">Add</button></td>';
      tr._product = p;
      resultsTbody.appendChild(tr);
    });
    setActiveResultIndex(0);
  }

  function getResultRows() {
    return Array.prototype.slice.call(resultsTbody.querySelectorAll('tr[data-result-index]'));
  }

  function setActiveResultIndex(nextIdx) {
    var rows = getResultRows();
    if (!rows.length) {
      activeResultIndex = -1;
      return;
    }
    if (nextIdx < 0) nextIdx = 0;
    if (nextIdx > rows.length - 1) nextIdx = rows.length - 1;
    activeResultIndex = nextIdx;
    rows.forEach(function (row, i) {
      if (i === activeResultIndex) {
        row.classList.add('bg-amber-50');
      } else {
        row.classList.remove('bg-amber-50');
      }
    });
    try {
      rows[activeResultIndex].scrollIntoView({ block: 'nearest' });
    } catch (e) {}
  }

  function addActiveOrTopResult() {
    var rows = getResultRows();
    if (!rows.length) return false;
    var idx = activeResultIndex >= 0 ? activeResultIndex : 0;
    var row = rows[idx];
    if (row && row._product) {
      addProductToQueue(row._product);
      searchMeta.textContent = 'Added: ' + (row._product.sku || '');
      return true;
    }
    return false;
  }

  async function doSearch(addTopOnEnter) {
    var q = (searchInput.value || '').trim();
    if (q.length < 2) {
      searchMeta.textContent = 'Type at least 2 characters.';
      return;
    }
    var reqId = ++searchReqId;
    searchMeta.textContent = 'Searching...';
    var url = '?page=products&action=search_product&q=' + encodeURIComponent(q);
    try {
      var res = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
      var data = await res.json();
      if (reqId !== searchReqId) return;
      if (!data.success || !Array.isArray(data.products)) {
        renderResults([]);
        searchMeta.textContent = data.message || 'No products found.';
        return;
      }
      renderResults(data.products);
      searchMeta.textContent = data.products.length + ' result(s)';
      if (addTopOnEnter && data.products.length > 0) {
        addActiveOrTopResult();
      }
    } catch (e) {
      renderResults([]);
      searchMeta.textContent = 'Search failed. Try again.';
    }
  }

  searchBtn.addEventListener('click', function () { doSearch(false); });
  clearSearchBtn && clearSearchBtn.addEventListener('click', function () {
    if (searchDebounce) clearTimeout(searchDebounce);
    searchReqId += 1; // invalidate in-flight responses
    searchInput.value = '';
    resultsTbody.innerHTML = '<tr><td colspan="7" class="px-3 py-8 text-center text-gray-400">Search products to start selection.</td></tr>';
    searchMeta.textContent = 'Search cleared.';
    try { searchInput.focus(); } catch (e) {}
  });
  searchInput.addEventListener('keydown', function (e) {
    if (e.key === 'Delete') {
      e.preventDefault();
      if (searchDebounce) clearTimeout(searchDebounce);
      searchReqId += 1;
      searchInput.value = '';
      activeResultIndex = -1;
      resultsTbody.innerHTML = '<tr><td colspan="7" class="px-3 py-8 text-center text-gray-400">Search products to start selection.</td></tr>';
      searchMeta.textContent = 'Search cleared (Delete key).';
      return;
    }
    if (e.key === 'ArrowDown') {
      var downRows = getResultRows();
      if (downRows.length) {
        e.preventDefault();
        setActiveResultIndex(activeResultIndex + 1);
      }
      return;
    }
    if (e.key === 'ArrowUp') {
      var upRows = getResultRows();
      if (upRows.length) {
        e.preventDefault();
        setActiveResultIndex(activeResultIndex - 1);
      }
      return;
    }
    if (e.key === 'Enter') {
      e.preventDefault();
      if (!addActiveOrTopResult()) {
        doSearch(true);
      }
    }
  });
  searchInput.addEventListener('input', function () {
    if (searchDebounce) clearTimeout(searchDebounce);
    var q = (searchInput.value || '').trim();
    if (q.length < 2) {
      resultsTbody.innerHTML = '<tr><td colspan="7" class="px-3 py-8 text-center text-gray-400">Type at least 2 characters to search.</td></tr>';
      searchMeta.textContent = 'Type at least 2 characters.';
      return;
    }
    searchDebounce = setTimeout(function () {
      doSearch(false);
    }, 220);
  });

  resultsTbody.addEventListener('click', function (e) {
    var btn = e.target && e.target.closest('[data-add]');
    if (!btn) return;
    var id = String(btn.getAttribute('data-add') || '');
    var row = btn.closest('tr');
    if (row && row._product && String(row._product.id) === id) {
      addProductToQueue(row._product);
    }
  });
  resultsTbody.addEventListener('mousemove', function (e) {
    var row = e.target && e.target.closest('tr[data-result-index]');
    if (!row) return;
    var idx = parseInt(row.dataset.resultIndex || '-1', 10);
    if (!isNaN(idx) && idx !== activeResultIndex) {
      setActiveResultIndex(idx);
    }
  });

  queueList.addEventListener('click', function (e) {
    var thumbBtn = e.target.closest('[data-bulk-queue-thumb]');
    if (thumbBtn) {
      var href = thumbBtn.getAttribute('data-img-href');
      if (href) {
        openQueueImageModal(href, thumbBtn.getAttribute('data-img-caption') || '');
      }
      return;
    }
    var rm = e.target.closest('[data-rm]');
    if (rm) {
      delete queueMap[String(rm.getAttribute('data-rm'))];
      renderQueue();
      return;
    }
    var dec = e.target.closest('[data-dec]');
    if (dec) {
      var dk = String(dec.getAttribute('data-dec'));
      if (queueMap[dk]) queueMap[dk].qty = Math.max(1, (parseInt(queueMap[dk].qty, 10) || 1) - 1);
      renderQueue();
      return;
    }
    var inc = e.target.closest('[data-inc]');
    if (inc) {
      var ik = String(inc.getAttribute('data-inc'));
      if (queueMap[ik]) queueMap[ik].qty = Math.min(99, (parseInt(queueMap[ik].qty, 10) || 1) + 1);
      renderQueue();
    }
  });

  queueList.addEventListener('change', function (e) {
    var input = e.target.closest('[data-qty]');
    if (!input) return;
    var k = String(input.getAttribute('data-qty'));
    var v = parseInt(input.value, 10);
    if (!queueMap[k]) return;
    queueMap[k].qty = Math.max(1, Math.min(99, isNaN(v) ? 1 : v));
    renderQueue();
  });

  clearAllBtn.addEventListener('click', function () {
    queueMap = {};
    renderQueue();
  });

  function showImportMessage(text, isError) {
    if (!importMsg) return;
    importMsg.textContent = text || '';
    importMsg.classList.remove(
      'hidden', 'text-red-800', 'text-stone-700', 'bg-red-50', 'border-red-200',
      'bg-stone-100', 'border-stone-200'
    );
    if (!text) {
      importMsg.classList.add('hidden');
      return;
    }
    if (isError) {
      importMsg.classList.add('text-red-800', 'bg-red-50', 'border-red-200');
    } else {
      importMsg.classList.add('text-stone-700', 'bg-stone-100', 'border-stone-200');
    }
  }

  importBtn && importBtn.addEventListener('click', async function () {
    if (!importFile || !importFile.files || !importFile.files.length) {
      showImportMessage('Choose a CSV or Excel file first.', true);
      return;
    }
    var fd = new FormData();
    fd.append('label_import_file', importFile.files[0]);
    var prev = importBtn.textContent;
    importBtn.disabled = true;
    importBtn.textContent = 'Importing…';
    showImportMessage('');
    try {
      var res = await fetch('?page=products&action=bulk_label_print_upload', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' },
        body: fd
      });
      var data = await res.json();
      if (!data || !data.success) {
        showImportMessage((data && data.message) ? data.message : 'Import failed.', true);
        return;
      }
      var list = data.products || [];
      list.forEach(function (p) { addProductToQueue(p); });
      var nf = data.not_found || [];
      var parts = [];
      if (list.length) {
        parts.push('Added ' + list.length + ' queue line(s) from the file.');
      } else {
        parts.push('No rows matched products in your catalog.');
      }
      if (nf.length) {
        var lines = nf.slice(0, 8).map(function (r) {
          return 'Line ' + r.line + ': ' + (r.item_code || '—') + ' / ' + (r.size || '—') + ' / ' + (r.color || '—');
        });
        parts.push('Not found (' + nf.length + '): ' + lines.join('; ') + (nf.length > 8 ? ' …' : ''));
      }
      showImportMessage(parts.join('\n'), nf.length && !list.length);
    } catch (e) {
      showImportMessage('Could not upload file. Try again.', true);
    } finally {
      importBtn.disabled = false;
      importBtn.textContent = prev;
    }
  });

  function clampQtyAll(n) {
    return Math.max(1, Math.min(99, n));
  }

  function getQtyAllInputValue() {
    return clampQtyAll(parseInt(qtyAllInput.value, 10) || 1);
  }

  function setQtyAllInputValue(n) {
    qtyAllInput.value = String(clampQtyAll(n));
  }

  function openInfoModal(title, message) {
    if (!infoModal || !infoModalTitle || !infoModalMessage) return;
    infoModalTitle.textContent = title || 'Notice';
    infoModalMessage.textContent = message || '';
    infoModal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    try { infoModalOk.focus(); } catch (e) {}
  }

  function closeInfoModal() {
    if (!infoModal) return;
    infoModal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  function openQtyAllModal() {
    var keys = Object.keys(queueMap);
    if (!keys.length) {
      openInfoModal('Selection queue is empty', 'Add at least one product to the queue first.');
      return;
    }
    qtyAllHint.textContent = keys.length + ' product(s) in queue.';
    setQtyAllInputValue(qtyAllLastApplied);
    qtyAllModal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    try {
      qtyAllInput.focus();
      qtyAllInput.select();
    } catch (e) {}
  }

  function closeQtyAllModal() {
    qtyAllModal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    try { setQtyAllBtn.focus(); } catch (e) {}
  }

  function applyQtyAllFromModal() {
    var keys = Object.keys(queueMap);
    if (!keys.length) {
      closeQtyAllModal();
      return;
    }
    var n = getQtyAllInputValue();
    qtyAllLastApplied = n;
    keys.forEach(function (k) { queueMap[k].qty = n; });
    renderQueue();
    closeQtyAllModal();
  }

  setQtyAllBtn.addEventListener('click', openQtyAllModal);

  infoModalOk && infoModalOk.addEventListener('click', closeInfoModal);
  infoModal && infoModal.addEventListener('click', function (e) {
    if (e.target === infoModal) closeInfoModal();
  });

  queueImageModalClose && queueImageModalClose.addEventListener('click', closeQueueImageModal);
  queueImageModal && queueImageModal.addEventListener('click', function (e) {
    if (e.target === queueImageModal) closeQueueImageModal();
  });

  qtyAllCancel.addEventListener('click', closeQtyAllModal);
  qtyAllApply.addEventListener('click', applyQtyAllFromModal);
  qtyAllModal.addEventListener('click', function (e) {
    if (e.target === qtyAllModal) closeQtyAllModal();
  });
  qtyAllInc.addEventListener('click', function () {
    setQtyAllInputValue(getQtyAllInputValue() + 1);
  });
  qtyAllDec.addEventListener('click', function () {
    setQtyAllInputValue(getQtyAllInputValue() - 1);
  });
  qtyAllInput.addEventListener('change', function () {
    setQtyAllInputValue(getQtyAllInputValue());
  });
  document.addEventListener('keydown', function (e) {
    if (queueImageModal && !queueImageModal.classList.contains('hidden')) {
      if (e.key === 'Escape') {
        e.preventDefault();
        closeQueueImageModal();
      }
      return;
    }
    if (infoModal && !infoModal.classList.contains('hidden')) {
      if (e.key === 'Escape') {
        e.preventDefault();
        closeInfoModal();
      } else if (e.key === 'Enter') {
        e.preventDefault();
        closeInfoModal();
      }
      return;
    }
    if (!qtyAllModal || qtyAllModal.classList.contains('hidden')) return;
    if (e.key === 'Escape') {
      e.preventDefault();
      closeQtyAllModal();
    } else if (e.key === 'Enter' && e.target === qtyAllInput) {
      e.preventDefault();
      applyQtyAllFromModal();
    }
  });

  function openPrintHtmlInFrame(html) {
    var old = document.getElementById('bulk-label-print-frame');
    if (old && old.parentNode) old.parentNode.removeChild(old);
    var frame = document.createElement('iframe');
    frame.id = 'bulk-label-print-frame';
    frame.setAttribute('title', 'Bulk label print');
    frame.style.cssText = 'position:fixed;left:-9999px;top:0;width:120mm;height:80mm;border:0;opacity:0;pointer-events:none;z-index:-1;';
    document.body.appendChild(frame);
    var d = frame.contentWindow.document;
    d.open();
    d.write(html || '');
    d.close();
  }

  generateBtn.addEventListener('click', async function () {
    var pc = Object.keys(queueMap).length;
    if (pc < 1) {
      openInfoModal('Nothing to print', 'Select at least one product.');
      return;
    }
    var payload = {
      template: templateSel.value,
      products: Object.keys(queueMap).map(function (k) {
        return { id: parseInt(k, 10), qty: queueMap[k].qty };
      })
    };

    var prevLabel = generateBtn.textContent;
    generateBtn.disabled = true;
    generateBtn.textContent = 'Preparing...';
    try {
      var res = await fetch('?page=products&action=bulk_label_print_generate', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      });
      var data = await res.json();
      if (!data || !data.success || !data.html) {
        alert((data && data.message) ? data.message : 'Could not generate labels.');
        return;
      }
      openPrintHtmlInFrame(data.html);
    } catch (e) {
      alert('Could not generate labels. Please try again.');
    } finally {
      generateBtn.disabled = false;
      generateBtn.textContent = prevLabel;
    }
  });

  updateCounters();
})();
</script>
