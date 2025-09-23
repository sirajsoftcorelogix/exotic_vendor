<?php
// echo "<pre>";
// print_r($purchaseOrder);
?>
<div class="container mx-auto p-6 bg-white rounded-lg shadow-md">
  <div class="flex justify-between items-center">
    <h2 class="po-title">Purchase Order: <?= ($purchaseOrder['status'] == 'draft') ? 'DRAFT' : htmlspecialchars($purchaseOrder['po_number'] ?? '') ?></h2>
    <a href="?page=purchase_orders&action=edit&po_id=<?= htmlspecialchars($purchaseOrder['id'] ?? '') ?>"><button class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Edit</button></a>
  </div>
  <hr class="my-8">
  <div class="flex justify-between mb-8">
    <!-- Left Column -->
    <div class="space-y-2">
      <div class="flex items-center">
        <label for="vendor" class="block text-gray-700 form-label">Vendor :</label>
        <?php echo $purchaseOrder['vendor_name'] ?? '' ?>
      </div>
      
      <div class="flex items-center">
        <label for="delivery-address" class="block text-gray-700 form-label">Delivery Address :</label>
        <?= $purchaseOrder['delivery_address'] ?? '' ?>
      </div>
      <div class="flex items-center">
        <label for="po-status" class="block text-gray-700 form-label">PO Status :</label>
        <div class="px-2 py-1 rounded-md font-semibold <?= $purchaseOrder['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : ($purchaseOrder['status'] == 'approved' ? 'bg-green-100 text-green-800' : ($purchaseOrder['status'] == 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) ?>">
          <?= htmlspecialchars(ucfirst($purchaseOrder['status'])) ?>
        </div>
      </div>
    </div>

    <!-- Right Column -->
    <div class="space-y-2">
      <div class="flex items-center">
        <label for="delivery-due-date" class="block text-gray-700 form-label">Delivery Due Date :</label>
        
          <?= htmlspecialchars(date('d M, Y', strtotime($purchaseOrder['expected_delivery_date']))) ?>
       
      </div>
      <!-- <div class="flex items-center">
        <label for="order-id" class="block text-gray-700 form-label">Order ID</label>
        <input readonly type="text" name="order-id" id="order-id" placeholder="2142086" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md bg-white form-input px-3 placeholder-gray-400 w-[150px]">
      </div> -->
      <div class="flex items-center">
        <label for="employee-name" class="block text-gray-700 form-label">Employee Name</label>
        <?= htmlspecialchars($purchaseOrder['user_name'] ?? '') ?>
      </div>
    </div>
  </div>

  <!-- Item Table -->
  <div class="bg-[rgba(245,245,245,1)] p-4 rounded-lg">
    <div class="space-y-1.5">
      <!-- Table Header -->
      <div class="grid grid-cols-12 gap-4 px-4 py-2 table-header bg-[rgba(245,245,245,1)] rounded-md">
        <div class="col-span-1">S.No</div>
        <div class="col-span-3">Item Summary</div>
        <div class="col-span-1">HSN</div>
        <div class="col-span-1">Image</div>
        <div class="col-span-1">GST&</div>
        <div class="col-span-1">Quantity</div>
        <div class="col-span-1">Unit</div>
        <div class="col-span-2">Rate</div>
        <div class="col-span-1">Amount</div>
      </div>
      <!-- Table Rows -->
    <?php     
    foreach ($items as $index => $item): ?>
      <div class="bg-white rounded-lg p-4 grid grid-cols-12 gap-4 items-center table-row-text">
        <div class="col-span-1"><?= $index + 1 ?></div>
        <div class="col-span-3"><?= htmlspecialchars($item['title']) ?></div>
        <div class="col-span-1"><?= htmlspecialchars($item['hsn']) ?></div>
        <div class="col-span-1"><img src="<?= isset($item['image']) ? $item['image'] : '' ?>" class="rounded-lg" onerror="this.onerror=null;this.src='https://placehold.co/56x88/cccccc/ffffff?text=Image';"></div>
        <div class="col-span-1"><?= htmlspecialchars($item['gst']) ?>%</div>
        <div class="col-span-1">
          <input type="text" value="<?= htmlspecialchars($item['quantity']) ?>" class="w-[50px] h-[25px] text-center border rounded-md focus:ring-0 form-input" readonly>
        </div>
        <div class="col-span-1">Nos</div>
        <div class="col-span-2">
          <input type="text" value="<?= htmlspecialchars($item['price']) ?>" class="w-[105px] h-[25px] text-center border rounded-md focus:ring-0 form-input" readonly>
        </div>
        <div class="col-span-1">₹<?= htmlspecialchars($item['amount']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Add Item Button and Totals -->
  <div class="mt-4 flex justify-between items-start">
    <!-- Add Item Button -->
    <div>
      <!-- <button class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Add Item</button> -->
    </div>
    <!-- Totals Section -->
    <div class="w-1/3">
      <div class="bg-[rgba(245,245,245,1)] p-4 rounded-lg">
        <div class="space-y-1">
          <div class="flex justify-between subtotal-text">
            <span>Subtotal :</span>
            <span>₹<?= $purchaseOrder['subtotal'] ?></span>
          </div>
          <div class="flex justify-between subtotal-text">
            <span>Shipping :</span>
            <span>₹<?= $purchaseOrder['shipping_cost'] ?></span>
          </div>
          <div class="flex justify-between subtotal-text">
            <span>GST :</span>
            <span>₹<?= $purchaseOrder['total_gst'] ?></span>
          </div>
        </div>
        <div class="mt-1 border-t border-gray-300 pt-1">
          <div class="flex justify-between final-total-text">
            <span>Grand Total :</span>
            <span>₹<?= $purchaseOrder['total_cost'] ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <hr class="my-8 border-gray-200">

  <!-- Notes and Terms -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div>
      <div class="flex justify-between items-center mb-1" style="height: 37px;">
        <label for="notes" class="block text-sm font-medium text-gray-700 notes-label">Add Note:</label>
      </div>
      <textarea readonly id="notes" name="notes" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" placeholder="Important note to remember" style="min-height: 148px;"><?= $purchaseOrder['notes'] ?></textarea>
    </div>
    <div>
      <div class="flex justify-between items-center mb-1">
        <label for="terms" class="block text-sm font-medium text-gray-700 notes-label">Terms & Conditions:</label>
        <!-- <button class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Load Template</button> -->
      </div>
      <textarea readonly id="terms" name="terms" class="mt-5 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" placeholder="Important terms & conditions to remember" style="min-height: 148px;"><?php echo $purchaseOrder['terms_and_conditions']; ?></textarea>
    </div>
  </div>

  <!-- Action Buttons -->
  <div class="mt-8 flex justify-end space-x-4">
    <!-- <button class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Preview</button> -->
    <a href="<?= base_url('?page=purchase_orders&action=list') ?>"> <button class="bg-black text-white font-semibold py-2 px-4 rounded-md action-button">Cancel</button></a>
  </div>
  <hr class="my-8 border-gray-200"> 
  <!-- Order Tracking Timeline -->
  <div class="w-full">
    <h2 class="timeline-title mb-8">Order Tracking:</h2>
    <div class="grid grid-cols-8">
      <!-- Step 1: Approved -->
      <div class="timeline-step completed">
        <div class="flex flex-col items-center text-center">
          <div class="relative w-full h-5 flex justify-center items-center">
            <div class="w-[18px] h-[18px] rounded-full bg-[rgba(39,174,96,1)] z-10"></div>
          </div>
          <p class="timeline-text mt-2">Created</p>
          <p class="timeline-date"><?php echo date('d M, Y', strtotime($purchaseOrder['created_at'])); ?></p>
        </div>
      </div>
      <!-- status log -->
      <?php if (!empty($status_log)) {
        foreach ($status_log as $log) { ?>
          <div class="timeline-step completed">
            <div class="flex flex-col items-center text-center">
              <div class="relative w-full h-5 flex justify-center items-center">
                <div class="w-[18px] h-[18px] rounded-full bg-[rgba(39,174,96,1)] z-10"></div>
              </div>
              <p class="timeline-text mt-2"><?php echo ucfirst($log['status']); ?></p>
              <p class="timeline-date"><?php echo date('d M, Y', strtotime($log['change_date'])); ?></p>
            </div>
          </div>
        <?php }
      } ?>
      <!-- Step 2: Sent to the Vendor -->
      <!-- <div class="timeline-step completed">
        <div class="flex flex-col items-center text-center">
          <div class="relative w-full h-5 flex justify-center items-center">
            <div class="w-[18px] h-[18px] rounded-full bg-[rgba(39,174,96,1)] z-10"></div>
          </div>
          <p class="timeline-text mt-2"><?php //echo ucfirst($purchaseOrder['status']); ?></p>
          <p class="timeline-date"><?php //echo date('d M, Y', strtotime($purchaseOrder['updated_at'])); ?></p>
        </div>
      </div> -->
      <!-- Step 3: In Production -->
      <!-- <div class="timeline-step completed">
        <div class="flex flex-col items-center text-center">
          <div class="relative w-full h-5 flex justify-center items-center">
            <div class="w-[18px] h-[18px] rounded-full bg-[rgba(39,174,96,1)] z-10"></div>
          </div>
          <p class="timeline-text mt-2">In Production</p>
          <p class="timeline-date">3 May, 2025</p>
        </div>
      </div> -->
      <!-- Step 4: Shipped -->
      <!-- <div class="timeline-step">
        <div class="flex flex-col items-center text-center">
          <div class="relative w-full h-5 flex justify-center items-center">
            <div class="w-[12px] h-[12px] rounded-full bg-[rgba(186,186,186,1)] z-10"></div>
          </div>
          <p class="timeline-text mt-2 text-gray-400">Shipped</p>
          <p class="timeline-date text-gray-400">6 May, 2025</p>
        </div>
      </div> -->
      
    </div>
  </div>

  <!-- invoice section -->
  <hr class="my-8 border-gray-200">
  <div class="space-y-1.5">
    <div>
      <h2 class="invoice-title mb-4">Invoices:</h2>
      <?php 
      
      if (empty($invoice)) { ?>
        <p class="text-gray-600">No invoices found for this purchase order.</p>
      <?php } else { ?>
        <div class="bg-[rgba(245,245,245,1)] p-6 rounded-lg grid grid-cols-8 gap-x-8 gap-y-2">
            <div class="col-span-2">
              <h4 class="invoice-header">Invoice Number</h4>
              <p class="invoice-text mt-2"><?php echo $invoice['invoice_no']; ?></p>
            </div>
          <div class="col-span-2">
            <h4 class="invoice-header">Invoice Date</h4>
            <p class="invoice-text mt-2"><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></p>
          </div>
          <!-- <div class="col-span-2">
            <h4 class="invoice-header">Due Date</h4>
            <p class="invoice-text mt-2"><?php echo $invoice['due_date']; ?></p>
          </div> -->
          <div class="col-span-2">
            <h4 class="invoice-header">Total Amount</h4>
            <p class="invoice-text mt-2">₹<?php echo $invoice['grand_total']; ?></p>
          </div>            
        
        <?php //extension
        $iconClass = '';
        $inv_name = basename($invoice['invoice'], "/");
        if (!empty($invoice['invoice'])) {
          $file_extension = pathinfo($invoice['invoice'], PATHINFO_EXTENSION);
          switch (strtolower($file_extension)) {
              case 'pdf':
                  $iconClass = 'fa-file-pdf';
                  break;             
              case 'png':
              case 'jpg':
              case 'jpeg':
              case 'gif':
                  $iconClass = 'fa-file-image';
                  break;
              default:
                  $iconClass = 'fa-file'; // Generic file icon
          }          
        } 
        ?>
        <div class="col-span-2 ">
            <!-- <p class="amount-box-text"> <i class="fas <?php //echo $iconClass ?> text-2xl text-gray-600"></i></p> -->
            <!-- <p class="amount-box-text"><a href="<?php //echo $invoice['invoice']; ?>" target="_blank"><?php //echo $inv_name; ?></a></p> -->
        <a href="<?php echo $invoice['invoice']; ?>" download style="color: white;"><button class="bg-[rgba(208,103,6,1)] text-center text-white font-semibold py-1 px-4 rounded-md action-button" style="width: 220px;">
          <i class="fas <?php echo $iconClass ?> text-2xl text-white-600"></i> Download</button>
        </a>
          </div>
      <?php } ?>
      </div>
    </div>
  </div>

  <hr class="my-8 border-gray-200">

  <!-- Payment Details -->
  <div class="space-y-1.5">
    <div>
      <h3 class="payment-details-title mb-4">Payment Details:</h3>
      <div class="flex items-stretch">
        <div class="bg-[rgba(245,245,245,1)] p-6 rounded-lg grid grid-cols-10 gap-x-8 gap-y-2 flex-grow">
          <div class="col-span-2">
            <h4 class="payment-details-header">Payment Date</h4>
            <p class="payment-details-text mt-2">17 June 2025</p>
          </div>
          <div class="col-span-2">
            <h4 class="payment-details-header">Payment Type</h4>
            <p class="payment-details-text mt-2">Advance</p>
          </div>
          <div class="col-span-2">
            <h4 class="payment-details-header">UTR No.</h4>
            <p class="payment-details-text mt-2">123456789012345</p>
          </div>
          <div class="col-span-2">
            <h4 class="payment-details-header">Bank Details</h4>
            <p class="payment-details-text mt-2">SBI Bank</p>
          </div>
          <div class="col-span-2">
            <h4 class="payment-details-header">Account Number</h4>
            <p class="payment-details-text mt-2">3153553245325</p>
          </div>
          <div class="col-span-full">
            <h4 class="payment-details-header">Payment Note:</h4>
            <p class="payment-details-text mt-2">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s</p>
          </div>
        </div>
        <div class="bg-[rgba(208,103,6,1)] text-white p-4 rounded-lg text-center ml-2 flex flex-col justify-center w-2/12">
          <p class="amount-box-text">Amount</p>
          <p class="amount-box-text">₹125485</p>
        </div>
      </div>
    </div>
    <div class="flex items-stretch">
      <div class="bg-[rgba(245,245,245,1)] p-6 rounded-lg grid grid-cols-10 gap-x-8 gap-y-2 flex-grow">
        <div class="col-span-2">
          <h4 class="payment-details-header">Payment Date</h4>
          <p class="payment-details-text mt-2">17 June 2025</p>
        </div>
        <div class="col-span-2">
          <h4 class="payment-details-header">Payment Type</h4>
          <p class="payment-details-text mt-2">Advance</p>
        </div>
        <div class="col-span-2">
          <h4 class="payment-details-header">UTR No.</h4>
          <p class="payment-details-text mt-2">123456789012345</p>
        </div>
        <div class="col-span-2">
          <h4 class="payment-details-header">Bank Details</h4>
          <p class="payment-details-text mt-2">SBI Bank</p>
        </div>
        <div class="col-span-2">
          <h4 class="payment-details-header">Account Number</h4>
          <p class="payment-details-text mt-2">3153553245325</p>
        </div>
        <div class="col-span-full">
          <h4 class="payment-details-header">Payment Note:</h4>
          <p class="payment-details-text mt-2">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s</p>
        </div>
      </div>
      <div class="bg-[rgba(208,103,6,1)] text-white p-4 rounded-lg text-center ml-2 flex flex-col justify-center w-2/12">
        <p class="amount-box-text">Amount</p>
        <p class="amount-box-text">₹125485</p>
      </div>
    </div>
  </div>
  <div class="mt-8 flex justify-end space-x-4">
    <button class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button" style="width: 220px;">Pending Amount : ₹279318</button>
    <button class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">+ Add Payment</button>
  </div>
  <hr class="my-8 border-gray-200">

  
  <div class="mt-8 flex justify-end space-x-4">
    <!-- <button class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Save</button> -->
    <a href="<?php echo isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : base_url('?page=purchase_orders&action=list'); ?>"> <button class="bg-black text-white font-semibold py-2 px-4 rounded-md action-button">Back</button></a>
  </div>
</div>
