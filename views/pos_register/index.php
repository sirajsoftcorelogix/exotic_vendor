
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
        placeholder="Search product by Name or Item Code"
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
          <span class="text-xs text-slate-500">Last 24 hours</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
          <!-- card -->
          <div class="rounded-xl border p-3 flex gap-3">
            <img src="https://placehold.co/80x120" class="rounded-lg object-cover" />
            <div class="text-xs">
              <div class="font-semibold line-clamp-2">
                Standing Brass Lord Vishnu Sculpture
              </div>
              <div class="text-slate-500 mt-1">Order #85255608</div>
              <div class="text-slate-500">₹ 24,840</div>
              <span class="inline-block mt-2 rounded-full bg-green-100 px-2 py-1 text-[11px] text-green-700">
                Delivered
              </span>
            </div>
          </div>

          <div class="rounded-xl border p-3 flex gap-3">
            <img src="https://placehold.co/80x120" class="rounded-lg object-cover" />
            <div class="text-xs">
              <div class="font-semibold line-clamp-2">
                Krishna Leela Leather Painting
              </div>
              <div class="text-slate-500 mt-1">Order #45754755</div>
              <div class="text-slate-500">₹ 5,25,000</div>
              <span class="inline-block mt-2 rounded-full bg-green-100 px-2 py-1 text-[11px] text-green-700">
                Delivered
              </span>
            </div>
          </div>

          <div class="rounded-xl border p-3 flex gap-3">
            <img src="https://placehold.co/80x120" class="rounded-lg object-cover" />
            <div class="text-xs">
              <div class="font-semibold line-clamp-2">
                Large Standing Lord Hanuman
              </div>
              <div class="text-slate-500 mt-1">Order #152782578</div>
              <div class="text-slate-500">₹ 12,485</div>
              <span class="inline-block mt-2 rounded-full bg-orange-100 px-2 py-1 text-[11px] text-orange-700">
                Under Process
              </span>
            </div>
          </div>

          <div class="rounded-xl border p-3 flex gap-3">
            <img src="https://placehold.co/80x120" class="rounded-lg object-cover" />
            <div class="text-xs">
              <div class="font-semibold line-clamp-2">
                Modern Art Brass & Resin Sculpture
              </div>
              <div class="text-slate-500 mt-1">Order #152782579</div>
              <div class="text-slate-500">₹ 16,355</div>
              <span class="inline-block mt-2 rounded-full bg-orange-100 px-2 py-1 text-[11px] text-orange-700">
                Under Process
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Products -->
      <div class="rounded-2xl bg-white border p-4">
        <h2 class="font-semibold text-sm mb-3">Products</h2>
        <!-- Product category tabs -->
      <!-- Product category tabs -->
      <div class="mt-3 flex flex-wrap items-center gap-3">

        <!-- Active -->
        <button
          class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-2 text-xs font-semibold text-white shadow-sm"
        >
          <!-- list icon -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          All Products
        </button>

        <!-- Sculptures -->
        <button
          class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
        >
          <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2l3 7h7l-5.5 4.5L18 22l-6-4-6 4 1.5-8.5L2 9h7z"/>
          </svg>
          Sculptures
        </button>

        <!-- Paintings -->
        <button
          class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
        >
          <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <path d="M21 15l-5-5L5 21"/>
          </svg>
          Paintings
        </button>

        <!-- Clothes -->
        <button
          class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
        >
          <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 4l4 4-4 4"/>
            <path d="M8 4L4 8l4 4"/>
            <path d="M4 8h16v12H4z"/>
          </svg>
          Clothes &amp; More
        </button>

        <!-- Home & Decor -->
        <button
          class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
        >
          <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 12l9-9 9 9"/>
            <path d="M9 21V9h6v12"/>
          </svg>
          Home &amp; Decore
        </button>

        <!-- Books -->
        <button
          class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
        >
          <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
            <path d="M6.5 2H20v15H6.5A2.5 2.5 0 014 14.5v-10A2.5 2.5 0 016.5 2z"/>
          </svg>
          Books
        </button>

        <!-- Jewellery -->
        <button
          class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
        >
          <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2l4 6-4 14-4-14 4-6z"/>
            <path d="M8 8h8"/>
          </svg>
          Jewellery
        </button>

      </div>



        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <!-- product -->
          <div class="border rounded-xl overflow-hidden">
            <img src="https://cdn.exoticindia.com/images/products/original/sculptures-2019/zem062.webp"
                 class="h-60 w-full object-cover" />
            <div class="p-3 text-xs">
              <div class="font-semibold line-clamp-2">
                Raja Rani of Rajasthan Sculpture
              </div>
              <div class="text-slate-500 mt-1">Brass · 12 x 4 x 5</div>
              <div class="font-semibold text-orange-600 mt-1">₹ 12,485</div>
            </div>
          </div>

          <div class="border rounded-xl overflow-hidden">
            <img src="https://placehold.co/400x600" class="h-60 w-full object-cover" />
            <div class="p-3 text-xs">
              <div class="font-semibold line-clamp-2">
                Supreme Lakshmi Ganesh Saraswati
              </div>
              <div class="text-slate-500 mt-1">Brass · Trio</div>
              <div class="font-semibold text-orange-600 mt-1">₹ 28,950</div>
            </div>
          </div>

          <div class="border rounded-xl overflow-hidden">
            <img src="https://placehold.co/400x600" class="h-60 w-full object-cover" />
            <div class="p-3 text-xs">
              <div class="font-semibold line-clamp-2">
                Handwoven Ivory Saree
              </div>
              <div class="text-slate-500 mt-1">Cotton · Free Size</div>
              <div class="font-semibold text-orange-600 mt-1">₹ 8,500</div>
            </div>
          </div>

          <div class="border rounded-xl overflow-hidden">
            <img src="https://placehold.co/400x600" class="h-60 w-full object-cover" />
            <div class="p-3 text-xs">
              <div class="font-semibold line-clamp-2">
                Wooden Shrine Panel (Vintage)
              </div>
              <div class="text-slate-500 mt-1">Wood · Hand Painted</div>
              <div class="font-semibold text-orange-600 mt-1">₹ 42,000</div>
            </div>
          </div>

          <div class="border rounded-xl overflow-hidden">
            <img src="https://cdn.exoticindia.com/images/products/original/sculptures-2019/zem062.webp"
                 class="h-60 w-full object-cover" />
            <div class="p-3 text-xs">
              <div class="font-semibold line-clamp-2">
                Raja Rani of Rajasthan Sculpture
              </div>
              <div class="text-slate-500 mt-1">Brass · 12 x 4 x 5</div>
              <div class="font-semibold text-orange-600 mt-1">₹ 12,485</div>
            </div>
          </div>

          <div class="border rounded-xl overflow-hidden">
            <img src="https://placehold.co/400x600" class="h-60 w-full object-cover" />
            <div class="p-3 text-xs">
              <div class="font-semibold line-clamp-2">
                Supreme Lakshmi Ganesh Saraswati
              </div>
              <div class="text-slate-500 mt-1">Brass · Trio</div>
              <div class="font-semibold text-orange-600 mt-1">₹ 28,950</div>
            </div>
          </div>

          <div class="border rounded-xl overflow-hidden">
            <img src="https://placehold.co/400x600" class="h-60 w-full object-cover" />
            <div class="p-3 text-xs">
              <div class="font-semibold line-clamp-2">
                Handwoven Ivory Saree
              </div>
              <div class="text-slate-500 mt-1">Cotton · Free Size</div>
              <div class="font-semibold text-orange-600 mt-1">₹ 8,500</div>
            </div>
          </div>

          <div class="border rounded-xl overflow-hidden">
            <img src="https://placehold.co/400x600" class="h-60 w-full object-cover" />
            <div class="p-3 text-xs">
              <div class="font-semibold line-clamp-2">
                Wooden Shrine Panel (Vintage)
              </div>
              <div class="text-slate-500 mt-1">Wood · Hand Painted</div>
              <div class="font-semibold text-orange-600 mt-1">₹ 42,000</div>
            </div>
          </div>
        </div>
      </div>

    </section>

    <!-- ===== PAYMENT / CART ===== -->
  <!-- ===== PAYMENT / CART ===== -->
