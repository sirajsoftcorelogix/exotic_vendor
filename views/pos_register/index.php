<!-- ===== PAGE WRAPPER ===== -->
<div class="min-h-screen">

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
        placeholder="Search product by Name or Item Code" id="searchName"
      />

      <!-- Right -->
      <div class="ml-auto flex items-center gap-3">
        <button class="rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white">
          Sold Order
        </button>
        <div class="flex items-center gap-2 border rounded-xl px-3 py-2">
          <div class="h-8 w-8 rounded-full bg-slate-300"></div>
          <div class="text-xs">
            <div class="font-semibold">Store_1</div>
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
      <div class="rounded-2xl bg-white border p-4">
        <div class="mb-3 flex justify-between">
          <h2 class="font-semibold text-sm">Sales Terminal</h2>
          <div class="flex items-center gap-2">
            <span class="text-xs text-gray-600">Show:</span>
            <select class="rounded-full border border-gray-300 bg-white px-4 py-1.5 text-xs text-gray-700 focus:outline-none">
              <option value="1h">1 hour</option>
              <option value="24h" selected>24 hours</option>
              <option value="7d">7 days</option>
              <option value="30d">30 days</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <a href="/order-details.php?order_id=85255608" class="block">
            <div class="flex justify-between gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md active:scale-[0.98]">
              <div class="flex-1 text-xs">
                <div class="font-semibold text-gray-800 line-clamp-2">Standing Brass Lord Vishnu Sculpture</div>
                <div class="mt-1 text-gray-500">Order #85255608</div>
                <div class="mt-1 font-semibold text-gray-700">Price : ₹ 24,840</div>
                <span class="inline-block mt-3 rounded-full bg-green-100 px-3 py-1 text-[11px] font-medium text-green-700">Delivered</span>
              </div>
              <img src="https://placehold.co/80x120" alt="Product image" class="h-24 w-16 rounded-lg object-cover border border-gray-200" />
            </div>
          </a>

          <a href="/order-details.php?order_id=85255608" class="block">
            <div class="flex justify-between gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md active:scale-[0.98]">
              <div class="flex-1 text-xs">
                <div class="font-semibold text-gray-800 line-clamp-2">Standing Brass Lord Vishnu Sculpture</div>
                <div class="mt-1 text-gray-500">Order #85255608</div>
                <div class="mt-1 font-semibold text-gray-700">Price : ₹ 24,840</div>
                <span class="inline-block mt-3 rounded-full bg-green-100 px-3 py-1 text-[11px] font-medium text-green-700">Delivered</span>
              </div>
              <img src="https://placehold.co/80x120" alt="Product image" class="h-24 w-16 rounded-lg object-cover border border-gray-200" />
            </div>
          </a>

          <a href="/order-details.php?order_id=85255608" class="block">
            <div class="flex justify-between gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md active:scale-[0.98]">
              <div class="flex-1 text-xs">
                <div class="font-semibold text-gray-800 line-clamp-2">Standing Brass Lord Vishnu Sculpture</div>
                <div class="mt-1 text-gray-500">Order #85255608</div>
                <div class="mt-1 font-semibold text-gray-700">Price : ₹ 24,840</div>
                <span class="inline-block mt-3 rounded-full bg-green-100 px-3 py-1 text-[11px] font-medium text-green-700">Delivered</span>
              </div>
              <img src="https://placehold.co/80x120" alt="Product image" class="h-24 w-16 rounded-lg object-cover border border-gray-200" />
            </div>
          </a>

        </div>
      </div>

      <!-- Products -->
      <div class="rounded-2xl bg-white border p-4">
        <h2 class="font-semibold text-sm mb-3">Products</h2>
        <div class="mt-3 flex flex-wrap items-center gap-3">
          <button class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-2 text-xs font-semibold text-white shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            All Products
          </button>
          <button class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2l3 7h7l-5.5 4.5L18 22l-6-4-6 4 1.5-8.5L2 9h7z" />
            </svg>
            Sculptures
          </button>
          <button class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="3" width="18" height="18" rx="2" />
              <circle cx="8.5" cy="8.5" r="1.5" />
              <path d="M21 15l-5-5L5 21" />
            </svg>
            Paintings
          </button>
          <button class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M16 4l4 4-4 4M8 4L4 8l4 4M4 8h16v12H4z" />
            </svg>
            Clothes &amp; More
          </button>
          <button class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 12l9-9 9 9M9 21V9h6v12" />
            </svg>
            Home &amp; Decore
          </button>
          <button class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 19.5A2.5 2.5 0 016.5 17H20M6.5 2H20v15H6.5A2.5 2.5 0 014 14.5v-10A2.5 2.5 0 016.5 2z" />
            </svg>
            Books
          </button>
        </div>
        <!-- Product Card -->
        <div class="mt-3 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="productsCards">
          <?php /*for ($i = 0; $i <= 12; $i++): ?>
          <div
            class="
              rounded-2xl
              border border-gray-200
              bg-white
              overflow-hidden
              shadow-sm
              hover:shadow-md
              transition
            "
          >
            <!-- Image -->
            <div class="bg-gray-50 p-2">
              <img
                src="https://cdn.exoticindia.com/images/products/original/sculptures-2019/zem062.webp"
                alt="Product image"
                class="mx-auto h-56 lg:h-52 xl:h-48 object-contain"
              />
            </div>

            <!-- Content -->
            <div class="px-3 pb-3 pt-2 text-xs">
              <!-- Title -->
              <div class="text-[9.5px] text-gray-800 leading-snug line-clamp-2">
                44" Large Standing Lord Hanuman in Sindoori Color | Brass Statue
              </div>

              <!-- Bottom row -->
              <div class="mt-2 flex flex-col gap-1">
                <!-- SKU + Stock -->
                <div class="flex items-center gap-1 whitespace-nowrap">
                  <div class="flex items-center gap-1 whitespace-nowrap">
                    <span class="rounded-md bg-orange-100 px-1.5 py-0.5 text-[9px] font-small text-orange-700">
                      DDS815
                    </span>
                    <span class="rounded-md bg-green-100 px-1.5 py-0.5 text-[9px] font-small text-green-700">
                      Stock : 2
                    </span>
                    <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[9px] font-small text-gray-700">
                      ₹ 1,21,485
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endfor; */?>
        </div>


      </div>
    </section>

    <!-- ===== PAYMENT / CART ===== -->
    <aside class="col-span-12 lg:col-span-3">
  <div class="sticky top-4 rounded-2xl bg-white border shadow-sm overflow-hidden">

    <!-- USER -->
    <div class="px-4 py-3 border-b">
      <div class="text-sm font-semibold justify-center text-center">Mrs. Avita Desi</div>
      <div class="text-[11px] text-slate-500 justify-center text-center">+91 99999 99999</div>
    </div>

    <div class="px-4 py-3 space-y-4 text-[12px]">

      <!-- PRODUCT -->
      <div class="flex gap-3">
        <img
          src="https://cdn.exoticindia.com/images/products/original/sculptures-2019/zem062.webp"
          class="h-12 w-12 rounded-lg bg-slate-50 object-contain"
        />

        <div class="flex-1 min-w-0">
          <div class="text-[9px] leading-snug line-clamp-2">
            44" Large Standing Lord Hanuman in Sindoori Color | Brass Statue
          </div>

          <div class="mt-1 flex items-center justify-between">
            <span class="text-orange-600 font-semibold">₹ 1,21,485</span>
          </div>
          <div class="mt-2 flex items-center justify-between">  
            <!-- qty -->
            <div class="flex items-center border rounded-md overflow-hidden">
              <button class="h-6 w-6 text-slate-600">−</button>
              <span class="h-6 w-7 flex items-center justify-center font-semibold">1</span>
              <button class="h-6 w-6 text-slate-600">+</button>
            </div>
          </div>
        </div>
      </div>

      <!-- SHIPPING -->
      <div class="flex gap-2">
        <div class="flex gap-2">

  <!-- LEFT PILL : Price -->
  <div class="flex items-center gap-2 rounded-lg bg-green-100 px-3 py-2">
    <div class="flex h-6 w-6 items-center justify-center rounded-md">
       <input type="checkbox" checked="" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-green-600 focus:ring-green-500" aria-label="Select express delivery">     
    </div>
    <div>
      <div class="text-[9px] text-green-900 leading-tight">Express Shipping</div>
      <div class="text-[11px] font-semibold text-green-900">₹ 8265</div>
    </div>
  </div>

  <!-- RIGHT PILL : Courier -->
  <div class="flex items-center gap-2 rounded-lg bg-green-200 px-3 py-2">
    <div class="flex h-6 w-6 items-center justify-center rounded-md bg-orange-500">
      <div class="flex h-6 w-6 items-center justify-center rounded-md bg-yellow-600">
  <svg class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none"
       stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="20 6 9 17 4 12"></polyline>
  </svg>
