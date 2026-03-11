<div class="min-h-screen">
  <a href="test_create_order_static();"></a>
  <!-- ===== TOP BAR ===== -->
  <header class="border-b bg-white">
    <div class="mx-auto flex max-w-[1500px] items-center gap-3 px-4 py-3">

      <!-- Menu -->
      <button class="h-10 w-10 rounded-xl hover:bg-slate-100 flex items-center justify-center">
        ☰
      </button>

      <!-- Search -->
      <input
        class="w-full max-w-lg rounded-xl border border-slate-200 px-4 py-2 text-sm focus:border-orange-500 outline-none"
        placeholder="Search product by Name or Item Code" id="searchName" />

      <!-- Right -->
      <div class="ml-auto flex items-center gap-3">

        <!-- Sold Order Button -->
        <button class="rounded-xl bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700">
          Sold Order
        </button>

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

            <option value="">Walk-in Customer</option>

            <?php foreach ($customers as $c): ?>

              <option value="<?= $c['id'] ?>"
                data-name="<?= htmlspecialchars($c['name']) ?>"
                data-phone="<?= htmlspecialchars($c['phone']) ?>"
                data-email="<?= htmlspecialchars($c['email']) ?>"
                <?= (!empty($_SESSION['pos_customer_id']) && $_SESSION['pos_customer_id'] == $c['id']) ? 'selected' : '' ?>>

                <?= htmlspecialchars($c['name']) ?> (<?= $c['phone'] ?>)

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
                        ₹ <?= number_format($item['price'], 2) ?>
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

                  <div class="flex gap-2">

                    <div class="flex items-center gap-2 rounded-lg bg-green-100 px-3 py-2">

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
                          ₹ <?= number_format($item['shipping_per_unit'], 2) ?>
                        </div>

                      </div>

                    </div>


                    <div class="flex items-center gap-2 rounded-lg bg-green-200 px-3 py-2">

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

                    </div>

                  </div>

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

          <!-- TOTALS -->
          <div class="pt-2 border-t space-y-1.5">
            <?php if (!empty($cartData['discount'])): ?>
              <div class="flex justify-between text-green-600">
                <span>Coupon Discount</span>
                <span>- ₹ <?= number_format($cartData['discount'], 2) ?></span>
              </div>
            <?php endif; ?>
            <div class="flex justify-between text-slate-600">
              <span>Sub Total</span>
              <span>₹ <?= number_format($cartData['subtotal'] ?? 0, 2) ?></span>
            </div>

            <div class="flex justify-between text-slate-600">
              <span>GST</span>
              <span>₹ <?= number_format($cartData['gst'] ?? 0, 2) ?></span>
            </div>

            <div class="flex justify-between font-semibold text-slate-900">
              <span>Total</span>
              <span>₹ <?= number_format($cartData['grand_total'] ?? 0, 2) ?></span>
            </div>

          </div>

          <!-- ACTION -->
          <button class="w-full rounded-xl bg-orange-600 py-3 text-white font-semibold">
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

<!-- Product Modal -->
<div id="productModal" class="fixed inset-0 z-[9999] hidden">
  <!-- overlay -->
  <div id="productModalOverlay" class="absolute inset-0 bg-black/50"></div>

  <!-- modal box -->
  <div class="relative mx-auto mt-10 w-[95%] max-w-3xl rounded-2xl bg-white shadow-xl">
    <div class="flex items-center justify-between border-b px-5 py-3">
      <div class="text-sm font-semibold text-gray-800" id="pmTitle">Product</div>

      <button
        id="productModalClose"
        class="rounded-lg px-2 py-1 text-gray-500 hover:bg-gray-100 hover:text-gray-800">
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

          <!-- Footer -->
          <div class="mt-6 flex flex-wrap items-center justify-end gap-2">

            <!-- Qty control -->
            <div class="mr-auto flex items-center gap-2">
              <label class="text-xs text-gray-600">Qty</label>

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


            <form method="POST" action="?page=pos_register&action=cart-add">
              <!-- <input type="hidden" name="action" value="add_to_cart"> -->
              <input type="hidden" name="code" id="modal_product_code">
              <input type="hidden" name="qty" id="modal_qty" value="1">
              <input type="hidden" name="options" id="modal_options">
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
          <label class="text-gray-500">Address</label>
          <input name="address_line1" class="w-full border rounded px-2 py-1.5">
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
          <label class="text-gray-500">Address</label>
          <input name="shipping_address_line1" class="w-full border rounded px-2 py-1.5">
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

        <!-- Payment Mode -->
        <div>
          <label class="text-xs text-gray-600">Payment Mode</label>

          <select name="payment_type" id="payment_mode"
            class="w-full mt-1 border rounded-lg px-3 py-2 text-sm">

            <option value="cod">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="pos_machine">POS Machine</option>
            <option value="razorpay">Razorpay</option>
            <option value="specialpay">SpecialPay</option>
            <option value="cheque">Cheque</option>
            <option value="demand_draft">Demand Draft</option>

          </select>
        </div>

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
<!-- CUSTOMER MODAL -->

