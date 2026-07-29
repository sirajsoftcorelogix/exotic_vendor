<div class="min-h-screen pos-register-page">
<?php
$posCheckoutApiDebug = isset($_SESSION['user']['email'])
    && strtolower(trim((string) $_SESSION['user']['email'])) === 'siraj.php@gmail.com';
?>
  <script>
    document.documentElement.classList.add('pos-page-hide-scrollbars');
  </script>
  <style>
    .pos-register-page {
      font-size: 14px;
      line-height: 1.45;
      color: #334155;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    /* Hide all scrollbars on POS screen (scroll/touch still works) */
    html.pos-page-hide-scrollbars {
      overflow-x: hidden;
    }
    html.pos-page-hide-scrollbars,
    html.pos-page-hide-scrollbars body,
    .pos-register-page,
    .pos-register-page * {
      scrollbar-width: none;
      -ms-overflow-style: none;
    }
    html.pos-page-hide-scrollbars::-webkit-scrollbar,
    html.pos-page-hide-scrollbars body::-webkit-scrollbar,
    .pos-register-page *::-webkit-scrollbar {
      width: 0 !important;
      height: 0 !important;
      background: transparent;
    }

    /* Consistent typography scale across POS */
    .pos-register-page .text-xs {
      font-size: 0.8125rem !important; /* 13px */
      line-height: 1.35 !important;
    }
    .pos-register-page .text-sm {
      font-size: 0.875rem !important; /* 14px */
      line-height: 1.45 !important;
    }
    .pos-register-page .text-base {
      font-size: 0.95rem !important; /* ~15px */
      line-height: 1.5 !important;
    }
    .pos-register-page [class*="text-[8px]"],
    .pos-register-page [class*="text-[9px]"],
    .pos-register-page [class*="text-[10px]"],
    .pos-register-page [class*="text-[11px]"],
    .pos-register-page [class*="text-[12px]"] {
      font-size: 0.8125rem !important;
      line-height: 1.35 !important;
    }

    /* Standardize controls */
    .pos-register-page input,
    .pos-register-page select,
    .pos-register-page textarea,
    .pos-register-page button {
      font-size: 0.875rem;
      line-height: 1.35;
    }
    .pos-register-page label {
      font-size: 0.8125rem;
      line-height: 1.35;
    }
    .pos-register-page h1,
    .pos-register-page h2,
    .pos-register-page h3 {
      letter-spacing: 0.01em;
    }

    /* Confirm Billing & Shipping — fit laptop viewport, scroll body only */
    #addressConfirmModal:not(.hidden) {
      display: flex !important;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }
    #addressConfirmModal .address-confirm-panel {
      max-height: min(90vh, 820px);
      width: 100%;
    }
    #addressConfirmModal .address-confirm-body {
      min-height: 0;
    }
    #addressConfirmModal .address-confirm-body input:not([type="checkbox"]),
    #addressConfirmModal .address-confirm-body select,
    #addressConfirmModal .address-confirm-body .pos-state-select {
      margin-top: 0.25rem;
      padding: 0.5rem 0.75rem;
      font-size: 0.875rem;
      line-height: 1.35;
      border-color: #cbd5e1;
    }
    #addressConfirmModal .address-confirm-body label.block {
      font-size: 0.8125rem;
    }
    #addressConfirmModal .field-req-star,
    #addressConfirmModal .pos-req-star {
      color: #dc2626 !important;
      font-weight: 700;
    }
    #addressConfirmModal .pos-phone-row {
      display: grid;
      grid-template-columns: 4.5rem minmax(0, 1fr);
      gap: 0.5rem;
      margin-top: 0.25rem;
    }
    #addressConfirmModal .pos-phone-code-select {
      width: 100%;
      min-width: 0;
      padding-left: 0.35rem;
      padding-right: 0.25rem;
      font-size: 0.8125rem;
      text-align: center;
    }
    #addressConfirmModal .pos-phone-number-input {
      min-width: 0;
    }
  </style>
  <?php
  $posCountryList = isset($country_list) && is_array($country_list)
      ? $country_list
      : (function_exists('country_array') ? country_array() : ['IN' => 'India']);
  $posCountryIsoByName = [];
  foreach ($posCountryList as $iso => $name) {
      $code = strtoupper(substr(trim((string)$iso), 0, 2));
      if ($code === '') {
          continue;
      }
      $posCountryIsoByName[strtolower(trim((string)$name))] = $code;
  }
  ?>
  <script>
    window.POS_SESSION_CUSTOMER_ID = <?= json_encode(!empty($_SESSION['pos_customer_id']) ? (string)(int)$_SESSION['pos_customer_id'] : '') ?>;
    window.POS_INITIAL_CUSTOMER = <?= json_encode(isset($selected_customer) ? $selected_customer : null, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
    window.POS_HIGH_VALUE_TRANSACTION_LIMIT = <?= json_encode((float)($high_value_transaction_limit ?? 200000.00)) ?>;
    window.POS_COUNTRY_ISO_BY_NAME = <?= json_encode($posCountryIsoByName, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
    window.POS_INDIA_STATES = <?= json_encode($pos_india_states ?? [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
    window.POS_COUNTRY_STATES = <?= json_encode($pos_country_states ?? ['IN' => ($pos_india_states ?? [])], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
    window.POS_COUNTRY_PHONE_CODES = <?= json_encode($pos_country_phone_codes ?? ['IN' => '91'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
    window.POS_DEFAULT_STATE = "Delhi";
    window.POS_STORE_PINCODE = <?= json_encode(trim((string)($pos_store_pincode ?? '')), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
    window.POS_ADDRESS_API_DEFAULTS = {
      confirm_phone: "8031404444",
      confirm_address1: "dummy Address",
      confirm_city: "Delhi",
      confirm_state: "Delhi"
    };
  </script>
  <!-- ===== TOP BAR ===== -->
  <header class="border-b bg-white">
    <div class="mx-auto flex max-w-[1500px] items-center gap-3 px-4 py-3">

      <!-- Search -->
      <div class="flex w-full max-w-xl items-start gap-2">
        <div class="relative min-w-0 flex-1">
          <textarea
            class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm focus:border-orange-500 outline-none resize-none min-h-[2.5rem] max-h-24 leading-snug"
            placeholder="Search by name or SKU — paste multiple SKUs separated by comma or new line"
            id="searchName"
            rows="2"
            autocomplete="off"
            aria-autocomplete="list"
            aria-controls="skuSuggest"
            aria-expanded="false"></textarea>
          <div
            id="skuSuggest"
            class="absolute left-0 right-0 top-full z-[9999] mt-1 hidden max-h-72 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg">
          </div>
          <p id="posSkuSearchError" class="hidden mt-2 text-xs font-medium text-red-600"></p>
          <p class="mt-1 text-[10px] text-slate-500 leading-snug">Paste from Excel — one SKU per row, or comma-separated.</p>
        </div>
        <div class="flex shrink-0 flex-col gap-2 pt-0.5">
          <button
            type="button"
            id="posSearchBtn"
            class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 transition whitespace-nowrap">
            <i class="fas fa-search text-xs" aria-hidden="true"></i>
            Search
          </button>
          <button
            type="button"
            id="posSearchClearBtn"
            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition whitespace-nowrap">
            <i class="fas fa-times text-xs" aria-hidden="true"></i>
            Clear
          </button>
        </div>
      </div>

      <!-- Right -->
      <div class="ml-auto flex items-center gap-3">

        <!-- Sold Order Button -->
        <button class="rounded-xl bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700">
          Sold Order
        </button>
        <a href="?page=pos_register&action=stock-report" class="rounded-xl border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-700 hover:bg-orange-100">
          Stock Report
        </a>

        <!-- Store / Profile -->
        <div class="flex items-center gap-2 border rounded-xl px-3 py-2">
          <div class="h-8 w-8 rounded-full bg-slate-300"></div>
          <div class="text-xs">
            <div class="font-semibold"> <?= $warehouse_name ?? 'No Warehouse'; ?></div>
            <div class="text-slate-500">Sales Terminal</div>
          </div>
        </div>

      </div>
    </div>
  </header>

  <!-- ===== CONTENT GRID ===== -->
  <main class="mx-auto max-w-[1500px] grid grid-cols-12 gap-5 px-4 py-5 items-start">

    <!-- ===== MAIN COLUMN ===== -->
    <section class="col-span-12 lg:col-span-9 space-y-5">

      <!-- Sales cards -->
      <!-- Products -->
      <div class="rounded-2xl bg-white border p-4">
        <h2 class="font-semibold text-sm mb-3">Products</h2>
        <div class="mt-3 flex flex-wrap items-center gap-3">
          <!-- All Products -->
          <?php $isFirst = true; ?>
          <?php foreach ($categories as $key => $cat): ?>
            <button
              data-category="<?= htmlspecialchars($key) ?>"
              class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold
                          <?= $isFirst
                            ? 'bg-orange-600 text-white'
                            : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                          ?>">

              <?= $cat['icon'] ?>
              <?= htmlspecialchars($cat['label']) ?>
            </button>
            <?php $isFirst = false; ?>
          <?php endforeach; ?>
        </div>
        <div class="flex flex-wrap items-center gap-3 mt-3">

          <!-- Sort -->
          <select id="sortBy" class="border rounded-lg px-3 py-2 text-xs">
            <option value="">Sort By</option>
            <option value="price_low_high">Price Low → High</option>
            <option value="price_high_low">Price High → Low</option>
            <option value="name_asc">Name A → Z</option>
            <option value="name_desc">Name Z → A</option>
            <!-- <option value="stock_high_low">Stock High → Low</option> -->
          </select>

          <!-- Price -->
          <input type="number" id="minPrice" placeholder="Min ₹"
            class="border rounded-lg px-3 py-2 text-xs w-24">

          <input type="number" id="maxPrice" placeholder="Max ₹"
            class="border rounded-lg px-3 py-2 text-xs w-24">

          <!-- Stock -->
          <!-- <select id="stockFilter" class="border rounded-lg px-3 py-2 text-xs">
            <option value="">All Stock</option>
            <option value="in_stock">In Stock</option>
            <option value="out_stock">Out of Stock</option>
          </select> -->

          <!-- APPLY BUTTON -->
          <button id="applyFilterBtn"
            class="bg-orange-600 text-white px-4 py-2 text-xs rounded-lg hover:bg-orange-700">
            Apply
          </button>

          <!-- RESET BUTTON -->
          <button id="resetFilterBtn"
            class="bg-gray-200 text-gray-700 px-4 py-2 text-xs rounded-lg hover:bg-gray-300">
            Reset
          </button>

        </div>
        <!-- Product Card -->
        <div id="productsListContainer" class="mt-3">
          <div
            class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
            id="productsCards">
          </div>
          <div id="productsPagination" class="mt-4 flex items-center justify-between border-t border-slate-200 pt-3">
            <button
              type="button"
              id="productsPagePrev"
              class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
              Reset
            </button>
            <span id="productsPageInfo" class="text-sm text-slate-600">Page 1 of 1</span>
            <button
              type="button"
              id="productsPageNext"
              class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
              Load More
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Checkout / Exotic cart removed — rebuild in progress -->
    <?php $cart = []; ?>

    <aside
      class="col-span-12 lg:col-span-3 flex flex-col lg:sticky lg:top-4 lg:self-start"
      data-pos-cart-sidebar="1">
      <div class="px-4 py-3 border-b shrink-0">

        <label class="text-sm text-gray-500">Customer <span class="text-red-600">*</span></label>

        <div class="flex gap-2 mt-1">

          <select id="customerSelect"
            name="customer_id"
            class="w-full border rounded-lg px-3 py-2.5 text-base"
            aria-label="Search customer">
          </select>

          <button onclick="openCustomerModal()"
            class="bg-orange-600 text-white px-3 py-2 rounded-lg text-base hover:bg-orange-700">
            +
          </button>


        </div>

      </div>
      <div
        class="flex flex-col rounded-2xl bg-white border shadow-sm overflow-hidden mt-2 lg:mt-0"
        data-pos-cart-scroll="1">
        <div class="px-4 py-3 border-b shrink-0 flex items-center justify-between gap-2">
          <div id="selectedCustomerNameCart" class="text-base font-semibold text-center text-slate-800 flex-1">Walk-in Customer</div>
          <a href="?page=pos_register&action=cart"
            class="shrink-0 inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[11px] font-semibold text-orange-800 hover:bg-orange-100 transition"
            title="Open full cart page">
            <i class="fas fa-external-link-alt text-[10px]" aria-hidden="true"></i>
            Cart
          </a>
        </div>
        <div id="selectedCustomerPhoneCart" class="text-sm text-slate-500 text-center px-4 pb-2 border-b shrink-0">-</div>

        <div class="pos-cart-panel-inner px-3 py-2">
          <div class="px-1 py-4 space-y-3 text-sm text-slate-600">
            <p class="font-semibold text-slate-800">Cart</p>
            <p class="text-xs text-slate-500">Loading cart from Exotic… If this message stays visible, refresh the page or open the browser console for errors.</p>
          </div>
        </div>
      </div>
    </aside>
  </main>
<?php include __DIR__ . '/partials/pos_modals_and_checkout.php'; ?>
</div>
