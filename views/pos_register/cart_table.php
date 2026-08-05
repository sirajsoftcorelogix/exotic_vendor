<?php
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
<div id="posCartTablePage" class="min-h-screen bg-slate-50" data-pos-cart-table-page="1">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="relative overflow-hidden rounded-2xl border border-orange-200/45 bg-gradient-to-br from-orange-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-orange-900/[0.04] mb-6">
      <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-orange-300/20 blur-3xl" aria-hidden="true"></div>
      <div class="relative px-5 py-7 sm:px-8 sm:py-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <div class="min-w-0 max-w-3xl">
          <div class="inline-flex items-center gap-2 rounded-full border border-orange-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-orange-900/90 shadow-sm backdrop-blur-sm mb-4">
            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-orange-100 text-orange-700">
              <i class="fas fa-shopping-cart text-[11px]" aria-hidden="true"></i>
            </span>
            <span>POS Register · Cart table</span>
          </div>
          <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">Cart — table view</h1>
          <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
            Full-width cart workspace for bulk SKU entry, quantity edits, discounts, and checkout.
          </p>
          <div class="mt-4 flex flex-wrap items-center gap-3 text-sm">
            <span class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 font-medium text-gray-700">
              <i class="fas fa-store-alt text-orange-600 text-xs" aria-hidden="true"></i>
              <?= htmlspecialchars($warehouse_name ?? 'No Warehouse') ?>
            </span>
          </div>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2 lg:pl-4 lg:self-center">
          <button
            type="button"
            id="posCartTableRefreshBtn"
            class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
            <i class="fas fa-sync-alt text-xs" aria-hidden="true"></i>
            Refresh cart
          </button>
          <a
            href="?page=pos_register&amp;action=list"
            class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-orange-300 bg-orange-600 text-white text-sm font-semibold hover:bg-orange-700 transition">
            <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i>
            Back to POS
          </a>
        </div>
      </div>
    </div>

    <style>
      #posCartTablePage .pos-cart-table-page-panel {
        font-size: 14px;
      }
      #posCartTablePage .pos-cart-table th,
      #posCartTablePage .pos-cart-table td {
        font-size: 0.8125rem;
      }
      #posCartTablePage .pos-cart-table th {
        font-size: 0.75rem;
      }
      #posCartTablePage .pos-cart-table .pos-cart-table-metric-col,
      .pos-cart-table-page-panel .pos-cart-table .pos-cart-table-metric-col {
        padding-left: 1rem;
        padding-right: 1rem;
      }
      @media (min-width: 768px) {
        #posCartTablePage .pos-cart-table .pos-cart-table-metric-col,
        .pos-cart-table-page-panel .pos-cart-table .pos-cart-table-metric-col {
          padding-left: 1.25rem;
          padding-right: 1.25rem;
        }
      }
      #posCartTablePage .pos-cart-table-line td:nth-child(3) {
        max-width: none;
      }
      #posCartTablePage .pos-cart-table .pos-cart-table-line img,
      #posCartTablePage .pos-cart-line-image-enlarge {
        display: block;
      }
      #posCartTablePage .pos-cart-line-image-enlarge {
        padding: 0;
      }
      #posCartImageLightboxImg {
        transition: opacity 0.15s ease;
      }
      #posCartTablePage .pos-cart-draft-section,
      #posCartTablePage .pos-cart-table-wrap {
        max-width: none;
      }
      #posCartTablePage .pos-cart-draft-table-wrap {
        overflow: visible;
      }
      #posCartTablePage .pos-cart-draft-row td {
        overflow: visible;
        vertical-align: top;
      }
      #posCartTablePage .pos-cart-draft-suggest.hidden {
        display: none !important;
        height: 0;
        max-height: 0;
        overflow: hidden;
        padding: 0;
        margin: 0;
        border: 0;
      }
      #posCartTablePage .pos-cart-draft-suggest:not(.hidden) {
        display: block;
      }
      #posCartTablePage .pos-cart-summary-discount-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        align-items: stretch;
      }
      @media (min-width: 768px) {
        #posCartTablePage .pos-cart-summary-discount-row {
          grid-template-columns: 1fr 1fr;
        }
      }
      #posCartTablePage .pos-cart-summary-box,
      #posCartTablePage .pos-cart-discount-box {
        min-width: 0;
      }
      #posCartTablePage .pos-cart-line-adjust {
        margin-top: 0.5rem;
      }
      #posCartTablePage .pos-cart-line-adjust .flex.flex-wrap {
        max-width: 100%;
      }
      #posCartTablePage .pos-cart-line-disc-toggle {
        padding: 0;
        background: none;
        border: 0;
        cursor: pointer;
        text-align: left;
      }
      #posCartTablePage .pos-customer-select-card .select2-container {
        min-width: 0;
      }
    </style>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-4 pos-customer-select-card">
      <label class="text-sm text-gray-500">Customer <span class="text-red-600">*</span></label>
      <div class="mt-2 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex min-w-0 flex-1 gap-2">
          <select
            id="customerSelect"
            name="customer_id"
            class="w-full min-w-0 border rounded-lg px-3 py-2.5 text-base"
            aria-label="Search customer"></select>
          <button
            type="button"
            onclick="openCustomerModal()"
            class="shrink-0 rounded-lg bg-orange-600 px-3 py-2 text-base text-white hover:bg-orange-700"
            title="Add customer"
            aria-label="Add customer">+</button>
        </div>
        <div class="shrink-0 text-sm lg:text-right">
          <div id="posCartTableCustomerName" onclick="editSelectedCustomer()" class="font-semibold text-slate-800 cursor-pointer hover:text-orange-600 hover:underline" title="Click to edit customer details"><?= htmlspecialchars($customerLabel) ?></div>
          <div id="posCartTableCustomerPhone" class="text-slate-500"><?= htmlspecialchars($customerSubtext) ?></div>
          <div id="posCartTableCustomerResidence" class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($customerResidenceSubtext) ?></div>
        </div>
      </div>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
      <div data-pos-cart-panel-mount class="p-4 sm:p-6">
        <div
          id="posExoticCartPanel"
          class="pos-exotic-cart-panel pos-cart-table-page-panel text-sm text-slate-800"
          aria-live="polite"
          aria-busy="false"></div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/product_modal.php'; ?>
<?php require __DIR__ . '/partials/customer_modal.php'; ?>

<script>
  window.POS_CART_TABLE_PAGE = true;
  window.POS_SESSION_CUSTOMER_ID = <?= json_encode(!empty($_SESSION['pos_customer_id']) ? (string)(int)$_SESSION['pos_customer_id'] : '') ?>;
  window.POS_INITIAL_CUSTOMER = <?= json_encode(isset($selected_customer) ? $selected_customer : null, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
</script>
<script src="<?php echo base_url(); ?>assets/js/pos_message_modal.js"></script>
<script src="<?php echo base_url(); ?>assets/js/pos_customer.js"></script>
<script src="<?php echo base_url(); ?>assets/js/pos_cart_hooks.js"></script>
<script src="<?php echo base_url(); ?>assets/js/pos.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var refreshBtn = document.getElementById('posCartTableRefreshBtn');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', function () {
        if (typeof window.refreshCart === 'function') {
          window.refreshCart();
        }
      });
    }
  });
</script>
