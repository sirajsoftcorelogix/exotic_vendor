<div class="min-h-screen">
  <a href="test_create_order_static();"></a>
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

        <!-- Cart Icon (clickable) -->
        <button
          onclick="openCart()"
          class="flex h-10 w-10 items-center justify-center rounded-xl border hover:bg-gray-100"
          title="Cart">
          <svg xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5 text-gray-700"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 3h2l.4 2M7 13h10l4-8H5.4
                    M7 13l-1.35 2.7A1 1 0 007.55 17h8.9
                    a1 1 0 00.9-.55L19 13
                    M7 13L5.4 5
                    M16 21a1 1 0 100-2
                    1 1 0 000 2z
                    M8 21a1 1 0 100-2
                    1 1 0 000 2z" />
          </svg>
        </button>

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
        <div class="mt-3 h-[70vh] overflow-y-auto no-scrollbar">
          <div
            class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
            id="productsCards">
          </div>
        </div>
      </div>
    </section>

    <!-- ===== PAYMENT / CART ===== -->
    <!-- CUSTOMER SELECT -->

    <?php

    $cart = $cartData['items'] ?? [];
    ?>

    <aside class="col-span-12 lg:col-span-3">
      <div class="px-4 py-3 border-b">

        <label class="text-xs text-gray-500">Customer</label>

        <div class="flex gap-2 mt-1">

          <select id="customerSelect"
            name="customer_id"
            class="w-full border rounded-lg px-3 py-2 text-sm">

            <option value="">Select Customer</option>

            <?php foreach ($customers as $c): ?>

              <option value="<?= $c['id'] ?>"
                data-name="<?= htmlspecialchars($c['name']) ?>"
                data-phone="<?= htmlspecialchars($c['phone']) ?>"
                data-email="<?= htmlspecialchars($c['email']) ?>"
                <?= (!empty($_SESSION['pos_customer_id']) && $_SESSION['pos_customer_id'] == $c['id']) ? 'selected' : '' ?>>

                <?= htmlspecialchars($c['name']) ?> | <?= $c['phone'] ?> | <?= $c['email'] ?>

              </option>

              </option>

            <?php endforeach; ?>

          </select>

          <button onclick="openCustomerModal()"
            class="bg-orange-600 text-white px-3 rounded-lg text-sm hover:bg-orange-700">
            +
          </button>


        </div>

      </div>
      <div class="sticky top-4 rounded-2xl bg-white border shadow-sm overflow-hidden">

        <!-- USER -->
        <div class="px-4 py-3 border-b">
          <div class="text-sm font-semibold text-center">
            <?= htmlspecialchars($_SESSION['user']['name'] ?? ' ') ?>
          </div>

          <div class="text-[11px] text-slate-500 text-center">
            <?= htmlspecialchars($_SESSION['user']['phone'] ?? '') ?>
          </div>
        </div>

        <?php if (isset($_SESSION['cart_success'])): ?>
          <div class="text-green-600 text-xs mb-2 px-4">
            <?= $_SESSION['cart_success'] ?>
          </div>
          <?php unset($_SESSION['cart_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['cart_error'])): ?>
          <div class="text-red-600 text-xs mb-2 px-4">
            <?= htmlspecialchars((string)$_SESSION['cart_error']) ?>
          </div>
          <?php unset($_SESSION['cart_error']); ?>
        <?php endif; ?>

        <div class="px-4 py-3 space-y-4 text-[12px]">

          <!-- PRODUCTS -->
          <div class="space-y-3" id="cartItems">

            <?php if (empty($cart)): ?>

              <div class="py-8 text-center text-gray-400 text-xs">
                Your cart is empty
              </div>

            <?php else: ?>

              <?php foreach ($cart as $item): ?>

                <div class="flex gap-3">

                  <img
                    src="<?= htmlspecialchars($item['imageurl'] ?? 'https://dummyimage.com/80x80/e5e7eb/6b7280&text=No+Image') ?>"
                    class="h-12 w-12 rounded-lg bg-slate-50 object-contain">

                  <div class="flex-1 min-w-0">

                    <div class="text-[9px] leading-snug line-clamp-2">
                      <?= htmlspecialchars($item['name']) ?>
                    </div>

                    <div class="mt-1 flex items-center justify-between">
                      <span class="text-orange-600 font-semibold">
                        <?= currencySymbol($cartData['currency']) ?> <?= number_format($item['price'], 2) ?>
                      </span>
                    </div>

                    <div class="mt-2 flex items-center justify-between">

                      <!-- QTY -->
                      <div class="flex items-center border rounded-md overflow-hidden">

                        <form method="POST" action="?page=pos_register&action=change-qty">
                          <!-- <input type="hidden" name="action" value="change_qty"> -->
                          <input type="hidden" name="cartref" value="<?= $item['cartref'] ?>">
                          <button type="submit"
                            name="newqty"
                            value="<?= $item['quantity'] - 1 ?>"
                            class="h-6 w-6 text-slate-600">−</button>
                        </form>

                        <span class="h-6 w-7 flex items-center justify-center font-semibold">
                          <?= $item['quantity'] ?>
                        </span>

                        <form method="POST" action="?page=pos_register&action=change-qty">
                          <!-- <input type="hidden" name="action" value="change_qty"> -->
                          <input type="hidden" name="cartref" value="<?= $item['cartref'] ?>">
                          <button type="submit"
                            name="newqty"
                            value="<?= $item['quantity'] + 1 ?>"
                            class="h-6 w-6 text-slate-600">+</button>
                        </form>

                      </div>

                      <!-- REMOVE -->
                      <form method="POST" action="?page=pos_register&action=remove-item">
                        <!-- <input type="hidden" name="action" value="remove"> -->
                        <input type="hidden" name="cartref" value="<?= $item['cartref'] ?>">
                        <button type="submit" class="text-[10px] text-red-600 hover:underline">
                          Remove
                        </button>
                      </form>

                    </div>

                  </div>

                </div>
             
                <?php if (!empty($item['shipping']) && $item['shipping'] > 0): ?>

                  <!-- <div class="flex gap-2"> -->

                  <!-- <div class="flex items-center gap-2 rounded-lg bg-green-100 px-3 py-2 justify-center "> -->
                  <div class="flex items-center justify-center gap-2 rounded-lg bg-green-100 px-3 py-2 text-center">
                    <div class="flex h-6 w-6 items-center justify-center rounded-md">

                      <form method="POST" action="?page=pos_register&action=toggle-shipping">

                        <!-- <input type="hidden" name="action" value="toggle_express_shipping"> -->
                        <input type="hidden" name="cartid" value="<?= $item['cartref'] ?>">

                        <input type="hidden" name="action"
                          value="<?= $item['express_selected'] ? 'delete' : 'add' ?>">

                        <input type="checkbox"
                          <?= $item['express_selected'] ? 'checked' : '' ?>
                          onchange="this.form.submit()"
                          class="h-4 w-4 rounded border-slate-300 text-green-600">

                      </form>

                    </div>

                    <div>


                      <div class="text-[9px] text-green-900 leading-tight">
                        <?= htmlspecialchars($item['shipping_title'] ?? 'Express Shipping') ?>
                      </div>

                      <div class="text-[11px] font-semibold text-green-900">
                        <?= currencySymbol($cartData['currency']) ?> <?= number_format($item['shipping_per_unit'], 2) ?>
                      </div>

                    </div>

                  </div>


                  <!-- <div class="flex items-center gap-2 rounded-lg bg-green-200 px-3 py-2">

                      <div class="flex h-6 w-6 items-center justify-center rounded-md bg-orange-500">

                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-yellow-600">

                          <svg class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="3">
                            <polyline points="20 6 9 17 4 12"></polyline>
                          </svg>

                        </div>
                      </div>

                      <div>
                        <div class="text-[10px] font-semibold text-green-900">
                          <?= htmlspecialchars($item['shipping_longtitle']) ?>
                        </div>
                      </div>

                    </div> -->

                  <!-- </div> -->

                <?php endif; ?>

              <?php endforeach; ?>

            <?php endif; ?>

          </div>


          <?php $coupon = $_SESSION['discount_coupon']['discountcoupondetails'] ?? ''; ?>

          <?php if (empty($coupon)): ?>

            <!-- APPLY COUPON -->
            <form method="POST" action="?page=pos_register&action=apply-coupon" class="flex gap-2">

              <!-- <input type="hidden" name="action" value="apply_coupon"> -->

              <input
                name="coupon"
                class="w-2/3 rounded-lg border px-2 py-2 text-xs"
                placeholder="Coupon/Discount Code">

              <button
                type="submit"
                class="w-1/3 rounded-lg bg-black px-4 py-2 text-xs text-white">
                Apply
              </button>

            </form>

          <?php else: ?>

            <!-- COUPON APPLIED -->
            <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2">

              <span class="text-[11px] text-green-700 font-semibold">
                Coupon Applied: <?= htmlspecialchars(explode('|', $coupon)[0]) ?>
              </span>

              <form method="POST" action="?page=pos_register&action=remove-coupon">
                <!-- <input type="hidden" name="action" value="remove_coupon"> -->

                <button
                  type="submit"
                  class="text-[11px] text-red-600 font-semibold hover:underline">
                  Remove
                </button>

              </form>

            </div>

          <?php endif; ?>
          <?php if (!empty($cartData['custom_discount']) && $cartData['custom_discount'] > 0): ?>
            <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2">

              <span class="text-[11px] text-green-700 font-semibold">
                Custom Discount
                (- <?= currencySymbol($cartData['currency']) ?>
                <?= number_format($cartData['custom_discount'], 2) ?>)
              </span>

              <form method="POST" action="?page=pos_register&action=remove-custom-discount">
                <button
                  type="submit"
                  class="text-[11px] text-green-600 font-semibold hover:underline">
                  Remove
                </button>
              </form>

            </div>
          <?php endif; ?>
          <div id="couponMessage" class="text-[11px]"></div>
          <?php if (isset($_SESSION['coupon_message'])): ?>

            <div class="text-[11px] mt-1
        <?= $_SESSION['coupon_status'] == 'success'
              ? 'text-green-600'
              : 'text-red-600' ?>">

              <?= htmlspecialchars($_SESSION['coupon_message']) ?>

            </div>

          <?php
            unset($_SESSION['coupon_message']);
            unset($_SESSION['coupon_status']);
          endif;
          ?>
          <!-- ADDON -->
          <!-- <select class="w-full rounded-lg border px-3 py-2" id="addonSelect">
            <option value="0">Add-on services</option>
            <option value="150">Gift Wrap (+₹150)</option>
            <option value="500">Insurance (+₹500)</option>
          </select> -->
          <?php $voucher = $_SESSION['gift_voucher']['giftvoucherdetails'] ?? ''; ?>

          <?php if (empty($voucher)): ?>

            <!-- <form method="POST" action="?page=pos_register&action=apply-gift-voucher" class="flex gap-2 mt-2">

              <input
                name="voucher"
                class="w-2/3 rounded-lg border px-2 py-2 text-xs"
                placeholder="Gift Voucher Code">

              <button
                type="submit"
                class="w-1/3 rounded-lg bg-blue-600 px-4 py-2 text-xs text-white">
                Apply
              </button>

            </form> -->

          <?php else: ?>

            <!-- <div class="flex items-center justify-between bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 mt-2">

              <span class="text-[11px] text-blue-700 font-semibold">
                Voucher Applied
              </span>

              <form method="POST" action="?page=pos_register&action=remove-gift-voucher">
                <button
                  type="submit"
                  class="text-[11px] text-red-600 font-semibold hover:underline">
                  Remove
                </button>
              </form>

            </div> -->

          <?php endif; ?>
          <!-- TOTALS -->
          <div class="pt-2 border-t space-y-1.5">
            <?php if (!empty($cartData['discount'])): ?>
              <div class="flex justify-between text-green-600">
                <span>Coupon Discount</span>
                <span>- <?= currencySymbol($cartData['currency']) ?> <?= number_format($cartData['discount'], 2) ?></span>
              </div>
            <?php endif; ?>
            <div class="flex justify-between text-slate-600">
              <span>Sub Total</span>
              <span><?= currencySymbol($cartData['currency']) ?> <?= number_format($cartData['subtotal'] ?? 0, 2) ?></span>
            </div>

            <div class="flex justify-between text-slate-600">
              <span>GST</span>
              <span><?= currencySymbol($cartData['currency']) ?> <?= number_format($cartData['gst'] ?? 0, 2) ?></span>
            </div>

            <div class="flex justify-between font-semibold text-slate-900">
              <span>Total</span>
              <span><?= currencySymbol($cartData['currency']) ?> <?= number_format($cartData['grand_total'] ?? 0, 2) ?></span>
            </div>

          </div>

          <!-- ACTION -->
          <button id="applyCustomDiscountBtn"
            class="w-full rounded-xl bg-orange-600 py-3 text-white font-semibold">
            Apply Cart Discount
          </button>

          <button
            onclick="openPaymentModal()"
            class="w-full rounded-xl bg-orange-600 py-3 text-white font-semibold hover:bg-orange-700">
            Proceed to Payment
          </button>

        </div>
      </div>
    </aside>


  </main>
</div>
<!-- <a
  href="/?page=posinvoice&action=generate_pdf&invoice_id=49"
  target="_blank"
  class="px-4 py-2 bg-green-600 text-white rounded">
  TEST PRINT
</a> -->
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


            <form method="POST" action="?page=pos_register&action=cart-add">
              <!-- <input type="hidden" name="action" value="add_to_cart"> -->
              <input type="hidden" name="code" id="modal_product_code">
              <input type="hidden" name="stock_check_code" id="modal_stock_check_code" value="">
              <input type="hidden" name="qty" id="modal_qty" value="1">
              <input type="hidden" name="options" id="modal_options">
              <input type="hidden" name="variation" id="modal_variation">
              <button type="submit"
                class="rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                Add to Cart
              </button>
            </form>
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

  <div class="absolute inset-0 bg-black/40" onclick="closeCustomerModal()"></div>

  <div class="relative mx-auto mt-10 w-[95%] max-w-2xl rounded-2xl bg-white shadow-xl max-h-[85vh] flex flex-col">

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
          <input name="last_name" required class="w-full border rounded px-2 py-1.5">
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

<!-- PAYMENT MODAL -->
<div id="paymentModal" class="fixed inset-0 z-[9999] hidden">

  <div class="absolute inset-0 bg-black/40" onclick="closePaymentModal()"></div>

  <div class="relative mx-auto mt-20 w-[95%] max-w-2xl rounded-2xl bg-white shadow-xl">

    <!-- Header -->
    <div class="flex items-center justify-between border-b px-6 py-4">
      <h2 class="text-lg font-semibold">Payment</h2>

      <button onclick="closePaymentModal()" class="text-gray-500 hover:text-gray-800 text-xl">
        ✕
      </button>
    </div>

    <!-- Body -->
    <div class="p-6 space-y-4">

      <div class="grid grid-cols-3 gap-4">

        <!-- Payment Type -->
        <div>
          <label class="text-xs text-gray-600">Payment Type</label>

          <select name="payment_stage" id="payment_stage"
            class="w-full mt-1 border rounded-lg px-3 py-2 text-sm">

            <option value="final">Final</option>
            <option value="partial">Partial</option>
            <option value="advance">Advance</option>

          </select>
        </div>

        <!-- Payment Mode
        <div>
          <label class="text-xs text-gray-600">Payment Mode</label>

          <select name="payment_type" id="payment_mode"
            class="w-full mt-1 border rounded-lg px-3 py-2 text-sm">

            <option value="offline">Offline</option>
            <option value="cod">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="pos_machine">POS Machine</option>
            <option value="razorpay">Razorpay</option>
            <option value="specialpay">SpecialPay</option>
            <option value="cheque">Cheque</option>
            <option value="demand_draft">Demand Draft</option>

          </select>
        </div> -->
        <input type="hidden" name="payment_type" id="payment_mode" value="offline">

        <!-- Payment Date -->
        <div>
          <label class="text-xs text-gray-600">Payment Date</label>

          <input
            type="date"
            value="<?= date('Y-m-d') ?>"
            class="w-full mt-1 border rounded-lg px-3 py-2 text-sm">
        </div>

      </div>


      <div class="grid grid-cols-2 gap-4">

        <!-- Amount -->
        <div>
          <label class="text-xs text-gray-600">Amount</label>

          <input
            type="number"
            id="payment_amount"
            value="<?= $cartData['grand_total'] ?? 0 ?>"
            class="w-full mt-1 border rounded-lg px-3 py-2 text-sm">
        </div>

        <!-- Transaction ID -->
        <div>
          <label class="text-xs text-gray-600">Transaction ID</label>

          <input
            type="text"
            id="transaction_id"
            placeholder="Enter transaction id"
            class="w-full mt-1 border rounded-lg px-3 py-2 text-sm">
        </div>

      </div>


      <!-- Note -->
      <div>
        <label class="text-xs text-gray-600">Note</label>

        <textarea
          name="note"
          placeholder="Enter note"
          class="w-full mt-1 border rounded-lg px-3 py-2 text-sm h-24"></textarea>
      </div>

    </div>


    <!-- Footer -->
    <div class="flex justify-end gap-3 border-t px-6 py-4">

      <button
        onclick="closePaymentModal()"
        class="px-5 py-2 rounded-lg bg-gray-300 text-gray-700 hover:bg-gray-400">
        Cancel
      </button>

      <button
        id="placeOrderBtn"
        class="px-5 py-2 rounded-lg bg-orange-600 text-white hover:bg-orange-700">
        Confirm Order
      </button>

    </div>

  </div>
</div>
<!-- DISCOUNT MODAL -->
<div id="discountModal" class="fixed inset-0 z-[9999] hidden">

  <div class="absolute inset-0 bg-black/40" onclick="closeDiscountModal()"></div>

  <div class="relative mx-auto mt-40 w-[95%] max-w-md rounded-2xl bg-white shadow-xl p-5">

    <h2 class="text-lg font-semibold mb-4">Apply Discount</h2>

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
      <label class="text-xs text-gray-600">Value</label>
      <input type="number" id="discount_value"
        class="w-full mt-1 border rounded-lg px-3 py-2 text-sm"
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
    document.getElementById("paymentModal").classList.remove("hidden");
  }

  function closePaymentModal() {
    document.getElementById("paymentModal").classList.add("hidden");
  }
</script>
<script>
  document.addEventListener("DOMContentLoaded", function() {

    document.getElementById("placeOrderBtn").addEventListener("click", function() {

      let customerId = document.getElementById("customerSelect").value;

      if (!customerId) {
        showToast("⚠ Please select customer first", "red");
        document.getElementById("customerSelect").focus();
        return;
      }

      let paymentStage = document.getElementById("payment_stage").value;
      let paymentAmount = parseFloat(document.getElementById("payment_amount").value);
      let grandTotal = parseFloat("<?= $cartData['grand_total'] ?? 0 ?>");

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

      // continue existing code
      createOrderNow();
    });
  });

  function createOrderNow() {

    let paymentType = document.getElementById("payment_mode").value;
    let paymentStage = document.getElementById("payment_stage").value;
    let paymentAmount = document.getElementById("payment_amount").value;
    let transactionId = document.getElementById("transaction_id").value;
    let note = document.querySelector("textarea[name='note']").value;

    let form = document.getElementById("customerForm");
    let formData = new FormData();

    for (let key in customerData) {
      formData.append(key, customerData[key]);
    }

    for (let element of form.elements) {
      if (element.name) {
        formData.append(element.name, element.value);
      }
    }

    formData.append("action", "create_order");
    formData.append("payment_type", paymentType);
    formData.append("payment_stage", paymentStage);
    formData.append("amount", paymentAmount);
    formData.append("transaction_id", transactionId);
    formData.append("note", note);
    formData.append("customer_id", $('#customerSelect').val());

    fetch("?page=pos_register&action=create-order", {
        method: "POST",
        body: formData
      })
      .then(res => res.json())
      .then(data => {

        if (data.success) {

          closePaymentModal();
          showToast("✓ Order Created", "green");

          importOrder(data.orderid, function() {
            autoCreateInvoiceThenPreview(data.orderid);
          });

        } else {
          showToast(data.message || "Order failed", "red");
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
            if (callback) callback();
          }, 800);

        } else {

          showToast("Import failed", "red");

        }

      })
      .catch(err => {
        console.error(err);
        showToast("✗ Import request failed", "red");
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
  document.getElementById("customerForm").addEventListener("submit", function(e) {

    e.preventDefault();

    let formData = new FormData(this);

    /* STORE FORM DATA */
    customerData = {};
    formData.forEach((value, key) => {
      customerData[key] = value;
    });

    fetch("?page=pos_register&action=add-customer", {
        method: "POST",
        body: formData
      })
      .then(res => res.json())
      .then(data => {

        if (data.success) {

          let select = document.getElementById("customerSelect");

          let option = document.createElement("option");

          option.value = data.customer.id;
          option.text = data.customer.name + " (" + data.customer.phone + ")";

          select.appendChild(option);

          select.value = data.customer.id;

          closeCustomerModal();

        }

      });

  });
</script>

<script>
  document.getElementById("customerSelect").addEventListener("change", function() {

    let id = this.value

    fetch("?page=pos_register&action=set-customer", {
      method: "POST",
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: "customer_id=" + id
    })

    let selected = this.options[this.selectedIndex]

    let name = selected.getAttribute("data-name")
    let phone = selected.getAttribute("data-phone")

    document.getElementById("selectedCustomerName").innerText = name || "Walk-in Customer"
    document.getElementById("selectedCustomerPhone").innerText = phone || "-"

  })
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

    $('#customerSelect').select2({
      placeholder: "Search Customer",
      allowClear: true,
      width: '100%',

      matcher: function(params, data) {

        if ($.trim(params.term) === '') {
          return data;
        }

        if (!data.element) return data;

        let term = params.term.toLowerCase();
        let el = $(data.element);

        let name = String(el.data('name') || '').toLowerCase();
        let phone = String(el.data('phone') || '').toLowerCase();
        let email = String(el.data('email') || '').toLowerCase();
        let text = String(data.text || '').toLowerCase();

        if (
          name.includes(term) ||
          phone.includes(term) ||
          email.includes(term) ||
          text.includes(term)
        ) {
          return data;
        }

        return null;
      },

      templateResult: formatCustomer,
      templateSelection: formatCustomerSelection
    });

  });

  function formatCustomer(data) {

    if (!data.id) return data.text;

    let el = $(data.element);

    let name = el.data('name') || data.text;
    let phone = el.data('phone') || '';
    let email = el.data('email') || '';

    return $(`
        <div>
            <div style="font-weight:600">${name}</div>
            <div style="font-size:11px;color:#777">
                ${phone} ${email ? ' | ' + email : ''}
            </div>
        </div>
    `);
  }

  function formatCustomerSelection(data) {

    if (!data.id) return data.text;

    let el = $(data.element);
    let name = el.data('name');

    return name || data.text;
  }
</script>

<script>
  // OPEN MODAL
  document.getElementById("applyCustomDiscountBtn").addEventListener("click", function() {
    document.getElementById("discountModal").classList.remove("hidden");
  });

  function closeDiscountModal() {
    document.getElementById("discountModal").classList.add("hidden");
  }

  // APPLY DISCOUNT
  function applyDiscount() {

    let type = document.getElementById("discount_type").value;
    let value = parseFloat(document.getElementById("discount_value").value);

    if (!value || value <= 0) {
      showToast("⚠ Enter valid discount", "red");
      return;
    }

    fetch("?page=pos_register&action=apply_custom_discount", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "type=" + type + "&value=" + value
      })
      .then(res => res.json())
      .then(data => {

        if (data.success) {
          showToast("✓ Discount Applied", "green");
          closeDiscountModal();
          location.reload();
        } else {
          showToast(data.message || "Discount failed", "red");
        }

      });

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