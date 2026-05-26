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
      <div class="relative w-full max-w-lg">
        <input
          class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm focus:border-orange-500 outline-none"
          placeholder="Search product by Name or SKU"
          id="searchName"
          autocomplete="off"
          aria-autocomplete="list"
          aria-controls="skuSuggest"
          aria-expanded="false" />
        <div
          id="skuSuggest"
          class="absolute left-0 right-0 top-full z-[9999] mt-1 hidden max-h-72 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg">
        </div>
        <p id="posSkuSearchError" class="hidden mt-2 text-xs font-medium text-red-600"></p>
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
          <div id="selectedCustomerNameCart" class="text-base font-semibold text-center text-slate-800">Walk-in Customer</div>
          <div id="selectedCustomerPhoneCart" class="text-sm text-slate-500 text-center">-</div>
        </div>

        <div class="pos-cart-panel-inner px-3 py-2">
          <div class="px-1 py-4 space-y-3 text-sm text-slate-600">
            <p class="font-semibold text-slate-800">Cart</p>
            <p class="text-xs text-slate-500">Loading cart from Exotic… If this message stays visible, refresh the page or open the browser console for errors.</p>
          </div>
        </div>
      </div>
    </aside>
<!-- Product Modal -->
<div id="productModal" class="fixed inset-0 z-[9999] hidden"
     data-pos-warehouse="<?= htmlspecialchars((string)($warehouse_name ?? ''), ENT_QUOTES, 'UTF-8') ?>">
  <!-- overlay -->
  <div id="productModalOverlay" class="absolute inset-0 bg-black/50"></div>

  <!-- modal box -->
  <div class="relative mx-auto mt-10 w-[95%] max-w-3xl rounded-2xl bg-white shadow-xl">
    <div class="flex items-start justify-between gap-3 border-b px-5 py-3">
      <h2
        id="pmTitle"
        class="min-w-0 flex-1 text-left text-sm font-semibold text-gray-900 leading-snug line-clamp-3 break-words">
        Product
      </h2>

      <button
        type="button"
        id="productModalClose"
        class="shrink-0 rounded-lg px-2 py-1 text-gray-500 hover:bg-gray-100 hover:text-gray-800">
        ✕
      </button>
    </div>

    <div class="p-5">
      <div class="grid grid-cols-1 gap-5 md:grid-cols-[220px_1fr]">
        <div class="rounded-xl border bg-gray-50 p-3">
          <img
            id="pmImage"
            src=""
            alt=""
            class="mx-auto h-56 w-full object-contain" />
        </div>

        <div>
          <div class="flex flex-wrap gap-2" id="pmBadges"></div>

          <div
            class="mt-4 grid grid-cols-[140px_10px_1fr] gap-x-2 gap-y-2 text-xs"
            id="pmDetails">
            <!-- rows injected here -->
          </div>
          <!-- ADDONS -->
          <div id="pmAddonsWrapper" class="mt-4 hidden">
            <div class="text-xs font-semibold text-gray-700 mb-2">
              Add-ons
            </div>

            <div id="pmAddons" class="space-y-2"></div>
          </div>

          <!-- Footer -->
          <div class="mt-6 flex flex-wrap items-center justify-end gap-2">

            <!-- Qty control -->
            <div class="mr-auto flex flex-col items-start gap-1">
              <div class="flex items-center gap-3 flex-wrap">
                <span
                  id="pmModalPrice"
                  class="hidden shrink-0 text-lg font-bold text-gray-900 tabular-nums tracking-tight"
                  aria-live="polite"></span>
                <label class="text-xs text-gray-600">Qty</label>
                <span id="pmQtyMaxHint" class="text-[10px] text-gray-500"></span>

                <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                  <button
                    type="button"
                    id="pmQtyDec"
                    class="h-9 w-9 text-slate-600 hover:bg-gray-50">
                    −
                  </button>

                  <span
                    id="pmQtyVal"
                    class="h-9 w-10 flex items-center justify-center font-semibold text-sm">
                    1
                  </span>

                  <button
                    type="button"
                    id="pmQtyInc"
                    class="h-9 w-9 text-slate-600 hover:bg-gray-50">
                    +
                  </button>
                </div>
              </div>
              <div id="pmQtySummary" class="hidden mt-0.5 max-w-[280px] space-y-0.5 text-[10px] leading-snug text-gray-600"></div>
            </div>


            <input type="hidden" id="modal_product_code" value="">
            <input type="hidden" id="modal_item_code" value="">
            <input type="hidden" id="modal_size" value="">
            <input type="hidden" id="modal_color" value="">
            <input type="hidden" id="modal_item_level" value="">
            <input type="hidden" id="modal_stock_check_code" value="">
            <input type="hidden" id="modal_qty" value="1">
            <input type="hidden" id="modal_options" value="">
            <input type="hidden" id="modal_variation" value="">
            <button
              type="button"
              id="pmAddToCartBtn"
              class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50 disabled:pointer-events-none">
              Add to cart
            </button>
            <!-- Close -->
            <button
              type="button"
              id="pmCloseBtn"
              class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
              Close
            </button>
          </div>
        </div>
      </div>

      <div id="pmSiblingSkusWrapper" class="hidden mt-5 border-t border-gray-100 pt-4">
        <div id="pmSiblingSkus" class="flex flex-wrap gap-2"></div>
      </div>
    </div>
  </div>
