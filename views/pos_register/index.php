<div class="min-h-screen pos-register-page">
<?php
$posCheckoutApiDebug = isset($_SESSION['user']['email'])
    && strtolower(trim((string) $_SESSION['user']['email'])) === 'siraj.php@gmail.com';

$customerLabel = 'Walk-in Customer';
$customerSubtext = '-';
$customerResidenceSubtext = '-';
if (!empty($selected_customer) && is_array($selected_customer)) {
    $customerLabel = trim((string)($selected_customer['name'] ?? '')) ?: 'Walk-in Customer';
    $phone = trim((string)($selected_customer['phone'] ?? ''));
    $email = trim((string)($selected_customer['email'] ?? ''));
    $customerSubtext = $phone !== '' ? $phone : ($email !== '' ? $email : '-');
    $customerResidenceSubtext = trim((string)($selected_customer['residence_text'] ?? '-')) ?: '-';
}
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
    window.POS_COUNTRY_LIST = <?= json_encode($posCountryList, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
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
    window.POS_FOLLOW_UP = <?= json_encode($pos_follow_up ?? null, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
    window.POS_FOLLOW_UP_SEED = <?= !empty($pos_follow_up_seed) ? 'true' : 'false' ?>;
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
        <div class="px-4 py-3 border-b shrink-0">
          <div id="selectedCustomerNameCart" onclick="editSelectedCustomer()" class="text-base font-semibold text-center text-slate-800 cursor-pointer hover:text-orange-600 hover:underline" title="Click to edit customer details"><?= htmlspecialchars($customerLabel) ?></div>
          <div id="selectedCustomerPhoneCart" class="text-sm text-slate-500 text-center"><?= htmlspecialchars($customerSubtext) ?></div>
          <div id="selectedCustomerResidenceCart" class="text-xs text-slate-500 text-center mt-0.5"><?= htmlspecialchars($customerResidenceSubtext) ?></div>
          <div id="posCurrencyToggleContainer" class="mt-2.5 flex justify-center hidden">
            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-0.5 text-xs">
              <button type="button" id="posCurrencyBtnCustomer" onclick="window.setPosCurrencyMode('CUSTOMER')" class="px-3 py-1 rounded-md font-medium transition bg-white text-orange-600 shadow-sm">
                <span id="posCurrencyCustomerLabel">USD ($)</span>
              </button>
              <button type="button" id="posCurrencyBtnINR" onclick="window.setPosCurrencyMode('INR')" class="px-3 py-1 rounded-md font-medium transition text-slate-600 hover:text-slate-900">
                INR (₹)
              </button>
            </div>
          </div>
        </div>

        <div class="pos-cart-panel-inner px-3 py-2">
          <div class="px-1 py-4 space-y-3 text-sm text-slate-600">
            <p class="font-semibold text-slate-800">Cart</p>
            <p class="text-xs text-slate-500">Loading cart from Exotic… If this message stays visible, refresh the page or open the browser console for errors.</p>
          </div>
        </div>
      </div>
    </aside>
<?php require __DIR__ . '/partials/product_modal.php'; ?>
<?php require __DIR__ . '/partials/customer_modal.php'; ?>

<!-- PAYMENT MODAL (POS checkout — wired to Exotic /order/create + pos_payments) -->
<div id="paymentModal" class="fixed inset-0 z-[9999] hidden">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closePaymentModal()"></div>
  <div class="relative mx-auto mt-12 w-[95%] max-w-2xl rounded-2xl bg-white shadow-2xl flex flex-col max-h-[90vh]">
    <div class="flex items-center justify-between border-b px-5 py-3 shrink-0">
      <h2 class="text-base font-semibold text-slate-800">Checkout &amp; payment</h2>
      <button type="button" onclick="closePaymentModal()" class="text-slate-400 hover:text-slate-700 text-xl leading-none" aria-label="Close">✕</button>
    </div>
    <div class="overflow-y-auto p-5 space-y-4 text-sm">
      <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-xs text-slate-600">
        Order total from the live cart (incl. discounts). Add one or more payment lines, then <strong>Place order</strong> to confirm addresses.
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-slate-500">Payment stage <span class="text-red-600">*</span></label>
          <select id="payment_stage" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" required>
            <option value="final">Final</option>
            <option value="partial">Partial</option>
            <option value="advance">Advance</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-slate-500">Payment date <span class="text-red-600">*</span></label>
          <input type="date" id="payment_date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" title="Today or earlier only" required>
        </div>
      </div>

      <div class="rounded-xl border border-orange-200 bg-gradient-to-r from-orange-50 to-amber-50 p-4">
        <div class="grid grid-cols-3 gap-3 text-center sm:text-left">
          <div>
            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Order total</div>
            <div id="payment_summary_order" class="mt-0.5 text-lg font-bold text-slate-900 tabular-nums">₹ 0.00</div>
          </div>
          <div>
            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Collecting now</div>
            <div id="payment_summary_paid" class="mt-0.5 text-lg font-bold text-orange-700 tabular-nums">₹ 0.00</div>
          </div>
          <div>
            <div id="payment_summary_balance_label" class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Balance</div>
            <div id="payment_summary_balance" class="mt-0.5 text-lg font-bold text-emerald-700 tabular-nums">₹ 0.00</div>
          </div>
        </div>
        <p id="payment_summary_hint" class="mt-2 hidden text-[11px] text-slate-600"></p>
      </div>

      <div class="rounded-xl border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-4 py-2.5">
          <div>
            <h3 class="text-sm font-semibold text-slate-800">Payment split</h3>
            <p class="text-[11px] text-slate-500">Each row is saved as a separate payment entry (same receipt)</p>
          </div>
          <button type="button" id="payment_split_add_btn" class="inline-flex items-center gap-1.5 rounded-lg border border-orange-300 bg-white px-3 py-1.5 text-xs font-semibold text-orange-700 hover:bg-orange-50 shadow-sm">
            <span class="text-base leading-none">+</span> Add mode
          </button>
        </div>
        <div class="hidden sm:grid sm:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)_minmax(0,1.2fr)_2.5rem] gap-2 px-4 py-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500 bg-white border-b border-slate-100">
          <span>Mode</span>
          <span>Amount (₹)</span>
          <span>Transaction / ref</span>
          <span></span>
        </div>
        <div id="payment_split_rows" class="divide-y divide-slate-100 bg-white"></div>
        <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-4 py-2.5 text-xs">
          <span class="text-slate-600"><span id="payment_split_count">0</span> payment line(s)</span>
          <span class="font-semibold text-slate-800">Split total: <span id="payment_split_total" class="text-orange-700 tabular-nums">₹ 0.00</span></span>
        </div>
      </div>
      <div id="payment_split_validation" class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 pr-8 text-xs text-red-700 relative">
        <button type="button" id="payment_split_validation_dismiss" class="absolute top-1.5 right-2 text-red-500 hover:text-red-800 leading-none" aria-label="Dismiss error">✕</button>
        <span id="payment_split_validation_text"></span>
      </div>
      <div id="posCheckoutErrorBanner" class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 pr-8 text-xs text-red-700 relative">
        <button type="button" id="posCheckoutErrorBannerDismiss" class="absolute top-1.5 right-2 text-red-500 hover:text-red-800 leading-none" aria-label="Dismiss error">✕</button>
        <span id="posCheckoutErrorBannerText"></span>
      </div>

      <!-- Legacy single-payment fields kept for scripts that read totals; updated by split UI -->
      <input type="hidden" id="payment_amount" value="">
      <input type="hidden" id="payment_mode" value="cash">
      <input type="hidden" id="transaction_id" value="">
      <div id="customInvoiceNumberWrap" class="hidden rounded-xl border border-emerald-100 bg-emerald-50/60 p-3">
        <label class="text-xs font-medium text-emerald-900">Override invoice number (optional)</label>
        <input type="text" id="custom_invoice_number" maxlength="50" class="mt-1 w-full rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm" placeholder="Auto-generated if left blank">
      </div>
      <div>
        <label class="text-xs text-slate-500">Note (optional)</label>
        <textarea id="payment_note" rows="2" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2"></textarea>
      </div>
      <?php if ($posCheckoutApiDebug): ?>
      <div id="paymentModalOrderApiPanel" class="hidden rounded-lg border border-slate-200 bg-slate-900 p-3">
        <div class="flex items-center justify-between gap-2 mb-2">
          <span class="text-xs font-semibold text-white">Last order-create API</span>
          <button type="button" id="paymentModalOrderApiFullBtn" class="text-[11px] text-orange-300 hover:text-white">Open in debug</button>
        </div>
        <pre id="paymentModalOrderApiPre" class="max-h-40 overflow-auto text-[10px] leading-snug text-slate-100 whitespace-pre-wrap break-words"></pre>
      </div>
      <?php endif; ?>
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-3 bg-slate-50 rounded-b-2xl shrink-0">
      <button type="button" onclick="closePaymentModal()" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancel</button>
      <button type="button" id="placeOrderBtn" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Place order</button>
    </div>
  </div>
</div>

<template id="payment_split_row_template">
  <div class="payment-split-row px-4 py-3 sm:grid sm:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)_minmax(0,1.2fr)_2.5rem] sm:gap-2 sm:items-start space-y-2 sm:space-y-0">
    <div>
      <label class="sm:hidden text-[10px] font-semibold text-slate-500 uppercase">Mode</label>
      <select class="payment-split-mode mt-0.5 sm:mt-0 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></select>
    </div>
    <div>
      <label class="sm:hidden text-[10px] font-semibold text-slate-500 uppercase">Amount (₹)</label>
      <input type="number" step="0.01" min="0" class="payment-split-amount mt-0.5 sm:mt-0 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm tabular-nums" placeholder="0.00" />
    </div>
    <div>
      <label class="sm:hidden text-[10px] font-semibold text-slate-500 uppercase">Transaction / ref</label>
      <input type="text" class="payment-split-txn mt-0.5 sm:mt-0 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Optional" />
      <p class="payment-split-txn-hint hidden mt-1 text-[10px] text-amber-700">Required for Razorpay / Cheque</p>
    </div>
    <div class="flex sm:justify-center sm:pt-2">
      <button type="button" class="payment-split-remove rounded-lg border border-red-200 bg-red-50 px-2 py-1.5 text-red-600 hover:bg-red-100 text-sm" title="Remove line">✕</button>
    </div>
  </div>
</template>

<!-- ADDRESS CONFIRMATION MODAL -->
<div id="addressConfirmModal" class="fixed inset-0 z-[10000] hidden">
  <div class="absolute inset-0 bg-black/40" onclick="closeAddressConfirmModal()"></div>
  <div class="address-confirm-panel relative mx-auto flex w-[96%] max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
    <div class="flex shrink-0 items-center justify-between border-b px-5 py-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-800">Confirm Billing &amp; Shipping Details</h2>
        <p class="mt-0.5 text-xs text-slate-500">Required: First name, Last name and State. Select phone country code (default +91 India) and enter the number.</p>
      </div>
      <button type="button" onclick="closeAddressConfirmModal()" class="text-lg leading-none text-gray-500 hover:text-gray-800" aria-label="Close">✕</button>
    </div>
    <div class="address-confirm-body flex-1 overflow-y-auto overscroll-contain">
    <div id="addressConfirmValidationSummary" class="mx-5 mt-3 hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 pr-8 text-sm text-red-700 relative">
      <button type="button" id="addressConfirmValidationDismiss" class="absolute top-2 right-2 text-red-500 hover:text-red-800 leading-none" aria-label="Dismiss error">✕</button>
      <span id="addressConfirmValidationSummaryText"></span>
    </div>
    <div id="highValueComplianceBanner" class="mx-5 mt-3 hidden rounded-lg border border-amber-300 bg-amber-100 px-3 py-2 text-sm font-semibold text-amber-900">High Value Transaction – Compliance Required</div>
    <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
      <div class="space-y-3">
        <h3 class="text-sm font-semibold text-slate-800">Billing Information</h3>
        <div class="grid grid-cols-2 gap-3">
          <label class="block text-xs font-medium text-slate-600">First Name <span class="field-req-star text-red-600">*</span><input id="confirm_first_name" class="w-full rounded border" placeholder="First Name"></label>
          <label class="block text-xs font-medium text-slate-600">Last Name <span class="field-req-star text-red-600">*</span><input id="confirm_last_name" class="w-full rounded border" placeholder="Last Name"></label>
        </div>
        <label class="block text-xs font-medium text-slate-600">Email<input id="confirm_email" type="email" class="w-full rounded border" placeholder="Email"></label>
        <label class="block text-xs font-medium text-slate-600">Phone <span class="field-req-star text-red-600">*</span>
          <div class="pos-phone-row">
            <select id="confirm_phone_code" class="pos-phone-code-select rounded border bg-white" aria-label="Billing phone country code">
              <?php
              $selected_phone_iso = 'IN';
              include __DIR__ . '/partials/phone_code_options.php';
              ?>
            </select>
            <input id="confirm_phone" class="pos-phone-number-input w-full rounded border" placeholder="Phone number" inputmode="tel" autocomplete="tel-national">
          </div>
        </label>
        <label class="block text-xs font-medium text-slate-600">Address 1<input id="confirm_address1" class="w-full rounded border" placeholder="Address 1"></label>
        <label class="block text-xs font-medium text-slate-600">Address 2<input id="confirm_address2" class="w-full rounded border" placeholder="Address 2"></label>
        <div class="grid grid-cols-2 gap-3">
          <label class="block text-xs font-medium text-slate-600">City<input id="confirm_city" class="w-full rounded border" placeholder="City"></label>
          <label class="block text-xs font-medium text-slate-600">State <span class="field-req-star text-red-600">*</span>
            <input id="confirm_state" type="text" class="pos-state-input w-full rounded border bg-white" placeholder="State" autocomplete="address-level1">
            <select id="confirm_state_select" class="pos-state-select hidden w-full rounded border bg-white"></select>
          </label>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <label class="block text-xs font-medium text-slate-600">ZIP / Pincode <span class="field-req-star text-red-600">*</span><input id="confirm_zip" class="w-full rounded border" placeholder="ZIP / Pincode"></label>
          <label class="block text-xs font-medium text-slate-600">Country
            <select id="confirm_country" class="w-full rounded border bg-white">
              <?php
              $selected_iso = 'IN';
              include __DIR__ . '/partials/iso_country_options.php';
              ?>
            </select>
          </label>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <label class="block text-xs font-medium text-slate-600">GSTIN<input id="confirm_gstin" class="w-full rounded border uppercase" placeholder="GSTIN (optional)" maxlength="15"></label>
          <label class="block text-xs font-medium text-slate-600">Trade Name<input id="confirm_trade_name" class="w-full rounded border" placeholder="Trade Name (optional)"></label>
        </div>
        <div id="highValueCompliancePanel" class="hidden rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
          <div class="mb-2 font-semibold text-amber-950">High Value Transaction – Compliance Required</div>
          <p class="mb-3 text-[11px] leading-snug text-amber-800">Additional details are required for final order completion. GSTIN B2B invoices derive PAN automatically.</p>
          <label class="block font-medium">Customer residency <span class="field-req-star text-red-600">*</span>
            <select id="customer_residency_status" class="mt-1 w-full rounded border border-amber-200 bg-white px-3 py-2 text-sm">
              <option value="INDIAN_RESIDENT">Indian Resident</option>
              <option value="NRI">NRI</option>
              <option value="FOREIGN_NATIONAL">Foreign National</option>
            </select>
          </label>
          <div id="panComplianceWrap" class="mt-3">
            <label class="block font-medium">PAN <span id="panRequiredStar" class="field-req-star text-red-600">*</span>
              <input id="customer_pan" maxlength="10" class="mt-1 w-full rounded border border-amber-200 bg-white px-3 py-2 text-sm uppercase" placeholder="ABCDE1234F">
            </label>
            <p id="panComplianceHint" class="mt-1 text-[11px] text-amber-700">PAN is required unless GSTIN is entered.</p>
          </div>
          <div class="mt-3">
            <label class="block font-medium">Aadhaar
              <input id="customer_aadhaar" maxlength="14" class="mt-1 w-full rounded border border-amber-200 bg-white px-3 py-2 text-sm" placeholder="Optional, 12 digits">
            </label>
          </div>
          <div id="passportComplianceWrap" class="mt-3 hidden">
            <label class="block font-medium">Passport Number <span id="passportRequiredStar" class="field-req-star text-red-600">*</span>
              <input id="passport_number" class="mt-1 w-full rounded border border-amber-200 bg-white px-3 py-2 text-sm uppercase" placeholder="Passport number">
            </label>
          </div>
          <div id="countryResidenceWrap" class="mt-3 hidden">
            <label class="block font-medium">Country of Residence <span id="countryRequiredStar" class="field-req-star text-red-600">*</span>
              <select id="country_of_residence" class="mt-1 w-full rounded border border-amber-200 bg-white px-3 py-2 text-sm">
                <option value="">Select Country of Residence</option>
                <?php
                  $selected_iso = '';
                  include __DIR__ . '/partials/iso_country_options.php';
                ?>
              </select>
            </label>
          </div>
          <p id="complianceInlineError" class="mt-2 hidden text-[11px] font-medium text-red-700"></p>
        </div>
      </div>
      <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h3 class="text-sm font-semibold text-slate-800">Shipping Information</h3>
          <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-medium text-slate-600">
            <input type="checkbox" id="confirm_shipping_same_as_billing" class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
            Same as billing
          </label>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <label class="block text-xs font-medium text-slate-600">First Name<input id="confirm_sfirst_name" class="w-full rounded border" placeholder="First Name"></label>
          <label class="block text-xs font-medium text-slate-600">Last Name<input id="confirm_slast_name" class="w-full rounded border" placeholder="Last Name"></label>
        </div>
        <label class="block text-xs font-medium text-slate-600">Phone
          <div class="pos-phone-row">
            <select id="confirm_sphone_code" class="pos-phone-code-select rounded border bg-white" aria-label="Shipping phone country code">
              <?php
              $selected_phone_iso = 'IN';
              include __DIR__ . '/partials/phone_code_options.php';
              ?>
            </select>
            <input id="confirm_sphone" class="pos-phone-number-input w-full rounded border" placeholder="Phone number" inputmode="tel" autocomplete="tel-national">
          </div>
        </label>
        <label class="block text-xs font-medium text-slate-600">Address 1<input id="confirm_saddress1" class="w-full rounded border" placeholder="Address 1"></label>
        <label class="block text-xs font-medium text-slate-600">Address 2<input id="confirm_saddress2" class="w-full rounded border" placeholder="Address 2"></label>
        <div class="grid grid-cols-2 gap-3">
          <label class="block text-xs font-medium text-slate-600">City<input id="confirm_scity" class="w-full rounded border" placeholder="City"></label>
          <label class="block text-xs font-medium text-slate-600">State
            <input id="confirm_sstate" type="text" class="pos-state-input w-full rounded border bg-white" placeholder="State" autocomplete="address-level1">
            <select id="confirm_sstate_select" class="pos-state-select hidden w-full rounded border bg-white"></select>
          </label>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <label class="block text-xs font-medium text-slate-600">ZIP / Pincode<input id="confirm_szip" class="w-full rounded border" placeholder="ZIP / Pincode"></label>
          <label class="block text-xs font-medium text-slate-600">Country
            <select id="confirm_scountry" class="w-full rounded border bg-white">
              <?php
              $selected_iso = 'IN';
              include __DIR__ . '/partials/iso_country_options.php';
              ?>
            </select>
          </label>
        </div>
        <label class="block text-xs font-medium text-slate-600">GSTIN<input id="confirm_sgstin" class="w-full rounded border uppercase" placeholder="GSTIN (optional)" maxlength="15"></label>
      </div>
    </div>
    </div>
    <div class="flex shrink-0 justify-end gap-3 border-t bg-slate-50 px-5 py-3">
      <button type="button" onclick="closeAddressConfirmModal()" class="rounded-lg bg-gray-200 px-5 py-2 text-sm text-gray-700 hover:bg-gray-300">Cancel</button>
      <button type="button" id="confirmAddressSubmitBtn" class="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700">
        Confirm &amp; Submit Order
      </button>
    </div>
  </div>
</div>

<!-- DELIVERY STATUS MODAL (last step before order submit) -->
<div id="deliveryStatusModal" class="fixed inset-0 z-[10001] hidden">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
  <div class="relative mx-auto mt-[12vh] w-[95%] max-w-lg rounded-2xl bg-white shadow-2xl max-h-[80vh] overflow-y-auto flex flex-col">
    <div class="border-b px-5 py-4 flex-shrink-0">
      <h2 class="text-base font-semibold text-slate-800">Delivery status</h2>
      <p class="mt-1 text-xs text-slate-500">Confirm how this order will be fulfilled before submitting.</p>
    </div>
    <div class="space-y-3 p-5 flex-1 overflow-y-auto">
      <label class="delivery-status-option flex cursor-pointer items-start gap-3 rounded-xl border-2 border-orange-400 bg-orange-50/60 p-4 transition hover:bg-orange-50">
        <input type="radio" name="pos_delivery_status" value="collected_from_showroom" class="mt-1 h-4 w-4 border-slate-300 text-orange-600 focus:ring-orange-500" checked>
        <span>
          <span class="block text-sm font-semibold text-slate-800">Collected from showroom by Customer</span>
          <span class="mt-0.5 block text-xs text-slate-500">Customer took goods from the store now · marks order <strong>Shipped</strong></span>
        </span>
      </label>
      <label class="delivery-status-option flex cursor-pointer items-start gap-3 rounded-xl border-2 border-transparent bg-slate-50 p-4 transition hover:border-slate-200 hover:bg-white">
        <input type="radio" name="pos_delivery_status" value="deliver_later" class="mt-1 h-4 w-4 border-slate-300 text-orange-600 focus:ring-orange-500">
        <span>
          <span class="block text-sm font-semibold text-slate-800">Deliver to customer Later</span>
          <span class="mt-0.5 block text-xs text-slate-500">Goods will be dispatched later · keeps order <strong>Pending</strong></span>
        </span>
      </label>
      
      <!-- E-way bill section (shown when collected_from_showroom is selected) -->
      <div id="ewayBillSection" class="space-y-3 border-t pt-4">
        <!-- <div class="flex items-center gap-2">
          <input type="checkbox" id="generate_ewb_for_delivery" class="rounded border-slate-300">
          <label for="generate_ewb_for_delivery" class="text-xs text-slate-600 cursor-pointer font-medium">Generate IRN and E-way bill for this shipment</label>
        </div> -->
        <div id="ewayBillFields" class="hidden space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
          <!-- Transport Mode Selection -->
          <div>
            <label for="delivery_veh_type" class="text-xs text-slate-600 font-medium">Transport Mode <span class="text-red-600">*</span></label>
            <select id="delivery_veh_type" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
              <option value="">-- Select transport mode --</option>
              <option value="1">(1) Road</option>
              <option value="2">(2) Rail</option>
              <option value="3">(3) Air</option>
              <option value="4">(4) Ship</option>
              <option value="5">(5) Road cum Ship</option>
            </select>
          </div>
          
          <!-- Road Transport Fields (Required) -->
          <div id="roadTransportFields" class="hidden grid grid-cols-2 gap-3">
            <div>
              <label for="delivery_veh_no" class="text-xs text-slate-600">Vehicle Number <span class="text-red-600">*</span></label>
              <input type="text" id="delivery_veh_no" placeholder="e.g., DL01AB1234" maxlength="20" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
            </div>
          </div>
          
          <!-- Rail/Air Transport Fields (Required) -->
          <div id="railAirTransportFields" class="hidden grid grid-cols-2 gap-3">
            <div>
              <label for="delivery_trans_doc_no" class="text-xs text-slate-600">Transport Document No. <span class="text-red-600">*</span></label>
              <input type="text" id="delivery_trans_doc_no" placeholder="e.g., TRN123456" maxlength="20" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
              <label for="delivery_trans_doc_date" class="text-xs text-slate-600">Document Date <span class="text-red-600">*</span></label>
              <input type="date" id="delivery_trans_doc_date" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
            </div>
          </div>
          
          <!-- Ship/Road cum Ship Transport Fields (Optional - either set can be used) -->
          <div id="shipTransportFields" class="hidden space-y-3">
            <p class="text-xs text-slate-500 italic">For Ship transport, you can provide Vehicle Number and/or Transport Document Number and Date.</p>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label for="delivery_ship_veh_no" class="text-xs text-slate-600">Vehicle Number (Optional)</label>
                <input type="text" id="delivery_ship_veh_no" placeholder="e.g., Vessel name" maxlength="20" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
              </div>
              <div class="col-span-1"></div>
            </div>
            <div class="border-t pt-3 mt-3">
              <p class="text-xs text-slate-500 italic mb-2">OR provide transport document details:</p>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label for="delivery_ship_trans_doc_no" class="text-xs text-slate-600">Transport Document No. (Optional)</label>
                  <input type="text" id="delivery_ship_trans_doc_no" placeholder="e.g., BL123456" maxlength="20" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                  <label for="delivery_ship_trans_doc_date" class="text-xs text-slate-600">Document Date (Optional)</label>
                  <input type="date" id="delivery_ship_trans_doc_date" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div id="deliveryStatusValidation" class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"></div>
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3 rounded-b-2xl flex-shrink-0">
      <button type="button" id="deliveryStatusBackBtn" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back</button>
      <button type="button" id="deliveryStatusSubmitBtn" class="inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-70">
        <span id="deliveryStatusSubmitBtnLabel">Submit order</span>
      </button>
    </div>
  </div>
</div>

<!-- POS checkout loading (covers screen after delivery/GST steps) -->
<div id="posCheckoutLoadingOverlay" class="fixed inset-0 z-[10003] hidden items-center justify-center bg-black/40 backdrop-blur-sm" role="status" aria-live="polite" aria-busy="true">
  <div class="mx-4 flex max-w-sm flex-col items-center rounded-2xl bg-white px-8 py-7 text-center shadow-2xl">
    <i class="fas fa-spinner fa-spin text-3xl text-orange-600" aria-hidden="true"></i>
    <p id="posCheckoutLoadingTitle" class="mt-4 text-base font-semibold text-slate-800">Creating order…</p>
    <p id="posCheckoutLoadingHint" class="mt-1 text-sm text-slate-500">Your request was accepted. Order creation is in progress.</p>
  </div>
</div>

<!-- LOCAL FALLBACK CONFIRM (Exotic order/create failed) -->
<div id="localFallbackConfirmModal" class="fixed inset-0 z-[10004] hidden">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
  <div class="relative mx-auto mt-[10vh] w-[95%] max-w-lg rounded-2xl bg-white shadow-2xl">
    <div class="border-b px-5 py-4">
      <h2 class="text-base font-semibold text-slate-800">Online order export failed</h2>
      <p class="mt-1 text-xs text-slate-500">The website API could not create this order on Exotic India.</p>
    </div>
    <div class="space-y-4 p-5">
      <div id="localFallbackApiErrorBox" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 relative">
        <button type="button" id="localFallbackApiErrorDismiss" class="absolute top-2 right-2 text-red-500 hover:text-red-800 leading-none" aria-label="Dismiss error">✕</button>
        <p class="text-xs font-semibold uppercase tracking-wide text-red-800 pr-6">API error</p>
        <p id="localFallbackApiError" class="mt-1 text-sm text-red-900 break-words pr-4"></p>
      </div>
      <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
        <p class="font-semibold">Create order locally in POS?</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-xs leading-relaxed text-amber-900">
          <li>A <strong>temporary order number</strong> (e.g. POS-TMP-…) will be assigned until the website API is working again.</li>
          <li>You can still <strong>register payment</strong> and <strong>create the tax invoice</strong> as usual.</li>
          <li>When the website API is active, the order can be <strong>published online</strong> from order details to replace the temp number with the real Exotic order ID.</li>
        </ul>
      </div>
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3 rounded-b-2xl">
      <button type="button" id="localFallbackCancelBtn" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancel</button>
      <button type="button" id="localFallbackConfirmBtn" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Save order &amp; continue checkout</button>
    </div>
  </div>
</div>

<!-- OVERSEAS GST CONFIRMATION (non-India customers) -->
<div id="overseasGstModal" class="fixed inset-0 z-[10002] hidden">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
  <div class="relative mx-auto mt-[14vh] w-[95%] max-w-lg rounded-2xl bg-white shadow-2xl">
    <div class="border-b px-5 py-4">
      <h2 class="text-base font-semibold text-slate-800">Apply GST on this invoice?</h2>
      <p class="mt-1 text-xs text-slate-500">Customer delivery country is outside India. Export orders are usually zero-rated.</p>
    </div>
    <div class="space-y-3 p-5">
      <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
        Delivery country: <strong id="overseasGstCountryLabel">—</strong>
      </div>
      <p class="text-xs leading-relaxed text-slate-600">
        Choose <strong>No GST</strong> for a typical zero-rated export invoice, or <strong>Apply GST</strong> if IGST must be charged on this export.
      </p>
    </div>
    <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3 rounded-b-2xl">
      <button type="button" id="overseasGstBackBtn" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back</button>
      <button type="button" id="overseasGstNoBtn" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">No GST (export)</button>
      <button type="button" id="overseasGstYesBtn" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Yes, apply GST</button>
    </div>
  </div>
</div>

<!-- DISCOUNT MODAL -->
<div id="discountModal" class="fixed inset-0 z-[9999] hidden">

  <div class="absolute inset-0 bg-black/40" onclick="closeDiscountModal()"></div>

  <div class="relative mx-auto mt-40 w-[95%] max-w-md rounded-2xl bg-white shadow-xl p-5">

    <h2 class="text-lg font-semibold mb-4">Apply Cash Discount</h2>

    <!-- TYPE -->
    <div class="mb-3">
      <label class="text-xs text-gray-600">Discount Type</label>
      <select id="discount_type"
        class="w-full mt-1 border rounded-lg px-3 py-2 text-sm">
        <option value="fixed">Fixed Amount (₹)</option>
        <option value="percent">Percentage (%)</option>
      </select>
    </div>

    <!-- VALUE -->
    <div class="mb-4">
      <input type="number" id="discount_value"
        class="w-full border rounded-lg px-3 py-2 text-sm"
        placeholder="Enter value">
    </div>

    <!-- BUTTONS -->
    <div class="flex justify-end gap-2">
      <button onclick="closeDiscountModal()"
        class="px-4 py-2 bg-gray-300 rounded-lg">Cancel</button>

      <button onclick="applyDiscount()"
        class="px-4 py-2 bg-orange-600 text-white rounded-lg">
        Apply
      </button>
    </div>

  </div>
</div>
<!-- CUSTOMER MODAL -->
<!-- INVOICE PREVIEW MODAL -->

<div id="invoicePreviewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50" onclick="closePreviewModal()">
  <div class="bg-white max-w-4xl w-full max-h-[90vh] overflow-y-auto rounded-lg" onclick="event.stopPropagation()">
    <div class="sticky top-0 bg-gray-100 p-4 border-b flex justify-between items-center">
      <h2 class="text-xl font-bold">Invoice Preview</h2>
      <button type="button" onclick="closePreviewModal()" class="text-red-600 hover:text-red-800 text-2xl">&times;</button>
    </div>
    <div id="invoicePreviewContent" class="p-4"></div>
    <div class="sticky bottom-0 bg-gray-100 p-4 border-t flex justify-end space-x-2">
      <button type="button" onclick="closePreviewModal()" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Close</button>
      <!-- <button type="button" onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Print</button> -->
      <a
        id="printInvoiceBtn"
        href="#"
        target="_blank"
        class="px-4 py-2 bg-blue-600 text-white rounded">
        Print
      </a>
    </div>
  </div>
</div>
</div>
</div>
</div>
</div>
</div>

<!-- ===== END PAGE WRAPPER ===== -->
<script src="<?php echo base_url(); ?>assets/js/pos_message_modal.js"></script>
<script src="<?php echo base_url(); ?>assets/js/pos_customer.js"></script>
<script src="<?php echo base_url(); ?>assets/js/compliance_doc_modal.js"></script>
<script src="<?php echo base_url(); ?>assets/js/pos_cart_hooks.js"></script>
<script src="<?php echo base_url(); ?>assets/js/order_follow_up_pos.js"></script>
<script src="<?php echo base_url(); ?>assets/js/pos.js"></script>
<!-- <script src="<?php echo 'http://' . $_SERVER['HTTP_HOST']; ?>/assets/js/pos.js"></script> -->
<script>
  function autoCreateInvoiceThenPreview(orderid) {

    fetch('?page=posinvoice&action=CreateAutoFromOrder', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          orderid: orderid
        })
      })
      .then(res => res.json())
      .then(data => {

        if (!data.success) {
          if (data.require_compliance && window.ComplianceDocModal) {
            window.ComplianceDocModal.open({
              customerId: data.customer_id,
              message: data.message,
              onSuccess: function () {
                autoCreateInvoiceThenPreview(orderid);
              }
            });
            return;
          }
          showToast(data.message || "Invoice create failed", "red");
          return;
        }

        showToast("✓ Invoice created", "green");

        previewInvoiceFromOrder(orderid);

      })
      .catch(err => {
        console.error(err);
        showToast("Invoice error", "red");
      });
  }


  function previewInvoiceFromOrder(orderNumber) {

    fetch('?page=posinvoice&action=preview', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          orderid: orderNumber
        })
      })
      .then(res => res.json())
      .then(data => {

        if (!data.success) {
          showToast("Preview failed", "red");
          return;
        }
        console.log(data, 'data')
        document.getElementById('invoicePreviewContent').innerHTML = data.html;
        document.getElementById('invoicePreviewModal').classList.remove('hidden');

        //  SET PRINT LINK
        if (data.invoice_id) {

          document.getElementById("printInvoiceBtn").href =
            "/?page=posinvoice&action=generate_pdf&invoice_id=" + data.invoice_id;

        } else {

          console.error("Invoice ID missing in preview response");

        }

      })
      .catch(err => {
        console.error(err);
        showToast("Preview error", "red");
      });
  }

  function openInvoicePreview(invoice_id) {

    fetch('?page=invoices&action=preview_after_create', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'invoice_id=' + invoice_id
      })
      .then(res => res.json())
      .then(data => {

        if (!data.success) {
          alert(data.message);
          return;
        }

        document.getElementById('invoicePreviewContent').innerHTML = data.html;
        document.getElementById('invoicePreviewModal').classList.remove('hidden');
        document.getElementById("printInvoiceBtn").href =
          "/?page=posinvoice&action=generate_pdf&invoice_id=" + invoice_id;

      });

  }

  function previewInvoice() {
    const formData = new FormData(document.getElementById('create_invoice'));

    // Collect item data
    const items = [];
    document.querySelectorAll('#invoiceTable tbody tr').forEach((row, idx) => {
      items.push({
        order_number: row.querySelector('input[name="order_number[]"]')?.value || '',
        box_no: row.querySelector('input[name="box_no[]"]')?.value || '',
        item_code: row.querySelector('input[name="item_code[]"]')?.value || '',
        item_name: row.querySelector('input[name="item_name[]"]')?.value || '',
        hsn: row.querySelector('input[name="hsn[]"]')?.value || '',
        quantity: row.querySelector('input[name="quantity[]"]')?.value || 0,
        unit_price: row.querySelector('input[name="unit_price[]"]')?.value || 0,
        cgst: row.querySelector('input[name="cgst[]"]')?.value || 0,
        sgst: row.querySelector('input[name="sgst[]"]')?.value || 0,
        igst: row.querySelector('input[name="igst[]"]')?.value || 0,
        tax_amount: row.querySelector('input[name="tax_amount[]"]')?.value || 0,
        line_total: row.querySelector('input[name="line_total[]"]')?.value || 0,
        currency: row.querySelector('input[name="currency[]"]')?.value || 'INR',
        image_url: row.querySelector('input[name="image_url[]"]')?.value || '',
        groupname: row.querySelector('input[name="groupname[]"]')?.value || ''
      });
    });

    if (items.length === 0) {
      alert('Please add at least one item to preview');
      return;
    }

    // Get selected address
    const vp_order_info_id = document.getElementById('vp_order_info_id').value;
    //const vpAddressInfoId = billToSelect && billToSelect.tagName === 'SELECT' ? billToSelect.value : '';

    const previewData = {
      invoice_date: formData.get('invoice_date') || new Date().toISOString().split('T')[0],
      customer_id: formData.get('customer_id') || 0,
      vp_order_info_id: vp_order_info_id || 0,
      subtotal: document.getElementById('subtotal')?.value || 0,
      tax_amount: document.getElementById('tax_amount')?.value || 0,
      discount_amount: document.getElementById('discount_amount')?.value || 0,
      total_amount: document.getElementById('total_amount')?.value || 0,
      status: formData.get('status') || 'draft',
      items: items
    };

    // Send to server for preview using template
    fetch('<?php echo base_url('?page=invoices&action=preview'); ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(previewData)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Display the HTML preview in modal
          const modal = document.getElementById('invoicePreviewModal');
          const previewContent = document.getElementById('invoicePreviewContent');

          // Set the HTML content from the tax invoice template
          previewContent.innerHTML = `<div style="max-height: 500px; overflow-y: auto; background: white;">${data.html}</div>`;

          modal.classList.remove('hidden');
        } else {
          alert('Error generating preview: ' + data.message);
        }
      })
      .catch(err => {
        console.error('Preview error:', err);
        alert('Failed to generate preview');
      });
  }


  function closePreviewModal() {
    document.getElementById('invoicePreviewModal').classList.add('hidden');
  }
