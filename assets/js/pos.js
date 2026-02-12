$(function () {
  let currentPage = 1;
  const perPage = 12;
  let currentCategory = '';

  let isLoading = false;
  let hasMore = true;

  let loadedKeys = new Set();
  let productsByKey = new Map(); // cache product by key

  const $cards = $('#productsCards');
  const $scrollWrapper = $cards.parent();

  // =========================
  // CART PANEL HOOKS (your aside)
  // =========================
  // REQUIRED HTML IDs:
  // #cartItems
  // #cartSubTotal, #cartGST, #cartTotal
  // #couponInput, #applyCouponBtn, #couponMessage
  // #addonSelect
  // #shippingCheckbox (optional; if missing shipping always applied)
  const $cartItems = $('#cartItems');
  const $cartSubTotal = $('#cartSubTotal');
  const $cartGST = $('#cartGST');
  const $cartTotal = $('#cartTotal');

  const $couponInput = $('#couponInput');
  const $applyCouponBtn = $('#applyCouponBtn');
  const $couponMessage = $('#couponMessage');

  const $addonSelect = $('#addonSelect');
  const $shippingCheckbox = $('#shippingCheckbox');

  // --- constants ---
  const GST_RATE = 0.18;
  const SHIPPING_CHARGE = 8265;

  // --- coupon state ---
  // supported coupons: percent/flat
  const COUPONS = {
    SAVE10: { type: 'percent', value: 10 },
    FLAT500: { type: 'flat', value: 500 },
    SAVE5: { type: 'percent', value: 5 }
  };

  let appliedCoupon = null; // {code,type,value} or null
  let addonCharge = 0;

  // --- cart state ---
  // key -> { product, qty }
  const cart = new Map();

  // =========================
  // HELPERS
  // =========================
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

  // treat "", null, undefined, "N/A", 0, "0", "0.00" as empty
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

  // =========================
  // MODAL HELPERS
  // =========================
  const $modal = $('#productModal');
  const $overlay = $('#productModalOverlay');
  const $close = $('#productModalClose');
  const $closeBtn = $('#pmCloseBtn');

  // Modal controls (must exist in modal HTML)
  const $pmAddToCart = $('#pmAddToCart');
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

    const title = (p.title || '').replace(/\s+/g, ' ').trim();
    $('#pmTitle').text(title || 'Product');

    const imgSrc = p.image || 'https://dummyimage.com/500x500/e5e7eb/6b7280&text=No+Image';
    $('#pmImage').attr('src', imgSrc).attr('alt', title || 'Product');

    // badges
    const badges = [];
    if (isMeaningful(p.item_code)) badges.push(`<span class="rounded-md bg-orange-100 px-2 py-1 text-[10px] text-orange-700">Code: ${p.item_code}</span>`);
    if (isMeaningful(p.sku)) badges.push(`<span class="rounded-md bg-blue-100 px-2 py-1 text-[10px] text-blue-700">SKU: ${p.sku}</span>`);
    if (isMeaningful(p.groupname)) badges.push(`<span class="rounded-md bg-gray-100 px-2 py-1 text-[10px] text-gray-700">${p.groupname}</span>`);
    if (isMeaningful(p.stock_qty)) badges.push(`<span class="rounded-md bg-green-100 px-2 py-1 text-[10px] text-green-700">Stock: ${p.stock_qty}</span>`);
    $('#pmBadges').html(badges.join(''));

    // details
    let html = '';

    if (isMeaningful(p.price)) {
      html += addRow('Price', `₹ ${Number(p.price).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    }

    if (isMeaningful(p.cost_price)) {
      html += addRow('Cost Price', `₹ ${Number(p.cost_price).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
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

  // =========================
  // CART UI + CALCULATION
  // =========================
  function cartItemTemplate(p, qty, key) {
    const imgSrc = p.image || 'https://dummyimage.com/200x200/e5e7eb/6b7280&text=No+Image';
    const title = (p.title || '').replace(/\s+/g, ' ').trim();
    const unitPrice = Number(p.price || 0);

    return `
      <div class="cart-item flex gap-3" data-pkey="${key}">
        <img
          src="${imgSrc}"
          class="h-12 w-12 rounded-lg bg-slate-50 object-contain"
          alt="${title.replace(/"/g, '&quot;')}"
        />

        <div class="flex-1 min-w-0">
          <div class="text-[9px] leading-snug line-clamp-2">
            ${title}
          </div>

          <div class="mt-1 flex items-center justify-between">
            <span class="text-orange-600 font-semibold">${formatPrice(unitPrice)}</span>
          </div>

          <div class="mt-2 flex items-center justify-between">
            <div class="flex items-center border rounded-md overflow-hidden">
              <button type="button" class="cart-dec h-6 w-6 text-slate-600">−</button>
              <span class="h-6 w-7 flex items-center justify-center font-semibold">${qty}</span>
              <button type="button" class="cart-inc h-6 w-6 text-slate-600">+</button>
            </div>

            <button type="button" class="cart-remove text-[10px] text-red-600 hover:underline">
              Remove
            </button>
          </div>
        </div>
      </div>
    `;
  }

  function calcSubTotal() {
    let sub = 0;
    cart.forEach(({ product, qty }) => {
      sub += Number(product.price || 0) * Number(qty || 0);
    });
    return sub;
  }

  function calcDiscount(subTotal) {
    if (!appliedCoupon) return 0;

    let d = 0;
    if (appliedCoupon.type === 'percent') {
      d = (subTotal * appliedCoupon.value) / 100;
    } else if (appliedCoupon.type === 'flat') {
      d = appliedCoupon.value;
    }

    if (d > subTotal) d = subTotal;
    return d;
  }

  function calcShipping() {
    // if checkbox not present, assume shipping applies
    if (!$shippingCheckbox.length) return SHIPPING_CHARGE;
    return $shippingCheckbox.is(':checked') ? SHIPPING_CHARGE : 0;
  }

  function updateTotals() {
    const sub = calcSubTotal();
    const discount = calcDiscount(sub);
    const shipping = calcShipping();

    const base = Math.max(0, sub - discount) + Number(addonCharge || 0) + Number(shipping || 0);
    const gst = base * GST_RATE;
    const total = base + gst;

    if ($cartSubTotal.length) $cartSubTotal.text(formatPrice(sub));
    if ($cartGST.length) $cartGST.text(formatPrice(gst));
    if ($cartTotal.length) $cartTotal.text(formatPrice(total));
  }

  function renderCartItem(key) {
    const item = cart.get(key);
    if (!item) return;

    const $existing = $cartItems.find(`.cart-item[data-pkey="${key}"]`);
    const html = cartItemTemplate(item.product, item.qty, key);

    if ($existing.length) $existing.replaceWith(html);
    else $cartItems.append(html);

    updateTotals();
  }

  function removeFromCart(key) {
    cart.delete(key);
    $cartItems.find(`.cart-item[data-pkey="${key}"]`).remove();
    updateTotals();
  }

  function changeQty(key, delta) {
    const item = cart.get(key);
    if (!item) return;

    item.qty = Number(item.qty || 0) + Number(delta || 0);

    if (item.qty <= 0) {
      removeFromCart(key);
      return;
    }
    renderCartItem(key);
  }

  function addToCartByKey(key, qtyToAdd = 1) {
    const p = productsByKey.get(key);
    if (!p) return;

    const addQty = Math.max(1, parseInt(qtyToAdd, 10) || 1);

    const existing = cart.get(key);
    if (existing) existing.qty += addQty;
    else cart.set(key, { product: p, qty: addQty });

    renderCartItem(key);
  }

  // cart events
  $cartItems.on('click', '.cart-inc', function () {
    const key = $(this).closest('.cart-item').data('pkey');
    changeQty(key, +1);
  });

  $cartItems.on('click', '.cart-dec', function () {
    const key = $(this).closest('.cart-item').data('pkey');
    changeQty(key, -1);
  });

  $cartItems.on('click', '.cart-remove', function () {
    const key = $(this).closest('.cart-item').data('pkey');
    removeFromCart(key);
  });

  // modal add to cart
  $pmAddToCart.on('click', function () {
    if (!activeModalKey) return;
    addToCartByKey(activeModalKey, getModalQty());
    closeModal();
  });

  // =========================
  // COUPON + ADDON + SHIPPING EVENTS
  // =========================
  function setCouponMessage(text, ok) {
    if (!$couponMessage.length) return;
    $couponMessage
      .removeClass('text-red-600 text-green-600')
      .addClass(ok ? 'text-green-600' : 'text-red-600')
      .text(text || '');
  }

  if ($applyCouponBtn.length) {
    $applyCouponBtn.on('click', function () {
      const code = ($couponInput.val() || '').trim().toUpperCase();

      if (!code) {
        appliedCoupon = null;
        setCouponMessage('Please enter a coupon code.', false);
        updateTotals();
        return;
      }

      const c = COUPONS[code];
      if (!c) {
        appliedCoupon = null;
        setCouponMessage('Invalid coupon code.', false);
        updateTotals();
        return;
      }

      appliedCoupon = { code, type: c.type, value: c.value };
      setCouponMessage(`Coupon applied: ${code}`, true);
      updateTotals();
    });
  }

  if ($addonSelect.length) {
    $addonSelect.on('change', function () {
      addonCharge = Number($(this).val() || 0);
      updateTotals();
    });
  }

  if ($shippingCheckbox.length) {
    $shippingCheckbox.on('change', function () {
      updateTotals();
    });
  }

  // =========================
  // PRODUCTS RENDERING + FETCH
  // =========================
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

    let appendedCount = 0;

    products.forEach(function (p) {
      const key = getProductKey(p);
      if (loadedKeys.has(key)) return;

      loadedKeys.add(key);
      productsByKey.set(key, p);

      const imgSrc = p.image || 'https://dummyimage.com/200x200/e5e7eb/6b7280&text=No+Image';
      const safeTitle = (p.title || 'Product').replace(/"/g, '&quot;');

      const cardHtml = `
        <div class="product-card cursor-pointer rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition"
             data-pkey="${key}">
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
      appendedCount++;
    });

    if (append && appendedCount === 0) hasMore = false;
  }

  function fetchProducts(page = 1, append = false) {
    if (isLoading) return;
    if (append && !hasMore) return;

    isLoading = true;
    showLoader(true);

    const productCode = $('#searchCode').val();
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
        product_name: productName
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

  // click card => open modal
  $cards.on('click', '.product-card', function () {
    const key = $(this).data('pkey');
    const p = productsByKey.get(key);
    if (!p) return;

    renderProductModal(p, key);
    openModal();
  });

  // category click
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

  // search debounce
  let searchTimeout = null;
  $('#searchCode, #searchName').on('keyup change', function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function () {
      resetAndLoad();
    }, 400);
  });

  // infinite scroll
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

  // initial totals
  updateTotals();

  // initial load
  resetAndLoad();
});
