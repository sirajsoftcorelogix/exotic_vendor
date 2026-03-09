<?php
// Top of index.php
require_once 'cart-functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

  // ADD TO CART
  if ($_POST['action'] === 'add_to_cart') {

    $code      = trim($_POST['code'] ?? '');
    $qty       = (int)($_POST['qty'] ?? 1);
    $variation = trim($_POST['variation'] ?? '');
    $options   = trim($_POST['options'] ?? '');

    if ($code !== '') {
      $addResult = add_to_cart($code, $qty, $variation, $options, false);
      $_SESSION['cart_message'] = $addResult['message'];
    }
  }

  // CHANGE QUANTITY
  if ($_POST['action'] === 'change_qty') {

    $cartref = $_POST['cartref'] ?? '';
    $newqty  = (int)($_POST['newqty'] ?? 1);

    if ($cartref) {
      change_qty($cartref, $newqty);
    }
  }

  // REMOVE ITEM
  if ($_POST['action'] === 'remove') {

    $cartref = $_POST['cartref'] ?? '';

    if ($cartref) {
      remove_item($cartref);
    }
  }

  // APPLY COUPON
  if ($_POST['action'] === 'apply_coupon') {

    $coupon = trim($_POST['coupon'] ?? '');

    if ($coupon !== '') {

      $apply = apply_coupon($coupon);

      if ($apply['success']) {
        $_SESSION['coupon_message'] = $apply['message'];
        $_SESSION['coupon_status']  = 'success';
      } else {
        $_SESSION['coupon_message'] = $apply['message'];
        $_SESSION['coupon_status']  = 'error';
      }
    }
  }
  // REMOVE COUPON
  if ($_POST['action'] === 'remove_coupon') {

    unset($_SESSION['discount_coupon']);

    // $_SESSION['coupon_message'] = "Coupon removed successfully";
    $_SESSION['coupon_status'] = "success";
  }

  // EXPRESS SHIPPING
  if ($_POST['action'] === 'toggle_express_shipping') {

    $cartid = $_POST['cartid'] ?? '';
    $shippingAction = $_POST['shipping_action'] ?? '';

    if ($cartid && $shippingAction) {
      modify_express_shipping($cartid, $shippingAction);
    }
  }

  if ($_POST['action'] === 'create_order') {
    header('Content-Type: application/json');

    $cartData = get_cart();
    $paymentType = $_POST['payment_type'] ?? 'cod';
    $note = $_POST['note'] ?? '';

    echo json_encode(create_order($cartData, $paymentType, $note));

    exit;
  }

  if ($_POST['action'] !== 'create_order') {
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
  }
  
}

// Get current cart to display
$cartData = get_cart();

?>
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
    <?php
    $cartData = get_cart();
    $cart = $cartData['items'] ?? [];
    ?>

    <aside class="col-span-12 lg:col-span-3">

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

                        <form method="POST">
                          <input type="hidden" name="action" value="change_qty">
                          <input type="hidden" name="cartref" value="<?= $item['cartref'] ?>">
                          <button type="submit"
                            name="newqty"
                            value="<?= $item['quantity'] - 1 ?>"
                            class="h-6 w-6 text-slate-600">−</button>
                        </form>

                        <span class="h-6 w-7 flex items-center justify-center font-semibold">
                          <?= $item['quantity'] ?>
                        </span>

                        <form method="POST">
                          <input type="hidden" name="action" value="change_qty">
                          <input type="hidden" name="cartref" value="<?= $item['cartref'] ?>">
                          <button type="submit"
                            name="newqty"
                            value="<?= $item['quantity'] + 1 ?>"
                            class="h-6 w-6 text-slate-600">+</button>
                        </form>

                      </div>

                      <!-- REMOVE -->
                      <form method="POST">
                        <input type="hidden" name="action" value="remove">
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

                        <form method="POST">

                          <input type="hidden" name="action" value="toggle_express_shipping">
                          <input type="hidden" name="cartid" value="<?= $item['cartref'] ?>">

                          <input type="hidden" name="shipping_action"
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
            <form method="POST" class="flex gap-2">

              <input type="hidden" name="action" value="apply_coupon">

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

              <form method="POST">
                <input type="hidden" name="action" value="remove_coupon">

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

           
            <form method="POST" id="addToCartForm">
              <input type="hidden" name="action" value="add_to_cart">
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
<!-- PAYMENT MODAL -->
<div id="paymentModal" class="fixed inset-0 z-[9999] hidden">

  <!-- Overlay -->
  <div class="absolute inset-0 bg-black/40" onclick="closePaymentModal()"></div>

  <!-- Modal Box -->
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

        <!-- Amount -->
        <div>
          <label class="text-xs text-gray-600">Amount</label>
          <input
            type="number"
            value="<?= $cartData['grand_total'] ?? 0 ?>"
            readonly
            class="w-full mt-1 border rounded-lg px-3 py-2 text-sm bg-gray-100 cursor-not-allowed">
        </div>

        <!-- Payment Mode -->
        <div>
          <label class="text-xs text-gray-600">Payment Mode</label>

          <select name="payment_type" id="payment_mode"
            class="w-full mt-1 border rounded-lg px-3 py-2 text-sm">

            <option value="cod">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="pos_machine">Credit/Debit Card (POS Machine)</option>
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
            readonly
            class="w-full mt-1 border rounded-lg px-3 py-2 text-sm bg-gray-100 ">
        </div>

      </div>

      <!-- Note -->
      <div>
        <label class="text-xs text-gray-600">Note</label>
        <textarea name="note"
          placeholder="Enter note"
          class="w-full mt-1 border rounded-lg px-3 py-2 text-sm h-28"></textarea>
      </div>

    </div>

    <!-- Footer -->
    <div class="flex justify-end gap-3 border-t px-6 py-4">

      <button
        onclick="closePaymentModal()"
        class="px-5 py-2 rounded-lg bg-gray-300 text-gray-700 hover:bg-gray-400">
        Cancel
      </button>

      <!-- <form method="POST">

        <input type="hidden" name="action" value="create_order">

        <input type="hidden" id="payment_type_input" name="payment_type">

        <input type="hidden" id="payment_note_input" name="note">

        <button
          class="px-5 py-2 rounded-lg bg-orange-600 text-white hover:bg-orange-700">
          Confirm Order
        </button>

      </form> -->
      <button
        id="placeOrderBtn"
        class="px-5 py-2 rounded-lg bg-orange-600 text-white hover:bg-orange-700">
        Confirm Order
      </button>


    </div>

  </div>
</div>

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

    const btn = document.getElementById("placeOrderBtn");

    if (!btn) return;

    btn.addEventListener("click", function() {

      let paymentType = document.getElementById("payment_mode").value;
      let note = document.querySelector("textarea[name='note']").value;

      let formData = new FormData();
      formData.append("action", "create_order");
      formData.append("payment_type", paymentType);
      formData.append("note", note);

      fetch("", {
          method: "POST",
          body: formData
        })
        .then(res => res.json())
        .then(data => {

          if (data.success) {

            closePaymentModal();

            let msg = document.createElement("div");
            msg.className = "fixed top-5 right-5 bg-green-600 text-white px-5 py-3 rounded-lg shadow-lg z-[99999]";
            msg.innerHTML = data.message + " (Order ID: " + data.orderid + ")";

            document.body.appendChild(msg);

            setTimeout(() => {
              msg.remove();
              location.reload();
            }, 2000);

          } else {

            alert(data.message || "Order failed");

          }

        })
        .catch(err => {
          console.error(err);
          alert("Server error");
        });

    });

  });
</script>