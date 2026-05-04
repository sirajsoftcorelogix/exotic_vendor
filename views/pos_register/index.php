<div class="min-h-screen pos-register-page">
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
  </style>
  <script>
    window.POS_SESSION_CUSTOMER_ID = <?= json_encode(!empty($_SESSION['pos_customer_id']) ? (string)(int)$_SESSION['pos_customer_id'] : '') ?>;
    window.POS_INITIAL_CUSTOMER = <?= json_encode(isset($selected_customer) ? $selected_customer : null, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
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
  <main class="mx-auto max-w-[1500px] grid grid-cols-12 gap-5 px-4 py-5">

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

    <aside class="col-span-12 lg:col-span-3" data-pos-cart-sidebar="1">
      <div class="px-4 py-3 border-b">

        <label class="text-sm text-gray-500">Customer</label>

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
      <div class="sticky top-4 rounded-2xl bg-white border shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b">
          <div id="selectedCustomerNameCart" class="text-base font-semibold text-center text-slate-800">Walk-in Customer</div>
          <div id="selectedCustomerPhoneCart" class="text-sm text-slate-500 text-center">-</div>
        </div>

        <div class="px-4 py-6 space-y-3 text-sm text-slate-600">
          <p class="font-semibold text-slate-800">Cart</p>
          <p class="text-xs text-slate-500">Loading cart from Exotic… If this message stays visible, refresh the page or open the browser console for errors.</p>
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
          <label class="text-gray-500">First Name</label>
          <input name="first_name" required class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Last Name</label>
          <input name="last_name" class="w-full border rounded px-2 py-1.5" placeholder="Optional">
        </div>

        <div>
          <label class="text-gray-500">Mobile</label>
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
          <label class="text-gray-500">State</label>
          <input name="state" class="w-full border rounded px-2 py-1.5">
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
          <label class="text-gray-500">State</label>
          <input name="shipping_state" class="w-full border rounded px-2 py-1.5">
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
          <label class="text-xs text-slate-500">Payment stage</label>
          <select id="payment_stage" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2">
            <option value="final">Final</option>
            <option value="partial">Partial</option>
            <option value="advance">Advance</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-slate-500">Payment mode</label>
          <select id="payment_mode" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2">
            <option value="cash">Cash</option>
            <option value="upi">UPI</option>
            <option value="bank_transfer">Bank transfer</option>
            <option value="pos_machine">POS machine</option>
            <option value="razorpay">Razorpay</option>
            <option value="cheque">Cheque</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-slate-500">Payment date</label>
          <input type="date" id="payment_date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2">
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-slate-500">Amount (₹)</label>
          <input type="number" step="0.01" min="0" id="payment_amount" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 tabular-nums" placeholder="0.00">
        </div>
        <div>
          <label class="text-xs text-slate-500">Transaction ID</label>
          <input type="text" id="transaction_id" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" placeholder="Required for Razorpay">
          <p id="transaction_id_required_hint" class="hidden mt-1 text-[11px] text-amber-700">Razorpay requires a transaction ID.</p>
        </div>
      </div>
      <div>
        <label class="text-xs text-slate-500">Note (optional)</label>
        <textarea id="payment_note" rows="2" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2"></textarea>
      </div>
      <div id="paymentModalOrderApiPanel" class="hidden rounded-lg border border-slate-200 bg-slate-900 p-3">
        <div class="flex items-center justify-between gap-2 mb-2">
          <span class="text-xs font-semibold text-white">Last order-create API</span>
          <button type="button" id="paymentModalOrderApiFullBtn" class="text-[11px] text-orange-300 hover:text-white">Open in debug</button>
        </div>
        <pre id="paymentModalOrderApiPre" class="max-h-40 overflow-auto text-[10px] leading-snug text-slate-100 whitespace-pre-wrap break-words"></pre>
      </div>
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
  <div class="relative mx-auto mt-10 w-[96%] max-w-4xl rounded-2xl bg-white shadow-xl">
    <div class="flex items-center justify-between border-b px-6 py-4">
      <h2 class="text-lg font-semibold">Confirm Billing &amp; Shipping Details</h2>
      <button type="button" onclick="closeAddressConfirmModal()" class="text-xl text-gray-500 hover:text-gray-800">✕</button>
    </div>
    <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
      <div class="space-y-3">
        <h3 class="text-sm font-semibold text-slate-800">Billing Information</h3>
        <div class="grid grid-cols-2 gap-3">
          <input id="confirm_first_name" class="w-full border rounded px-3 py-2 text-sm" placeholder="First Name">
          <input id="confirm_last_name" class="w-full border rounded px-3 py-2 text-sm" placeholder="Last Name">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <input id="confirm_email" class="w-full border rounded px-3 py-2 text-sm" placeholder="Email">
          <input id="confirm_phone" class="w-full border rounded px-3 py-2 text-sm" placeholder="Phone">
        </div>
        <input id="confirm_address1" class="w-full border rounded px-3 py-2 text-sm" placeholder="Address 1">
        <input id="confirm_address2" class="w-full border rounded px-3 py-2 text-sm" placeholder="Address 2">
        <div class="grid grid-cols-2 gap-3">
          <input id="confirm_city" class="w-full border rounded px-3 py-2 text-sm" placeholder="City">
          <input id="confirm_state" class="w-full border rounded px-3 py-2 text-sm" placeholder="State">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <input id="confirm_zip" class="w-full border rounded px-3 py-2 text-sm" placeholder="ZIP">
          <input id="confirm_country" class="w-full border rounded px-3 py-2 text-sm" placeholder="Country">
        </div>
        <input id="confirm_gstin" class="w-full border rounded px-3 py-2 text-sm" placeholder="GSTIN (optional)">
      </div>
      <div class="space-y-3">
        <h3 class="text-sm font-semibold text-slate-800">Shipping Information</h3>
        <div class="grid grid-cols-2 gap-3">
          <input id="confirm_sfirst_name" class="w-full border rounded px-3 py-2 text-sm" placeholder="Shipping First Name">
          <input id="confirm_slast_name" class="w-full border rounded px-3 py-2 text-sm" placeholder="Shipping Last Name">
        </div>
        <input id="confirm_sphone" class="w-full border rounded px-3 py-2 text-sm" placeholder="Shipping Phone">
        <input id="confirm_saddress1" class="w-full border rounded px-3 py-2 text-sm" placeholder="Shipping Address 1">
        <input id="confirm_saddress2" class="w-full border rounded px-3 py-2 text-sm" placeholder="Shipping Address 2">
        <div class="grid grid-cols-2 gap-3">
          <input id="confirm_scity" class="w-full border rounded px-3 py-2 text-sm" placeholder="Shipping City">
          <input id="confirm_sstate" class="w-full border rounded px-3 py-2 text-sm" placeholder="Shipping State">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <input id="confirm_szip" class="w-full border rounded px-3 py-2 text-sm" placeholder="Shipping ZIP">
          <input id="confirm_scountry" class="w-full border rounded px-3 py-2 text-sm" placeholder="Shipping Country">
        </div>
      </div>
    </div>
    <div class="flex justify-end gap-3 border-t px-6 py-4">
      <button type="button" onclick="closeAddressConfirmModal()" class="rounded-lg bg-gray-300 px-5 py-2 text-gray-700 hover:bg-gray-400">Cancel</button>
      <button type="button" id="confirmAddressSubmitBtn" class="rounded-lg bg-orange-600 px-5 py-2 text-white hover:bg-orange-700">
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
  function openPaymentModal() {
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
    var ct = typeof window.getPosCartTotalsForCheckout === "function" ? window.getPosCartTotalsForCheckout() : null;
    var pa = document.getElementById("payment_amount");
    if (pa && ct && ct.grandTotal != null && !isNaN(parseFloat(String(ct.grandTotal)))) {
      pa.value = String(ct.grandTotal);
    }
    pm.classList.remove("hidden");
  }

  window.openOrderCreateApiResponseModal = function () {
    var pre = document.getElementById("paymentModalOrderApiPre");
    var panel = document.getElementById("paymentModalOrderApiPanel");
    if (!pre || !panel) {
      return;
    }
    var d = window.__posLastOrderCreateDebug;
    try {
      pre.textContent = d ? JSON.stringify(d, null, 2) : "No order-create debug stored yet.";
    } catch (e) {
      pre.textContent = String(d);
    }
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
    document.getElementById("addressConfirmModal").classList.add("hidden");
  }

  function getSelectedCustomerId() {
    var fromSelect = typeof jQuery !== "undefined" ? jQuery("#customerSelect").val() : document.getElementById("customerSelect").value;
    if (Array.isArray(fromSelect)) {
      fromSelect = fromSelect[0] || "";
    }
    return (fromSelect && String(fromSelect)) || (window.POS_SESSION_CUSTOMER_ID && String(window.POS_SESSION_CUSTOMER_ID)) || "";
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
      confirm_phone: firstNonEmpty(billing.phone, billing.mobile, billing.billing_mobile),
      confirm_address1: firstNonEmpty(billing.address1, billing.address_line1, billing.billing_address_line1),
      confirm_address2: firstNonEmpty(billing.address2, billing.address_line2, billing.billing_address_line2),
      confirm_city: firstNonEmpty(billing.city),
      confirm_state: firstNonEmpty(billing.state),
      confirm_zip: firstNonEmpty(billing.zip, billing.zipcode),
      confirm_country: firstNonEmpty(billing.country, "IN"),
      confirm_gstin: firstNonEmpty(billing.gstin),

      // Shipping: support normalized keys + DB/raw aliases.
      confirm_sfirst_name: shippingFirstName,
      confirm_slast_name: shippingLastName,
      confirm_saddress1: firstNonEmpty(shipping.saddress1, shipping.shipping_address_line1, shipping.address1, shipping.address_line1),
      confirm_saddress2: firstNonEmpty(shipping.saddress2, shipping.shipping_address_line2, shipping.address2, shipping.address_line2),
      confirm_scity: firstNonEmpty(shipping.scity, shipping.shipping_city, shipping.city),
      confirm_sstate: firstNonEmpty(shipping.sstate, shipping.shipping_state, shipping.state),
      confirm_szip: firstNonEmpty(shipping.szip, shipping.shipping_zipcode, shipping.zip, shipping.zipcode),
      confirm_scountry: firstNonEmpty(shipping.scountry, shipping.shipping_country, shipping.country, "IN"),
      confirm_sphone: firstNonEmpty(shipping.sphone, shipping.shipping_mobile, shipping.mobile, shipping.phone)
    };
    Object.keys(map).forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.value = map[id];
    });
  }

  /** When shipping is blank but billing is present, mirror billing into shipping (same-as-billing / walk-in). */
  function applyShippingFallbackFromBilling() {
    function read(id) {
      var el = document.getElementById(id);
      return el ? String(el.value || "").trim() : "";
    }
    function write(id, v) {
      var el = document.getElementById(id);
      if (el) el.value = v;
    }
    var sf = read("confirm_sfirst_name");
    var sl = read("confirm_slast_name");
    if (sf === "" && sl === "") {
      var bf = read("confirm_first_name");
      var bl = read("confirm_last_name");
      if (bf !== "") {
        write("confirm_sfirst_name", bf);
        write("confirm_slast_name", bl);
      }
    }
    if (read("confirm_sphone") === "" && read("confirm_phone") !== "") {
      write("confirm_sphone", read("confirm_phone"));
    }
    if (read("confirm_saddress1") === "" && read("confirm_address1") !== "") {
      write("confirm_saddress1", read("confirm_address1"));
    }
    if (read("confirm_saddress2") === "" && read("confirm_address2") !== "") {
      write("confirm_saddress2", read("confirm_address2"));
    }
    if (read("confirm_scity") === "" && read("confirm_city") !== "") {
      write("confirm_scity", read("confirm_city"));
    }
    if (read("confirm_sstate") === "" && read("confirm_state") !== "") {
      write("confirm_sstate", read("confirm_state"));
    }
    if (read("confirm_szip") === "" && read("confirm_zip") !== "") {
      write("confirm_szip", read("confirm_zip"));
    }
    if (read("confirm_scountry") === "" && read("confirm_country") !== "") {
      write("confirm_scountry", read("confirm_country"));
    }
  }

  function getAddressConfirmPayload() {
    var read = function(id) {
      var el = document.getElementById(id);
      return el ? String(el.value || "").trim() : "";
    };
    var shippingFirstName = read("confirm_sfirst_name");
    var shippingLastName = read("confirm_slast_name");
    var shippingFullName = [shippingFirstName, shippingLastName].filter(Boolean).join(" ").trim();
    return {
      confirm_address_submit: "1",
      confirm_first_name: read("confirm_first_name"),
      confirm_last_name: read("confirm_last_name"),
      confirm_email: read("confirm_email"),
      confirm_phone: read("confirm_phone"),
      confirm_address1: read("confirm_address1"),
      confirm_address2: read("confirm_address2"),
      confirm_city: read("confirm_city"),
      confirm_state: read("confirm_state"),
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
      confirm_sstate: read("confirm_sstate"),
      confirm_szip: read("confirm_szip"),
      confirm_scountry: read("confirm_scountry"),
      confirm_sphone: read("confirm_sphone")
    };
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
        applyShippingFallbackFromBilling();
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
    try {
      pre.textContent = JSON.stringify(debug, null, 2);
    } catch (e) {
      pre.textContent = String(debug);
    }
    panel.classList.remove("hidden");
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
    function syncRazorpayTxnHint() {
      if (!paymentModeSelect || !txnRequiredHint) {
        return;
      }
      txnRequiredHint.classList.toggle("hidden", paymentModeSelect.value !== "razorpay");
    }
    if (paymentModeSelect) {
      paymentModeSelect.addEventListener("change", syncRazorpayTxnHint);
      syncRazorpayTxnHint();
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

        loadAndOpenAddressConfirm(customerId);
      });
    }

    var confirmAddressSubmitBtn = document.getElementById("confirmAddressSubmitBtn");
    if (confirmAddressSubmitBtn) {
      confirmAddressSubmitBtn.addEventListener("click", function() {
        var payload = getAddressConfirmPayload();
        if (!payload.confirm_first_name || !payload.confirm_phone || !payload.confirm_state || !payload.confirm_zip) {
          showToast("⚠ Billing details are incomplete.", "red");
          return;
        }
        if (!payload.confirm_sfirst_name || !payload.confirm_sphone || !payload.confirm_sstate) {
          showToast("⚠ Shipping details are incomplete.", "red");
          return;
        }
        createOrderNow(payload);
      });
    }
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
    var body = Object.assign({}, addressPayload, {
      customer_id: String(customerId),
      payment_stage: payStage,
      payment_mode: payMode,
      payment_amount: payAmt,
      transaction_id: txn,
      payment_note: note,
      order_total: orderTotal
    });
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
    document.getElementById("customerModal").classList.remove("hidden")
  }

  function closeCustomerModal() {
    document.getElementById("customerModal").classList.add("hidden")
  }
  let customerData = {};
  document.addEventListener("DOMContentLoaded", function () {
    var customerForm = document.getElementById("customerForm");
    if (!customerForm) return;

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
      "state": "shipping_state",
      "zipcode": "shipping_zipcode"
    };

    Object.keys(map).forEach(billingField => {

      const shippingField = map[billingField];

      const billingInput = document.querySelector(`[name="${billingField}"]`);
      const shippingInput = document.querySelector(`[name="${shippingField}"]`);

      if (!billingInput || !shippingInput) return;

      if (checkbox.checked) {

        shippingInput.value = billingInput.value;
        // shippingInput.readOnly = true;
        shippingInput.classList.add("bg-gray-100");

        /* LIVE SYNC */
        billingInput.addEventListener("input", function() {
          if (checkbox.checked) {
            shippingInput.value = billingInput.value;
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