</div>
</div>
</div>
</div>
</div>

<!-- ===== END PAGE WRAPPER ===== -->
<!-- <script src="<?php echo base_url(); ?>/assets/js/pos.js"></script> -->
<script src="<?php echo 'http://' . $_SERVER['HTTP_HOST']; ?>/assets/js/pos.js"></script>
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

      let paymentType = document.getElementById("payment_mode").value
      let paymentStage = document.getElementById("payment_stage").value
      let paymentAmount = document.getElementById("payment_amount").value
      let transactionId = document.getElementById("transaction_id").value
      let note = document.querySelector("textarea[name='note']").value

      /* GET CUSTOMER FORM DATA */
      // let formData = new FormData(document.getElementById("customerForm"))
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
      for (let pair of formData.entries()) {
        // console.log(pair[0] + ":", pair[1]);
      }

      formData.append("action", "create_order")
      formData.append("payment_type", paymentType)
      formData.append("payment_stage", paymentStage)
      formData.append("amount", paymentAmount)
      formData.append("transaction_id", transactionId)
      formData.append("note", note)

      fetch("?page=pos_register&action=create-order", {
          method: "POST",
          body: formData
        })
        .then(res => res.json())
        .then(data => {

          if (data.success) {
            console.log(data.success, "data.success")
            closePaymentModal()

            importOrder(data.orderid)

            let msg = document.createElement("div")
            msg.className = "fixed top-5 right-5 bg-green-600 text-white px-5 py-3 rounded-lg shadow-lg z-[99999]"
            msg.innerHTML = data.message + " (Order ID: " + data.orderid + ")"

            document.body.appendChild(msg)

            setTimeout(() => {
              msg.remove()
              location.reload()
            }, 2000)

          } else {

            alert(data.message || "Order failed")

          }

        })

    })
  });

  function importOrder(orderid) {
    const secretKey = 'b2d1127032446b78ce2b8911b72f6b155636f6898af2cf5d3aafdccf46778801';
    const url = 'index.php?page=orders&action=import_orders&secret_key=' + secretKey + '&orderid=' + orderid;

    fetch(url, {
        method: 'GET'
      })
      .then(response => response.json())
      .then(data => {
        console.log('Order imported successfully:', data);

        // Display success message
        let successMsg = document.createElement("div");
        successMsg.className = "fixed top-5 right-5 bg-blue-600 text-white px-5 py-3 rounded-lg shadow-lg z-[99999]";
        successMsg.innerHTML = "✓ Order imported successfully";
        document.body.appendChild(successMsg);

        setTimeout(() => {
          successMsg.remove();
        }, 3000);
      })
      .catch(error => {
        console.error('Error importing order:', error);

        // Display error message
        let errorMsg = document.createElement("div");
        errorMsg.className = "fixed top-5 right-5 bg-red-600 text-white px-5 py-3 rounded-lg shadow-lg z-[99999]";
        errorMsg.innerHTML = "✗ Error importing order";
        document.body.appendChild(errorMsg);

        setTimeout(() => {
          errorMsg.remove();
        }, 3000);
      });
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
        shippingInput.readOnly = true;
        shippingInput.classList.add("bg-gray-100");

        /* LIVE SYNC */
        billingInput.addEventListener("input", function() {
          if (checkbox.checked) {
            shippingInput.value = billingInput.value;
          }
        });

      } else {

        shippingInput.readOnly = false;
        shippingInput.classList.remove("bg-gray-100");

      }

    });

  }
</script>