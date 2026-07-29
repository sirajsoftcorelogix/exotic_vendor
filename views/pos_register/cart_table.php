<?php
$customerLabel = 'Walk-in Customer';
$customerPhone = '-';
if (!empty($selected_customer) && is_array($selected_customer)) {
    $customerLabel = trim((string)($selected_customer['name'] ?? '')) ?: 'Walk-in Customer';
    $customerPhone = trim((string)($selected_customer['phone'] ?? '')) ?: '-';
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
            <span class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-gray-700">
              <i class="fas fa-user text-slate-500 text-xs" aria-hidden="true"></i>
              <span class="font-semibold"><?= htmlspecialchars($customerLabel) ?></span>
              <span class="text-slate-500"><?= htmlspecialchars($customerPhone) ?></span>
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
      #posCartTablePage .pos-cart-table-line td:nth-child(2) {
        max-width: none;
      }
      #posCartTablePage .pos-cart-draft-section,
      #posCartTablePage .pos-cart-table-wrap {
        max-width: none;
      }
    </style>

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

<script>
  window.POS_CART_TABLE_PAGE = true;
  window.POS_INITIAL_CUSTOMER = <?= json_encode(isset($selected_customer) ? $selected_customer : null, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
</script>
<script src="<?php echo base_url(); ?>assets/js/pos_cart_hooks.js"></script>
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
