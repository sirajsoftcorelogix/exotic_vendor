$(function () {
  let productApiCache = {};
  let currentPage = 1;
  const perPage = 50;
  let currentCategory = '';

  let isLoading = false;
  let hasMore = true;
  let totalPages = 1;

  let loadedKeys = new Set();
  let productsByKey = new Map();

  const $cards = $('#productsCards');
  const $listHost = $('#productsListContainer');
  const $pagePrev = $('#productsPagePrev');
  const $pageNext = $('#productsPageNext');
  const $pageInfo = $('#productsPageInfo');

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

  /** Match POSRegisterController::fixImageUrl — relative paths must hit CDN, not the portal origin. */
  function fixModalImageSrc(path) {
    if (path == null || path === '') return '';
    const s = String(path).trim();
    if (!s) return '';
    if (/^https?:\/\//i.test(s)) return s;
    if (s.indexOf('//') === 0) return 'https:' + s;
    return s.indexOf('/') === 0
      ? 'https://cdn.exoticindia.com' + s
      : 'https://cdn.exoticindia.com/' + s;
  }

  function showLoader(show) {
    if (show) {
      if (!$('#productsLoader').length) {
        $listHost.append(
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

  /** Prefer full variant SKU when present (e.g. ITEM--blue); fallback to item_code; then API-only code. */
  function getLookupCode(p) {
    if (!p) return '';
    const sku = p.sku != null ? String(p.sku).trim() : '';
    if (sku !== '') return sku;
    const ic = p.item_code != null ? String(p.item_code).trim() : '';
    if (ic !== '') return ic;
    return p.requested_code != null ? String(p.requested_code).trim() : '';
  }

  function escapeRegExpStr(s) {
    return String(s || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  /**
   * Main-site cart expects parent item_code + variation "size:color" for multi-SKU parents.
   * Single-SKU rows use full SKU as code with empty variation.
   */
  function resolveCartPayload(p) {
    const lookup = getLookupCode(p) || String(p.code || '').trim();
    const icRaw = String(p.item_code || '').trim();

    let size =
      p.size != null && String(p.size).trim() !== '' && String(p.size).trim() !== '0'
        ? String(p.size).trim()
        : '';
    let color =
      p.color != null && String(p.color).trim() !== '' && String(p.color).trim() !== '0'
        ? String(p.color).trim()
        : '';

    const multiVariant = icRaw !== '' && lookup !== '' && lookup !== icRaw;

    if (!color && icRaw && lookup) {
      try {
        const re = new RegExp('^' + escapeRegExpStr(icRaw) + '--(.+)$', 'i');
        const m = lookup.match(re);
        if (m) color = m[1].trim();
      } catch (e) {
        /* ignore */
      }
    }

    let variation = '';
    if (!size && color) variation = ':' + color;
    else if (size && !color) variation = size + ':';
    else if (size && color) variation = size + ':' + color;

    if (!variation && color) variation = ':' + color;

    let cartCode = multiVariant ? icRaw : lookup;
    let variationOut = variation;

    if (multiVariant && !variationOut) {
      cartCode = lookup;
      variationOut = '';
    }

    const stockCheckCode = lookup || icRaw || cartCode;

    return { cartCode, variation: variationOut, stockCheckCode };
  }

  function isMeaningful(val) {
    if (val === null || val === undefined) return false;
    const s = String(val).trim();
    if (s === '' || s.toLowerCase() === 'n/a') return false;
    const n = Number(s);
    if (!Number.isNaN(n) && n === 0) return false;
    return true;
  }

  function formatGstPercentForModal(raw) {
    if (raw === null || raw === undefined) return '';
    const s = String(raw).trim();
    if (s === '' || s.toLowerCase() === 'n/a') return '';
    const n = Number(s);
    if (!Number.isNaN(n)) {
      if (n === 0) return '0%';
      return (Number.isInteger(n) ? String(n) : String(n)) + '%';
    }
    return s;
  }

  /** Money amount for display (includes 0; isMeaningful hides zero). */
  function hasDisplayablePrice(val) {
    if (val === null || val === undefined) return false;
    const s = String(val).trim();
    if (s === '' || s.toLowerCase() === 'n/a') return false;
    const n = Number(s);
    return !Number.isNaN(n);
  }

  /** API `dimensions` string, or L × W × H from VP columns. */
  function formatMeasurementLine(p) {
    if (!p) return '';
    if (isMeaningful(p.dimensions)) return String(p.dimensions).replace(/\s+/g, ' ').trim();
    const h = isMeaningful(p.prod_height) ? String(p.prod_height).trim() : '';
    const w = isMeaningful(p.prod_width) ? String(p.prod_width).trim() : '';
    const l = isMeaningful(p.prod_length) ? String(p.prod_length).trim() : '';
    const u = isMeaningful(p.length_unit) ? String(p.length_unit).trim() : '';
    const parts = [h, w, l].filter(Boolean);
    if (!parts.length) return '';
    const line = `${parts.join(' × ')}${u ? ' ' + u : ''}`;
    return line.trim();
  }

  function fmtFloorQty(n) {
    if (n === null || n === undefined || String(n).trim() === '') return '';
    const x = Number(n);
    if (Number.isNaN(x)) return '';
    return String(Math.floor(x));
  }

  /** Prefer API kg; else VP `product_weight` + unit. */
  function formatWeightLine(p) {
    if (!p) return '';
    if (isMeaningful(p.weight)) {
      const w = String(p.weight).trim();
      if (/kg|g|gram|lb|oz|mt|ton|ml|l\b/i.test(w)) return w;
      return `${w} kg`;
    }
    const wt = isMeaningful(p.product_weight) ? String(p.product_weight).trim() : '';
    const wtu = isMeaningful(p.product_weight_unit) ? String(p.product_weight_unit).trim() : '';
    if (!wt) return '';
    return wtu ? `${wt} ${wtu}` : wt;
  }

  function siblingHtmlEscape(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function renderSiblingSkusBlock(rows) {
    const $wrap = $('#pmSiblingSkusWrapper');
    const $list = $('#pmSiblingSkus');
    if (!$wrap.length || !$list.length) return;
    const list = Array.isArray(rows)
      ? rows.filter(function (r) {
        return r && String(r.sku || '').trim() !== '';
      })
      : [];
    if (!list.length) {
      $wrap.addClass('hidden');
      $list.empty();
      return;
    }
    $wrap.removeClass('hidden');
    let html = '';
    list.forEach(function (s) {
      const sku = String(s.sku || '').trim();
      html += `<button type="button" class="pm-sibling-sku-link inline-flex items-center rounded-lg border border-orange-100 bg-orange-50/80 px-2.5 py-1.5 text-left text-[10px] font-semibold text-orange-900 hover:bg-orange-100 transition" data-sibling-sku="${siblingHtmlEscape(sku)}">${siblingHtmlEscape(sku)}</button>`;
    });
    $list.html(html);
  }

  function loadSiblingSkusForProduct(p) {
    if (Array.isArray(p.sibling_skus)) {
      renderSiblingSkusBlock(p.sibling_skus);
      return;
    }
    if (isMeaningful(p.item_code) && getLookupCode(p)) {
      $.ajax({
        url: '?page=pos_register&action=sibling-skus',
        type: 'GET',
        dataType: 'json',
        data: {
          item_code: String(p.item_code).trim(),
          exclude_sku: getLookupCode(p)
        }
      })
        .done(function (res) {
          renderSiblingSkusBlock(res.data || []);
        })
        .fail(function () {
          renderSiblingSkusBlock([]);
        });
      return;
    }
    renderSiblingSkusBlock([]);
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
  /** When set from `stock_qty`, qty controls cannot exceed this (warehouse running stock). */
  let modalWarehouseMaxQty = null;
  let modalPreselectedAddonEntries = [];

  function openModal() {
    $modal.removeClass('hidden');
    $('body').addClass('overflow-hidden');
  }

  function closeModal() {
    $modal.addClass('hidden');
    $('body').removeClass('overflow-hidden');
    activeModalKey = null;
    modalWarehouseMaxQty = null;
    $('#pmQtyMaxHint').text('');
    $('#pmSiblingSkus').empty();
    $('#pmSiblingSkusWrapper').addClass('hidden');
    $('#modal_stock_check_code').val('');
    $('#modal_options').val('');
    modalPreselectedAddonEntries = [];
    $('#pmQtySummary').empty().addClass('hidden');
    $('#pmModalPrice').addClass('hidden').text('');
  }

  $overlay.on('click', closeModal);
  $close.on('click', closeModal);
  $closeBtn.on('click', closeModal);

  function updateModalQtyUiState() {
    const max = modalWarehouseMaxQty;
    const q = getModalQty();
    const $submit = $('#productModal').find('form[action*="cart-add"] button[type="submit"]');

    if (typeof max === 'number' && max === 0) {
      $submit.prop('disabled', true).addClass('opacity-50');
      $pmQtyInc.prop('disabled', true);
      $pmQtyDec.prop('disabled', true);
    } else if (typeof max === 'number' && max > 0) {
      $submit.prop('disabled', false).removeClass('opacity-50');
      $pmQtyInc.prop('disabled', q >= max);
      $pmQtyDec.prop('disabled', q <= 1);
    } else {
      $submit.prop('disabled', false).removeClass('opacity-50');
      $pmQtyInc.prop('disabled', false);
      $pmQtyDec.prop('disabled', q <= 1);
    }
  }

  function setModalQty(qty) {
    let raw = qty;
    if (typeof raw === 'string') raw = parseInt(raw, 10);
    let q = Number.isFinite(raw) ? raw : NaN;
    const max = modalWarehouseMaxQty;

    if (typeof max === 'number' && max >= 0) {
      if (max === 0) {
        q = 0;
      } else {
        if (!Number.isFinite(q) || q < 1) q = 1;
        q = Math.min(q, max);
      }
    } else if (!Number.isFinite(q) || q < 1) {
      q = 1;
    }

    $pmQtyVal.text(String(q));
    $('#modal_qty').val(q);
    updateModalQtyUiState();
  }

  function getModalQty() {
    const n = parseInt(String($pmQtyVal.text()).trim(), 10);
    return Number.isFinite(n) ? n : 1;
  }

  function normalizeAddonEntries(entries) {
    if (!Array.isArray(entries)) return [];
    const out = [];
    const seen = new Set();
    entries.forEach(function (v) {
      const s = String(v || '').trim();
      if (!s) return;
      const k = s.toLowerCase();
      if (seen.has(k)) return;
      seen.add(k);
      out.push(s);
    });
    return out;
  }

  function applyPreselectedAddonsToModal(entries) {
    const wanted = normalizeAddonEntries(entries);
    const wantedLower = new Set(wanted.map(function (x) { return x.toLowerCase(); }));
    $('#productModal .addon-checkbox').each(function () {
      const entry = String($(this).data('entry') || '').trim().toLowerCase();
      $(this).prop('checked', entry && wantedLower.has(entry));
    });
    $('#modal_options').val(wanted.join('|'));
  }

  $pmQtyDec.on('click', function () {
    const max = modalWarehouseMaxQty;
    if (typeof max === 'number' && max === 0) return;
    setModalQty(getModalQty() - 1);
  });

  $pmQtyInc.on('click', function () {
    const max = modalWarehouseMaxQty;
    const cur = getModalQty();
    if (typeof max === 'number' && max > 0 && cur >= max) return;
    setModalQty(cur + 1);
  });

  $('#productModal').find('form[action*="cart-add"]').on('submit', function (e) {
    const selectedEntries = [];
    $('#productModal .addon-checkbox:checked').each(function () {
      const entry = $(this).data('entry');
      if (entry) {
        selectedEntries.push(String(entry));
      }
    });
    $('#modal_options').val(selectedEntries.join('|'));

    const max = modalWarehouseMaxQty;
    const q = parseInt(String($('#modal_qty').val()), 10);
    const qtyNum = Number.isFinite(q) ? q : 0;
    if (typeof max === 'number' && max >= 0) {
      if (max === 0 || qtyNum > max) {
        e.preventDefault();
        alert(
          max === 0
            ? 'Out of stock in this warehouse.'
            : 'Maximum quantity available is ' + max + '.'
        );
        return false;
      }
    }
  });

  function renderProductModal(p, key) {
    activeModalKey = key;
    $('#pmAddons').html('');
    $('#pmAddonsWrapper').addClass('hidden');
    const title = (p.title || '').replace(/\s+/g, ' ').trim();
    $('#pmTitle').text(title || 'Product');

    const imgSrc =
      fixModalImageSrc(p.image) ||
      'https://dummyimage.com/500x500/e5e7eb/6b7280&text=No+Image';
    $('#pmImage').attr('src', imgSrc).attr('alt', title || 'Product');

    const sqRaw = p.stock_qty;
    modalWarehouseMaxQty = null;
    if (sqRaw !== null && sqRaw !== undefined && String(sqRaw).trim() !== '') {
      const n = Number(sqRaw);
      if (!Number.isNaN(n)) {
        modalWarehouseMaxQty = Math.max(0, Math.floor(n));
      }
    }
    const $hint = $('#pmQtyMaxHint');
    if ($hint.length) {
      $hint.text(
        modalWarehouseMaxQty !== null && modalWarehouseMaxQty >= 0
          ? modalWarehouseMaxQty > 0
            ? `(max ${modalWarehouseMaxQty})`
            : '(out of stock)'
          : ''
      );
    }

    setModalQty(modalWarehouseMaxQty === 0 ? 0 : 1);

    //  ADDONS UI (FINAL CLEAN)

    let addons = [];

    if (p && p.addon_options && Array.isArray(p.addon_options.default_options)) {
      addons = p.addon_options.default_options;
    }
    if (p.express_shipping_option && p.express_shipping_option.price) {
      addons.push({
        title: p.express_shipping_option.title || 'Express Shipping',
        price: p.express_shipping_option.price,
        cart_entry: p.express_shipping_option.cart_entry || ''
      });
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

    const cp = resolveCartPayload(p);
    $('#modal_product_code').val(cp.cartCode || String(p.code || p.id || ''));
    $('#modal_variation').val(cp.variation);
    $('#modal_stock_check_code').val(cp.stockCheckCode);

    const badges = [];
    const icRaw = isMeaningful(p.item_code) ? String(p.item_code).trim() : '';
    const skuRaw = isMeaningful(p.sku) ? String(p.sku).trim() : '';

    if (icRaw && skuRaw && icRaw !== skuRaw) {
      badges.push(`<span class="rounded-md bg-orange-100 px-2 py-1 text-[10px] text-orange-700">Item code: ${icRaw}</span>`);
      badges.push(`<span class="rounded-md bg-blue-100 px-2 py-1 text-[10px] text-blue-700">SKU: ${skuRaw}</span>`);
    } else if (skuRaw) {
      badges.push(`<span class="rounded-md bg-blue-100 px-2 py-1 text-[10px] text-blue-700">SKU: ${skuRaw}</span>`);
    } else if (icRaw) {
      badges.push(`<span class="rounded-md bg-blue-100 px-2 py-1 text-[10px] text-blue-700">SKU: ${icRaw}</span>`);
    } else {
      const fallback = getLookupCode(p);
      if (fallback) {
        badges.push(`<span class="rounded-md bg-blue-100 px-2 py-1 text-[10px] text-blue-700">SKU: ${fallback}</span>`);
      }
    }

    //  MAIN CATEGORY (RIGHT SIDE STYLE)
    if (isMeaningful(p.maincategory)) {
      badges.push(`<span class="rounded-md bg-gray-100 px-2 py-1 text-[10px] text-gray-700 capitalize">${p.maincategory}</span>`);
    }

    //  STOCK (include 0 — isMeaningful treats 0 as empty)
    if (p.stock_qty != null && String(p.stock_qty).trim() !== '' && !Number.isNaN(Number(p.stock_qty))) {
      badges.push(`<span class="rounded-md bg-green-100 px-2 py-1 text-[10px] text-green-700">Stock: ${p.stock_qty}</span>`);
    }

    $('#pmBadges').html(badges.join(''));

    let html = '';

    const measurementLine = formatMeasurementLine(p);
    if (measurementLine) {
      html += addRow('Measurements', measurementLine);
    }

    const weightLine = formatWeightLine(p);
    if (weightLine) {
      html += addRow('Weight', weightLine);
    }

    if (isMeaningful(p.warehouse_location)) {
      html += addRow('Location', String(p.warehouse_location).replace(/\s+/g, ' ').trim());
    }

    if (isMeaningful(p.hsn)) {
      html += addRow('HSN Code', String(p.hsn).replace(/\s+/g, ' ').trim());
    }

    const gstPct = formatGstPercentForModal(p.gst_percent);
    if (gstPct !== '') {
      html += addRow('GST %', gstPct);
    }

    if (isMeaningful(p.size)) {
      html += addRow('Size', String(p.size).replace(/\s+/g, ' ').trim());
    }
    if (isMeaningful(p.color)) {
      html += addRow('Color', String(p.color).replace(/\s+/g, ' ').trim());
    }
    if (isMeaningful(p.material)) {
      html += addRow('Material', String(p.material).replace(/\s+/g, ' ').trim());
    }

    if (!html) {
      html = `<div class="col-span-3 text-xs text-gray-500">No additional details available.</div>`;
    }

    $('#pmDetails').html(html);

    const $pmPrice = $('#pmModalPrice');
    if ($pmPrice.length) {
      if (hasDisplayablePrice(p.price)) {
        $pmPrice
          .removeClass('hidden')
          .text(
            `₹ ${Number(p.price).toLocaleString('en-IN', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2
            })}`
          );
      } else {
        $pmPrice.addClass('hidden').text('');
      }
    }

    renderQtySummaryUnderInput(p);

    loadSiblingSkusForProduct(p);
  }

  function renderQtySummaryUnderInput(p) {
    const $wrap = $('#pmQtySummary');
    if (!$wrap.length) return;
    $wrap.empty();
    let lines = 0;

    const whName = String(
      p.current_warehouse_name || $('#productModal').data('posWarehouse') || ''
    ).trim();

    // Current POS warehouse (session) — matches Stock badge qty
    if (
      p.stock_qty != null &&
      String(p.stock_qty).trim() !== '' &&
      !Number.isNaN(Number(p.stock_qty))
    ) {
      const label =
        whName !== '' ? 'Total Qty at (' + whName + '): ' : 'Total Qty: ';
      $wrap.append(
        $('<div/>').append(
          $('<span/>').text(label),
          $('<span class="font-semibold text-gray-800"/>').text(fmtFloorQty(p.stock_qty))
        )
      );
      lines++;
    }

    // Default warehouse from exotic_address.is_default
    if (
      p.default_store_qty != null &&
      String(p.default_store_qty).trim() !== '' &&
      !Number.isNaN(Number(p.default_store_qty))
    ) {
      const dn = p.default_store_name ? String(p.default_store_name).trim() : '';
      if (dn !== '') {
        $wrap.append(
          $('<div/>').append(
            $('<span/>').text('Qty at (' + dn + '): '),
            $('<span class="font-semibold text-gray-800"/>').text(fmtFloorQty(p.default_store_qty))
          )
        );
        lines++;
      }
    }

    $wrap.toggleClass('hidden', lines === 0);
  }

  $(document).on('click', '.pm-sibling-sku-link', function (e) {
    e.preventDefault();
    const sku = $(this).attr('data-sibling-sku');
    if (!sku) return;
    openProductModalByCode(String(sku));
  });

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

      const lookupCode = getLookupCode(p);
      const cardHtml = `
        <div class="product-card cursor-pointer rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition"
             data-pkey="${key}"
data-code="${lookupCode}">
          <div class="bg-gray-50 p-2">
            <img src="${imgSrc}" alt="${safeTitle}"
                 class="mx-auto h-56 lg:h-52 xl:h-48 object-contain" />
          </div>

          <div class="px-3 pb-3 pt-2 text-xs">
            <div class="text-[9.5px] text-gray-800 leading-snug line-clamp-2">
              ${(p.title || '').replace(/\s+/g, ' ').trim()}
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1">
              <span class="rounded-md bg-orange-100 px-1.5 py-0.5 text-[9px] text-orange-700">
                ${lookupCode || ''}
              </span>
              <span class="text-base font-semibold tracking-tight text-gray-900">
                ${formatPrice(p.price)}
              </span>
            </div>
          </div>
        </div>
      `;

      $cards.append(cardHtml);
    });
  }

  function updatePaginationUi(pageCount) {
    const resolvedTotalPages = Number.isFinite(pageCount) && pageCount > 0
      ? pageCount
      : totalPages;
    const hasPageCount = Number.isFinite(resolvedTotalPages) && resolvedTotalPages > 0;
    if ($pageInfo.length) {
      $pageInfo.text(
        hasPageCount
          ? ('Loaded Page ' + String(currentPage) + ' of ' + String(resolvedTotalPages))
          : ('Page ' + String(currentPage))
      );
    }
    if ($pagePrev.length) {
      $pagePrev.prop('disabled', currentPage <= 1 || isLoading);
    }
    if ($pageNext.length) {
      $pageNext.prop('disabled', !hasMore || isLoading);
    }
  }

  function fetchProducts(page = 1, append = false) {
    if (isLoading) return;
    if (append && !hasMore) return;

    isLoading = true;
    updatePaginationUi();
    showLoader(true);
    const sortBy = $('#sortBy').val();
    const minPrice = $('#minPrice').val();
    const maxPrice = $('#maxPrice').val();
    const $stockFilterEl = $('#stockFilter');
    // Align POS listing default with stock report compare URL (stock_status=in).
    const stockFilter = $stockFilterEl.length ? String($stockFilterEl.val() || 'in') : 'in';
    // One search box: same semantics as stock report (title OR item_code OR sku).
    const searchVal = String($('#searchName').val() || '').trim();
    const productName = searchVal;
    const productCode = '';
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
        const pagesFromApi = parseInt(res.total_pages, 10);
        totalPages = Number.isFinite(pagesFromApi) && pagesFromApi > 0 ? pagesFromApi : 1;

        if (res.has_more != null) {
          hasMore = !!res.has_more;
        } else if (res.total_pages != null) {
          hasMore = currentPage < parseInt(res.total_pages, 10);
        } else {
          hasMore = rows.length === perPage;
        }

        // Incremental pagination: append when loading next page.
        renderProducts(rows, append);
        updatePaginationUi(totalPages);
      },
      error: function (xhr, status, err) {
        console.error('Error loading products', err);
      },
      complete: function () {
        isLoading = false;
        showLoader(false);
        updatePaginationUi();
      }
    });
  }

  function resetAndLoad() {
    currentPage = 1;
    hasMore = true;
    totalPages = 1;
    fetchProducts(1, false);
    updatePaginationUi();
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
    openProductModalByCode(code, []);
  });
  $(document).on('click', '.pos-cart-item', function (e) {
    if ($(e.target).closest('form,button,input,a,select,textarea,label').length) {
      return;
    }
    const code = String($(this).data('product-code') || '').trim();
    const selectedEntriesRaw = String($(this).data('selected-entries') || '').trim();
    const selectedEntries = selectedEntriesRaw
      ? selectedEntriesRaw.split('|').map(function (x) { return String(x || '').trim(); }).filter(Boolean)
      : [];
    if (!code) return;
    openProductModalByCode(code, selectedEntries);
  });
  function openProductModalByCode(code, preselectedAddonEntries = []) {
    if (!code) return;
    modalPreselectedAddonEntries = normalizeAddonEntries(preselectedAddonEntries);
    openModal();

    //  CACHE HIT
    if (productApiCache[code]) {
      renderProductModal(productApiCache[code], code);
      applyPreselectedAddonsToModal(modalPreselectedAddonEntries);
      return;
    }

    //  LOADING STATE
    $('#pmTitle').text('Loading...');
    $('#pmDetails').html('Loading...');
    $('#pmModalPrice').addClass('hidden').text('');

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
        applyPreselectedAddonsToModal(modalPreselectedAddonEntries);
      }
    });
  }
  function checkAvailabilityAndMaybeOpen(product) {
    if (!product) return;
    const productId = product.id != null ? String(product.id) : '';
    const itemCode = product.item_code != null ? String(product.item_code) : '';
    const sku = product.sku != null ? String(product.sku) : '';
    const codeForPopup = sku || itemCode;
    if (!codeForPopup) return;
    const url = '?page=pos_register&action=product-availability'
      + (productId ? ('&product_id=' + encodeURIComponent(productId)) : ('&q=' + encodeURIComponent(codeForPopup)));
    fetch(url, {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data && data.success && data.current_available === false && data.message) {
          alert(data.message);
          openProductModalByCode(codeForPopup, []);
        }
      })
      .catch(function () {
        // keep search flow functional even if availability endpoint fails
      });
  }
  function renderModalData(p) {

    $('#pmTitle').text(p.title || 'Product');
    $('#pmImage').attr('src', fixModalImageSrc(p.image) || '');

    $('#pmDetails').html(`
        <div>Price</div><div>:</div><div>₹ ${p.price || 0}</div>
        <div>Material</div><div>:</div><div>${p.material || '-'}</div>
        <div>Size</div><div>:</div><div>${p.size || '-'}</div>
        <div>Color</div><div>:</div><div>${p.color || '-'}</div>
    `);

    const cpMd = resolveCartPayload(p);
    $('#modal_product_code').val(cpMd.cartCode || String(p.code || ''));
    $('#modal_variation').val(cpMd.variation);
    $('#modal_stock_check_code').val(cpMd.stockCheckCode);

    // ADDONS
    let addonsHtml = '';

    if (p.addon_options) {
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
  // $(document).on('change', '.addon-checkbox', function () {

  //   let selected = [];

  //   $('.addon-checkbox:checked').each(function () {
  //     let entry = $(this).data('entry');

  //     if (entry) {
  //       selected.push(entry);
  //     }
  //   });

  //   //  JOIN WITH PIPE
  //   let optionsStr = selected.join('|');

  //   console.log("FINAL OPTIONS:", optionsStr);

  //   //  SET HIDDEN INPUT
  //   $('#modal_options').val(optionsStr);
  // });
  $(document).on('change', '.addon-checkbox', function () {
    let selected = [];

    $('.addon-checkbox:checked').each(function () {
        selected.push($(this).data('entry'));
    });

    $('#modal_options').val(selected.join('|'));
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

  if ($pagePrev.length) {
    $pagePrev.on('click', function () {
      if (isLoading) return;
      resetAndLoad();
    });
  }
  if ($pageNext.length) {
    $pageNext.on('click', function () {
      if (isLoading || !hasMore) return;
      fetchProducts(currentPage + 1, true);
    });
  }

  // Initial load – products only
  resetAndLoad();
});

// In pos.js, add this
$('#addonSelect').on('change', function () {
  let val = $(this).val();
  let optionsStr = val !== '0' ? 'OPTIONALS_GIFTWRAP:blank:' + val : '';
  $('#modal_options').val(optionsStr);  // add <input type="hidden" name="options" id="modal_options"> in form
});