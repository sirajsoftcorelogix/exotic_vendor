$(function () {
  let productApiCache = {};
  let currentPage = 1;
  const perPage = 12;
  let currentCategory = '';

  let isLoading = false;
  let hasMore = true;

  let loadedKeys = new Set();
  let productsByKey = new Map();

  const $cards = $('#productsCards');
  const $scrollWrapper = $cards.parent();

  // ────────────────────────────────────────────────
  // HELPERS (needed for products & modal)
  // ────────────────────────────────────────────────
  function formatPrice(price) {
    const p = parseFloat(price || 0);
    return '₹ ' + p.toLocaleString('en-IN', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function showLoader(show) {
    if (show) {
      if (!$('#productsLoader').length) {
        $scrollWrapper.append(
          '<div id="productsLoader" class="text-center text-xs text-gray-500 py-4">Loading...</div>'
        );
      }
    } else {
      $('#productsLoader').remove();
    }
  }

  function getProductKey(p) {
    return (p.id != null && p.id !== '') ? `id:${p.id}` : `code:${p.item_code || ''}`;
  }

  function isMeaningful(val) {
    if (val === null || val === undefined) return false;
    const s = String(val).trim();
    if (s === '' || s.toLowerCase() === 'n/a') return false;
    const n = Number(s);
    if (!Number.isNaN(n) && n === 0) return false;
    return true;
  }

  function addRow(label, value) {
    return `
      <div class="text-gray-600">${label}</div>
      <div class="text-gray-400">:</div>
      <div class="font-medium text-gray-800">${value}</div>
    `;
  }

  // ────────────────────────────────────────────────
  // MODAL HELPERS (kept unchanged)
  // ────────────────────────────────────────────────
  const $modal = $('#productModal');
  const $overlay = $('#productModalOverlay');
  const $close = $('#productModalClose');
  const $closeBtn = $('#pmCloseBtn');

  const $pmQtyDec = $('#pmQtyDec');
  const $pmQtyInc = $('#pmQtyInc');
  const $pmQtyVal = $('#pmQtyVal');

  let activeModalKey = null;

  function openModal() {
    $modal.removeClass('hidden');
    $('body').addClass('overflow-hidden');
  }

  function closeModal() {
    $modal.addClass('hidden');
    $('body').removeClass('overflow-hidden');
    activeModalKey = null;
  }

  $overlay.on('click', closeModal);
  $close.on('click', closeModal);
  $closeBtn.on('click', closeModal);
  function setModalQty(qty) {
    const q = Math.max(1, parseInt(qty || 1, 10));

    $pmQtyVal.text(q);

    // IMPORTANT: update hidden field
    $('#modal_qty').val(q);

  }

  function getModalQty() {
    return Math.max(1, parseInt($pmQtyVal.text() || '1', 10));
  }

  $pmQtyDec.on('click', function () {
    setModalQty(getModalQty() - 1);
  });

  $pmQtyInc.on('click', function () {
    setModalQty(getModalQty() + 1);
  });

  function renderProductModal(p, key) {
    activeModalKey = key;
    $('#pmAddons').html('');
    $('#pmAddonsWrapper').addClass('hidden');
    const title = (p.title || '').replace(/\s+/g, ' ').trim();
    $('#pmTitle').text(title || 'Product');

    const imgSrc = p.image || 'https://dummyimage.com/500x500/e5e7eb/6b7280&text=No+Image';
    $('#pmImage').attr('src', imgSrc).attr('alt', title || 'Product');
    $('#modal_product_code').val(p.item_code || p.code || p.id || '');

    //  SET QTY
    setModalQty(1);

    // SET VARIATION (FINAL FIX)
    let size = (p.size && p.size !== '0') ? String(p.size).trim() : '';
    let color = (p.color && p.color !== '0') ? String(p.color).trim() : '';

    let variation = '';
    //  ADDONS UI (FINAL CLEAN)

    let addons = [];

    if (p && p.addon_options && Array.isArray(p.addon_options.default_options)) {
      addons = p.addon_options.default_options;
    }

    if (addons.length > 0) {

      let addonsHtml = '';

      addons.forEach(opt => {

        //  CONDITION
        let isExpress = (opt.title || '').toLowerCase().includes('express');

        let bgClass = isExpress ? 'bg-green-100' : 'bg-[#f5f5f5]';
        let textColor = isExpress ? 'text-green-900' : 'text-gray-800';
        let priceColor = isExpress ? 'text-green-900' : 'text-gray-700';

        addonsHtml += `
    <label class="flex items-center justify-between gap-2 rounded-lg ${bgClass} px-3 py-2 cursor-pointer">

      <div class="flex items-center gap-2">

        <input type="checkbox"
               class="addon-checkbox h-4 w-4 ${isExpress ? 'text-green-600' : 'text-gray-600'} border-gray-300 rounded"
               data-entry="${opt.cart_entry}">

        <div>
          <div class="text-[10px] ${textColor} leading-tight">
            ${opt.title}
          </div>
        </div>

      </div>

      <div class="text-[11px] font-semibold ${priceColor} whitespace-nowrap">
        ₹ ${parseFloat(opt.price).toFixed(2)}
      </div>

    </label>
  `;
      });

      $('#pmAddons').html(addonsHtml);
      $('#pmAddonsWrapper').removeClass('hidden');

    } else {
      $('#pmAddonsWrapper').addClass('hidden');
      $('#pmAddons').html('');
    }
    // build variation properly
    if (!size && color) {
      variation = ':' + color;
    } else if (size && !color) {
      variation = size + ':';
    } else if (size && color) {
      variation = size + ':' + color;
    }

    // fallback (VERY IMPORTANT for your case)
    if (!variation && p.color) {
      variation = ':' + p.color;
    }

    // final set
    $('#modal_variation').val(variation);


    const badges = [];

    if (isMeaningful(p.item_code)) {
      badges.push(`<span class="rounded-md bg-orange-100 px-2 py-1 text-[10px] text-orange-700">Code: ${p.item_code}</span>`);
    }

    // SKU = same as code (your API)
    if (isMeaningful(p.item_code)) {
      badges.push(`<span class="rounded-md bg-blue-100 px-2 py-1 text-[10px] text-blue-700">SKU: ${p.item_code}</span>`);
    }

    //  MAIN CATEGORY (RIGHT SIDE STYLE)
    if (isMeaningful(p.maincategory)) {
      badges.push(`<span class="rounded-md bg-gray-100 px-2 py-1 text-[10px] text-gray-700 capitalize">${p.maincategory}</span>`);
    }

    //  STOCK
    if (isMeaningful(p.stock_qty)) {
      badges.push(`<span class="rounded-md bg-green-100 px-2 py-1 text-[10px] text-green-700">Stock: ${p.stock_qty}</span>`);
    }

    $('#pmBadges').html(badges.join(''));

    let html = '';

    if (isMeaningful(p.price)) {
      html += addRow('Price', `₹ ${Number(p.price).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    }

    if (isMeaningful(p.cost_price)) {
      html += addRow('Cost Price', `₹ ${Number(p.cost_price).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    }
    //  DIMENSIONS
    if (isMeaningful(p.dimensions)) {
      html += addRow('Dimensions', p.dimensions);
    }

    //  WEIGHT
    if (isMeaningful(p.weight)) {
      html += addRow('Weight', p.weight + ' kg');
    }
    if (isMeaningful(p.hsn)) html += addRow('HSN', p.hsn);
    if (isMeaningful(p.color)) html += addRow('Color', p.color);
    if (isMeaningful(p.size)) html += addRow('Size', p.size);
    if (isMeaningful(p.material)) html += addRow('Material', p.material);

    const wt = isMeaningful(p.product_weight) ? p.product_weight : null;
    const wtu = isMeaningful(p.product_weight_unit) ? p.product_weight_unit : '';
    if (wt) html += addRow('Weight', `${wt} ${wtu}`.trim());

    const h = isMeaningful(p.prod_height) ? p.prod_height : null;
    const w = isMeaningful(p.prod_width) ? p.prod_width : null;
    const l = isMeaningful(p.prod_length) ? p.prod_length : null;
    const dimUnit = isMeaningful(p.length_unit) ? p.length_unit : '';

    if (h || w || l) {
      const parts = [h, w, l].filter(v => isMeaningful(v));
      html += addRow('Dimensions', `${parts.join(' × ')} ${dimUnit}`.trim());
    }

    if (!html) {
      html = `<div class="col-span-3 text-xs text-gray-500">No additional details available.</div>`;
    }

    $('#pmDetails').html(html);
    setModalQty(1);
  }

  // ────────────────────────────────────────────────
  // PRODUCTS RENDERING + FETCH (unchanged)
  // ────────────────────────────────────────────────
  function renderProducts(products, append = false) {
    if (!append) {
      $cards.empty();
      loadedKeys.clear();
      productsByKey.clear();
    }

    if (!products || products.length === 0) {
      if (!append) {
        $cards.append(
          '<div class="col-span-full text-center text-xs text-gray-500 py-4">No products found.</div>'
        );
      }
      return;
    }

    products.forEach(function (p) {
      const key = getProductKey(p);
      if (loadedKeys.has(key)) return;

      loadedKeys.add(key);
      productsByKey.set(key, p);

      const imgSrc = p.image || 'https://dummyimage.com/200x200/e5e7eb/6b7280&text=No+Image';
      const safeTitle = (p.title || 'Product').replace(/"/g, '&quot;');

      const cardHtml = `
        <div class="product-card cursor-pointer rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition"
             data-pkey="${key}"
data-code="${p.item_code}">
          <div class="bg-gray-50 p-2">
            <img src="${imgSrc}" alt="${safeTitle}"
                 class="mx-auto h-56 lg:h-52 xl:h-48 object-contain" />
          </div>

          <div class="px-3 pb-3 pt-2 text-xs">
            <div class="text-[9.5px] text-gray-800 leading-snug line-clamp-2">
              ${(p.title || '').replace(/\s+/g, ' ').trim()}
            </div>

            <div class="mt-2 flex items-center gap-1 whitespace-nowrap">
              <span class="rounded-md bg-orange-100 px-1.5 py-0.5 text-[9px] text-orange-700">
                ${p.item_code || ''}
              </span>
              <span class="rounded-md bg-green-100 px-1.5 py-0.5 text-[9px] text-green-700">
                Stock : ${p.stock_qty != null ? p.stock_qty : '-'}
              </span>
              <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[9px] text-gray-700">
                ${formatPrice(p.price)}
              </span>
            </div>
          </div>
        </div>
      `;

      $cards.append(cardHtml);
    });
  }

  function fetchProducts(page = 1, append = false) {
    if (isLoading) return;
    if (append && !hasMore) return;

    isLoading = true;
    showLoader(true);
    const sortBy = $('#sortBy').val();
    const minPrice = $('#minPrice').val();
    const maxPrice = $('#maxPrice').val();
    const stockFilter = $('#stockFilter').val();
    // Search input matches by SKU and/or product name
    const productCode = $('#searchName').val();
    const productName = $('#searchName').val();
    const requestedPage = page;

    $.ajax({
      url: '?page=pos_register&action=products-ajax',
      type: 'GET',
      dataType: 'json',
      data: {
        page_no: requestedPage,
        per_page: perPage,
        category: currentCategory,
        product_code: productCode,
        product_name: productName,
        sort_by: sortBy,
        min_price: minPrice,
        max_price: maxPrice,
        stock_filter: stockFilter
      },
      success: function (res) {
        const rows = res.data || [];
        currentPage = requestedPage;

        if (res.total_pages != null) {
          hasMore = currentPage < parseInt(res.total_pages, 10);
        } else {
          hasMore = rows.length === perPage;
        }

        renderProducts(rows, append);
      },
      error: function (xhr, status, err) {
        console.error('Error loading products', err);
      },
      complete: function () {
        isLoading = false;
        showLoader(false);
      }
    });
  }

  function resetAndLoad() {
    currentPage = 1;
    hasMore = true;
    fetchProducts(1, false);
    $scrollWrapper.scrollTop(0);
  }
  $('#applyFilterBtn').on('click', function () {
    resetAndLoad();
  });

  $('#resetFilterBtn').on('click', function () {
    $('#sortBy').val('');
    $('#minPrice').val('');
    $('#maxPrice').val('');
    $('#stockFilter').val('');
    resetAndLoad();
  });
  // ────────────────────────────────────────────────
  // EVENT LISTENERS (only products & modal)
  // ────────────────────────────────────────────────

  $cards.on('click', '.product-card1', function () {
    const key = $(this).data('pkey');
    const p = productsByKey.get(key);
    if (!p) return;

    renderProductModal(p, key);
    openModal();
  });

  $('[data-category]').on('click', function () {
    $('[data-category]')
      .removeClass('bg-orange-600 text-white')
      .addClass('border border-slate-200 bg-white text-slate-700')
      .find('svg')
      .removeClass('text-white')
      .addClass('text-slate-500');

    $(this)
      .addClass('bg-orange-600 text-white')
      .removeClass('bg-white text-slate-700')
      .find('svg')
      .removeClass('text-slate-500')
      .addClass('text-white');

    currentCategory = $(this).data('category') || '';
    resetAndLoad();
  });
  $(document).on('click', '.product-card', function () {

    let code = $(this).data('code');
    if (!code) return;
    openProductModalByCode(code);
  });
  function openProductModalByCode(code) {
    if (!code) return;
    openModal();

    //  CACHE HIT
    if (productApiCache[code]) {
      renderProductModal(productApiCache[code], code);
      return;
    }

    //  LOADING STATE
    $('#pmTitle').text('Loading...');
    $('#pmDetails').html('Loading...');

    $.ajax({
      url: '?page=pos_register&action=get-product-api',
      type: 'GET',
      data: { code: code },
      dataType: 'json',
      success: function (res) {
        let p = res.data || {};
        //  SAVE CACHE
        productApiCache[code] = p;
        //  USE EXISTING MODAL FUNCTION
        renderProductModal(p, code);
      }
    });
  }
  function checkAvailabilityAndMaybeOpen(product) {
    if (!product) return;
    const productId = product.id != null ? String(product.id) : '';
    const itemCode = product.item_code != null ? String(product.item_code) : '';
    const sku = product.sku != null ? String(product.sku) : '';
    const codeForPopup = itemCode || sku;
    if (!codeForPopup) return;
    const url = '?page=pos_register&action=product-availability'
      + (productId ? ('&product_id=' + encodeURIComponent(productId)) : ('&q=' + encodeURIComponent(itemCode || sku)));
    fetch(url, {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data && data.success && data.current_available === false && data.message) {
          alert(data.message);
          openProductModalByCode(codeForPopup);
        }
      })
      .catch(function () {
        // keep search flow functional even if availability endpoint fails
      });
  }
  function renderModalData(p) {

    $('#pmTitle').text(p.title || 'Product');
    $('#pmImage').attr('src', p.image || '');

    $('#pmDetails').html(`
        <div>Price</div><div>:</div><div>₹ ${p.price || 0}</div>
        <div>Material</div><div>:</div><div>${p.material || '-'}</div>
        <div>Size</div><div>:</div><div>${p.size || '-'}</div>
        <div>Color</div><div>:</div><div>${p.color || '-'}</div>
    `);

    $('#modal_product_code').val(p.item_code || '');

    // ADDONS
    let addonsHtml = '';

    if (p.addon_options) {
      alert("sdsdsdsd")
      p.addon_options.default_options.forEach(opt => {
        addonsHtml += `
                <label class="flex justify-between border px-3 py-2 rounded-lg">
                    <div>
                        <input type="checkbox" class="addon-checkbox"
                               data-entry="${opt.cart_entry}">
                        ${opt.title}
                    </div>
                    <div>₹ ${opt.price}</div>
                </label>
            `;
      });

      $('#pmAddons').html(addonsHtml);
      $('#pmAddonsWrapper').removeClass('hidden');

    } else {
      $('#pmAddonsWrapper').addClass('hidden');
    }
  }
  //  HANDLE ADDON SELECTION
  $(document).on('change', '.addon-checkbox', function () {

    let selected = [];

    $('.addon-checkbox:checked').each(function () {
      let entry = $(this).data('entry');

      if (entry) {
        selected.push(entry);
      }
    });

    //  JOIN WITH PIPE
    let optionsStr = selected.join('|');

    console.log("FINAL OPTIONS:", optionsStr);

    //  SET HIDDEN INPUT
    $('#modal_options').val(optionsStr);
  });
  let searchTimeout = null;
  const $searchName = $('#searchName');
  const $skuSuggest = $('#skuSuggest');
  const $searchErr = $('#posSkuSearchError');
  const skuSearchBase = '?page=products&action=search_product';
  let activeSuggestRequest = 0;

  function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, function (c) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
    });
  }

  function hideSuggest() {
    $searchName.attr('aria-expanded', 'false');
    $skuSuggest.addClass('hidden').empty();
  }

  function hideSearchError() {
    if ($searchErr.length) {
      $searchErr.addClass('hidden').text('');
    }
  }

  function showSearchError(msg) {
    if ($searchErr.length) {
      $searchErr.text(msg || 'No product found with this SKU.').removeClass('hidden');
    }
  }

  function renderSuggest(rows) {
    if (!rows || rows.length === 0) {
      hideSuggest();
      return;
    }

    const html = rows.slice(0, 12).map(function (p) {
      const sku = p.sku || p.item_code || p.code || '';
      const itemCode = p.item_code || p.itemcode || '';
      const title = p.title || p.name || '';
      const trimmedTitle = title.length > 72 ? (title.slice(0, 69) + '...') : title;
      return `
        <button type="button"
          class="w-full text-left px-3 py-2 hover:bg-slate-50 transition border-b border-slate-100 last:border-0"
          data-sku="${escapeHtml(sku)}"
          data-item-code="${escapeHtml(itemCode)}">
          <div class="min-w-0">
            <div class="text-xs font-semibold text-slate-800 truncate">${escapeHtml(sku)}</div>
            <div class="text-[11px] text-slate-500 truncate">${escapeHtml(itemCode)}${trimmedTitle ? (' · ' + escapeHtml(trimmedTitle)) : ''}</div>
          </div>
        </button>
      `;
    }).join('');

    $searchName.attr('aria-expanded', 'true');
    $skuSuggest.html(html).removeClass('hidden');
  }

  let suggestTimeout = null;
  function fetchSuggest(term) {
    const t = String(term || '').trim();
    if (t.length < 2) {
      hideSuggest();
      return;
    }

    clearTimeout(suggestTimeout);
    suggestTimeout = setTimeout(function () {
      const reqId = ++activeSuggestRequest;
      fetch(skuSearchBase + '&q=' + encodeURIComponent(t), {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (reqId !== activeSuggestRequest) return;
          if (data && data.success && Array.isArray(data.products)) {
            renderSuggest(data.products);
            return;
          }
          hideSuggest();
        })
        .catch(function () {
          if (reqId !== activeSuggestRequest) return;
          hideSuggest();
        });
    }, 280);
  }

  $skuSuggest.on('click', 'button[data-sku]', function () {
    const sku = ($(this).data('sku') || '').toString();
    const itemCode = ($(this).data('item-code') || '').toString();
    const selected = sku || itemCode;
    if (!selected) return;
    hideSearchError();
    $searchName.val(selected);
    hideSuggest();
    resetAndLoad();
    checkAvailabilityAndMaybeOpen({
      id: '',
      sku: sku,
      item_code: itemCode
    });
  });

  $searchName.on('blur', function () {
    // allow click selection before hiding
    setTimeout(hideSuggest, 150);
  });

  $searchName.on('keydown', function (e) {
    if (e.key === 'Escape') {
      hideSuggest();
      return;
    }
    if (e.key === 'Enter') {
      e.preventDefault();
      hideSuggest();
      hideSearchError();
      const q = String($searchName.val() || '').trim();
      if (q.length < 1) {
        showSearchError('Enter a SKU.');
        return;
      }
      fetch(skuSearchBase + '&q=' + encodeURIComponent(q) + '&exact=1', {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && data.success && data.product) {
            const sku = (data.product.sku != null ? String(data.product.sku) : '');
            const itemCode = (data.product.item_code != null ? String(data.product.item_code) : '');
            const selected = sku || itemCode || q;
            $searchName.val(selected);
            hideSearchError();
            resetAndLoad();
            checkAvailabilityAndMaybeOpen(data.product);
            return;
          }
          showSearchError((data && data.message) ? data.message : 'No product found with this SKU.');
        })
        .catch(function () {
          showSearchError('Could not verify SKU. Try again.');
        });
    }
  });

  $searchName.on('keyup change', function () {
    hideSearchError();
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function () {
      resetAndLoad();
    }, 400);

    fetchSuggest($searchName.val());
  });

  $scrollWrapper.on('scroll', function () {
    const scrollTop = $(this).scrollTop();
    const scrollHeight = this.scrollHeight;
    const containerHeight = $(this).innerHeight();

    if (scrollTop + containerHeight >= scrollHeight - 150) {
      if (!isLoading && hasMore) {
        fetchProducts(currentPage + 1, true);
      }
    }
  });

  // Initial load – products only
  resetAndLoad();
});

// In pos.js, add this
$('#addonSelect').on('change', function () {
  let val = $(this).val();
  let optionsStr = val !== '0' ? 'OPTIONALS_GIFTWRAP:blank:' + val : '';
  $('#modal_options').val(optionsStr);  // add <input type="hidden" name="options" id="modal_options"> in form
});