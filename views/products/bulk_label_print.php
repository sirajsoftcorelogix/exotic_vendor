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

    <div class="bg-white border rounded-xl shadow-sm p-4 h-fit xl:sticky xl:top-4">
      <h3 class="text-base font-semibold text-gray-800">Selection Queue</h3>
      <p class="text-xs text-gray-500 mt-1">Set label quantity per product before print.</p>

      <div class="mt-3 grid grid-cols-2 gap-2">
        <button id="bulkLabelSetQtyAll" type="button" class="px-3 py-2 text-xs rounded border border-gray-300 hover:bg-gray-50">Set qty for all</button>
        <button id="bulkLabelClearAll" type="button" class="px-3 py-2 text-xs rounded border border-red-300 text-red-700 hover:bg-red-50">Clear all</button>
      </div>

      <div class="mt-3 border rounded-lg max-h-[280px] overflow-auto">
        <div id="bulkLabelQueueEmpty" class="p-4 text-sm text-gray-400 text-center">No products selected.</div>
        <div id="bulkLabelQueueList" class="divide-y hidden"></div>
      </div>

      <div class="mt-4 space-y-3">
        <div>
          <label for="bulkLabelTemplate" class="block text-sm font-medium text-gray-700 mb-1">Label template</label>
          <select id="bulkLabelTemplate" class="w-full border rounded-lg px-3 py-2 text-sm">
            <option value="jewelry">Jewelry — 100 × 12.9 mm</option>
            <option value="textile">Textile — 64 × 34 mm</option>
            <option value="mg_store">MG Road — 75 × 50 mm</option>
          </select>
        </div>

        <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm">
          <div class="flex justify-between"><span>Selected products</span><strong id="bulkLabelCountProducts">0</strong></div>
          <div class="flex justify-between"><span>Total labels</span><strong id="bulkLabelCountLabels">0</strong></div>
        </div>

        <button id="bulkLabelGenerateBtn" type="button"
          class="w-full px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold">
          Print labels
        </button>
        <p class="text-xs text-gray-500">Uses existing label templates to generate a bulk print job.</p>
      </div>
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

  var queueMap = {}; // key: product id -> {product, qty}
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

  function renderQueue() {
    var keys = Object.keys(queueMap);
    queueList.innerHTML = '';
    keys.forEach(function (k) {
      var row = queueMap[k];
      var p = row.product || {};
      var img = imageUrlForProduct(p);
      var thumbHtml = img
        ? '<img src="' + esc(img) + '" alt="" class="w-full h-full object-cover" loading="lazy" onerror="this.style.display=\'none\';this.parentNode.querySelector(\'span\').style.display=\'flex\';" /><span class="hidden items-center justify-center w-full h-full text-[10px] text-gray-400">No image</span>'
        : '<span class="flex items-center justify-center w-full h-full text-[10px] text-gray-400">No image</span>';
      var wrap = document.createElement('div');
      wrap.className = 'p-3';
      wrap.innerHTML =
        '<div class="flex items-start justify-between gap-2">' +
          '<div class="min-w-0 flex items-start gap-2">' +
            '<div class="w-10 h-10 rounded border border-gray-200 overflow-hidden bg-gray-50 shrink-0">' + thumbHtml + '</div>' +
            '<div class="min-w-0">' +
            '<div class="text-sm font-semibold text-gray-800 truncate">' + esc(p.sku || '—') + '</div>' +
            '<div class="text-xs text-gray-500 truncate">' + esc(p.item_code || '') + ' · ' + esc(p.title || '') + '</div>' +
            '</div>' +
          '</div>' +
          '<button type="button" data-rm="' + esc(k) + '" class="text-xs text-red-700 hover:text-red-900">Remove</button>' +
        '</div>' +
        '<div class="mt-2 flex items-center gap-2">' +
          '<button type="button" data-dec="' + esc(k) + '" class="h-7 w-7 rounded border border-gray-300 text-sm">-</button>' +
          '<input data-qty="' + esc(k) + '" value="' + esc(String(row.qty || 1)) + '" class="w-16 border rounded px-2 py-1 text-sm text-center" />' +
          '<button type="button" data-inc="' + esc(k) + '" class="h-7 w-7 rounded border border-gray-300 text-sm">+</button>' +
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
        ? '<img src="' + esc(img) + '" alt="" class="w-full h-full object-cover" loading="lazy" onerror="this.style.display=\\'none\\';this.parentNode.querySelector(\\'span\\').style.display=\\'flex\\';" /><span class="hidden items-center justify-center w-full h-full text-[10px] text-gray-400">No image</span>'
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

  setQtyAllBtn.addEventListener('click', function () {
    var v = prompt('Set quantity for all selected products (1-99):', '1');
    if (v == null) return;
    var n = Math.max(1, Math.min(99, parseInt(v, 10) || 1));
    Object.keys(queueMap).forEach(function (k) { queueMap[k].qty = n; });
    renderQueue();
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
      alert('Select at least one product.');
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