</script>
<script>
  function posPaymentDateLocalYmd() {
    var d = new Date();
    var y = d.getFullYear();
    var m = String(d.getMonth() + 1).padStart(2, "0");
    var day = String(d.getDate()).padStart(2, "0");
    return y + "-" + m + "-" + day;
  }

  function syncPaymentDatePickerMax() {
    var pd = document.getElementById("payment_date");
    if (!pd) {
      return;
    }
    var today = posPaymentDateLocalYmd();
    pd.setAttribute("max", today);
    if (pd.value && pd.value > today) {
      pd.value = today;
    }
  }

  function syncPaymentDatePickerMax() {
    var el = document.getElementById("payment_date");
    if (!el || typeof posPaymentDateLocalYmd !== "function") {
      return;
    }
    var t = posPaymentDateLocalYmd();
    el.max = t;
    if (el.value && el.value > t) {
      el.value = t;
    }
  }

  var POS_PAYMENT_MODE_OPTIONS = <?= json_encode(
      $pos_payment_mode_options ?? [],
      JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
  ) ?>;

  function populatePaymentSplitModeSelect(selectEl, selectedMode) {
    if (!selectEl) return;
    var prev = String(selectedMode || selectEl.value || "cash").toLowerCase();
    selectEl.innerHTML = "";
    var options = Array.isArray(POS_PAYMENT_MODE_OPTIONS) ? POS_PAYMENT_MODE_OPTIONS : [];
    if (!options.length) {
      options = [
        ["cash", "Cash"],
        ["cod", "Cash on Delivery (COD)"],
        ["upi", "UPI"],
        ["bank_transfer", "Bank transfer"],
        ["pos_machine", "POS machine"],
        ["razorpay", "Razorpay"],
        ["cheque", "Cheque"],
        ["adminorder", "Admin Order"],
        ["waived", "Waived (no charge)"]
      ];
    }
    var isWaivedAllowed = typeof window.isPosFollowUpWaivedCheckout === "function" && window.isPosFollowUpWaivedCheckout();
    options.forEach(function(pair) {
      if (!Array.isArray(pair) || !pair[0]) return;
      var mode = String(pair[0]).toLowerCase();
      if (mode === "waived" && !isWaivedAllowed) {
        return;
      }
      var opt = document.createElement("option");
      opt.value = String(pair[0]);
      opt.textContent = String(pair[1] || pair[0]);
      selectEl.appendChild(opt);
    });
    if (prev && selectEl.querySelector('option[value="' + prev.replace(/"/g, "") + '"]')) {
      selectEl.value = prev;
    } else if (selectEl.options.length) {
      selectEl.selectedIndex = 0;
    }
  }

  function refreshAllPaymentSplitModeSelects() {
    var container = getPaymentSplitRowsContainer();
    if (!container) return;
    container.querySelectorAll(".payment-split-row").forEach(function(row) {
      var modeEl = row.querySelector(".payment-split-mode");
      populatePaymentSplitModeSelect(modeEl, modeEl ? modeEl.value : "cash");
    });
  }

  function formatPaymentInr(amount) {
    var n = parseFloat(String(amount));
    if (!isFinite(n)) {
      n = 0;
    }
    try {
      return new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR", minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
    } catch (e) {
      return "₹ " + n.toFixed(2);
    }
  }

  function getPaymentSplitRowsContainer() {
    return document.getElementById("payment_split_rows");
  }

  function syncPaymentSplitTxnHint(row) {
    if (!row) return;
    var mode = String(row.querySelector(".payment-split-mode")?.value || "").toLowerCase();
    var hint = row.querySelector(".payment-split-txn-hint");
    var txn = row.querySelector(".payment-split-txn");
    var need = mode === "razorpay" || mode === "cheque";
    if (hint) hint.classList.toggle("hidden", !need);
    if (txn) txn.placeholder = need ? "Required" : "Optional";
  }

  function bindPaymentSplitRow(row) {
    if (!row || row.dataset.bound === "1") return;
    row.dataset.bound = "1";
    row.querySelectorAll("input, select").forEach(function(el) {
      el.addEventListener("input", recalcPaymentSplitUi);
      el.addEventListener("change", recalcPaymentSplitUi);
    });
    var modeEl = row.querySelector(".payment-split-mode");
    if (modeEl) {
      modeEl.addEventListener("change", function() {
        syncPaymentSplitTxnHint(row);
      });
    }
    var removeBtn = row.querySelector(".payment-split-remove");
    if (removeBtn) {
      removeBtn.addEventListener("click", function() {
        var container = getPaymentSplitRowsContainer();
        if (!container || container.children.length <= 1) return;
        row.remove();
        recalcPaymentSplitUi();
      });
    }
    syncPaymentSplitTxnHint(row);
  }

  function addPaymentSplitRow(mode, amount, txn) {
    var tpl = document.getElementById("payment_split_row_template");
    var container = getPaymentSplitRowsContainer();
    if (!tpl || !container) return;
    var node = tpl.content.cloneNode(true);
    container.appendChild(node);
    var row = container.lastElementChild;
    if (!row) return;
    var modeEl = row.querySelector(".payment-split-mode");
    var amtEl = row.querySelector(".payment-split-amount");
    var txnEl = row.querySelector(".payment-split-txn");
    populatePaymentSplitModeSelect(modeEl, mode || "cash");
    if (modeEl && mode) modeEl.value = mode;
    if (amtEl != null && amount != null && amount !== "") amtEl.value = String(amount);
    if (txnEl && txn) txnEl.value = txn;
    bindPaymentSplitRow(row);
    recalcPaymentSplitUi();
  }

  function resetPaymentSplitRows(grandTotal) {
    var container = getPaymentSplitRowsContainer();
    if (!container) return;
    container.innerHTML = "";
    var isWaived = typeof window.isPosFollowUpWaivedCheckout === "function" && window.isPosFollowUpWaivedCheckout();
    if (isWaived) {
      addPaymentSplitRow("waived", 0, "");
    } else {
      var total = parseFloat(String(grandTotal));
      addPaymentSplitRow("cash", isFinite(total) && total > 0 ? total : "", "");
    }
  }

  function collectAllPaymentSplitRowsFromUi() {
    var container = getPaymentSplitRowsContainer();
    if (!container) return [];
    var out = [];
    container.querySelectorAll(".payment-split-row").forEach(function(row) {
      var mode = String(row.querySelector(".payment-split-mode")?.value || "").trim().toLowerCase();
      var amount = parseFloat(String(row.querySelector(".payment-split-amount")?.value || ""));
      var txn = String(row.querySelector(".payment-split-txn")?.value || "").trim();
      if (!mode) return;
      if (mode === "waived") {
        out.push({ mode: "waived", amount: 0, transaction_id: txn });
        return;
      }
      if (!isFinite(amount) || amount <= 0) return;
      out.push({ mode: mode, amount: Math.round(amount * 100) / 100, transaction_id: txn });
    });
    return out;
  }

  function getPaymentSplitAdvanceTotalFromUi() {
    var total = 0;
    collectAllPaymentSplitRowsFromUi().forEach(function(s) {
      if (s.mode !== "cod") total += s.amount;
    });
    return Math.round(total * 100) / 100;
  }

  function getPaymentSplitCodTotalFromUi() {
    var total = 0;
    collectAllPaymentSplitRowsFromUi().forEach(function(s) {
      if (s.mode === "cod") total += s.amount;
    });
    return Math.round(total * 100) / 100;
  }

  function paymentSplitHasCodFromUi() {
    return getPaymentSplitCodTotalFromUi() > 0.001;
  }

  function collectPaymentSplitsFromUi() {
    return collectAllPaymentSplitRowsFromUi();
  }

  function getPaymentSplitTotalFromUi() {
    var total = 0;
    collectAllPaymentSplitRowsFromUi().forEach(function(s) {
      total += s.amount;
    });
    return Math.round(total * 100) / 100;
  }

  function syncLegacyPaymentHiddenFields(splits, total) {
    var amountEl = document.getElementById("payment_amount");
    var modeEl = document.getElementById("payment_mode");
    var txnEl = document.getElementById("transaction_id");
    if (amountEl) amountEl.value = String(total);
    var primary = splits[0] || { mode: "cash", transaction_id: "" };
    splits.forEach(function(s) {
      if (s.amount > primary.amount) primary = s;
    });
    if (modeEl) modeEl.value = primary.mode || "cash";
    if (txnEl) txnEl.value = primary.transaction_id || "";
  }

  function recalcPaymentSplitUi() {
    var splits = collectAllPaymentSplitRowsFromUi();
    var splitTotal = getPaymentSplitTotalFromUi();
    var advanceTotal = getPaymentSplitAdvanceTotalFromUi();
    var codTotal = getPaymentSplitCodTotalFromUi();
    var hasCod = paymentSplitHasCodFromUi();
    var orderTotal = getCurrentCheckoutTotal();
    var stage = String(document.getElementById("payment_stage")?.value || "final").toLowerCase();
    var target = orderTotal;
    var balance = Math.round((target - splitTotal) * 100) / 100;

    syncLegacyPaymentHiddenFields(splits, hasCod ? advanceTotal : splitTotal);

    var orderEl = document.getElementById("payment_summary_order");
    var paidEl = document.getElementById("payment_summary_paid");
    var balEl = document.getElementById("payment_summary_balance");
    var balLabelEl = document.getElementById("payment_summary_balance_label");
    var hintEl = document.getElementById("payment_summary_hint");
    var countEl = document.getElementById("payment_split_count");
    var totalEl = document.getElementById("payment_split_total");

    if (orderEl) orderEl.textContent = formatPaymentInr(target);
    if (paidEl) paidEl.textContent = formatPaymentInr(hasCod ? advanceTotal : splitTotal);
    if (balLabelEl) balLabelEl.textContent = hasCod ? "COD pending" : "Balance";
    if (balEl) {
      balEl.textContent = formatPaymentInr(hasCod ? codTotal : balance);
      if (hasCod) {
        balEl.className = "mt-0.5 text-lg font-bold text-amber-700 tabular-nums";
      } else if (stage === "final" && Math.abs(balance) < 0.02) {
        balEl.className = "mt-0.5 text-lg font-bold text-emerald-700 tabular-nums";
      } else if (balance > 0.01) {
        balEl.className = "mt-0.5 text-lg font-bold text-amber-700 tabular-nums";
      } else if (balance < -0.01) {
        balEl.className = "mt-0.5 text-lg font-bold text-red-700 tabular-nums";
      } else {
        balEl.className = "mt-0.5 text-lg font-bold text-emerald-700 tabular-nums";
      }
    }
    if (countEl) countEl.textContent = String(splits.length);
    if (totalEl) totalEl.textContent = formatPaymentInr(splitTotal);
    if (hintEl) {
      if (hasCod) {
        if (Math.abs(splitTotal - orderTotal) > 0.02) {
          hintEl.textContent = "Advance plus COD must equal order total (" + formatPaymentInr(orderTotal) + ").";
          hintEl.classList.remove("hidden");
        } else if (codTotal > 0.001) {
          hintEl.textContent = formatPaymentInr(codTotal) + " will be collected on delivery.";
          hintEl.classList.remove("hidden");
        } else {
          hintEl.classList.add("hidden");
        }
      } else if (stage === "final" && balance > 0.02) {
        hintEl.textContent = "Add " + formatPaymentInr(balance) + " more to match order total.";
        hintEl.classList.remove("hidden");
      } else if (stage === "final" && balance < -0.02) {
        hintEl.textContent = "Split total exceeds order total by " + formatPaymentInr(Math.abs(balance)) + ".";
        hintEl.classList.remove("hidden");
      } else if ((stage === "partial" || stage === "advance") && splitTotal + 0.02 >= orderTotal && orderTotal > 0) {
        hintEl.textContent = "Partial / advance must be less than order total.";
        hintEl.classList.remove("hidden");
      } else {
        hintEl.classList.add("hidden");
      }
    }
    syncCustomInvoiceNumberField();
  }

  function validatePaymentSplitsForCheckout(grandTotal, options) {
    options = options || {};
    var box = document.getElementById("payment_split_validation");
    var boxText = document.getElementById("payment_split_validation_text");
    var hideErr = function() {
      if (box) {
        box.classList.add("hidden");
      }
      if (boxText) {
        boxText.textContent = "";
      }
    };
    var showErr = function(msg) {
      if (boxText) {
        boxText.textContent = msg;
      } else if (box) {
        box.textContent = msg;
      }
      if (box) {
        box.classList.remove("hidden");
      }
    };

    var splits = collectAllPaymentSplitRowsFromUi();
    if (!splits.length) {
      showErr("Add at least one payment line.");
      return null;
    }

    var paymentStage = String(document.getElementById("payment_stage")?.value || "final").toLowerCase();
    var advanceTotal = getPaymentSplitAdvanceTotalFromUi();
    var codTotal = getPaymentSplitCodTotalFromUi();
    var paymentAmount = getPaymentSplitTotalFromUi();
    var hasCod = codTotal > 0.001;

    var isWaivedAllowed = typeof window.isPosFollowUpWaivedCheckout === "function" && window.isPosFollowUpWaivedCheckout();

    for (var i = 0; i < splits.length; i++) {
      if (splits[i].mode === "waived") {
        if (!isWaivedAllowed) {
          showErr("Waived payment mode is only allowed for Reship or Replacement follow-up orders.");
          return null;
        }
        if (isFinite(splits[i].amount) && splits[i].amount > 0.001) {
          showErr("Waived payment line must be zero amount.");
          return null;
        }
        continue;
      }
      if (!isFinite(splits[i].amount) || splits[i].amount <= 0) {
        showErr("Each payment line must have amount greater than zero.");
        return null;
      }
    }

    var allWaived = splits.every(function(s) { return s.mode === "waived"; });
    if (allWaived) {
      if (!isWaivedAllowed) {
        showErr("Waived payment mode is only allowed for Reship or Replacement follow-up orders.");
        return null;
      }
      var primaryWaived = splits[0] || { mode: "waived", transaction_id: "" };
      return {
        splits: splits,
        total: 0,
        advanceTotal: 0,
        codTotal: 0,
        paymentStage: "final",
        hasCod: false,
        primaryMode: "waived",
        primaryTxn: primaryWaived.transaction_id || ""
      };
    }

    if (hasCod) {
      if (paymentAmount + 0.02 < grandTotal) {
        showErr("Advance plus COD must equal order total ₹ " + grandTotal);
        return null;
      }
      if (paymentAmount - 0.02 > grandTotal) {
        showErr("Advance plus COD exceeds order total.");
        return null;
      }
      paymentStage = "advance";
    } else {
      if (paymentAmount <= 0) {
        showErr("Payment amount must be greater than zero.");
        return null;
      }

      if (paymentStage === "final") {
        if (paymentAmount + 0.02 < grandTotal) {
          showErr("Final payment must be FULL amount ₹ " + grandTotal);
          return null;
        }
        if (paymentAmount - 0.02 > grandTotal) {
          showErr("Over payment not allowed");
          return null;
        }
      } else if (paymentStage === "partial" || paymentStage === "advance") {
        if (paymentAmount + 0.02 >= grandTotal) {
          showErr("Partial payment must be less than total ₹ " + grandTotal);
          return null;
        }
      }
    }

    var container = getPaymentSplitRowsContainer();
    for (var i = 0; i < splits.length; i++) {
      var s = splits[i];
      if ((s.mode === "razorpay" || s.mode === "cheque") && !s.transaction_id) {
        showErr((s.mode === "cheque" ? "Cheque number" : "Transaction ID") + " is required for " + s.mode + " (line " + (i + 1) + ").");
        var rows = container ? container.querySelectorAll(".payment-split-row") : [];
        var row = rows[i];
        var txnInput = row ? row.querySelector(".payment-split-txn") : null;
        if (txnInput) txnInput.focus();
        return null;
      }
    }

    var highValueLimit = getHighValueLimit();
    var cashLegNeeds269 = splits.some(function(s) {
      return s.mode === "cash" && s.amount + 0.02 >= highValueLimit;
    });
    if (cashLegNeeds269 && !options.skip269stConfirm) {
      if (typeof window.showPosConfirmModal === "function") {
        window.showPosConfirmModal({
          title: "Section 269ST Cash Warning",
          message: "Cash receipts of ₹2,00,000 or more are restricted under Income Tax Act Section 269ST. Please switch to digital payment.\n\nDo you still want to continue after acknowledging this warning?",
          confirmText: "Acknowledge & Continue",
          cancelText: "Switch Payment",
          tone: "warning",
          onConfirm: function() {
            if (typeof options.on269stConfirmed === "function") {
              options.on269stConfirmed();
            }
          },
          onCancel: function() {
            showErr("Please switch to digital payment or acknowledge the cash warning.");
          }
        });
        return null;
      }
    }

    hideErr();
    return {
      payment_stage: paymentStage,
      payment_amount: hasCod ? advanceTotal : paymentAmount,
      payment_splits: splits,
      sec269st_cash_warning_confirmed: cashLegNeeds269 ? "1" : "0",
      primary_mode: splits.reduce(function(best, s) { return s.amount > best.amount ? s : best; }, splits[0]).mode,
      primary_txn: splits.reduce(function(best, s) { return s.amount > best.amount ? s : best; }, splits[0]).transaction_id
    };
  }

  function openPaymentModal() {
    if (typeof window.hasUnconfirmedLocalStockWarnings === "function" && window.hasUnconfirmedLocalStockWarnings()) {
      showToast("Please confirm local stock for cart items (Y or N) before checkout.", "violet");
      return;
    }
    var customerId = typeof window.getSelectedCustomerId === "function" ? window.getSelectedCustomerId() : "";
    if (!customerId) {
      if (typeof window.showPosMessageModal === "function") {
        window.showPosMessageModal({
          title: "Customer required",
          message: "Please select customer first",
          tone: "warning",
          onClose: function () {
            if (typeof window.focusPosCustomerSelect === "function") {
              window.focusPosCustomerSelect();
            } else if (typeof jQuery !== "undefined" && jQuery("#customerSelect").data("select2")) {
              jQuery("#customerSelect").select2("open");
            } else {
              var cs = document.getElementById("customerSelect");
              if (cs) cs.focus();
            }
          }
        });
      } else {
        showToast("Please select customer first", "red");
        if (typeof jQuery !== "undefined" && jQuery("#customerSelect").data("select2")) {
          jQuery("#customerSelect").select2("open");
        } else {
          var cs = document.getElementById("customerSelect");
          if (cs) cs.focus();
        }
      }
      return;
    }
    var pm = document.getElementById("paymentModal");
    if (!pm) {
      return;
    }
    var apiPanel = document.getElementById("paymentModalOrderApiPanel");
    var apiPre = document.getElementById("paymentModalOrderApiPre");
    if (apiPanel) {
      apiPanel.classList.add("hidden");
    }
    if (apiPre) {
      apiPre.textContent = "";
    }
    syncPaymentDatePickerMax();
    var ct = typeof window.getPosCartTotalsForCheckout === "function" ? window.getPosCartTotalsForCheckout() : null;
    var grand = ct && ct.grandTotal != null && !isNaN(parseFloat(String(ct.grandTotal)))
      ? parseFloat(String(ct.grandTotal))
      : parseFloat("<?= (float)($cartData['grand_total'] ?? 0) ?>");
    resetPaymentSplitRows(grand);
    recalcPaymentSplitUi();
    syncCustomInvoiceNumberField();
    pm.classList.remove("hidden");
  }

  window.openOrderCreateApiResponseModal = function () {
    var pre = document.getElementById("paymentModalOrderApiPre");
    var panel = document.getElementById("paymentModalOrderApiPanel");
    if (!pre || !panel) {
      return;
    }
    var d = window.__posLastOrderCreateDebug;
    pre.textContent = formatOrderCreateDebugText(d);
    panel.classList.remove("hidden");
  };

  function closePaymentModal() {
    var pm = document.getElementById("paymentModal");
    if (pm) {
      pm.classList.add("hidden");
    }
  }

  var pendingAddressPayloadForCheckout = null;
  var pendingCheckoutPayloadForGst = null;

  function syncDeliveryStatusOptionStyles() {
    document.querySelectorAll("#deliveryStatusModal .delivery-status-option").forEach(function(label) {
      var radio = label.querySelector('input[name="pos_delivery_status"]');
      var on = radio && radio.checked;
      label.classList.toggle("border-orange-400", !!on);
      label.classList.toggle("bg-orange-50/60", !!on);
      label.classList.toggle("border-transparent", !on);
      label.classList.toggle("bg-slate-50", !on);
    });
  }

  function syncEwayBillSectionVisibility() {
    var status = getSelectedPosDeliveryStatus();
    var ewayBillSection = document.getElementById("ewayBillSection");
    if (ewayBillSection) {
      if (status === "collected_from_showroom") {
        ewayBillSection.classList.remove("hidden");
      } else {
        ewayBillSection.classList.add("hidden");
        // Reset E-way bill fields when section is hidden
        var generateEwbCheckbox = document.getElementById("generate_ewb_for_delivery");
        if (generateEwbCheckbox) {
          generateEwbCheckbox.checked = false;
          document.getElementById("ewayBillFields").classList.add("hidden");
          document.getElementById("delivery_veh_no").value = "";
          document.getElementById("delivery_veh_type").value = "";
          document.getElementById("delivery_trans_doc_no").value = "";
          document.getElementById("delivery_trans_doc_date").value = "";
          document.getElementById("delivery_ship_veh_no").value = "";
          document.getElementById("delivery_ship_trans_doc_no").value = "";
          document.getElementById("delivery_ship_trans_doc_date").value = "";
        }
      }
    }
  }

  function syncTransportModeFields() {
    var modeSelect = document.getElementById("delivery_veh_type");
    var roadFields = document.getElementById("roadTransportFields");
    var railAirFields = document.getElementById("railAirTransportFields");
    var shipFields = document.getElementById("shipTransportFields");
    
    if (!modeSelect || !roadFields || !railAirFields || !shipFields) return;
    
    var mode = String(modeSelect.value || "").trim();
    // Mode 1 = Road, Mode 2 = Rail, Mode 3 = Air, Mode 4 = Ship, Mode 5 = Road cum Ship
    var isRoad = mode === "1";
    var isRailOrAir = mode === "2" || mode === "3";
    var isShip = mode === "4" || mode === "5";
    
    roadFields.classList.toggle("hidden", !isRoad);
    railAirFields.classList.toggle("hidden", !isRailOrAir);
    shipFields.classList.toggle("hidden", !isShip);
  }

  function getSelectedPosDeliveryStatus() {
    var picked = document.querySelector('#deliveryStatusModal input[name="pos_delivery_status"]:checked');
    return picked ? String(picked.value || "").trim() : "";
  }

  function openDeliveryStatusModal(addressPayload) {
    pendingAddressPayloadForCheckout = addressPayload;
    var modal = document.getElementById("deliveryStatusModal");
    var err = document.getElementById("deliveryStatusValidation");
    if (err) {
      err.classList.add("hidden");
      err.textContent = "";
    }
    var status = addressPayload && addressPayload.pos_delivery_status ? String(addressPayload.pos_delivery_status) : "";
    var selectedRadio = status
      ? document.querySelector('#deliveryStatusModal input[name="pos_delivery_status"][value="' + status + '"]')
      : null;
    var defaultRadio = document.querySelector('#deliveryStatusModal input[name="pos_delivery_status"][value="collected_from_showroom"]');
    if (selectedRadio) {
      selectedRadio.checked = true;
    } else if (defaultRadio) {
      defaultRadio.checked = true;
    }
    syncDeliveryStatusOptionStyles();
    if (modal) {
      modal.classList.remove("hidden");
    }
  }

  function closeDeliveryStatusModal() {
    var modal = document.getElementById("deliveryStatusModal");
    if (modal) {
      modal.classList.add("hidden");
    }
  }

  function closeAllPosCheckoutModals() {
    closeDeliveryStatusModal();
    closeLocalFallbackConfirmModal();
    closeOverseasGstModal();
    closeAddressConfirmModal();
    closePaymentModal();
    setPosCheckoutLoading(false);
  }

  var pendingLocalFallbackCheckoutPayload = null;

  function showPosCheckoutErrorBanner(msg) {
    var banner = document.getElementById("posCheckoutErrorBanner");
    var text = document.getElementById("posCheckoutErrorBannerText");
    if (!banner || !text) {
      return;
    }
    text.textContent = msg || "Checkout failed.";
    banner.classList.remove("hidden");
  }

  function hidePosCheckoutErrorBanner() {
    var banner = document.getElementById("posCheckoutErrorBanner");
    var text = document.getElementById("posCheckoutErrorBannerText");
    if (banner) {
      banner.classList.add("hidden");
    }
    if (text) {
      text.textContent = "";
    }
  }

  function showAddressConfirmValidationError(msg) {
    var summary = document.getElementById("addressConfirmValidationSummary");
    var text = document.getElementById("addressConfirmValidationSummaryText");
    if (!summary || !text) {
      return;
    }
    text.textContent = msg || "";
    summary.classList.remove("hidden");
  }

  function hideAddressConfirmValidationError() {
    var summary = document.getElementById("addressConfirmValidationSummary");
    var text = document.getElementById("addressConfirmValidationSummaryText");
    if (summary) {
      summary.classList.add("hidden");
    }
    if (text) {
      text.textContent = "";
    }
  }

  function openLocalFallbackConfirmModal(apiErrorMessage, debug, addressPayload) {
    pendingLocalFallbackCheckoutPayload = addressPayload || null;
    var modal = document.getElementById("localFallbackConfirmModal");
    var errEl = document.getElementById("localFallbackApiError");
    var errBox = document.getElementById("localFallbackApiErrorBox");
    if (errEl) {
      errEl.textContent = apiErrorMessage || "Unknown API error.";
    }
    if (errBox) {
      errBox.classList.remove("hidden");
    }
    if (modal) {
      modal.classList.remove("hidden");
    }
    var confirmBtn = document.getElementById("localFallbackConfirmBtn");
    if (confirmBtn) {
      confirmBtn.disabled = false;
      confirmBtn.textContent = "Save order & continue checkout";
    }
    window.__posLastOrderCreateDebug = debug || null;
    if (window.__posLastOrderCreateDebug && typeof showPaymentModalOrderApiRecord === "function") {
      showPaymentModalOrderApiRecord(window.__posLastOrderCreateDebug);
    }
  }

  function closeLocalFallbackConfirmModal() {
    var modal = document.getElementById("localFallbackConfirmModal");
    if (modal) {
      modal.classList.add("hidden");
    }
    pendingLocalFallbackCheckoutPayload = null;
    var confirmBtn = document.getElementById("localFallbackConfirmBtn");
    if (confirmBtn) {
      confirmBtn.disabled = false;
      confirmBtn.textContent = "Save order & continue checkout";
    }
  }

  var posCheckoutLoadingActive = false;

  function setPosCheckoutLoading(isLoading, options) {
    options = options || {};
    posCheckoutLoadingActive = !!isLoading;

    var globalOverlay = document.getElementById("posCheckoutLoadingOverlay");
    var submitBtn = document.getElementById("deliveryStatusSubmitBtn");
    var backBtn = document.getElementById("deliveryStatusBackBtn");
    var submitLabel = document.getElementById("deliveryStatusSubmitBtnLabel");
    var titleEl = document.getElementById("posCheckoutLoadingTitle");
    var hintEl = document.getElementById("posCheckoutLoadingHint");

    if (titleEl && options.title) {
      titleEl.textContent = options.title;
    } else if (titleEl && !isLoading) {
      titleEl.textContent = "Creating order…";
    }
    if (hintEl && options.hint) {
      hintEl.textContent = options.hint;
    } else if (hintEl && !isLoading) {
      hintEl.textContent = "Your request was accepted. Order creation is in progress.";
    }

    if (globalOverlay) {
      globalOverlay.classList.toggle("hidden", !isLoading);
      globalOverlay.classList.toggle("flex", !!isLoading);
      globalOverlay.setAttribute("aria-busy", isLoading ? "true" : "false");
    }
    if (submitBtn) {
      submitBtn.disabled = !!isLoading;
    }
    if (backBtn) {
      backBtn.disabled = !!isLoading;
    }
    if (submitLabel) {
      submitLabel.textContent = isLoading ? "Creating order…" : "Submit order";
    }

    ["overseasGstBackBtn", "overseasGstNoBtn", "overseasGstYesBtn"].forEach(function(id) {
      var btn = document.getElementById(id);
      if (btn) {
        btn.disabled = !!isLoading;
      }
    });
  }

  function openAddressConfirmModal() {
    document.getElementById("addressConfirmModal").classList.remove("hidden");
  }

  function closeAddressConfirmModal() {
    setConfirmShippingSameAsBilling(false);
    document.getElementById("addressConfirmModal").classList.add("hidden");
  }

  function getSelectedCustomerId() {
    var fromSelect = typeof jQuery !== "undefined" ? jQuery("#customerSelect").val() : document.getElementById("customerSelect").value;
    if (Array.isArray(fromSelect)) {
      fromSelect = fromSelect[0] || "";
    }
    return (fromSelect && String(fromSelect)) || (window.POS_SESSION_CUSTOMER_ID && String(window.POS_SESSION_CUSTOMER_ID)) || "";
  }

  function normalizePosCountryCode(raw, selectEl) {
    var v = String(raw || "").trim();
    if (v === "") {
      return "IN";
    }
    var upper = v.toUpperCase();
    if (upper.length === 2 && selectEl && selectEl.querySelector('option[value="' + upper + '"]')) {
      return upper;
    }
    if (selectEl) {
      var i;
      for (i = 0; i < selectEl.options.length; i++) {
        var opt = selectEl.options[i];
        if (opt.value.toUpperCase() === upper) {
          return opt.value;
        }
        if (opt.text.toLowerCase() === v.toLowerCase()) {
          return opt.value;
        }
      }
    }
    var byName = window.POS_COUNTRY_ISO_BY_NAME || {};
    var mapped = byName[v.toLowerCase()];
    if (mapped) {
      return String(mapped).toUpperCase().substring(0, 2);
    }
    return upper.length >= 2 ? upper.substring(0, 2) : "IN";
  }

  function setPosCountrySelect(id, raw) {
    var el = document.getElementById(id);
    if (!el || el.tagName !== "SELECT") {
      return;
    }
    if (!raw && id === "country_of_residence") {
      el.value = "";
      return;
    }
    el.value = normalizePosCountryCode(raw, el);
    if (!el.value && id !== "country_of_residence") {
      el.value = "IN";
    }
  }

  var POS_STATE_FIELD_CONFIG = {
    billing: { countryId: "confirm_country", inputId: "confirm_state", selectId: "confirm_state_select" },
    shipping: { countryId: "confirm_scountry", inputId: "confirm_sstate", selectId: "confirm_sstate_select" }
  };

  function isPosIndiaCountry(code) {
    var c = String(code || "").trim().toUpperCase();
    return c === "IN" || c === "IND" || c === "INDIA";
  }

  function syncResidencyFromBillingCountry(countryCode, existingResidency) {
    var residencyEl = document.getElementById("customer_residency_status");
    if (!residencyEl) return;
    var isIndia = isPosIndiaCountry(countryCode);
    if (isIndia) {
      residencyEl.value = "INDIAN_RESIDENT";
    } else {
      if (existingResidency && (existingResidency === "NRI" || existingResidency === "FOREIGN_NATIONAL")) {
        residencyEl.value = existingResidency;
      } else {
        residencyEl.value = "FOREIGN_NATIONAL";
      }
    }
  }

  function resolvePosCountryFromPayloadValue(raw, selectId) {
    var selectEl = selectId ? document.getElementById(selectId) : null;
    return normalizePosCountryCode(raw, selectEl);
  }

  function resolvePosPlaceOfSupplyCountry(payload) {
    if (!payload || typeof payload !== "object") {
      return "IN";
    }
    var hasShipping = String(payload.confirm_saddress1 || "").trim() !== "";
    if (hasShipping) {
      return resolvePosCountryFromPayloadValue(payload.confirm_scountry || "IN", "confirm_scountry");
    }
    return resolvePosCountryFromPayloadValue(payload.confirm_country || "IN", "confirm_country");
  }

  function posCountryDisplayName(code) {
    var normalized = String(code || "").trim().toUpperCase();
    var selectEl = document.getElementById("confirm_scountry") || document.getElementById("confirm_country");
    if (selectEl) {
      var i;
      for (i = 0; i < selectEl.options.length; i++) {
        if (String(selectEl.options[i].value || "").toUpperCase() === normalized) {
          return selectEl.options[i].text || normalized;
        }
      }
    }
    return normalized || "—";
  }

  function needsOverseasGstConfirmation(payload) {
    return !isPosIndiaCountry(resolvePosPlaceOfSupplyCountry(payload));
  }

  function openOverseasGstModal(payload) {
    pendingCheckoutPayloadForGst = payload;
    var label = document.getElementById("overseasGstCountryLabel");
    if (label) {
      label.textContent = posCountryDisplayName(resolvePosPlaceOfSupplyCountry(payload));
    }
    document.getElementById("overseasGstModal").classList.remove("hidden");
  }

  function closeOverseasGstModal() {
    pendingCheckoutPayloadForGst = null;
    document.getElementById("overseasGstModal").classList.add("hidden");
  }

  function submitCheckoutWithExportGst(applyGst) {
    if (!pendingCheckoutPayloadForGst) {
      showToast("Checkout details missing — please try again.", "red");
      return;
    }
    var payload = Object.assign({}, pendingCheckoutPayloadForGst, {
      apply_export_gst: applyGst ? "1" : "0"
    });
    closeOverseasGstModal();
    closeDeliveryStatusModal();
    createOrderNow(payload);
  }

  function isPosStateDropdownCountry(code) {
    var c = String(code || "").trim().toUpperCase();
    return c === "IN" || c === "IND" || c === "INDIA" || c === "US" || c === "USA" || c === "UNITED STATES";
  }

  function fetchPosIndiaStates() {
    return fetchPosCountryStates("IN").then(function(states) {
      window.POS_INDIA_STATES = states;
      return states;
    });
  }

  function fetchPosCountryStates(countryCode) {
    var country = String(countryCode || "IN").trim().toUpperCase().substring(0, 2) || "IN";
    var stateMap = window.POS_COUNTRY_STATES || {};
    if (Array.isArray(stateMap[country]) && stateMap[country].length) {
      return Promise.resolve(stateMap[country]);
    }

    return fetch("index.php?page=pos_register&action=states-by-country&country=" + encodeURIComponent(country), {
      credentials: "same-origin",
      headers: { Accept: "application/json" }
    })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        window.POS_COUNTRY_STATES = window.POS_COUNTRY_STATES || {};
        window.POS_COUNTRY_STATES[country] = Array.isArray(data) ? data : [];
        if (country === "IN") {
          window.POS_INDIA_STATES = window.POS_COUNTRY_STATES[country];
        }
        return window.POS_COUNTRY_STATES[country];
      })
      .catch(function() {
        window.POS_COUNTRY_STATES = window.POS_COUNTRY_STATES || {};
        window.POS_COUNTRY_STATES[country] = [];
        return [];
      });
  }

  function populatePosStateSelect(selectEl, states, selectedValue) {
    if (!selectEl) return;
    var selected = String(selectedValue || "").trim();
    var selectedLower = selected.toLowerCase();
    var html = '<option value="">Select state</option>';
    (states || []).forEach(function(state) {
      var name = String((state && state.name) || "").trim();
      if (!name) return;
      var esc = name.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/"/g, "&quot;");
      html += '<option value="' + esc + '">' + esc + "</option>";
    });
    selectEl.innerHTML = html;
    if (selected) {
      var matched = false;
      Array.prototype.forEach.call(selectEl.options, function(opt) {
        if (opt.value.toLowerCase() === selectedLower) {
          opt.selected = true;
          matched = true;
        }
      });
      if (!matched) {
        var opt = document.createElement("option");
        opt.value = selected;
        opt.textContent = selected;
        opt.selected = true;
        selectEl.appendChild(opt);
      }
    }
  }

  function resetPosStateSelect(selectEl, message) {
    if (!selectEl) return;
    var label = message || "Select state";
    var esc = label.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/"/g, "&quot;");
    selectEl.innerHTML = '<option value="">' + esc + "</option>";
    selectEl.value = "";
  }

  function getPosStateValue(inputId) {
    var selectEl = document.getElementById(inputId + "_select");
    var inputEl = document.getElementById(inputId);
    if (selectEl && !selectEl.classList.contains("hidden")) {
      return String(selectEl.value || "").trim();
    }
    return inputEl ? String(inputEl.value || "").trim() : "";
  }

  function setPosStateValue(inputId, value) {
    var val = String(value || "").trim();
    var selectEl = document.getElementById(inputId + "_select");
    var inputEl = document.getElementById(inputId);
    if (selectEl && !selectEl.classList.contains("hidden")) {
      var cfg = inputId === "confirm_sstate" ? POS_STATE_FIELD_CONFIG.shipping : POS_STATE_FIELD_CONFIG.billing;
      var countryEl = cfg ? document.getElementById(cfg.countryId) : null;
      var country = countryEl ? normalizePosCountryCode(countryEl.value, countryEl) : "IN";
      var stateMap = window.POS_COUNTRY_STATES || {};
      populatePosStateSelect(selectEl, stateMap[country] || [], val);
      return;
    }
    if (inputEl) inputEl.value = val;
  }

  function syncPosStateField(kind, preferredValue) {
    var cfg = POS_STATE_FIELD_CONFIG[kind];
    if (!cfg) return Promise.resolve();
    var countryEl = document.getElementById(cfg.countryId);
    var inputEl = document.getElementById(cfg.inputId);
    var selectEl = document.getElementById(cfg.selectId);
    if (!countryEl || !inputEl || !selectEl) return Promise.resolve();

    var country = normalizePosCountryCode(countryEl.value, countryEl);
    var useStateDropdown = isPosStateDropdownCountry(country);
    var defaultState = isPosIndiaCountry(country) ? String(window.POS_DEFAULT_STATE || "Delhi") : "";
    var value = preferredValue !== undefined ? String(preferredValue || "").trim() : getPosStateValue(cfg.inputId);
    if (!value) value = defaultState;

    if (!useStateDropdown) {
      if (value) inputEl.value = value;
      else if (selectEl.value && !inputEl.value) inputEl.value = selectEl.value;
      selectEl.classList.add("hidden");
      inputEl.classList.remove("hidden");
      return Promise.resolve();
    }

    inputEl.value = "";
    resetPosStateSelect(selectEl, "Loading states...");
    inputEl.classList.add("hidden");
    selectEl.classList.remove("hidden");
    return fetchPosCountryStates(country).then(function(states) {
      populatePosStateSelect(selectEl, states, value);
      inputEl.classList.add("hidden");
      selectEl.classList.remove("hidden");
    });
  }

  function syncResidencyFromBillingCountry(countryCode) {
    var residencyEl = document.getElementById("customer_residency_status");
    if (!residencyEl) return;
    var isIndia = isPosIndiaCountry(countryCode);
    if (isIndia) {
      residencyEl.value = "INDIAN_RESIDENT";
    } else {
      if (!residencyEl.value || residencyEl.value === "INDIAN_RESIDENT") {
        residencyEl.value = "FOREIGN_NATIONAL";
      }
    }
  }

  function syncAllPosStateFields(preferred) {
    preferred = preferred || {};
    return Promise.all([
      syncPosStateField("billing", preferred.billing),
      syncPosStateField("shipping", preferred.shipping)
    ]);
  }

  function setAddressConfirmFields(payload) {
    var billing = (payload && payload.billing) || {};
    var shipping = (payload && payload.shipping) || {};
    function firstNonEmpty() {
      for (var i = 0; i < arguments.length; i++) {
        var v = arguments[i];
        if (v != null && String(v).trim() !== "") return String(v).trim();
      }
      return "";
    }
    var shippingName = firstNonEmpty(
      shipping.sname,
      [shipping.shipping_first_name, shipping.shipping_last_name].filter(Boolean).join(" "),
      [shipping.first_name, shipping.last_name].filter(Boolean).join(" ")
    );
    var shippingNameParts = shippingName.split(/\s+/).filter(Boolean);
    var shippingFirstName = firstNonEmpty(
      shipping.shipping_first_name,
      shipping.first_name,
      shippingNameParts[0] || ""
    );
    var shippingLastName = firstNonEmpty(
      shipping.shipping_last_name,
      shipping.last_name,
      shippingNameParts.length > 1 ? shippingNameParts.slice(1).join(" ") : ""
    );
    var map = {
      // Billing: support normalized keys + DB/raw aliases.
      confirm_first_name: firstNonEmpty(billing.first_name, billing.billing_first_name),
      confirm_last_name: firstNonEmpty(billing.last_name, billing.billing_last_name),
      confirm_email: firstNonEmpty(billing.email, billing.cus_email, billing.billing_email),
      confirm_address1: firstNonEmpty(billing.address1, billing.address_line1, billing.billing_address_line1),
      confirm_address2: firstNonEmpty(billing.address2, billing.address_line2, billing.billing_address_line2),
      confirm_city: firstNonEmpty(billing.city),
      confirm_state: firstNonEmpty(billing.state),
      confirm_zip: firstNonEmpty(billing.zip, billing.zipcode, window.POS_STORE_PINCODE || ""),
      confirm_gstin: firstNonEmpty(billing.gstin),
      confirm_trade_name: firstNonEmpty(billing.trade_name),

      // Shipping: support normalized keys + DB/raw aliases.
      confirm_sfirst_name: shippingFirstName,
      confirm_slast_name: shippingLastName,
      confirm_saddress1: firstNonEmpty(shipping.saddress1, shipping.shipping_address_line1, shipping.address1, shipping.address_line1),
      confirm_saddress2: firstNonEmpty(shipping.saddress2, shipping.shipping_address_line2, shipping.address2, shipping.address_line2),
      confirm_scity: firstNonEmpty(shipping.scity, shipping.shipping_city, shipping.city),
      confirm_sstate: firstNonEmpty(shipping.sstate, shipping.shipping_state, shipping.state),
      confirm_szip: firstNonEmpty(shipping.szip, shipping.shipping_zipcode, shipping.zip, shipping.zipcode),
      confirm_sgstin: firstNonEmpty(shipping.sgstin, shipping.shipping_gstin)
    };
    var billingPhoneFull = firstNonEmpty(
      billing.phone,
      billing.mobile,
      billing.billing_mobile,
      (window.POS_ADDRESS_API_DEFAULTS || {}).confirm_phone || "8031404444"
    );
    var shippingPhoneFull = firstNonEmpty(
      shipping.sphone,
      shipping.shipping_mobile,
      shipping.mobile,
      shipping.phone
    );
    var billingCountryRaw = firstNonEmpty(billing.country, billing.billing_country, "IN");
    var shippingCountryRaw = firstNonEmpty(shipping.scountry, shipping.shipping_country, shipping.country, "IN");
    Object.keys(map).forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.value = map[id];
    });
    setPosCountrySelect("confirm_country", billingCountryRaw);
    setPosCountrySelect("confirm_scountry", shippingCountryRaw);
    var billingCountry = normalizePosCountryCode(document.getElementById("confirm_country")?.value || "IN", document.getElementById("confirm_country"));
    var shippingCountry = normalizePosCountryCode(document.getElementById("confirm_scountry")?.value || "IN", document.getElementById("confirm_scountry"));
    setPosPhoneFields("confirm_phone", "confirm_phone_code", billingPhoneFull, billingCountry);
    setPosPhoneFields("confirm_sphone", "confirm_sphone_code", shippingPhoneFull, shippingCountry);
    var defaultState = String(window.POS_DEFAULT_STATE || "Delhi");
    syncAllPosStateFields({
      billing: map.confirm_state || (isPosIndiaCountry(billingCountry) ? defaultState : ""),
      shipping: map.confirm_sstate || (isPosIndiaCountry(shippingCountry) ? defaultState : "")
    }).then(function() {
      syncHighValueComplianceUi();
    });
    var compliance = (payload && payload.compliance) || {};
    [
      ["customer_residency_status", compliance.customer_residency_status || "INDIAN_RESIDENT"],
      ["customer_pan", compliance.customer_pan || ""],
      ["customer_aadhaar", compliance.customer_aadhaar || ""],
      ["passport_number", compliance.passport_number || ""]
    ].forEach(function(row) {
      var el = document.getElementById(row[0]);
      if (el) el.value = row[1];
    });
    setPosCountrySelect("country_of_residence", compliance.country_of_residence || billingCountry);
    syncResidencyFromBillingCountry(billingCountry, compliance.customer_residency_status);
  }

  var POS_SHIPPING_ADDRESS_FIELD_IDS = [
    "confirm_sfirst_name",
    "confirm_slast_name",
    "confirm_sphone_code",
    "confirm_sphone",
    "confirm_saddress1",
    "confirm_saddress2",
    "confirm_scity",
    "confirm_sstate",
    "confirm_szip",
    "confirm_scountry",
    "confirm_sgstin"
  ];

  var POS_BILLING_TO_SHIPPING_FIELDS = [
    ["confirm_first_name", "confirm_sfirst_name"],
    ["confirm_last_name", "confirm_slast_name"],
    ["confirm_phone_code", "confirm_sphone_code"],
    ["confirm_phone", "confirm_sphone"],
    ["confirm_address1", "confirm_saddress1"],
    ["confirm_address2", "confirm_saddress2"],
    ["confirm_city", "confirm_scity"],
    ["confirm_state", "confirm_sstate"],
    ["confirm_zip", "confirm_szip"],
    ["confirm_country", "confirm_scountry"],
    ["confirm_gstin", "confirm_sgstin"]
  ];

  function isShippingSameAsBillingChecked() {
    var cb = document.getElementById("confirm_shipping_same_as_billing");
    return !!(cb && cb.checked);
  }

  function copyBillingToShippingFields() {
    var billingCountry = document.getElementById("confirm_country");
    var shippingCountry = document.getElementById("confirm_scountry");
    if (billingCountry && shippingCountry) {
      shippingCountry.value = billingCountry.value;
    }
    var billingStateVal = getPosStateValue("confirm_state");
    POS_BILLING_TO_SHIPPING_FIELDS.forEach(function(pair) {
      if (pair[0] === "confirm_country" || pair[0] === "confirm_state") {
        return;
      }
      var billingEl = document.getElementById(pair[0]);
      var shippingEl = document.getElementById(pair[1]);
      if (billingEl && shippingEl) {
        shippingEl.value = billingEl.value;
      }
    });
    syncPosStateField("shipping", billingStateVal);
  }

  function setShippingFieldsSyncedFromBilling(synced) {
    POS_SHIPPING_ADDRESS_FIELD_IDS.forEach(function(id) {
      var el = document.getElementById(id);
      if (el) {
        if (el.tagName === "SELECT") {
          el.disabled = synced;
        } else {
          el.readOnly = synced;
        }
        el.classList.toggle("bg-slate-100", synced);
        el.classList.toggle("cursor-not-allowed", synced);
      }
      var stateSelect = document.getElementById(id + "_select");
      if (stateSelect) {
        stateSelect.disabled = synced;
        stateSelect.classList.toggle("bg-slate-100", synced);
        stateSelect.classList.toggle("cursor-not-allowed", synced);
      }
    });
  }

  function onBillingFieldChangedForShippingSync() {
    if (isShippingSameAsBillingChecked()) {
      copyBillingToShippingFields();
    }
  }

  function setConfirmShippingSameAsBilling(checked) {
    var cb = document.getElementById("confirm_shipping_same_as_billing");
    if (cb) {
      cb.checked = !!checked;
    }
    if (checked) {
      copyBillingToShippingFields();
    }
    setShippingFieldsSyncedFromBilling(!!checked);
  }

  function initConfirmShippingSameAsBilling() {
    var cb = document.getElementById("confirm_shipping_same_as_billing");
    if (!cb || cb.dataset.bound === "1") {
      return;
    }
    cb.dataset.bound = "1";
    cb.addEventListener("change", function() {
      setConfirmShippingSameAsBilling(cb.checked);
    });
    POS_BILLING_TO_SHIPPING_FIELDS.forEach(function(pair) {
      var billingEl = document.getElementById(pair[0]);
      if (!billingEl) return;
      billingEl.addEventListener("input", onBillingFieldChangedForShippingSync);
      billingEl.addEventListener("change", onBillingFieldChangedForShippingSync);
    });
    var billingStateSelect = document.getElementById("confirm_state_select");
    if (billingStateSelect) {
      billingStateSelect.addEventListener("change", onBillingFieldChangedForShippingSync);
    }
  }

  function hasConfirmShippingFieldsFilled() {
    if (isShippingSameAsBillingChecked()) {
      return true;
    }
    for (var i = 0; i < POS_SHIPPING_ADDRESS_FIELD_IDS.length; i++) {
      var el = document.getElementById(POS_SHIPPING_ADDRESS_FIELD_IDS[i]);
      if (el && String(el.value || "").trim() !== "") {
        return true;
      }
    }
    return false;
  }

  function getAddressConfirmPayload() {
    var read = function(id) {
      var el = document.getElementById(id);
      return el ? String(el.value || "").trim() : "";
    };
    var shippingFirstName = read("confirm_sfirst_name");
    var shippingLastName = read("confirm_slast_name");
    var shippingFullName = [shippingFirstName, shippingLastName].filter(Boolean).join(" ").trim();
    var billingPhoneCodeIso = read("confirm_phone_code") || "IN";
    var shippingPhoneCodeIso = read("confirm_sphone_code") || "IN";
    return {
      confirm_address_submit: "1",
      confirm_first_name: read("confirm_first_name"),
      confirm_last_name: read("confirm_last_name"),
      confirm_email: read("confirm_email"),
      confirm_phone: posBuildFullPhone(billingPhoneCodeIso, read("confirm_phone")),
      confirm_phone_code: billingPhoneCodeIso,
      confirm_address1: read("confirm_address1"),
      confirm_address2: read("confirm_address2"),
      confirm_city: read("confirm_city"),
      confirm_state: getPosStateValue("confirm_state"),
      confirm_zip: read("confirm_zip"),
      confirm_country: read("confirm_country"),
      confirm_gstin: read("confirm_gstin"),
      confirm_trade_name: read("confirm_trade_name"),
      confirm_sfirst_name: shippingFirstName,
      confirm_slast_name: shippingLastName,
      // Keep combined name for backward compatibility on server side.
      confirm_sname: shippingFullName,
      confirm_saddress1: read("confirm_saddress1"),
      confirm_saddress2: read("confirm_saddress2"),
      confirm_scity: read("confirm_scity"),
      confirm_sstate: getPosStateValue("confirm_sstate"),
      confirm_szip: read("confirm_szip"),
      confirm_scountry: read("confirm_scountry"),
      confirm_sphone: posBuildFullPhone(shippingPhoneCodeIso, read("confirm_sphone")),
      confirm_sphone_code: shippingPhoneCodeIso,
      confirm_sgstin: read("confirm_sgstin").toUpperCase(),
      confirm_shipping_same_as_billing: isShippingSameAsBillingChecked() ? "1" : "0",
      confirm_omit_shipping_api: "0",
      customer_residency_status: read("customer_residency_status") || "INDIAN_RESIDENT",
      customer_pan: read("customer_pan").replace(/\s+/g, "").toUpperCase(),
      customer_aadhaar: read("customer_aadhaar").replace(/\D/g, ""),
      passport_number: read("passport_number").replace(/\s+/g, "").toUpperCase(),
      country_of_residence: read("country_of_residence"),
      sec269st_cash_warning_confirmed: "0"
    };
  }

  function applyPosCheckoutAddressDefaults(payload) {
    var defaults = window.POS_ADDRESS_API_DEFAULTS || {};
    var out = Object.assign({}, payload);
    Object.keys(defaults).forEach(function(key) {
      if (!String(out[key] || "").trim()) {
        out[key] = defaults[key];
      }
    });
    if (!String(out.confirm_state || "").trim()) {
      out.confirm_state = String(window.POS_DEFAULT_STATE || "Delhi");
    }
    if (!String(out.confirm_zip || "").trim() && window.POS_STORE_PINCODE) {
      out.confirm_zip = String(window.POS_STORE_PINCODE).trim();
    }
    if (!String(out.confirm_phone || "").trim() && defaults.confirm_phone) {
      out.confirm_phone = String(defaults.confirm_phone).trim();
    }
    out = applyShippingSameAsBillingToPayload(out);
    return out;
  }

  function applyShippingSameAsBillingToPayload(payload) {
    var out = Object.assign({}, payload);
    var sameAsBilling = out.confirm_shipping_same_as_billing === "1"
      || out.confirm_shipping_same_as_billing === 1
      || out.confirm_shipping_same_as_billing === true;
    if (!sameAsBilling) {
      return applyBillingFallbacksToShippingPayload(out);
    }
    var pairs = [
      ["confirm_first_name", "confirm_sfirst_name"],
      ["confirm_last_name", "confirm_slast_name"],
      ["confirm_address1", "confirm_saddress1"],
      ["confirm_address2", "confirm_saddress2"],
      ["confirm_city", "confirm_scity"],
      ["confirm_state", "confirm_sstate"],
      ["confirm_zip", "confirm_szip"],
      ["confirm_country", "confirm_scountry"],
      ["confirm_phone", "confirm_sphone"],
      ["confirm_phone_code", "confirm_sphone_code"],
      ["confirm_gstin", "confirm_sgstin"]
    ];
    pairs.forEach(function(pair) {
      var billingVal = String(out[pair[0]] || "").trim();
      if (billingVal !== "") {
        out[pair[1]] = billingVal;
      }
    });
    var sf = String(out.confirm_sfirst_name || "").trim();
    var sl = String(out.confirm_slast_name || "").trim();
    out.confirm_sname = [sf, sl].filter(Boolean).join(" ").trim();
    return out;
  }

  function applyBillingFallbacksToShippingPayload(payload) {
    var out = Object.assign({}, payload);
    var pairs = [
      ["confirm_sfirst_name", "confirm_first_name"],
      ["confirm_slast_name", "confirm_last_name"],
      ["confirm_saddress1", "confirm_address1"],
      ["confirm_saddress2", "confirm_address2"],
      ["confirm_scity", "confirm_city"],
      ["confirm_sstate", "confirm_state"],
      ["confirm_szip", "confirm_zip"],
      ["confirm_scountry", "confirm_country"],
      ["confirm_sphone", "confirm_phone"],
      ["confirm_sgstin", "confirm_gstin"]
    ];
    pairs.forEach(function(pair) {
      if (!String(out[pair[0]] || "").trim()) {
        var billingVal = String(out[pair[1]] || "").trim();
        if (billingVal !== "") {
          out[pair[0]] = billingVal;
        }
      }
    });
    if (!String(out.confirm_sname || "").trim()) {
      out.confirm_sname = [out.confirm_sfirst_name, out.confirm_slast_name].filter(Boolean).join(" ").trim();
    } else if (!String(out.confirm_slast_name || "").trim() && String(out.confirm_last_name || "").trim()) {
      var firstPart = String(out.confirm_sfirst_name || out.confirm_sname || "").trim();
      var lastPart = String(out.confirm_last_name || "").trim();
      out.confirm_slast_name = lastPart;
      out.confirm_sname = [firstPart, lastPart].filter(Boolean).join(" ").trim();
    }
    return out;
  }

  function ensurePosDefaultStateOnForm() {
    var countryEl = document.getElementById("confirm_country");
    var country = countryEl ? normalizePosCountryCode(countryEl.value, countryEl) : "IN";
    if (!isPosIndiaCountry(country)) {
      return;
    }
    var defaultState = String(window.POS_DEFAULT_STATE || "Delhi");
    if (!getPosStateValue("confirm_state")) {
      setPosStateValue("confirm_state", defaultState);
    }
  }

  function setPosFieldInvalid(id, invalid) {
    var el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle("border-red-500", !!invalid);
    el.classList.toggle("ring-1", !!invalid);
    el.classList.toggle("ring-red-200", !!invalid);
  }

  function clearAddressValidationState() {
    ["confirm_first_name", "confirm_last_name", "confirm_phone_code", "confirm_phone", "confirm_zip", "confirm_state", "confirm_state_select", "confirm_email", "confirm_gstin", "confirm_sgstin", "customer_pan", "customer_aadhaar", "passport_number", "country_of_residence"].forEach(function(id) {
      setPosFieldInvalid(id, false);
    });
    POS_SHIPPING_ADDRESS_FIELD_IDS.forEach(function(id) {
      setPosFieldInvalid(id, false);
    });
  }

  function getHighValueLimit() {
    var limit = parseFloat(String(window.POS_HIGH_VALUE_TRANSACTION_LIMIT || "200000"));
    return isFinite(limit) && limit > 0 ? limit : 200000;
  }

  function getCurrentCheckoutTotal() {
    var live = typeof window.getPosCartTotalsForCheckout === "function" ? window.getPosCartTotalsForCheckout() : null;
    var total = live && live.grandTotal != null ? parseFloat(String(live.grandTotal)) : NaN;
    return isFinite(total) ? total : 0;
  }

  function isFullFinalPaymentSelected() {
    var stageEl = document.getElementById("payment_stage");
    var stage = stageEl ? String(stageEl.value || "").toLowerCase() : "";
    var amount = getPaymentSplitTotalFromUi();
    var total = getCurrentCheckoutTotal();
    return stage === "final" && total > 0 && Math.abs(amount - total) <= 0.02;
  }

  function syncCustomInvoiceNumberField() {
    var wrap = document.getElementById("customInvoiceNumberWrap");
    var input = document.getElementById("custom_invoice_number");
    if (!wrap) return;
    var show = isFullFinalPaymentSelected();
    wrap.classList.toggle("hidden", !show);
    if (!show && input) {
      input.value = "";
    }
  }

  function isHighValueTransaction() {
    return getCurrentCheckoutTotal() >= getHighValueLimit() && isFullFinalPaymentSelected();
  }

  function formatInrAmount(amount) {
    try {
      return new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR", maximumFractionDigits: 0 }).format(amount);
    } catch (e) {
      return "₹" + String(amount);
    }
  }

  function syncHighValueComplianceUi() {
    var highValue = isHighValueTransaction();
    var gstin = (document.getElementById("confirm_gstin")?.value || "").trim();
    var residency = (document.getElementById("customer_residency_status")?.value || "INDIAN_RESIDENT").toUpperCase();
    var banner = document.getElementById("highValueComplianceBanner");
    var panel = document.getElementById("highValueCompliancePanel");
    var panWrap = document.getElementById("panComplianceWrap");
    var passportWrap = document.getElementById("passportComplianceWrap");
    var countryWrap = document.getElementById("countryResidenceWrap");
    var panHint = document.getElementById("panComplianceHint");
    var panStar = document.getElementById("panRequiredStar");
    var passportStar = document.getElementById("passportRequiredStar");
    var countryStar = document.getElementById("countryRequiredStar");
    var panVal = (document.getElementById("customer_pan")?.value || "").replace(/\s+/g, "").trim();

    if (banner) {
      banner.textContent = "High Value Transaction – Compliance Required (limit " + formatInrAmount(getHighValueLimit()) + ")";
      banner.classList.toggle("hidden", !highValue);
    }
    if (panel) panel.classList.toggle("hidden", !highValue);
    if (!highValue) {
      updateConfirmAddressButtonState();
      return;
    }

    var hasGstin = gstin !== "";
    if (panWrap) panWrap.classList.toggle("hidden", residency === "FOREIGN_NATIONAL");
    if (passportWrap) passportWrap.classList.toggle("hidden", residency === "INDIAN_RESIDENT");
    if (countryWrap) countryWrap.classList.toggle("hidden", residency === "INDIAN_RESIDENT");
    if (panStar) panStar.classList.toggle("hidden", hasGstin || residency === "FOREIGN_NATIONAL" || (residency === "NRI" && panVal !== ""));
    if (passportStar) passportStar.classList.toggle("hidden", residency === "INDIAN_RESIDENT" || (residency === "NRI" && panVal !== ""));
    if (countryStar) countryStar.classList.toggle("hidden", residency === "INDIAN_RESIDENT" || (residency === "NRI" && panVal !== ""));
    if (panHint) {
      panHint.textContent = hasGstin
        ? "GSTIN present. PAN will be derived automatically for B2B invoice handling."
        : (residency === "NRI" ? "For NRI, enter PAN or Passport Number with Country of Residence." : "PAN is required unless GSTIN is entered.");
    }
    updateConfirmAddressButtonState();
  }

  function isHighValueComplianceDataComplete() {
    if (!isHighValueTransaction()) return true;
    var gstin = (document.getElementById("confirm_gstin")?.value || "").trim().toUpperCase();
    if (gstin !== "") {
      return /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/.test(gstin);
    }
    var residency = (document.getElementById("customer_residency_status")?.value || "INDIAN_RESIDENT").toUpperCase();
    var pan = (document.getElementById("customer_pan")?.value || "").replace(/\s+/g, "").toUpperCase();
    var aadhaar = (document.getElementById("customer_aadhaar")?.value || "").replace(/\D/g, "");
    var passport = (document.getElementById("passport_number")?.value || "").replace(/\s+/g, "").toUpperCase();
    var countryResidence = (document.getElementById("country_of_residence")?.value || "").trim();
    var panOk = pan === "" || /^[A-Z]{5}[0-9]{4}[A-Z]$/.test(pan);
    var passportOk = passport === "" || passport.length >= 6;
    if (!panOk || !passportOk) return false;
    if (residency === "INDIAN_RESIDENT") return pan !== "";
    if (residency === "NRI") return pan !== "" || (passport.length >= 6 && countryResidence !== "");
    return passport.length >= 6 && countryResidence !== "";
  }

  function validateHighValueCompliancePayload() {
    if (!isHighValueTransaction()) {
      return { ok: true, message: "" };
    }
    if (isHighValueComplianceDataComplete()) {
      return { ok: true, message: "" };
    }
    var gstin = (document.getElementById("confirm_gstin")?.value || "").trim().toUpperCase();
    var residency = (document.getElementById("customer_residency_status")?.value || "INDIAN_RESIDENT").toUpperCase();
    var pan = (document.getElementById("customer_pan")?.value || "").replace(/\s+/g, "").toUpperCase();
    var passport = (document.getElementById("passport_number")?.value || "").replace(/\s+/g, "").toUpperCase();
    var countryResidence = (document.getElementById("country_of_residence")?.value || "").trim();
    var message = "High value transaction compliance is incomplete.";
    if (gstin !== "" && !/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/.test(gstin)) {
      message = "GSTIN format is invalid.";
      setPosFieldInvalid("confirm_gstin", true);
    } else if (pan !== "" && !/^[A-Z]{5}[0-9]{4}[A-Z]$/.test(pan)) {
      message = "PAN format is invalid.";
      setPosFieldInvalid("customer_pan", true);
    } else if (residency === "INDIAN_RESIDENT" && gstin === "" && pan === "") {
      message = "PAN is required for Indian resident high value transactions.";
      setPosFieldInvalid("customer_pan", true);
    } else if (residency === "NRI" && gstin === "" && pan === "" && (passport.length < 6 || countryResidence === "")) {
      message = "For NRI customers, enter PAN or Passport Number with Country of Residence.";
      if (passport.length < 6) setPosFieldInvalid("passport_number", true);
      if (countryResidence === "") setPosFieldInvalid("country_of_residence", true);
    } else if (residency === "FOREIGN_NATIONAL" && gstin === "") {
      if (passport.length < 6) {
        message = "Passport Number is required for foreign national high value transactions.";
        setPosFieldInvalid("passport_number", true);
      } else if (countryResidence === "") {
        message = "Country of Residence is required for foreign national high value transactions.";
        setPosFieldInvalid("country_of_residence", true);
      }
    }
    return { ok: false, message: message };
  }

  function updateConfirmAddressButtonState() {
    var btn = document.getElementById("confirmAddressSubmitBtn");
    if (!btn) return;
    btn.disabled = false;
    btn.classList.remove("opacity-50", "cursor-not-allowed");
    btn.title = "";
  }

  function posCountryPhoneCode(iso) {
    var code = String(iso || "").trim().toUpperCase().substring(0, 2);
    var map = window.POS_COUNTRY_PHONE_CODES || {};
    return String(map[code] || "").replace(/\D/g, "");
  }

  function posNormalizeCountryIso(iso) {
    return String(iso || "IN").trim().toUpperCase().substring(0, 2);
  }

  function posPhoneDigits(phone) {
    return String(phone || "").replace(/\D/g, "");
  }

  function posBuildFullPhone(codeIso, localPhone) {
    var local = posPhoneDigits(localPhone);
    if (!local) {
      return "";
    }
    var iso = posNormalizeCountryIso(codeIso);
    var dial = posCountryPhoneCode(iso);
    if (!dial) {
      return local;
    }
    if (local.indexOf(dial) === 0) {
      return local;
    }
    return dial + local;
  }

  function posSplitPhoneForCountry(fullPhone, countryIso) {
    var digits = posPhoneDigits(fullPhone);
    var country = posNormalizeCountryIso(countryIso);
    if (!digits) {
      return { codeIso: country, local: "" };
    }
    var expected = posCountryPhoneCode(country);
    if (expected && digits.indexOf(expected) === 0) {
      return { codeIso: country, local: digits.substring(expected.length) };
    }
    var map = window.POS_COUNTRY_PHONE_CODES || {};
    var matchedIso = "";
    Object.keys(map).forEach(function(iso) {
      if (matchedIso) {
        return;
      }
      var dial = String(map[iso] || "").replace(/\D/g, "");
      if (dial && digits.indexOf(dial) === 0) {
        matchedIso = iso;
      }
    });
    if (matchedIso) {
      return {
        codeIso: matchedIso,
        local: digits.substring(posCountryPhoneCode(matchedIso).length)
      };
    }
    if (country === "IN" && digits.length === 10 && /^[6-9]/.test(digits)) {
      return { codeIso: "IN", local: digits };
    }
    if (country === "US" && digits.length === 10) {
      return { codeIso: "US", local: digits };
    }
    return { codeIso: country, local: digits };
  }

  function setPosPhoneFields(localInputId, codeSelectId, fullPhone, countryIso) {
    var split = posSplitPhoneForCountry(fullPhone, countryIso);
    var phoneEl = document.getElementById(localInputId);
    var codeEl = document.getElementById(codeSelectId);
    if (phoneEl) {
      phoneEl.value = split.local;
    }
    if (codeEl) {
      if (codeEl.querySelector('option[value="' + split.codeIso + '"]')) {
        codeEl.value = split.codeIso;
      } else {
        codeEl.value = "IN";
      }
    }
  }

  function syncPosPhoneCodeFromCountry(countryIso, codeSelectId) {
    var codeEl = document.getElementById(codeSelectId);
    if (!codeEl) {
      return;
    }
    var iso = posNormalizeCountryIso(countryIso);
    if (codeEl.querySelector('option[value="' + iso + '"]')) {
      codeEl.value = iso;
    }
  }

  function posPhoneMatchesCountry(phone, countryIso) {
    var digits = posPhoneDigits(phone);
    var country = String(countryIso || "").trim().toUpperCase().substring(0, 2);
    if (!digits || !country) {
      return { ok: true };
    }
    var expected = posCountryPhoneCode(country);
    if (!expected) {
      return { ok: true };
    }
    if (digits.indexOf(expected) === 0) {
      return { ok: true };
    }
    if (country === "IN" && digits.length === 10 && /^[6-9]/.test(digits)) {
      return { ok: true };
    }
    if (country === "US" && digits.length === 10) {
      return { ok: true };
    }
    return {
      ok: false,
      message: "Phone country code must match " + posCountryDisplayName(country)
        + " (+" + expected + " or a valid local number)."
    };
  }

  function validateAddressConfirmPayload(payload) {
    clearAddressValidationState();
    var missing = [];
    var firstInvalidId = "";
    var firstName = String(payload.confirm_first_name || "").trim();
    var lastName = String(payload.confirm_last_name || "").trim();
    var state = String(payload.confirm_state || "").trim();
    var zip = String(payload.confirm_zip || "").trim();
    if (!firstName) {
      missing.push("First name");
      setPosFieldInvalid("confirm_first_name", true);
      firstInvalidId = "confirm_first_name";
    }
    if (!lastName) {
      missing.push("Last name");
      setPosFieldInvalid("confirm_last_name", true);
      if (!firstInvalidId) firstInvalidId = "confirm_last_name";
    }
    if (!zip) {
      missing.push("ZIP / Pincode");
      setPosFieldInvalid("confirm_zip", true);
      if (!firstInvalidId) firstInvalidId = "confirm_zip";
    }
    var phone = String(payload.confirm_phone || "").trim();
    var billingCountry = posNormalizeCountryIso(payload.confirm_country || "IN");
    var billingPhoneCode = posNormalizeCountryIso(payload.confirm_phone_code || "IN");
    if (!phone) {
      missing.push("Phone");
      setPosFieldInvalid("confirm_phone", true);
      setPosFieldInvalid("confirm_phone_code", true);
      if (!firstInvalidId) firstInvalidId = "confirm_phone";
    } else if (billingPhoneCode !== billingCountry) {
      setPosFieldInvalid("confirm_phone", true);
      setPosFieldInvalid("confirm_phone_code", true);
      if (!firstInvalidId) firstInvalidId = "confirm_phone";
      showAddressConfirmValidationError("Billing: Phone country code must match billing country.");
      var billingPhoneEl = document.getElementById("confirm_phone");
      if (billingPhoneEl) billingPhoneEl.focus();
      return false;
    } else {
      var billingPhoneCheck = posPhoneMatchesCountry(phone, billingCountry);
      if (!billingPhoneCheck.ok) {
        setPosFieldInvalid("confirm_phone", true);
        setPosFieldInvalid("confirm_phone_code", true);
        if (!firstInvalidId) firstInvalidId = "confirm_phone";
        showAddressConfirmValidationError("Billing: " + billingPhoneCheck.message);
        var billingPhoneEl = document.getElementById("confirm_phone");
        if (billingPhoneEl) billingPhoneEl.focus();
        return false;
      }
    }
    if (!state) {
      missing.push("State");
      var stateSelect = document.getElementById("confirm_state_select");
      var stateInput = document.getElementById("confirm_state");
      if (stateSelect && !stateSelect.classList.contains("hidden")) {
        setPosFieldInvalid("confirm_state_select", true);
        if (!firstInvalidId) firstInvalidId = "confirm_state_select";
      } else if (stateInput) {
        setPosFieldInvalid("confirm_state", true);
        if (!firstInvalidId) firstInvalidId = "confirm_state";
      }
    }

    var shippingGstin = String(payload.confirm_sgstin || "").trim().toUpperCase();
    if (shippingGstin !== "" && !/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/.test(shippingGstin)) {
      setPosFieldInvalid("confirm_sgstin", true);
      showAddressConfirmValidationError("Shipping GSTIN format is invalid.");
      var sgstinEl = document.getElementById("confirm_sgstin");
      if (sgstinEl) sgstinEl.focus();
      return false;
    }

    if (missing.length) {
      var message = "Please complete: " + missing.slice(0, 6).join(", ") + (missing.length > 6 ? " and " + (missing.length - 6) + " more" : "") + ".";
      showAddressConfirmValidationError(message);
      var first = firstInvalidId ? document.getElementById(firstInvalidId) : null;
      if (first) first.focus();
      return false;
    }

    if (!isShippingSameAsBillingChecked()) {
      var shippingPhone = String(payload.confirm_sphone || "").trim();
      var shippingCountry = posNormalizeCountryIso(payload.confirm_scountry || "IN");
      var shippingPhoneCode = posNormalizeCountryIso(payload.confirm_sphone_code || "IN");
      if (shippingPhone) {
        if (shippingPhoneCode !== shippingCountry) {
          setPosFieldInvalid("confirm_sphone", true);
          setPosFieldInvalid("confirm_sphone_code", true);
          showAddressConfirmValidationError("Shipping: Phone country code must match shipping country.");
          var shippingPhoneEl = document.getElementById("confirm_sphone");
          if (shippingPhoneEl) shippingPhoneEl.focus();
          return false;
        }
        var shippingPhoneCheck = posPhoneMatchesCountry(shippingPhone, shippingCountry);
        if (!shippingPhoneCheck.ok) {
          setPosFieldInvalid("confirm_sphone", true);
          setPosFieldInvalid("confirm_sphone_code", true);
          showAddressConfirmValidationError("Shipping: " + shippingPhoneCheck.message);
          var shippingPhoneEl = document.getElementById("confirm_sphone");
          if (shippingPhoneEl) shippingPhoneEl.focus();
          return false;
        }
      }
    }

    var complianceCheck = validateHighValueCompliancePayload();
    if (!complianceCheck.ok) {
      showAddressConfirmValidationError(complianceCheck.message);
      syncHighValueComplianceUi();
      showToast("⚠ " + complianceCheck.message, "red");
      return false;
    }

    hideAddressConfirmValidationError();
    return true;
  }

  function loadAndOpenAddressConfirm(customerId) {
    fetch("index.php?page=pos_register&action=customer-order-info&customer_id=" + encodeURIComponent(customerId), {
      credentials: "same-origin",
      headers: { "Accept": "application/json" }
    })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (!data || !data.success) {
          showToast("Could not load customer address info.", "red");
          return;
        }
        setAddressConfirmFields(data);
        setConfirmShippingSameAsBilling(false);
        initConfirmShippingSameAsBilling();
        ensurePosDefaultStateOnForm();
        openAddressConfirmModal();
      })
      .catch(function() {
        showToast("Could not load customer address info.", "red");
      });
  }

  function showPaymentModalOrderApiRecord(debug) {
    var panel = document.getElementById("paymentModalOrderApiPanel");
    var pre = document.getElementById("paymentModalOrderApiPre");
    if (!panel || !pre) {
      return;
    }
    pre.textContent = formatOrderCreateDebugText(debug);
    panel.classList.remove("hidden");
  }

  function posBashQuote(v) {
    return "'" + String(v == null ? "" : v).replace(/'/g, "'\"'\"'") + "'";
  }

  function buildOrderCreateCurl(request) {
    if (!request) {
      return "";
    }
    var endpoint = String(request.endpoint || "/order/create");
    var query = request.query || {};
    var body = request.body || {};
    var queryParts = [];
    Object.keys(query).forEach(function(k) {
      if (query[k] != null && String(query[k]) !== "") {
        queryParts.push(encodeURIComponent(k) + "=" + encodeURIComponent(String(query[k])));
      }
    });
    var url = endpoint + (queryParts.length ? ("?" + queryParts.join("&")) : "");
    var lines = [
      "curl --location --request " + String(request.method || "POST").toUpperCase() + " " + posBashQuote(url),
      "--header " + posBashQuote("Content-Type: application/x-www-form-urlencoded")
    ];
    Object.keys(body).forEach(function(k) {
      lines.push("--data-urlencode " + posBashQuote(k + "=" + String(body[k] == null ? "" : body[k])));
    });
    return lines.join(" \\\n  ");
  }

  function formatOrderCreateDebugText(debug) {
    if (!debug) {
      return "No order-create debug stored yet.";
    }
    var request = debug.request || null;
    var response = debug.response || null;
    var requestJsonObj = request || {
      endpoint: "/order/create",
      method: "POST",
      query: {},
      body: {}
    };
    var responseJsonObj = response || {
      http_code: debug.http_code || "",
      data: debug.data || {},
      raw_snippet: debug.raw_snippet || ""
    };
    var lines = [];
    lines.push("at: " + String(debug.at || ""));
    lines.push("");
    lines.push("REQUEST JSON");
    lines.push("------------");
    try {
      lines.push(JSON.stringify(requestJsonObj, null, 2));
    } catch (e1) {
      lines.push(String(requestJsonObj));
    }
    lines.push("");
    lines.push("RESPONSE JSON");
    lines.push("-------------");
    try {
      lines.push(JSON.stringify(responseJsonObj, null, 2));
    } catch (e2) {
      lines.push(String(responseJsonObj));
    }
    lines.push("");
    lines.push("CURL");
    lines.push("----");
    lines.push(buildOrderCreateCurl(requestJsonObj) || "N/A");
    return lines.join("\n");
  }
