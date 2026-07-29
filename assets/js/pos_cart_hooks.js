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
  /** Last op=add snapshot (kept after retrieve overwrites lastCartApiDebug). */
  var lastCartAddApiDebug = null;
  /** After a successful apply: fixed = INR entered; percent = 0–100, re-synced to API after each retrieve. */
  var posCustomDiscountPersist = null;
  /** Stable keys (sku+qty+local stock) persisted in sessionStorage across page reload. */
  var POS_LOCAL_STOCK_CONFIRM_SS_KEY = 'pos_local_stock_confirmed_v1';

  function loadLocalStockConfirmedMap() {
    try {
      var raw = sessionStorage.getItem(POS_LOCAL_STOCK_CONFIRM_SS_KEY);
      if (!raw) {
        return {};
      }
      var o = JSON.parse(raw);
      return o && typeof o === 'object' ? o : {};
    } catch (eLoad) {
      return {};
    }
  }

  function persistLocalStockConfirmedMap() {
    try {
      sessionStorage.setItem(POS_LOCAL_STOCK_CONFIRM_SS_KEY, JSON.stringify(posLocalStockConfirmedByRef));
    } catch (eSave) {
      /* ignore quota / private mode */
    }
  }

  var posLocalStockConfirmedByRef = loadLocalStockConfirmedMap();
  /** Last successful cart retrieve root `data` (for instant re-render after line discount edits). */
  var lastRetrieveCartDataSnapshot = null;
  var CART_VIEW_SS_KEY = 'pos_cart_view_mode';
  var cartViewMode = 'card';
  var posCartDraftSuggestTimer = null;

  function isCartTablePage() {
    return !!(
      window.POS_CART_TABLE_PAGE ||
      document.getElementById('posCartTablePage') ||
      document.querySelector('[data-pos-cart-table-page]')
    );
  }

  function posCartTablePageUrl() {
    return '?page=pos_register&action=cart-table';
  }

  function posRegisterListUrl(extraHash) {
    var url = '?page=pos_register&action=list';
    if (extraHash) {
      url += extraHash.charAt(0) === '#' ? extraHash : '#' + extraHash;
    }
    return url;
  }

  if (isCartTablePage()) {
    cartViewMode = 'table';
  } else {
    try {
      var storedCartView = sessionStorage.getItem(CART_VIEW_SS_KEY);
      if (storedCartView === 'table') {
        sessionStorage.setItem(CART_VIEW_SS_KEY, 'card');
      }
    } catch (eClearTableView) {
      /* ignore */
    }
    cartViewMode = 'card';
  }

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
      '<span class="text-sm font-semibold text-slate-800">Cart API — proxy &amp; Exotic</span>' +
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

  /** @param {string} [url] */
  function parseQueryStringFromUrl(url) {
    var q = {};
    if (!url || url.indexOf('?') < 0) {
      return q;
    }
    var search = url.split('?')[1] || '';
    search.split('&').forEach(function (pair) {
      if (!pair) {
        return;
      }
      var idx = pair.indexOf('=');
      var k = decodeURIComponent(idx >= 0 ? pair.slice(0, idx) : pair);
      var v = idx >= 0 ? decodeURIComponent(pair.slice(idx + 1)) : '';
      q[k] = v;
    });
    return q;
  }

  /** @param {Record<string, unknown>|null} dbg */
  function resolveUpstreamFromDebug(dbg) {
    if (!dbg) {
      return null;
    }
    if (dbg.upstreamExoticIndia != null) {
      return dbg.upstreamExoticIndia;
    }
    var pr = dbg.parsedProxyResponse;
    if (pr && pr.upstream != null) {
      return pr.upstream;
    }
    return null;
  }

  /** @param {Record<string, unknown>|null} dbg */
  function buildProxyRequestJson(dbg) {
    if (!dbg) {
      return null;
    }
    var out = {
      method: dbg.method,
      url: dbg.requestUrl,
      headers: dbg.requestHeaders || {}
    };
    if (dbg.requestBody != null) {
      out.jsonBody = cloneSafeJson(dbg.requestBody);
    }
    if (dbg.requestQuery && typeof dbg.requestQuery === 'object') {
      out.query = cloneSafeJson(dbg.requestQuery);
    } else if (dbg.requestUrl) {
      var pq = parseQueryStringFromUrl(String(dbg.requestUrl));
      if (Object.keys(pq).length) {
        out.query = pq;
      }
    }
    return out;
  }

  /** @param {Record<string, unknown>|null} dbg */
  function buildExoticRequestJson(dbg) {
    var up = resolveUpstreamFromDebug(dbg);
    if (!up || typeof up !== 'object') {
      return null;
    }
    var exotic = {
      api_base: up.api_base,
      endpoint: up.endpoint,
      browser_request_json:
        up.browser_request_json != null ? cloneSafeJson(up.browser_request_json) : undefined,
      discount_query_merged_into_url:
        up.discount_query_merged_into_url != null
          ? cloneSafeJson(up.discount_query_merged_into_url)
          : undefined,
      extra_headers: up.extra_headers != null ? cloneSafeJson(up.extra_headers) : undefined,
      attempts: []
    };
    if (Array.isArray(up.attempts)) {
      up.attempts.forEach(function (a) {
        if (!a || typeof a !== 'object') {
          return;
        }
        exotic.attempts.push({
          label: a.label,
          request_url: a.request_url,
          post_body: a.post_body != null ? cloneSafeJson(a.post_body) : {}
        });
      });
    }
    return exotic;
  }

  /** @param {Record<string, unknown>|null} dbg */
  function buildCartApiDebugOpSnapshot(dbg) {
    if (!dbg) {
      return null;
    }
    var pr = dbg.parsedProxyResponse;
    return {
      time: dbg.at,
      op: dbg.op,
      proxy_request_json: buildProxyRequestJson(dbg),
      exotic_request_json: buildExoticRequestJson(dbg),
      fetchHttpStatus: dbg.fetchHttpStatus,
      response: pr
        ? {
            success: pr.success,
            http_code: pr.http_code,
            message: pr.message,
            parseError: pr.parseError,
            data: cloneSafeJson(pr.data),
            raw: pr.raw,
            upstream: pr.upstream != null ? cloneSafeJson(pr.upstream) : undefined
          }
        : { networkError: dbg.networkError },
      rawResponseText: dbg.rawProxyResponseText
    };
  }

  function openPosCartApiDebugModal() {
    if (!lastCartApiDebug && !lastCartAddApiDebug) {
      toast('No cart API call recorded yet.', 'red');
      return;
    }
    var m = ensureCartApiDebugModal();
    var pre = document.getElementById('posCartApiDebugPre');
    if (!pre) {
      return;
    }
    var dbg = lastCartApiDebug || lastCartAddApiDebug;
    var focusDbg = lastCartAddApiDebug || dbg;
    var out = {
      note:
        'Flow: browser → ?page=pos_register&action=cart-api → server forwards to https://www.exoticindia.com/api/cart/*. proxy_request_json = what this POS sent to the proxy. exotic_request_json = what the server sent to Exotic India (URL query + form post_body per attempt). last_add_to_cart is kept when retrieve runs after add.',
      proxy_request_json: buildProxyRequestJson(focusDbg),
      exotic_request_json: buildExoticRequestJson(focusDbg),
      last_add_to_cart: buildCartApiDebugOpSnapshot(lastCartAddApiDebug),
      last_cart_op: buildCartApiDebugOpSnapshot(dbg)
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

  /** @param {unknown} v @param {number} [depth] */
  function extractMessageFromUnknown(v, depth) {
    depth = depth || 0;
    if (depth > 12) {
      return '';
    }
    if (v == null) {
      return '';
    }
    if (typeof v === 'string') {
      var ts = v.trim();
      return ts || '';
    }
    if (typeof v === 'number' && !isNaN(v)) {
      return String(v);
    }
    if (typeof v !== 'object') {
      return '';
    }
    if (Array.isArray(v)) {
      var parts = [];
      for (var i = 0; i < v.length && parts.length < 5; i++) {
        var ps = extractMessageFromUnknown(v[i], depth + 1);
        if (ps) {
          parts.push(ps);
        }
      }
      return parts.join('; ');
    }
    var o = /** @type {Record<string, unknown>} */ (v);
    var msgKeys = [
      'message',
      'Message',
      'error',
      'Error',
      'errormessage',
      'msg',
      'reason',
      'detail',
      'description',
      'error_description',
      'title',
      'text',
      'errorMessage',
      'UserMessage',
      'userMessage',
      'statusMessage',
      'StatusMessage',
      'exceptionMessage'
    ];
    for (var ki = 0; ki < msgKeys.length; ki++) {
      var mk = msgKeys[ki];
      if (o[mk] == null || o[mk] === '') {
        continue;
      }
      var got = extractMessageFromUnknown(o[mk], depth + 1);
      if (got) {
        return got;
      }
    }
    var errKeys = ['errors', 'Errors', 'validation', 'ValidationErrors'];
    for (var ei = 0; ei < errKeys.length; ei++) {
      if (o[errKeys[ei]] == null) {
        continue;
      }
      var g2 = extractMessageFromUnknown(o[errKeys[ei]], depth + 1);
      if (g2) {
        return g2;
      }
    }
    var wraps = ['data', 'result', 'payload', 'response'];
    for (var wi = 0; wi < wraps.length; wi++) {
      var wk = wraps[wi];
      if (!o[wk] || typeof o[wk] !== 'object') {
        continue;
      }
      var g3 = extractMessageFromUnknown(o[wk], depth + 1);
      if (g3) {
        return g3;
      }
    }
    for (var sk in o) {
      if (!Object.prototype.hasOwnProperty.call(o, sk)) {
        continue;
      }
      var sub = o[sk];
      if (!sub || typeof sub !== 'object') {
        continue;
      }
      var g4 = extractMessageFromUnknown(sub, depth + 1);
      if (g4) {
        return g4;
      }
    }
    for (var sk2 in o) {
      if (!Object.prototype.hasOwnProperty.call(o, sk2)) {
        continue;
      }
      if (typeof o[sk2] !== 'string') {
        continue;
      }
      var t2 = String(o[sk2]).trim();
      if (t2) {
        return t2;
      }
    }
    return '';
  }

  /**
   * @param {{ message?: unknown, data?: unknown, raw?: string }} parsedLike
   */
  function extractCartApiUserMessage(parsedLike) {
    if (!parsedLike || typeof parsedLike !== 'object') {
      return '';
    }
    var pl = /** @type {Record<string, unknown>} */ (parsedLike);
    if (pl.message != null && pl.message !== '') {
      var fromTop = extractMessageFromUnknown(pl.message, 0);
      if (fromTop) {
        return fromTop;
      }
    }
    if (pl.data != null && typeof pl.data === 'object') {
      var fromData = extractMessageFromUnknown(pl.data, 0);
      if (fromData) {
        return fromData;
      }
    }
    if (pl.raw) {
      var raw = String(pl.raw);
      if (raw.indexOf('{') !== -1) {
        try {
          var inner = JSON.parse(raw);
          var fromRaw = extractMessageFromUnknown(inner, 0);
          if (fromRaw) {
            return fromRaw;
          }
        } catch (eRaw) {
          /* ignore */
        }
      }
    }
    return '';
  }

  function cartRequest(op, opt) {
    opt = opt || {};
    var method = (opt.method || 'GET').toUpperCase();
    var url = cartUrl(op, opt.query);
    var requestQuery =
      opt.query && typeof opt.query === 'object' ? cloneSafeJson(opt.query) : null;
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

    function rememberCartApiDebug(entry) {
      var dbg = Object.assign(
        {
          requestQuery: requestQuery
        },
        entry
      );
      setLastCartApiDebug(dbg);
      if (op === 'add') {
        try {
          lastCartAddApiDebug = JSON.parse(JSON.stringify(dbg));
        } catch (eAdd) {
          lastCartAddApiDebug = dbg;
        }
      }
    }
    return fetch(url, init).then(function (res) {
      return res.text().then(function (text) {
        var cleaned = text.replace(/^\uFEFF/, '').trim();
        var maxRaw = 200000;
        var rawPreview =
          cleaned.length > maxRaw ? cleaned.slice(0, maxRaw) + '\n…(truncated)' : cleaned;
        var r;
        try {
          var parsed = cleaned ? JSON.parse(cleaned) : {};
          var extractedMsg = extractCartApiUserMessage(parsed);
          r = {
            success: !!parsed.success,
            http_code: parsed.http_code != null ? parsed.http_code : res.status,
            data: parsed.data || {},
            raw: parsed.raw || '',
            message:
              (parsed.message && String(parsed.message).trim()) ||
              extractedMsg ||
              ''
          };
          if (parsed.upstream != null) {
            r.upstream = parsed.upstream;
          }
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
        rememberCartApiDebug({
          op: op,
          method: method,
          requestUrl: url,
          requestHeaders: reqHeaders,
          requestBody: opt.jsonBody != null ? opt.jsonBody : null,
          fetchHttpStatus: res.status,
          parsedProxyResponse: r,
          rawProxyResponseText: rawPreview,
          upstreamExoticIndia: r && r.upstream != null ? r.upstream : null
        });
        return r;
      });
    }).catch(function (err) {
      rememberCartApiDebug({
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
        (r.message && String(r.message).trim()) ||
        extractCartApiUserMessage({
          message: r.message,
          data: r.data,
          raw: r.raw
        });
      if (!msg) {
        msg = 'Request failed (HTTP ' + (r.http_code || '') + ')';
      }
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

  function lineLocalStockQty(row) {
    var n = pickNumber(row, ['local_stock', 'localStock', 'stock_qty', 'stockQty']);
    return n == null || isNaN(n) ? null : n;
  }

  function getLocalStockWarnings(cartData) {
    var items = getCartItems(cartData || {});
    var warnings = [];
    items.forEach(function (row) {
      var localStock = lineLocalStockQty(row);
      if (localStock == null) {
        return;
      }
      var qty = lineQty(row);
      if (qty <= localStock) {
        return;
      }
      var code = String(pickFirst(row, ['code', 'item_code', 'sku']) || '').trim();
      var title = lineTitle(row);
      warnings.push({
        code: code,
        title: title,
        quantity: qty,
        local_stock: localStock,
        shortage: Math.max(0, qty - localStock)
      });
    });
    return warnings;
  }

  function formatLocalStockQty(n) {
    if (n == null || isNaN(Number(n))) {
      return '—';
    }
    var x = Number(n);
    if (Math.abs(x - Math.round(x)) < 0.0001) {
      return String(Math.round(x));
    }
    return String(x);
  }

  function localStockProceedPrompt(localStock) {
    return 'Local Stock = ' + formatLocalStockQty(localStock) + ', Proceed?';
  }

  function localStockAckLabel(localStock) {
    return 'Local Stock = ' + formatLocalStockQty(localStock);
  }

  function localStockConfirmSignature(ref, qty, localStock) {
    return String(ref || '') + '|' + String(qty) + '|' + formatLocalStockQty(localStock);
  }

  /** @param {Record<string, unknown>|null} [row] */
  function localStockStableConfirmKey(row, ref, qty, localStock) {
    var code = '';
    if (row && typeof row === 'object') {
      code = String(pickFirst(row, ['code', 'item_code', 'sku']) || '').trim().toUpperCase();
    }
    if (!code && ref) {
      return 'ref:' + localStockConfirmSignature(ref, qty, localStock);
    }
    if (!code) {
      return '';
    }
    var variation = String(
      pickFirst(row, ['variation', 'variant', 'subcode', 'options']) || ''
    ).trim();
    return (
      'sku:' + code + '|' + variation + '|' + String(qty) + '|' + formatLocalStockQty(localStock)
    );
  }

  /** @param {Record<string, unknown>|null} [row] */
  function isLocalStockConfirmed(ref, qty, localStock, row) {
    var key = localStockStableConfirmKey(row, ref, qty, localStock);
    if (!key) {
      return false;
    }
    return posLocalStockConfirmedByRef[key] === '1';
  }

  /** @param {Record<string, unknown>|null} [row] */
  function setLocalStockConfirmed(ref, qty, localStock, row) {
    var key = localStockStableConfirmKey(row, ref, qty, localStock);
    if (!key) {
      return;
    }
    posLocalStockConfirmedByRef[key] = '1';
    persistLocalStockConfirmedMap();
  }

  /** @param {Record<string, unknown>|null|undefined} cartData @param {string} ref */
  function findCartRowByRef(cartData, ref) {
    if (!ref) {
      return null;
    }
    var items = getCartItems(cartData || {});
    for (var i = 0; i < items.length; i++) {
      if (lineCartRef(items[i]) === ref) {
        return items[i];
      }
    }
    return null;
  }

  /** Drop saved Y/N for a product so re-adding to cart asks again. */
  function clearLocalStockConfirmedForProduct(row, ref) {
    var prefixes = [];
    if (row && typeof row === 'object') {
      var code = String(pickFirst(row, ['code', 'item_code', 'sku']) || '').trim().toUpperCase();
      if (code) {
        prefixes.push('sku:' + code + '|');
      }
    }
    if (ref) {
      prefixes.push('ref:' + String(ref) + '|');
    }
    if (!prefixes.length) {
      return;
    }
    var changed = false;
    Object.keys(posLocalStockConfirmedByRef).forEach(function (k) {
      for (var pi = 0; pi < prefixes.length; pi++) {
        if (k.indexOf(prefixes[pi]) === 0) {
          delete posLocalStockConfirmedByRef[k];
          changed = true;
          break;
        }
      }
    });
    if (changed) {
      persistLocalStockConfirmedMap();
    }
  }

  function clearAllLocalStockConfirmed() {
    if (Object.keys(posLocalStockConfirmedByRef).length === 0) {
      return;
    }
    posLocalStockConfirmedByRef = {};
    persistLocalStockConfirmedMap();
  }

  /** @param {Record<string, unknown>|null|undefined} cartData */
  function pruneLocalStockConfirmed(refsUsed, cartData) {
    var keep = {};
    var items = getCartItems(cartData || {});
    items.forEach(function (row) {
      var localStock = lineLocalStockQty(row);
      if (localStock == null) {
        return;
      }
      var qty = lineQty(row);
      if (qty <= localStock) {
        return;
      }
      var ref = lineCartRef(row);
      var key = localStockStableConfirmKey(row, ref, qty, localStock);
      if (key && posLocalStockConfirmedByRef[key] === '1') {
        keep[key] = '1';
      }
    });
    posLocalStockConfirmedByRef = keep;
    persistLocalStockConfirmedMap();
  }

  function getUnconfirmedLocalStockLines(cartData) {
    var items = getCartItems(cartData || {});
    var out = [];
    items.forEach(function (row) {
      var localStock = lineLocalStockQty(row);
      if (localStock == null) {
        return;
      }
      var qty = lineQty(row);
      if (qty <= localStock) {
        return;
      }
      var ref = lineCartRef(row);
      if (!ref || isLocalStockConfirmed(ref, qty, localStock, row)) {
        return;
      }
      out.push({
        cartref: ref,
        code: String(pickFirst(row, ['code', 'item_code', 'sku']) || '').trim(),
        title: lineTitle(row),
        quantity: qty,
        local_stock: localStock
      });
    });
    return out;
  }

  function rerenderCartFromSnapshot() {
    var d = window.__posCartLastRetrieveData;
    if (d && typeof d === 'object') {
      renderCartUI(d);
    }
  }

  function formatLocalStockWarning(warnings) {
    if (!Array.isArray(warnings) || warnings.length === 0) {
      return '';
    }
    if (warnings.length === 1) {
      return localStockProceedPrompt(warnings[0].local_stock) + ' (Y / N in cart)';
    }
    return warnings.length + ' items need local stock confirmation in cart (Y / N)';
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

  /** Selected add-on extras per unit (frame, express, etc.) from Exotic cart line. */
  function lineAddonExtraRupee(row) {
    if (!row || typeof row !== 'object') {
      return 0;
    }
    var extra = 0;
    var frame = parseMoneyValue(pickFirst(row, ['framevalue', 'frame_value', 'frame_price']));
    if (frame != null && frame > 0) {
      extra += frame;
    } else {
      var addons = row.addons_selected;
      if (Array.isArray(addons)) {
        addons.forEach(function (addon) {
          if (!addon || typeof addon !== 'object') {
            return;
          }
          var v = parseMoneyValue(addon.value != null ? addon.value : addon.price);
          if (v != null && v > 0) {
            extra += v;
          }
        });
      }
    }
    var expressChosen = pickFirst(row, ['express_shipping_chosen', 'express_shipping_selected']);
    var expressOn =
      expressChosen === true ||
      expressChosen === 1 ||
      expressChosen === '1' ||
      String(expressChosen || '').toLowerCase() === 'true';
    if (expressOn) {
      var expressCost = parseMoneyValue(row.express_shipping_cost);
      if (expressCost != null && expressCost > 0) {
        extra += expressCost;
      }
    }
    return round2(extra);
  }

  /** Parse POS custom add-on segments from cart line options (Name:_blank_:Price). */
  function parseCustomAddonOptionsFromRow(row) {
    if (!row || typeof row !== 'object') {
      return [];
    }
    var raw = pickFirst(row, ['options', 'option', 'selected_options']);
    if (raw == null || String(raw).trim() === '') {
      return [];
    }
    var out = [];
    String(raw)
      .split('|')
      .forEach(function (part) {
        var s = String(part || '').trim();
        if (!s) {
          return;
        }
        var marker = ':_blank_:';
        var idx = s.indexOf(marker);
        if (idx <= 0) {
          return;
        }
        var name = s.slice(0, idx);
        var priceStr = s.slice(idx + marker.length);
        if (!/^[A-Za-z_]+$/.test(name)) {
          return;
        }
        var price = parseMoneyValue(priceStr);
        if (price == null || price < 0) {
          return;
        }
        out.push({ name: name, price: price });
      });
    return out;
  }

  function addonDisplayPrice(addon) {
    if (!addon || typeof addon !== 'object') {
      return null;
    }
    return parseMoneyValue(
      pickFirst(addon, ['value', 'price', 'amount', 'addon_price', 'cost', 'Price', 'Value'])
    );
  }

  function formatAddonLabelWithPrice(name, price) {
    var label = String(name || '').trim();
    if (!label) {
      return '';
    }
    if (price != null && !isNaN(price) && price > 0) {
      return label + ' (' + formatRupeeInrDisplay(price) + ')';
    }
    return label;
  }

  function lineAddonLabels(row) {
    if (!row || typeof row !== 'object') {
      return [];
    }
    var labels = [];
    var customFromOptions = parseCustomAddonOptionsFromRow(row);
    var priceByName = {};
    customFromOptions.forEach(function (item) {
      if (item.name) {
        priceByName[item.name.toLowerCase()] = item.price;
      }
    });

    var addons = row.addons_selected;
    if (Array.isArray(addons)) {
      addons.forEach(function (addon) {
        if (!addon || typeof addon !== 'object') {
          return;
        }
        var name = String(addon.name || addon.title || '').trim();
        if (!name) {
          return;
        }
        var price = addonDisplayPrice(addon);
        if ((price == null || price <= 0) && priceByName[name.toLowerCase()] != null) {
          price = priceByName[name.toLowerCase()];
        }
        var label = formatAddonLabelWithPrice(name, price);
        if (label) {
          labels.push(label);
        }
      });
    }

    if (!labels.length && customFromOptions.length) {
      customFromOptions.forEach(function (item) {
        var label = formatAddonLabelWithPrice(item.name, item.price);
        if (label) {
          labels.push(label);
        }
      });
    }

    if (!labels.length) {
      var frame = parseMoneyValue(pickFirst(row, ['framevalue', 'frame_value', 'frame_price']));
      if (frame != null && frame > 0) {
        labels.push(formatAddonLabelWithPrice('Add-on', frame));
      }
    }
    if (
      labels.indexOf('Express Shipping') === -1 &&
      !labels.some(function (lbl) {
        return String(lbl).indexOf('Express Shipping') === 0;
      }) &&
      (row.express_shipping_chosen === true ||
        row.express_shipping_chosen === 1 ||
        row.express_shipping_chosen === '1' ||
        String(row.express_shipping_chosen || '').toLowerCase() === 'true')
    ) {
      var expressCost = parseMoneyValue(row.express_shipping_cost);
      labels.push(formatAddonLabelWithPrice('Express Shipping', expressCost));
    }
    return labels;
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

  var CART_IMAGE_LIGHTBOX_ID = 'posCartImageLightbox';

  function ensurePosCartImageLightbox() {
    var lb = document.getElementById(CART_IMAGE_LIGHTBOX_ID);
    if (lb) {
      return lb;
    }
    lb = document.createElement('div');
    lb.id = CART_IMAGE_LIGHTBOX_ID;
    lb.className =
      'fixed inset-0 z-[10060] hidden flex-col items-center justify-center bg-black/85 p-4 sm:p-6';
    lb.setAttribute('role', 'dialog');
    lb.setAttribute('aria-modal', 'true');
    lb.setAttribute('aria-label', 'Enlarged product image');
    lb.innerHTML =
      '<p class="sr-only">Enlarged product image</p>' +
      '<button type="button" class="pos-cart-image-lightbox-close absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/30 bg-white/10 text-white text-xl font-light hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-400" aria-label="Close">&times;</button>' +
      '<img id="posCartImageLightboxImg" src="" alt="" class="max-h-[90vh] max-w-full rounded-lg object-contain shadow-2xl ring-1 ring-white/10 bg-white" />';
    document.body.appendChild(lb);
    lb.addEventListener('click', function (ev) {
      if (ev.target === lb) {
        closePosCartImageLightbox();
      }
    });
    var closeBtn = lb.querySelector('.pos-cart-image-lightbox-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function (ev) {
        ev.stopPropagation();
        closePosCartImageLightbox();
      });
    }
    return lb;
  }

  function openPosCartImageLightbox(url, alt) {
    if (!url || !String(url).trim()) {
      return;
    }
    var lb = ensurePosCartImageLightbox();
    var img = document.getElementById('posCartImageLightboxImg');
    if (!img) {
      return;
    }
    img.src = String(url).trim();
    img.alt = alt ? String(alt) : 'Product image';
    lb.classList.remove('hidden');
    lb.classList.add('flex');
    lb.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function closePosCartImageLightbox() {
    var lb = document.getElementById(CART_IMAGE_LIGHTBOX_ID);
    var img = document.getElementById('posCartImageLightboxImg');
    if (!lb) {
      return;
    }
    lb.classList.add('hidden');
    lb.classList.remove('flex');
    lb.style.display = 'none';
    if (img) {
      img.src = '';
      img.alt = '';
    }
    document.body.style.overflow = '';
  }

  function buildCartLineThumbnailHtml(imgUrl, title) {
    var safeTitle = escapeHtml(String(title || 'Product'));
    if (!imgUrl) {
      return (
        '<div class="shrink-0 w-14 h-14 rounded-md border border-dashed border-slate-200 bg-slate-50 flex items-center justify-center text-slate-300 text-sm" title="No image">\u25c7</div>'
      );
    }
    return (
      '<button type="button" class="pos-cart-line-image-enlarge shrink-0 w-14 h-14 rounded-md border border-slate-200 bg-white overflow-hidden cursor-pointer hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-400"' +
      ' data-full-src="' +
      escapeHtml(imgUrl) +
      '" data-image-alt="' +
      safeTitle +
      '" title="Click to enlarge" aria-label="Enlarge product image">' +
      '<img src="' +
      escapeHtml(imgUrl) +
      '" alt="' +
      safeTitle +
      '" class="pointer-events-none h-full w-full object-contain p-0.5" loading="lazy" decoding="async" />' +
      '</button>'
    );
  }

  function parseMoneyValue(val) {
    if (val == null || val === '') {
      return null;
    }
    var n = parseFloat(String(val).replace(/,/g, ''));
    return isNaN(n) ? null : n;
  }

  function round2(n) {
    return Math.round(n * 100) / 100;
  }

  /** Best-effort list unit ₹ (GST-inclusive unit as returned by retrieve). */
  function lineListUnitNumber(row, qty) {
    var u = parseMoneyValue(lineUnitPriceStr(row));
    if (u != null && u >= 0) {
      return round2(u + lineAddonExtraRupee(row));
    }
    var lt = parseMoneyValue(lineLineTotalStr(row, qty));
    if (lt != null && qty >= 1 && lt >= 0) {
      return lt / qty;
    }
    return null;
  }

  /** Base item code for Exotic POS price edit APIs. */
  function lineItemCodeForApi(row) {
    var c = pickFirst(row, ['item_code', 'itemcode', 'product_code', 'code', 'sku']);
    return c != null ? String(c).trim() : '';
  }

  function lineSizeForApi(row) {
    var s = pickFirst(row, ['size', 'Size', 'variationsize', 'variation_size']);
    return s != null && String(s).trim() !== '' ? String(s).trim() : '';
  }

  function lineColorForApi(row) {
    var c = pickFirst(row, ['color', 'Color', 'colour', 'variationcolor', 'variation_color']);
    if (c != null && String(c).trim() !== '') {
      return String(c).trim();
    }
    var v = pickFirst(row, ['variation', 'Variation', 'variant']);
    return v != null && String(v).trim() !== '' ? String(v).trim() : '';
  }

  function computePosUnitFromList(listUnit) {
    if (listUnit == null || isNaN(listUnit) || listUnit < 0) {
      return 0;
    }
    return round2(listUnit);
  }

  /** Extended line ₹ after user-defined line discounts (GST-inclusive POS total for the row). */
  function linePosExtendedFromRow(row) {
    var qty = lineQty(row);
    var listU = lineListUnitNumber(row, qty);
    if (listU == null) {
      listU = 0;
    }
    var ref = lineCartRef(row);
    var posU = computePosUnitFromList(listU);
    return round2(posU * qty);
  }

  function cartDeductionPoolFromTotals(totals) {
    if (!totals || typeof totals !== 'object') {
      return 0;
    }
    var c = totals.couponDeduction != null && !isNaN(Number(totals.couponDeduction)) ? Number(totals.couponDeduction) : 0;
    var u =
      totals.customDeduction != null && !isNaN(Number(totals.customDeduction)) ? Number(totals.customDeduction) : 0;
    var g =
      totals.giftDeduction != null && !isNaN(Number(totals.giftDeduction)) ? Number(totals.giftDeduction) : 0;
    return round2(Math.max(0, c) + Math.max(0, u) + Math.max(0, g));
  }

  function lineListExtendedWeight(row, qty) {
    var listU = lineListUnitNumber(row, qty);
    if (listU == null || listU < 0) {
      listU = 0;
    }
    return Math.max(0, round2(listU * qty));
  }

  /** Split pool across lines by list/catalog extended value; remainder on last rounded row. */
  function proportionalAllocatePool(pool, weights) {
    var n = weights.length;
    var out = new Array(n).fill(0);
    if (!(pool > 0.001) || !n) {
      return out;
    }
    var sumW = 0;
    for (var wi = 0; wi < n; wi++) {
      sumW += weights[wi];
    }
    var remaining = pool;
    if (!(sumW > 0.001)) {
      var base = round2(pool / n);
      for (var ej = 0; ej < n; ej++) {
        if (ej === n - 1) {
          out[ej] = round2(remaining);
        } else {
          out[ej] = base;
          remaining = round2(remaining - base);
        }
      }
      return out;
    }
    for (var k = 0; k < n; k++) {
      if (k === n - 1) {
        out[k] = round2(remaining);
      } else {
        var share = round2((pool * weights[k]) / sumW);
        out[k] = share;
        remaining = round2(remaining - share);
      }
    }
    return out;
  }

  /**
   * Share coupon + custom across lines (weight = list ₹ extended). Caps each slice at the line POS extended
   * so row totals stay ≥ 0 and redistributes overflow to lines with spare room (multi-pass).
   */
  function computePerLineCartAllocations(data, totals) {
    var items = getCartItems(data || {});
    var n = items.length;
    var out = new Array(n).fill(0);
    var pool = cartDeductionPoolFromTotals(totals);
    if (!n || !(pool > 0.001)) {
      return out;
    }
    var weights = [];
    var posExts = [];
    for (var ix = 0; ix < n; ix++) {
      var qtyW = lineQty(items[ix]);
      weights.push(lineListExtendedWeight(items[ix], qtyW));
      posExts.push(linePosExtendedFromRow(items[ix]));
    }
    out = proportionalAllocatePool(pool, weights);
    var maxRounds = n + 4;
    for (var rnd = 0; rnd < maxRounds; rnd++) {
      var surplus = 0;
      for (var i = 0; i < n; i++) {
        var px = posExts[i];
        if (out[i] > px + 1e-6) {
          surplus = round2(surplus + round2(out[i] - px));
          out[i] = px;
        }
      }
      if (!(surplus > 1e-4)) {
        break;
      }
      var roomSum = 0;
      var room = [];
      for (var j = 0; j < n; j++) {
        var rm = Math.max(0, round2(posExts[j] - out[j]));
        room[j] = rm;
        roomSum += rm;
      }
      if (!(roomSum > 1e-4)) {
        break;
      }
      var remGive = surplus;
      var giveTotal = surplus;
      for (var g = 0; g < n; g++) {
        var gs = g === n - 1 ? remGive : round2((giveTotal * room[g]) / roomSum);
        var capGs = Math.max(0, round2(posExts[g] - out[g]));
        if (gs > capGs) {
          gs = capGs;
        }
        out[g] = round2(out[g] + gs);
        remGive = round2(remGive - gs);
      }
    }
    var drift = round2(pool);
    var sumOk = 0;
    for (var z = 0; z < n; z++) {
      sumOk += out[z];
    }
    drift = round2(drift - sumOk);
    if (Math.abs(drift) > 1e-4) {
      var bestIx = -1;
      var bestRm = -1;
      var wantAdd = drift > 0 ? drift : 0;
      if (wantAdd > 1e-6) {
        for (var b = 0; b < n; b++) {
          var rmb = Math.max(0, round2(posExts[b] - out[b]));
          if (rmb >= wantAdd - 1e-6 && rmb > bestRm) {
            bestRm = rmb;
            bestIx = b;
          }
        }
        if (bestIx < 0) {
          bestRm = -1;
          for (var bb = 0; bb < n; bb++) {
            var rm2 = Math.max(0, round2(posExts[bb] - out[bb]));
            if (rm2 > bestRm + 1e-9) {
              bestRm = rm2;
              bestIx = bb;
            }
          }
        }
        if (bestIx >= 0 && bestRm > 1e-6 && wantAdd > 1e-6) {
          var bump = Math.min(wantAdd, bestRm);
          out[bestIx] = round2(out[bestIx] + bump);
        }
      } else if (drift < -1e-4) {
        for (var bk = n - 1; bk >= 0; bk--) {
          var takeOff = Math.min(out[bk], -drift);
          if (!(takeOff > 1e-6)) {
            continue;
          }
          out[bk] = round2(out[bk] - takeOff);
          drift = round2(drift + takeOff);
          if (Math.abs(drift) < 1e-4) {
            break;
          }
        }
      }
    }
    return out;
  }

  function sumEffectiveMerchAfterCartAlloc(data, totals) {
    var items = getCartItems(data || {});
    if (!items.length) {
      return 0;
    }
    var allocs = computePerLineCartAllocations(data, totals);
    var sum = 0;
    for (var i = 0; i < items.length; i++) {
      var px = linePosExtendedFromRow(items[i]);
      sum += Math.max(0, round2(px - (allocs[i] || 0)));
    }
    return round2(sum);
  }

  function sumAdjustedMerchFromCartItems(data) {
    var items = getCartItems(data || {});
    var sum = 0;
    for (var i = 0; i < items.length; i++) {
      var qty = lineQty(items[i]);
      var listU = lineListUnitNumber(items[i], qty);
      var ref = lineCartRef(items[i]);
      if (listU == null) {
        listU = 0;
      }
      var posU = computePosUnitFromList(listU, qty, ref);
      sum += posU * qty;
    }
    return round2(sum);
  }

  /** Total ₹ discount from manual per-line adjustments (excludes coupon/custom pool). */
  function sumManualLineDiscountFromCartItems(data) {
    var items = getCartItems(data || {});
    if (!items.length) {
      return 0;
    }
    var sum = 0;
    for (var i = 0; i < items.length; i++) {
      var row = items[i];
      var qty = lineQty(row);
      var baseExt = lineListExtendedWeight(row, qty);
      var posExt = linePosExtendedFromRow(row);
      var d = round2(baseExt - posExt);
      if (d > 0) {
        sum += d;
      }
    }
    return round2(sum);
  }

  /**
   * Recompute GST on discounted line totals (after line discounts + share of coupon/custom allocation).
   * Preference:
   * 1) use line GST rate (if available) on adjusted inclusive line total
   * 2) fallback to scaling API GST amount by adjusted/original line ratio
   * @param {Record<string, unknown>|null|undefined} [totals] — when coupon/custom pool &gt; 0, allocates into lines here
   */
  function sumAdjustedGstFromCartItems(data, totals) {
    var items = getCartItems(data || {});
    if (!items.length) {
      return null;
    }
    var allocs = null;
    if (totals && cartDeductionPoolFromTotals(totals) > 0.001) {
      allocs = computePerLineCartAllocations(data, totals);
    }
    var sum = 0;
    var ok = false;
    for (var i = 0; i < items.length; i++) {
      var row = items[i];
      var qty = lineQty(row);
      var listU = lineListUnitNumber(row, qty);
      if (listU == null) {
        listU = 0;
      }
      var ref = lineCartRef(row);
      var posU = computePosUnitFromList(listU, qty, ref);
      var posExtLine = round2(posU * qty);
      var allocCut = allocs != null && allocs.length === items.length ? allocs[i] || 0 : 0;
      var adjLine = Math.max(0, round2(posExtLine - allocCut));
      if (!(adjLine > 0)) {
        continue;
      }
      var rate = lineGstRatePercent(row);
      if (rate != null && rate > 0 && rate <= 40) {
        var gstByRate = adjLine - adjLine / (1 + rate / 100);
        if (!isNaN(gstByRate) && gstByRate >= 0) {
          sum += gstByRate;
          ok = true;
          continue;
        }
      }
      var origLine = parseMoneyValue(lineLineTotalStr(row, qty));
      var origG = lineResolvedGstRupees(row, qty);
      if (origLine != null && origLine > 0 && origG != null && !isNaN(origG) && origG >= 0) {
        var scaled = origG * (adjLine / origLine);
        if (!isNaN(scaled) && scaled >= 0) {
          sum += scaled;
          ok = true;
        }
      }
    }
    return ok ? round2(sum) : null;
  }

  /** @returns {Record<string, unknown>} */
  function mergeTotalsWithLineAdjustments(data, totals) {
    var items = getCartItems(data || {});
    if (!items.length || !totals) {
      return totals;
    }
    var rawMerch = sumLineTotalsFromCartItems(data);
    var pool = cartDeductionPoolFromTotals(totals);
    var effMerch = sumEffectiveMerchAfterCartAlloc(data, totals);
    var comparableRaw = rawMerch;
    if (comparableRaw == null || isNaN(comparableRaw)) {
      comparableRaw = sumAdjustedMerchFromCartItems(data);
    }
    var needsMergeCartLevel = pool > 0.001;
    var needsMergeLineAdj = comparableRaw != null && !isNaN(comparableRaw) && Math.abs(effMerch - comparableRaw) >= 0.02;
    if (!needsMergeCartLevel && !needsMergeLineAdj) {
      return totals;
    }
    var out = Object.assign({}, totals);
    var baseSub = out.subtotal != null && !isNaN(out.subtotal) ? out.subtotal : comparableRaw || effMerch;
    if (comparableRaw != null && !isNaN(comparableRaw)) {
      out.subtotal = round2(baseSub + (effMerch - comparableRaw));
    }
    var adjGst = sumAdjustedGstFromCartItems(data, totals);
    if (adjGst != null && !isNaN(adjGst)) {
      out.gstTotal = round2(adjGst);
    }
    if (needsMergeCartLevel) {
      // Cart-level discounts are already absorbed into line totals (and thus subtotal).
      // Keep amounts for display, but don't subtract again when computing grand total.
      out.cartDiscountAbsorbed = true;
    }
    var coupon =
      !needsMergeCartLevel && out.couponDeduction != null && !isNaN(Number(out.couponDeduction))
        ? Number(out.couponDeduction)
        : 0;
    var cust =
      !needsMergeCartLevel && out.customDeduction != null && !isNaN(Number(out.customDeduction))
        ? Number(out.customDeduction)
        : 0;
    var grand = out.subtotal - coupon - cust;
    out.grandTotal = round2(grand >= 0 ? grand : 0);
    return out;
  }

  /**
   * Totals used to allocate coupon/custom discount to lines at checkout (API fields may omit custom_reduce).
   * @returns {Record<string, unknown>|null}
   */
  function getTotalsForCheckoutAlloc() {
    var d = window.__posCartLastRetrieveData;
    if (!d || typeof d !== 'object') {
      return null;
    }
    var rawT = totalsFromRetrieve(d);
    if (cartDeductionPoolFromTotals(rawT) > 0.001) {
      return rawT;
    }
    var last = window.__posCartLastTotals;
    if (last && cartDeductionPoolFromTotals(last) > 0.001) {
      return {
        subtotal: last.subtotal,
        couponDeduction: last.couponDeduction,
        customDeduction: last.customDeduction,
        giftDeduction: last.giftDeduction
      };
    }
    if (posCustomDiscountPersist && posCustomDiscountPersist.value > 0) {
      var send = computeCustomReduceInr(
        posCustomDiscountPersist.mode,
        posCustomDiscountPersist.value,
        rawT
      );
      if (send > 0) {
        return Object.assign({}, rawT, { customDeduction: send });
      }
    }
    return rawT;
  }

  /**
   * @returns {Array<{ itemcode: string, size: string, color: string, price: string }>}
   */
  function buildPosLinePricesPayload(data, totalsOverride) {
    var items = getCartItems(data || {});
    var rawT =
      totalsOverride && typeof totalsOverride === 'object'
        ? totalsOverride
        : totalsFromRetrieve(data && typeof data === 'object' ? data : {});
    var alloc =
      cartDeductionPoolFromTotals(rawT) > 0.001 ? computePerLineCartAllocations(data || {}, rawT) : null;
    var out = [];
    for (var i = 0; i < items.length; i++) {
      var row = items[i];
      var qty = lineQty(row);
      var posExt = linePosExtendedFromRow(row);
      var cut =
        alloc != null && alloc.length === items.length ? alloc[i] || 0 : 0;
      var effExt = Math.max(0, round2(posExt - cut));
      var unitAfter = qty >= 1 ? round2(effExt / qty) : effExt;
      out.push({
        itemcode: lineItemCodeForApi(row),
        size: lineSizeForApi(row),
        color: lineColorForApi(row),
        price:
          formatMoneyDisplay(unitAfter) != null ? String(formatMoneyDisplay(unitAfter)) : String(round2(unitAfter))
      });
    }
    return out;
  }

  /** Unit price from cart line (API fields vary). */
  function lineUnitPriceStr(row) {
    var v = pickFirst(row, ['unit_price', 'item_price', 'single_price', 'original_price', 'price', 'selling_price']);
    return v != null && String(v) !== '' ? String(v) : '';
  }

  /** Line amount from API or unit price × qty when Exotic omits an explicit line total. */
  function lineLineTotalStr(row, qty) {
    var addonExtra = lineAddonExtraRupee(row);
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
      var t = (unit + addonExtra) * qty;
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

    var giftDeduction =
      pickNumber(d, [
        'giftvoucherreduction',
        'gift_voucher_reduction',
        'giftvoucher_reduce',
        'gift_voucher_reduce',
        'giftvoucherdiscount',
        'gift_discount',
        'giftvouchereduction'
      ]) ||
      pickNumber(cd, [
        'giftvoucher_reduce',
        'gift_voucher_reduce',
        'giftvoucher_reduction',
        'giftvoucherdiscount'
      ]);

    /**
     * Grand total for this panel: GST-inclusive merchandise (sub) minus coupon/custom/gift.
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
      if (giftDeduction != null && !isNaN(giftDeduction)) {
        grandTotal -= giftDeduction;
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
      giftDeduction: giftDeduction,
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

  /** Display ₹ 1,234.56 (en-IN grouping). */
  function formatRupeeInrDisplay(val) {
    if (val == null || (typeof val === 'number' && isNaN(val))) {
      return '\u2014';
    }
    var n = typeof val === 'number' ? val : parseFloat(String(val).replace(/,/g, ''));
    if (isNaN(n)) {
      return String(val);
    }
    return (
      '\u20b9 ' +
      n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    );
  }

  function summaryAmountCell(val) {
    var disp = formatMoneyDisplay(val);
    if (disp == null) {
      return '\u2014';
    }
    var n = parseFloat(String(disp).replace(/,/g, ''));
    if (isNaN(n)) {
      return escapeHtml(disp);
    }
    return (
      '\u20b9 ' +
      n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    );
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
    var text = disp == null ? '\u2014' : summaryAmountCell(val);
    var hasRemove = !isGrand && removeBtnClass && String(removeBtnClass).trim() !== '';
    var rowClass = isGrand
      ? 'flex justify-between items-baseline gap-3 text-base font-bold text-slate-900 pt-3 mt-2 border-t border-dashed border-slate-300'
      : 'flex justify-between items-center gap-2 text-xs text-slate-600 py-1.5';
    var labelSpan =
      '<span class="' +
      (isGrand ? 'text-slate-800 font-bold' : 'text-slate-500 font-medium min-w-0 pr-1') +
      '">' +
      escapeHtml(label) +
      '</span>';
    var amountSpan =
      '<span class="tabular-nums ' +
      (isGrand ? 'text-orange-600' : disp == null ? 'text-slate-400' : 'text-slate-800 font-semibold') +
      '">' +
      (disp == null ? escapeHtml('\u2014') : text) +
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

  /**
   * Summary row with optional note line under the label.
   * @param {string} label
   * @param {number|null} val
   * @param {string} [note] Small secondary line (e.g. "(included in line totals)")
   * @param {boolean} isGrand
   * @param {string} [removeBtnClass]
   */
  function moneyRowSummaryNote(label, val, note, isGrand, removeBtnClass) {
    var disp = formatMoneyDisplay(val);
    var text = disp == null ? '\u2014' : summaryAmountCell(val);
    var hasRemove = !isGrand && removeBtnClass && String(removeBtnClass).trim() !== '';
    var rowClass = isGrand
      ? 'flex justify-between items-baseline gap-3 text-base font-bold text-slate-900 pt-3 mt-2 border-t border-dashed border-slate-300'
      : 'flex justify-between items-start gap-2 text-xs text-slate-600 py-1.5';

    var safeNote = note != null && String(note).trim() !== '' ? String(note).trim() : '';
    var labelHtml =
      '<span class="' +
      (isGrand ? 'text-slate-800 font-bold' : 'text-slate-500 font-medium min-w-0 pr-1') +
      '">' +
      escapeHtml(label) +
      (safeNote && !isGrand
        ? '<div class="text-[11px] text-slate-400 leading-snug mt-0.5">' + escapeHtml(safeNote) + '</div>'
        : '') +
      '</span>';

    var amountSpan =
      '<span class="tabular-nums ' +
      (isGrand ? 'text-orange-600' : disp == null ? 'text-slate-400' : 'text-slate-800 font-semibold') +
      '">' +
      (disp == null ? escapeHtml('\u2014') : text) +
      '</span>';

    if (!hasRemove) {
      return '<div class="' + rowClass + '">' + labelHtml + amountSpan + '</div>';
    }
    return (
      '<div class="' +
      rowClass +
      '">' +
      labelHtml +
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
    var shell = sticky.querySelector('[data-pos-cart-scroll]');
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

  function buildCartViewToggleHtml() {
    if (isCartTablePage()) {
      return (
        '<div class="pos-cart-view-toggle flex flex-wrap items-center justify-between gap-2 mb-4 pb-3 border-b border-slate-100">' +
        '<span class="text-xs font-bold uppercase tracking-wider text-slate-500">Cart lines</span>' +
        '<a href="' +
        escapeHtml(posRegisterListUrl()) +
        '" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 transition">' +
        '<i class="fas fa-th-large text-[10px]" aria-hidden="true"></i> Cards view on POS</a>' +
        '</div>'
      );
    }
    return (
      '<div class="pos-cart-view-toggle flex items-center justify-between gap-2 mb-3 pb-2 border-b border-slate-100">' +
      '<span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Cart</span>' +
      '<div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5">' +
      '<button type="button" class="pos-cart-view-toggle-btn rounded-md px-2.5 py-1 text-[10px] font-semibold transition bg-white text-orange-700 shadow-sm" data-view="card">Cards</button>' +
      '<a href="' +
      escapeHtml(posCartTablePageUrl()) +
      '" target="_blank" rel="noopener noreferrer" class="pos-cart-view-table-link rounded-md px-2.5 py-1 text-[10px] font-semibold transition text-slate-600 hover:text-slate-800 hover:bg-white/80">Table</a>' +
      '</div></div>'
    );
  }

  function captureCartDraftRows(panel) {
    if (!panel) {
      return [{ sku: '', qty: '1' }];
    }
    var rows = [];
    panel.querySelectorAll('.pos-cart-draft-row').forEach(function (tr) {
      var skuEl = tr.querySelector('.pos-cart-draft-sku');
      var qtyEl = tr.querySelector('.pos-cart-draft-qty');
      rows.push({
        sku: skuEl ? String(skuEl.value || '').trim() : '',
        qty: qtyEl ? String(qtyEl.value || '1').trim() : '1'
      });
    });
    return rows.length ? rows : [{ sku: '', qty: '1' }];
  }

  function buildCartDraftRowHtml(row, isOnly) {
    var sku = escapeHtml(String((row && row.sku) || ''));
    var qty = escapeHtml(String((row && row.qty) || '1'));
    var removeBtn =
      isOnly
        ? ''
        : '<button type="button" class="pos-cart-draft-remove text-slate-400 hover:text-red-600 px-1" title="Remove row" aria-label="Remove row"><i class="fas fa-times text-[10px]"></i></button>';
    return (
      '<tr class="pos-cart-draft-row">' +
      '<td class="py-1.5 pr-1"><div class="relative">' +
      '<input type="text" class="pos-cart-draft-sku w-full min-w-[5rem] rounded border border-slate-300 bg-white px-2 py-1.5 text-[11px] outline-none focus:border-orange-500" placeholder="Type SKU…" autocomplete="off" value="' +
      sku +
      '" />' +
      '<div class="pos-cart-draft-suggest absolute left-0 right-0 top-full z-[100] mt-0.5 hidden max-h-36 overflow-auto rounded-lg border border-slate-200 bg-white shadow-lg" style="display:none"></div>' +
      '</div></td>' +
      '<td class="py-1.5 px-1 w-14">' +
      '<input type="number" min="1" step="1" class="pos-cart-draft-qty w-full rounded border border-slate-300 bg-white px-1 py-1.5 text-center text-[11px]" value="' +
      qty +
      '" />' +
      '</td>' +
      '<td class="py-1.5 pl-1 w-8 text-right whitespace-nowrap">' +
      removeBtn +
      '</td>' +
      '</tr>'
    );
  }

  function buildCartDraftSectionHtml(draftRows) {
    var rows = Array.isArray(draftRows) && draftRows.length ? draftRows : [{ sku: '', qty: '1' }];
    var body = rows
      .map(function (row, idx) {
        return buildCartDraftRowHtml(row, rows.length === 1);
      })
      .join('');
    return (
      '<div class="pos-cart-draft-section mt-3 rounded-lg border border-dashed border-orange-200 bg-orange-50/40 p-2">' +
      '<div class="flex items-center justify-between gap-2 mb-1.5">' +
      '<p class="text-[10px] font-bold uppercase tracking-wider text-orange-800/80">Add by SKU</p>' +
      '<button type="button" class="pos-cart-draft-add-row inline-flex items-center gap-1 rounded-md border border-orange-200 bg-white px-2 py-1 text-[10px] font-semibold text-orange-700 hover:bg-orange-50">' +
      '<i class="fas fa-plus text-[9px]" aria-hidden="true"></i> Add row</button>' +
      '</div>' +
      '<div class="pos-cart-draft-table-wrap -mx-0.5">' +
      '<table class="w-full border-collapse text-[11px]">' +
      '<thead><tr class="text-left text-[9px] uppercase tracking-wide text-slate-500">' +
      '<th class="pb-1 font-semibold">SKU</th><th class="pb-1 font-semibold w-14 text-center">Qty</th><th class="pb-1 w-8"></th>' +
      '</tr></thead>' +
      '<tbody class="pos-cart-draft-body">' +
      body +
      '</tbody></table></div>' +
      '<p class="mt-1.5 text-[9px] text-slate-500 leading-snug">Pick from suggestions or press Enter to open product details (add-ons &amp; custom add-ons). Shift+Enter for a new line in SKU lists.</p>' +
      '</div>'
    );
  }

  function normalizeCartFacet(val) {
    if (val == null) {
      return '';
    }
    var s = String(val).trim();
    if (s === '' || s === '0' || s.toLowerCase() === 'n/a') {
      return '';
    }
    return s;
  }

  function buildCartAddPayloadFromProduct(product, qty) {
    var p = product || {};
    var size = normalizeCartFacet(p.size);
    var color = normalizeCartFacet(p.color);
    var itemCode = normalizeCartFacet(p.item_code);
    var sku = normalizeCartFacet(p.sku);
    var level = String(p.item_level || '').trim().toLowerCase();
    var variation = '';
    if (size || color) {
      variation = (size || '') + ':' + (color || '');
    }
    var cartCode = '';
    if (level === 'parent') {
      cartCode = itemCode;
    } else if (level === 'variation' || (itemCode !== '' && variation !== '')) {
      cartCode = itemCode;
    } else {
      cartCode = itemCode || sku;
    }
    if (!cartCode) {
      cartCode = sku;
    }
    return {
      code: cartCode,
      qty: qty,
      variation: variation,
      options: '',
      item_level: String(p.item_level || '').trim(),
      item_code: itemCode,
      size: size,
      color: color
    };
  }

  function posCartDraftRowFromTarget(el) {
    return el && el.closest ? el.closest('.pos-cart-draft-row') : null;
  }

  function posCartDraftProductFromPickBtn(btn) {
    if (!btn) {
      return null;
    }
    return {
      sku: btn.getAttribute('data-sku'),
      item_code: btn.getAttribute('data-item-code'),
      size: btn.getAttribute('data-size'),
      color: btn.getAttribute('data-color'),
      item_level: btn.getAttribute('data-item-level')
    };
  }

  function posCartDraftHideSuggest(boxEl) {
    if (!boxEl) {
      return;
    }
    boxEl.classList.add('hidden');
    boxEl.innerHTML = '';
    boxEl.style.display = 'none';
  }

  function posCartDraftSetActivePick(boxEl, idx) {
    if (!boxEl) {
      return null;
    }
    var picks = boxEl.querySelectorAll('.pos-cart-draft-sku-pick');
    if (!picks.length) {
      return null;
    }
    var safeIdx = Math.max(0, Math.min(idx, picks.length - 1));
    picks.forEach(function (btn, i) {
      btn.classList.toggle('pos-cart-draft-sku-pick-active', i === safeIdx);
      btn.setAttribute('aria-selected', i === safeIdx ? 'true' : 'false');
    });
    boxEl.setAttribute('data-active-pick-idx', String(safeIdx));
    return picks[safeIdx];
  }

  function posCartDraftActivePick(rowEl) {
    var boxEl = rowEl ? rowEl.querySelector('.pos-cart-draft-suggest') : null;
    if (!boxEl || boxEl.classList.contains('hidden')) {
      return null;
    }
    var active = boxEl.querySelector('.pos-cart-draft-sku-pick-active');
    if (active) {
      return active;
    }
    return boxEl.querySelector('.pos-cart-draft-sku-pick');
  }

  function posCartDraftOpenProductModal(product, rowEl) {
    var p = product || {};
    var sku = normalizeCartFacet(p.sku);
    var itemCode = normalizeCartFacet(p.item_code);
    var code = sku || itemCode || normalizeCartFacet(p.code);
    if (!code) {
      toast('Missing product code', 'red');
      return;
    }
    var qtyEl = rowEl ? rowEl.querySelector('.pos-cart-draft-qty') : null;
    var qty = parseInt(String(qtyEl && qtyEl.value != null ? qtyEl.value : '1'), 10);
    if (!qty || qty < 1) {
      qty = 1;
    }
    var suggest = rowEl ? rowEl.querySelector('.pos-cart-draft-suggest') : null;
    if (suggest) {
      posCartDraftHideSuggest(suggest);
    }
    var skuIn = rowEl ? rowEl.querySelector('.pos-cart-draft-sku') : null;
    if (skuIn) {
      skuIn.value = code;
    }
    window.__posCartDraftRowAfterAdd = rowEl || null;
    if (typeof window.openProductModalByCode !== 'function') {
      toast('Product details unavailable — reload the page and try again.', 'red');
      window.__posCartDraftRowAfterAdd = null;
      return;
    }
    window.openProductModalByCode(
      code,
      [],
      {
        id: p.id,
        sku: sku || code,
        item_code: itemCode,
        title: p.title || p.name,
        name: p.name || p.title,
        size: p.size,
        color: p.color,
        item_level: p.item_level,
        image: p.image || p.image_url,
        price: p.price
      },
      { qty: qty }
    );
  }

  function posCartDraftExactLookup(sku, rowEl) {
    var q = String(sku || '').trim();
    if (!q) {
      return Promise.resolve();
    }
    return fetch('?page=products&action=search_product&q=' + encodeURIComponent(q) + '&exact=1', {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' }
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (data && data.success && data.product) {
          posCartDraftOpenProductModal(data.product, rowEl);
          return;
        }
        toast((data && data.message) ? data.message : 'No product found for SKU: ' + q, 'red');
      })
      .catch(function () {
        toast('Could not look up SKU. Try again.', 'red');
      });
  }

  function posCartDraftFetchSuggest(inputEl) {
    if (!inputEl) {
      return;
    }
    var rowEl = posCartDraftRowFromTarget(inputEl);
    var boxEl = rowEl ? rowEl.querySelector('.pos-cart-draft-suggest') : null;
    if (!boxEl) {
      return;
    }
    var q = String(inputEl.value || '').trim();
    if (q.length < 2) {
      posCartDraftHideSuggest(boxEl);
      return;
    }
    clearTimeout(posCartDraftSuggestTimer);
    posCartDraftSuggestTimer = setTimeout(function () {
      fetch('?page=products&action=search_product&by=sku&q=' + encodeURIComponent(q), {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' }
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          var products =
            data && data.success && Array.isArray(data.products) ? data.products : [];
          if (!products.length) {
            posCartDraftHideSuggest(boxEl);
            return;
          }
          boxEl.innerHTML = products
            .slice(0, 10)
            .map(function (p, pickIdx) {
              var skuLbl = escapeHtml(String(p.sku || p.item_code || ''));
              var title = String(p.title || p.name || '').trim();
              var titleShort = title.length > 48 ? title.slice(0, 45) + '…' : title;
              var activeCls =
                pickIdx === 0 ? ' pos-cart-draft-sku-pick-active bg-orange-50 ring-1 ring-orange-200/80' : '';
              return (
                '<button type="button" class="pos-cart-draft-sku-pick block w-full text-left px-2 py-1.5 hover:bg-orange-50 border-b border-slate-100 last:border-0' +
                activeCls +
                '"' +
                ' data-sku="' +
                escapeHtml(String(p.sku || '')) +
                '" data-item-code="' +
                escapeHtml(String(p.item_code || '')) +
                '" data-size="' +
                escapeHtml(String(p.size || '')) +
                '" data-color="' +
                escapeHtml(String(p.color || '')) +
                '" data-item-level="' +
                escapeHtml(String(p.item_level || '')) +
                '" role="option"' +
                (pickIdx === 0 ? ' aria-selected="true"' : ' aria-selected="false"') +
                '>' +
                '<span class="font-semibold text-slate-800">' +
                skuLbl +
                '</span>' +
                (titleShort
                  ? '<span class="text-slate-500"> · ' + escapeHtml(titleShort) + '</span>'
                  : '') +
                '</button>'
              );
            })
            .join('');
          boxEl.classList.remove('hidden');
          boxEl.style.display = 'block';
          boxEl.setAttribute('data-active-pick-idx', '0');
        })
        .catch(function () {
          posCartDraftHideSuggest(boxEl);
        });
    }, 180);
  }

  function computeCartLineDisplay(row, idx, items, data, lineCartAllocs) {
    var ref = lineCartRef(row);
    var qty = lineQty(row);
    var unitPrice = lineUnitPriceStr(row);
    var listUNum = lineListUnitNumber(row, qty);
    var posUNum = listUNum != null ? computePosUnitFromList(listUNum, qty, ref) : null;
    var posExtBase = linePosExtendedFromRow(row);
    var effUnitNum;
    var effLineNum;
    if (lineCartAllocs != null && lineCartAllocs.length === items.length) {
      effLineNum = Math.max(0, round2(posExtBase - (lineCartAllocs[idx] || 0)));
      effUnitNum = qty >= 1 ? round2(effLineNum / qty) : effLineNum;
    } else {
      effUnitNum =
        posUNum != null
          ? posUNum
          : listUNum != null
            ? listUNum
            : parseMoneyValue(unitPrice);
      effLineNum =
        posUNum != null
          ? round2(posUNum * qty)
          : parseMoneyValue(lineLineTotalStr(row, qty));
      if ((effLineNum == null || isNaN(effLineNum)) && effUnitNum != null && !isNaN(effUnitNum)) {
        effLineNum = round2(effUnitNum * qty);
      }
    }
    return {
      ref: ref,
      qty: qty,
      unitDisp:
        effUnitNum != null && !isNaN(effUnitNum)
          ? formatRupeeInrDisplay(effUnitNum)
          : unitPrice
            ? '\u20b9 ' + escapeHtml(String(unitPrice))
            : '\u2014',
      lineDisp:
        effLineNum != null && !isNaN(effLineNum)
          ? formatRupeeInrDisplay(effLineNum)
          : '\u2014'
    };
  }

  function buildCartTableCartHtml(items, data, lineCartAllocs) {
    var html =
      '<div class="pos-cart-table-wrap overflow-x-auto -mx-0.5">' +
      '<table class="pos-cart-table w-full border-collapse text-[11px]">' +
      '<thead><tr class="border-b border-slate-200 text-left text-[9px] uppercase tracking-wide text-slate-500">' +
      '<th class="py-1.5 pr-2 font-semibold w-16">Image</th>' +
      '<th class="py-1.5 pr-1 font-semibold">SKU</th>' +
      '<th class="py-1.5 pr-1 font-semibold">Product</th>' +
      '<th class="py-1.5 px-1 font-semibold w-12 text-center">Qty</th>' +
      '<th class="py-1.5 px-1 font-semibold w-14 text-right">Price</th>' +
      '<th class="py-1.5 pl-1 font-semibold w-14 text-right">Total</th>' +
      '<th class="py-1.5 w-7"></th>' +
      '</tr></thead><tbody>';

    if (!items.length) {
      html +=
        '<tr><td colspan="7" class="py-4 text-center text-[11px] text-slate-400 italic">No items in cart yet — add SKUs below.</td></tr>';
    } else {
      items.forEach(function (row, idx) {
        var disp = computeCartLineDisplay(row, idx, items, data, lineCartAllocs);
        var ref = disp.ref;
        var qty = disp.qty;
        var title = lineTitle(row);
        var sub = lineSubDisplay(row);
        var productCode = String(pickFirst(row, ['code', 'item_code', 'sku']) || '').trim();
        var codeLbl = String(sub || productCode || '').trim() || '\u2014';
        var imgUrl = lineImageUrl(row);
        var maxSell = lineMaxSellableQty(row, data || {});
        var maxAttr =
          ref && maxSell != null && maxSell >= 1
            ? ' max="' + escapeHtml(String(maxSell)) + '" data-max-qty="' + escapeHtml(String(maxSell)) + '"'
            : '';
        html +=
          '<tr class="pos-cart-table-line border-b border-slate-100 hover:bg-slate-50/80">' +
          '<td class="py-2 pr-2 align-top w-16">' +
          buildCartLineThumbnailHtml(imgUrl, title) +
          '</td>' +
          '<td class="py-2 pr-1 align-top">' +
          '<span class="pos-cart-line-item font-semibold tabular-nums text-slate-700 cursor-pointer hover:text-orange-700"' +
          (productCode ? ' data-product-code="' + escapeHtml(productCode) + '"' : '') +
          ' title="View product">' +
          escapeHtml(codeLbl) +
          '</span></td>' +
          '<td class="py-2 pr-1 align-top' +
          (isCartTablePage() ? '' : ' max-w-[7rem]') +
          '">' +
          '<div class="' +
          (isCartTablePage()
            ? 'pos-cart-line-item text-slate-800 leading-snug cursor-pointer'
            : 'pos-cart-line-item line-clamp-2 text-slate-800 leading-snug cursor-pointer') +
          '"' +
          (productCode ? ' data-product-code="' + escapeHtml(productCode) + '"' : '') +
          ' title="' +
          escapeHtml(title) +
          '">' +
          escapeHtml(title) +
          '</div></td>' +
          '<td class="py-2 px-1 align-top text-center">';
        if (ref) {
          html +=
            '<input type="number" min="1" step="1" class="pos-cart-qty-input w-11 rounded border border-slate-300 bg-white px-1 py-1 text-center text-[11px] font-semibold outline-none focus:border-orange-500"' +
            maxAttr +
            ' data-cartref="' +
            escapeHtml(ref) +
            '" value="' +
            escapeHtml(String(qty)) +
            '" />';
        } else {
          html += escapeHtml(String(qty));
        }
        html +=
          '</td>' +
          '<td class="py-2 px-1 align-top text-right tabular-nums text-slate-600 whitespace-nowrap">' +
          disp.unitDisp +
          '</td>' +
          '<td class="py-2 pl-1 align-top text-right tabular-nums font-semibold text-orange-600 whitespace-nowrap">' +
          disp.lineDisp +
          '</td>' +
          '<td class="py-2 align-top text-right">';
        if (ref) {
          html +=
            '<button type="button" class="pos-cart-delete-btn inline-flex h-7 w-7 items-center justify-center rounded-full text-red-500 hover:bg-red-50" data-cartref="' +
            escapeHtml(ref) +
            '" title="Remove" aria-label="Remove"><i class="fas fa-trash-alt text-[10px]"></i></button>';
        }
        html += '</td></tr>';
      });
    }

    html += '</tbody></table></div>';
    return html;
  }

  function renderCartUI(data) {
    var panel = ensureCartPanel();
    if (!panel) {
      return;
    }
    panel.className = isCartTablePage()
      ? 'pos-exotic-cart-panel pos-cart-table-page-panel text-sm text-slate-800'
      : 'pos-exotic-cart-panel rounded-2xl border border-slate-200/90 bg-gradient-to-b from-slate-50 to-white shadow-sm px-3 py-4 text-sm text-slate-800 mx-0.5 mb-2';
    var items = getCartItems(data);
    var refsUsed = [];
    for (var ri = 0; ri < items.length; ri++) {
      var r0 = lineCartRef(items[ri]);
      if (r0) {
        refsUsed.push(r0);
      }
    }
    if (!items.length) {
      lastRetrieveCartDataSnapshot = null;
      clearAllLocalStockConfirmed();
    } else {
      pruneLocalStockConfirmed(refsUsed, data);
      lastRetrieveCartDataSnapshot =
        data && typeof data === 'object' ? data : {};
    }

    window.__posCartLastRetrieveData =
      lastRetrieveCartDataSnapshot ||
      (data && typeof data === 'object' ? data : null);

    var rawTotalsForAlloc = totalsFromRetrieve(data || {});
    var lineCartAllocs =
      cartDeductionPoolFromTotals(rawTotalsForAlloc) > 0.001
        ? computePerLineCartAllocations(data || {}, rawTotalsForAlloc)
        : null;

    var totals = mergeTotalsWithLineAdjustments(data || {}, rawTotalsForAlloc);
    var existingPanel = document.getElementById(PANEL_ID);
    var draftSnapshot =
      isCartTablePage() || cartViewMode === 'table' ? captureCartDraftRows(existingPanel) : null;
    var html = '';

    html += buildCartViewToggleHtml();

    if (isCartTablePage() || cartViewMode === 'table') {
      html += buildCartTableCartHtml(items, data, lineCartAllocs);
      html += buildCartDraftSectionHtml(draftSnapshot);
    } else if (!items.length) {
      html +=
        '<div class="flex flex-col items-center justify-center py-8 px-2 text-center rounded-xl border border-dashed border-slate-200 bg-slate-50/60">' +
        '<span class="text-2xl mb-1 opacity-40" aria-hidden="true">🛒</span>' +
        '<p class="text-sm font-medium text-slate-600">Your cart is empty</p>' +
        '<p class="text-[11px] text-slate-400 mt-1 max-w-[14rem]">Add products from the grid to see them here.</p>' +
        '</div>';
    } else {
      html += '<div class="flex flex-col pr-0.5">';
      items.forEach(function (row, idx) {
        var ref = lineCartRef(row);
        var qty = lineQty(row);
        var maxSell = lineMaxSellableQty(row, data || {});
        var localStockQty = lineLocalStockQty(row);
        var localStockShort = localStockQty != null && qty > localStockQty;
        var title = lineTitle(row);
        var sub = lineSubDisplay(row);
        var unitPrice = lineUnitPriceStr(row);
        var lineTotal = lineLineTotalStr(row, qty);
        var listUNum = lineListUnitNumber(row, qty);
        var posUNum = listUNum != null ? computePosUnitFromList(listUNum) : null;
        var imgUrl = lineImageUrl(row);
        var productCode = String(pickFirst(row, ['code', 'item_code', 'sku']) || '').trim();
        var codeLbl = String(sub || productCode || '').trim() || '\u2014';
        var posExtBase = linePosExtendedFromRow(row);
        var effUnitNum;
        var effLineNum;
        if (lineCartAllocs != null && lineCartAllocs.length === items.length) {
          effLineNum = Math.max(0, round2(posExtBase - (lineCartAllocs[idx] || 0)));
          effUnitNum = qty >= 1 ? round2(effLineNum / qty) : effLineNum;
        } else {
          effUnitNum =
            posUNum != null
              ? posUNum
              : listUNum != null
                ? listUNum
                : parseMoneyValue(unitPrice);
          effLineNum =
            posUNum != null
              ? round2(posUNum * qty)
              : parseMoneyValue(lineLineTotalStr(row, qty));
          if ((effLineNum == null || isNaN(effLineNum)) && effUnitNum != null && !isNaN(effUnitNum)) {
            effLineNum = round2(effUnitNum * qty);
          }
        }
        var unitDisp =
          effUnitNum != null && !isNaN(effUnitNum)
            ? formatRupeeInrDisplay(effUnitNum)
            : unitPrice
              ? '\u20b9 ' + escapeHtml(String(unitPrice))
              : '\u2014';
        var lineDisp =
          effLineNum != null && !isNaN(effLineNum)
            ? formatRupeeInrDisplay(effLineNum)
            : lineTotal
              ? '\u20b9 ' + escapeHtml(String(lineTotal))
              : '\u2014';
        var betweenClass =
          idx < items.length - 1 ? 'border-b border-dashed border-slate-300 pb-4 mb-4' : '';
        html += '<div' + (betweenClass ? ' class="' + betweenClass + '"' : '') + '>';
        html +=
          '<div class="pos-cart-line-item group flex flex-col gap-2"' +
          ' data-cart-row="1"' +
          (productCode ? ' data-product-code="' + escapeHtml(productCode) + '"' : '') +
          ' role="button" tabindex="0" title="View product details">';
        html += '<div class="flex gap-2.5">';
        html += buildCartLineThumbnailHtml(imgUrl, title);
        html += '<div class="min-w-0 flex-1 flex flex-col">';
        html += '<div class="flex items-start justify-between gap-2">';
        html +=
          '<span class="text-[11px] font-medium text-slate-500 tabular-nums">' +
          escapeHtml(codeLbl) +
          '</span>';
        if (ref) {
          html +=
            '<button type="button" class="pos-cart-delete-btn -mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600 shadow-sm transition hover:bg-red-100" data-cartref="' +
            escapeHtml(ref) +
            '" title="Remove from cart" aria-label="Remove from cart">' +
            '<i class="fas fa-trash-alt text-[12px]" aria-hidden="true"></i></button>';
        }
        html += '</div>';
        html +=
          '<div class="text-[13px] font-bold text-slate-900 leading-snug mt-0.5 pr-1 line-clamp-3">' +
          escapeHtml(title) +
          '</div>';
        var addonLabels = lineAddonLabels(row);
        if (addonLabels.length) {
          html +=
            '<div class="mt-1 text-[10px] font-medium text-emerald-800 leading-snug">' +
            escapeHtml('+ ' + addonLabels.join(', ')) +
            '</div>';
        }
        html += '</div></div>';
        html +=
          '<div class="text-[13px] tabular-nums text-slate-800 mt-0.5 leading-relaxed">' +
          unitDisp +
          ' \u00d7 ' +
          escapeHtml(String(qty)) +
          ' = <span class="font-bold text-orange-600">' +
          lineDisp +
          '</span>';
        html += '</div>';
        if (localStockShort && ref && !isLocalStockConfirmed(ref, qty, localStockQty, row)) {
          html +=
            '<div class="pos-local-stock-confirm mt-1 flex max-w-full flex-col gap-1.5 rounded-md border-2 border-violet-300 bg-violet-100 px-2.5 py-1.5 text-[11px] font-semibold leading-snug text-violet-950 shadow-sm ring-1 ring-violet-200/80" role="alert"' +
            ' data-cartref="' +
            escapeHtml(ref) +
            '" data-product-code="' +
            escapeHtml(productCode) +
            '" data-qty="' +
            escapeHtml(String(qty)) +
            '" data-local-stock="' +
            escapeHtml(formatLocalStockQty(localStockQty)) +
            '">' +
            '<span class="min-w-0">' +
            escapeHtml(localStockProceedPrompt(localStockQty)) +
            '</span>' +
            '<span class="inline-flex shrink-0 items-center gap-1.5">' +
            '<button type="button" class="pos-local-stock-yes min-w-[2rem] rounded-md bg-violet-700 px-2.5 py-1 text-[11px] font-bold text-white shadow-sm transition hover:bg-violet-800" title="Proceed with this item" aria-label="Proceed yes">Y</button>' +
            '<span class="font-bold text-violet-500" aria-hidden="true">|</span>' +
            '<button type="button" class="pos-local-stock-no min-w-[2rem] rounded-md border border-violet-400 bg-white px-2.5 py-1 text-[11px] font-bold text-violet-900 shadow-sm transition hover:bg-violet-50" title="Remove from cart" aria-label="Proceed no">N</button>' +
            '</span>' +
            '</div>';
        } else if (
          localStockQty != null &&
          localStockShort &&
          ref &&
          isLocalStockConfirmed(ref, qty, localStockQty, row)
        ) {
          html +=
            '<div class="mt-1 inline-flex max-w-full items-center rounded-md border border-violet-200/80 bg-violet-50/90 px-2 py-1 text-[11px] font-semibold tabular-nums text-violet-900 shadow-sm">' +
            escapeHtml(localStockAckLabel(localStockQty)) +
            '</div>';
        }
        html += '<div class="flex flex-wrap items-center gap-2 mt-1">';
        if (ref) {
          var maxAttr =
            maxSell != null && maxSell >= 1
              ? ' max="' + escapeHtml(String(maxSell)) + '" data-max-qty="' + escapeHtml(String(maxSell)) + '"'
              : '';
          var maxHint =
            maxSell != null && maxSell >= 1
              ? '<span class="text-[11px] text-slate-400 tabular-nums">Max ' +
                escapeHtml(String(maxSell)) +
                ' / Order</span>'
              : '';
          html +=
            '<span class="text-[11px] font-bold text-slate-600 tracking-wide">QTY :</span>' +
            '<input type="number" min="1" step="1" class="pos-cart-qty-input w-12 rounded border border-slate-300 bg-white px-1.5 py-1 text-center text-xs font-semibold text-slate-900 outline-none focus:border-orange-500"' +
            maxAttr +
            ' data-cartref="' +
            escapeHtml(ref) +
            '" value="' +
            escapeHtml(String(qty)) +
            '" title="' +
            (maxSell != null && maxSell >= 1 ? escapeHtml('Maximum ' + maxSell + ' per order') : '') +
            '" />' +
            maxHint;
        } else {
          html +=
            '<span class="text-[10px] text-amber-700">Missing cart reference \u2014 cannot update line.</span>';
        }
        html += '</div>';
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
      isAmountGreaterThanZero(totals.giftDeduction) ||
      totals.grandTotal != null;
    if (showSummary) {
      html +=
        '<div class="mt-4 rounded-lg border border-slate-200 bg-white p-3 shadow-sm">' +
        '<p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Summary</p>' +
        '<div class="space-y-0.5">';
      html += moneyRowSummary('Sub total (incl. GST)', totals.subtotal, false);
      var manualLineDisc = sumManualLineDiscountFromCartItems(data || {});
      html += moneyRowSummary(
        'Line Discount',
        isAmountGreaterThanZero(manualLineDisc) ? manualLineDisc : 0,
        false
      );
      if (isAmountGreaterThanZero(totals.customDeduction)) {
        var cdLbl = 'Custom Discount';
        if (posCustomDiscountPersist && posCustomDiscountPersist.mode === 'percent') {
          cdLbl += ' (' + formatPctLabel(posCustomDiscountPersist.value) + ')';
        } else if (posCustomDiscountPersist && posCustomDiscountPersist.mode === 'fixed') {
          cdLbl += ' (fixed ₹)';
        }
        html += moneyRowSummaryNote(
          cdLbl,
          totals.customDeduction,
          totals.cartDiscountAbsorbed ? '(included in line totals)' : '',
          false,
          'pos-cart-summary-remove-custom'
        );
      }
      if (isAmountGreaterThanZero(totals.couponDeduction)) {
        var couponLbl =
          totals.couponDisplayName && String(totals.couponDisplayName).trim() !== ''
            ? 'Coupon (' + String(totals.couponDisplayName).trim() + ')'
            : 'Coupon';
        html += moneyRowSummaryNote(
          couponLbl,
          totals.couponDeduction,
          totals.cartDiscountAbsorbed ? '(included in line totals)' : '',
          false,
          'pos-cart-summary-remove-coupon'
        );
      }
      if (isAmountGreaterThanZero(totals.giftDeduction)) {
        html += moneyRowSummaryNote(
          'Gift Voucher',
          totals.giftDeduction,
          totals.cartDiscountAbsorbed ? '(included in line totals)' : '',
          false,
          ''
        );
      }
      html += moneyRowSummary('GST Total', totals.gstTotal, false);
      html += moneyRowSummary('GRAND Total', totals.grandTotal, true);
      html += '</div></div>';
    }

    html +=
      '<div class="mt-4 rounded-lg border border-slate-200 bg-white p-3 shadow-sm space-y-3">' +
      '<p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Discount</p>' +
      '<div class="space-y-1">' +
      '<div class="flex gap-2 flex-wrap items-stretch">' +
      '<input type="text" class="pos-cart-coupon-input flex-1 min-w-[8rem] rounded border border-slate-300 bg-white px-3 py-2 text-xs outline-none focus:border-orange-500 placeholder:text-slate-400" placeholder="Discount Coupon" />' +
      '<button type="button" class="pos-cart-coupon-apply shrink-0 rounded bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800">Apply</button>' +
      '<button type="button" class="pos-cart-coupon-clear shrink-0 rounded border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Clear</button>' +
      '</div></div>' +
      '<div class="space-y-1 border-t border-slate-100 pt-3">' +
      '<div class="flex flex-wrap items-stretch gap-2">' +
      '<select class="pos-cart-customdisc-mode shrink-0 rounded border border-slate-300 bg-white px-2 py-2 text-xs font-medium text-slate-800 shadow-sm outline-none focus:border-orange-500" aria-label="Discount type">' +
      '<option value="percent">% Off</option>' +
      '<option value="fixed">Fixed (₹)</option>' +
      '</select>' +
      '<input type="number" step="0.01" min="0" class="pos-cart-customdisc-input min-w-[5rem] flex-1 rounded border border-slate-300 bg-white px-3 py-2 text-xs shadow-sm outline-none focus:border-orange-500 placeholder:text-slate-400" placeholder="Amount" />' +
      '<button type="button" class="pos-cart-customdisc-apply shrink-0 rounded bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800">Apply</button>' +
      '<button type="button" class="pos-cart-customdisc-clear inline-flex h-9 w-9 shrink-0 items-center justify-center rounded border border-slate-300 bg-white text-slate-600 shadow-sm transition hover:bg-red-50 hover:border-red-200 hover:text-red-700" title="Remove custom discount" aria-label="Remove custom discount">' +
      '<i class="fas fa-trash-alt text-sm" aria-hidden="true"></i></button>' +
      '</div></div></div>';

    if (items.length > 0) {
      html +=
        '<div class="mt-4">' +
        '<button type="button" class="pos-cart-checkout-btn w-full rounded-lg bg-orange-600 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:bg-orange-700 active:scale-[0.99]">Place order</button>' +
        '</div>';
    }

    html +=
      '<div class="mt-3 flex justify-center">' +
      '<button type="button" class="pos-cart-api-debug-link rounded-full border border-slate-200/90 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700" ' +
      'onclick="event.preventDefault();event.stopPropagation();if(typeof window.openPosCartApiDebugModal===\'function\'){window.openPosCartApiDebugModal();}">' +
      'Review API request / response (proxy + Exotic add)' +
      '</button>' +
      '</div>';

    window.__posCartLastTotals = {
      grandTotal: totals.grandTotal,
      subtotal: totals.subtotal,
      gstTotal: totals.gstTotal,
      couponDeduction: totals.couponDeduction,
      customDeduction: totals.customDeduction,
      giftDeduction: totals.giftDeduction,
      cartDiscountAbsorbed: !!totals.cartDiscountAbsorbed,
      couponDisplayName: totals.couponDisplayName || ''
    };
    window.__posCartLocalStockWarnings = getLocalStockWarnings(data || {});

    panel.innerHTML = html;
    var modeSel = panel.querySelector('.pos-cart-customdisc-mode');
    var inpDisc = panel.querySelector('.pos-cart-customdisc-input');
    if (modeSel && inpDisc) {
      if (posCustomDiscountPersist) {
        modeSel.value = posCustomDiscountPersist.mode === 'percent' ? 'percent' : 'fixed';
        inpDisc.value = String(posCustomDiscountPersist.value);
      }
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

    if (window.__posOpenCheckoutAfterCartLoad) {
      window.__posOpenCheckoutAfterCartLoad = false;
      try {
        history.replaceState(null, '', window.location.pathname + window.location.search);
      } catch (eHash) {
        /* ignore */
      }
      if (typeof window.openPaymentModal === 'function') {
        window.openPaymentModal();
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
          return;
        }
        var cartImgLb = document.getElementById(CART_IMAGE_LIGHTBOX_ID);
        if (cartImgLb && !cartImgLb.classList.contains('hidden')) {
          closePosCartImageLightbox();
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
      if (t.closest && t.closest('.pos-cart-line-adjust')) {
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
          var panel = document.getElementById(PANEL_ID);
          if (!panel || !panel.contains(t)) {
            return;
          }
          var inpM = panel.querySelector('.pos-cart-customdisc-input');
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
        var panelQ = document.getElementById(PANEL_ID);
        if (!panelQ || !panelQ.contains(t)) {
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
      'input',
      function (e) {
        var t = e.target;
        if (!t || !t.matches || !t.matches('.pos-cart-draft-sku')) {
          return;
        }
        var panelIn = document.getElementById(PANEL_ID);
        if (!panelIn || !panelIn.contains(t)) {
          return;
        }
        posCartDraftFetchSuggest(t);
      },
      false
    );

    document.body.addEventListener(
      'mousedown',
      function (e) {
        var draftPick = e.target && e.target.closest ? e.target.closest('.pos-cart-draft-sku-pick') : null;
        if (!draftPick) {
          return;
        }
        var panelMp = document.getElementById(PANEL_ID);
        if (!panelMp || !panelMp.contains(draftPick)) {
          return;
        }
        e.preventDefault();
        var rowPick = posCartDraftRowFromTarget(draftPick);
        posCartDraftOpenProductModal(posCartDraftProductFromPickBtn(draftPick), rowPick);
      },
      false
    );

    document.body.addEventListener(
      'keydown',
      function (e) {
        var t = e.target;
        if (!t || !t.matches || !t.matches('.pos-cart-draft-sku')) {
          return;
        }
        var panelKd = document.getElementById(PANEL_ID);
        if (!panelKd || !panelKd.contains(t)) {
          return;
        }
        var rowKd = posCartDraftRowFromTarget(t);
        var suggestKd = rowKd ? rowKd.querySelector('.pos-cart-draft-suggest') : null;
        var suggestOpen = suggestKd && !suggestKd.classList.contains('hidden');
        var picks = suggestOpen ? suggestKd.querySelectorAll('.pos-cart-draft-sku-pick') : null;

        if (suggestOpen && picks && picks.length && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
          e.preventDefault();
          var curIdx = parseInt(String(suggestKd.getAttribute('data-active-pick-idx') || '0'), 10);
          if (isNaN(curIdx) || curIdx < 0) {
            curIdx = 0;
          }
          var nextIdx = e.key === 'ArrowDown' ? curIdx + 1 : curIdx - 1;
          var nextPick = posCartDraftSetActivePick(suggestKd, nextIdx);
          if (nextPick && typeof nextPick.scrollIntoView === 'function') {
            nextPick.scrollIntoView({ block: 'nearest' });
          }
          return;
        }

        if (e.key === 'Escape') {
          if (suggestOpen) {
            e.preventDefault();
            posCartDraftHideSuggest(suggestKd);
          }
          return;
        }

        if (e.key !== 'Enter' || e.shiftKey) {
          return;
        }

        e.preventDefault();
        if (suggestOpen && picks && picks.length) {
          var pickBtn = posCartDraftActivePick(rowKd);
          if (pickBtn) {
            posCartDraftOpenProductModal(posCartDraftProductFromPickBtn(pickBtn), rowKd);
            return;
          }
        }
        posCartDraftExactLookup(t.value, rowKd);
      },
      false
    );

    document.body.addEventListener(
      'mouseover',
      function (e) {
        var draftPick = e.target && e.target.closest ? e.target.closest('.pos-cart-draft-sku-pick') : null;
        if (!draftPick) {
          return;
        }
        var panel = document.getElementById(PANEL_ID);
        if (!panel || !panel.contains(draftPick)) {
          return;
        }
        var boxEl = draftPick.closest('.pos-cart-draft-suggest');
        if (!boxEl) {
          return;
        }
        var picks = boxEl.querySelectorAll('.pos-cart-draft-sku-pick');
        var idx = Array.prototype.indexOf.call(picks, draftPick);
        if (idx >= 0) {
          posCartDraftSetActivePick(boxEl, idx);
        }
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
        var imgEnlarge =
          e.target && e.target.closest ? e.target.closest('.pos-cart-line-image-enlarge') : null;
        if (imgEnlarge && panel.contains(imgEnlarge)) {
          e.preventDefault();
          e.stopPropagation();
          openPosCartImageLightbox(
            imgEnlarge.getAttribute('data-full-src') || '',
            imgEnlarge.getAttribute('data-image-alt') || 'Product image'
          );
          return;
        }
        var viewBtn = e.target && e.target.closest ? e.target.closest('.pos-cart-view-toggle-btn') : null;
        if (viewBtn && panel.contains(viewBtn)) {
          e.preventDefault();
          var nextView = String(viewBtn.getAttribute('data-view') || '').trim();
          if (nextView === 'card') {
            cartViewMode = 'card';
            try {
              sessionStorage.setItem(CART_VIEW_SS_KEY, cartViewMode);
            } catch (eSetView) {
              /* ignore */
            }
            rerenderCartFromSnapshot();
          }
          return;
        }
        var draftAddRow = e.target && e.target.closest ? e.target.closest('.pos-cart-draft-add-row') : null;
        if (draftAddRow && panel.contains(draftAddRow)) {
          e.preventDefault();
          var draftBody = panel.querySelector('.pos-cart-draft-body');
          if (draftBody) {
            var tmpTb = document.createElement('tbody');
            tmpTb.innerHTML = buildCartDraftRowHtml({ sku: '', qty: '1' }, false);
            var newTr = tmpTb.querySelector('tr');
            if (newTr) {
              draftBody.appendChild(newTr);
            }
          }
          return;
        }
        var draftRemove = e.target && e.target.closest ? e.target.closest('.pos-cart-draft-remove') : null;
        if (draftRemove && panel.contains(draftRemove)) {
          e.preventDefault();
          var rowRm = posCartDraftRowFromTarget(draftRemove);
          var draftBodyRm = panel.querySelector('.pos-cart-draft-body');
          if (rowRm && draftBodyRm && draftBodyRm.querySelectorAll('.pos-cart-draft-row').length > 1) {
            rowRm.remove();
          }
          return;
        }
        var dbgLink = e.target && e.target.closest ? e.target.closest('.pos-cart-api-debug-link') : null;
        if (dbgLink && panel.contains(dbgLink)) {
          openPosCartApiDebugModal();
          return;
        }
        var chkPay = e.target && e.target.closest ? e.target.closest('.pos-cart-checkout-btn') : null;
        if (chkPay && panel.contains(chkPay)) {
          e.preventDefault();
          e.stopPropagation();
          if (typeof window.hasUnconfirmedLocalStockWarnings === 'function' && window.hasUnconfirmedLocalStockWarnings()) {
            toast('Please confirm local stock for cart items (Y or N) before checkout.', 'violet');
            return;
          }
          if (typeof window.openPaymentModal === 'function') {
            window.openPaymentModal();
          } else {
            var checkoutUrl = posRegisterListUrl('checkout');
            if (window.opener && !window.opener.closed) {
              try {
                window.opener.location.href = checkoutUrl;
                window.opener.focus();
                return;
              } catch (eOpener) {
                /* fall through */
              }
            }
            window.location.href = checkoutUrl;
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
        var stockYes = e.target && e.target.closest ? e.target.closest('.pos-local-stock-yes') : null;
        if (stockYes && panel.contains(stockYes)) {
          e.preventDefault();
          e.stopPropagation();
          var wrapY = stockYes.closest('.pos-local-stock-confirm');
          var refY = wrapY ? String(wrapY.getAttribute('data-cartref') || '').trim() : '';
          var qtyY = wrapY ? parseInt(String(wrapY.getAttribute('data-qty') || ''), 10) : NaN;
          var lsY = wrapY ? parseFloat(String(wrapY.getAttribute('data-local-stock') || '')) : NaN;
          if (refY && qtyY >= 1 && !isNaN(lsY)) {
            var rowY = {
              code: wrapY ? wrapY.getAttribute('data-product-code') : '',
              variation: wrapY ? wrapY.getAttribute('data-variation') : ''
            };
            setLocalStockConfirmed(refY, qtyY, lsY, rowY);
            rerenderCartFromSnapshot();
          }
          return;
        }
        var stockNo = e.target && e.target.closest ? e.target.closest('.pos-local-stock-no') : null;
        if (stockNo && panel.contains(stockNo)) {
          e.preventDefault();
          e.stopPropagation();
          var wrapN = stockNo.closest('.pos-local-stock-confirm');
          var refN = wrapN ? String(wrapN.getAttribute('data-cartref') || '').trim() : '';
          if (refN) {
            var codeN = wrapN ? String(wrapN.getAttribute('data-product-code') || '').trim() : '';
            window.handleDeleteItem({ cartref: refN, product_code: codeN });
          }
          return;
        }
        var del = e.target && e.target.closest ? e.target.closest('.pos-cart-delete-btn') : null;
        if (del && panel.contains(del)) {
          e.preventDefault();
          e.stopPropagation();
          var refD = String(del.getAttribute('data-cartref') || '').trim();
          if (refD) {
            var lineDel = del.closest('.pos-cart-table-line') || del.closest('.pos-cart-line-item');
            var codeElDel = lineDel ? lineDel.querySelector('[data-product-code]') : null;
            var codeD = codeElDel
              ? String(codeElDel.getAttribute('data-product-code') || '').trim()
              : lineDel
                ? String(lineDel.getAttribute('data-product-code') || '').trim()
                : '';
            window.handleDeleteItem({ cartref: refD, product_code: codeD });
          }
          return;
        }
        var lineItem = e.target && e.target.closest ? e.target.closest('.pos-cart-line-item') : null;
        if (lineItem && panel.contains(lineItem)) {
          if (e.target.closest && e.target.closest('.pos-cart-line-adjust')) {
            return;
          }
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

  /**
   * Exotic cart can keep custom_reduce after checkout; clear it when the cart is empty
   * and this browser session has not applied a custom discount.
   * @param {Record<string, unknown>} cartData
   * @returns {Promise<boolean>}
   */
  function clearStaleCustomDiscountOnEmptyCart(cartData) {
    if (posCustomDiscountPersist) {
      return Promise.resolve(false);
    }
    if (getCartItems(cartData || {}).length > 0) {
      return Promise.resolve(false);
    }
    var customDeduction = totalsFromRetrieve(cartData || {}).customDeduction;
    if (customDeduction == null || isNaN(Number(customDeduction)) || Number(customDeduction) <= 0.001) {
      return Promise.resolve(false);
    }
    return cartRequest('customdiscount', { query: { custom_reduce: '0' } }).then(function (r2) {
      cartHandleApiMessages(r2);
      return !!(r2 && r2.success);
    });
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
      return clearStaleCustomDiscountOnEmptyCart(data).then(function (didClearStale) {
        if (didClearStale) {
          return refreshCartInternal({ skipPctSync: true });
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
      var parentMsg =
        typeof window.POS_PARENT_ITEM_CART_MSG === 'string'
          ? window.POS_PARENT_ITEM_CART_MSG
          : 'Parent Level Item can not be added to the cart';
      if (
        (typeof window.isParentLevelProduct === 'function' && window.isParentLevelProduct(p)) ||
        String(p.item_level || '').trim().toLowerCase() === 'parent'
      ) {
        if (typeof window.notifyParentItemCartBlocked === 'function') {
          window.notifyParentItemCartBlocked();
        } else {
          toast(parentMsg, 'red');
        }
        return undefined;
      }
      var body = {
        code: String(p.code || '').trim(),
        qty: parseInt(String(p.qty != null ? p.qty : 1), 10) || 1,
        variation: String(p.variation || ''),
        options: String(p.options || ''),
        item_level: String(p.item_level || '').trim(),
        item_code: String(p.item_code || '').trim(),
        size: String(p.size || '').trim(),
        color: String(p.color || '').trim()
      };
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
            var blockMsg = String(r.message || '');
            if (
              blockMsg === parentMsg ||
              blockMsg.toLowerCase().indexOf('parent level item') !== -1
            ) {
              if (typeof window.notifyParentItemCartBlocked === 'function') {
                window.notifyParentItemCartBlocked();
              } else {
                toast(parentMsg, 'red');
              }
              return r;
            }
            openPosCartApiDebugModal();
            return r;
          }
          toast('Added to cart.', 'green');
          if (typeof window.closePosProductModal === 'function') {
            window.closePosProductModal();
          }
          var draftRow = window.__posCartDraftRowAfterAdd;
          if (draftRow) {
            window.__posCartDraftRowAfterAdd = null;
            var draftSkuIn = draftRow.querySelector('.pos-cart-draft-sku');
            if (draftSkuIn) {
              draftSkuIn.value = '';
              draftSkuIn.focus();
            }
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
      var p = payload || {};
      var ref = String(p.cartref || '').trim();
      if (!ref) {
        toast('Invalid delete', 'red');
        return undefined;
      }
      var rowDel = findCartRowByRef(window.__posCartLastRetrieveData, ref);
      if (!rowDel && p.product_code) {
        rowDel = { code: String(p.product_code).trim() };
      }
      clearLocalStockConfirmedForProduct(rowDel, ref);
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

  window.getPosCustomDiscountMetaForCheckout = function () {
    if (!posCustomDiscountPersist || !(posCustomDiscountPersist.value > 0)) {
      return null;
    }
    return {
      mode: posCustomDiscountPersist.mode === 'percent' ? 'percent' : 'fixed',
      value: posCustomDiscountPersist.value
    };
  };

  window.getPosCartTotalsForCheckout = function () {
    return window.__posCartLastTotals || null;
  };

  /** Receipt / checkout discount breakdown (cart + line level). */
  window.getPosReceiptDiscountsForCheckout = function () {
    var d = window.__posCartLastRetrieveData;
    var last = window.__posCartLastTotals || {};
    var lineDisc = d && typeof d === 'object' ? sumManualLineDiscountFromCartItems(d) : 0;
    return {
      couponDeduction:
        last.couponDeduction != null && !isNaN(Number(last.couponDeduction))
          ? Number(last.couponDeduction)
          : 0,
      customDeduction:
        last.customDeduction != null && !isNaN(Number(last.customDeduction))
          ? Number(last.customDeduction)
          : 0,
      giftDeduction:
        last.giftDeduction != null && !isNaN(Number(last.giftDeduction)) ? Number(last.giftDeduction) : 0,
      lineDiscount: lineDisc > 0 ? lineDisc : 0,
      grandTotal:
        last.grandTotal != null && !isNaN(Number(last.grandTotal)) ? Number(last.grandTotal) : 0
    };
  };

  window.getPosLinePricesPayloadForCheckout = function () {
    var d = window.__posCartLastRetrieveData;
    if (!d || typeof d !== 'object') {
      return [];
    }
    return buildPosLinePricesPayload(d, getTotalsForCheckoutAlloc());
  };

  window.hasPosLinePriceOverridesForCheckout = function () {
    var d = window.__posCartLastRetrieveData;
    if (!d || typeof d !== 'object') {
      return false;
    }
    var allocTotals = getTotalsForCheckoutAlloc();
    if (allocTotals && cartDeductionPoolFromTotals(allocTotals) > 0.001) {
      return true;
    }
    if (posCustomDiscountPersist && posCustomDiscountPersist.value > 0) {
      return true;
    }
    return false;
  };

  window.getPosLocalStockWarningsForCheckout = function () {
    var d = window.__posCartLastRetrieveData;
    if (!d || typeof d !== 'object') {
      return [];
    }
    return getLocalStockWarnings(d);
  };

  window.hasUnconfirmedLocalStockWarnings = function () {
    var d = window.__posCartLastRetrieveData;
    if (!d || typeof d !== 'object') {
      return false;
    }
    return getUnconfirmedLocalStockLines(d).length > 0;
  };

  window.formatPosLocalStockWarning = formatLocalStockWarning;

  function initPosCartHooks() {
    bindCartDelegatesOnce();
    ensureCartPanel();
    ensurePosCartImageLightbox();
    window.refreshCart();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPosCartHooks);
  } else {
    initPosCartHooks();
  }
})(window);