</div>

<!-- CUSTOMER MODAL -->
<div id="customerModal" class="fixed inset-0 z-[9999] hidden">

  <div class="absolute inset-0 z-0 bg-black/40" onclick="closeCustomerModal()"></div>

  <div class="relative z-10 mx-auto mt-10 w-[95%] max-w-2xl rounded-2xl bg-white shadow-xl max-h-[85vh] flex flex-col">

    <!-- HEADER -->
    <div class="flex items-center justify-between border-b px-5 py-3">
      <h2 class="text-sm font-semibold">Add Customer</h2>
      <button onclick="closeCustomerModal()" class="text-gray-500 text-lg">✕</button>
    </div>

    <!-- form -->
    <form id="customerForm" class="p-5 space-y-4 text-xs overflow-y-auto" method="POST">

      <!-- BILLING -->
      <div class="font-semibold text-gray-700">Billing Details</div>

      <div class="grid grid-cols-2 gap-3">

        <div>
          <label class="text-gray-500">First Name <span class="text-red-600">*</span></label>
          <input name="first_name" required class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Last Name</label>
          <input name="last_name" class="w-full border rounded px-2 py-1.5" placeholder="Optional">
        </div>

        <div>
          <label class="text-gray-500">Mobile <span class="text-red-600">*</span></label>
          <input name="mobile" required class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Email</label>
          <input name="cus_email" class="w-full border rounded px-2 py-1.5">
        </div>

        <div class="col-span-2">
          <label class="text-gray-500">Address 1</label>
          <input name="address_line1" class="w-full border rounded px-2 py-1.5">
        </div>
        <div class="col-span-2">
          <label class="text-gray-500">Address 2</label>
          <input name="address_line2" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">City</label>
          <input name="city" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Country</label>
          <select name="country" id="customer_country" class="w-full border rounded px-2 py-1.5 bg-white">
            <?php
            $selected_iso = 'IN';
            include __DIR__ . '/partials/iso_country_options.php';
            ?>
          </select>
        </div>

        <div>
          <label class="text-gray-500">State</label>
          <select name="state" id="customer_state" class="w-full border rounded px-2 py-1.5 bg-white">
            <option value="">Select state</option>
          </select>
          <input id="customer_state_text" class="hidden w-full border rounded px-2 py-1.5" placeholder="State">
        </div>

        <div>
          <label class="text-gray-500">Zipcode</label>
          <input name="zipcode" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">GSTIN</label>
          <input name="gstin" class="w-full border rounded px-2 py-1.5">
        </div>

      </div>

      <!-- SHIPPING -->
      <div class="flex items-center gap-2 mt-2">
        <input type="checkbox" id="sameAddress" onchange="copyBilling()">
        <label class="text-xs text-gray-600">Shipping same as billing</label>
      </div>

      <div class="font-semibold text-gray-700">Shipping Details</div>

      <div class="grid grid-cols-2 gap-3">

        <div>
          <label class="text-gray-500">First Name</label>
          <input name="shipping_first_name" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Last Name</label>
          <input name="shipping_last_name" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Mobile</label>
          <input name="shipping_mobile" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Email</label>
          <input name="shipping_email" class="w-full border rounded px-2 py-1.5">
        </div>

        <div class="col-span-2">
          <label class="text-gray-500">Address 1</label>
          <input name="shipping_address_line1" class="w-full border rounded px-2 py-1.5">
        </div>
        <div class="col-span-2">
          <label class="text-gray-500">Address 2</label>
          <input name="shipping_address_line2" class="w-full border rounded px-2 py-1.5">
        </div>
        <div>
          <label class="text-gray-500">City</label>
          <input name="shipping_city" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Country</label>
          <select name="shipping_country" id="customer_shipping_country" class="w-full border rounded px-2 py-1.5 bg-white">
            <?php
            $selected_iso = 'IN';
            include __DIR__ . '/partials/iso_country_options.php';
            ?>
          </select>
        </div>

        <div>
          <label class="text-gray-500">State</label>
          <select name="shipping_state" id="customer_shipping_state" class="w-full border rounded px-2 py-1.5 bg-white">
            <option value="">Select state</option>
          </select>
          <input id="customer_shipping_state_text" class="hidden w-full border rounded px-2 py-1.5" placeholder="State">
        </div>

        <div>
          <label class="text-gray-500">Zipcode</label>
          <input name="shipping_zipcode" class="w-full border rounded px-2 py-1.5">
        </div>

      </div>

      <!-- buttons -->
      <div class="flex justify-end gap-3 border-t pt-4">

        <button type="button"
          onclick="closeCustomerModal()"
          class="px-4 py-1.5 rounded bg-gray-300 text-gray-700">
          Cancel
        </button>

        <button type="submit"
          class="px-4 py-1.5 rounded bg-orange-600 text-white">
          Save Customer
        </button>

      </div>

    </form>

  </div>

