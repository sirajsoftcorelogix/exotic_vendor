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
          <div id="pmStockWarning" class="hidden mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] leading-snug text-amber-900"></div>
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

          <!-- CUSTOM ADDONS -->
          <div id="pmCustomAddonsWrapper" class="mt-4">
            <div class="text-xs font-semibold text-gray-700 mb-2">
              Custom add-ons
            </div>
            <div class="flex flex-wrap items-end gap-2">
              <div class="flex min-w-[140px] flex-1 flex-col gap-0.5">
                <label for="pmCustomAddonName" class="text-[10px] text-gray-500">Name (letters, underscore; spaces → _)</label>
                <input
                  type="text"
                  id="pmCustomAddonName"
                  autocomplete="off"
                  placeholder="Wooden_Frame"
                  class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs" />
              </div>
              <div class="flex w-24 flex-col gap-0.5">
                <label for="pmCustomAddonPrice" class="text-[10px] text-gray-500">Price (₹)</label>
                <input
                  type="number"
                  id="pmCustomAddonPrice"
                  min="0"
                  step="0.01"
                  autocomplete="off"
                  placeholder="1200"
                  class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs" />
              </div>
              <button
                type="button"
                id="pmCustomAddonAddBtn"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                Add
              </button>
            </div>
            <p id="pmCustomAddonError" class="mt-1 hidden text-[10px] text-red-600"></p>
            <div id="pmCustomAddonsList" class="mt-2 space-y-2"></div>
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
