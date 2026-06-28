<div class="container mx-auto px-4 py-8">
    <!-- include daterangepicker assets -->
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Customer Invoices</h1>
        <!-- <a href="<?php //echo base_url('?page=invoices&action=create'); ?>" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">+ Create Invoice</a> -->
    </div>
    
    <!-- FILTER SECTION -->
    <form method="GET" class="bg-white border-2 border-purple-500 rounded-xl p-6 mb-6">
      <input type="hidden" name="page" value="dispatch">
      <input type="hidden" name="action" value="list">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-5">

        <div>
          <label class="text-sm font-semibold">Date Range :</label>
          <input type="text" id="daterange" name="date_range" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none" value="<?= htmlspecialchars($_GET['date_range'] ?? '') ?>">
        </div>

        <div>
          <label class="text-sm font-semibold">AWB Number :</label>
          <input type="text" name="awb_number" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none" value="<?= htmlspecialchars($_GET['awb_number'] ?? '') ?>">
        </div>

        <div>
          <label class="text-sm font-semibold">Order Number :</label>
          <input type="text" name="order_number" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none" value="<?= htmlspecialchars($_GET['order_number'] ?? '') ?>">
        </div>

        <div>
          <label class="text-sm font-semibold">Invoice No:</label>
          <input type="text" name="invoice_number" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none" value="<?= htmlspecialchars($_GET['invoice_number'] ?? '') ?>">
        </div>

        <div>
          <label class="text-sm font-semibold">Customer Phone / Email:</label>
          <input type="text" name="customer_contact" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none" value="<?= htmlspecialchars($_GET['customer_contact'] ?? '') ?>">
        </div>

        <div>
          <label class="text-sm font-semibold">Payment:</label>
          <select name="payment_mode" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none">
            <option value="">All</option>
            <option value="cod" <?= ($_GET['payment_mode'] ?? '') === 'cod' ? 'selected' : '' ?>>COD</option>
            <option value="prepaid" <?= ($_GET['payment_mode'] ?? '') === 'prepaid' ? 'selected' : '' ?>>Prepaid</option>
          </select>
        </div>

        <div>
          <label class="text-sm font-semibold">Status :</label>
          <select name="status" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none">
            <option value="">All</option>
            <option value="ready_to_ship" <?= ($_GET['status'] ?? '') === 'ready_to_ship' ? 'selected' : '' ?>>Ready to Ship</option>
            <option value="NEW" <?= ($_GET['status'] ?? '') === 'NEW' ? 'selected' : '' ?>>Dispatched</option>
            <option value="failed" <?= ($_GET['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
          </select>
        </div>
        <div>
            <label class="text-sm font-semibold">Box Size</label>
              <select name="box_size" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none" >              
                  <option value="">Select Size</option>
                  <option value="R-1" data-length="22" data-width="17" data-height="5">R-1 (22x17x5 inch)</option>
                  <option value="R-2" data-length="16" data-width="13" data-height="13">R-2 (16x13x13 inch)</option>
                  <option value="R-3" data-length="16" data-width="11" data-height="7">R-3 (16x11x7 inch)</option>
                  <option value="R-4" data-length="13" data-width="10" data-height="7">R-4 (13x10x7 inch)</option>
                  <option value="R-5" data-length="21" data-width="11" data-height="7">R-5 (21x11x7 inch)</option>
                  <option value="R-6" data-length="11" data-width="10" data-height="8">R-6 (11x10x8 inch)</option>
                  <option value="R-7" data-length="8" data-width="6" data-height="5">R-7 (8x6x5 inch)</option>
                  <option value="R-8" data-length="12" data-width="12" data-height="1.5">R-8 (12x12x1.5 inch)</option>
                  <option value="R-9" data-length="17" data-width="12" data-height="2">R-9 (17x12x2 inch)</option>
                  <option value="R-10" data-length="12" data-width="9" data-height="2">R-10 (12x9x2 inch)</option>
                  <option value="R-11" data-length="10" data-width="10" data-height="2">R-11 (10x10x2 inch)</option>
                  <option value="R-12" data-length="13" data-width="9" data-height="5">R-12 (13x9x5 inch)</option>
                  <option value="R-13" data-length="11" data-width="8" data-height="5">R-13 (11x8x5 inch)</option>
                  <option value="R-14" data-length="14" data-width="12" data-height="10">R-14 (14x12x10 inch)</option>
              </select>
        </div>
        <div>
          <label class="text-sm font-semibold">Category :</label>
          <select class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2" name="category">
            <option value="">All Categories</option>
            <?php foreach (getCategories() as $key => $value): ?>
                <option value="<?php echo $key; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] === $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-sm font-semibold">Invoice Value Min (₹):</label>
          <input type="number" name="invoice_value_min" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none" placeholder="Min" min="0" step="0.01" value="<?= htmlspecialchars($_GET['invoice_value_min'] ?? '') ?>">
        </div>
        <div>
          <label class="text-sm font-semibold">Invoice Value Max (₹):</label>
          <input type="number" name="invoice_value_max" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none" placeholder="Max" min="0" step="0.01" value="<?= htmlspecialchars($_GET['invoice_value_max'] ?? '') ?>">
        </div>
        <div>
          <label class="text-sm font-semibold">Created By:</label>
          <select name="created_by" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none">
            <option value="">All Staff</option>
            <?php foreach ($staffList as $staffId => $staffName): ?>
                <option value="<?php echo $staffId; ?>" <?php echo (isset($_GET['created_by']) && $_GET['created_by'] == $staffId) ? 'selected' : ''; ?>><?php echo htmlspecialchars($staffName); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="text-sm font-semibold">Box Weight Min (kg):</label>
          <input type="number" name="box_weight_min" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none" placeholder="Min" min="0" step="0.01" value="<?= htmlspecialchars($_GET['box_weight_min'] ?? '') ?>">
        </div>
        <div>
          <label class="text-sm font-semibold">Box Weight Max (kg):</label>
          <input type="number" name="box_weight_max" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none" placeholder="Max" min="0" step="0.01" value="<?= htmlspecialchars($_GET['box_weight_max'] ?? '') ?>">
        </div>
        <div>
          <label class="text-sm font-semibold">Item Code:</label>
          <input type="text" name="item_code" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none" placeholder="Item Code" value="<?= htmlspecialchars($_GET['item_code'] ?? '') ?>">
        </div>
        <div>
          <label class="text-sm font-semibold">Payment ID:</label>
          <input type="text" name="payment_id" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none" placeholder="Payment ID" value="<?= htmlspecialchars($_GET['payment_id'] ?? '') ?>">
        </div>
        <div>
          <label class="text-sm font-semibold">Batch No:</label>
          <input type="text" name="batch_no" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none" placeholder="Batch No" value="<?= htmlspecialchars($_GET['batch_no'] ?? '') ?>">
        </div>
        <div class="">
          <label for="country" class="text-sm font-semibold">Country</label>
          <select id="country" name="country" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none">
              <option value="" selected>All Country</option>
              <optgroup label="Easy">
                  <option value="overseas">Overseas</option>
                  <option value="IN">India</option>
              </optgroup>
              <?php foreach ($country_list as $key => $value): ?>
                  <option value="<?php echo $key; ?>" <?php echo (isset($_GET['country']) && $_GET['country'] === $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
              <?php endforeach; ?>
          </select>
        </div>
        <div><?php //print_r($warehouseList);?>
          <label class="text-sm font-semibold">Warehouse:</label>
          <select name="warehouse_id" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none">
            <!-- <option value="">All Warehouses</option> -->
            <?php 
           
            foreach ($warehouseList as $warehouse): ?>
                <option value="<?php echo $warehouse['id']; ?>" <?php echo (isset($_GET['warehouse_id']) && $_GET['warehouse_id'] == $warehouse['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($warehouse['address_title']); ?></option>
            <?php endforeach; ?>
          </select>

        </div>
        <div>
          <label class="text-sm font-semibold">Shipping Carrier:</label>
          <select name="shipping_carrier" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none">
            <!-- <option value="">All Carriers</option> -->
            <option value="shiprocket" <?php echo (isset($_GET['shipping_carrier']) && $_GET['shipping_carrier'] === 'shiprocket') ? 'selected' : ''; ?>>ShipRocket</option>
            <!-- <option value="bluedart" <?php //echo (isset($_GET['shipping_carrier']) && $_GET['shipping_carrier'] === 'bluedart') ? 'selected' : ''; ?>>BlueDart</option> -->
          </select>
        </div>
        <div class="flex items-end">
          <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 rounded-md transition">
            Search
          </button>
        </div>

      </div>
    </form>
    <?PHP $query_string = ''; ?>

    <!-- ACTION BAR -->
    <div class="flex flex-col md:flex-row justify-end items-center gap-3 mb-5">
      <!--clear all check-->
      <button id="clear-selection-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md transition" onclick="localStorage.removeItem('selected_dispatch_invoices'); document.querySelectorAll('input.label-checkbox').forEach(cb => cb.checked = false);">
        Clear Selection
      </button>
      <button id="bulk-print-labels-btn" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md transition">
        Print Label
      </button>
      <button id="bulk-update-status-btn" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md transition">
        Update Status
      </button>
      
      <select id="sort-order" class="border border-gray-300 rounded-md px-3 py-2" onchange="location.href='?page=dispatch&action=list&sort=' + this.value + '&<?= $query_string ?>';">
                    
                    <option value="desc" <?= (isset($_GET['sort']) && $_GET['sort'] === 'desc') ? 'selected' : '' ?>>Sort By New to Old</option>
                    <option value="asc" <?= (isset($_GET['sort']) && $_GET['sort'] === 'asc') ? 'selected' : '' ?>>Sort By Old to New</option>
            </select>
    </div>


    <!-- ORDER LIST -->
    <div class="space-y-4">
      <?php if (!empty($invoices)): ?>
        <?php foreach ($invoices as $invoice): ?>
          <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
              <!-- LEFT -->
              <div class="flex gap-4">
                <input type="checkbox" class="mt-1 w-5 h-5 label-checkbox" value="<?= htmlspecialchars($invoice['id']); ?>">
                <div class="flex gap-8 flex-wrap">
                  <div class="flex flex-col gap-2">
                    <div> 
                    <p class="text-xs text-gray-500">Inv No.</p>
                    <?php if($invoice['status'] == 'cancelled'): ?>
                      <p class="text-red-500 font-semibold"><s><?php echo htmlspecialchars($invoice['invoice_number'] ?? $invoice['id']); ?></s></p>
                    <?php else: ?>
                      <p class="text-blue-600 font-semibold"><a href="<?php echo base_url('?page=invoices&action=generate_pdf&invoice_id=' . $invoice['id']); ?>"><?php echo htmlspecialchars($invoice['invoice_number'] ?? $invoice['id']); ?></a></p>
                    <?php endif; ?>
                    <p class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($invoice['invoice_date'] ?? '')); ?></p>                 
                    </div>
                    <div>
<?php
                    // build order links only if order_number is set (avoid duplicates)
                    $orderLinks = [];
                    $seen = [];
                    foreach ($invoice['items'] ?? [] as $item) {
                        $num = trim((string)($item['order_number'] ?? ''));
                        if ($num === '' || isset($seen[$num])) {
                            continue;
                        }
                        $seen[$num] = true;
                        $orderLinks[] = '<a href="' . base_url('?page=orders&action=get_order_details_html&type=outer&order_number=' . htmlspecialchars($num)) . '">' . htmlspecialchars($num) . '</a>';
                    }
                    if (!empty($orderLinks)): ?>
                    <p class="text-xs text-gray-500">Order No.</p>
                    <p class="text-blue-600 font-semibold"><?php echo implode('<br>', $orderLinks); ?></p>
                    <p class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($invoice['invoice_date'] ?? '')); ?></p>
                    <?php endif; ?>

                    <!-- <p class="text-xs text-gray-500">Shiprocket Shipment ID</p>
                    <p class="text-blue-600 font-semibold">
                      <?php 
                        //$shiprocketOrderIds = [];
                        // if (!empty($invoice_dispatch[$invoice['id']])) {
                        //   foreach ($invoice_dispatch[$invoice['id']] as $dispatch) {
                        //     if (!empty($dispatch['shiprocket_shipment_id'])) {
                        //       echo htmlspecialchars($dispatch['shiprocket_shipment_id']) . '<br>';
                        //     }
                        //   }
                        // }
                        //echo implode(' | ', $shiprocketOrderIds);
                      ?></p>   -->
                  </div>
                  </div>
                  
                </div>
              </div>
              <!-- MIDDLE GRID -->
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 flex-1">
                <div class="flex flex-col gap-2">
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
                  <p class="text-xs text-gray-500">Dispatch Status</p>
                  <div class="flex gap-2 mt-2">
                    <?php 
                      // $dispatchStatus = '-';
                      // if (!empty($invoice_dispatch[$invoice['id']])) {
                      //   $allDispatched = true;
                      //   foreach ($invoice_dispatch[$invoice['id']] as $dispatch) {
                      //     if (empty($dispatch['awb_code'])) {
                      //       $allDispatched = false;
                      //       break;
                      //     }
                      //   }
                      //   $dispatchStatus = $allDispatched ? '<span class="bg-green-100 text-green-600 text-xs px-2 py-1 rounded">Dispatched</span>' : '<span class="bg-yellow-100 text-yellow-600 text-xs px-2 py-1 rounded">Ready to Ship</span>';
                      // }
                      // echo $dispatchStatus;
                      foreach ($invoice_dispatch[$invoice['id']] as $dispatch) {
                          if (strtolower($dispatch['shipment_status'] ?? '') === 'cancelled' || strtolower($dispatch['shipment_status'] ?? '') === 'cancellation requested') {
                              echo '<span class="bg-red-100 text-red-600 text-xs px-2 py-1 rounded">Cancelled</span>';
                          }  else {
                              echo '<span class="bg-blue-100 text-blue-600 text-xs px-2 py-1 rounded">' . htmlspecialchars($dispatch['shipment_status'] ?? '') . '</span>';
                          }
                        }
                    ?>
                  </div>
                </div>  
                </div>
                <div class="flex flex-col gap-2">
                  <div>
                    <p class="text-xs text-gray-500">Customer</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($invoice['name'] ?? '-'); ?></p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($invoice['email'] ?? ''); ?></p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($invoice['phone'] ?? ''); ?></p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Items:</p>
                    <p class="font-semibold">
                      <?php 
                        $items = $invoice['items'] ?? [];
                        $itemCount = count($items);
                        if ($itemCount > 0) {
                          $itemCodes = array_column($items, 'item_code');
                          
                          echo htmlspecialchars($items[0]['item_code'] ?? '');
                          if ($itemCount > 1) {
                            echo '<span class="text-xs text-blue-500 cursor-pointer" title="' . implode(', ', $itemCodes) . '">[ +' . ($itemCount - 1). ' ]</span>';
                          }
                        }
                      ?>
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Created by: <?php echo htmlspecialchars($staffList[$invoice['created_by']] ?? '-'); ?></p>
                  </div>
                </div>
                <div class="flex flex-col gap-2">
                  <div>
                    <p class="text-xs text-gray-500">AWB:</p>
                    <p class="text-blue-600 font-medium text-sm">
                      <?php 
                        $awbs = [];
                        if (!empty($invoice_dispatch[$invoice['id']])) {
                          foreach ($invoice_dispatch[$invoice['id']] as $dispatch) {
                            if (!empty($dispatch['awb_code'])) {
                              if(strtolower($dispatch['shipment_status'] ?? '') === 'cancelled' || strtolower($dispatch['shipment_status'] ?? '') === 'cancellation requested') {
                                $link = '<span class="line-through text-red-500">' . htmlspecialchars($dispatch['awb_code']) . '</span>';
                              } else {
                                $link = !empty($dispatch['label_url']) ? '<a href="' . htmlspecialchars($dispatch['label_url']) . '" target="_blank">' . htmlspecialchars($dispatch['awb_code']) . '</a>' : htmlspecialchars($dispatch['awb_code']);
                              }
                              $awbs[] = $link;
                            }
                          }
                        }
                        echo implode(' | ', $awbs);
                      ?>
                    </p>
                    <p class="text-xs text-gray-400 mt-1"><?php echo date('d M Y', strtotime($invoice['invoice_date'] ?? '')); ?></p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Box Size</p>
                    <p class="font-semibold text-gray-800">
                      <?php 
                        $boxSizes = [];
                        if (!empty($invoice_dispatch[$invoice['id']])) {
                          foreach ($invoice_dispatch[$invoice['id']] as $dispatch) {
                            //length, width, height
                            $dimensions = [];
                            if (!empty($dispatch['length'])) {
                              $dimensions[] = $dispatch['length'];
                            }
                            if (!empty($dispatch['width'])) {
                              $dimensions[] = $dispatch['width'];
                            }
                            if (!empty($dispatch['height'])) {
                              $dimensions[] = $dispatch['height'];
                            }
                            
                            if (!empty($dispatch['box_size'])) {
                              $boxSizes[] = htmlspecialchars($dispatch['box_size']);
                            } elseif (!empty($dimensions)) {
                              $dimensions = array_map(function($d) { return rtrim(rtrim((string)$d, '0'), '.'); }, $dimensions);
                              $boxSizes[] = implode('x', $dimensions) . ' inch';
                            }
                          }
                        }
                        echo !empty($boxSizes) ? implode(' | ', $boxSizes) : '-';
                      ?>
                    </p>
                    <p class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars($invoice['batch_no'] ? 'Batch No: ' . $invoice['batch_no'] : ''); ?></p>
                  </div>
                </div>
                <div class="flex flex-col gap-3">
                  <div>
                    <p class="text-xs text-gray-500">Shipper ID</p>
                    <div class="font-semibold text-gray-800 flex flex-wrap items-center gap-x-2 gap-y-1">
                      <?php
                        $shipperIdParts = [];
                        $eiDispatchesForShipper = $invoice_dispatch[$invoice['id']] ?? [];
                        $shipperBoxCount = count($eiDispatchesForShipper);
                        if ($shipperBoxCount > 0) {
                          foreach ($eiDispatchesForShipper as $shipperDispatch) {
                            $shipperDispatchId = (int) ($shipperDispatch['id'] ?? 0);
                            if ($shipperDispatchId <= 0) {
                              continue;
                            }
                            $sid = trim((string) ($shipperDispatch['exotic_shipment_id'] ?? ''));
                            $boxNo = (int) ($shipperDispatch['box_no'] ?? 0);
                            $boxPrefix = $shipperBoxCount > 1 && $boxNo > 0
                              ? '<span class="text-xs font-normal text-gray-500 mr-0.5">Box ' . $boxNo . ':</span> '
                              : '';
                            if ($sid !== '') {
                              $shipperIdParts[] = $boxPrefix . htmlspecialchars($sid);
                            } elseif (
                              trim((string) ($shipperDispatch['awb_code'] ?? '')) !== ''
                              && !in_array(
                                strtolower(trim((string) ($shipperDispatch['shipment_status'] ?? ''))),
                                ['cancelled', 'cancellation requested'],
                                true
                              )
                            ) {
                              $genTitle = $shipperBoxCount > 1 && $boxNo > 0
                                ? 'Generate Shipper ID (Box ' . $boxNo . ')'
                                : 'Generate Shipper ID';
                              $shipperIdParts[] = $boxPrefix . '<button type="button" class="inline-flex items-center justify-center text-orange-500 hover:text-orange-600 hover:bg-orange-50 rounded-full p-1 align-middle border-0 bg-transparent cursor-pointer" onclick="openShipmentAddModal(' . $shipperDispatchId . ')" title="' . htmlspecialchars($genTitle, ENT_QUOTES, 'UTF-8') . '" aria-label="' . htmlspecialchars($genTitle, ENT_QUOTES, 'UTF-8') . '">'
                                . '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">'
                                . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>'
                                . '</svg></button>';
                            } else {
                              $shipperIdParts[] = $boxPrefix . '<span class="text-gray-400 font-normal">-</span>';
                            }
                          }
                        }
                        echo !empty($shipperIdParts) ? implode('<span class="text-gray-300 font-normal">|</span>', $shipperIdParts) : '-';
                      ?>
                    </div>
                  </div>
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
                    <p class="text-xs text-gray-500">ETD:</p>
                    <p class="font-semibold">
                      <?php 
                        $etd = null;
                        if (!empty($invoice_dispatch[$invoice['id']])) {
                          foreach ($invoice_dispatch[$invoice['id']] as $dispatch) {
                            if (!empty($dispatch['etd'])) {
                              $etd = $dispatch['etd'];
                              break;
                            }
                          }
                        }
                        echo $etd ? date('d M Y', strtotime($etd)) : '-';
                      ?>
                    </p>
                  </div>
                </div>
              </div>
              <!-- RIGHT -->
              <div class="flex flex-col sm:items-end gap-3">
                <div class="relative ">
                  <button class="text-gray-600 hover:bg-gray-100 rounded-full px-2 text-lg" onclick="toggleMenu(this)">
                    ⋮
                  </button>
                  <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                    <?php if (strtolower(trim((string)($invoice['status'] ?? ''))) !== 'cancelled'): ?>
                    <a href="<?php echo base_url('?page=invoices&action=generate_pdf&invoice_id=' . $invoice['id']); ?>" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Download invoice</a>
                    <?php endif; ?>
                    <?php /*if (!empty($invoice_dispatch[$invoice['id']])): ?>
                      <?php foreach ($invoice_dispatch[$invoice['id']] as $dispatch): ?>
                        <a href="<?php echo $dispatch['label_url']; ?>" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Download <Label><?php echo htmlspecialchars($dispatch['awb_code']); ?></Label></a>
                      <?php endforeach; ?>
                    <?php endif; */?>
                    <?php
                      $needsRetry = false;
                      $reDispatch = false;
                      $canCancelDispatch = false;
                      $invoiceCancelled = strtolower(trim((string)($invoice['status'] ?? ''))) === 'cancelled';
                      if (!empty($invoice_dispatch[$invoice['id']])) {
                        foreach ($invoice_dispatch[$invoice['id']] as $dispatch) {
                          if (empty($dispatch['awb_code'])) {
                            $needsRetry = true;
                            break;
                          }
                          if (strtolower($dispatch['shipment_status'] ?? '') === 'cancelled' || strtolower($dispatch['shipment_status'] ?? '') === 'cancellation requested') {
                            $reDispatch = true;
                          }
                        }
                        if (!$invoiceCancelled) {
                          foreach ($invoice_dispatch[$invoice['id']] as $dispatch) {
                            $shipStatus = strtolower(trim((string)($dispatch['shipment_status'] ?? '')));
                            if ($shipStatus !== 'cancelled' && $shipStatus !== 'cancellation requested') {
                              $canCancelDispatch = true;
                              break;
                            }
                          }
                        }
                      }
                    ?>
                    <?php if ($needsRetry): ?>
                      <button class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 border-none bg-transparent cursor-pointer" onclick="retryDispatchAjax(<?php echo htmlspecialchars($invoice['id']); ?>)" style="padding: 0.5rem 1rem;">AWB Generate</button>
                      
                    <?php endif; ?>
                    <?php if ($reDispatch): ?>
                      <button class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 border-none bg-transparent cursor-pointer" onclick="reDispatchAjax(<?php echo htmlspecialchars($invoice['id']); ?>)" style="padding: 0.5rem 1rem;">Re-Dispatch</button>
                    <?php endif; ?>
                    <?php if ($canCancelDispatch): ?>
                    <button class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 border-none bg-transparent cursor-pointer" onclick="cancelDispatchAjax(<?php echo htmlspecialchars($invoice['id']); ?>)" style="padding: 0.5rem 1rem;">Cancel Dispatch</button>
                    <?php endif; ?>
                    <button class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 border-none bg-transparent cursor-pointer" onclick="updateStatusAjax(<?php echo htmlspecialchars($invoice['id']); ?>)" style="padding: 0.5rem 1rem;">Update Status</button>
                    <?php if (strtolower(trim((string)($invoice['status'] ?? ''))) !== 'cancelled'): ?>
                    <button class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 border-none bg-transparent cursor-pointer" onclick="cancelInvoiceAjax(<?php echo htmlspecialchars($invoice['id']); ?>)" style="padding: 0.5rem 1rem;">Cancel Invoice</button>
                    <?php endif; ?>
                  </div>
                </div>
                <div></div>
                <div></div>
                
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
    <div class="mt-6 flex justify-center space-x-4 items-center">
         Showing <?php echo count($invoices); ?> of <?php echo $totalInvoices; ?> invoices
      <a href="<?php echo base_url('?page=dispatch&action=list'); ?>" class="px-4 py-2 rounded bg-gray-100 text-gray-800 hover:bg-gray-200">← Back </a> 
      
      
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
    <!--record per page dropdown -->
    <div class="flex justify-end mt-4">
      <label for="perPage" class="text-sm font-semibold mr-2 m">Records per page:</label>
      <select id="perPage" name="perPage" class="border border-gray-300 rounded-md px-1 py-2" onchange="location = this.value;">
        <?php 
          $options = [10, 25, 50, 100];
          $baseUrl = base_url('?page=dispatch&action=list&p=1'); // Reset to page 1 on change
          foreach ($options as $option): 
            $url = $baseUrl . '&per_page=' . $option;
        ?>
          <option value="<?php echo $url; ?>" <?php echo ($perPage ?? 10) == $option ? 'selected' : ''; ?>>
            <?php echo $option; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    </div>
</div>

<div id="shipment-add-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-black bg-opacity-40 z-0" data-shipment-add-close></div>
  <div class="shipment-add-panel relative z-10 w-full max-w-lg max-h-[92vh] overflow-hidden bg-white rounded-lg shadow-xl flex flex-col">
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 shrink-0">
      <div>
        <h2 class="text-lg font-semibold text-gray-900">Generate Shipper ID</h2>
        <p class="text-xs text-gray-500 mt-0.5">Register this package on Exotic India (one time per box)</p>
      </div>
      <button type="button" class="text-gray-600 hover:text-gray-900 text-2xl leading-none" data-shipment-add-close aria-label="Close">&times;</button>
    </div>
    <div class="p-4 overflow-y-auto space-y-4 text-sm">
      <div id="shipment-add-meta" class="text-sm text-gray-700 bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-2"></div>
      <div id="shipment-add-shipper-id-box" class="hidden rounded-lg border border-green-200 bg-green-50 p-4 text-center">
        <p class="text-xs font-semibold uppercase tracking-wide text-green-800 mb-1">Shipper ID</p>
        <p id="shipment-add-shipper-id-value" class="text-2xl font-black text-green-900 break-all"></p>
      </div>
      <div id="shipment-add-issues" class="hidden text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-lg p-4"></div>
      <div id="shipment-add-action-row" class="flex flex-wrap gap-2 items-center">
        <button type="button" id="shipment-add-execute-btn" class="bg-orange-500 hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold px-5 py-2.5 rounded-lg text-sm">Generate Shipper ID</button>
        <span id="shipment-add-status" class="text-xs text-gray-500"></span>
      </div>
      <div id="shipment-add-result" class="hidden rounded-lg border p-4 text-sm"></div>
      <details id="shipment-add-technical" class="hidden text-xs border border-gray-200 rounded-lg p-3 bg-gray-50">
        <summary class="cursor-pointer font-semibold text-gray-600 select-none">Technical details (for support)</summary>
        <div class="mt-3 space-y-3">
          <div>
            <label class="font-semibold text-gray-700 block mb-1">Request sent</label>
            <textarea id="shipment-add-request" class="w-full h-36 font-mono text-xs border border-gray-300 rounded p-2 bg-white" readonly spellcheck="false"></textarea>
          </div>
          <div id="shipment-add-response-wrap" class="hidden space-y-3">
            <div>
              <label class="font-semibold text-gray-700 block mb-1">Response summary</label>
              <pre id="shipment-add-response-summary" class="w-full max-h-32 overflow-auto font-mono text-xs border border-gray-300 rounded p-2 bg-white whitespace-pre-wrap"></pre>
            </div>
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="font-semibold text-gray-700">Response body</label>
                <button type="button" id="shipment-add-copy-raw-btn" class="text-orange-600 hover:text-orange-700 font-semibold">Copy</button>
              </div>
              <pre id="shipment-add-response-raw" class="w-full max-h-40 overflow-auto font-mono text-xs border border-gray-300 rounded p-2 bg-white whitespace-pre-wrap"></pre>
            </div>
            <div id="shipment-add-response-headers-wrap" class="hidden">
              <label class="font-semibold text-gray-700 block mb-1">Response headers</label>
              <pre id="shipment-add-response-headers" class="w-full max-h-32 overflow-auto font-mono text-xs border border-gray-300 rounded p-2 bg-white whitespace-pre-wrap"></pre>
            </div>
          </div>
        </div>
      </details>
    </div>
    <div class="px-4 py-3 border-t border-gray-200 flex justify-end gap-2 bg-gray-50 shrink-0">
      <button type="button" id="shipment-add-done-btn" class="hidden bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2 rounded text-sm">Done</button>
      <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-4 py-2 rounded text-sm" data-shipment-add-close>Close</button>
    </div>
  </div>
</div>

<script>
function toggleMenu(button) {

    const menu = button.nextElementSibling;

    // close all other menus
    document.querySelectorAll('.dropdown-menu').forEach(m => {
        if (m !== menu) m.classList.add('hidden');
    });

    menu.classList.toggle('hidden');
}

// close when clicking outside
document.addEventListener("click", function(e) {

    if (!e.target.closest(".relative")) {
        document.querySelectorAll(".dropdown-menu").forEach(menu => {
            menu.classList.add("hidden");
        });
    }

});

// Persist selected invoices across pages using localStorage
const DISPATCH_STORAGE_KEY = 'selected_dispatch_invoices';

function saveCheckedInvoices() {
    let checked = JSON.parse(localStorage.getItem(DISPATCH_STORAGE_KEY) || '[]');
    const allCbs = Array.from(document.querySelectorAll('input.label-checkbox'));
    const checkedOnPage = allCbs.filter(cb => cb.checked).map(cb => cb.value);
    const uncheckedOnPage = allCbs.filter(cb => !cb.checked).map(cb => cb.value);

    checked = checked.filter(id => !uncheckedOnPage.includes(id));
    checkedOnPage.forEach(id => { if (!checked.includes(id)) checked.push(id); });
    localStorage.setItem(DISPATCH_STORAGE_KEY, JSON.stringify(checked));
}

function restoreCheckedInvoices() {
    const checked = JSON.parse(localStorage.getItem(DISPATCH_STORAGE_KEY) || '[]');
    document.querySelectorAll('input.label-checkbox').forEach(cb => {
        cb.checked = checked.includes(cb.value);
    });
}
  const bulkUpdateBtn = document.getElementById('bulk-update-status-btn');
  if (bulkUpdateBtn) {
    bulkUpdateBtn.addEventListener('click', function(e) {
      e.preventDefault();
      const ids = getSelectedInvoiceIds();
      if (ids.length === 0) {
        alert('Please select at least one invoice');
        return;
      }
      bulkUpdateBtn.disabled = true;
      const origText = bulkUpdateBtn.textContent;
      bulkUpdateBtn.textContent = 'Processing...';
      fetch('?page=dispatch&action=bulk_update_status', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ invoice_ids: ids })
      })
      .then(res => {
        bulkUpdateBtn.disabled = false;
        bulkUpdateBtn.textContent = origText;
        if (!res.ok) throw new Error('Network response was not ok');
        return res.json();
      })
      .then(data => {
        if (data && data.status === 'success') {
          const summary = data.summary || {};
          const msg = `Processed ${summary.processed_invoices || 0} invoices, ${summary.processed_dispatches || 0} dispatches. Updated: ${summary.updated || 0}`;
          if (typeof showAlert === 'function') {
            showAlert(msg, 'success');
          } else {
            alert(msg);
          }
          setTimeout(() => location.reload(), 3000);
        } else {
          const err = data.message || 'Failed to update status';
          if (typeof showAlert === 'function') showAlert(err, 'error'); else alert(err);
        }
      })
      .catch(err => {
        console.error(err);
        bulkUpdateBtn.disabled = false;
        bulkUpdateBtn.textContent = origText;
        if (typeof showAlert === 'function') showAlert('Error updating statuses: ' + err.message, 'error'); else alert('Error updating statuses: ' + err.message);
      });
    });
  }

