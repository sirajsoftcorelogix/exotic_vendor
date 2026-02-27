<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Customer Invoices</h1>
        <!-- <a href="<?php //echo base_url('?page=invoices&action=create'); ?>" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">+ Create Invoice</a> -->
    </div>
    
    <!-- FILTER SECTION -->
    <div class="bg-white border-2 border-purple-500 rounded-xl p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-5">

        <div>
          <label class="text-sm font-semibold">Date Range :</label>
          <input type="text" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none">
        </div>

        <div>
          <label class="text-sm font-semibold">AWB Number :</label>
          <input type="text" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none">
        </div>

        <div>
          <label class="text-sm font-semibold">Order Number :</label>
          <input type="text" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none">
        </div>

        <div>
          <label class="text-sm font-semibold">Invoice No:</label>
          <input type="text" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none">
        </div>

        <div>
          <label class="text-sm font-semibold">Customer Phone / Email:</label>
          <input type="text" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none">
        </div>

        <div>
          <label class="text-sm font-semibold">Payment:</label>
          <select class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none">
            <option>COD</option>
            <option>Prepaid</option>
          </select>
        </div>

        <div>
          <label class="text-sm font-semibold">Box Size:</label>
          <input type="text" value='R1 - 6" x 4" x 3.5"' class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2">
        </div>

        <div>
          <label class="text-sm font-semibold">Status :</label>
          <select class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2">
            <option>Ready to Ship</option>
            <option>Dispatched</option>
          </select>
        </div>

        <div>
          <label class="text-sm font-semibold">Category :</label>
          <select class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2">
            <option>Books</option>
          </select>
        </div>

        <div class="flex items-end">
          <button class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 rounded-md transition">
            Search
          </button>
        </div>

      </div>
    </div>


    <!-- ACTION BAR -->
    <div class="flex flex-col md:flex-row justify-end items-center gap-3 mb-5">
      <button class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md transition">
        Print Label
      </button>
      <button class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md transition">
        Update Status
      </button>
      <select class="border border-gray-300 rounded-md px-3 py-2">
        <option>Sort</option>
      </select>
    </div>


    <!-- ORDER LIST -->
    <div class="space-y-4">
      <?php if (!empty($invoices)): ?>
        <?php foreach ($invoices as $invoice): ?>
          <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md transition">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
              <!-- LEFT -->
              <div class="flex gap-4">
                <input type="checkbox" class="mt-1 w-5 h-5">
                <div class="flex gap-8 flex-wrap">
                  <div>
                    <p class="text-xs text-gray-500">Inv No.</p>
                    <p class="text-blue-600 font-semibold"><?php echo htmlspecialchars($invoice['invoice_number'] ?? $invoice['id']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($invoice['invoice_date'] ?? '')); ?></p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Order No.</p>
                    <p class="text-blue-600 font-semibold"><?php echo htmlspecialchars($invoice['vp_order_info_id'] ?? ''); ?></p>
                    <p class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($invoice['invoice_date'] ?? '')); ?></p>
                  </div>
                </div>
              </div>
              <!-- MIDDLE GRID -->
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 flex-1">
                <div>
                  <p class="text-xs text-gray-500">Invoice Total</p>
                  <p class="font-semibold text-gray-800">₹ <?php echo number_format($invoice['total_amount'] ?? 0, 2); ?></p>
                  <div class="flex gap-2 mt-2">
                    <?php if (isset($invoice['status']) && strtolower($invoice['status']) == 'cod'): ?>
                      <span class="bg-red-100 text-red-600 text-xs px-2 py-1 rounded">COD</span>
                    <?php else: ?>
                      <span class="bg-green-100 text-green-600 text-xs px-2 py-1 rounded">Prepaid</span>
                    <?php endif; ?>
                  </div>
                </div>
                <div>
                  <p class="text-xs text-gray-500">Items:</p>
                  <p class="font-semibold">
                    <?php echo htmlspecialchars($invoice['item_code'] ?? ''); ?>
                    <?php if (isset($invoice['item_count']) && $invoice['item_count'] > 1): ?>
                      <span class="text-red-500 text-sm">+<?php echo $invoice['item_count'] - 1; ?></span>
                    <?php endif; ?>
                  </p>
                </div>
                <div>
                  <p class="text-xs text-gray-500">AWB:</p>
                  <p class="text-blue-600 font-medium text-sm">
                    <?php 
                      $awbs = [];
                      if (!empty($invoice_dispatch[$invoice['id']])) {
                        foreach ($invoice_dispatch[$invoice['id']] as $dispatch) {
                          if (!empty($dispatch['awb_code'])) $awbs[] = $dispatch['awb_code'];
                        }
                      }
                      echo htmlspecialchars(implode(' | ', $awbs));
                    ?>
                  </p>
                  <p class="text-xs text-gray-400 mt-1"><?php echo date('d M Y', strtotime($invoice['invoice_date'] ?? '')); ?></p>
                </div>
                <div>
                  <p class="text-xs text-gray-500">RTO Risk</p>
                  <p class="font-semibold text-gray-800">-</p>
                </div>
              </div>
              <!-- RIGHT -->
              <div class="flex flex-col lg:items-end gap-3">
                <div>
                  <p class="text-xs text-gray-500">Applied wt.</p>
                  <p class="font-semibold">
                    <?php 
                      $wt = 0;
                      if (!empty($invoice_dispatch[$invoice['id']])) {
                        foreach ($invoice_dispatch[$invoice['id']] as $dispatch) {
                          $wt += (float)($dispatch['billing_weight'] ?? 0);
                        }
                      }
                      echo $wt > 0 ? number_format($wt, 3) . ' Kg' : '-';
                    ?>
                  </p>
                </div>
                <div>
                  <p class="text-xs text-gray-500">Charges:</p>
                  <p class="font-semibold">
                    ₹ <?php 
                      $charges = 0;
                      if (!empty($invoice_dispatch[$invoice['id']])) {
                        foreach ($invoice_dispatch[$invoice['id']] as $dispatch) {
                          $charges += (float)($dispatch['shipping_charges'] ?? 0);
                        }
                      }
                      echo number_format($charges, 2);
                    ?>
                  </p>
                </div>
                <button class="text-gray-600 hover:bg-gray-100 rounded-full p-2 text-lg">
                  ⋮
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
          <p class="text-yellow-700 font-semibold">No invoices found.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- PAGINATION -->
    <?php if (($totalPages ?? 1) > 1): ?>
      <div class="flex justify-center mt-8">
        <nav class="inline-flex rounded-md shadow-sm" aria-label="Pagination">
          <?php $baseUrl = base_url('?page=dispatch&action=list'); ?>
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="<?php echo $baseUrl . '&p=' . $i . '&per_page=' . ($perPage ?? 10); ?>"
              class="px-4 py-2 border border-gray-300 <?php echo ($page ?? 1) == $i ? 'bg-orange-500 text-white' : 'bg-white text-gray-700'; ?> hover:bg-orange-100">
              <?php echo $i; ?>
            </a>
          <?php endfor; ?>
        </nav>
      </div>
    <?php endif; ?>
</div>