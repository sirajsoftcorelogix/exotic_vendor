$(function () {
  let productApiCache = {};
  let currentPage = 1;
  const perPage = 48;
  let currentCategory = '';

  let isLoading = false;
  let hasMore = true;
  let totalPages = 1;

  let loadedKeys = new Set();
  let productsByKey = new Map();
  /** @type {Record<string, number>} */
  let productApiRequestSeq = {};

  const $cards = $('#productsCards');
  const $listHost = $('#productsListContainer');
  const $pagePrev = $('#productsPagePrev');
  const $pageNext = $('#productsPageNext');
  const $pageInfo = $('#productsPageInfo');

  // ────────────────────────────────────────────────
  // HELPERS (needed for products & modal)
  // ────────────────────────────────────────────────
  const POS_PARENT_ITEM_CART_MSG = 'Parent Level Item can not be added to the cart';

  function isParentLevelProduct(p) {
    if (!p || typeof p !== 'object') return false;
    if (p.is_parent_level === true || p.is_parent_level === 1 || p.is_parent_level === '1') {
      return true;
    }
    return String(p.item_level || '').trim().toLowerCase() === 'parent';
  }

  function notifyParentItemCartBlocked() {
    if (typeof window.toast === 'function') {
      window.toast(POS_PARENT_ITEM_CART_MSG, 'red');
    } else {
      alert(POS_PARENT_ITEM_CART_MSG);
    }
  }

  window.isParentLevelProduct = isParentLevelProduct;
  window.notifyParentItemCartBlocked = notifyParentItemCartBlocked;
  window.POS_PARENT_ITEM_CART_MSG = POS_PARENT_ITEM_CART_MSG;

  function getPosCurrencyInfo() {
    return {
      code: 'INR',
      symbol: '₹'
    };
  }

  function getPosCurrencyCode() {
    return 'INR';
  }

  function getPosCurrencySymbol() {
    return '₹';
  }

  function formatPrice(price) {
    const p = parseFloat(price || 0);
    return '₹ ' + p.toLocaleString('en-IN', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  window.reformatPosProductPrices = function () {
    if (typeof jQuery !== 'undefined') {
      jQuery('.pos-product-price').each(function () {
        const raw = jQuery(this).attr('data-raw-price');
        if (raw != null && raw !== '') {
          jQuery(this).text(formatPrice(raw));
        }
      });
    }
  };

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

  function buildModalSeedFromGrid(row, code) {
    if (!row || typeof row !== 'object') return null;
    const lookup = getLookupCode(row) || String(code || '').trim();
    return Object.assign({}, row, {
      requested_code: String(code || lookup || '').trim(),
      sku: row.sku || lookup,
      item_code: row.item_code || '',
      gst_percent: row.gst_percent != null ? row.gst_percent : row.gst,
      _partial: true
    });
  }

  function getProductKey(p) {
    return (p.id != null && p.id !== '') ? `id:${p.id}` : `code:${p.item_code || ''}`;
  }

  function fetchProductApiDetails(code, preselectedAddonEntries) {
    const reqId = (productApiRequestSeq[code] || 0) + 1;
    productApiRequestSeq[code] = reqId;

    return $.ajax({
      url: '?page=pos_register&action=get-product-api',
      type: 'GET',
      data: { code: code },
      dataType: 'json'
    })
      .done(function (res) {
        if (productApiRequestSeq[code] !== reqId) return;
        const p = (res && res.data) ? res.data : {};
        productApiCache[code] = p;
        renderProductModal(p, code);
        applyPreselectedAddonsToModal(preselectedAddonEntries);
      })
      .fail(function () {
        if (productApiRequestSeq[code] !== reqId) return;
        if (!productApiCache[code]) {
          $('#pmTitle').text('Could not load product');
          $('#pmDetails').html(
            '<div class="col-span-3 text-xs text-red-600">Failed to load product details. Please try again.</div>'
          );
        }
      });
  }

  /** Scan/search lookup (may be variant SKU); cart add uses item_code + size/color instead. */
  function getLookupCode(p) {
    if (!p) return '';
    const sku = p.sku != null ? String(p.sku).trim() : '';
    if (sku !== '') return sku;
    const ic = p.item_code != null ? String(p.item_code).trim() : '';
    if (ic !== '') return ic;
    return p.requested_code != null ? String(p.requested_code).trim() : '';
  }

  function normalizeFacet(val) {
    if (val == null) return '';
    const s = String(val).trim();
    if (s === '' || s === '0' || s.toLowerCase() === 'n/a') return '';
    return s;
  }

  /** Exotic cart variation string from vp_products.size / color only. */
  function buildVariationFromSizeColor(size, color) {
    if (!size && !color) return '';
    if (!size && color) return ':' + color;
    if (size && !color) return size + ':';
    return size + ':' + color;
  }

  /**
   * Cart add: parent/variant rows use item_code + size:color from DB (not variant SKU).
   */
  function resolveCartPayload(p) {
    if (!p) return { cartCode: '', variation: '', stock_check_code: '' };

    const ic = normalizeFacet(p.item_code);
    const level = String(p.item_level || '').trim().toLowerCase();
    const size = normalizeFacet(p.size);
    const color = normalizeFacet(p.color);
    const variation = buildVariationFromSizeColor(size, color);

    if (level === 'parent') {
      return { cartCode: ic, variation: '', stock_check_code: '' };
    }

    if (level === 'variation' || (ic !== '' && variation !== '')) {
      return { cartCode: ic, variation: variation, stock_check_code: '' };
    }

    const singleCode = ic || normalizeFacet(p.sku) || String(p.code || '').trim();
    return { cartCode: singleCode, variation: '', stock_check_code: '' };
  }

  function setModalCartFields(p, cp) {
    $('#modal_product_code').val(cp.cartCode || String(p.code || ''));
    $('#modal_variation').val(cp.variation || '');
    $('#modal_stock_check_code').val(cp.stock_check_code || '');
    $('#modal_item_code').val(normalizeFacet(p.item_code));
    $('#modal_size').val(normalizeFacet(p.size));
    $('#modal_color').val(normalizeFacet(p.color));
    $('#modal_product_id').val(p.id || p.product_id || '');
    const isUnpub =
      (p.published === 0 || p.published === '0' || p.published === false) &&
      (p.is_published === false || p.is_published === 0 || p.is_published === '0') &&
      String(p.status_label || '').toLowerCase() === 'unpublished';
    $('#modal_published').val(isUnpub ? '0' : '1');
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
  /** When set, qty controls cannot exceed this (warehouse running stock). Null = no cap (cross-store / unmapped). */
  let modalWarehouseMaxQty = null;
  let modalStockWarningMessage = '';
  let modalPreselectedAddonEntries = [];
  /** @type {{ name: string, price: number, cartEntry: string }[]} */
  let modalCustomAddons = [];
  const CUSTOM_ADDON_MIDDLE = '_blank_';

  let activeModalProduct = null;

  function openModal() {
    $modal.removeClass('hidden');
    $('body').addClass('overflow-hidden');
  }

  window.setPosModalPriceMode = function (mode) {
    if (typeof window.setPosCurrencyMode === 'function') {
      window.setPosCurrencyMode(mode);
    } else {
      window.POS_CURRENCY_MODE = mode;
    }
    if (activeModalProduct) {
      updateModalPriceDisplay(activeModalProduct);
    }
  };

  function getClientModalCurrencyInfo() {
    let code = 'INR';
    let symbol = '₹';

    if (window.POS_CURRENCY_MODE === 'INR') {
      return { code: 'INR', symbol: '₹' };
    }

    if (window.POS_CURRENT_CUSTOMER_CURRENCY_CODE) {
      code = String(window.POS_CURRENT_CUSTOMER_CURRENCY_CODE).trim().toUpperCase();
      if (window.POS_CURRENT_CUSTOMER_CURRENCY_SYMBOL) symbol = String(window.POS_CURRENT_CUSTOMER_CURRENCY_SYMBOL).trim();
    } else if (window.POS_INITIAL_CUSTOMER && window.POS_INITIAL_CUSTOMER.currency_code) {
      const ic = window.POS_INITIAL_CUSTOMER;
      if (ic.currency_code) code = String(ic.currency_code).trim().toUpperCase();
      if (ic.currency_symbol) symbol = String(ic.currency_symbol).trim();
    }

    if (!symbol) {
      if (code === 'INR') symbol = '₹';
      else if (code === 'USD') symbol = '$';
      else if (code === 'EUR') symbol = '€';
      else if (code === 'GBP') symbol = '£';
      else symbol = code;
    }

    return { code: code, symbol: symbol };
  }

  function updateModalPriceDisplay(p) {
    if (!p) return;
    const $pmPrice = $('#pmModalPrice');
    if (!$pmPrice.length) return;

    if (!hasDisplayablePrice(p.price)) {
      $pmPrice.addClass('hidden').text('');
      return;
    }

    const mode = window.POS_CURRENCY_MODE || 'CUSTOMER';
    const clientCurr = getClientModalCurrencyInfo();
    const custCountry = window.POS_CURRENT_CUSTOMER_COUNTRY_CODE || (window.POS_INITIAL_CUSTOMER && window.POS_INITIAL_CUSTOMER.country_code) || '';

    if (mode === 'CUSTOMER' && clientCurr.code !== 'INR' && custCountry && custCountry !== 'IN') {
      const itemCode = p.item_code || p.sku || p.requested_code || '';
      const color = p.color || '';
      const size = p.size || '';

      if (!p._nonInrPrices) p._nonInrPrices = {};

      if (p._nonInrPrices[clientCurr.code] != null) {
        const altPrice = p._nonInrPrices[clientCurr.code];
        const locale = clientCurr.code === 'INR' ? 'en-IN' : 'en-US';
        $pmPrice
          .removeClass('hidden')
          .text(`${clientCurr.symbol} ${Number(altPrice).toLocaleString(locale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
        return;
      }

      $pmPrice.removeClass('hidden').text('Loading price…');

      $.ajax({
        url: '?page=pos_register&action=get-product-price',
        type: 'GET',
        data: {
          code: itemCode,
          country_code: custCountry,
          color: color,
          size: size
        },
        dataType: 'json'
      }).done(function (res) {
        if (res && res.status && res.price != null) {
          p._nonInrPrices[clientCurr.code] = res.price;
          if (activeModalProduct === p && (window.POS_CURRENCY_MODE || 'CUSTOMER') === 'CUSTOMER') {
            const locale = clientCurr.code === 'INR' ? 'en-IN' : 'en-US';
            $pmPrice
              .removeClass('hidden')
              .text(`${clientCurr.symbol} ${Number(res.price).toLocaleString(locale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
          }
        } else {
          // Fallback to INR price display if endpoint returns no price
          $pmPrice
            .removeClass('hidden')
            .text(`₹ ${Number(p.price).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
        }
      }).fail(function () {
        $pmPrice
          .removeClass('hidden')
          .text(`₹ ${Number(p.price).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
      });
    } else {
      $pmPrice
        .removeClass('hidden')
        .text(`₹ ${Number(p.price).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    }
  }

  function closeModal() {
    $modal.addClass('hidden');
    $('body').removeClass('overflow-hidden');
    activeModalKey = null;
    activeModalProduct = null;
    modalWarehouseMaxQty = null;
    modalStockWarningMessage = '';
    $('#pmQtyMaxHint').text('');
    $('#pmStockWarning').addClass('hidden').text('');
    $('#pmSiblingSkus').empty();
    $('#pmSiblingSkusWrapper').addClass('hidden');
    $('#modal_stock_check_code').val('');
    $('#modal_options').val('');
    modalPreselectedAddonEntries = [];
    resetCustomAddonsUi();
    $('#pmQtySummary').empty().addClass('hidden');
    $('#pmModalPrice').addClass('hidden').text('');
    window.__posCartDraftRowAfterAdd = null;
    window.__posModalOpenOpts = null;
  }

  /** Called after cart add (e.g. from pos_cart_hooks.js) to dismiss the product popup. */
  window.closePosProductModal = closeModal;

  $overlay.on('click', closeModal);
  $close.on('click', closeModal);
  $closeBtn.on('click', closeModal);

  function resolveModalQtyCap(p) {
    if (!p || typeof p !== 'object') return null;
    if (p.enforce_qty_cap === true && p.qty_cap != null && String(p.qty_cap).trim() !== '') {
      const capped = Math.floor(Number(p.qty_cap));
      return Number.isFinite(capped) && capped > 0 ? capped : null;
    }
    const sqRaw = p.stock_qty;
    if (sqRaw !== null && sqRaw !== undefined && String(sqRaw).trim() !== '') {
      const n = Number(sqRaw);
      if (!Number.isNaN(n) && n > 0) {
        return Math.floor(n);
      }
    }
    return null;
  }

  function renderStockWarningBanner(message, warningType) {
    const $banner = $('#pmStockWarning');
    modalStockWarningMessage = String(message || '').trim();
    if (!$banner.length) return;

    const amberClasses = ['border-amber-200', 'bg-amber-50', 'text-amber-900', 'font-normal'];
    const redClasses = ['border-red-600', 'bg-red-200', 'text-red-950', 'font-semibold'];
    $banner.removeClass(amberClasses.concat(redClasses).join(' '));

    if (modalStockWarningMessage) {
      const isUnmappedAnywhere = String(warningType || '').trim() === 'unmapped_anywhere';
      $banner
        .text(modalStockWarningMessage)
        .removeClass('hidden')
        .addClass((isUnmappedAnywhere ? redClasses : amberClasses).join(' '));
    } else {
      $banner.addClass('hidden').text('');
    }
  }

  function updateModalQtyUiState() {
    const max = modalWarehouseMaxQty;
    const q = getModalQty();
    const $submit = $('#pmAddToCartBtn');

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

  function applyModalOpenOptsIfPending() {
    const opts = window.__posModalOpenOpts;
    if (!opts || typeof opts !== 'object') {
      return;
    }
    if (opts.qty != null) {
      const q = parseInt(String(opts.qty), 10);
      if (Number.isFinite(q) && q >= 1) {
        setModalQty(q);
      }
    }
    window.__posModalOpenOpts = null;
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

  function readAddonCartEntry(el) {
    if (!el) return '';
    return String(el.getAttribute('data-entry') || el.getAttribute('data-cart-entry') || '').trim();
  }

  function parseAddonPriceRupee(val) {
    if (val == null || val === '') return null;
    const n = parseFloat(String(val).replace(/,/g, '').trim());
    return Number.isFinite(n) ? n : null;
  }

  function formatAddonPriceRupee(val) {
    const n = parseAddonPriceRupee(val);
    if (n == null) return '—';
    const info = getPosCurrencyInfo();
    const locale = info.code === 'INR' ? 'en-IN' : 'en-US';
    try {
      return n.toLocaleString(locale, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    } catch (e) {
      return n.toFixed(2);
    }
  }

  function formatCustomAddonPriceForCart(price) {
    const n = parseAddonPriceRupee(price);
    if (n == null || n < 0) return '';
    if (Math.abs(n - Math.round(n)) < 0.000001) {
      return String(Math.round(n));
    }
    return n.toFixed(2);
  }

  function normalizeCustomAddonName(raw) {
    return String(raw || '').trim().replace(/\s+/g, '_');
  }

  function isValidCustomAddonName(name) {
    return /^[A-Za-z_]+$/.test(String(name || ''));
  }

  function sanitizeCustomAddonNameField() {
    const $input = $('#pmCustomAddonName');
    if (!$input.length) return '';
    const raw = String($input.val() || '');
    const normalized = normalizeCustomAddonName(raw);
    if (normalized !== raw) {
      $input.val(normalized);
    }
    return normalized;
  }

  function buildCustomAddonCartEntry(name, price) {
    const n = normalizeCustomAddonName(name);
    if (!isValidCustomAddonName(n)) return '';
    const priceStr = formatCustomAddonPriceForCart(price);
    if (!priceStr) return '';
    return n + ':' + CUSTOM_ADDON_MIDDLE + ':' + priceStr;
  }

  function parseCustomAddonCartEntry(entry) {
    const s = String(entry || '').trim();
    const marker = ':' + CUSTOM_ADDON_MIDDLE + ':';
    const idx = s.indexOf(marker);
    if (idx <= 0) return null;
    const name = s.slice(0, idx);
    const priceStr = s.slice(idx + marker.length);
    if (!/^[A-Za-z_]+$/.test(name)) return null;
    const price = parseAddonPriceRupee(priceStr);
    if (price == null || price < 0) return null;
    return { name: name, price: price, cartEntry: s };
  }

  function showCustomAddonError(msg) {
    const $err = $('#pmCustomAddonError');
    if (!$err.length) return;
    $err.text(String(msg || '')).removeClass('hidden');
  }

  function hideCustomAddonError() {
    $('#pmCustomAddonError').addClass('hidden').text('');
  }

  function renderCustomAddonsList() {
    const $list = $('#pmCustomAddonsList');
    if (!$list.length) return;
    if (!modalCustomAddons.length) {
      $list.empty();
      return;
    }
    let html = '';
    modalCustomAddons.forEach(function (item, idx) {
      html +=
        '<div class="flex items-center justify-between gap-2 rounded-lg bg-[#f5f5f5] px-3 py-2">' +
        '<div class="text-[10px] text-gray-800">' + siblingHtmlEscape(item.name) + '</div>' +
        '<div class="flex items-center gap-2">' +
        '<span class="text-[11px] font-semibold text-gray-700">' + getPosCurrencySymbol() + ' ' + formatAddonPriceRupee(item.price) + '</span>' +
        '<button type="button" class="pm-custom-addon-remove text-[10px] text-red-600 hover:underline" data-idx="' + idx + '">Remove</button>' +
        '</div></div>';
    });
    $list.html(html);
  }

  function resetCustomAddonsUi() {
    modalCustomAddons = [];
    $('#pmCustomAddonName').val('');
    $('#pmCustomAddonPrice').val('');
    hideCustomAddonError();
    renderCustomAddonsList();
  }

  function collectAllModalAddonEntries() {
    const selected = [];
    $('#productModal .addon-checkbox:checked').each(function () {
      const entry = readAddonCartEntry(this);
      if (entry) selected.push(entry);
    });
    modalCustomAddons.forEach(function (item) {
      if (item.cartEntry) selected.push(item.cartEntry);
    });
    return normalizeAddonEntries(selected);
  }

  function syncModalOptionsFromAddons() {
    $('#modal_options').val(collectAllModalAddonEntries().join('|'));
  }

  function addCustomAddonFromInputs() {
    const name = sanitizeCustomAddonNameField();
    const priceRaw = $('#pmCustomAddonPrice').val();
    if (!name) {
      showCustomAddonError('Enter an add-on name.');
      return false;
    }
    if (!isValidCustomAddonName(name)) {
      showCustomAddonError('Name may only contain letters (A–Z) and underscores. Spaces are converted to underscores automatically.');
      return false;
    }
    const price = parseAddonPriceRupee(priceRaw);
    if (price == null || price < 0) {
      showCustomAddonError('Enter a valid price (0 or greater).');
      return false;
    }
    const cartEntry = buildCustomAddonCartEntry(name, price);
    if (!cartEntry) {
      showCustomAddonError('Could not build add-on. Check name and price.');
      return false;
    }
    const dup = modalCustomAddons.some(function (x) {
      return x.cartEntry.toLowerCase() === cartEntry.toLowerCase();
    });
    if (dup) {
      showCustomAddonError('This custom add-on is already in the list.');
      return false;
    }
    modalCustomAddons.push({ name: name, price: price, cartEntry: cartEntry });
    $('#pmCustomAddonName').val('');
    $('#pmCustomAddonPrice').val('');
    hideCustomAddonError();
    renderCustomAddonsList();
    syncModalOptionsFromAddons();
    return true;
  }

  function applyPreselectedAddonsToModal(entries) {
    const wanted = normalizeAddonEntries(entries);
    const apiEntries = [];
    const customItems = [];
    wanted.forEach(function (entry) {
      const parsed = parseCustomAddonCartEntry(entry);
      if (parsed) {
        customItems.push(parsed);
      } else {
        apiEntries.push(entry);
      }
    });
    const wantedLower = new Set(apiEntries.map(function (x) { return x.toLowerCase(); }));
    $('#productModal .addon-checkbox').each(function () {
      const entry = readAddonCartEntry(this).toLowerCase();
      $(this).prop('checked', entry && wantedLower.has(entry));
    });
    modalCustomAddons = customItems.map(function (p) {
      return { name: p.name, price: p.price, cartEntry: p.cartEntry };
    });
    renderCustomAddonsList();
    syncModalOptionsFromAddons();
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

  $('#productModal').on('click', '#pmAddToCartBtn', function (e) {
    e.preventDefault();
    if (typeof window.isPosCartEditable === 'function' && !window.isPosCartEditable()) {
      const msg = typeof window.getPosCartLockedMessage === 'function'
        ? window.getPosCartLockedMessage()
        : 'Adding or modifying products is not allowed for Reship orders.';
      if (typeof window.showPosMessageModal === 'function') {
        window.showPosMessageModal({ title: 'Reship order', message: msg, tone: 'warning' });
      } else {
        alert(msg);
      }
      return;
    }
    if (String($('#modal_item_level').val() || '').trim().toLowerCase() === 'parent') {
      notifyParentItemCartBlocked();
      return;
    }
    syncModalOptionsFromAddons();

    const max = modalWarehouseMaxQty;
    const q = parseInt(String($('#modal_qty').val()), 10);
    const qtyNum = Number.isFinite(q) ? q : 0;
    if (typeof max === 'number' && max > 0 && qtyNum > max) {
      alert('Maximum quantity available is ' + max + '.');
      return;
    }
    if (qtyNum < 1) {
      alert('Please enter a quantity of at least 1.');
      return;
    }

    if (typeof window.handleAddToCart === 'function') {
      const pubValRaw = $('#modal_published').val();
      window.handleAddToCart({
        code: String($('#modal_product_code').val() || '').trim(),
        qty: qtyNum || getModalQty(),
        options: String($('#modal_options').val() || '').trim(),
        variation: String($('#modal_variation').val() || '').trim(),
        item_level: String($('#modal_item_level').val() || '').trim(),
        item_code: String($('#modal_item_code').val() || '').trim(),
        size: String($('#modal_size').val() || '').trim(),
        color: String($('#modal_color').val() || '').trim(),
        product_id: $('#modal_product_id').val(),
        published: (pubValRaw === '0' || pubValRaw === 0) ? 0 : 1
      });
    }
  });

  function renderProductModal(p, key) {
    activeModalKey = key;
    activeModalProduct = p;
    resetCustomAddonsUi();
    $('#pmAddons').html('');
    $('#pmAddonsWrapper').addClass('hidden');
    const title = (p.title || '').replace(/\s+/g, ' ').trim();
    $('#pmTitle').text(title || 'Product');

    const imgSrc =
      fixModalImageSrc(p.image) ||
      'https://dummyimage.com/500x500/e5e7eb/6b7280&text=No+Image';
    $('#pmImage').attr('src', imgSrc).attr('alt', title || 'Product');

    const sqRaw = p.stock_qty;
    modalWarehouseMaxQty = resolveModalQtyCap(p);
    renderStockWarningBanner(
      p.stock_warning_message || p.stock_warning || '',
      p.stock_warning_type || ''
    );
    const $hint = $('#pmQtyMaxHint');
    if ($hint.length) {
      if (modalWarehouseMaxQty !== null && modalWarehouseMaxQty > 0) {
        $hint.text('(max ' + modalWarehouseMaxQty + ')');
      } else if (sqRaw !== null && sqRaw !== undefined && String(sqRaw).trim() !== '' && !Number.isNaN(Number(sqRaw)) && Number(sqRaw) <= 0) {
        $hint.text('(out of stock here)');
      } else {
        $hint.text('');
      }
    }

    setModalQty(modalWarehouseMaxQty === null ? 1 : (modalWarehouseMaxQty === 0 ? 0 : 1));

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

        const cartEntry = String(opt.cart_entry || '').trim();
        const addonPriceLabel = formatAddonPriceRupee(opt.price_display != null ? opt.price_display : opt.price);
        addonsHtml += `
    <label class="flex items-center justify-between gap-2 rounded-lg ${bgClass} px-3 py-2 cursor-pointer">

      <div class="flex items-center gap-2">

        <input type="checkbox"
               class="addon-checkbox h-4 w-4 ${isExpress ? 'text-green-600' : 'text-gray-600'} border-gray-300 rounded"
               data-entry="${siblingHtmlEscape(cartEntry)}">

        <div>
          <div class="text-[10px] ${textColor} leading-tight">
            ${opt.title}
          </div>
        </div>

      </div>

      <div class="text-[11px] font-semibold ${priceColor} whitespace-nowrap">
        ${getPosCurrencySymbol()} ${addonPriceLabel}
      </div>

    </label>
  `;
      });

      $('#pmAddons').html(addonsHtml);
      $('#pmAddonsWrapper').removeClass('hidden');

    } else if (p._partial) {
      $('#pmAddonsWrapper').removeClass('hidden');
      $('#pmAddons').html('<div class="text-[10px] text-gray-500 px-1 py-2">Loading add-on options…</div>');
    } else {
      $('#pmAddonsWrapper').addClass('hidden');
      $('#pmAddons').html('');
    }

    const cp = resolveCartPayload(p);
    setModalCartFields(p, cp);

    const parentItem = isParentLevelProduct(p);
    $('#modal_item_level').val(parentItem ? 'parent' : String(p.item_level || '').trim());
    const $addBtn = $('#pmAddToCartBtn');
    const cartLocked = typeof window.isPosCartEditable === 'function' && !window.isPosCartEditable();
    const addBlocked = parentItem || cartLocked;
    if ($addBtn.length) {
      $addBtn.prop('disabled', addBlocked);
      $addBtn.toggleClass('opacity-50 cursor-not-allowed', addBlocked);
      $addBtn.attr(
        'title',
        parentItem ? POS_PARENT_ITEM_CART_MSG : (cartLocked ? 'Cart is locked for Reship orders.' : '')
      );
    }

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

    const isUnpublished =
      (p.published === 0 || p.published === '0' || p.published === false) &&
      (p.is_published === false || p.is_published === 0 || p.is_published === '0') &&
      String(p.status_label || '').toLowerCase() === 'unpublished';

    if (isUnpublished) {
      badges.push(`<span class="rounded-md bg-red-100 px-2 py-1 text-[10px] font-semibold text-red-700">Status: Unpublished</span>`);
    }

    $('#pmBadges').html(badges.join(''));

    let html = '';

    if (isUnpublished) {
      html += addRow('Status', '<span class="font-semibold text-red-600">Unpublished</span>');
    }

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

    if (typeof window.updatePosCurrencyToggleUI === 'function') {
      window.updatePosCurrencyToggleUI();
    }
    updateModalPriceDisplay(p);

    renderQtySummaryUnderInput(p);

    loadSiblingSkusForProduct(p);
    applyModalOpenOptsIfPending();
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
      if (isParentLevelProduct(p)) return;

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
              <span class="text-base font-semibold tracking-tight text-gray-900 pos-product-price" data-raw-price="${p.price}">
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
    // One search box: same semantics as stock report (title OR item_code OR sku).
    const searchVal = String($('#searchName').val() || '').trim();
    // Browse default: in-stock only. When searching, include zero-stock matches.
    const stockFilterDefault = searchVal !== '' ? 'all' : 'in';
    const stockFilter = $stockFilterEl.length
      ? String($stockFilterEl.val() || stockFilterDefault)
      : stockFilterDefault;
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
        if (!append && requestedPage === 1) {
          reportMissingPosSearchTerms(rows, parsePosSearchTerms(searchVal));
        }
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

  $('#sortBy').on('change', function () {
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

  $cards.on('click', '.product-card', function () {
    const key = $(this).data('pkey');
    const code = $(this).data('code');
    if (!code) return;
    const seed = key ? productsByKey.get(String(key)) : null;
    openProductModalByCode(code, [], seed || null);
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
  function openProductModalByCode(code, preselectedAddonEntries = [], seedProduct = null, openOpts = null) {
    if (!code) return;
    window.__posModalOpenOpts = openOpts && typeof openOpts === 'object' ? openOpts : null;
    modalPreselectedAddonEntries = normalizeAddonEntries(preselectedAddonEntries);
    openModal();

    // CACHE HIT — refetch if cached before stock-context fields were added
    if (productApiCache[code] && productApiCache[code].stock_warning_type === undefined) {
      delete productApiCache[code];
    }
    if (productApiCache[code]) {
      renderProductModal(productApiCache[code], code);
      applyPreselectedAddonsToModal(modalPreselectedAddonEntries);
      return;
    }

    const seed = buildModalSeedFromGrid(seedProduct, code);
    if (seed) {
      renderProductModal(seed, code);
    } else {
      $('#pmTitle').text('Loading...');
      $('#pmDetails').html('Loading...');
      $('#pmModalPrice').addClass('hidden').text('');
      $('#modal_item_level').val('');
    }

    fetchProductApiDetails(code, modalPreselectedAddonEntries);
  }
  window.openProductModalByCode = openProductModalByCode;
  function checkAvailabilityAndMaybeOpen(product) {
    if (!product) return;
    const productId = product.id != null ? String(product.id) : '';
    const itemCode = product.item_code != null ? String(product.item_code) : '';
    const sku = product.sku != null ? String(product.sku) : '';
    const codeForPopup = sku || itemCode;
    if (!codeForPopup) return;
    openProductModalByCode(codeForPopup, [], product);
  }
  function renderModalData(p) {
    activeModalProduct = p;
    const isUnpublished =
      (p.published === 0 || p.published === '0' || p.published === false) &&
      (p.is_published === false || p.is_published === 0 || p.is_published === '0') &&
      String(p.status_label || '').toLowerCase() === 'unpublished';

    const statusRow = isUnpublished
      ? `<div>Status</div><div>:</div><div class="font-semibold text-red-600">Unpublished</div>`
      : '';

    $('#pmTitle').text(p.title || 'Product');
    $('#pmImage').attr('src', fixModalImageSrc(p.image) || '');

    $('#pmDetails').html(`
        ${statusRow}
        <div>Price</div><div>:</div><div>${formatPrice(p.price)}</div>
        <div>Material</div><div>:</div><div>${p.material || '-'}</div>
        <div>Size</div><div>:</div><div>${p.size || '-'}</div>
        <div>Color</div><div>:</div><div>${p.color || '-'}</div>
    `);

    if (typeof window.updatePosCurrencyToggleUI === 'function') {
      window.updatePosCurrencyToggleUI();
    }
    updateModalPriceDisplay(p);

    const cpMd = resolveCartPayload(p);
    setModalCartFields(p, cpMd);

    // ADDONS
    let addonsHtml = '';

    if (p.addon_options) {
      p.addon_options.default_options.forEach(opt => {
        const cartEntry = String(opt.cart_entry || '').trim();
        const addonPriceLabel = formatAddonPriceRupee(opt.price_display != null ? opt.price_display : opt.price);
        addonsHtml += `
                <label class="flex justify-between border px-3 py-2 rounded-lg">
                    <div>
                        <input type="checkbox" class="addon-checkbox"
                               data-entry="${siblingHtmlEscape(cartEntry)}">
                        ${opt.title}
                    </div>
                    <div>${getPosCurrencySymbol()} ${addonPriceLabel}</div>
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
  $(document).on('change', '#productModal .addon-checkbox', function () {
    syncModalOptionsFromAddons();
  });

  $('#productModal').on('input', '#pmCustomAddonName', function () {
    sanitizeCustomAddonNameField();
    hideCustomAddonError();
  });

  $('#productModal').on('click', '#pmCustomAddonAddBtn', function (e) {
    e.preventDefault();
    addCustomAddonFromInputs();
  });

  $('#productModal').on('click', '.pm-custom-addon-remove', function (e) {
    e.preventDefault();
    const idx = parseInt(String($(this).attr('data-idx')), 10);
    if (!Number.isFinite(idx) || idx < 0 || idx >= modalCustomAddons.length) return;
    modalCustomAddons.splice(idx, 1);
    renderCustomAddonsList();
    syncModalOptionsFromAddons();
  });

  $('#productModal').on('keydown', '#pmCustomAddonName, #pmCustomAddonPrice', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      addCustomAddonFromInputs();
    }
  });
  let searchTimeout = null;
  const $searchName = $('#searchName');
  const $skuSuggest = $('#skuSuggest');
  const $searchErr = $('#posSkuSearchError');
  const skuSearchBase = '?page=products&action=search_product';
  let activeSuggestRequest = 0;
  let activeSuggestIndex = -1;

  function setActiveSuggestIndex(idx) {
    const $buttons = $skuSuggest.find('button[data-sku]');
    if (!$buttons.length) {
      activeSuggestIndex = -1;
      return null;
    }
    const safeIdx = Math.max(0, Math.min(idx, $buttons.length - 1));
    $buttons.removeClass('bg-orange-50 ring-1 ring-orange-200/80');
    const $active = $buttons.eq(safeIdx);
    $active.addClass('bg-orange-50 ring-1 ring-orange-200/80');
    activeSuggestIndex = safeIdx;
    if ($active.length && $active[0].scrollIntoView) {
      $active[0].scrollIntoView({ block: 'nearest' });
    }
    return $active;
  }

  function getActiveSuggestButton() {
    const $buttons = $skuSuggest.find('button[data-sku]');
    if (!$buttons.length || $skuSuggest.hasClass('hidden')) {
      return null;
    }
    if (activeSuggestIndex >= 0 && activeSuggestIndex < $buttons.length) {
      return $buttons.eq(activeSuggestIndex);
    }
    return $buttons.eq(0);
  }

  function applySuggestSelection($btn) {
    if (!$btn || !$btn.length) return;
    const sku = ($btn.data('sku') || '').toString();
    const itemCode = ($btn.data('item-code') || '').toString();
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
  }

  /** Split POS search into terms when comma/semicolon/newline/tab present; else one phrase. */
  function parsePosSearchTerms(raw) {
    const s = String(raw || '').trim();
    if (!s) return [];
    if (/[,;\r\n\t]/.test(s)) {
      return s
        .split(/[\s,;]+/)
        .map(function (part) { return String(part || '').trim(); })
        .filter(Boolean)
        .filter(function (term, idx, arr) { return arr.indexOf(term) === idx; });
    }
    return [s];
  }

  /** Last token while typing a comma/newline-separated multi-SKU query. */
  function activePosSearchFragment(raw) {
    const s = String(raw || '');
    if (!/[,;\r\n\t]/.test(s)) {
      return s.trim();
    }
    const parts = s.split(/[\s,;]+/);
    return String(parts[parts.length - 1] || '').trim();
  }

  function rowMatchesPosSearchTerm(row, term) {
    const tl = String(term || '').trim().toLowerCase();
    if (!tl) return false;
    const sku = String(row.sku || '').trim().toLowerCase();
    const itemCode = String(row.item_code || '').trim().toLowerCase();
    return sku === tl || itemCode === tl;
  }

  function reportMissingPosSearchTerms(rows, terms) {
    if (!terms || terms.length <= 1) return;
    const missing = terms.filter(function (term) {
      return !rows.some(function (row) { return rowMatchesPosSearchTerm(row, term); });
    });
    if (missing.length) {
      showSearchError(
        missing.length === 1
          ? ('SKU not found: ' + missing[0])
          : ('SKUs not found: ' + missing.join(', '))
      );
    }
  }

  function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, function (c) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
    });
  }

  function hideSuggest() {
    $searchName.attr('aria-expanded', 'false');
    $skuSuggest.addClass('hidden').empty().css('display', 'none');
    activeSuggestIndex = -1;
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

  function runPosSearch() {
    hideSuggest();
    hideSearchError();
    const q = String($searchName.val() || '').trim();
    if (q.length < 1) {
      showSearchError('Enter a SKU or product name.');
      return;
    }

    const terms = parsePosSearchTerms(q);
    if (terms.length > 1) {
      resetAndLoad();
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

  function clearPosSearch() {
    $searchName.val('');
    hideSuggest();
    hideSearchError();
    resetAndLoad();
  }

  function renderSuggest(rows) {
    if (!rows || rows.length === 0) {
      hideSuggest();
      return;
    }

    const html = rows.slice(0, 12).map(function (p, idx) {
      const sku = p.sku || p.item_code || p.code || '';
      const itemCode = p.item_code || p.itemcode || '';
      const title = p.title || p.name || '';
      const trimmedTitle = title.length > 72 ? (title.slice(0, 69) + '...') : title;
      const activeCls = idx === 0 ? ' bg-orange-50 ring-1 ring-orange-200/80' : '';
      return `
        <button type="button"
          class="w-full text-left px-3 py-2 hover:bg-slate-50 transition border-b border-slate-100 last:border-0${activeCls}"
          data-sku="${escapeHtml(sku)}"
          data-item-code="${escapeHtml(itemCode)}"
          role="option"
          aria-selected="${idx === 0 ? 'true' : 'false'}">
          <div class="min-w-0">
            <div class="text-xs font-semibold text-slate-800 truncate">${escapeHtml(sku)}</div>
            <div class="text-[11px] text-slate-500 truncate">${escapeHtml(itemCode)}${trimmedTitle ? (' · ' + escapeHtml(trimmedTitle)) : ''}</div>
          </div>
        </button>
      `;
    }).join('');

    $searchName.attr('aria-expanded', 'true');
    $skuSuggest.html(html).removeClass('hidden').css('display', 'block');
    activeSuggestIndex = rows.length ? 0 : -1;
  }

  let suggestTimeout = null;
  function fetchSuggest(term) {
    const terms = parsePosSearchTerms(term);
    if (terms.length > 1) {
      hideSuggest();
      return;
    }

    const t = activePosSearchFragment(term);
    if (t.length < 2) {
      hideSuggest();
      return;
    }

    clearTimeout(suggestTimeout);
    suggestTimeout = setTimeout(function () {
      const reqId = ++activeSuggestRequest;
      fetch(skuSearchBase + '&q=' + encodeURIComponent(t) + '&by=sku', {
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
    applySuggestSelection($(this));
  });

  $searchName.on('blur', function () {
    // allow click selection before hiding
    setTimeout(hideSuggest, 150);
  });

  $searchName.on('keydown', function (e) {
    const suggestOpen = !$skuSuggest.hasClass('hidden') && $skuSuggest.find('button[data-sku]').length > 0;
    if (e.key === 'Escape') {
      hideSuggest();
      return;
    }
    if (suggestOpen && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
      e.preventDefault();
      const nextIdx = e.key === 'ArrowDown'
        ? (activeSuggestIndex < 0 ? 0 : activeSuggestIndex + 1)
        : (activeSuggestIndex < 0 ? 0 : activeSuggestIndex - 1);
      setActiveSuggestIndex(nextIdx);
      return;
    }
    // Enter without Shift: pick highlighted suggestion or run search. Shift+Enter inserts a newline in the textarea.
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      if (suggestOpen) {
        const $active = getActiveSuggestButton();
        if ($active && $active.length) {
          applySuggestSelection($active);
          return;
        }
      }
      runPosSearch();
    }
  });

  $('#posSearchBtn').on('click', function (e) {
    e.preventDefault();
    runPosSearch();
  });

  $('#posSearchClearBtn').on('click', function (e) {
    e.preventDefault();
    clearPosSearch();
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
      if (isLoading || currentPage <= 1) return;
      fetchProducts(currentPage - 1, false);
    });
  }
  if ($pageNext.length) {
    $pageNext.on('click', function () {
      if (isLoading || !hasMore) return;
      fetchProducts(currentPage + 1, true);
    });
  }

  // Initial load – products grid only (skip on cart-table page)
  if ($cards.length) {
    resetAndLoad();
  }
});

// In pos.js, add this
$('#addonSelect').on('change', function () {
  let val = $(this).val();
  let optionsStr = val !== '0' ? 'OPTIONALS_GIFTWRAP:blank:' + val : '';
  $('#modal_options').val(optionsStr);  // add <input type="hidden" name="options" id="modal_options"> in form
});