</script>
<script>
  document.addEventListener("DOMContentLoaded", function() {

    if (window.location.hash === "#checkout") {
      window.__posOpenCheckoutAfterCartLoad = true;
    }

    var fullOrderApiBtn = document.getElementById("paymentModalOrderApiFullBtn");
    if (fullOrderApiBtn) {
      fullOrderApiBtn.addEventListener("click", function () {
        if (typeof window.openOrderCreateApiResponseModal === "function") {
          window.openOrderCreateApiResponseModal();
        }
      });
    }

    var paymentSplitAddBtn = document.getElementById("payment_split_add_btn");
    if (paymentSplitAddBtn) {
      paymentSplitAddBtn.addEventListener("click", function() {
        addPaymentSplitRow("cash", "", "");
      });
    }
    var paymentStageEl = document.getElementById("payment_stage");
    if (paymentStageEl) {
      paymentStageEl.addEventListener("change", recalcPaymentSplitUi);
    }

    var paymentDateInput = document.getElementById("payment_date");
    if (paymentDateInput && typeof posPaymentDateLocalYmd === "function") {
      paymentDateInput.addEventListener("input", function () {
        var t = posPaymentDateLocalYmd();
        if (paymentDateInput.value && paymentDateInput.value > t) {
          paymentDateInput.value = t;
        }
      });
    }

    var placeOrderBtn = document.getElementById("placeOrderBtn");
    if (placeOrderBtn) {
      placeOrderBtn.addEventListener("click", function() {

        var customerId = getSelectedCustomerId();

        if (!customerId) {
          if (typeof window.showPosMessageModal === "function") {
            window.showPosMessageModal({
              title: "Customer required",
              message: "Please select customer first",
              tone: "warning",
              onClose: function () {
                if (typeof window.focusPosCustomerSelect === "function") {
                  window.focusPosCustomerSelect();
                } else if (typeof jQuery !== "undefined" && jQuery("#customerSelect").data("select2")) {
                  jQuery("#customerSelect").select2("open");
                } else {
                  var cs = document.getElementById("customerSelect");
                  if (cs) cs.focus();
                }
              }
            });
          } else {
            showToast("Please select customer first", "red");
            if (typeof jQuery !== "undefined" && jQuery("#customerSelect").data("select2")) {
              jQuery("#customerSelect").select2("open");
            } else {
              var cs = document.getElementById("customerSelect");
              if (cs) cs.focus();
            }
          }
          return;
        }

        var liveT = typeof window.getPosCartTotalsForCheckout === "function" ? window.getPosCartTotalsForCheckout() : null;
        var grandTotal = liveT && liveT.grandTotal != null && !isNaN(parseFloat(String(liveT.grandTotal)))
          ? parseFloat(String(liveT.grandTotal))
          : parseFloat("<?= (float)($cartData['grand_total'] ?? 0) ?>");

        function proceedToCheckoutStep(opts) {
          opts = opts || {};
          var payInfo = validatePaymentSplitsForCheckout(grandTotal, {
            skip269stConfirm: !!opts.skip269stConfirm,
            on269stConfirmed: function() {
              proceedToCheckoutStep({ skip269stConfirm: true });
            }
          });
          if (!payInfo) {
            return;
          }

          var payDateEl = document.getElementById("payment_date");
          if (payDateEl && payDateEl.value) {
            var todayYmd = posPaymentDateLocalYmd();
            if (payDateEl.value > todayYmd) {
              showToast("⚠ Payment date cannot be in the future", "red");
              payDateEl.value = todayYmd;
              payDateEl.focus();
              return;
            }
          }

          loadAndOpenAddressConfirm(customerId);
        }

        proceedToCheckoutStep();
      });
    }

    initConfirmShippingSameAsBilling();

    document.querySelectorAll('#deliveryStatusModal input[name="pos_delivery_status"]').forEach(function(el) {
      el.addEventListener("change", function() {
        syncDeliveryStatusOptionStyles();
        syncEwayBillSectionVisibility();
      });
    });

    var deliveryStatusBackBtn = document.getElementById("deliveryStatusBackBtn");
    if (deliveryStatusBackBtn) {
      deliveryStatusBackBtn.addEventListener("click", function() {
        closeDeliveryStatusModal();
        setPosCheckoutLoading(false);
      });
    }

    // E-way bill checkbox handler
    var generateEwbCheckbox = document.getElementById("generate_ewb_for_delivery");
    var ewayBillFields = document.getElementById("ewayBillFields");
    if (generateEwbCheckbox && ewayBillFields) {
      generateEwbCheckbox.addEventListener("change", function() {
        if (this.checked) {
          ewayBillFields.classList.remove("hidden");
          syncTransportModeFields();
        } else {
          ewayBillFields.classList.add("hidden");
          // Reset all fields when unchecked
          document.getElementById("delivery_veh_no").value = "";
          document.getElementById("delivery_veh_type").value = "";
          document.getElementById("delivery_trans_doc_no").value = "";
          document.getElementById("delivery_trans_doc_date").value = "";
          document.getElementById("delivery_ship_veh_no").value = "";
          document.getElementById("delivery_ship_trans_doc_no").value = "";
          document.getElementById("delivery_ship_trans_doc_date").value = "";
        }
      });
    }

    // Transport mode field handler
    var transportModeSelect = document.getElementById("delivery_veh_type");
    if (transportModeSelect) {
      transportModeSelect.addEventListener("change", function() {
        syncTransportModeFields();
      });
    }

    var deliveryStatusSubmitBtn = document.getElementById("deliveryStatusSubmitBtn");
    if (deliveryStatusSubmitBtn) {
      deliveryStatusSubmitBtn.addEventListener("click", function() {
        var status = getSelectedPosDeliveryStatus();
        var err = document.getElementById("deliveryStatusValidation");
        if (!status) {
          if (err) {
            err.textContent = "Please select a delivery status.";
            err.classList.remove("hidden");
          }
          return;
        }

        // Validate E-way bill fields if checkbox is checked
        var generateEwb = document.getElementById("generate_ewb_for_delivery") && document.getElementById("generate_ewb_for_delivery").checked;
        if (generateEwb) {
          var transportMode = (document.getElementById("delivery_veh_type").value || "").trim();
          
          if (!transportMode) {
            if (err) {
              err.textContent = "⚠ Please select a transport mode.";
              err.classList.remove("hidden");
            }
            document.getElementById("delivery_veh_type").focus();
            return;
          }

          // Validate Road mode fields (required)
          if (transportMode === "1") {
            var vehNo = (document.getElementById("delivery_veh_no").value || "").trim();
            if (!vehNo) {
              if (err) {
                err.textContent = "⚠ Vehicle Number is required for Road transport.";
                err.classList.remove("hidden");
              }
              document.getElementById("delivery_veh_no").focus();
              return;
            }
          }
          // Validate Rail/Air mode fields (required)
          else if (transportMode === "2" || transportMode === "3") {
            var transModeLabel = transportMode === "2" ? "Rail" : "Air";
            var transDocNo = (document.getElementById("delivery_trans_doc_no").value || "").trim();
            var transDocDate = (document.getElementById("delivery_trans_doc_date").value || "").trim();
            
            if (!transDocNo) {
              if (err) {
                err.textContent = "⚠ Transport Document Number is required for " + transModeLabel + " transport.";
                err.classList.remove("hidden");
              }
              document.getElementById("delivery_trans_doc_no").focus();
              return;
            }
            
            if (!transDocDate) {
              if (err) {
                err.textContent = "⚠ Document Date is required for " + transModeLabel + " transport.";
                err.classList.remove("hidden");
              }
              document.getElementById("delivery_trans_doc_date").focus();
              return;
            }
          }
          // Validate Ship/Road cum Ship mode fields (flexible - at least one set required)
          else if (transportMode === "4" || transportMode === "5") {
            var shipVehNo = (document.getElementById("delivery_ship_veh_no").value || "").trim();
            var shipTransDocNo = (document.getElementById("delivery_ship_trans_doc_no").value || "").trim();
            var shipTransDocDate = (document.getElementById("delivery_ship_trans_doc_date").value || "").trim();
            
            // At least one field must be provided
            if (!shipVehNo && !shipTransDocNo && !shipTransDocDate) {
              if (err) {
                err.textContent = "⚠ Provide either Vehicle Number or Transport Document Number and Date (or both).";
                err.classList.remove("hidden");
              }
              document.getElementById("delivery_ship_veh_no").focus();
              return;
            }
            
            // If transport doc is partially filled, both fields are required
            if ((shipTransDocNo !== "" && shipTransDocDate === "") || (shipTransDocNo === "" && shipTransDocDate !== "")) {
              if (err) {
                err.textContent = "⚠ Both Transport Document Number and Date must be provided together.";
                err.classList.remove("hidden");
              }
              return;
            }
          }
        }

        if (!pendingAddressPayloadForCheckout) {
          showToast("Address details missing — go back and confirm billing/shipping.", "red");
          return;
        }
        var payload = Object.assign({}, pendingAddressPayloadForCheckout, { pos_delivery_status: status });
        if (needsOverseasGstConfirmation(payload)) {
          openOverseasGstModal(payload);
          return;
        }
        closeDeliveryStatusModal();
        
        // Add E-way bill data if checked
        if (generateEwb) {
          payload.generate_ewb = "1";
          payload.ewb_veh_type = document.getElementById("delivery_veh_type").value;
          
          // Road mode: vehicle number
          if (payload.ewb_veh_type === "1") {
            payload.ewb_veh_no = document.getElementById("delivery_veh_no").value;
          }
          // Rail/Air mode: transport document details
          else if (payload.ewb_veh_type === "2" || payload.ewb_veh_type === "3") {
            payload.ewb_trans_doc_no = document.getElementById("delivery_trans_doc_no").value;
            payload.ewb_trans_doc_date = document.getElementById("delivery_trans_doc_date").value;
          }
          // Ship/Road cum Ship mode: flexible fields
          else if (payload.ewb_veh_type === "4" || payload.ewb_veh_type === "5") {
            payload.ewb_ship_veh_no = document.getElementById("delivery_ship_veh_no").value;
            payload.ewb_ship_trans_doc_no = document.getElementById("delivery_ship_trans_doc_no").value;
            payload.ewb_ship_trans_doc_date = document.getElementById("delivery_ship_trans_doc_date").value;
          }
        }
        
        createOrderNow(payload);
      });
    }

    var overseasGstBackBtn = document.getElementById("overseasGstBackBtn");
    if (overseasGstBackBtn) {
      overseasGstBackBtn.addEventListener("click", function() {
        closeOverseasGstModal();
      });
    }
    var overseasGstNoBtn = document.getElementById("overseasGstNoBtn");
    if (overseasGstNoBtn) {
      overseasGstNoBtn.addEventListener("click", function() {
        submitCheckoutWithExportGst(false);
      });
    }
    var overseasGstYesBtn = document.getElementById("overseasGstYesBtn");
    if (overseasGstYesBtn) {
      overseasGstYesBtn.addEventListener("click", function() {
        submitCheckoutWithExportGst(true);
      });
    }

    var localFallbackCancelBtn = document.getElementById("localFallbackCancelBtn");
    if (localFallbackCancelBtn) {
      localFallbackCancelBtn.addEventListener("click", function() {
        var resumePayload = pendingLocalFallbackCheckoutPayload;
        closeLocalFallbackConfirmModal();
        if (resumePayload) {
          openDeliveryStatusModal(resumePayload);
        }
      });
    }
    var localFallbackConfirmBtn = document.getElementById("localFallbackConfirmBtn");
    if (localFallbackConfirmBtn) {
      localFallbackConfirmBtn.addEventListener("click", function() {
        if (!pendingLocalFallbackCheckoutPayload) {
          showPosCheckoutErrorBanner("Checkout details missing — please try again from delivery status.");
          closeLocalFallbackConfirmModal();
          return;
        }
        localFallbackConfirmBtn.disabled = true;
        localFallbackConfirmBtn.textContent = "Saving…";
        createOrderNow(pendingLocalFallbackCheckoutPayload, { confirmLocalFallback: true, keepLocalFallbackModalOpen: true });
      });
    }

    var localFallbackApiErrorDismiss = document.getElementById("localFallbackApiErrorDismiss");
    if (localFallbackApiErrorDismiss) {
      localFallbackApiErrorDismiss.addEventListener("click", function() {
        var errBox = document.getElementById("localFallbackApiErrorBox");
        if (errBox) {
          errBox.classList.add("hidden");
        }
      });
    }

    var paymentSplitValidationDismiss = document.getElementById("payment_split_validation_dismiss");
    if (paymentSplitValidationDismiss) {
      paymentSplitValidationDismiss.addEventListener("click", function() {
        var box = document.getElementById("payment_split_validation");
        var boxText = document.getElementById("payment_split_validation_text");
        if (box) {
          box.classList.add("hidden");
        }
        if (boxText) {
          boxText.textContent = "";
        }
      });
    }

    var posCheckoutErrorBannerDismiss = document.getElementById("posCheckoutErrorBannerDismiss");
    if (posCheckoutErrorBannerDismiss) {
      posCheckoutErrorBannerDismiss.addEventListener("click", hidePosCheckoutErrorBanner);
    }

    var addressConfirmValidationDismiss = document.getElementById("addressConfirmValidationDismiss");
    if (addressConfirmValidationDismiss) {
      addressConfirmValidationDismiss.addEventListener("click", hideAddressConfirmValidationError);
    }

    var confirmAddressSubmitBtn = document.getElementById("confirmAddressSubmitBtn");
    if (confirmAddressSubmitBtn) {
      confirmAddressSubmitBtn.addEventListener("click", function() {
        if (isShippingSameAsBillingChecked()) {
          copyBillingToShippingFields();
        }
        ensurePosDefaultStateOnForm();
        var payload = getAddressConfirmPayload();
        if (!validateAddressConfirmPayload(payload)) {
          return;
        }
        payload = applyPosCheckoutAddressDefaults(payload);
        openDeliveryStatusModal(payload);
      });
    }

    document.querySelectorAll("#addressConfirmModal input").forEach(function(el) {
      el.addEventListener("input", function() {
        setPosFieldInvalid(el.id, false);
        syncHighValueComplianceUi();
      });
    });
    ["confirm_country", "confirm_scountry"].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) {
        el.addEventListener("change", function() {
          setPosFieldInvalid(id, false);
          if (id === "confirm_country") {
            syncPosPhoneCodeFromCountry(el.value, "confirm_phone_code");
            setPosCountrySelect("country_of_residence", el.value);
            syncResidencyFromBillingCountry(el.value);
            syncPosStateField("billing", "").then(function() {
              if (isShippingSameAsBillingChecked()) {
                copyBillingToShippingFields();
              }
            });
          } else {
            syncPosPhoneCodeFromCountry(el.value, "confirm_sphone_code");
            syncPosStateField("shipping", "");
          }
        });
      }
    });
    ["confirm_phone_code", "confirm_sphone_code"].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) {
        el.addEventListener("change", function() {
          setPosFieldInvalid(id, false);
          setPosFieldInvalid(id === "confirm_phone_code" ? "confirm_phone" : "confirm_sphone", false);
          if (id === "confirm_phone_code" && isShippingSameAsBillingChecked()) {
            copyBillingToShippingFields();
          }
        });
      }
    });
    syncAllPosStateFields().then(function() {
      ensurePosDefaultStateOnForm();
    });
    ["customer_residency_status", "confirm_gstin", "customer_pan", "passport_number", "country_of_residence"].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) {
        el.addEventListener("change", syncHighValueComplianceUi);
        el.addEventListener("input", syncHighValueComplianceUi);
      }
    });
  });

  function createOrderNow(addressPayload, checkoutOptions) {
    checkoutOptions = checkoutOptions || {};
    if (posCheckoutLoadingActive) {
      return;
    }
    var customerId = getSelectedCustomerId();
    var live = typeof window.getPosCartTotalsForCheckout === "function" ? window.getPosCartTotalsForCheckout() : null;
    var disc =
      typeof window.getPosReceiptDiscountsForCheckout === "function"
        ? window.getPosReceiptDiscountsForCheckout()
        : null;
    var waivedFollowUp =
      typeof window.isPosFollowUpWaivedCheckout === "function" && window.isPosFollowUpWaivedCheckout();
    var orderTotal =
      disc && disc.grandTotal > 0
        ? disc.grandTotal
        : live && live.grandTotal != null
          ? parseFloat(String(live.grandTotal))
          : NaN;
    var payStage = document.getElementById("payment_stage").value;
    var paySplits = collectPaymentSplitsFromUi();
    var payAmt = getPaymentSplitTotalFromUi();
    if (waivedFollowUp) {
      orderTotal = 0;
      payStage = "final";
      paySplits = [{ mode: "waived", amount: 0, transaction_id: "" }];
      payAmt = 0;
    } else if (!isFinite(orderTotal) || orderTotal <= 0) {
      showToast("Cart total unavailable — add items or refresh the cart.", "red");
      return;
    }
    var primarySplit = paySplits.reduce(function(best, s) {
      return s.amount > best.amount ? s : best;
    }, paySplits[0] || { mode: "cash", amount: 0, transaction_id: "" });
    var payMode = waivedFollowUp ? "waived" : (primarySplit.mode || "cash");
    var txn = primarySplit.transaction_id || "";
    var note = (document.getElementById("payment_note") && document.getElementById("payment_note").value) || "";
    var subTotalGoods = live && live.subtotal != null ? parseFloat(String(live.subtotal)) : NaN;
    var gstTotal = live && live.gstTotal != null ? parseFloat(String(live.gstTotal)) : NaN;
    var couponDeduction = disc ? disc.couponDeduction : (live && live.couponDeduction != null ? parseFloat(String(live.couponDeduction)) : NaN);
    var customDeduction = disc ? disc.customDeduction : (live && live.customDeduction != null ? parseFloat(String(live.customDeduction)) : NaN);
    var giftDeduction = disc ? disc.giftDeduction : (live && live.giftDeduction != null ? parseFloat(String(live.giftDeduction)) : NaN);
    var lineDiscount = disc ? disc.lineDiscount : 0;
    var customDiscMeta =
      typeof window.getPosCustomDiscountMetaForCheckout === "function"
        ? window.getPosCustomDiscountMetaForCheckout()
        : null;
    var customInvoiceEl = document.getElementById("custom_invoice_number");
    var customInvoiceNumber = isFullFinalPaymentSelected() && customInvoiceEl
      ? (customInvoiceEl.value || "").trim()
      : "";
    var body = Object.assign({}, addressPayload, {
      customer_id: String(customerId),
      payment_stage: payStage,
      payment_mode: payMode,
      payment_amount: payAmt,
      payment_splits: paySplits,
      transaction_id: txn,
      payment_note: note,
      order_total: orderTotal,
      receipt_subtotal_goods: isFinite(subTotalGoods) ? subTotalGoods : orderTotal,
      receipt_gst_total: isFinite(gstTotal) ? gstTotal : 0,
      receipt_coupon_discount: isFinite(couponDeduction) ? couponDeduction : 0,
      receipt_cash_discount: isFinite(customDeduction) ? customDeduction : 0,
      receipt_gift_discount: isFinite(giftDeduction) ? giftDeduction : 0,
      receipt_line_discount: lineDiscount > 0 ? lineDiscount : 0,
      receipt_discounts_absorbed: !!(live && live.cartDiscountAbsorbed),
      custom_discount_mode: customDiscMeta ? customDiscMeta.mode : "",
      custom_discount_value: customDiscMeta ? customDiscMeta.value : 0,
      coupon_display_name: live && live.couponDisplayName ? String(live.couponDisplayName) : ""
    });
    if (customInvoiceNumber !== "") {
      body.custom_invoice_number = customInvoiceNumber;
    }
    var cashLeg269 = paySplits.some(function(s) {
      return s.mode === "cash" && s.amount + 0.02 >= getHighValueLimit();
    });
    if (cashLeg269) {
      body.sec269st_cash_warning_confirmed = "1";
    }
    var stockWarnings =
      typeof window.getPosLocalStockWarningsForCheckout === "function"
        ? window.getPosLocalStockWarningsForCheckout()
        : [];
    if (Array.isArray(stockWarnings) && stockWarnings.length > 0) {
      body.local_stock_warnings = stockWarnings;
    }
    var linePricePayload =
      typeof window.getPosLinePricesPayloadForCheckout === "function"
        ? window.getPosLinePricesPayloadForCheckout()
        : [];
    var followUpLinePrices =
      typeof window.getPosFollowUpLinePricesOverride === "function"
        ? window.getPosFollowUpLinePricesOverride()
        : null;
    if (Array.isArray(followUpLinePrices) && followUpLinePrices.length > 0) {
      linePricePayload = followUpLinePrices;
    }
    if (Array.isArray(linePricePayload) && linePricePayload.length > 0) {
      body.pos_line_prices = linePricePayload;
    }
    if (checkoutOptions.confirmLocalFallback) {
      body.confirm_local_fallback = "1";
    }
    setPosCheckoutLoading(true);
    fetch("index.php?page=pos_register&action=checkout-create", {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify(body)
    })
      .then(function (res) {
        return res.text().then(function (text) {
          var cleaned = text.replace(/^\uFEFF/, "").trim();
          try {
            return JSON.parse(cleaned);
          } catch (e) {
            throw new Error(cleaned.slice(0, 400) || "Invalid JSON");
          }
        });
      })
      .then(function (data) {
        if (!data || !data.success) {
          window.__posLastOrderCreateDebug = data && data.order_create_debug ? data.order_create_debug : null;
          if (data && data.requires_local_fallback_confirm) {
            closeDeliveryStatusModal();
            openLocalFallbackConfirmModal(
              data.api_error_message || data.message || "Unknown API error.",
              data.order_create_debug || null,
              addressPayload
            );
            return;
          }
          if (checkoutOptions.keepLocalFallbackModalOpen) {
            openLocalFallbackConfirmModal(
              (data && data.message) ? data.message : "Checkout failed.",
              data && data.order_create_debug ? data.order_create_debug : null,
              addressPayload
            );
            return;
          }
          if (window.__posLastOrderCreateDebug && typeof showPaymentModalOrderApiRecord === "function") {
            showPaymentModalOrderApiRecord(window.__posLastOrderCreateDebug);
          }
          if (data && data.order_number) {
            closeAddressConfirmModal();
            closeDeliveryStatusModal();
            closeLocalFallbackConfirmModal();
            closeOverseasGstModal();
          }
          if (data && data.requires_compliance) {
            closeDeliveryStatusModal();
            syncHighValueComplianceUi();
            showAddressConfirmValidationError(data.message || "Additional details required for High Value Transaction.");
          } else {
            showPosCheckoutErrorBanner(data && data.message ? data.message : "Checkout failed");
          }
          return;
        }
        window.__posLastOrderCreateDebug = null;
        hidePosCheckoutErrorBanner();
        pendingAddressPayloadForCheckout = null;
        closeAllPosCheckoutModals();
        showToast(data.message || "Order placed.", "green");
        if (data.redirect_url) {
          window.location.assign(data.redirect_url);
        }
        return;
      })
      .catch(function (err) {
        console.error(err);
        if (checkoutOptions.keepLocalFallbackModalOpen) {
          openLocalFallbackConfirmModal(
            err && err.message ? err.message : "Checkout request failed.",
            window.__posLastOrderCreateDebug || null,
            addressPayload
          );
        } else {
          showPosCheckoutErrorBanner(err && err.message ? err.message : "Checkout request failed");
        }
      })
      .finally(function () {
        setPosCheckoutLoading(false);
        if (checkoutOptions.keepLocalFallbackModalOpen) {
          var confirmBtn = document.getElementById("localFallbackConfirmBtn");
          if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.textContent = "Save order & continue checkout";
          }
        }
      });
  }

  function importOrder(orderid, callback = null) {

    const secretKey = 'b2d1127032446b78ce2b8911b72f6b155636f6898af2cf5d3aafdccf46778801';
    const url = 'index.php?page=orders&action=import_orders&secret_key=' + secretKey + '&orderid=' + orderid;

    fetch(url)
      .then(res => res.text())
      .then(text => {

        console.log("IMPORT RESPONSE:", text);

        if (text.includes("orders imported successfully") || text.includes("Import Result")) {

          showToast("✓ Order imported & Invoice created", "blue");

          setTimeout(() => {
            if (callback) callback(true, text);
          }, 800);

        } else {

          showToast("Import failed", "red");
          if (callback) callback(false, text);

        }

      })
      .catch(err => {
        console.error(err);
        showToast("✗ Import request failed", "red");
        if (callback) callback(false, '');
      });
  }

  function showToast(msg, color) {

    let div = document.createElement("div");

    div.className = `fixed top-5 right-5 bg-${color}-600 text-white px-5 py-3 rounded-lg shadow-lg z-[99999]`;

    div.innerHTML = msg;

    document.body.appendChild(div);

    setTimeout(() => div.remove(), 3000);
  }
