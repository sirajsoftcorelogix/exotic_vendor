<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
  <!-- SKU quick jump (product detail) -->
  <div class="bg-gradient-to-r from-amber-50 via-white to-orange-50/50 rounded-xl border border-amber-100/80 shadow-sm p-4 sm:p-5">
    <form id="productDetailSkuSearchForm" class="relative" autocomplete="off">
      <label for="productDetailSkuInput" class="block text-sm font-semibold text-gray-800 mb-1.5">
        <i class="fas fa-search text-amber-600 mr-1.5" aria-hidden="true"></i>Jump to product by SKU
      </label>
      <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-3">
        <div class="relative flex-1 min-w-0">
          <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-amber-500/90 z-10">
            <i class="fas fa-barcode text-sm"></i>
          </span>
          <input type="text" id="productDetailSkuInput" name="sku_jump"
            class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-lg shadow-inner bg-white/90 placeholder:text-gray-400 focus:ring-2 focus:ring-amber-400/80 focus:border-amber-500 outline-none transition"
            placeholder="Type SKU — suggestions appear after 2 characters"
            value="<?php echo htmlspecialchars($products['sku'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            aria-autocomplete="list" aria-controls="productDetailSkuSuggestions" aria-expanded="false" />
          <div id="productDetailSkuSuggestions" role="listbox"
            class="hidden absolute left-0 right-0 top-full mt-1.5 max-h-72 overflow-y-auto rounded-xl border border-gray-200/90 bg-white shadow-xl shadow-amber-900/10 z-[100] py-1">
          </div>
        </div>
        <button type="submit"
          class="shrink-0 inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg bg-gradient-to-b from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-sm font-semibold shadow-md shadow-amber-600/25 border border-amber-600/30 transition w-full sm:w-auto">
          <i class="fas fa-arrow-right text-xs opacity-90"></i> Go
        </button>
      </div>
      <p class="mt-1.5 text-xs text-gray-500">Select a suggestion or enter the full SKU and press <kbd class="px-1 rounded bg-gray-100 border text-[10px]">Go</kbd></p>
      <p id="productDetailSkuError" class="hidden mt-3 text-sm font-medium text-red-600 flex items-center gap-2">
        <i class="fas fa-exclamation-circle"></i><span id="productDetailSkuErrorText"></span>
      </p>
    </form>
  </div>
  <script>
  (function () {
    var searchBase = <?php echo json_encode(base_url('?page=products&action=search_product'), JSON_UNESCAPED_SLASHES); ?>;
    var detailBase = <?php echo json_encode(base_url('?page=products&action=detail&id='), JSON_UNESCAPED_SLASHES); ?>;
    var currentId = <?php echo (int)($products['id'] ?? 0); ?>;

    var form = document.getElementById('productDetailSkuSearchForm');
    var input = document.getElementById('productDetailSkuInput');
    var box = document.getElementById('productDetailSkuSuggestions');
    var errWrap = document.getElementById('productDetailSkuError');
    var errText = document.getElementById('productDetailSkuErrorText');
    var debounceTimer = null;
    var activeFetch = 0;

    function hideError() {
      errWrap.classList.add('hidden');
      errText.textContent = '';
    }

    function showError(msg) {
      errText.textContent = msg;
      errWrap.classList.remove('hidden');
    }

    function closeSuggestions() {
      box.classList.add('hidden');
      box.innerHTML = '';
      input.setAttribute('aria-expanded', 'false');
    }

    function goToProduct(id) {
      if (!id || id === currentId) {
        if (id === currentId) showError('You are already on this product.');
        return;
      }
      window.location.href = detailBase + encodeURIComponent(id);
    }

    function renderSuggestions(products) {
      box.innerHTML = '';
      if (!products || !products.length) {
        closeSuggestions();
        return;
      }
      input.setAttribute('aria-expanded', 'true');
      box.classList.remove('hidden');
      products.forEach(function (p, idx) {
        var id = p.id;
        var sku = (p.sku != null ? String(p.sku) : '');
        var ic = (p.item_code != null ? String(p.item_code) : '');
        var title = (p.title != null ? String(p.title) : '');
        if (title.length > 72) title = title.slice(0, 69) + '…';
        var row = document.createElement('button');
        row.type = 'button';
        row.setAttribute('role', 'option');
        row.className = 'w-full text-left px-4 py-2.5 text-sm hover:bg-amber-50/90 focus:bg-amber-50 outline-none border-b border-gray-50 last:border-0 flex flex-col gap-0.5 transition';
        row.innerHTML = '<span class="font-semibold text-gray-900 tracking-tight">' + escapeHtml(sku) + '</span>' +
          '<span class="text-xs text-gray-500">' + escapeHtml(ic) + (title ? ' · ' + escapeHtml(title) : '') + '</span>';
        row.addEventListener('click', function () {
          input.value = sku;
          closeSuggestions();
          goToProduct(parseInt(id, 10));
        });
        box.appendChild(row);
      });
    }

    function escapeHtml(s) {
      var d = document.createElement('div');
      d.textContent = s;
      return d.innerHTML;
    }

    function fetchSuggestions(q) {
      if (q.length < 2) {
        closeSuggestions();
        return;
      }
      var myId = ++activeFetch;
      var url = searchBase + (searchBase.indexOf('?') >= 0 ? '&' : '?') + 'q=' + encodeURIComponent(q);
      fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (myId !== activeFetch) return;
          if (data.success && data.products && data.products.length) {
            renderSuggestions(data.products);
          } else {
            closeSuggestions();
          }
        })
        .catch(function () {
          if (myId !== activeFetch) return;
          closeSuggestions();
        });
    }

    input.addEventListener('input', function () {
      hideError();
      var q = (input.value || '').trim();
      clearTimeout(debounceTimer);
      if (q.length < 2) {
        closeSuggestions();
        return;
      }
      debounceTimer = setTimeout(function () { fetchSuggestions(q); }, 280);
    });

    input.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeSuggestions();
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      hideError();
      var q = (input.value || '').trim();
      if (q.length < 1) {
        showError('Enter a SKU.');
        return;
      }
      var url = searchBase + (searchBase.indexOf('?') >= 0 ? '&' : '?') + 'q=' + encodeURIComponent(q) + '&exact=1';
      fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.success && data.product && data.product.id) {
            goToProduct(parseInt(data.product.id, 10));
          } else {
            showError((data && data.message) ? data.message : 'No product found with this SKU.');
          }
        })
        .catch(function () {
          showError('Could not verify SKU. Try again.');
        });
    });

    document.addEventListener('click', function (e) {
      if (!form.contains(e.target)) closeSuggestions();
    });
  })();
  </script>
  <?php
    $groupRaw = trim((string)($products['groupname'] ?? ''));
    $groupNameLower = strtolower($groupRaw);
    if ($groupRaw === '') {
        $groupNameDisplay = 'Default Group';
    } elseif (function_exists('mb_convert_case')) {
        $groupNameDisplay = mb_convert_case($groupRaw, MB_CASE_TITLE, 'UTF-8');
    } else {
        $groupNameDisplay = ucwords(strtolower($groupRaw));
    }
    $isBookProduct = strpos($groupNameLower, 'book') !== false;
    $authorRaw = trim((string)($products['author'] ?? ''));
    $permanentlyAvailableVal = (int)($products['permanently_available'] ?? 0);
    $permanentlyAvailableText = $permanentlyAvailableVal === 1 ? 'Yes' : 'No';
  ?>
  <!-- PRODUCT HEADER -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="flex gap-4">
      <!-- <div class="w-24 h-32 bg-white rounded-[10px] outline outline-2 outline-offset-[-2px] outline-amber-600 ">
        <img onclick="openImagePopup('<?php //echo $products['image']; ?>')" src="<?php //echo htmlspecialchars($products['image'] ?? 'https://placehold.co/90x120'); ?>" class="w-full h-full px-3 py-3 cursor-pointer" />
      </div> -->
      <div>
        <div onclick="openImagePopup('<?php echo htmlspecialchars($products['image'] ?? '', ENT_QUOTES); ?>')" class="flex h-32 w-24 shrink-0 items-center justify-center overflow-hidden rounded-[10px] bg-white outline outline-2 outline-offset-[-2px] outline-amber-600 cursor-pointer">
          <img src="<?php echo htmlspecialchars($products['image'] ?? 'https://placehold.co/90x120'); ?>" alt="" class="block h-full w-full max-h-full max-w-full object-contain cursor-pointer" />
        </div>
        <p class="inline-flex items-center gap-1.5 text-[11px] text-gray-600 mt-2 px-2 py-1 rounded-md bg-gray-50 border border-gray-200">
          <i class="fas fa-barcode text-amber-600" aria-hidden="true"></i>
          UPC: <span class="font-medium text-gray-800"><?php echo htmlspecialchars((string)($products['upc'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
        </p>
      </div>
      <div>
        <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-md font-medium">
          <?php echo htmlspecialchars($groupNameDisplay, ENT_QUOTES, 'UTF-8'); ?>
        </span> 
        <span class="text-xs ml-2 px-2 py-1 rounded-md bg-gray-100 text-gray-700 font-medium"><?php echo $products['item_code'] ?? ''; ?></span>
        
        <h2 class="font-semibold mt-2 text-lg">
          <?php echo htmlspecialchars($products['title'] ?? 'Product Title'); ?>
        </h2>
        <div class="mt-1 flex flex-wrap items-center gap-2">
          <p class="text-sm text-gray-500">SKU: <?php echo htmlspecialchars($products['sku'] ?? ''); ?></p>
          <button
            type="button"
            id="refreshProductApiBtn"
            data-item-code="<?php echo htmlspecialchars((string)($products['item_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border border-amber-300 bg-amber-50 text-amber-700 text-xs font-semibold hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1"
            onclick="updateProductProfileFromApi(this)">
            <i class="fas fa-sync-alt text-[11px]" aria-hidden="true"></i>
            Refresh from API
          </button>
        </div>
        <?php if ($isBookProduct): ?>
          <?php if ($authorRaw !== ''): ?>
            <p class="text-sm text-gray-600 mt-1">Author: <span class="font-medium text-gray-800"><?php echo htmlspecialchars($authorRaw, ENT_QUOTES, 'UTF-8'); ?></span></p>
          <?php endif; ?>
          <?php $publisherRaw = trim((string)($products['publisher'] ?? '')); ?>
          <?php if ($publisherRaw !== ''): ?>
            <p class="text-sm text-gray-600">Publisher: <span class="font-medium text-gray-800"><?php echo htmlspecialchars($publisherRaw, ENT_QUOTES, 'UTF-8'); ?></span></p>
          <?php endif; ?>
        <?php elseif ($authorRaw !== ''): ?>
          <p class="text-sm text-gray-600 mt-1">Artist: <span class="font-medium text-gray-800"><?php echo htmlspecialchars($authorRaw, ENT_QUOTES, 'UTF-8'); ?></span></p>
        <?php endif; ?>
        <div class="flex flex-wrap gap-2 mt-2">
          <?php foreach ($products['variants'] as $variant): 
            if(isset($variant['sku']) && !empty($variant['sku'])): ?>
            <span class="px-2 py-1 border rounded text-xs"><a href="<?php echo base_url('?page=products&action=detail&id='.$variant['id']); ?>"><?php echo $variant['sku']; ?></a></span>
          <?php endif; endforeach; ?>
        </div>
      </div>
    </div>
    <!-- Measures -->
    <div class="rounded-xl border border-orange-200 bg-gradient-to-br from-orange-50 to-amber-50 p-4">
      <h3 class="font-semibold mb-3 text-gray-800 flex items-center gap-2">
        <i class="fas fa-ruler-combined text-orange-600"></i>Measurements
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-sm">
        <div class="rounded-lg border border-orange-100 bg-white/80 px-3 py-2 flex items-center justify-between gap-3"><span class="text-gray-500 whitespace-nowrap"><i class="fas fa-expand-arrows-alt mr-1 text-orange-600"></i>Size</span><span class="font-semibold text-gray-800 text-right"><?php echo htmlspecialchars((string)($products['size'] ?? '—')); ?></span></div>
        <div class="rounded-lg border border-orange-100 bg-white/80 px-3 py-2 flex items-center justify-between gap-3"><span class="text-gray-500 whitespace-nowrap"><i class="fas fa-palette mr-1 text-orange-600"></i>Color</span><span class="font-semibold text-gray-800 text-right"><?php echo htmlspecialchars((string)($products['color'] ?? '—')); ?></span></div>
        <div class="rounded-lg border border-orange-100 bg-white/80 px-3 py-2 flex items-center justify-between gap-3"><span class="text-gray-500 whitespace-nowrap"><i class="fas fa-ruler-horizontal mr-1 text-orange-600"></i>Length</span><span class="font-semibold text-gray-800 text-right"><?php echo htmlspecialchars($products['prod_length'] ?  $products['prod_length'].' '.$products['length_unit'] : '—'); ?></span></div>
        <div class="rounded-lg border border-orange-100 bg-white/80 px-3 py-2 flex items-center justify-between gap-3"><span class="text-gray-500 whitespace-nowrap"><i class="fas fa-ruler-vertical mr-1 text-orange-600"></i>Height</span><span class="font-semibold text-gray-800 text-right"><?php echo htmlspecialchars($products['prod_height'] ? $products['prod_height'].' '.$products['length_unit'] : '—'); ?></span></div>
        <div class="rounded-lg border border-orange-100 bg-white/80 px-3 py-2 flex items-center justify-between gap-3"><span class="text-gray-500 whitespace-nowrap"><i class="fas fa-arrows-alt-h mr-1 text-orange-600"></i>Width</span><span class="font-semibold text-gray-800 text-right"><?php echo htmlspecialchars($products['prod_width'] ? $products['prod_width'].' '.$products['length_unit'] : '—'); ?></span></div>
        <div class="rounded-lg border border-orange-100 bg-white/80 px-3 py-2 flex items-center justify-between gap-3"><span class="text-gray-500 whitespace-nowrap"><i class="fas fa-weight mr-1 text-orange-600"></i>Weight</span><span class="font-semibold text-gray-800 text-right"><?php echo htmlspecialchars($products['product_weight'] ?  $products['product_weight'] .' ' .$products['product_weight_unit'] : '—'); ?></span></div>
      </div>
    </div>
  </div>
  <?php require __DIR__ . '/partials/jewelry_label_print_link.php'; ?>
  <!-- INVENTORY -->
  <!-- <div class="bg-white rounded-lg p-4 grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
    <div>
      <p class="text-gray-500 text-sm">Local Stock</p>
      <p class="text-xl font-semibold"><?php //echo htmlspecialchars($products['local_stock'] ?? '0'); ?></p>
    </div>
    <div>
      <p class="text-gray-500 text-sm">Committed</p>
      <p class="text-xl font-semibold">0</p>
    </div>
    <div>
      <p class="text-gray-500 text-sm">Available</p>
      <p class="text-xl font-semibold"><?php //echo htmlspecialchars($products['stocks']['current_stock'] ?? '0'); ?></p>
    </div>
    <div>
      <p class="text-gray-500 text-sm">In Purchase</p>
      <p class="text-xl font-semibold">0</p>
    </div>
    <div>
      <p class="text-gray-500 text-sm">Sold</p>
      <p class="text-xl font-semibold"></p>
    </div>
  </div> -->
  <!-- Inventory -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
  <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm space-y-4 col-span-2">
    <h3 class="font-semibold text-gray-800 flex items-center gap-2"><i class="fas fa-boxes text-amber-600"></i>Inventory</h3>
      <!-- Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5">
        <!-- Local Stock -->
        <div class="flex items-center justify-between border border-blue-100 bg-blue-50/50 rounded-lg p-3 relative">
          <div>
            <p class="text-sm text-gray-500">Local Stock</p>
            <p class="text-lg font-semibold leading-tight"><?php echo htmlspecialchars($products['local_stock'] ?? '0'); ?></p>
          </div>
          <div class="bg-blue-100 text-blue-600 h-8 w-8 rounded-md flex items-center justify-center text-sm">
            <i class="fas fa-box-open"></i>
          </div>
          <button class="absolute top-0 right-1 text-gray-500 hover:text-blue-600" onclick="openStockModal()">
            <i class="fas fa-edit text-sm"></i>
          </button>
        </div>
        <!-- Committed -->
        <div class="flex items-center justify-between border border-purple-100 bg-purple-50/50 rounded-lg p-3">
          <div>
            <p class="text-sm text-gray-500">Committed</p>
            <p class="text-lg font-semibold leading-tight"><?php echo htmlspecialchars($products['committed_stock'] ?? '0'); ?></p>
          </div>
          <div class="bg-purple-100 text-purple-600 h-8 w-8 rounded-md flex items-center justify-center text-sm">
            <i class="fas fa-link"></i>
          </div>
        </div>
        <!-- Available -->
        <div class="flex items-center justify-between border border-green-100 bg-green-50/50 rounded-lg p-3">
          <div>
            <p class="text-sm text-gray-500">Available</p>
            <p class="text-lg font-semibold leading-tight"><?php echo htmlspecialchars($products['available_stock'] ?? '0'); ?></p>
          </div>
          <div class="bg-green-100 text-green-600 h-8 w-8 rounded-md flex items-center justify-center text-sm">
            <i class="fas fa-check-circle"></i>
          </div>
        </div>
        <!-- In Purchase -->
        <div class="flex items-center justify-between border border-orange-100 bg-orange-50/50 rounded-lg p-3">
          <div>
            <p class="text-sm text-gray-500">In Purchase</p>
            <p class="text-lg font-semibold leading-tight"><?php echo count($products['in_purchase_list']); ?></p>
          </div>
          <div class="bg-orange-100 text-orange-600 h-8 w-8 rounded-md flex items-center justify-center text-sm">
            <i class="fas fa-shopping-cart"></i>
          </div>
        </div>
      </div>
      <!-- Number Sold -->
     <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 mt-2.5">
          <div class="flex items-center justify-between border border-gray-200 rounded-lg p-3 bg-white">
            <div>
              <p class="text-sm text-gray-500">Number Sold</p>
              <p class="text-lg font-semibold leading-tight"><?php echo htmlspecialchars($products['numsold'] ?? '0'); ?></p>
            </div>
            <div class="bg-gray-100 text-gray-600 h-8 w-8 rounded-md flex items-center justify-center text-sm">
              <i class="fas fa-chart-line"></i>
            </div>
          </div>

          <div class="flex items-center justify-between border border-red-100 rounded-lg p-3 bg-red-50 relative">
            <div>
              <p class="text-sm text-gray-500">Min Stock</p>
              <p class="text-lg font-semibold text-red-600 leading-tight"><?php echo htmlspecialchars($products['min_stock'] ?? '0'); ?></p>
            </div>
            <div class="bg-red-100 text-red-600 h-8 w-8 rounded-md flex items-center justify-center text-sm">
               <i class="fas fa-bell"></i>
            </div>
            <button class="absolute top-1 right-1 text-gray-400 hover:text-red-600" onclick="openMinMaxModal()">
              <i class="fas fa-pencil-alt text-[10px]"></i>
            </button>
          </div>

          <div class="flex items-center justify-between border border-blue-100 rounded-lg p-3 bg-blue-50 relative">
            <div>
              <p class="text-sm text-gray-500">Max Stock</p>
              <p class="text-lg font-semibold text-blue-600 leading-tight"><?php echo htmlspecialchars($products['max_stock'] ?? '0'); ?></p>
            </div>
            <div class="bg-blue-100 text-blue-600 h-8 w-8 rounded-md flex items-center justify-center text-sm">
               <i class="fas fa-shield-alt"></i>
            </div>
            <button class="absolute top-1 right-1 text-gray-400 hover:text-blue-600" onclick="openMinMaxModal()">
              <i class="fas fa-pencil-alt text-[10px]"></i>
            </button>
          </div>

          <div class="flex items-center justify-between border border-emerald-100 rounded-lg p-3 bg-emerald-50">
            <div>
              <p class="text-sm text-gray-500">Permanently Available</p>
              <p class="text-lg font-semibold text-emerald-700 leading-tight"><?php echo htmlspecialchars($permanentlyAvailableText); ?></p>
            </div>
            <div class="bg-emerald-100 text-emerald-700 h-8 w-8 rounded-md flex items-center justify-center text-sm">
               <i class="fas fa-check"></i>
            </div>
          </div>

          <div class="flex items-center justify-between border border-indigo-100 rounded-lg p-3 bg-indigo-50">
            <div>
              <p class="text-sm text-gray-500">Leadtime</p>
              <p class="text-lg font-semibold text-indigo-700 leading-tight"><?php echo htmlspecialchars((string)($products['leadtime'] ?? '0')); ?></p>
            </div>
            <div class="bg-indigo-100 text-indigo-700 h-8 w-8 rounded-md flex items-center justify-center text-sm">
               <i class="fas fa-hourglass-half"></i>
            </div>
          </div>

          <div class="flex items-center justify-between border border-cyan-100 rounded-lg p-3 bg-cyan-50">
            <div>
              <p class="text-sm text-gray-500">Instock Leadtime</p>
              <p class="text-lg font-semibold text-cyan-700 leading-tight"><?php echo htmlspecialchars((string)($products['instock_leadtime'] ?? '0')); ?></p>
            </div>
            <div class="bg-cyan-100 text-cyan-700 h-8 w-8 rounded-md flex items-center justify-center text-sm">
               <i class="fas fa-truck"></i>
            </div>
          </div>
        </div>
  </div>

  <!-- Price -->
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
      <h3 class="font-semibold mb-3 flex items-center gap-2 text-gray-800"><i class="fas fa-receipt text-emerald-600"></i>Price</h3>
      <div class="space-y-2.5 text-sm">
        <div class="flex justify-between items-center bg-gradient-to-r from-green-50 to-emerald-50 p-2.5 rounded-lg border border-green-100">
          <span class="text-gray-700"><i class="fas fa-tag mr-1 px-2 py-1 rounded text-xs text-green-600 bg-green-100"></i>Price India</span><span class="font-semibold text-gray-900">₹<?php echo htmlspecialchars($products['price_india'] ?? '0'); ?></span>
        </div>
        <div class="flex justify-between items-center bg-gradient-to-r from-green-50 to-emerald-50 p-2.5 rounded-lg border border-green-100">
          <span class="text-gray-700"><i class="fas fa-dollar-sign px-2 py-1 rounded text-xs mr-1 text-green-600 bg-green-100"></i>USD Price</span><span class="font-semibold text-gray-900">$<?php echo htmlspecialchars((string)($products['usd_price_inbound'] ?? '0')); ?></span>
        </div>
        
        <hr class="border-t">
        <div class="text-xs text-gray-500 mt-2 text-center">
          HSN: <?php echo htmlspecialchars($products['hsn'] ?? ''); ?> | GST: <?php echo htmlspecialchars($products['gst'] ?? ''); ?>%
        </div>
      </div>
    </div>
   </div>
  <!-- VENDORS + NOTES -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- Vendors -->
    <div class="bg-white rounded-lg p-4 md:col-span-2">
      <h3 class="font-semibold mb-3">Vendors</h3>
      <div class="space-y-3 text-sm">
        <?php 
          if(empty($products['vendors'])) {
            echo '<p class="text-gray-500">No vendors associated with this product.</p>';
          }
          else{
          foreach ($products['vendors'] as $vendor): ?>
          <div class="border rounded p-3">
            <div class="flex items-center gap-3 mb-2">
              <div class="w-10 h-10 bg-orange-200 rounded-full overflow-hidden flex items-center justify-center text-white">
                <i class="fas fa-store text-orange-500 text-lg"></i>
              </div>             
              <div class="ml-13 ">
                <b><?php echo htmlspecialchars($vendor['vendor_name'] ?? ''); ?></b>
                <p class="text-gray-500"><i class="fas fa-map-marker-alt text-xs mr-1"></i><?php echo htmlspecialchars($vendor['city'] ?? ''); ?>, <?php echo htmlspecialchars($vendor['state'] ?? ''); ?> <i class="fas fa-phone text-xs ml-2 mr-1"></i><?php echo htmlspecialchars($vendor['vendor_phone'] ?? ''); ?></p>
             </div>
            </div>
          </div>
        <?php endforeach;
        } ?>
        <!-- in purchase list -->
        <div class="border border-amber-500 rounded p-3 bg-yellow-50">
          <?php if(!empty($products['in_purchase_list'])): ?>
            <h4 class="font-semibold mb-2">Pending Purchase</h4>   
            <div class="flex flex-wrap gap-2 mb-2">         
            <?php foreach($products['in_purchase_list'] as $key => $purchase): ?>
              <a class="hover:bg-yellow-100 text-blue-600 cursor-pointer" href="<?php echo base_url('?page=purchase_orders&action=view&po_id=' . htmlspecialchars($key ?? '')); ?>"> <?php echo htmlspecialchars($purchase ?? ''); ?> </a>
            <?php endforeach; ?>   
            </div>        
          <?php else: ?>
            <p class="text-gray-500">No purchases are currently in progress for this product.</p>
          <?php endif; ?>
            
        </div>
      </div>
    </div>
    <!-- Notes -->
    <div class="bg-white rounded-lg p-4">
      <h3 class="font-semibold mb-3">Notes</h3>
      <textarea id="product-notes" class="w-full border rounded p-2 text-sm resize-none" rows="8"
        placeholder="Add notes here..."><?php echo htmlspecialchars($products['notes'] ?? ''); ?></textarea>
      <button class="mt-2 px-4 py-2 bg-blue-600 text-white rounded text-sm" onclick="saveProductNotes(<?php echo htmlspecialchars($products['id'] ?? 0); ?>)">Save Notes</button>
    </div>
  </div>
  <!-- STOCK TRANSACTIONS -->
  <div class="bg-white rounded-lg p-4 overflow-x-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold">Stock Transactions</h3>
          <a target="_blank" href="<?php echo base_url('?page=products&action=inventory_ledger&sku=' . htmlspecialchars($products['sku'] ?? '')); ?>"><i title="View stock movement history for this product" class="fas fa-exchange-alt text-orange-500"></i></a>
        </div>
    
    
    <!--search fileds-->
    <div class="flex flex-wrap gap-4 mb-4">
      <!-- <input type="text" id="searchRefId" placeholder="Search by Ref ID" class="border rounded p-2 text-sm"> -->
       <div>
            <label for="date" class="block text-sm font-medium text-gray-600 mb-1">Date Range</label>
            <input type="text" id="dateRange" name="dateRange" class="border rounded p-2 text-sm" placeholder="Select date range">
       </div>
        <script>
            $(function() {
                // Initialize date range picker: display format 'DD MMM YYYY' (e.g., 25 Dec 2015)
                $('#dateRange').daterangepicker({
                    autoUpdateInput: false,
                    locale: {
                        cancelLabel: 'Clear',
                        format: 'DD MMM YYYY'
                    }
                });
                $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format('DD MMM YYYY') + ' - ' + picker.endDate.format('DD MMM YYYY'));
                });
                $('#dateRange').on('cancel.daterangepicker', function(ev, picker) {
                    $(this).val('');
                });
            });
        </script>
       <div>
          <label for="searchType" class="block text-sm font-medium text-gray-600 mb-1">Transaction Type</label>  
          <select id="searchType" class="border rounded p-2 text-sm">
            <option value="">All Types</option>
            <option value="IN">Purchase</option>
            <option value="OUT">Sale</option>
            <option value="TRANSFER_IN">Transfer In</option>
            <option value="TRANSFER_OUT">Transfer Out</option>
            <option value="OPENING_STOCK">Opening Stock</option>
          </select>
      </div>   
      <div> 
          <label for="searchWarehouse" class="block text-sm font-medium text-gray-600 mb-1">Warehouse</label>  
          <select id="searchWarehouse" class="border rounded p-2 text-sm">
            <option value="">All Warehouses</option>
            <?php 
              if(!empty($products['warehouses'])) {
                foreach($products['warehouses'] as $warehouse) {
                  echo '<option value="' . htmlspecialchars($warehouse['id']) . '">' . htmlspecialchars($warehouse['name']) . '</option>';
                }
              }
            ?>
          </select>
      </div>
      <div class="flex items-end">
        <label for="searchType" class="block text-sm font-medium text-gray-600 mb-1 invisible"> </label>
        <button class="px-4 py-2 bg-orange-500 hover:bg-orange-700 text-white rounded text-sm" onclick="filterStockHistory()">Search</button>
      </div>
    </div>
    <table id="stockHistoryTable" class="min-w-full text-sm border">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 border">Date</th>
          <th class="p-2 border">Ref ID</th>
          <th class="p-2 border">Type</th>
          <th class="p-2 border">Stock In</th>
          <th class="p-2 border">Stock Out</th>
          <th class="p-2 border">Balance</th>
          <th class="p-2 border">Warehouse</th>
          <th class="p-2 border">Location</th>
        </tr>
      </thead>
      <tbody>
        <?php //print_r($products['warehouses']);
        if(!empty($products['stock_history'])) {
          foreach($products['stock_history'] as $history) {
            $fallbackType = ['IN' => 'Purchase', 'OUT' => 'Sale', 'TRANSFER_IN' => 'Transfer In', 'TRANSFER_OUT' => 'Transfer Out', 'OPENING_STOCK' => 'Opening Stock'];
            $fallbackIcon = ['IN' => 'fa-arrow-up', 'OUT' => 'fa-arrow-down', 'TRANSFER_IN' => 'fa-exchange-alt', 'TRANSFER_OUT' => 'fa-exchange-alt', 'OPENING_STOCK' => 'fa-boxes'];
            $fallbackColor = ['IN' => 'text-green-600', 'OUT' => 'text-red-600', 'TRANSFER_IN' => 'text-blue-600', 'TRANSFER_OUT' => 'text-blue-600', 'OPENING_STOCK' => 'text-emerald-700'];
            $mt = $history['movement_type'] ?? '';
            $dispLabel = $history['ledger_type'] ?? ($fallbackType[$mt] ?? $mt);
            $dispIcon = $history['ledger_icon'] ?? ($fallbackIcon[$mt] ?? '');
            $dispColor = $history['ledger_color_class'] ?? ($fallbackColor[$mt] ?? '');
            ?>
            <tr class="text-center">
              <td class="p-2 border"><?php echo htmlspecialchars(date('d M Y', strtotime($history['created_at'] ?? ''))); ?></td>
              <td class="p-2 border"><?php echo htmlspecialchars($history['ref_id'] ?? ''); ?></td>
              <td class="p-2 border <?php echo htmlspecialchars($dispColor); ?>">
                <i class="fas <?php echo htmlspecialchars($dispIcon); ?>"></i>
                <?php echo htmlspecialchars($dispLabel); ?>
              </td>
              <td class="p-2 border"><?php echo htmlspecialchars(in_array(($history['movement_type'] ?? ''), ['IN','OPENING_STOCK'], true) ? $history['quantity'] : ''); ?></td>
              <td class="p-2 border"><?php echo htmlspecialchars($history['movement_type'] == 'OUT' ? $history['quantity'] : ''); ?></td>
              <td class="p-2 border"><?php echo htmlspecialchars($history['running_stock'] ?? '0'); ?></td>
              <td class="p-2 border"><?php echo htmlspecialchars($history['warehouse_name'] ?? ''); ?></td>
              <td class="p-2 border"><?php echo htmlspecialchars($history['location'] ?? ''); ?></td>
            </tr> 
            <?php
          }
        } else {
          echo '<tr><td colspan="9" class="p-4 text-center text-gray-500">No stock transactions found.</td></tr>';
        }
        ?>
        
      </tbody>
    </table>
    <!-- Pagination -->
    <div id="paginationContainer" class="flex justify-center items-center gap-2 mt-4">
      <button id="prevBtn" class="px-3 py-1 bg-gray-300 text-gray-700 rounded disabled:opacity-50" onclick="previousPage()">Previous</button>
      <span id="pageInfo" class="text-sm text-gray-600">Page 1</span>
      <button id="nextBtn" class="px-3 py-1 bg-gray-300 text-gray-700 rounded disabled:opacity-50" onclick="nextPage()">Next</button>
    </div>
  </div>
