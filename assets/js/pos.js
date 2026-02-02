$(function () {
  let currentPage = 1;
  const perPage = 12;
  let currentCategory = '';

  let isLoading = false;
  let hasMore = true;

  // Track already rendered products to avoid duplicates
  // Use p.id if available, otherwise item_code
  let loadedKeys = new Set();

  const $cards = $('#productsCards');
  const $scrollWrapper = $cards.parent(); // wrapper is the scrollable div

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

  function renderProducts(products, append = false) {
    if (!append) {
      $cards.empty();
      loadedKeys.clear();
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

      // Skip duplicates
      if (loadedKeys.has(key)) return;
      loadedKeys.add(key);

      const imgSrc = p.image || 'https://dummyimage.com/200x200/e5e7eb/6b7280&text=No+Image';

      const cardHtml = `
        <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition">
          <div class="bg-gray-50 p-2">
            <img src="${imgSrc}" alt="${p.title || 'Product'}"
                 class="mx-auto h-56 lg:h-52 xl:h-48 object-contain" />
          </div>

          <div class="px-3 pb-3 pt-2 text-xs">
            <div class="text-[9.5px] text-gray-800 leading-snug line-clamp-2">
              ${p.title || ''}
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

    // If server returned products but all were duplicates, stop to avoid endless calls
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

        // IMPORTANT: trust the requestedPage (many APIs forget to return current_page correctly)
        currentPage = requestedPage;

        // Prefer total_pages if your API has it; fallback to length check
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
    // scroll top on new filter/search (optional but nice)
    $scrollWrapper.scrollTop(0);
  }

  // Category click
  $('[data-category]').on('click', function () {
    // Reset all buttons
    $('[data-category]')
      .removeClass('bg-orange-600 text-white')
      .addClass('border border-slate-200 bg-white text-slate-700')
      .find('svg')
      .removeClass('text-white')
      .addClass('text-slate-500');

    // Activate clicked button
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

  $('[data-category]').on('click', function () {
    // UI active state
    $('[data-category]')
      .removeClass('bg-orange-600 text-white')
      .addClass('border border-slate-200 bg-white text-slate-700');

    $(this)
      .addClass('bg-orange-600 text-white')
      .removeClass('bg-white text-slate-700');

    // Set selected category
    currentCategory = $(this).data('category') || '';

    // Reset paging + reload
    currentPage = 1;
    hasMore = true;

    fetchProducts(1, false);
  });


  // Infinite scroll (scrollable container)
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