</div>

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
        Order total is taken from the live cart summary (incl. discounts). Select payment stage, then <strong>Place order</strong> to confirm addresses next.
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label class="text-xs text-slate-500">Payment stage <span class="text-red-600">*</span></label>
          <select id="payment_stage" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" required>
            <option value="final">Final</option>
            <option value="partial">Partial</option>
            <option value="advance">Advance</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-slate-500">Payment mode <span class="text-red-600">*</span></label>
          <select id="payment_mode" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" required>
            <option value="cash">Cash</option>
            <option value="upi">UPI</option>
            <option value="bank_transfer">Bank transfer</option>
            <option value="pos_machine">POS machine</option>
            <option value="razorpay">Razorpay</option>
            <option value="cheque">Cheque</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-slate-500">Payment date <span class="text-red-600">*</span></label>
          <input type="date" id="payment_date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" title="Today or earlier only" required>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-slate-500">Amount (₹) <span class="text-red-600">*</span></label>
          <input type="number" step="0.01" min="0" id="payment_amount" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 tabular-nums" placeholder="0.00" required>
        </div>
        <div>
          <label id="transaction_id_label" class="text-xs text-slate-500">Transaction ID</label>
          <input type="text" id="transaction_id" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" placeholder="Required for Razorpay">
          <p id="transaction_id_required_hint" class="hidden mt-1 text-[11px] text-amber-700">Razorpay requires a transaction ID.</p>
        </div>
      </div>
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