</div>
<!-- Image Popup -->
<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeImagePopup(event)">
    <div class="bg-white p-4 rounded-md max-w-3xl max-h-3xl relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <img id="popupImage" class="max-w-full max-h-[80vh] rounded" src="" alt="Image Preview">
    </div>
</div>
<!-- <div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
  <div class="bg-white p-4 rounded max-h-screen max-w-lg flex flex-col items-center">
    <img id="popupImage" src="" alt="Product Image" class="max-w-full h-full object-contain" />
    <button onclick="document.getElementById('imagePopup').classList.add('hidden')" class="mt-2 px-4 py-2 bg-red-600 text-white rounded">Close</button>
  </div>
</div> -->
<div id="minMaxModal" class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6 relative">
        <button onclick="closeMinMaxModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700">✕</button>
        
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Update Stock Thresholds</h2>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Minimum Stock Level</label>
                <input type="number" id="input_min_stock" 
                       value="<?php echo htmlspecialchars($products['min_stock'] ?? '0'); ?>"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Maximum Stock Level</label>
                <input type="number" id="input_max_stock" 
                       value="<?php echo htmlspecialchars($products['max_stock'] ?? '0'); ?>"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 outline-none">
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
            <button onclick="closeMinMaxModal()" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Cancel</button>
            <button onclick="submitMinMaxUpdate()" class="px-4 py-2 text-sm bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600">Update Limits</button>
        </div>
    </div>
