$(function () {
  let currentPage = 1;
  const perPage = 12;
  let currentCategory = '';

  let isLoading = false;
  let hasMore = true;

  let loadedKeys = new Set();
  let productsByKey = new Map(); // ✅ store full product object by key (no API call)

  const $cards = $('#productsCards');
  const $scrollWrapper = $cards.parent();

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

  // ---------- Modal helpers ----------
  const $modal = $('#productModal');
  const $overlay = $('#productModalOverlay');
  const $close = $('#productModalClose');
  const $closeBtn = $('#pmCloseBtn');

  function openModal() {
    $modal.removeClass('hidden');
    $('body').addClass('overflow-hidden');
  }

  function closeModal() {
    $modal.addClass('hidden');
    $('body').removeClass('overflow-hidden');
  }

  $overlay.on('click', closeModal);
  $close.on('click', closeModal);
  $closeBtn.on('click', closeModal);

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

  function renderProductModal(p) {
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

    // weight
    const wt = isMeaningful(p.product_weight) ? p.product_weight : null;
    const wtu = isMeaningful(p.product_weight_unit) ? p.product_weight_unit : '';
    if (wt) html += addRow('Weight', `${wt} ${wtu}`.trim());

    // dimensions
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
  }

  function renderProducts(products, append = false) {
    if (!append) {
      $cards.empty();
      loadedKeys.clear();
      productsByKey.clear(); // ✅ reset cache on fresh load
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

      // ✅ cache entire product object for modal
      productsByKey.set(key, p);

      const imgSrc = p.image || 'https://dummyimage.com/200x200/e5e7eb/6b7280&text=No+Image';

      const cardHtml = `
        <div class="product-card cursor-pointer rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition"
             data-pkey="${key}">
          <div class="bg-gray-50 p-2">
            <img src="${imgSrc}" alt="${(p.title || 'Product').replace(/"/g, '&quot;')}"
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

    if (append && appendedCount === 0) {
      hasMore = false;
    }
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

  // ✅ CLICK CARD => OPEN MODAL USING CACHED PRODUCT JSON
  $cards.on('click', '.product-card', function () {
    const key = $(this).data('pkey');
    const p = productsByKey.get(key);

    if (!p) return;

    renderProductModal(p);
    openModal();
  });

  // Category click
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

  // Search debounce
  let searchTimeout = null;
  $('#searchCode, #searchName').on('keyup change', function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function () {
      resetAndLoad();
    }, 400);
  });

  // Infinite scroll
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

  // Initial load
  resetAndLoad();
});
