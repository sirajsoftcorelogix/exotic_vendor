$(function () {
    let currentPage     = 1;
    const perPage       = 12;   // initial 12 products
    let currentCategory = '';

    function formatPrice(price) {
      const p = parseFloat(price || 0);
      return '₹ ' + p.toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    }

    function renderProducts(products) {
      const $container = $('#productsCards');
      $container.empty();

      if (!products || products.length === 0) {
        $container.append(
          '<div class="col-span-4 text-center text-xs text-gray-500 py-4">No products found.</div>'
        );
        return;
      }

      products.forEach(function (p) {
        const imgSrc = p.image_url || 'https://via.placeholder.com/200x200?text=No+Image';
        const cardHtml = `
          <div
            class="
              rounded-2xl
              border border-gray-200
              bg-white
              overflow-hidden
              shadow-sm
              hover:shadow-md
              transition
            "
          >
            <!-- Image -->
            <div class="bg-gray-50 p-2">
              <img
                src="${imgSrc}"
                alt="${p.product_name || 'Product image'}"
                class="mx-auto h-56 lg:h-52 xl:h-48 object-contain"
              />
            </div>

            <!-- Content -->
            <div class="px-3 pb-3 pt-2 text-xs">
              <!-- Title -->
              <div class="text-[9.5px] text-gray-800 leading-snug line-clamp-2">
                ${p.product_name || ''}
              </div>

              <!-- Bottom row -->
              <div class="mt-2 flex flex-col gap-1">
                <!-- SKU + Stock -->
                <div class="flex items-center gap-1 whitespace-nowrap">
                  <div class="flex items-center gap-1 whitespace-nowrap">
                    <span class="rounded-md bg-orange-100 px-1.5 py-0.5 text-[9px] font-small text-orange-700">
                      ${p.product_code || ''}
                    </span>
                    <span class="rounded-md bg-green-100 px-1.5 py-0.5 text-[9px] font-small text-green-700">
                      Stock : ${p.stock_qty != null ? p.stock_qty : '-'}
                    </span>
                    <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[9px] font-small text-gray-700">
                      ${formatPrice(p.price)}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        `;
        $container.append(cardHtml);
      });
    }

    function fetchProducts(page = 1) {
      const productCode = $('#searchCode').val();
      const productName = $('#searchName').val();

      $.ajax({
        url: '?page=pos_register&action=products-ajax', // adjust route if needed
        type: 'GET',
        dataType: 'json',
        data: {
          page_no: page,
          per_page: perPage,
          category: currentCategory,
          product_code: productCode,
          product_name: productName
        },
        success: function (res) {
          currentPage = res.current_page || 1;
          renderProducts(res.data || []);
          // you can also use res.total to build pagination if needed
        },
        error: function (xhr, status, err) {
          console.error('Error loading products', err);
        }
      });
    }

    // Category filter
    $('[data-category]').on('click', function () {
      $('[data-category]')
        .removeClass('bg-orange-600 text-white')
        .addClass('border border-slate-200 bg-white text-slate-700');

      $(this)
        .addClass('bg-orange-600 text-white')
        .removeClass('bg-white text-slate-700');

      currentCategory = $(this).data('category') || '';
      fetchProducts(1);
    });

    // Search filters (code + name) with small debounce
    let searchTimeout = null;
    $('#searchCode, #searchName').on('keyup change', function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(function () {
        fetchProducts(1);
      }, 400);
    });

    // Initial load
    fetchProducts(1);
  });
