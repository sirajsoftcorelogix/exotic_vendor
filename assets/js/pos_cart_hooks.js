/**
 * Exotic India cart (server-backed via same-origin cart-api proxy → https://www.exoticindia.com/api/cart/*).
 * Totals and lines are rendered only from the latest /cart/retrieve response — no local cart model.
 */
(function (window) {
  'use strict';

  var cartActionLock = false;
  var cartDelegatesBound = false;
  /** Last coupon id applied in this session (fallback label when retrieve omits name fields). */
  var lastAppliedCouponDisplay = '';
  var PANEL_ID = 'posExoticCartPanel';
  var MODAL_ID = 'posCartApiDebugModal';
  /** @type {Record<string, unknown>|null} */
  var lastCartApiDebug = null;
  /** After a successful apply: fixed = INR entered; percent = 0–100, re-synced to API after each retrieve. */
  var posCustomDiscountPersist = null;

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
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
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

  /** Cart line thumbnail URL (Exotic: full https, path from site root, or CDN-relative). */
  function lineImageUrl(row) {
    var raw = pickFirst(row, [
      'imageurl',
      'image_url',
      'image',
      'thumb',
      'thumbnail',
      'img',
      'small_image',
      'product_image'
    ]);
    if (raw == null || String(raw).trim() === '') {
      return '';
    }
    var s = String(raw).trim();
    if (/^\/\//.test(s)) {
      return 'https:' + s;
    }
    if (/^https?:\/\//i.test(s)) {
      return s;
    }
    if (s.charAt(0) === '/') {
      return 'https://www.exoticindia.com' + s;
    }
    return 'https://cdn.exoticindia.com/' + s.replace(/^\/+/, '');
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

  /** Raw GST amount fields on the line (may mirror `gst` slab when both are the same small number). */
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

  /** GST % / slab on line (Exotic uses `gst` for rate, not rupees). */
  function lineGstRatePercent(row) {
    return pickNumber(row, ['gst_rate', 'gst_percent', 'gstrate', 'gst']);
  }

  /**
   * GST in rupees from GST-inclusive line total: tax = T - T/(1+p/100).
   * Used when `gstamount` equals `gst` (both encode the rate) or when no separate rupee amount exists.
   */
  function gstRupeesFromInclusiveLine(row, qty, percent) {
    if (percent == null || percent <= 0 || percent > 40) {
      return null;
    }
    var inclusive = parseMoneyValue(lineLineTotalStr(row, qty));
    if (inclusive == null || inclusive <= 0) {
      return null;
    }
    var tax = inclusive - inclusive / (1 + percent / 100);
    if (isNaN(tax) || tax <= 0) {
      return null;
    }
    return Math.round(tax * 100) / 100;
  }

  function lineResolvedGstRupees(row, qty) {
    var apiAmt = lineGstAmountRupee(row);
    var rate = lineGstRatePercent(row);
    if (rate != null && rate > 0 && rate <= 40 && apiAmt != null && Math.abs(apiAmt - rate) < 1e-6) {
      var extracted = gstRupeesFromInclusiveLine(row, qty, rate);
      if (extracted != null) {
        return extracted;
      }
    }
    if (apiAmt != null && (rate == null || Math.abs(apiAmt - rate) >= 1e-6)) {
      return apiAmt;
    }
    if (apiAmt == null && rate != null && rate > 0 && rate <= 40) {
      return gstRupeesFromInclusiveLine(row, qty, rate);
    }
    return null;
  }

  function sumGstFromCartLineItems(cartData) {
    var items = getCartItems(cartData || {});
    if (!items.length) {
      return null;
    }
    var sum = 0;
    var ok = false;
    for (var i = 0; i < items.length; i++) {
      var qty = lineQty(items[i]);
      var g = lineResolvedGstRupees(items[i], qty);
      if (g != null) {
        sum += g;
        ok = true;
      }
    }
    return ok ? Math.round(sum * 100) / 100 : null;
  }

  /** Human-readable coupon label for summary row (Exotic field names vary). */
  function pickCouponDisplayName(data) {
    var d = data && typeof data === 'object' ? data : {};
    var cd = typeof d.checkoutdata === 'object' && d.checkoutdata !== null ? d.checkoutdata : {};
    function asString(v) {
      if (v == null || v === '') {
        return '';
      }
      if (typeof v === 'object') {
        var inner =
          pickFirst(v, ['name', 'title', 'coupon_name', 'couponname', 'code', 'coupon_code', 'couponcode', 'id']) ||
          '';
        return inner !== '' && inner != null ? String(inner).trim() : '';
      }
      return String(v).trim();
    }
    var v =
      pickFirst(d, [
        'coupon_name',
        'couponname',
        'applied_coupon_name',
        'discount_coupon_name',
        'coupondisplayname',
        'coupon_title',
        'coupon_display_name'
      ]) ||
      pickFirst(cd, [
        'coupon_name',
        'couponname',
        'discount_coupon_name',
        'applied_coupon_name',
        'coupon_display_name'
      ]);
    var s = asString(v);
    if (s !== '') {
      return s;
    }
    v =
      pickFirst(d, [
        'coupon_code',
        'couponcode',
        'discount_coupon_code',
        'applied_coupon',
        'couponid',
        'coupon_id'
      ]) || pickFirst(cd, ['coupon_code', 'couponcode', 'couponid', 'coupon_id']);
    return asString(v);
  }

  function totalsFromRetrieve(data) {
    var d = data && typeof data === 'object' ? data : {};
    var cd = typeof d.checkoutdata === 'object' && d.checkoutdata !== null ? d.checkoutdata : {};

    /**
     * Exotic cart merchandise sub total is GST-inclusive (line prices include tax).
     * Do not use pretax / pre-tax style keys here — they would read as exclusive and invite sub + GST mistakes.
     */
    var sub =
      pickNumber(d, [
        'items_total',
        'items_subtotal',
        'cart_subtotal',
        'subtotal',
        'sub_total',
        'merchandise_total'
      ]) || pickNumber(cd, ['items_total', 'items_subtotal', 'subtotal', 'sub_total']);
    if (sub == null) {
      sub = sumLineTotalsFromCartItems(d);
    }

    /** Tax component included in line/sub totals — informational only; never add again to sub for grand. */
    var gstTotal =
      pickNumber(d, [
        'gst_total',
        'total_gst',
        'total_gst_amount',
        'totalgst',
        'gst_tax_total',
        'tax_total',
        'total_tax',
        'gst_amount'
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

    var couponDisplayName = pickCouponDisplayName(data);
    if (
      (!couponDisplayName || String(couponDisplayName).trim() === '') &&
      isAmountGreaterThanZero(couponDeduction) &&
      lastAppliedCouponDisplay
    ) {
      couponDisplayName = lastAppliedCouponDisplay;
    }

    var customDeduction =
      pickNumber(d, [
        'customreduction',
        'custom_reduction',
        'custom_discount',
        'customdiscount',
        'custom_reduce'
      ]) || pickNumber(cd, ['custom_discount', 'customdiscount']);

    /**
     * Grand total for this panel: GST-inclusive merchandise (sub) minus coupon/custom only.
     * We do not use the API "grand/total" here — Exotic often includes shipping or a sub+GST-style figure while sub is already tax-inclusive.
     */
    var grandTotal = null;
    if (sub != null && !isNaN(sub)) {
      grandTotal = sub;
      if (couponDeduction != null && !isNaN(couponDeduction)) {
        grandTotal -= couponDeduction;
      }
      if (customDeduction != null && !isNaN(customDeduction)) {
        grandTotal -= customDeduction;
      }
      grandTotal = Math.round(grandTotal * 100) / 100;
    } else {
      grandTotal =
        pickNumber(d, [
          'totalamount',
          'grandtotal',
          'grand_total',
          'amount_payable',
          'payable_total',
          'order_total',
          'final_total',
          'finaltotal',
          'total_amount',
          'total'
        ]) || pickNumber(cd, ['grandtotal', 'grand_total', 'totalamount', 'amount_payable', 'total']);
    }

    return {
      subtotal: sub,
      gstTotal: gstTotal,
      couponDeduction: couponDeduction,
      couponDisplayName: couponDisplayName,
      customDeduction: customDeduction,
      grandTotal: grandTotal
    };
  }

  /**
   * @param {'fixed'|'percent'} mode
   * @param {number} raw INR amount (fixed) or 0–100 (percent of merchandise sub total)
   * @param {{ subtotal?: number|null, couponDeduction?: number|null }} t
   * @returns {number} Rupees to send as custom_reduce (capped so sub − coupon − discount stays ≥ 0)
   */
  function computeCustomReduceInr(mode, raw, t) {
    t = t || {};
    var sub = t.subtotal;
    if (sub == null || isNaN(sub) || sub <= 0) {
      return 0;
    }
    var coupon = t.couponDeduction != null && !isNaN(t.couponDeduction) ? t.couponDeduction : 0;
    var maxRoom = Math.max(0, sub - coupon);
    var amt = 0;
    if (mode === 'percent') {
      if (!(raw > 0) || raw > 100) {
        return 0;
      }
      amt = (sub * raw) / 100;
    } else {
      if (!(raw > 0)) {
        return 0;
      }
      amt = raw;
    }
    if (amt > maxRoom) {
      amt = maxRoom;
    }
    return Math.round(amt * 100) / 100;
  }

  function formatPctLabel(raw) {
    var n = typeof raw === 'number' ? raw : parseFloat(String(raw));
    if (isNaN(n)) {
      return '';
    }
    var s = n.toFixed(2).replace(/\.?0+$/, '');
    return s + '%';
  }

  /**
   * Keeps server custom_reduce aligned when cart or coupon changes (percent mode only).
   * @param {Record<string, unknown>} cartData
   * @returns {Promise<boolean>} true if a follow-up retrieve is needed
   */
  function maybeSyncPercentCustomDiscount(cartData) {
    if (!posCustomDiscountPersist || posCustomDiscountPersist.mode !== 'percent') {
      return Promise.resolve(false);
    }
    var pval = posCustomDiscountPersist.value;
    if (!(pval > 0) || pval > 100) {
      return Promise.resolve(false);
    }
    var t = totalsFromRetrieve(cartData && typeof cartData === 'object' ? cartData : {});
    var want = computeCustomReduceInr('percent', pval, t);
    var cur = t.customDeduction != null && !isNaN(t.customDeduction) ? t.customDeduction : 0;
    if (Math.abs(want - cur) < 0.02) {
      return Promise.resolve(false);
    }
    return cartRequest('customdiscount', { query: { custom_reduce: String(want) } }).then(function (r2) {
      cartHandleApiMessages(r2);
      return !!(r2 && r2.success);
    });
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

  /**
   * One summary row; uses em dash when API did not supply a number.
   * @param {string} [removeBtnClass] Optional button class for a trailing trash/remove control (non-grand rows only).
   */
  function moneyRowSummary(label, val, isGrand, removeBtnClass) {
    var disp = formatMoneyDisplay(val);
    var text = disp == null ? '—' : disp;
    var hasRemove = !isGrand && removeBtnClass && String(removeBtnClass).trim() !== '';
    var rowClass = isGrand
      ? 'flex justify-between items-baseline gap-3 text-base font-bold text-slate-900 pt-3 mt-2 border-t border-slate-200/90'
      : 'flex justify-between items-center gap-2 text-xs text-slate-600 py-1.5';
    var labelSpan =
      '<span class="' +
      (isGrand ? 'text-slate-700' : 'text-slate-500 font-medium min-w-0 pr-1') +
      '">' +
      escapeHtml(label) +
      '</span>';
    var amountSpan =
      '<span class="tabular-nums ' +
      (isGrand ? 'text-orange-700' : disp == null ? 'text-slate-400' : 'text-slate-800 font-semibold') +
      '">' +
      escapeHtml(text) +
      '</span>';
    if (!hasRemove) {
      return '<div class="' + rowClass + '">' + labelSpan + amountSpan + '</div>';
    }
    return (
      '<div class="' +
      rowClass +
      '">' +
      labelSpan +
      '<span class="flex items-center gap-2 shrink-0">' +
      amountSpan +
      '<button type="button" class="' +
      String(removeBtnClass) +
      ' inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-red-200 hover:bg-red-50 hover:text-red-700" title="Remove" aria-label="Remove">' +
      '<i class="fas fa-trash-alt text-[11px]" aria-hidden="true"></i></button>' +
      '</span></div>'
    );
  }

  function ensureCartPanel() {
    var el = document.getElementById(PANEL_ID);
    if (el) {
      return el;
    }
    var aside =
      document.querySelector('aside[data-pos-cart-sidebar]') ||
      document.querySelector('.pos-register-page aside') ||
      document.querySelector('aside.col-span-12');
    if (!aside) {
      return null;
    }
    var sticky = aside.querySelector('.rounded-2xl.bg-white.border.shadow-sm') || aside.querySelector('.sticky');
    if (!sticky) {
      return null;
    }
    var shell =
      sticky.querySelector('[data-pos-cart-scroll]') || sticky.querySelector('.pos-cart-shell-scroll');
    var mountParent = shell ? shell.querySelector('.pos-cart-panel-inner') || shell : sticky;
    var ph = mountParent.querySelector('.space-y-3.text-sm.text-slate-600');
    if (!ph) {
      ph = sticky.querySelector('.px-4.py-6.space-y-3');
    }
    if (ph) {
      ph.style.display = 'none';
    }
    el = document.createElement('div');
    el.id = PANEL_ID;
    el.className =
      'pos-exotic-cart-panel rounded-2xl border border-slate-200/90 bg-gradient-to-b from-slate-50 to-white shadow-sm px-3 py-4 text-sm text-slate-800 mx-0.5 mb-2';
    mountParent.appendChild(el);
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
    panel.className =
      'pos-exotic-cart-panel rounded-2xl border border-slate-200/90 bg-gradient-to-b from-slate-50 to-white shadow-sm px-3 py-4 text-sm text-slate-800 mx-0.5 mb-2';
    var items = getCartItems(data);
    var totals = totalsFromRetrieve(data || {});
    var html = '';

    if (!items.length) {
      html +=
        '<div class="flex flex-col items-center justify-center py-8 px-2 text-center rounded-xl border border-dashed border-slate-200 bg-slate-50/60">' +
        '<span class="text-2xl mb-1 opacity-40" aria-hidden="true">🛒</span>' +
        '<p class="text-sm font-medium text-slate-600">Your cart is empty</p>' +
        '<p class="text-[11px] text-slate-400 mt-1 max-w-[14rem]">Add products from the grid to see them here.</p>' +
        '</div>';
    } else {
      html +=
        '<div class="flex flex-col gap-2.5 pr-0.5">' +
        '<p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 px-0.5">In cart</p>';
      items.forEach(function (row) {
        var ref = lineCartRef(row);
        var qty = lineQty(row);
        var maxSell = lineMaxSellableQty(row, data || {});
        var title = lineTitle(row);
        var sub = lineSubDisplay(row);
        var unitPrice = lineUnitPriceStr(row);
        var lineTotal = lineLineTotalStr(row, qty);
        var imgUrl = lineImageUrl(row);
        var productCode = String(pickFirst(row, ['code', 'item_code', 'sku']) || '').trim();
        html +=
          '<div class="pos-cart-line-item group flex gap-2.5 rounded-xl border border-slate-100 bg-white p-2 shadow-sm cursor-pointer transition-all hover:border-slate-200 hover:shadow-md active:scale-[0.99]" data-cart-row="1"' +
          (productCode ? ' data-product-code="' + escapeHtml(productCode) + '"' : '') +
          ' role="button" tabindex="0" title="View product details">';
        if (imgUrl) {
          html +=
            '<div class="shrink-0 w-16 h-16 self-start rounded-lg border border-slate-100 bg-gradient-to-br from-white to-slate-50 overflow-hidden ring-1 ring-slate-100 group-hover:ring-orange-100">' +
            '<img src="' +
            escapeHtml(imgUrl) +
            '" alt="' +
            escapeHtml(title) +
            '" class="h-full w-full object-contain p-0.5" loading="lazy" decoding="async" />' +
            '</div>';
        } else {
          html +=
            '<div class="shrink-0 w-16 h-16 self-start rounded-lg border border-dashed border-slate-200 bg-slate-50 flex items-center justify-center text-slate-300 text-base leading-none" title="No image">◇</div>';
        }
        html += '<div class="min-w-0 flex-1 flex flex-col gap-1">';
        html +=
          '<div class="flex items-start justify-between gap-2">' +
          '<div class="min-w-0 flex-1">' +
          '<div class="text-[13px] font-semibold leading-tight text-slate-900 line-clamp-2">' +
          escapeHtml(title) +
          '</div>';
        if (sub) {
          html +=
            '<div class="mt-0.5 text-[10px] font-medium uppercase tracking-wide text-slate-400">' +
            escapeHtml(sub) +
            '</div>';
        }
        html += '</div>';
        html += '<div class="shrink-0 text-right leading-tight">';
        if (unitPrice && lineTotal) {
          html +=
            '<div class="text-[10px] text-slate-500 tabular-nums">' +
            escapeHtml(String(qty)) +
            ' \u00d7 ' +
            escapeHtml(unitPrice) +
            '</div>' +
            '<div class="text-sm font-bold tabular-nums text-orange-700">' +
            escapeHtml(lineTotal) +
            '</div>';
        } else if (lineTotal) {
          html += '<div class="text-sm font-bold tabular-nums text-slate-900">' + escapeHtml(lineTotal) + '</div>';
        } else if (unitPrice) {
          html += '<div class="text-xs font-semibold tabular-nums text-slate-800">' + escapeHtml(unitPrice) + '</div>';
        }
        html += '</div></div>';
        html += '<div class="flex items-center justify-between gap-2 border-t border-slate-100 pt-1.5">';
        if (ref) {
          var maxAttr =
            maxSell != null && maxSell >= 1 ? ' max="' + escapeHtml(String(maxSell)) + '" data-max-qty="' + escapeHtml(String(maxSell)) + '"' : '';
          var hintInline =
            maxSell != null && maxSell >= 1
              ? '<span class="text-[10px] text-slate-400 tabular-nums">\u00b7 max ' +
                escapeHtml(String(maxSell)) +
                '/order</span>'
              : '';
          html +=
            '<div class="flex min-w-0 flex-wrap items-center gap-x-1.5 gap-y-0.5">' +
            '<span class="text-[10px] font-medium text-slate-500">Qty</span>' +
            '<input type="number" min="1" step="1" class="pos-cart-qty-input w-11 rounded-md border border-slate-200 bg-white px-1 py-0.5 text-center text-xs font-semibold text-slate-800 shadow-sm outline-none transition focus:border-orange-400 focus:ring-1 focus:ring-orange-100"' +
            maxAttr +
            ' data-cartref="' +
            escapeHtml(ref) +
            '" value="' +
            escapeHtml(String(qty)) +
            '" title="' +
            (maxSell != null && maxSell >= 1 ? escapeHtml('Maximum ' + maxSell + ' per order') : '') +
            '" />' +
            hintInline +
            '</div>' +
            '<button type="button" class="pos-cart-delete-btn inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-red-100 bg-red-50/80 text-red-700 transition hover:bg-red-100 hover:border-red-200" data-cartref="' +
            escapeHtml(ref) +
            '" title="Remove from cart" aria-label="Remove from cart">' +
            '<i class="fas fa-trash-alt text-[12px]" aria-hidden="true"></i></button>';
        } else {
          html += '<span class="text-[10px] text-amber-700">Missing cart reference — cannot update line.</span>';
        }
        html += '</div></div></div>';
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
      html +=
        '<div class="mt-4 rounded-xl border border-slate-100 bg-slate-50/90 p-3 shadow-inner">' +
        '<p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-2 px-0.5">Summary</p>' +
        '<div class="rounded-lg bg-white/90 px-2 py-1 ring-1 ring-slate-100/80">';
      html += moneyRowSummary('Sub total (incl. GST)', totals.subtotal, false);
      html += moneyRowSummary('GST', totals.gstTotal, false);
      if (isAmountGreaterThanZero(totals.couponDeduction)) {
        var couponLbl =
          totals.couponDisplayName && String(totals.couponDisplayName).trim() !== ''
            ? 'Coupon (' + String(totals.couponDisplayName).trim() + ')'
            : 'Coupon';
        html += moneyRowSummary(couponLbl, totals.couponDeduction, false, 'pos-cart-summary-remove-coupon');
      }
      if (isAmountGreaterThanZero(totals.customDeduction)) {
        var cdLbl = 'Custom discount';
        if (posCustomDiscountPersist && posCustomDiscountPersist.mode === 'percent') {
          cdLbl += ' (' + formatPctLabel(posCustomDiscountPersist.value) + ')';
        } else if (posCustomDiscountPersist && posCustomDiscountPersist.mode === 'fixed') {
          cdLbl += ' (fixed ₹)';
        }
        html += moneyRowSummary(cdLbl, totals.customDeduction, false, 'pos-cart-summary-remove-custom');
      }
      html += moneyRowSummary('Grand total', totals.grandTotal, true);
      html += '</div></div>';
    }

    html +=
      '<div class="mt-4 rounded-xl border border-slate-100 bg-white p-3 shadow-sm space-y-3">' +
      '<p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Discounts</p>' +
      '<div class="space-y-1.5">' +
      '<label class="text-xs font-medium text-slate-600">Coupon</label>' +
      '<div class="flex gap-2 flex-wrap">' +
      '<input type="text" class="pos-cart-coupon-input flex-1 min-w-[6rem] rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs shadow-sm outline-none transition placeholder:text-slate-400 focus:border-orange-300 focus:bg-white focus:ring-2 focus:ring-orange-100" placeholder="Coupon code" />' +
      '<button type="button" class="pos-cart-coupon-apply shrink-0 rounded-lg bg-orange-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-orange-700 active:scale-[0.98]">Apply</button>' +
      '<button type="button" class="pos-cart-coupon-clear shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">Clear</button>' +
      '</div></div>' +
      '<div class="space-y-1.5 border-t border-slate-100 pt-3">' +
      '<label class="text-xs font-medium text-slate-600">Custom discount</label>' +
      '<div class="flex gap-2 flex-wrap items-stretch">' +
      '<select class="pos-cart-customdisc-mode shrink-0 rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs font-medium text-slate-700 shadow-sm outline-none focus:border-orange-300 focus:ring-2 focus:ring-orange-100" aria-label="Discount type">' +
      '<option value="fixed">Fixed (₹)</option>' +
      '<option value="percent">Percent (%)</option>' +
      '</select>' +
      '<input type="number" step="0.01" min="0" class="pos-cart-customdisc-input flex-1 min-w-[5rem] rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs shadow-sm outline-none transition placeholder:text-slate-400 focus:border-orange-300 focus:bg-white focus:ring-2 focus:ring-orange-100" placeholder="Amount (₹)" />' +
      '<button type="button" class="pos-cart-customdisc-apply shrink-0 rounded-lg bg-orange-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-orange-700 active:scale-[0.98]">Set</button>' +
      '<button type="button" class="pos-cart-customdisc-clear inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-red-50 hover:border-red-200 hover:text-red-700" title="Remove custom discount" aria-label="Remove custom discount">' +
      '<i class="fas fa-trash-alt text-sm" aria-hidden="true"></i></button>' +
      '</div></div></div>';

    if (items.length > 0) {
      html +=
        '<div class="mt-3 flex justify-center">' +
        '<button type="button" class="pos-cart-checkout-btn rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">Proceed to payment</button>' +
        '</div>';
    }

    html +=
      '<div class="mt-3 flex justify-center">' +
      '<button type="button" class="pos-cart-api-debug-link rounded-full border border-slate-200/90 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700" ' +
      'onclick="event.preventDefault();event.stopPropagation();if(typeof window.openPosCartApiDebugModal===\'function\'){window.openPosCartApiDebugModal();}">' +
      'Last API request / response' +
      '</button>' +
      '</div>';

    window.__posCartLastTotals = {
      grandTotal: totals.grandTotal,
      subtotal: totals.subtotal,
      gstTotal: totals.gstTotal,
      couponDeduction: totals.couponDeduction,
      customDeduction: totals.customDeduction
    };

    panel.innerHTML = html;
    var modeSel = panel.querySelector('.pos-cart-customdisc-mode');
    var inpDisc = panel.querySelector('.pos-cart-customdisc-input');
    if (modeSel && inpDisc && posCustomDiscountPersist) {
      modeSel.value = posCustomDiscountPersist.mode === 'percent' ? 'percent' : 'fixed';
      inpDisc.value = String(posCustomDiscountPersist.value);
      if (modeSel.value === 'percent') {
        inpDisc.setAttribute('max', '100');
        inpDisc.setAttribute('step', '0.01');
        inpDisc.placeholder = 'e.g. 10';
      } else {
        inpDisc.removeAttribute('max');
        inpDisc.setAttribute('step', '0.01');
        inpDisc.placeholder = 'Amount (₹)';
      }
    }
  }

  function bindCartDelegatesOnce() {
    if (cartDelegatesBound) {
      return;
    }
    cartDelegatesBound = true;

    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') {
        var modal = document.getElementById(MODAL_ID);
        if (modal && !modal.classList.contains('hidden')) {
          closePosCartApiDebugModal();
        }
        return;
      }
      if (ev.key !== 'Enter' && ev.key !== ' ') {
        return;
      }
      var t = ev.target;
      if (!t || !t.closest) {
        return;
      }
      if (t.matches && t.matches('input, button, textarea, select')) {
        return;
      }
      var lineItem = t.closest('.pos-cart-line-item');
      if (!lineItem) {
        return;
      }
      var panelK = document.getElementById(PANEL_ID);
      if (!panelK || !panelK.contains(lineItem)) {
        return;
      }
      var pk = String(lineItem.getAttribute('data-product-code') || '').trim();
      if (pk && typeof window.openProductModalByCode === 'function') {
        ev.preventDefault();
        window.openProductModalByCode(pk, []);
      }
    });

    document.body.addEventListener(
      'change',
      function (e) {
        var t = e.target;
        if (!t || !t.matches) {
          return;
        }
        if (t.matches('.pos-cart-customdisc-mode')) {
          var panelM = document.getElementById(PANEL_ID);
          if (!panelM || !panelM.contains(t)) {
            return;
          }
          var inpM = panelM.querySelector('.pos-cart-customdisc-input');
          if (inpM) {
            if (t.value === 'percent') {
              inpM.setAttribute('max', '100');
              inpM.setAttribute('step', '0.01');
              inpM.placeholder = 'e.g. 10';
            } else {
              inpM.removeAttribute('max');
              inpM.setAttribute('step', '0.01');
              inpM.placeholder = 'Amount (₹)';
            }
          }
          return;
        }
        if (!t.matches('.pos-cart-qty-input')) {
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
        var chkPay = e.target && e.target.closest ? e.target.closest('.pos-cart-checkout-btn') : null;
        if (chkPay && panel.contains(chkPay)) {
          if (typeof window.openPaymentModal === 'function') {
            window.openPaymentModal();
          }
          return;
        }
        var sumRmC = e.target && e.target.closest ? e.target.closest('.pos-cart-summary-remove-coupon') : null;
        if (sumRmC && panel.contains(sumRmC)) {
          window.applyCoupon('');
          return;
        }
        var sumRmD = e.target && e.target.closest ? e.target.closest('.pos-cart-summary-remove-custom') : null;
        if (sumRmD && panel.contains(sumRmD)) {
          window.applyCustomDiscount(0);
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
        var lineItem = e.target && e.target.closest ? e.target.closest('.pos-cart-line-item') : null;
        if (lineItem && panel.contains(lineItem)) {
          if (e.target.closest('button, input, a, label, textarea, select')) {
            return;
          }
          var pcode = String(lineItem.getAttribute('data-product-code') || '').trim();
          if (pcode && typeof window.openProductModalByCode === 'function') {
            e.preventDefault();
            window.openProductModalByCode(pcode, []);
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
          var ms = panel.querySelector('.pos-cart-customdisc-mode');
          var num = di ? parseFloat(String(di.value)) : NaN;
          var mDisc = ms && ms.value === 'percent' ? 'percent' : 'fixed';
          if (isNaN(num) || num < 0) {
            toast('Enter a valid discount.', 'red');
            return;
          }
          if (mDisc === 'percent' && num > 100) {
            toast('Percentage must be between 0 and 100.', 'red');
            return;
          }
          window.applyCustomDiscount(num, { mode: mDisc });
          return;
        }
        var cdc = e.target && e.target.closest ? e.target.closest('.pos-cart-customdisc-clear') : null;
        if (cdc && panel.contains(cdc)) {
          window.applyCustomDiscount(0);
          return;
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

  function refreshCartInternal(opts) {
    opts = opts || {};
    var skipPctSync = !!opts.skipPctSync;
    return cartRequest('retrieve', {}).then(function (r) {
      cartHandleApiMessages(r);
      var data = {};
      if (r && r.success && r.data && typeof r.data === 'object') {
        data = r.data;
        renderCartUI(data);
      } else if (r && r.data && typeof r.data === 'object' && Object.keys(r.data).length) {
        data = r.data;
        renderCartUI(data);
      } else {
        renderCartUI({});
      }
      if (skipPctSync) {
        return r;
      }
      return maybeSyncPercentCustomDiscount(data).then(function (didSync) {
        if (!didSync) {
          return r;
        }
        return refreshCartInternal({ skipPctSync: true });
      });
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
          toast('Added to cart.', 'green');
          if (typeof window.closePosProductModal === 'function') {
            window.closePosProductModal();
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
            lastAppliedCouponDisplay = id;
            toast('Coupon applied.', 'green');
          } else {
            lastAppliedCouponDisplay = '';
          }
          return refreshCartInternal();
        })
        .finally(function () {
          setPanelBusy(false);
        });
    });
  };

  /**
   * @param {number} amount Fixed INR, or percent 0–100 when opt.mode === 'percent'
   * @param {{ mode?: 'fixed'|'percent' }} [opt]
   * @returns {void|Promise<void>}
   */
  window.applyCustomDiscount = function (amount, opt) {
    return withCartLock(function () {
      opt = opt || {};
      var mode = opt.mode === 'percent' ? 'percent' : 'fixed';
      var a = parseFloat(String(amount));
      if (isNaN(a) || a < 0) {
        a = 0;
      }
      setPanelBusy(true);
      if (a === 0) {
        posCustomDiscountPersist = null;
        return cartRequest('customdiscount', { query: { custom_reduce: '0' } })
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
      }
      if (mode === 'percent' && a > 100) {
        setPanelBusy(false);
        toast('Percentage must be between 0 and 100.', 'red');
        return undefined;
      }
      var t0 = window.__posCartLastTotals || {};
      var tModel = {
        subtotal: t0.subtotal,
        couponDeduction: t0.couponDeduction
      };
      var send = computeCustomReduceInr(mode, a, tModel);
      if (!(send > 0)) {
        setPanelBusy(false);
        if (mode === 'percent') {
          toast('Cannot apply percent — sub total is missing or zero. Add items to the cart first.', 'red');
        } else {
          toast('Discount must be greater than zero.', 'red');
        }
        return undefined;
      }
      if (mode === 'fixed' && a > send + 0.01) {
        toast('Discount capped at ₹' + send.toFixed(2) + ' (sub total minus coupon).', 'green');
      } else if (mode === 'percent') {
        toast('Applied ' + formatPctLabel(a) + ' (₹' + send.toFixed(2) + ').', 'green');
      }
      return cartRequest('customdiscount', { query: { custom_reduce: String(send) } })
        .then(function (r) {
          cartHandleApiMessages(r);
          if (!r.success) {
            return r;
          }
          posCustomDiscountPersist = { mode: mode, value: a };
          return refreshCartInternal();
        })
        .finally(function () {
          setPanelBusy(false);
        });
    });
  };

  window.getPosCartTotalsForCheckout = function () {
    return window.__posCartLastTotals || null;
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