function getSelectedInvoiceIds() {
    const visibleChecked = Array.from(document.querySelectorAll('input.label-checkbox:checked')).map(cb => cb.value);
    const stored = JSON.parse(localStorage.getItem(DISPATCH_STORAGE_KEY) || '[]');
    return Array.from(new Set([...stored, ...visibleChecked]));
}

document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('label-checkbox')) {
        saveCheckedInvoices();
    }
});

document.addEventListener('DOMContentLoaded', restoreCheckedInvoices);

    // initialize date range picker for filter
    $(function() {
        if ($('#daterange').length) {
            $('#daterange').daterangepicker({
                autoUpdateInput: false,
                showDropdowns: true,
                locale: { format: 'YYYY-MM-DD' },
                autoApply: true,
                opens: 'right'
            });
            $('#daterange').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
            });
            $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
            // if input already has value (from GET), ensure picker reflects it
            var existing = $('#daterange').val();
            if (existing) {
                var parts = existing.split(' to ');
                if (parts.length === 2) {
                    $('#daterange').data('daterangepicker').setStartDate(parts[0]);
                    $('#daterange').data('daterangepicker').setEndDate(parts[1]);
                }
            }
        }
    });
const bulkPrintBtn = document.getElementById('bulk-print-labels-btn');
if (bulkPrintBtn) {
    bulkPrintBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const ids = getSelectedInvoiceIds();
        if (ids.length === 0) {
            alert('Please select at least one invoice');
            return;
        }
        //processing
        bulkPrintBtn.disabled = true;
        bulkPrintBtn.innerHTML = '<span class="animate-spin">⏳</span> Processing...';
        fetch('?page=dispatch&action=merge_labels', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ invoice_ids: ids })
        })
        .then(res => {
            bulkPrintBtn.disabled = false;
            bulkPrintBtn.textContent = 'Print Label';
            if (!res.ok) throw new Error('Network response was not ok');
            return res.blob();
        })
        .then(blob => {
            const url = URL.createObjectURL(blob);
            const win = window.open(url, '_blank');
            win.onload = function() {
                setTimeout(() => win.print(), 500);
            };
            bulkPrintBtn.disabled = false;
            bulkPrintBtn.textContent = 'Print Label';
        })
        .catch(err => {
            console.error(err);
            showAlert('Error merging labels: ' + err.message);
            bulkPrintBtn.disabled = false;
            bulkPrintBtn.textContent = 'Print Label';
        });
    });
}
</script>
<script>
    function toggleMenu(button) {
      const menu = button.nextElementSibling;
      menu.classList.toggle('hidden');
    }
    function retryDispatchAjax(invoiceId) {
      if (!confirm('Retry dispatch for this invoice?')) return;
      
      fetch('?page=dispatch&action=retry_invoice', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ invoice_id: invoiceId })
      })
      .then(res => res.json())
      .then(data => {
          if (data.success) {
              const results = Array.isArray(data.results) ? data.results : [];
              const contentHtml = results.map((res, idx) => {
                  const awb = res?.data?.awb_info_response;
                  const label = res?.data?.label_info_response;
                  const awbmsg = awb?.response?.data || '';
                  const labelmsg = label?.response || '';
                  return `
                    <div class="mb-4">
                      <div class="font-semibold mb-2">Dispatch #${idx + 1}</div>
                      <p class="text-sm text-gray-700"><strong>AWB Response:</strong> ${escapeHtml(awbmsg)}</p>                     
                      <details class="mb-2">
                        <summary class="cursor-pointer font-medium">AWB Info</summary>
                        <pre class="whitespace-pre-wrap text-xs mt-1">${escapeHtml(JSON.stringify(awb, null, 2))}</pre>
                      </details>
                      <p class="text-sm text-gray-700"><strong>Label Response:</strong> ${escapeHtml(labelmsg)}</p>
                      <details>
                        <summary class="cursor-pointer font-medium">Label Info</summary>
                        <pre class="whitespace-pre-wrap text-xs mt-1">${escapeHtml(JSON.stringify(label, null, 2))}</pre>
                      </details>
                    </div>`;
              }).join('');

              if (contentHtml) {
                  showModal('Retry Shipment Results', contentHtml);
              } else {
                  showAlert('Retry initiated successfully. No API response data available.', 'success');
              }
          } else {
              alert('Error: ' + (data.message || 'Failed to retry dispatch'));
          }
      })
      .catch(err => {
          console.error(err);
          alert('Error retrying dispatch');
      });
    }

    function escapeHtml(text) {
      if (typeof text !== 'string') return text;
      return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    let shipmentAddCurrentDispatchId = 0;

    function closeShipmentAddModal() {
      const modal = document.getElementById('shipment-add-modal');
      if (modal) {
        modal.classList.add('hidden');
        modal.querySelectorAll('.shipment-add-panel > div').forEach(el => el.classList.remove('hidden'));
      }
      shipmentAddCurrentDispatchId = 0;
    }

    function resetShipmentAddModalState() {
      ['shipment-add-result', 'shipment-add-shipper-id-box'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
      });
      const doneBtn = document.getElementById('shipment-add-done-btn');
      if (doneBtn) doneBtn.classList.add('hidden');
      const actionRow = document.getElementById('shipment-add-action-row');
      if (actionRow) actionRow.classList.remove('hidden');
      const technicalEl = document.getElementById('shipment-add-technical');
      if (technicalEl) technicalEl.classList.add('hidden');
    }

    function renderShipmentAddPreview(data, keepResponse) {
      const meta = document.getElementById('shipment-add-meta');
      const issuesEl = document.getElementById('shipment-add-issues');
      const requestEl = document.getElementById('shipment-add-request');
      const executeBtn = document.getElementById('shipment-add-execute-btn');
      const actionRow = document.getElementById('shipment-add-action-row');
      const statusEl = document.getElementById('shipment-add-status');
      const resultEl = document.getElementById('shipment-add-result');
      const shipperIdBox = document.getElementById('shipment-add-shipper-id-box');
      const shipperIdValue = document.getElementById('shipment-add-shipper-id-value');
      const doneBtn = document.getElementById('shipment-add-done-btn');
      const technicalEl = document.getElementById('shipment-add-technical');
      const responseWrap = document.getElementById('shipment-add-response-wrap');
      const responseSummaryEl = document.getElementById('shipment-add-response-summary');
      const responseRawEl = document.getElementById('shipment-add-response-raw');
      const responseHeadersWrap = document.getElementById('shipment-add-response-headers-wrap');
      const responseHeadersEl = document.getElementById('shipment-add-response-headers');

      const d = data.dispatch || {};
      const boxLabel = d.box_no ? ('Box ' + d.box_no) : 'This package';
      meta.innerHTML = [
        '<div><span class="text-gray-500">Order</span> <strong>' + escapeHtml(String(d.order_number || '—')) + '</strong></div>',
        '<div><span class="text-gray-500">Tracking (AWB)</span> <strong>' + escapeHtml(String(d.awb_code || '—')) + '</strong></div>',
        '<div><span class="text-gray-500">Courier</span> <strong>' + escapeHtml(String(d.courier_name || '—')) + '</strong></div>',
        '<div><span class="text-gray-500">Package</span> <strong>' + escapeHtml(boxLabel) + '</strong></div>',
      ].join('');

      const existingId = String(d.exotic_shipment_id || data.shipment_id || '').trim();
      const alreadyGenerated = !!data.already_generated || existingId !== '';
      const canGenerate = data.can_generate !== false;

      if (alreadyGenerated && shipperIdBox && shipperIdValue) {
        shipperIdBox.classList.remove('hidden');
        shipperIdValue.textContent = existingId;
        if (actionRow) actionRow.classList.add('hidden');
        if (resultEl) {
          resultEl.classList.remove('hidden');
          resultEl.className = 'rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-900';
          resultEl.textContent = 'Shipper ID is already generated for this package.';
        }
        if (doneBtn) doneBtn.classList.remove('hidden');
      } else {
        if (shipperIdBox) shipperIdBox.classList.add('hidden');
        if (shipperIdValue) shipperIdValue.textContent = '';
        if (actionRow) actionRow.classList.toggle('hidden', !canGenerate);
        if (resultEl) {
          resultEl.classList.add('hidden');
          resultEl.textContent = '';
        }
        if (doneBtn) doneBtn.classList.add('hidden');
      }

      const issues = Array.isArray(data.user_issues) && data.user_issues.length
        ? data.user_issues
        : (Array.isArray(data.issues) ? data.issues : []);
      if (issues.length && !alreadyGenerated) {
        issuesEl.classList.remove('hidden');
        issuesEl.innerHTML = '<p class="font-semibold mb-2">Please fix the following first:</p><ul class="list-disc ml-4 space-y-1">' +
          issues.map(i => '<li>' + escapeHtml(String(i)) + '</li>').join('') + '</ul>';
      } else {
        issuesEl.classList.add('hidden');
        issuesEl.innerHTML = '';
      }

      if (requestEl) requestEl.value = data.payload_json || '';
      if (technicalEl) technicalEl.classList.toggle('hidden', !(data.payload_json || keepResponse));
      if (executeBtn) executeBtn.disabled = !data.ready || alreadyGenerated || !canGenerate;

      if (!keepResponse) {
        statusEl.textContent = alreadyGenerated
          ? ''
          : (!canGenerate
            ? 'Shipper ID can only be generated when AWB is present and shipment is not cancelled.'
            : (data.ready ? 'Ready to generate Shipper ID.' : 'Complete the steps above, then try again.'));
        if (responseWrap) responseWrap.classList.add('hidden');
        if (responseSummaryEl) responseSummaryEl.textContent = '';
        if (responseRawEl) {
          responseRawEl.textContent = '';
          responseRawEl.dataset.copyText = '';
        }
        if (responseHeadersWrap) responseHeadersWrap.classList.add('hidden');
        if (responseHeadersEl) responseHeadersEl.textContent = '';
      }
    }

    function showShipmentAddResult(data) {
      const resultEl = document.getElementById('shipment-add-result');
      const shipperIdBox = document.getElementById('shipment-add-shipper-id-box');
      const shipperIdValue = document.getElementById('shipment-add-shipper-id-value');
      const actionRow = document.getElementById('shipment-add-action-row');
      const doneBtn = document.getElementById('shipment-add-done-btn');
      const statusEl = document.getElementById('shipment-add-status');
      const technicalEl = document.getElementById('shipment-add-technical');

      if (!resultEl) return;

      resultEl.classList.remove('hidden');
      if (data.success) {
        resultEl.className = 'rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-900';
        resultEl.textContent = data.message || 'Shipper ID generated successfully.';
        const sid = String(data.shipment_id || '').trim();
        if (sid && shipperIdBox && shipperIdValue) {
          shipperIdBox.classList.remove('hidden');
          shipperIdValue.textContent = sid;
        }
        if (actionRow) actionRow.classList.add('hidden');
        if (doneBtn) doneBtn.classList.remove('hidden');
        if (statusEl) statusEl.textContent = '';
      } else {
        resultEl.className = 'rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900';
        resultEl.textContent = data.message || 'Could not generate Shipper ID. Please try again.';
        if (statusEl) statusEl.textContent = '';
      }
      if (technicalEl) technicalEl.classList.remove('hidden');
    }

    function loadShipmentAddPreview(dispatchId, keepResponse) {
      const statusEl = document.getElementById('shipment-add-status');
      const executeBtn = document.getElementById('shipment-add-execute-btn');
      if (!keepResponse) {
        statusEl.textContent = 'Loading…';
        if (executeBtn) executeBtn.disabled = true;
      }

      return fetch('?page=dispatch&action=shipment_add_preview&dispatch_id=' + encodeURIComponent(dispatchId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          throw new Error(data.message || 'Could not load package details');
        }
        renderShipmentAddPreview(data, keepResponse);
        return data;
      });
    }

    function openShipmentAddModal(dispatchId) {
      shipmentAddCurrentDispatchId = parseInt(dispatchId, 10) || 0;
      if (!shipmentAddCurrentDispatchId) {
        return;
      }

      document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('hidden'));

      const modal = document.getElementById('shipment-add-modal');
      modal.classList.remove('hidden');
      modal.querySelectorAll('.shipment-add-panel > div').forEach(el => el.classList.remove('hidden'));
      resetShipmentAddModalState();

      loadShipmentAddPreview(shipmentAddCurrentDispatchId).catch(err => {
        const msg = 'Could not open Generate Shipper ID: ' + err.message;
        if (typeof showAlert === 'function') showAlert(msg, 'error'); else alert(msg);
        closeShipmentAddModal();
      });
    }

    document.getElementById('shipment-add-done-btn')?.addEventListener('click', function() {
      closeShipmentAddModal();
      location.reload();
    });

    document.getElementById('shipment-add-copy-raw-btn')?.addEventListener('click', async function() {
      const rawEl = document.getElementById('shipment-add-response-raw');
      const text = rawEl?.dataset?.copyText != null && rawEl.dataset.copyText !== ''
        ? rawEl.dataset.copyText
        : (rawEl?.textContent || '');
      if (!text) {
        if (typeof showAlert === 'function') showAlert('No response body to copy', 'warning');
        return;
      }
      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(text);
        } else {
          const temp = document.createElement('textarea');
          temp.value = text;
          document.body.appendChild(temp);
          temp.select();
          document.execCommand('copy');
          document.body.removeChild(temp);
        }
        if (typeof showAlert === 'function') showAlert('Response body copied', 'success');
      } catch (err) {
        if (typeof showAlert === 'function') showAlert('Copy failed', 'error');
      }
    });

    document.getElementById('shipment-add-execute-btn')?.addEventListener('click', function() {
      if (!shipmentAddCurrentDispatchId) return;

      const btn = this;
      const statusEl = document.getElementById('shipment-add-status');

      btn.disabled = true;
      statusEl.textContent = 'Generating Shipper ID…';

      fetch('?page=dispatch&action=shipment_add_execute', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ dispatch_id: shipmentAddCurrentDispatchId })
      })
      .then(res => res.json().then(data => ({ ok: res.ok, data })))
      .then(({ ok, data }) => {
        if (data.already_generated) {
          showShipmentAddResult({
            success: true,
            message: data.message || 'Shipper ID is already generated.',
            shipment_id: data.shipment_id || '',
          });
          loadShipmentAddPreview(shipmentAddCurrentDispatchId, true).catch(function() {});
          return;
        }

        const responseWrap = document.getElementById('shipment-add-response-wrap');
        const responseSummaryEl = document.getElementById('shipment-add-response-summary');
        const responseRawEl = document.getElementById('shipment-add-response-raw');
        const responseHeadersWrap = document.getElementById('shipment-add-response-headers-wrap');
        const responseHeadersEl = document.getElementById('shipment-add-response-headers');

        if (responseWrap) responseWrap.classList.remove('hidden');
        const rawBody = (data.response_raw != null && String(data.response_raw) !== '')
          ? String(data.response_raw)
          : '';
        const summary = {
          success: data.success,
          message: data.message,
          http_code: data.http_code,
          shipment_id: data.shipment_id || '',
        };
        if (responseSummaryEl) responseSummaryEl.textContent = JSON.stringify(summary, null, 2);
        if (responseRawEl) {
          if (rawBody !== '') {
            try {
              responseRawEl.textContent = JSON.stringify(JSON.parse(rawBody), null, 2);
            } catch (e) {
              responseRawEl.textContent = rawBody;
            }
          } else {
            responseRawEl.textContent = '(empty response from server)';
          }
          responseRawEl.dataset.copyText = rawBody;
        }
        const respHeaders = String(data.response_headers || '').trim();
        if (respHeaders !== '' && responseHeadersWrap && responseHeadersEl) {
          responseHeadersWrap.classList.remove('hidden');
          responseHeadersEl.textContent = respHeaders;
        } else if (responseHeadersWrap) {
          responseHeadersWrap.classList.add('hidden');
        }

        showShipmentAddResult(data);

        if (data.success && data.shipment_id) {
          loadShipmentAddPreview(shipmentAddCurrentDispatchId, true).catch(function() {});
        } else if (!ok && data.user_issues && data.user_issues.length) {
          renderShipmentAddPreview({
            dispatch: data.dispatch || {},
            user_issues: data.user_issues,
            issues: data.issues || [],
            payload_json: document.getElementById('shipment-add-request')?.value || '',
            ready: false,
            api_url: data.api_url
          }, true);
        }
      })
      .catch(err => {
        statusEl.textContent = '';
        showShipmentAddResult({ success: false, message: 'Something went wrong. ' + err.message });
      })
      .finally(() => {
        btn.disabled = false;
      });
    });

    document.querySelectorAll('[data-shipment-add-close]').forEach(el => {
      el.addEventListener('click', closeShipmentAddModal);
    });

    function showModal(title, contentHtml) {
      const existing = document.getElementById('retry-response-modal');
      if (existing) existing.remove();

      const modal = document.createElement('div');
      modal.id = 'retry-response-modal';
      modal.className = 'fixed inset-0 z-50 flex items-center justify-center';
      modal.innerHTML = `
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative w-full max-w-2xl max-h-[90vh] overflow-auto bg-white rounded-lg shadow-lg">
          <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
            <h2 class="text-lg font-semibold">${title}</h2>
            <button class="text-gray-600 hover:text-gray-900 text-2xl leading-none close-modal-btn" type="button" aria-label="Close">&times;</button>
          </div>
          <div class="p-4 text-xs text-gray-800">${contentHtml}</div>
          <div class="px-4 py-3 border-t border-gray-200 flex justify-end gap-2 bg-gray-50 rounded-b">
            <button class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-4 py-2 rounded text-sm close-modal-btn">Close</button>
            <button class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2 rounded text-sm close-reload-btn">Close and Reload</button>
          </div>
        </div>
      `;

      document.body.appendChild(modal);

      const closeBtns = modal.querySelectorAll('.close-modal-btn');
      const reloadBtn = modal.querySelector('.close-reload-btn');
      const backdrop = modal.querySelector('.absolute');
      
      const closeModal = () => modal.remove();
      const closeAndReload = () => {
        modal.remove();
        location.reload();
      };

      closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
      if (reloadBtn) reloadBtn.addEventListener('click', closeAndReload);
      if (backdrop) backdrop.addEventListener('click', closeModal);
    }

    document.addEventListener('click', function(event) {
      if (!event.target.closest('.relative')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
          menu.classList.add('hidden');
        });
      }
    });
    function cancelDispatchAjax(invoiceId) {
      customConfirm('Are you sure you want to cancel? This action cannot be undone.').then(confirmed => {
        if (confirmed) {
          fetch('?page=dispatch&action=cancel_dispatch', {
              method: 'POST',
              headers: {'Content-Type': 'application/json'},
              body: JSON.stringify({ invoice_id: invoiceId })
          })
          .then(res => {
              if (!res.ok) throw new Error('Network response was not ok');
              return res.text();
          })
          .then(text => {
              console.log('Cancel dispatch response:', text);
              try {
                  const data = JSON.parse(text);
                  if (data.success) {
                      showAlert('Cancel initiated successfully. Reloading...', 'success');
                      location.reload();
                  } else {
                      showAlert('Error: ' + (data.message || 'Failed to cancel dispatch'), 'error');
                  }
              } catch (e) {
                  console.error('JSON Parse Error:', e);
                  console.error('Response text:', text);
                  alert('Error: Invalid response from server');
              }
          })
          .catch(err => {
              console.error(err);
              alert('Error canceling dispatch');
          });
        }
      });
    }
    function updateStatusAjax(invoiceId) {
      fetch('?page=dispatch&action=bulk_update_status', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ invoice_ids: [invoiceId] })
      })
      .then(res => res.json())
      .then(data => {
          if (data.status === 'success') {
              showAlert('Status updated successfully. Reloading...', 'success');
              setTimeout(() => location.reload(), 3000);              
          } else {
              alert('Error: ' + (data.message || 'Failed to update status'));
          }
      })
      .catch(err => {
          console.error(err);
          alert('Error updating status');
      });
    }
    function reDispatchAjax(invoiceId) {
      //if (!confirm('Re-dispatch will attempt to create a new shipment for this invoice. Proceed?')) return;
      customConfirm('Re-dispatch will attempt to create a new shipment for this invoice. Proceed?').then(confirmed => {
        if (confirmed) {
            fetch('?page=dispatch&action=re_dispatch_invoice', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ invoice_id: invoiceId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('Re-dispatch initiated successfully. Reloading...', 'success');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to re-dispatch'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error re-dispatching');
            });
      
        }
      });
  }
  function cancelInvoiceAjax(invoiceId) {
    customConfirm(
        'Cancelling this invoice will also cancel the associated Dispatch.This action cannot be undone. Are you sure you want to continue?',
        { okText: 'Confirm Cancellation' }
    ).then(confirmed => {
      if (confirmed) {
        fetch('?page=dispatch&action=cancel_invoice', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ invoice_id: invoiceId })
        })
        .then(async res => {
            const text = await res.text();
            try {
                return text ? JSON.parse(text) : null;
            } catch (parseErr) {
                console.error('Cancel invoice JSON parse failed:', text);
                throw new Error('Invalid server response');
            }
        })
        .then(data => {
            if (!data) {
                alert('Error: Empty server response');
                return;
            }
            if (data.success) {
                showAlert('Invoice cancellation initiated successfully. Reloading...', 'success');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to cancel invoice'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error canceling invoice');
        });
      }
    });
  }
    
</script>