<!-- ADDRESS CONFIRMATION MODAL -->
<div id="addressConfirmModal" class="fixed inset-0 z-[10000] hidden">
  <div class="absolute inset-0 bg-black/40" onclick="closeAddressConfirmModal()"></div>
  <div class="address-confirm-panel relative mx-auto flex w-[96%] max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
    <div class="flex shrink-0 items-center justify-between border-b px-5 py-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-800">Confirm Billing &amp; Shipping Details</h2>
        <p class="mt-0.5 text-xs text-slate-500">Required: First name and State. Other fields use defaults when left blank.</p>
      </div>
      <button type="button" onclick="closeAddressConfirmModal()" class="text-lg leading-none text-gray-500 hover:text-gray-800" aria-label="Close">✕</button>
    </div>
    <div class="address-confirm-body flex-1 overflow-y-auto overscroll-contain">
    <div id="addressConfirmValidationSummary" class="mx-5 mt-3 hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>
    <div id="highValueComplianceBanner" class="mx-5 mt-3 hidden rounded-lg border border-amber-300 bg-amber-100 px-3 py-2 text-sm font-semibold text-amber-900">High Value Transaction – Compliance Required</div>
    <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
      <div class="space-y-3">
        <h3 class="text-sm font-semibold text-slate-800">Billing Information</h3>
        <div class="grid grid-cols-2 gap-3">
          <label class="block text-xs font-medium text-slate-600">First Name <span class="field-req-star text-red-600">*</span><input id="confirm_first_name" class="w-full rounded border" placeholder="First Name"></label>
          <label class="block text-xs font-medium text-slate-600">Last Name<input id="confirm_last_name" class="w-full rounded border" placeholder="Last Name"></label>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <label class="block text-xs font-medium text-slate-600">Email<input id="confirm_email" type="email" class="w-full rounded border" placeholder="Email"></label>
          <label class="block text-xs font-medium text-slate-600">Phone <span class="field-req-star text-red-600">*</span><input id="confirm_phone" class="w-full rounded border" placeholder="Phone"></label>
        </div>
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
        <label class="block text-xs font-medium text-slate-600">GSTIN<input id="confirm_gstin" class="w-full rounded border uppercase" placeholder="GSTIN (optional)" maxlength="15"></label>
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
              <input id="country_of_residence" class="mt-1 w-full rounded border border-amber-200 bg-white px-3 py-2 text-sm" placeholder="Country of residence">
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
        <label class="block text-xs font-medium text-slate-600">Phone<input id="confirm_sphone" class="w-full rounded border" placeholder="Phone"></label>
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
<script src="<?php echo base_url(); ?>assets/js/pos_cart_hooks.js"></script>
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
          "/?page=invoices&action=generate_pdf&invoice_id=" + invoice_id;

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

  function openPaymentModal() {
    if (typeof window.hasUnconfirmedLocalStockWarnings === "function" && window.hasUnconfirmedLocalStockWarnings()) {
      showToast("Please confirm local stock for cart items (Y or N) before checkout.", "violet");
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
    var pa = document.getElementById("payment_amount");
    if (pa && ct && ct.grandTotal != null && !isNaN(parseFloat(String(ct.grandTotal)))) {
      pa.value = String(ct.grandTotal);
    }
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
    el.value = normalizePosCountryCode(raw, el);
    if (!el.value) {
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
      confirm_phone: firstNonEmpty(billing.phone, billing.mobile, billing.billing_mobile, (window.POS_ADDRESS_API_DEFAULTS || {}).confirm_phone || "8031404444"),
      confirm_address1: firstNonEmpty(billing.address1, billing.address_line1, billing.billing_address_line1),
      confirm_address2: firstNonEmpty(billing.address2, billing.address_line2, billing.billing_address_line2),
      confirm_city: firstNonEmpty(billing.city),
      confirm_state: firstNonEmpty(billing.state),
      confirm_zip: firstNonEmpty(billing.zip, billing.zipcode, window.POS_STORE_PINCODE || ""),
      confirm_gstin: firstNonEmpty(billing.gstin),

      // Shipping: support normalized keys + DB/raw aliases.
      confirm_sfirst_name: shippingFirstName,
      confirm_slast_name: shippingLastName,
      confirm_saddress1: firstNonEmpty(shipping.saddress1, shipping.shipping_address_line1, shipping.address1, shipping.address_line1),
      confirm_saddress2: firstNonEmpty(shipping.saddress2, shipping.shipping_address_line2, shipping.address2, shipping.address_line2),
      confirm_scity: firstNonEmpty(shipping.scity, shipping.shipping_city, shipping.city),
      confirm_sstate: firstNonEmpty(shipping.sstate, shipping.shipping_state, shipping.state),
      confirm_szip: firstNonEmpty(shipping.szip, shipping.shipping_zipcode, shipping.zip, shipping.zipcode),
      confirm_sphone: firstNonEmpty(shipping.sphone, shipping.shipping_mobile, shipping.mobile, shipping.phone)
    };
    var billingCountryRaw = firstNonEmpty(billing.country, billing.billing_country, "IN");
    var shippingCountryRaw = firstNonEmpty(shipping.scountry, shipping.shipping_country, shipping.country, "IN");
    Object.keys(map).forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.value = map[id];
    });
    setPosCountrySelect("confirm_country", billingCountryRaw);
    setPosCountrySelect("confirm_scountry", shippingCountryRaw);
    var compliance = (payload && payload.compliance) || {};
    [
      ["customer_residency_status", compliance.customer_residency_status || "INDIAN_RESIDENT"],
      ["customer_pan", compliance.customer_pan || ""],
      ["customer_aadhaar", compliance.customer_aadhaar || ""],
      ["passport_number", compliance.passport_number || ""],
      ["country_of_residence", compliance.country_of_residence || ""]
    ].forEach(function(row) {
      var el = document.getElementById(row[0]);
      if (el) el.value = row[1];
    });
    var billingCountry = normalizePosCountryCode(document.getElementById("confirm_country")?.value || "IN", document.getElementById("confirm_country"));
    var shippingCountry = normalizePosCountryCode(document.getElementById("confirm_scountry")?.value || "IN", document.getElementById("confirm_scountry"));
    var defaultState = String(window.POS_DEFAULT_STATE || "Delhi");
    syncAllPosStateFields({
      billing: map.confirm_state || (isPosIndiaCountry(billingCountry) ? defaultState : ""),
      shipping: map.confirm_sstate || (isPosIndiaCountry(shippingCountry) ? defaultState : "")
    }).then(function() {
      syncHighValueComplianceUi();
    });
  }

  var POS_SHIPPING_ADDRESS_FIELD_IDS = [
    "confirm_sfirst_name",
    "confirm_slast_name",
    "confirm_sphone",
    "confirm_saddress1",
    "confirm_saddress2",
    "confirm_scity",
    "confirm_sstate",
    "confirm_szip",
    "confirm_scountry"
  ];

  var POS_BILLING_TO_SHIPPING_FIELDS = [
    ["confirm_first_name", "confirm_sfirst_name"],
    ["confirm_last_name", "confirm_slast_name"],
    ["confirm_phone", "confirm_sphone"],
    ["confirm_address1", "confirm_saddress1"],
    ["confirm_address2", "confirm_saddress2"],
    ["confirm_city", "confirm_scity"],
    ["confirm_state", "confirm_sstate"],
    ["confirm_zip", "confirm_szip"],
    ["confirm_country", "confirm_scountry"]
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
        el.readOnly = synced;
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
    var omitShippingOnOrder = hasConfirmShippingFieldsFilled();
    return {
      confirm_address_submit: "1",
      confirm_first_name: read("confirm_first_name"),
      confirm_last_name: read("confirm_last_name"),
      confirm_email: read("confirm_email"),
      confirm_phone: read("confirm_phone"),
      confirm_address1: read("confirm_address1"),
      confirm_address2: read("confirm_address2"),
      confirm_city: read("confirm_city"),
      confirm_state: getPosStateValue("confirm_state"),
      confirm_zip: read("confirm_zip"),
      confirm_country: read("confirm_country"),
      confirm_gstin: read("confirm_gstin"),
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
      confirm_sphone: read("confirm_sphone"),
      confirm_shipping_same_as_billing: isShippingSameAsBillingChecked() ? "1" : "0",
      confirm_omit_shipping_api: omitShippingOnOrder ? "1" : "0",
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
    ["confirm_first_name", "confirm_phone", "confirm_zip", "confirm_state", "confirm_state_select", "confirm_email", "confirm_gstin", "customer_pan", "customer_aadhaar", "passport_number", "country_of_residence"].forEach(function(id) {
      setPosFieldInvalid(id, false);
    });
    POS_SHIPPING_ADDRESS_FIELD_IDS.forEach(function(id) {
      setPosFieldInvalid(id, false);
    });
    var summary = document.getElementById("addressConfirmValidationSummary");
    if (summary) {
      summary.classList.add("hidden");
      summary.textContent = "";
    }
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
    var amountEl = document.getElementById("payment_amount");
    var stage = stageEl ? String(stageEl.value || "").toLowerCase() : "";
    var amount = amountEl ? parseFloat(String(amountEl.value || "")) : NaN;
    var total = getCurrentCheckoutTotal();
    return stage === "final" && isFinite(amount) && total > 0 && Math.abs(amount - total) <= 0.02;
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
    return getCurrentCheckoutTotal() >= getHighValueLimit();
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

  function updateConfirmAddressButtonState() {
    var btn = document.getElementById("confirmAddressSubmitBtn");
    if (!btn) return;
    btn.disabled = false;
    btn.classList.remove("opacity-50", "cursor-not-allowed");
    btn.title = "";
  }

  function validateAddressConfirmPayload(payload) {
    clearAddressValidationState();
    var missing = [];
    var firstInvalidId = "";
    var firstName = String(payload.confirm_first_name || "").trim();
    var state = String(payload.confirm_state || "").trim();
    var zip = String(payload.confirm_zip || "").trim();
    if (!firstName) {
      missing.push("First name");
      setPosFieldInvalid("confirm_first_name", true);
      firstInvalidId = "confirm_first_name";
    }
    if (!zip) {
      missing.push("ZIP / Pincode");
      setPosFieldInvalid("confirm_zip", true);
      if (!firstInvalidId) firstInvalidId = "confirm_zip";
    }
    var phone = String(payload.confirm_phone || "").trim();
    if (!phone) {
      missing.push("Phone");
      setPosFieldInvalid("confirm_phone", true);
      if (!firstInvalidId) firstInvalidId = "confirm_phone";
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

    if (missing.length) {
      var summary = document.getElementById("addressConfirmValidationSummary");
      var message = "Please complete: " + missing.slice(0, 6).join(", ") + (missing.length > 6 ? " and " + (missing.length - 6) + " more" : "") + ".";
      if (summary) {
        summary.textContent = message;
        summary.classList.remove("hidden");
      }
      showToast("⚠ " + message, "red");
      var first = firstInvalidId ? document.getElementById(firstInvalidId) : null;
      if (first) first.focus();
      return false;
    }
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

    var fullOrderApiBtn = document.getElementById("paymentModalOrderApiFullBtn");
    if (fullOrderApiBtn) {
      fullOrderApiBtn.addEventListener("click", function () {
        if (typeof window.openOrderCreateApiResponseModal === "function") {
          window.openOrderCreateApiResponseModal();
        }
      });
    }

    var paymentModeSelect = document.getElementById("payment_mode");
    var txnRequiredHint = document.getElementById("transaction_id_required_hint");
    var txnLabel = document.getElementById("transaction_id_label");
    var txnInput = document.getElementById("transaction_id");
    function syncRazorpayTxnHint() {
      if (!paymentModeSelect || !txnRequiredHint) {
        return;
      }
      var mode = String(paymentModeSelect.value || "").toLowerCase();
      txnRequiredHint.classList.toggle("hidden", mode !== "razorpay");
      if (txnLabel) {
        txnLabel.innerHTML = (mode === "cheque" ? "Cheque Number" : "Transaction ID") + (mode === "razorpay" ? ' <span class="text-red-600">*</span>' : "");
      }
      if (txnInput) {
        txnInput.placeholder = mode === "cheque" ? "Enter cheque number" : "Required for Razorpay";
        txnInput.required = mode === "razorpay";
      }
    }
    if (paymentModeSelect) {
      paymentModeSelect.addEventListener("change", syncRazorpayTxnHint);
      syncRazorpayTxnHint();
    }
    var paymentStageEl = document.getElementById("payment_stage");
    var paymentAmountEl = document.getElementById("payment_amount");
    if (paymentStageEl) {
      paymentStageEl.addEventListener("change", syncCustomInvoiceNumberField);
    }
    if (paymentAmountEl) {
      paymentAmountEl.addEventListener("input", syncCustomInvoiceNumberField);
      paymentAmountEl.addEventListener("change", syncCustomInvoiceNumberField);
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
          showToast("⚠ Please select customer first", "red");
            if (typeof jQuery !== "undefined" && jQuery("#customerSelect").data("select2")) {
            jQuery("#customerSelect").select2("open");
          } else {
            var cs = document.getElementById("customerSelect");
            if (cs) cs.focus();
          }
          return;
        }

        let paymentStage = document.getElementById("payment_stage").value;
        let paymentAmount = parseFloat(document.getElementById("payment_amount").value);
        var liveT = typeof window.getPosCartTotalsForCheckout === "function" ? window.getPosCartTotalsForCheckout() : null;
        var grandTotal = liveT && liveT.grandTotal != null && !isNaN(parseFloat(String(liveT.grandTotal)))
          ? parseFloat(String(liveT.grandTotal))
          : parseFloat("<?= (float)($cartData['grand_total'] ?? 0) ?>");

        if (!paymentAmount || paymentAmount <= 0) {
          showToast("⚠ Payment amount must be greater than 0", "red");
          return;
        }

        //  FINAL PAYMENT STRICT VALIDATION
        if (paymentStage === "final") {

          if (paymentAmount < grandTotal) {
            showToast("⚠ Final payment must be FULL amount ₹ " + grandTotal, "red");
            return;
          }

          if (paymentAmount > grandTotal) {
            showToast("⚠ Over payment not allowed", "red");
            return;
          }

        }

        //  PARTIAL VALIDATION
        if (paymentStage === "partial" || paymentStage === "advance") {

          if (paymentAmount >= grandTotal) {
            showToast("⚠ Partial payment must be less than total ₹ " + grandTotal, "red");
            return;
          }

        }

        var paymentModeVal = document.getElementById("payment_mode").value;
        var txnVal = (document.getElementById("transaction_id").value || "").trim();
        if (paymentModeVal === "razorpay" && txnVal === "") {
          showToast("⚠ Razorpay requires a transaction ID", "red");
          var txnEl = document.getElementById("transaction_id");
          if (txnEl) {
            txnEl.focus();
          }
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
      });
    }

    initConfirmShippingSameAsBilling();

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
        createOrderNow(payload);
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
            syncPosStateField("billing", "").then(function() {
              if (isShippingSameAsBillingChecked()) {
                copyBillingToShippingFields();
              }
            });
          } else {
            syncPosStateField("shipping", "");
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

  function createOrderNow(addressPayload) {
    var customerId = getSelectedCustomerId();
    var live = typeof window.getPosCartTotalsForCheckout === "function" ? window.getPosCartTotalsForCheckout() : null;
    var orderTotal = live && live.grandTotal != null ? parseFloat(String(live.grandTotal)) : NaN;
    if (!isFinite(orderTotal) || orderTotal <= 0) {
      showToast("Cart total unavailable — add items or refresh the cart.", "red");
      return;
    }
    var payStage = document.getElementById("payment_stage").value;
    var payMode = document.getElementById("payment_mode").value;
    var payAmt = parseFloat(document.getElementById("payment_amount").value);
    var txn = (document.getElementById("transaction_id").value || "").trim();
    var note = (document.getElementById("payment_note") && document.getElementById("payment_note").value) || "";
    var subTotalGoods = live && live.subtotal != null ? parseFloat(String(live.subtotal)) : NaN;
    var gstTotal = live && live.gstTotal != null ? parseFloat(String(live.gstTotal)) : NaN;
    var couponDeduction = live && live.couponDeduction != null ? parseFloat(String(live.couponDeduction)) : NaN;
    var customDeduction = live && live.customDeduction != null ? parseFloat(String(live.customDeduction)) : NaN;
    var customInvoiceEl = document.getElementById("custom_invoice_number");
    var customInvoiceNumber = isFullFinalPaymentSelected() && customInvoiceEl
      ? (customInvoiceEl.value || "").trim()
      : "";
    var body = Object.assign({}, addressPayload, {
      customer_id: String(customerId),
      payment_stage: payStage,
      payment_mode: payMode,
      payment_amount: payAmt,
      transaction_id: txn,
      payment_note: note,
      order_total: orderTotal,
      receipt_subtotal_goods: isFinite(subTotalGoods) ? subTotalGoods : orderTotal,
      receipt_gst_total: isFinite(gstTotal) ? gstTotal : 0,
      receipt_coupon_discount: isFinite(couponDeduction) ? couponDeduction : 0,
      receipt_cash_discount: isFinite(customDeduction) ? customDeduction : 0
    });
    if (customInvoiceNumber !== "") {
      body.custom_invoice_number = customInvoiceNumber;
    }
    if (String(payMode || "").toLowerCase() === "cash" && isFinite(payAmt) && payAmt >= getHighValueLimit()) {
      var okCash = window.confirm("Cash receipts of ₹2,00,000 or more are restricted under Income Tax Act Section 269ST. Please switch to digital payment.\n\nDo you still want to continue after acknowledging this warning?");
      if (!okCash) {
        showToast("Please switch to digital payment or acknowledge the cash warning.", "red");
        return;
      }
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
    var hasLinePriceOv =
      typeof window.hasPosLinePriceOverridesForCheckout === "function"
        ? window.hasPosLinePriceOverridesForCheckout()
        : false;
    if (hasLinePriceOv && Array.isArray(linePricePayload) && linePricePayload.length > 0) {
      body.pos_line_prices = linePricePayload;
    }
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
          if (window.__posLastOrderCreateDebug && typeof showPaymentModalOrderApiRecord === "function") {
            showPaymentModalOrderApiRecord(window.__posLastOrderCreateDebug);
          }
          if (data && data.requires_compliance) {
            syncHighValueComplianceUi();
            var summary = document.getElementById("addressConfirmValidationSummary");
            if (summary) {
              summary.textContent = data.message || "Additional details required for High Value Transaction.";
              summary.classList.remove("hidden");
            }
          }
          showToast(data && data.message ? data.message : "Checkout failed", "red");
          return;
        }
        window.__posLastOrderCreateDebug = null;
        showToast(data.message || "Order placed.", "green");
        closeAddressConfirmModal();
        closePaymentModal();
        if (data.redirect_url) {
          window.location.href = data.redirect_url;
        }
      })
      .catch(function (err) {
        console.error(err);
        showToast(err && err.message ? err.message : "Checkout request failed", "red");
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
  function openCustomerModal() {
    document.getElementById("customerModal").classList.remove("hidden");
    syncCustomerCountryStateFields();
  }

  function closeCustomerModal() {
    document.getElementById("customerModal").classList.add("hidden")
  }
  let customerData = {};

  function syncCustomerStateSelect(countryId, stateId, preferredValue) {
    var countryEl = document.getElementById(countryId);
    var stateEl = document.getElementById(stateId);
    var textEl = document.getElementById(stateId + "_text");
    if (!countryEl || !stateEl || !textEl) {
      return Promise.resolve();
    }

    var fieldName = stateEl.getAttribute("data-field-name") || textEl.getAttribute("data-field-name") || stateEl.name || textEl.name;
    if (fieldName) {
      stateEl.setAttribute("data-field-name", fieldName);
      textEl.setAttribute("data-field-name", fieldName);
    }
    var country = String(countryEl.value || "IN").trim().toUpperCase().substring(0, 2) || "IN";
    var selected = preferredValue !== undefined ? preferredValue : (stateEl.classList.contains("hidden") ? textEl.value : stateEl.value);
    if (country !== "IN" && country !== "US") {
      textEl.value = selected || stateEl.value || textEl.value;
      textEl.name = fieldName;
      stateEl.name = "";
      stateEl.classList.add("hidden");
      textEl.classList.remove("hidden");
      return Promise.resolve();
    }

    resetPosStateSelect(stateEl, "Loading states...");
    textEl.classList.add("hidden");
    stateEl.classList.remove("hidden");
    return fetchPosCountryStates(country).then(function(states) {
      populatePosStateSelect(stateEl, states, selected);
      stateEl.name = fieldName;
      textEl.name = "";
      textEl.classList.add("hidden");
      stateEl.classList.remove("hidden");
    });
  }

  function syncCustomerCountryStateFields() {
    return Promise.all([
      syncCustomerStateSelect("customer_country", "customer_state"),
      syncCustomerStateSelect("customer_shipping_country", "customer_shipping_state")
    ]);
  }

  document.addEventListener("DOMContentLoaded", function () {
    var customerForm = document.getElementById("customerForm");
    if (!customerForm) return;

    syncCustomerCountryStateFields();
    [
      ["customer_country", "customer_state"],
      ["customer_shipping_country", "customer_shipping_state"]
    ].forEach(function(pair) {
      var countryEl = document.getElementById(pair[0]);
      if (countryEl) {
        countryEl.addEventListener("change", function() {
          syncCustomerStateSelect(pair[0], pair[1], "");
        });
      }
    });

    customerForm.addEventListener("submit", function (e) {
      e.preventDefault();

      if (!customerForm.checkValidity()) {
        customerForm.reportValidity();
        return;
      }

      var formData = new FormData(customerForm);

      customerData = {};
      formData.forEach(function (value, key) {
        customerData[key] = value;
      });

      fetch("index.php?page=pos_register&action=add-customer", {
          method: "POST",
          credentials: "same-origin",
          body: formData
        })
        .then(function (res) {
          return res.text().then(function (text) {
            try {
              var cleaned = text.replace(/^\uFEFF/, "").trim();
              return JSON.parse(cleaned);
            } catch (err) {
              console.error("add-customer: not JSON (status " + res.status + ")", text.slice(0, 800));
              throw new Error("Server did not return JSON. Check network tab / PHP errors.");
            }
          });
        })
        .then(function (data) {
          if (!data.success) {
            showToast(data.message || "Could not save customer", "red");
            return;
          }

          var select = document.getElementById("customerSelect");
          if (!select) return;

          var idStr = String(data.customer.id);
          var label = (data.customer.name || "") + " (" + (data.customer.phone || "") + ")";
          window.POS_SESSION_CUSTOMER_ID = idStr;

          if (window.jQuery && jQuery.fn.select2) {
            var $s = jQuery(select);
            var opt = new Option(label, idStr, true, true);
            opt.setAttribute("data-name", data.customer.name || "");
            opt.setAttribute("data-phone", data.customer.phone || "");
            opt.setAttribute("data-email", data.customer.email || "");
            $s.append(opt);
            $s.val(idStr).trigger("change");
            fetch("index.php?page=pos_register&action=set-customer", {
              method: "POST",
              credentials: "same-origin",
              headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "X-Requested-With": "XMLHttpRequest"
              },
              body: "customer_id=" + encodeURIComponent(idStr)
            });
            if (typeof updatePosCustomerLabels === "function") {
              updatePosCustomerLabels(data.customer.name, data.customer.phone);
            }
          } else {
            var option = document.createElement("option");
            option.value = idStr;
            option.textContent = label;
            option.setAttribute("data-name", data.customer.name || "");
            option.setAttribute("data-phone", data.customer.phone || "");
            option.setAttribute("data-email", data.customer.email || "");
            select.appendChild(option);
            select.value = idStr;
            select.dispatchEvent(new Event("change", { bubbles: true }));
            fetch("index.php?page=pos_register&action=set-customer", {
              method: "POST",
              credentials: "same-origin",
              headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "X-Requested-With": "XMLHttpRequest"
              },
              body: "customer_id=" + encodeURIComponent(idStr)
            });
            if (typeof updatePosCustomerLabels === "function") {
              updatePosCustomerLabels(data.customer.name, data.customer.phone);
            }
          }

          showToast("✓ Customer saved", "green");
          closeCustomerModal();
        })
        .catch(function (err) {
          console.error(err);
          showToast(err.message || "Save customer failed", "red");
        });
    });
  });
</script>

<script>
  function updatePosCustomerLabels(name, phone) {
    var nameText = (name != null && String(name).trim() !== "") ? String(name).trim() : "Walk-in Customer";
    var phoneText = (phone != null && String(phone).trim() !== "") ? String(phone).trim() : "-";
    var nameCartEl = document.getElementById("selectedCustomerNameCart");
    var phoneCartEl = document.getElementById("selectedCustomerPhoneCart");
    if (nameCartEl) nameCartEl.textContent = nameText;
    if (phoneCartEl) phoneCartEl.textContent = phoneText;
  }
</script>

<script>
  function copyBilling() {

    const checkbox = document.getElementById("sameAddress");

    const map = {
      "first_name": "shipping_first_name",
      "last_name": "shipping_last_name",
      "mobile": "shipping_mobile",
      "cus_email": "shipping_email",
      "address_line1": "shipping_address_line1",
      "address_line2": "shipping_address_line2",
      "city": "shipping_city",
      "country": "shipping_country",
      "state": "shipping_state",
      "zipcode": "shipping_zipcode"
    };

    Object.keys(map).forEach(billingField => {

      const shippingField = map[billingField];

      const billingInput = document.querySelector(`[name="${billingField}"]`);
      const shippingInput = document.querySelector(`[name="${shippingField}"]`);

      if (!billingInput || !shippingInput) return;

      if (checkbox.checked) {

        const syncShippingValue = function() {
          shippingInput.value = billingInput.value;
          if (billingField === "country") {
            const billingState = document.querySelector('[name="state"]');
            syncCustomerStateSelect("customer_shipping_country", "customer_shipping_state", billingState ? billingState.value : "");
          }
          if (billingField === "state") {
            syncCustomerStateSelect("customer_shipping_country", "customer_shipping_state", billingInput.value);
          }
        };

        syncShippingValue();
        // shippingInput.readOnly = true;
        shippingInput.classList.add("bg-gray-100");

        /* LIVE SYNC */
        billingInput.addEventListener("input", function() {
          if (checkbox.checked) {
            syncShippingValue();
          }
        });
        billingInput.addEventListener("change", function() {
          if (checkbox.checked) {
            syncShippingValue();
          }
        });

      } else {

        // shippingInput.readOnly = false;
        shippingInput.classList.remove("bg-gray-100");

      }

    });

  }
</script>

<script>
  $(document).ready(function() {

    var $cust = $('#customerSelect');
    $cust.select2({
      placeholder: "Type at least 2 characters to search…",
      allowClear: true,
      width: '100%',
      minimumInputLength: 2,
      ajax: {
        url: "index.php?page=pos_register&action=customer-search",
        type: "GET",
        dataType: "json",
        delay: 320,
        headers: { "X-Requested-With": "XMLHttpRequest" },
        data: function (params) {
          return { q: params.term || "" };
        },
        processResults: function (data) {
          if (!data || !data.success || !Array.isArray(data.customers)) {
            return { results: [] };
          }
          return {
            results: data.customers.map(function (c) {
              var disp = c.display || ((c.name || "") + " | " + (c.phone || "") + (c.email ? " | " + c.email : ""));
              return {
                id: String(c.id),
                text: disp,
                name: c.name || "",
                phone: c.phone || "",
                email: c.email || ""
              };
            })
          };
        },
        cache: true
      },
      templateResult: formatCustomer,
      templateSelection: formatCustomerSelection
    });

    $cust.on("select2:select", function (e) {
      var d = e.params.data;
      window.POS_SESSION_CUSTOMER_ID = d.id ? String(d.id) : "";
      fetch("index.php?page=pos_register&action=set-customer", {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: "customer_id=" + encodeURIComponent(d.id || "")
      });
      updatePosCustomerLabels(d.name, d.phone);
    });

    $cust.on("select2:clear", function () {
      window.POS_SESSION_CUSTOMER_ID = "";
      fetch("index.php?page=pos_register&action=set-customer", {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: "customer_id="
      });
      updatePosCustomerLabels("", "");
    });

    if (window.POS_INITIAL_CUSTOMER && window.POS_INITIAL_CUSTOMER.id) {
      var ic = window.POS_INITIAL_CUSTOMER;
      var opt = new Option(ic.text || ic.name || "", String(ic.id), true, true);
      opt.setAttribute("data-name", ic.name || "");
      opt.setAttribute("data-phone", ic.phone || "");
      opt.setAttribute("data-email", ic.email || "");
      $cust.append(opt);
      $cust.val(String(ic.id)).trigger("change");
      updatePosCustomerLabels(ic.name, ic.phone);
    }

  });

  function formatCustomer(data) {

    if (!data.id) return data.text;

    var name = data.name || "";
    var phone = data.phone || "";
    var email = data.email || "";
    if ((!name || !phone) && data.element) {
      var el = $(data.element);
      name = name || String(el.data("name") || "");
      phone = phone || String(el.data("phone") || "");
      email = email || String(el.data("email") || "");
    }
    if (!name) {
      name = String(data.text || "").split("|")[0].trim();
    }

    return $(`
        <div>
            <div style="font-weight:600">${name}</div>
            <div style="font-size:11px;color:#777">
                ${phone}${email ? " | " + email : ""}
            </div>
        </div>
    `);
  }

  function formatCustomerSelection(data) {

    if (!data.id) return data.text;

    var name = data.name || "";
    if (!name && data.element) {
      name = $(data.element).data("name") || "";
    }
    return name || data.text;
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
    showToast("Discount API removed — wire new cart before applying discounts.", "red");
    closeDiscountModal();
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