</div>
<!-- Stock Adjustment Card -->
<!-- Overlay -->
<div id="stockModal" class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
  <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl p-8 relative transform scale-95 transition-transform duration-300">
    <button onclick="closeStockModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-xl">
      ✕
    </button>
    
    <div class="mb-6">
      <h2 class="text-xl font-semibold text-gray-800">Stock Adjustment</h2>
      <p class="text-sm text-gray-500 mt-1">
        Adjust product inventory for selected warehouse and location
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
      <div>
        <label class="block text-sm font-medium text-gray-600 mb-1">
          Adjustment Type
        </label>
        <select id="adj_type" class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
          <option value="OUT">Decrease</option>
          <option value="IN">Increase</option>
        </select>
        <input type="hidden" name="user_id" id="current_user" value="<?php echo $_SESSION['user']['id'] ?? ''; ?>">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-600 mb-1">
          Warehouse
        </label>
        <select id="adj_warehouse" class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
          <?php foreach ($products['warehouses'] as $value): 
            $selected = ($value['id'] == ($products['stock_movements']['warehouse_id'] ?? '')) ? 'selected' : '';
          ?> 
            <option value="<?php echo $value['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($value['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-600 mb-1">
          Location
        </label>
        <input type="text" id="adj_location" 
               value="<?php echo htmlspecialchars($products['stock_movements']['location'] ?? ''); ?>"
               class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-600 mb-1">
          Quantity
        </label>
        <input type="number" id="adj_quantity" 
               value="1"
               class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
      </div>
    </div>

    <div class="mt-6">
      <label class="block text-sm font-medium text-gray-600 mb-1">
        Reason
      </label>
      <textarea id="adj_reason" rows="4"
        class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-3 text-sm focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
    </div>

    <div class="flex justify-end gap-4 mt-8">
      <button onclick="closeStockModal()"
        class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-600 hover:bg-gray-100">
        Cancel
      </button>
      <button onclick="submitStockAdjustment()"
        class="px-6 py-2.5 rounded-xl bg-orange-500 text-white font-semibold
               hover:bg-orange-600 active:scale-95 transition">
        Save Adjustment
      </button>
    </div>
  </div>
