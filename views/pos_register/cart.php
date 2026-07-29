<?php
$posCheckoutApiDebug = isset($_SESSION['user']['email'])
    && strtolower(trim((string) $_SESSION['user']['email'])) === 'siraj.php@gmail.com';
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
<div class="min-h-screen pos-cart-page bg-slate-50/40">
  <style>
    .pos-cart-page {
      font-size: 14px;
      line-height: 1.45;
      color: #334155;
    }
    .pos-cart-page .pos-exotic-cart-panel {
      border: none !important;
      background: transparent !important;
      box-shadow: none !important;
      padding: 0 !important;
      margin: 0 !important;
    }
    .pos-cart-page-table-wrap {
      border-radius: 0.75rem;
      border: 1px solid #e2e8f0;
      background: #fff;
      overflow: hidden;
    }
    .pos-cart-page-table thead {
      background: linear-gradient(to right, #fffbeb, #fff);
    }
    .pos-cart-page-table tbody tr:hover {
      background: rgba(255, 247, 237, 0.35);
    }
    .pos-cart-page-table .pos-cart-line-thumb {
      width: 3rem;
      height: 3rem;
      object-fit: contain;
      border-radius: 0.375rem;
      border: 1px solid #e2e8f0;
      background: #fff;
      padding: 0.125rem;
    }
    .pos-cart-page-table .pos-cart-line-thumb-placeholder {
      width: 3rem;
      height: 3rem;
      border-radius: 0.375rem;
      border: 1px dashed #cbd5e1;
      background: #f8fafc;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #94a3b8;
      font-size: 0.75rem;
    }
    .pos-cart-page-summary-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }
    @media (min-width: 1024px) {
      .pos-cart-page-summary-grid {
        grid-template-columns: minmax(0, 1fr) minmax(18rem, 22rem);
        align-items: start;
      }
    }
  </style>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl border border-orange-200/45 bg-gradient-to-br from-orange-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-orange-900/[0.04] mb-6">
      <div class="relative px-5 py-6 sm:px-7 sm:py-7 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="min-w-0">
          <div class="inline-flex items-center gap-2 rounded-full border border-orange-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-orange-900/90 shadow-sm mb-3">
            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-orange-100 text-orange-700">
              <i class="fas fa-shopping-cart text-[11px]" aria-hidden="true"></i>
            </span>
            <span>POS Register</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900">Cart</h1>
          <p class="mt-2 text-sm text-gray-600 max-w-2xl">
            Add items by SKU, apply line and cart discounts, then place your order.
            <?php if (!empty($warehouse_name)): ?>
              Store: <strong class="text-gray-800"><?= htmlspecialchars((string) $warehouse_name) ?></strong>
            <?php endif; ?>
          </p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2 justify-end">
          <a href="?page=pos_register&action=list"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 transition whitespace-nowrap">
            <i class="fas fa-arrow-left text-xs opacity-90" aria-hidden="true"></i>
            Back to register
          </a>
        </div>
      </div>
    </div>

    <!-- Customer -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
      <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-slate-50 to-white">
        <h2 class="text-sm font-semibold text-gray-900">Customer <span class="text-red-600">*</span></h2>
        <p class="text-xs text-gray-500 mt-0.5">Required before checkout.</p>
      </div>
      <div class="p-5">
        <div class="flex flex-col sm:flex-row gap-3 sm:items-start max-w-2xl">
          <div class="flex-1 min-w-0">
            <select id="customerSelect"
              name="customer_id"
              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white"
              aria-label="Search customer">
            </select>
          </div>
          <button type="button" onclick="openCustomerModal()"
            class="shrink-0 inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 transition">
            <i class="fas fa-user-plus text-xs" aria-hidden="true"></i>
            Add customer
          </button>
        </div>
        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm">
          <span id="selectedCustomerNameCart" class="font-semibold text-slate-800">Walk-in Customer</span>
          <span id="selectedCustomerPhoneCart" class="text-slate-500">-</span>
        </div>
      </div>
    </div>

    <!-- Cart lines + summary (rendered by pos_cart_hooks.js) -->
    <div class="pos-cart-page-summary-grid">
      <div class="min-w-0">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
            <div>
              <h2 class="text-sm font-semibold text-gray-900">Line items</h2>
              <p class="text-xs text-gray-500 mt-0.5">Search SKU to add rows; edit qty, price, and discounts inline.</p>
            </div>
          </div>
          <div class="p-4 sm:p-5">
            <div id="posExoticCartPanel" class="pos-exotic-cart-panel" aria-live="polite" aria-busy="true">
              <div class="py-8 text-center text-sm text-slate-500">
                <i class="fas fa-spinner fa-spin text-orange-500 mr-2" aria-hidden="true"></i>
                Loading cart…
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    window.POS_CART_PAGE_MODE = true;
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
      confirm_sphone: "8031404444"
    };
  </script>

  <?php include __DIR__ . '/partials/pos_modals_and_checkout.php'; ?>
</div>