</div>

    </div>
    <div class="leading-tight">
      <div class="text-[10px] font-semibold text-green-900">
        Express Blue Dart
      </div>
      <div class="text-[9px] text-green-900">
        Shipping (24–48 hours)
      </div>
    </div>
  </div>
</div>
    

</div>


      <!-- COUPON -->
      <div class="flex gap-2">
      <input
        class="w-2/3 rounded-lg border px-2 py-2 text-xs outline-none focus:border-orange-500"
        placeholder="Coupon/Discount Code"
      />
      <button
        class="w-1/3 rounded-lg bg-black px-4 py-2 text-xs font-semibold text-white"
      >
        Add
      </button>
    </div>


      <!-- ADD-ON -->
      <select class="w-full rounded-lg border px-3 py-2 outline-none focus:border-orange-500">
        <option>Add-on services</option>
        <option>Gift Wrap</option>
        <option>Insurance</option>
      </select>

      <!-- TOTALS -->
      <div class="pt-2 border-t space-y-1.5">
        <div class="flex justify-between text-slate-600">
          <span>Sub Total</span>
          <span>₹ 99,617.70</span>
        </div>
        <div class="flex justify-between text-slate-600">
          <span>GST (18%)</span>
          <span>₹ 21,867.30</span>
        </div>
        <div class="flex justify-between font-semibold text-slate-900">
          <span>Total</span>
          <span>₹ 1,21,485</span>
        </div>
      </div>

      <!-- ACTIONS -->
      <button class="w-full rounded-xl bg-orange-600 py-3 text-white font-semibold">
        Apply Cart Discount
      </button>
      <button class="w-full rounded-xl bg-orange-600 py-3 text-white font-semibold">
        Proceed to Payment
      </button>

    </div>
  </div>
</aside>
  </main>
</div>
<!-- ===== END PAGE WRAPPER ===== -->
<script src="<?php echo base_url(); ?>/assets/js/pos.js"></script> 