</div>
<!--Stock Adjustment Card Ends -->
<div id="profileStatusModal" class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
  <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-5 relative">
    <button type="button" onclick="closeProfileStatusModal()" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700">✕</button>
    <div class="flex items-start gap-3">
      <div id="profileStatusModalIcon" class="h-9 w-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
        <i class="fas fa-info-circle"></i>
      </div>
      <div class="min-w-0">
        <h3 id="profileStatusModalTitle" class="text-base font-semibold text-gray-800">Notice</h3>
        <p id="profileStatusModalMessage" class="text-sm text-gray-600 mt-1 leading-relaxed"></p>
      </div>
    </div>
    <div class="mt-5 flex justify-end">
      <button id="profileStatusModalOkBtn" type="button" onclick="closeProfileStatusModal()" class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold">OK</button>
    </div>
  </div>
</div>
<script>
  var profileStatusReloadOnClose = false;

  function showProfileStatusModal(message, type, reloadOnClose) {
    var modal = document.getElementById('profileStatusModal');
    var titleEl = document.getElementById('profileStatusModalTitle');
    var msgEl = document.getElementById('profileStatusModalMessage');
    var iconWrap = document.getElementById('profileStatusModalIcon');
    var iconEl = iconWrap ? iconWrap.querySelector('i') : null;

    profileStatusReloadOnClose = !!reloadOnClose;
    if (titleEl) titleEl.textContent = (type === 'success') ? 'Success' : ((type === 'error') ? 'Error' : 'Notice');
    if (msgEl) msgEl.textContent = message || '';

    if (iconWrap && iconEl) {
      iconWrap.className = 'h-9 w-9 rounded-full flex items-center justify-center shrink-0';
      if (type === 'success') {
        iconWrap.classList.add('bg-emerald-100', 'text-emerald-700');
        iconEl.className = 'fas fa-check-circle';
      } else if (type === 'error') {
        iconWrap.classList.add('bg-red-100', 'text-red-700');
        iconEl.className = 'fas fa-exclamation-circle';
      } else {
        iconWrap.classList.add('bg-blue-100', 'text-blue-700');
        iconEl.className = 'fas fa-info-circle';
      }
    }

    if (modal) modal.classList.remove('hidden');
  }

  function closeProfileStatusModal() {
    var modal = document.getElementById('profileStatusModal');
    if (modal) modal.classList.add('hidden');
    if (profileStatusReloadOnClose) {
      profileStatusReloadOnClose = false;
      window.location.reload();
    }
  }

  async function updateProductProfileFromApi(btn) {
    var itemCode = (btn && btn.dataset && btn.dataset.itemCode) ? String(btn.dataset.itemCode).trim() : '';
    if (!itemCode) {
      showProfileStatusModal('Item code is missing for this product.', 'error', false);
      return;
    }

    var oldHtml = btn.innerHTML;
    btn.disabled = true;
    btn.classList.add('opacity-70', 'cursor-not-allowed');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin text-[11px]" aria-hidden="true"></i> Updating...';

    try {
      var res = await fetch('index.php?page=products&action=update_api_call&itemCode=' + encodeURIComponent(itemCode), {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      });
      var data = await res.json();
      if (data && data.success) {
        showProfileStatusModal('Product updated successfully from API.', 'success', true);
        return;
      }
      showProfileStatusModal('Update failed: ' + ((data && data.message) ? data.message : 'Unknown error'), 'error', false);
    } catch (e) {
      showProfileStatusModal('An error occurred while updating this product.', 'error', false);
    } finally {
      btn.disabled = false;
      btn.classList.remove('opacity-70', 'cursor-not-allowed');
      btn.innerHTML = oldHtml;
    }
  }

  function openStockModal() {
    document.getElementById('stockModal').classList.remove('hidden');
  }
  function closeStockModal() {
    document.getElementById('stockModal').classList.add('hidden');
  }
