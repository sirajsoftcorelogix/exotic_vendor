/**
 * Exotic India cart (server-backed via same-origin cart-api proxy → https://www.exoticindia.com/api/cart/*).
 * Totals and lines are rendered only from the latest /cart/retrieve response — no local cart model.
 */
(function (window) {
  'use strict';

  var cartActionLock = false;
  var cartDelegatesBound = false;
  var PANEL_ID = 'posExoticCartPanel';
  var MODAL_ID = 'posCartApiDebugModal';
  /** @type {Record<string, unknown>|null} */
  var lastCartApiDebug = null;

  function setLastCartApiDebug(entry) {
    lastCartApiDebug = Object.assign({ at: new Date().toISOString() }, entry);
  }

  function ensureCartApiDebugModal() {
    var m = document.getElementById(MODAL_ID);
    if (m) {
      return m;
    }
    m = document.createElement('div');
    m.id = MODAL_ID;
    m.className =
      'fixed inset-0 z-[10000] hidden flex items-center justify-center bg-black/50 p-4';
    m.setAttribute('role', 'dialog');
    m.setAttribute('aria-modal', 'true');
    m.setAttribute('aria-label', 'Cart API debug');
    m.innerHTML =
      '<div id="posCartApiDebugBackdrop" class="absolute inset-0 bg-black/40" aria-hidden="true"></div>' +
      '<div class="pos-cart-api-debug-inner relative z-10 max-h-[88vh] w-full max-w-3xl overflow-hidden rounded-xl bg-white shadow-xl flex flex-col shadow-2xl">' +
      '<div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-4 py-2">' +
      '<span class="text-sm font-semibold text-slate-800">Last cart API (proxy)</span>' +
      '<button type="button" class="pos-cart-api-debug-close rounded px-2 py-1 text-slate-500 hover:bg-slate-100" aria-label="Close">✕</button>' +
      '</div>' +
      '<pre id="posCartApiDebugPre" class="min-h-[120px] flex-1 overflow-auto p-4 text-[10px] font-mono leading-relaxed text-slate-800 whitespace-pre-wrap break-words"></pre>' +
      '</div>';
    m.style.position = 'fixed';
    m.style.inset = '0';
    m.style.zIndex = '10050';
    m.style.display = 'none';
    m.style.alignItems = 'center';
    m.style.justifyContent = 'center';
    m.style.padding = '1rem';
    m.style.boxSizing = 'border-box';
    document.body.appendChild(m);
    var backdrop = document.getElementById('posCartApiDebugBackdrop');
    if (backdrop) {
      backdrop.addEventListener('click', function () {
        closePosCartApiDebugModal();
      });
    }
    return m;
  }

  function closePosCartApiDebugModal() {
    var m = document.getElementById(MODAL_ID);
    if (m) {
      m.classList.add('hidden');
      m.style.display = 'none';
    }
  }

  function cloneSafeJson(value) {
    if (value === undefined) {
      return undefined;
    }
    try {
      return JSON.parse(JSON.stringify(value));
    } catch (e) {
      return '[unserializable or circular: ' + String(e && e.message ? e.message : e) + ']';
    }
  }

  function openPosCartApiDebugModal() {
    if (!lastCartApiDebug) {
      toast('No cart API call recorded yet.', 'red');
      return;
    }
    var m = ensureCartApiDebugModal();
    var pre = document.getElementById('posCartApiDebugPre');
    if (!pre) {
      return;
    }
    var pr = lastCartApiDebug.parsedProxyResponse;
    var out = {
      note:
        'Vendor calls same-origin ?page=pos_register&action=cart-api — server forwards to https://www.exoticindia.com/api/cart/* with x-api-* headers.',
      request: {
        time: lastCartApiDebug.at,
        op: lastCartApiDebug.op,
        method: lastCartApiDebug.method,
        url: lastCartApiDebug.requestUrl,
        fetchHttpStatus: lastCartApiDebug.fetchHttpStatus,
        headers: lastCartApiDebug.requestHeaders || {},
        jsonBody: lastCartApiDebug.requestBody != null ? lastCartApiDebug.requestBody : undefined
      },
      response: pr
        ? {
            success: pr.success,
            http_code: pr.http_code,
            message: pr.message,
            parseError: pr.parseError,
            data: cloneSafeJson(pr.data),
            raw: pr.raw
          }
        : { networkError: lastCartApiDebug.networkError },
      rawResponseText: lastCartApiDebug.rawProxyResponseText
    };
    try {
      pre.textContent = JSON.stringify(out, null, 2);
    } catch (e) {
      pre.textContent =
        'Could not stringify debug payload: ' +
        String(e && e.message ? e.message : e) +
        '\n\n' +
        String(lastCartApiDebug && lastCartApiDebug.op ? 'op=' + lastCartApiDebug.op : '');
    }
    m.classList.remove('hidden');
    m.style.display = 'flex';
  }

  window.openPosCartApiDebugModal = openPosCartApiDebugModal;
  window.closePosCartApiDebugModal = closePosCartApiDebugModal;

  function toast(msg, color) {
    if (typeof window.showToast === 'function') {
      window.showToast(msg, color || 'red');
      return;
    }
    if (typeof console !== 'undefined' && console.warn) {
      console.warn('[POS cart]', msg);
    }
  }

  function cartUrl(op, query) {
    var qs = 'page=pos_register&action=cart-api&op=' + encodeURIComponent(op);
    query = query || {};
    Object.keys(query).forEach(function (k) {
      if (query[k] == null || query[k] === '') {
        return;
      }
      qs += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(String(query[k]));
    });
    return '?' + qs;
  }

  function cartRequest(op, opt) {
    opt = opt || {};
    var method = (opt.method || 'GET').toUpperCase();
    var url = cartUrl(op, opt.query);
    var init = {
      method: method,
      credentials: 'same-origin',
      headers: {}
    };
    if (opt.jsonBody != null) {
      init.headers['Content-Type'] = 'application/json';
      init.body = JSON.stringify(opt.jsonBody);
    }
    var reqHeaders = {};
    Object.keys(init.headers).forEach(function (k) {
      reqHeaders[k] = init.headers[k];
    });
    return fetch(url, init).then(function (res) {
      return res.text().then(function (text) {
        var cleaned = text.replace(/^\uFEFF/, '').trim();
        var maxRaw = 200000;
        var rawPreview =
          cleaned.length > maxRaw ? cleaned.slice(0, maxRaw) + '\n…(truncated)' : cleaned;
        var r;
        try {
          var parsed = cleaned ? JSON.parse(cleaned) : {};
          r = {
            success: !!parsed.success,
            http_code: parsed.http_code != null ? parsed.http_code : res.status,
            data: parsed.data || {},
            raw: parsed.raw || '',
            message: parsed.message || (parsed.data && parsed.data.message) || ''
          };
        } catch (e) {
          r = {
            success: false,
            http_code: res.status,
            data: {},
            raw: cleaned,
            parseError: true,
            message: 'Invalid JSON from cart API'
          };
        }
        setLastCartApiDebug({
          op: op,
          method: method,
          requestUrl: url,
          requestHeaders: reqHeaders,
          requestBody: opt.jsonBody != null ? opt.jsonBody : null,
          fetchHttpStatus: res.status,
          parsedProxyResponse: r,
          rawProxyResponseText: rawPreview
        });
        return r;
      });
    }).catch(function (err) {
      setLastCartApiDebug({
        op: op,
        method: method,
        requestUrl: url,
        requestHeaders: reqHeaders,
        requestBody: opt.jsonBody != null ? opt.jsonBody : null,
        fetchHttpStatus: null,
        parsedProxyResponse: null,
        rawProxyResponseText: null,
        networkError: err && err.message ? err.message : String(err)
      });
      toast(err && err.message ? err.message : 'Cart request failed', 'red');
      return { success: false, http_code: 0, data: {}, raw: '', message: 'Network error' };
    });
  }

  function cartHandleApiMessages(r) {
    if (!r || r.parseError) {
      toast((r && r.message) || 'Cart response error', 'red');
      return;
    }
    if (!r.success) {
      var msg =
        r.message ||
        (r.data && (r.data.message || r.data.error || r.data.errormessage)) ||
        ('Request failed (HTTP ' + (r.http_code || '') + ')');
      if (String(r.raw || '').length && String(msg).length < 3) {
        msg = 'Cart API error — check response';
      }
      toast(String(msg), 'red');
    }
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function pickFirst(obj, keys) {
    if (!obj || typeof obj !== 'object') {
      return null;
    }
    for (var i = 0; i < keys.length; i++) {
      var k = keys[i];
      if (obj[k] != null && obj[k] !== '') {
        return obj[k];
      }
    }
    return null;
  }

  function pickNumber(obj, keys) {
    var v = pickFirst(obj, keys);
    if (v == null) {
      return null;
    }
    var n = parseFloat(String(v).replace(/,/g, ''));
    return isNaN(n) ? null : n;
  }

  function getCartItems(data) {
    if (!data || typeof data !== 'object') {
      return [];
    }
    var items = data.cartitems || data.cart_items || data.items || data.lines;
    return Array.isArray(items) ? items : [];
  }

  function lineCartRef(row) {
    // Retrieve payload uses cartref for the line id; modifyqty URL param is cartid (same value).
    return String(
      pickFirst(row, [
        'cartref',
        'cart_ref',
        'cartid',
        'cart_id',
        'CartId',
        'cartitem_id',
        'line_id',
        'cart_item_id'
      ]) || ''
    ).trim();
  }

  function lineQty(row) {
    var q = pickFirst(row, ['quantity', 'qty', 'prqt']);
    var n = parseInt(String(q == null ? '1' : q), 10);
    return isNaN(n) || n < 1 ? 1 : n;
  }

  /**
   * Exotic cart line / browse history sellable cap (not computed locally — from API fields).
   * @param {Record<string, unknown>} row
   * @param {Record<string, unknown>} [cartData]
   * @returns {number|null} positive max qty, or null if unknown / unlimited
   */
  function lineMaxSellableQty(row, cartData) {
    var fromLine = pickNumber(row, ['availability', 'avail_qty', 'stock', 'max_qty', 'maxqty']);
    if (fromLine != null && fromLine >= 1) {
      return Math.floor(fromLine);
    }
    var code = String(pickFirst(row, ['code', 'item_code', 'sku']) || '').trim();
    if (!code || !cartData || typeof cartData !== 'object') {
      return null;
    }
    var bh = cartData.browsing_history;
    var products = bh && typeof bh === 'object' ? bh.products : null;
    if (!Array.isArray(products)) {
      return null;
    }
    var upper = code.toUpperCase();
    for (var i = 0; i < products.length; i++) {
      var p = products[i];
      if (String((p && p.itemcode) || '').toUpperCase() !== upper) {
        continue;
      }
      var st = pickNumber(p, ['stock']);
      if (st != null && st >= 1) {
        return Math.floor(st);
      }
      return null;
    }
    return null;
  }

  function lineTitle(row) {
    return String(
      pickFirst(row, ['title', 'name', 'product_name', 'item_name', 'description']) || 'Item'
    );
  }

  function lineSubDisplay(row) {
    var sku = pickFirst(row, ['sku', 'item_code', 'code']);
    return sku ? String(sku) : '';
  }

  function parseMoneyValue(val) {
    if (val == null || val === '') {
      return null;
    }
    var n = parseFloat(String(val).replace(/,/g, ''));
    return isNaN(n) ? null : n;
  }

  /** Unit price from cart line (API fields vary). */
  function lineUnitPriceStr(row) {
    var v = pickFirst(row, ['unit_price', 'item_price', 'single_price', 'original_price', 'price', 'selling_price']);
    return v != null && String(v) !== '' ? String(v) : '';
  }

  /** Line amount from API or unit price × qty when Exotic omits an explicit line total. */
  function lineLineTotalStr(row, qty) {
    var explicit = pickFirst(row, [
      'line_total',
      'linetotal',
      'line_total_amount',
      'lineamount',
      'line_amount',
      'extended_price',
      'row_total',
      'amount'
    ]);
    if (explicit != null && String(explicit) !== '') {
      return String(explicit);
    }
    var unit = parseMoneyValue(
      pickFirst(row, ['unit_price', 'item_price', 'original_price', 'price', 'selling_price'])
    );
    if (unit != null && qty >= 1) {
      var t = unit * qty;
      return Math.abs(t - Math.round(t)) < 1e-9 ? String(Math.round(t)) : t.toFixed(2);
    }
    return '';
  }

  function sumLineTotalsFromCartItems(cartData) {
    var items = getCartItems(cartData || {});
    if (!items.length) {
      return null;
    }
    var sum = 0;
    var ok = false;
    for (var i = 0; i < items.length; i++) {
      var qty = lineQty(items[i]);
      var ltStr = lineLineTotalStr(items[i], qty);
      var n = parseMoneyValue(ltStr);
      if (n != null) {
        sum += n;
        ok = true;
      }
    }
    return ok ? sum : null;
  }

  /** Sum GST in currency from lines only — never use `gst` on the line (that is typically the % slab, e.g. 3). */
  function lineGstAmountRupee(row) {
    return pickNumber(row, [
      'gstamount',
      'gst_amount',
      'line_gst_amount',
      'line_gst_total',
      'line_tax',
      'line_tax_amount',
      'tax_amount',
      'igst_amount',
      'cgst_amount',
      'sgst_amount',
      'total_line_gst'
    ]);
  }

  function sumGstFromCartLineItems(cartData) {
    var items = getCartItems(cartData || {});
    if (!items.length) {
      return null;
    }
    var sum = 0;
    var ok = false;
    for (var i = 0; i < items.length; i++) {
      var g = lineGstAmountRupee(items[i]);
      if (g != null) {
        sum += g;
        ok = true;
      }
    }
    return ok ? sum : null;
  }

  function totalsFromRetrieve(data) {
    var d = data && typeof data === 'object' ? data : {};
    var cd = typeof d.checkoutdata === 'object' && d.checkoutdata !== null ? d.checkoutdata : {};

    var sub =
      pickNumber(d, [
        'subtotal',
        'sub_total',
        'cart_subtotal',
        'items_total',
        'items_subtotal',
        'pretax_total',
        'merchandise_total'
      ]) || pickNumber(cd, ['subtotal', 'sub_total', 'items_total']);
    if (sub == null) {
      sub = sumLineTotalsFromCartItems(d);
    }

    var gstTotal =
      pickNumber(d, [
        'gst_total',
        'total_gst',
        'total_gst_amount',
        'totalgst',
        'gst_tax_total',
        'tax_total',
        'total_tax',
        'gst_amount',
        'gstamount'
      ]) || pickNumber(cd, ['gst_total', 'total_gst', 'total_gst_amount', 'tax_total']);
    if (gstTotal == null) {
      gstTotal = sumGstFromCartLineItems(d);
    }

    var couponDeduction =
      pickNumber(d, [
        'couponreduction',
        'coupon_reduction',
        'coupon_discount_total',
        'coupon_discount',
        'coupondiscount',
        'coupon_discount_amount'
      ]) || pickNumber(cd, ['coupon_discount', 'coupondiscount']);

    var customDeduction =
      pickNumber(d, [
        'customreduction',
        'custom_reduction',
        'custom_discount',
        'customdiscount',
        'custom_reduce'
      ]) || pickNumber(cd, ['custom_discount', 'customdiscount']);

    var grandTotal =
      pickNumber(d, [
        'totalamount',
        'grandtotal',
        'grand_total',
        'total',
        'amount_payable',
        'payable_total',
        'order_total'
      ]) || pickNumber(cd, ['grandtotal', 'grand_total', 'total', 'totalamount']);

    return {
      subtotal: sub,
      gstTotal: gstTotal,
      couponDeduction: couponDeduction,
      customDeduction: customDeduction,
      grandTotal: grandTotal
    };
  }

  function formatMoneyDisplay(val) {
    if (val == null || (typeof val === 'number' && isNaN(val))) {
      return null;
    }
    if (typeof val === 'number') {
      return Math.abs(val - Math.round(val)) < 1e-9 ? String(Math.round(val)) : val.toFixed(2);
    }
    return String(val);
  }

  function isAmountGreaterThanZero(val) {
    if (val == null || val === '') {
      return false;
    }
    var n = typeof val === 'number' ? val : parseFloat(String(val).replace(/,/g, ''));
    return !isNaN(n) && n > 0;
  }

  /** Always one row; uses em dash when API did not supply a number. */
  function moneyRowSummary(label, val, isGrand) {
    var disp = formatMoneyDisplay(val);
    var text = disp == null ? '—' : disp;
    var rowClass = isGrand
      ? 'flex justify-between text-sm font-semibold text-slate-900 pt-2 border-t border-slate-100 mt-1'
      : 'flex justify-between text-xs text-slate-700 py-0.5';
    return (
      '<div class="' +
      rowClass +
      '"><span>' +
      escapeHtml(label) +
      '</span><span class="tabular-nums ' +
      (disp == null ? 'text-slate-400' : '') +
      '">' +
      escapeHtml(text) +
      '</span></div>'
    );
  }

  function ensureCartPanel() {
    var el = document.getElementById(PANEL_ID);
    if (el) {
      return el;
    }
    var aside = document.querySelector('aside.col-span-12');
    if (!aside) {
      return null;
    }
    var sticky = aside.querySelector('.sticky');
    if (!sticky) {
      return null;
    }
    var ph = sticky.querySelector('.px-4.py-6.space-y-3');
    if (ph) {
      ph.style.display = 'none';
    }
    el = document.createElement('div');
    el.id = PANEL_ID;
    el.className = 'pos-exotic-cart-panel border-t border-slate-100 px-4 py-3 text-sm text-slate-800';
    sticky.appendChild(el);
    return el;
  }

  function setPanelBusy(busy) {
    var p = document.getElementById(PANEL_ID);
    if (!p) {
      return;
    }
    p.setAttribute('aria-busy', busy ? 'true' : 'false');
    p.style.opacity = busy ? '0.65' : '';
    p.style.pointerEvents = busy ? 'none' : '';
  }

  function renderCartUI(data) {
    var panel = ensureCartPanel();
    if (!panel) {
      return;
    }
    var items = getCartItems(data);
    var totals = totalsFromRetrieve(data || {});
    var html = '';

    if (!items.length) {
      html += '<p class="text-xs text-slate-500 py-2">Cart is empty.</p>';
    } else {
      html += '<div class="space-y-1 max-h-[50vh] overflow-y-auto pr-1">';
      items.forEach(function (row) {
        var ref = lineCartRef(row);
        var qty = lineQty(row);
        var maxSell = lineMaxSellableQty(row, data || {});
        var title = lineTitle(row);
        var sub = lineSubDisplay(row);
        var unitPrice = lineUnitPriceStr(row);
        var lineTotal = lineLineTotalStr(row, qty);
        html += '<div class="border-b border-slate-100 py-2 last:border-0" data-cart-row="1">';
        html += '<div class="text-xs font-medium text-slate-900 leading-snug">' + escapeHtml(title) + '</div>';
        if (sub) {
          html += '<div class="text-[10px] text-slate-500">' + escapeHtml(sub) + '</div>';
        }
        if (unitPrice) {
          html +=
            '<div class="text-[10px] text-slate-600 mt-0.5">' +
            '<span class="text-slate-500">Price:</span> ' +
            '<span class="tabular-nums font-medium text-slate-800">' +
            escapeHtml(unitPrice) +
            '</span></div>';
        }
        if (lineTotal) {
          html +=
            '<div class="text-[10px] text-slate-800 mt-0.5">' +
            '<span class="text-slate-500">Amount:</span> ' +
            '<span class="tabular-nums font-semibold">' +
            escapeHtml(lineTotal) +
            '</span></div>';
        }
        html += '<div class="mt-1 flex flex-wrap items-center gap-2">';
        if (ref) {
          var maxAttr =
            maxSell != null && maxSell >= 1 ? ' max="' + escapeHtml(String(maxSell)) + '" data-max-qty="' + escapeHtml(String(maxSell)) + '"' : '';
          var hint =
            maxSell != null && maxSell >= 1
              ? '<span class="text-[10px] text-slate-500 w-full basis-full">Max ' +
                escapeHtml(String(maxSell)) +
                ' per order</span>'
              : '';
          html +=
            '<label class="text-[10px] text-slate-500">Qty</label>' +
            '<input type="number" min="1" step="1" class="pos-cart-qty-input w-14 border border-slate-200 rounded px-1 py-0.5 text-xs"' +
            maxAttr +
            ' data-cartref="' +
            escapeHtml(ref) +
            '" value="' +
            escapeHtml(String(qty)) +
            '" title="' +
            (maxSell != null && maxSell >= 1 ? escapeHtml('Maximum ' + maxSell + ' per order') : '') +
            '" />' +
            hint +
            '<button type="button" class="pos-cart-delete-btn text-xs text-red-600 hover:underline" data-cartref="' +
            escapeHtml(ref) +
            '">Remove</button>';
        } else {
          html += '<span class="text-[10px] text-amber-700">Missing cart reference — cannot update line.</span>';
        }
        html += '</div></div>';
      });
      html += '</div>';
    }

    var showSummary =
      items.length > 0 ||
      totals.subtotal != null ||
      totals.gstTotal != null ||
      isAmountGreaterThanZero(totals.couponDeduction) ||
      isAmountGreaterThanZero(totals.customDeduction) ||
      totals.grandTotal != null;
    if (showSummary) {
      html += '<div class="mt-3 pt-2 border-t border-slate-200 space-y-0.5">';
      html += moneyRowSummary('Sub total', totals.subtotal, false);
      html += moneyRowSummary('GST total', totals.gstTotal, false);
      if (isAmountGreaterThanZero(totals.couponDeduction)) {
        html += moneyRowSummary('Coupon discount deduction', totals.couponDeduction, false);
      }
      if (isAmountGreaterThanZero(totals.customDeduction)) {
        html += moneyRowSummary('Custom discount deduction', totals.customDeduction, false);
      }
      html += moneyRowSummary('Grand total', totals.grandTotal, true);
      html += '</div>';
    }

    html += '<div class="mt-3 pt-2 border-t border-slate-200 space-y-2">';
    html += '<div class="text-xs font-medium text-slate-700">Coupon</div>';
    html +=
      '<div class="flex gap-1 flex-wrap">' +
      '<input type="text" class="pos-cart-coupon-input flex-1 min-w-[100px] border border-slate-200 rounded px-2 py-1 text-xs" placeholder="Coupon ID" />' +
      '<button type="button" class="pos-cart-coupon-apply shrink-0 bg-slate-800 text-white px-2 py-1 rounded text-xs">Apply</button>' +
      '<button type="button" class="pos-cart-coupon-clear shrink-0 border border-slate-200 px-2 py-1 rounded text-xs">Clear</button>' +
      '</div>';
    html += '<div class="text-xs font-medium text-slate-700 pt-1">Custom discount</div>';
    html +=
      '<div class="flex gap-1 flex-wrap">' +
      '<input type="number" step="0.01" min="0" class="pos-cart-customdisc-input flex-1 min-w-[80px] border border-slate-200 rounded px-2 py-1 text-xs" placeholder="Amount (0 removes)" />' +
      '<button type="button" class="pos-cart-customdisc-apply shrink-0 bg-slate-800 text-white px-2 py-1 rounded text-xs">Set</button>' +
      '</div>';
    html += '</div>';

    html +=
      '<div class="mt-3 pt-2 border-t border-slate-200 text-center">' +
      '<button type="button" class="pos-cart-api-debug-link text-xs text-blue-700 hover:underline" ' +
      'onclick="event.preventDefault();event.stopPropagation();if(typeof window.openPosCartApiDebugModal===\'function\'){window.openPosCartApiDebugModal();}">' +
      'Last API request / response' +
      '</button>' +
      '</div>';

    panel.innerHTML = html;
  }

  function bindCartDelegatesOnce() {
    if (cartDelegatesBound) {
      return;
    }
    cartDelegatesBound = true;

    document.addEventListener('keydown', function (ev) {
      if (ev.key !== 'Escape') {
        return;
      }
      var modal = document.getElementById(MODAL_ID);
      if (modal && !modal.classList.contains('hidden')) {
        closePosCartApiDebugModal();
      }
    });

    document.body.addEventListener(
      'change',
      function (e) {
        var t = e.target;
        if (!t || !t.matches || !t.matches('.pos-cart-qty-input')) {
          return;
        }
        var panel = document.getElementById(PANEL_ID);
        if (!panel || !panel.contains(t)) {
          return;
        }
        var ref = String(t.getAttribute('data-cartref') || '').trim();
        var qty = parseInt(String(t.value), 10);
        if (!ref) {
          return;
        }
        if (!qty || qty < 1) {
          toast('Quantity must be at least 1', 'red');
          return;
        }
        var maxAttr = t.getAttribute('data-max-qty');
        var maxQ = maxAttr != null && maxAttr !== '' ? parseInt(String(maxAttr), 10) : NaN;
        if (!isNaN(maxQ) && maxQ >= 1 && qty > maxQ) {
          toast('Maximum quantity for this item is ' + maxQ + ' per order.', 'red');
          t.value = String(maxQ);
          return;
        }
        window.handleUpdateQty({ cartref: ref, qty: qty });
      },
      false
    );

    document.body.addEventListener(
      'click',
      function (e) {
        var dbgClose = e.target && e.target.closest ? e.target.closest('.pos-cart-api-debug-close') : null;
        if (dbgClose) {
          closePosCartApiDebugModal();
          return;
        }
        var panel = document.getElementById(PANEL_ID);
        if (!panel) {
          return;
        }
        var dbgLink = e.target && e.target.closest ? e.target.closest('.pos-cart-api-debug-link') : null;
        if (dbgLink && panel.contains(dbgLink)) {
          openPosCartApiDebugModal();
          return;
        }
        var del = e.target && e.target.closest ? e.target.closest('.pos-cart-delete-btn') : null;
        if (del && panel.contains(del)) {
          var refD = String(del.getAttribute('data-cartref') || '').trim();
          if (refD) {
            window.handleDeleteItem({ cartref: refD });
          }
          return;
        }
        var cap = e.target && e.target.closest ? e.target.closest('.pos-cart-coupon-apply') : null;
        if (cap && panel.contains(cap)) {
          var inp = panel.querySelector('.pos-cart-coupon-input');
          var v = inp ? String(inp.value || '').trim() : '';
          window.applyCoupon(v);
          return;
        }
        var ccl = e.target && e.target.closest ? e.target.closest('.pos-cart-coupon-clear') : null;
        if (ccl && panel.contains(ccl)) {
          window.applyCoupon('');
          return;
        }
        var cad = e.target && e.target.closest ? e.target.closest('.pos-cart-customdisc-apply') : null;
        if (cad && panel.contains(cad)) {
          var di = panel.querySelector('.pos-cart-customdisc-input');
          var num = di ? parseFloat(String(di.value)) : NaN;
          window.applyCustomDiscount(isNaN(num) ? 0 : num);
        }
      },
      false
    );
  }

  /**
   * @param {function(): void|Promise<void>} fn
   * @returns {void|Promise<void>}
   */
  function withCartLock(fn) {
    if (cartActionLock) {
      return undefined;
    }
    cartActionLock = true;
    var result;
    try {
      result = fn();
    } catch (e) {
      cartActionLock = false;
      console.error('[POS cart hooks]', e);
      toast(String(e && e.message ? e.message : e), 'red');
      return undefined;
    }
    if (result && typeof result.then === 'function') {
      return result.finally(function () {
        cartActionLock = false;
      });
    }
    cartActionLock = false;
    return result;
  }

  function refreshCartInternal() {
    return cartRequest('retrieve', {}).then(function (r) {
      cartHandleApiMessages(r);
      if (r && r.success && r.data && typeof r.data === 'object') {
        renderCartUI(r.data);
      } else if (r && r.data && typeof r.data === 'object' && Object.keys(r.data).length) {
        renderCartUI(r.data);
      } else {
        renderCartUI({});
      }
      return r;
    });
  }

  function findCartLineByCode(cartData, codeUpper) {
    var items = getCartItems(cartData || {});
    for (var i = 0; i < items.length; i++) {
      var row = items[i];
      var c = String(pickFirst(row, ['code', 'item_code', 'sku']) || '').toUpperCase();
      if (c === codeUpper) {
        return row;
      }
    }
    return null;
  }

  function findCartLineByCartref(cartData, ref) {
    var r = String(ref || '').trim();
    if (!r) {
      return null;
    }
    var items = getCartItems(cartData || {});
    for (var i = 0; i < items.length; i++) {
      var row = items[i];
      if (lineCartRef(row) === r) {
        return row;
      }
    }
    return null;
  }

  /**
   * After a successful add/modify + retrieve, warn if Exotic kept fewer units than requested (stock cap).
   * @param {number} requestedQty
   * @param {Record<string, unknown>} cartData
   * @param {{ code?: string, cartref?: string }} match
   */
  function toastIfQtyCappedAfterSuccess(requestedQty, cartData, match) {
    if (!cartData || typeof cartData !== 'object' || requestedQty < 1) {
      return;
    }
    var row = null;
    if (match.code) {
      row = findCartLineByCode(cartData, String(match.code).toUpperCase());
    } else if (match.cartref) {
      row = findCartLineByCartref(cartData, match.cartref);
    }
    if (!row) {
      return;
    }
    var actual = lineQty(row);
    if (requestedQty <= actual) {
      return;
    }
    var maxS = lineMaxSellableQty(row, cartData);
    var capHint =
      maxS != null && maxS >= 1
        ? ' Maximum per order for this item is ' + maxS + '.'
        : ' Quantity is limited per order for this item.';
    toast(
      'You requested ' +
        requestedQty +
        ' unit(s), but only ' +
        actual +
        ' ' +
        (actual === 1 ? 'is' : 'are') +
        ' in the cart (less than requested).' +
        capHint,
      'red'
    );
  }

  /** @param {Record<string, unknown>} [payload] */
  window.handleAddToCart = function (payload) {
    return withCartLock(function () {
      var p = payload || {};
      var body = {
        code: String(p.code || '').trim(),
        qty: parseInt(String(p.qty != null ? p.qty : 1), 10) || 1,
        variation: String(p.variation || ''),
        options: String(p.options || '')
      };
      if (p.stock_check_code != null && String(p.stock_check_code).trim() !== '') {
        body.stock_check_code = String(p.stock_check_code).trim();
      }
      if (!body.code) {
        toast('Missing product code', 'red');
        return undefined;
      }
      setPanelBusy(true);
      var requestedQty = body.qty;
      return cartRequest('add', { method: 'POST', jsonBody: body })
        .then(function (r) {
          cartHandleApiMessages(r);
          if (!r.success) {
            return r;
          }
          return refreshCartInternal().then(function (r2) {
            if (r2 && r2.data && typeof r2.data === 'object') {
              toastIfQtyCappedAfterSuccess(requestedQty, r2.data, { code: body.code });
            }
            return r2;
          });
        })
        .finally(function () {
          setPanelBusy(false);
        });
    });
  };

  /** @param {Record<string, unknown>} [payload] */
  window.handleUpdateQty = function (payload) {
    return withCartLock(function () {
      var p = payload || {};
      var ref = String(p.cartref || '').trim();
      var qty = parseInt(String(p.qty), 10);
      if (!ref || !qty || qty < 1) {
        toast('Invalid cart update', 'red');
        return undefined;
      }
      setPanelBusy(true);
      var sentQty = qty;
      return cartRequest('modifyqty', { query: { cartid: ref, newqty: String(qty) } })
        .then(function (r) {
          cartHandleApiMessages(r);
          if (!r.success) {
            return r;
          }
          return refreshCartInternal().then(function (r2) {
            if (r2 && r2.data && typeof r2.data === 'object') {
              toastIfQtyCappedAfterSuccess(sentQty, r2.data, { cartref: ref });
            }
            return r2;
          });
        })
        .finally(function () {
          setPanelBusy(false);
        });
    });
  };

  /** @param {Record<string, unknown>} [payload] */
  window.handleDeleteItem = function (payload) {
    return withCartLock(function () {
      var ref = String((payload || {}).cartref || '').trim();
      if (!ref) {
        toast('Invalid delete', 'red');
        return undefined;
      }
      setPanelBusy(true);
      return cartRequest('delete', { query: { cartid: ref } })
        .then(function (r) {
          cartHandleApiMessages(r);
          if (!r.success) {
            return r;
          }
          return refreshCartInternal();
        })
        .finally(function () {
          setPanelBusy(false);
        });
    });
  };

  window.refreshCart = function () {
    return withCartLock(function () {
      setPanelBusy(true);
      return refreshCartInternal().finally(function () {
        setPanelBusy(false);
      });
    });
  };

  /**
   * @param {string} couponId
   * @returns {void|Promise<void>}
   */
  window.applyCoupon = function (couponId) {
    return withCartLock(function () {
      setPanelBusy(true);
      var id = String(couponId || '').trim();
      var chain = id
        ? cartRequest('addcoupon', { query: { couponid: id } })
        : cartRequest('removecoupon', {});
      return chain
        .then(function (r) {
          if (id) {
            cartHandleApiMessages(r);
            if (!r.success) {
              return r;
            }
          }
          return refreshCartInternal();
        })
        .finally(function () {
          setPanelBusy(false);
        });
    });
  };

  /**
   * @param {number} amount
   * @returns {void|Promise<void>}
   */
  window.applyCustomDiscount = function (amount) {
    return withCartLock(function () {
      setPanelBusy(true);
      var a = parseFloat(String(amount));
      if (isNaN(a) || a < 0) {
        a = 0;
      }
      return cartRequest('customdiscount', { query: { custom_reduce: String(a) } })
        .then(function (r) {
          cartHandleApiMessages(r);
          if (!r.success) {
            return r;
          }
          return refreshCartInternal();
        })
        .finally(function () {
          setPanelBusy(false);
        });
    });
  };

  function initPosCartHooks() {
    bindCartDelegatesOnce();
    ensureCartPanel();
    window.refreshCart();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPosCartHooks);
  } else {
    initPosCartHooks();
  }
})(window);