<aside class="col-span-12 lg:col-span-3">
  <div class="sticky top-5 rounded-2xl bg-white border overflow-hidden">

    <div class="border-b p-4">
      <div class="font-semibold text-sm">Mrs. Avita Desi</div>
      <div class="text-xs text-slate-500">+91 99999 99999</div>
    </div>

    <div class="p-4 space-y-4 text-xs">

      <!-- cart item -->
      <div class="flex gap-3">
        <img
          src="https://cdn.exoticindia.com/images/products/original/sculptures-2019/zem062.webp"
          class="h-12 w-12 rounded-lg object-cover"
          alt="Product"
        />

        <div class="flex-1 min-w-0">
          <div class="font-semibold line-clamp-2">
            Large Standing Lord Hanuman | Brass
          </div>

          <!-- price + qty stepper (like screenshot) -->
          <div class="mt-1 flex items-center justify-between gap-3">
            <div class="text-orange-600 font-semibold">₹ 12,485</div>

            <div class="inline-flex items-center rounded-lg border border-slate-200 bg-white overflow-hidden">
              <button
                type="button"
                class="h-7 w-7 inline-flex items-center justify-center text-slate-600 hover:bg-slate-50"
                aria-label="Decrease quantity"
              >
                −
              </button>
              <div class="h-7 w-8 inline-flex items-center justify-center text-[12px] font-semibold text-slate-800">
                1
              </div>
              <button
                type="button"
                class="h-7 w-7 inline-flex items-center justify-center text-slate-600 hover:bg-slate-50"
                aria-label="Increase quantity"
              >
                +
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- shipping (checkbox + express) -->
      <div class="rounded-xl bg-green-50 p-3 flex justify-between items-center gap-3">
        <div class="flex items-start gap-3">
          <input
            type="checkbox"
            checked
            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-green-600 focus:ring-green-500"
            aria-label="Select express delivery"
          />
          <div>
            <div class="font-semibold text-green-800">Express Delivery</div>
            <div class="text-green-700 font-semibold">₹ 8,265</div>
          </div>
        </div>

        <div class="text-[11px] font-semibold text-green-800 text-right">
          Blue Dart<br /><span class="text-green-700 font-medium">(24–48 hrs)</span>
        </div>
      </div>

      <!-- coupon -->
      <div class="flex gap-2">
        <input
          class="flex-1 rounded-xl border border-slate-200 px-3 py-2 focus:border-orange-500 outline-none"
          placeholder="Coupon/Discount Code"
        />
        <button class="rounded-xl bg-black text-white px-4 py-2 font-semibold hover:bg-slate-900">
          Add
        </button>
      </div>

      <!-- service dropdown (after coupon) -->
      <div>
        <select
          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 focus:border-orange-500 outline-none"
        >
          <option value="">Add-on services</option>
          <option value="giftwrap">Gift Wrap</option>
          <option value="insurance">Shipping Insurance</option>
          <option value="priority">Priority Handling</option>
        </select>
      </div>

      <!-- Payment Options Tabs -->
      <div class="mt-2">
        <div class="mb-2 text-xs font-semibold text-slate-700">Payment Method</div>

        <div class="flex flex-wrap gap-2">
          <!-- Active -->
          <button
            class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-2 text-xs font-semibold text-white"
          >
            <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="5" width="20" height="14" rx="2" />
              <line x1="2" y1="10" x2="22" y2="10" />
            </svg>
            Card
          </button>

          <button
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
          >
            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 7h16" />
              <path d="M4 12h16" />
              <path d="M4 17h10" />
            </svg>
            UPI
          </button>

          <button
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
          >
            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 21h18" />
              <path d="M6 18V9" />
              <path d="M10 18V9" />
              <path d="M14 18V9" />
              <path d="M18 18V9" />
              <path d="M12 3l9 6H3l9-6z" />
            </svg>
            Net Banking
          </button>

          <button
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
          >
            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="6" width="20" height="12" rx="2" />
              <circle cx="12" cy="12" r="3" />
            </svg>
            Cash
          </button>
        </div>
      </div>

      <!-- totals -->
      <div class="space-y-2 pt-2">
        <div class="flex justify-between text-slate-600">
          <span>Sub Total</span><span>₹ 99,617.70</span>
        </div>
        <div class="flex justify-between text-slate-600">
          <span>GST (18%)</span><span>₹ 21,867.30</span>
        </div>
        <div class="flex justify-between font-semibold text-slate-800">
          <span>Total</span><span>₹ 1,21,485</span>
        </div>
      </div>

      <!-- actions -->
      <button class="w-full rounded-xl bg-orange-600 py-3 text-white font-semibold hover:bg-orange-700">
        Apply Cart Discount
      </button>
      <button class="w-full rounded-xl bg-orange-600 py-3 text-white font-semibold hover:bg-orange-700">
        Proceed to Payment
      </button>
    </div>
  </div>
</aside>


  </main>
</div>