</script>

<script>
  function updateDiscountPlaceholder() {
    const typeEl = document.getElementById("discount_type");
    const valueEl = document.getElementById("discount_value");
    if (!typeEl || !valueEl) return;

    valueEl.placeholder = typeEl.value === "percent" ? "Enter percentage" : "Enter amount";
  }

  (function () {
    var applyBtn = document.getElementById("applyCustomDiscountBtn");
    var dtype = document.getElementById("discount_type");
    if (dtype) {
      dtype.addEventListener("change", updateDiscountPlaceholder);
    }
    updateDiscountPlaceholder();

    if (!applyBtn) {
      return;
    }

    applyBtn.addEventListener("click", function() {
      var dm = document.getElementById("discountModal");
      if (dm) {
        dm.classList.remove("hidden");
      }
      updateDiscountPlaceholder();
    });
  })();

  function closeDiscountModal() {
    var dm = document.getElementById("discountModal");
    if (dm) {
      dm.classList.add("hidden");
    }
  }

  function applyDiscount() {
    var typeEl = document.getElementById("discount_type");
    var valueEl = document.getElementById("discount_value");
    var type = typeEl ? typeEl.value : "";
    var value = valueEl ? parseFloat(valueEl.value) : NaN;
    if (!value || value <= 0) {
      showToast("⚠ Enter valid discount", "red");
      return;
    }
    if (typeof window.applyCustomDiscount !== "function") {
      showToast("Cart is still loading — try again in a moment.", "red");
      return;
    }
    var mode = type === "percent" ? "percent" : "fixed";
    if (mode === "percent" && value > 100) {
      showToast("Percentage must be between 0 and 100.", "red");
      return;
    }
    closeDiscountModal();
    window.applyCustomDiscount(value, { mode: mode });
  }
</script>
<script>
  //   $('#sortBy, #minPrice, #maxPrice, #stockFilter').on('change keyup', function () {
  //   clearTimeout(searchTimeout);
  //   searchTimeout = setTimeout(function () {
  //     resetAndLoad();
  //   }, 400);
  // });
</script>