function openImagePopup(imageUrl) {
    const popup = document.getElementById('imagePopup');
    const popupImage = document.getElementById('popupImage');
    popupImage.src = imageUrl;
    popup.classList.remove('hidden');
}
function closeImagePopup() {
    const popup = document.getElementById('imagePopup');
    popup.classList.add('hidden');
}
  function saveProductNotes(productId) {
    const notes = document.getElementById('product-notes').value;
    fetch(`index.php?page=products&action=save_product_notes`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ product_id: productId, notes: notes })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showAlert('Notes saved successfully!');
      } else {
        alert('Failed to save notes.');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while saving notes.');
    });
  }
  let currentPage = 1;
  let totalPages = 1;
  const itemsPerPage = 10;
  let lastFilterParams = {};
  function filterStockHistory(page = 1) {
    const dateRange = document.getElementById('dateRange').value;
    const type = document.getElementById('searchType').value;
    const warehouse = document.getElementById('searchWarehouse').value;
    // Parse date range
    let startDate = '';
    let endDate = '';
    if (dateRange) {
      const [start, end] = dateRange.split(' - ');
      // Convert 'DD MMM YYYY' to 'YYYY-MM-DD'
      const startMoment = moment(start, 'DD MMM YYYY');
      const endMoment = moment(end, 'DD MMM YYYY');
      startDate = startMoment.format('YYYY-MM-DD');
      endDate = endMoment.format('YYYY-MM-DD');
    }
    // Store filter params for pagination (use page_no to avoid colliding with router 'page')
    lastFilterParams = {
      product_id: <?php echo htmlspecialchars($products['id'] ?? 0); ?>,
      sku: '<?php echo htmlspecialchars($products['sku'] ?? ''); ?>',
      start_date: startDate,
      end_date: endDate,
      type: type,
      warehouse: warehouse,
      page_no: page,
      limit: itemsPerPage
    };
    const params = new URLSearchParams(lastFilterParams);
    const url = `index.php?page=products&action=get_filtered_stock_history&${params.toString()}`;
    console.log('Fetching stock history from:', url);
    fetch(url)
      .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text().then(text => {
          console.log('Raw response:', text);
          return JSON.parse(text);
        });
      })
      .then(data => {
        if (data.success && data.records) {
          const tbody = document.querySelector('#stockHistoryTable tbody');
          tbody.innerHTML = '';
          if (data.records.length > 0) {
            data.records.forEach(history => {
              const row = document.createElement('tr');
              row.className = 'text-center';
              
              row.innerHTML = `
                <td class="p-2 border">${history.formatted_date || history.created_at}</td>
                <td class="p-2 border">${history.ref_id || ''}</td>
                <td class="p-2 border ${history.textColor}">
                  <i class="fas ${history.icon}"></i>
                  ${history.type}
                </td>
                <td class="p-2 border">${(history.movement_type === 'IN' || history.movement_type === 'OPENING_STOCK') ? history.quantity : ''}</td>
                <td class="p-2 border">${history.movement_type === 'OUT' ? history.quantity : ''}</td>
                <td class="p-2 border">${history.running_stock || '0'}</td>
                <td class="p-2 border">${history.warehouse_name || ''}</td>
                <td class="p-2 border">${history.location || ''}</td>
              `;
              tbody.appendChild(row);
            });
          } else {
            tbody.innerHTML = '<tr><td colspan="9" class="p-4 text-center text-gray-500">No stock transactions found.</td></tr>';
          }
          // Update pagination
          currentPage = page;
          totalPages = Math.ceil((data.total || 0) / itemsPerPage);
          updatePaginationButtons();
          document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
        } else {
          console.error('API Error:', data);
          alert('Error fetching data: ' + (data.message || 'Unknown error'));
        }
      })
      .catch(error => {
        //console.error('Error fetching filtered stock history:', error);
        alert('Failed to fetch stock history: ' + error.message);
      });
  }
  function updatePaginationButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages || totalPages === 0;
  }
  function previousPage() {
    if (currentPage > 1) {
      filterStockHistory(currentPage - 1);
    }
  }
  function nextPage() {
    if (currentPage < totalPages) {
      filterStockHistory(currentPage + 1);
    }
  }
  // Load initial data on page load
  document.addEventListener('DOMContentLoaded', function() {
    filterStockHistory(1);
  });
</script>
<script>
  function submitStockAdjustment() {
    // 1. Collect Data
    const adjustmentData = {
        product_id: <?php echo json_encode($products['id'] ?? 0); ?>,
        user_id: document.getElementById('current_user').value,
        sku: <?php echo json_encode($products['sku'] ?? ''); ?>,
        type: document.getElementById('adj_type').value,
        warehouse_id: document.getElementById('adj_warehouse').value,
        location: document.getElementById('adj_location').value,
        quantity: document.getElementById('adj_quantity').value,
        reason: document.getElementById('adj_reason').value
    };

    // 2. Simple Validation
    if (!adjustmentData.quantity || adjustmentData.quantity <= 0) {
        alert("Please enter a valid quantity.");
        return;
    }

    // 3. Send to Server
    fetch(`index.php?page=products&action=save_stock_adjustment`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(adjustmentData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Success Popup
            alert('✅ Success: Stock has been updated!'); 
            
            // Close the modal
            closeStockModal();
            
            // Refresh the history table immediately
            if (typeof filterStockHistory === "function") {
                filterStockHistory(1);
            }
            
            // Optional: If you want the top stock counters to update, 
            // you might need a page reload or another JS update function
            location.reload(); 
        } else {
            alert('❌ Failed: ' + (data.message || 'Failed to save adjustment.'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An unexpected error occurred. Please check the console.');
    });
}
</script>
<script>
    function openMinMaxModal() {
    document.getElementById('minMaxModal').classList.remove('hidden');
}

function closeMinMaxModal() {
    document.getElementById('minMaxModal').classList.add('hidden');
}

function submitMinMaxUpdate() {
    const data = {
        product_id: <?php echo json_encode($products['id'] ?? 0); ?>,
        min_stock: document.getElementById('input_min_stock').value,
        max_stock: document.getElementById('input_max_stock').value
    };

    fetch('index.php?page=products&action=update_stock_limits', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(res => {
        if(res.success) {
            alert('✅ Limits updated!');
            location.reload(); // Reload to see changes
        } else {
            alert('❌ Failed: ' + res.message);
        }
    });
}
</script>