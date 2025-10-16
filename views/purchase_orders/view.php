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
      <!-- Totals Section -->
      <div class="flex-grow"></div>
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
  </div>
  <hr class="my-8 border-gray-200"> 
  <!-- Order Tracking Timeline -->
  <div class="w-full">
    <h2 class="timeline-title mb-8">Order Tracking:</h2>
    <div class="grid grid-cols-8 bg-[rgba(245,245,245,1)] p-6 rounded-lg grid grid-cols-8 gap-y-2">
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
      
    </div>
  </div>

  <!-- invoice section -->
  <hr class="my-8 border-gray-200">
  <div class="space-y-1.5">
    <div>
      <h2 class="timeline-title mb-4"><?php echo $invoice['invoice_type'] === 'performa' ? 'Proforma Invoices:' : 'Invoices:'; ?></h2>
      <?php 
      
      if (empty($invoice)) { ?>
        <p class="text-gray-600">No invoices found for this purchase order.</p>
          <?php } else {
            if (!empty($invoice['invoice'])) {
            ?>
            <div class="bg-[rgba(245,245,245,1)] p-6 rounded-lg grid grid-cols-8 gap-x-8 gap-y-2">
                <div class="col-span-2">
                  <h4 class="payment-details-header">Invoice Number</h4>
                  <p class="payment-details-text mt-2"><?php echo $invoice['invoice_no']; ?></p>
                </div>
              <div class="col-span-2">
                <h4 class="payment-details-header">Invoice Date</h4>
                <p class="payment-details-text mt-2"><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></p>
              </div>
              
              <div class="col-span-2">
                <h4 class="payment-details-header">Total Amount</h4>
                <p class="payment-details-text mt-2">₹<?php echo $invoice['grand_total']; ?></p>
              </div>            
              <div class="col-span-1">
                <h4 class="payment-details-header">GST</h4>
                <p class="payment-details-text mt-2"><?php echo $invoice['gst_total']; ?></p>
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
            <div class="col-span-1 flex flex-col items-center justify-center">
              <!-- <p class="amount-box-text"> <i class="fas <?php //echo $iconClass ?> text-2xl text-gray-600"></i></p> -->
              <!-- <p class="amount-box-text"><a href="<?php //echo $invoice['invoice']; ?>" target="_blank"><?php //echo $inv_name; ?></a></p> -->
              <!-- <a href="<?php //echo $invoice['invoice']; ?>" download style="color: white;"><button class="bg-[rgba(208,103,6,1)] text-center text-white font-semibold py-1 px-4 rounded-md action-button mb-2" >
                <i class="fas <?php //echo $iconClass ?> text-2xl text-white-600"></i> Download</button>
              </a> -->
              <!-- <button id="open-payment-popup-btn" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">+ Add Payment</button> -->
              <!-- <button id="open-challan-popup-btn" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">+ Add Delivery Challan</button> -->
              <div class="menu-wrapper inline-block relative">
                  <button class="menu-button text-gray-500 hover:text-gray-700 font-semibold" onclick="toggleMenu(this)">
                  <i class="fas fa-cog"></i> Action<!-- &#x22EE;  Vertical ellipsis -->
                  </button>
                  <ul class="menu-popup text-left" style="min-width: 220px;">
                      <li><a href="<?php echo $invoice['invoice']; ?>" download ><i class="fas fa-download <?php //echo $iconClass ?> text-2xl text-gray-600"></i> Download Invoice</a></li>
                      <li id="open-payment-popup-btn"><i class="fas fa-money-bill-wave text-2xl text-gray-600"></i> Add Payment</li>
                      <li id="open-challan-popup-btn"><i class="fas fa-truck text-2xl text-gray-600"></i> Add Delivery Challan</li>
                      <!-- <li ><a href="#" id="open-payment-popup-btn" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">+ Add Payment</a></li> -->
                  </ul> 
              </div>
            </div>
            <div class="col-span-2">
                <h4 class="payment-details-header">Shipping</h4>
                <p class="payment-details-text mt-2"><?php echo $invoice['shipping']; ?></p>
              </div>
              <div class="col-span-2">
                <h4 class="payment-details-header">Grand Total</h4>
                <p class="payment-details-text mt-2"><?php echo $invoice['grand_total']; ?></p>
              </div>
          </div>
          <?php } else if (!empty($invoice['performa'])) { ?>
            <div class="bg-[rgba(245,245,245,1)] p-6 rounded-lg grid grid-cols-8 gap-x-8 gap-y-2">
                <div class="col-span-2">
                  <h4 class="invoice-header">Proforma Number</h4>
                  <p class="invoice-text mt-2"><?php echo $invoice['invoice_no']; ?></p>
                </div>
              <div class="col-span-2">
                <h4 class="invoice-header">Proforma Date</h4>
                <p class="invoice-text mt-2"><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></p>
              </div>              
              <div class="col-span-2">
                <h4 class="invoice-header">Total Amount</h4>
                <p class="invoice-text mt-2">₹<?php echo $invoice['grand_total']; ?></p>
              </div>            
            
            <?php //extension
            $iconClass = '';
            $inv_name = basename($invoice['performa'], "/");
            if (!empty($invoice['performa'])) {
              $file_extension = pathinfo($invoice['performa'], PATHINFO_EXTENSION);
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
                <a href="<?php echo $invoice['performa']; ?>" download style="color: white;"><button class="bg-[rgba(208,103,6,1)] text-center text-white font-semibold py-1 px-4 rounded-md action-button mb-2" >
                  <i class="fas <?php echo $iconClass ?> text-2xl text-white-600"></i> Download</button>
                </a>
                <button id="open-payment-popup-btn" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">+ Add Payment</button>
                
            </div>
          </div>
          <?php }

        } ?>
        
      </div>
    </div>
  </div>
<div class="container mx-auto p-6 bg-white rounded-lg shadow-md mt-8">
  <!-- <hr class="my-8 border-gray-200"> -->

  <!-- Payment Details -->
  <div class="space-y-1.5">    
    <h3 class="payment-details-title mb-4">Payment Details:</h3>    
    <?php foreach($payment as $paymentDetails) { ?>     
    <div class="flex items-stretch mb-4">
      <div class="bg-[rgba(245,245,245,1)] p-6 rounded-lg grid grid-cols-10 gap-x-8 gap-y-2 flex-grow">
        <div class="col-span-2">
          <h4 class="payment-details-header">Payment Date</h4>
          <p class="payment-details-text mt-2"><?php echo $paymentDetails['payment_date']; ?></p>
        </div>
        <div class="col-span-2">
          <h4 class="payment-details-header">Payment Type</h4>
          <p class="payment-details-text mt-2"><?php echo $paymentDetails['payment_type']; ?></p>
        </div>
        <div class="col-span-2">
          <h4 class="payment-details-header">UTR No.</h4>
          <p class="payment-details-text mt-2"><?php echo $paymentDetails['bank_transaction_reference_no']; ?></p>
        </div>
        <div class="col-span-2">
          <h4 class="payment-details-header">Bank Details</h4>
          <p class="payment-details-text mt-2"><?php echo $paymentDetails['vendor_bank_name']; ?></p>
        </div>
        <div class="col-span-2">
          <h4 class="payment-details-header">Account Number</h4>
          <p class="payment-details-text mt-2"><?php echo $paymentDetails['vendor_bank_account_number']; ?></p>
        </div>
        <div class="col-span-full">
          <h4 class="payment-details-header">Payment Note:</h4>
          <p class="payment-details-text mt-2"><?php echo $paymentDetails['payment_note']; ?></p>
        </div>
      </div>
      <div class="bg-[rgba(245,245,245,1)] p-6 rounded-lg grid p-4 rounded-lg text-center flex flex-col justify-center w-2/12">
        <div class="flex flex-col justify-center bg-[rgba(208,103,6,1)] p-4 rounded-lg"> 
        <p class="amount-box-text">Amount</p>
        <p class="amount-box-text">₹<?php echo number_format($paymentDetails['amount_paid'], 2); ?></p>
        </div>
        <div class="mt-4 flex justify-center space-x-2">
          <button type="button" id="editPayment<?php echo $paymentDetails['id']; ?>" class="edit-payment text-gray-500 hover:text-red-700 " title="Edit Payment"><span class="text-lg"><i class="fa fa-edit"></i></span></button>
          <button type="button" id="removePayment<?php echo $paymentDetails['id']; ?>" class="remove-payment text-gray-500 hover:text-red-700" title="Remove Payment"> <span class="text-lg"><i class="fa fa-trash-alt"></i></span> </button>
        </div>
      </div>
    </div>
    <?php } ?>
    <div class="mt-8 flex justify-end space-x-4">
      <span class="bg-red-100 text-red-800 border font-semibold py-2 px-4 rounded-md action-button" style="width: 220px;">Pending Amount : ₹<?php echo $purchaseOrder['total_cost'] - $data['total_amount_paid']; ?></span>      
    </div>
  </div>
</div>
    <!--Challan Details-->
<div class="container mx-auto p-6 bg-white rounded-lg shadow-md mt-8">
  <div class="space-y-1.5">    
    <h3 class="payment-details-title mb-4">Delivery Challan Details:</h3>    
    <?php //foreach($challan as $challanDetails) { 
    if (!empty($challan)) {
      
    ?>     
    <div class="flex items-stretch mb-4">
      <div class="bg-[rgba(245,245,245,1)] p-6 rounded-lg grid grid-cols-10 gap-x-8 gap-y-2 flex-grow">
        <div class="col-span-2">
          <h4 class="payment-details-header">Delivery Challan Number</h4>
          <p class="payment-details-text mt-2"><?php echo $challan['delivery_challan_no']; ?></p>
        </div>
        <div class="col-span-2">
          <h4 class="payment-details-header">Delivery Challan Date</h4>
          <p class="payment-details-text mt-2"><?php echo date('d M Y', strtotime($challan['delivery_challan_date'])); ?></p>
        </div>        
        <div class="col-span-2">
          <h4 class="payment-details-header">Mode of Transport</h4>
          <p class="payment-details-text mt-2"><?php echo $challan['mode_of_transport']; ?></p>
        </div>
        <div class="col-span-2">
          <h4 class="payment-details-header">Vehicle Number</h4>
          <p class="payment-details-text mt-2"><?php echo $challan['vehicle_no']; ?></p>
        </div>
        <div class="col-span-1">
          <h4 class="payment-details-header">Challan</h4>
          <p class="payment-details-text mt-2"><a href="<?php echo base_url($challan['delivery_challan_copy']); ?>" target="_blank" class="text-blue-700 hover:underline"><i class="fa fa-file"></i> View</a></p>
        </div>
        <div class="col-span-1">
          <div class="mt-4 flex justify-center space-x-2">
            <button type="button" id="editChallan<?php echo $challan['id']; ?>" class="edit-challan text-gray-500 hover:text-red-700 " title="Edit Challan"><span class="text-lg"><i class="fa fa-edit"></i></span></button>
            <button type="button" id="removeChallan<?php echo $challan['id']; ?>" class="remove-challan text-gray-500 hover:text-red-700" title="Remove Challan"> <span class="text-lg"><i class="fa fa-trash-alt"></i></span> </button>
          </div>
        </div>
      </div>
    </div>
    <?php } ?>
  </div>
</div>
<hr class="my-8 border-gray-200">


<div class="mt-8 flex justify-end space-x-4">    
  <a href="<?php echo isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : base_url('?page=purchase_orders&action=list'); ?>"> <button class="bg-black text-white font-semibold py-2 px-4 rounded-md action-button">Back</button></a>
</div>



<!-- add payment details popup -->
<div id="payment-popup" class="hidden">
    <!-- Background Overlay -->
    <!-- <div id="popup-overlay" class="fixed inset-0 bg-black bg-opacity-25 z-40"></div> -->

    <!-- Sliding Container -->
    <div id="payment-modal-slider" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: calc(45% + 61px); min-width: 661px;">

        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-payment-popup-btn" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Popup Panel -->
        <div id="payment-popup-panel" class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Add / Edit Payment Details</h2>

                    <div id="payment-error" class="hidden mb-4">
                        <p class="text-red-600 text-sm"></p>
                    </div>
                    <form id="payment-form" >
                      <h5 class="font-bold text-gray-800 mb-4 pb-4 border-b">Basic payment details</h5>
                      
                        <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">                          
                            <div>
                                <label for="invoice_id" class="text-sm font-medium text-gray-700">Invoice:<i class="text-red-500">*</i></label>
                                <select id="invoice_id" name="invoice_id" class="form-input w-full bg-white mt-1">
                                    <!-- <option value="" disabled selected>Select Invoice</option> -->
                                    <?php
                                    //print_r($invoice);
                                    //foreach ($invoice as $inv): ?>
                                        <option value="<?= htmlspecialchars($invoice['id'] ?? '') ?>"><?= htmlspecialchars($invoice['invoice_no'] ?? '') ?></option>
                                    <?php //endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="payment_date" class="text-sm font-medium text-gray-700">Payment Date:<i class="text-red-500">*</i></label>
                                <input type="date" id="payment_date" name="payment_date" class="form-input w-full mt-1" max="<?= date('Y-m-d') ?>">
                            </div>
                            <div>
                                <label for="payment_mode" class="text-sm font-medium text-gray-700">Payment Mode:<i class="text-red-500">*</i></label>
                                <select id="payment_mode" name="payment_mode" class="form-input w-full bg-white mt-1">
                                    <option value="" disabled selected>Select mode</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="debit_card">Debit Card</option>
                                    <option value="cash">Cash</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div>
                                <label for="payment_type" class="text-sm font-medium text-gray-700">Payment Type:<i class="text-red-500">*</i></label>
                                <select id="payment_type" name="payment_type" class="form-input w-full bg-white mt-1">
                                    <option value="" disabled selected>Select type</option>
                                    <option value="full">Full</option>
                                    <option value="partial">Partial</option>
                                    <option value="advance">Advance</option>
                                    <option value="final">Final</option>
                                    <option value="refund">Refund</option>
                                    <option value="credit_note">Credit Note</option>
                                </select>
                            </div>
                            <div>
                                <label for="amount_paid" class="text-sm font-medium text-gray-700">Amount Paid ₹:<i class="text-red-500">*</i></label>
                                <input type="number" id="amount_paid" name="amount_paid" class="form-input w-full mt-1" step="0.01" placeholder="INR">
                            </div>
                            <div>
                                <label for="balance_due" class="text-sm font-medium text-gray-700">Balance Due ₹:</label>
                                <input type="text" id="balance_due" name="balance_due" value="<?php echo $purchaseOrder['total_cost'] - $data['total_amount_paid']; ?>" class="form-input w-full mt-1 bg-gray-100" readonly>
                            </div>
                        </div>  
                        <h5 class="font-bold text-gray-800 mb-4 pb-4 border-b">Bank Details</h5><?php //print_array($vendor_bank);?>
                        <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
                            <div>
                                <label for="vendor_bank_account_name" class="text-sm font-medium text-gray-700">Vendor Bank Account Name:</label>
                                <input type="text" id="vendor_bank_account_name" value="<?php echo $vendor_bank['account_name'] ?? ''; ?>" name="vendor_bank_account_name" class="form-input w-full mt-1 bg-gray-100" readonly>
                            </div>
                            <div>
                                <label for="vendor_bank_account_number" class="text-sm font-medium text-gray-700">Vendor Bank Account Number:</label>
                                <input type="text" id="vendor_bank_account_number" value="<?php echo $vendor_bank['account_number'] ?? ''; ?>" name="vendor_bank_account_number" class="form-input w-full mt-1 bg-gray-100" readonly>
                            </div>
                            <div>
                                <label for="vendor_bank_ifsc_code" class="text-sm font-medium text-gray-700">Vendor Bank IFSC Code:</label>
                                <input type="text" id="vendor_bank_ifsc_code" value="<?php echo $vendor_bank['ifsc_code'] ?? ''; ?>" name="vendor_bank_ifsc_code" class="form-input w-full mt-1 bg-gray-100" maxlength="11" readonly>
                            </div>
                            <div>
                                <label for="vendor_branch_name" class="text-sm font-medium text-gray-700">Vendor Bank Branch Name:</label>
                                <input type="text" id="vendor_branch_name" value="<?php echo $vendor_bank['branch_name'] ?? ''; ?>" name="vendor_branch_name" class="form-input w-full mt-1 bg-gray-100" maxlength="11" readonly>
                            </div>
                            <div>
                                <label for="vendor_bank_name" class="text-sm font-medium text-gray-700">Vendor Bank Name:</label>
                                <input type="text" id="vendor_bank_name" name="vendor_bank_name" value="<?php echo $vendor_bank['bank_name'] ?? ''; ?>" class="form-input w-full mt-1 bg-gray-100" maxlength="100" readonly>
                            </div>
                            <div>
                                <label for="bank_transaction_reference_no" class="text-sm font-medium text-gray-700">Bank Transaction Reference No. (UTR):<i class="text-red-500">*</i></label>
                                <input type="text" id="bank_transaction_reference_no" name="bank_transaction_reference_no" class="form-input w-full mt-1" maxlength="25" placeholder="UTR Number">
                            </div>
                        </div>
                        <h5 class="font-bold text-gray-800 mb-4 pb-4 border-b">Additional Information</h5>
                        <div class="mb-6">
                            <label for="payment_note" class="text-sm font-medium text-gray-700">Payment Note:</label>
                            <textarea id="payment_note" name="payment_note" class="form-input w-full h-24 mt-1 p-2" rows="4" maxlength="500" placeholder="Add any additional notes regarding the payment"></textarea>
                        </div>                      

                        <div class="flex justify-end items-center gap-4 pt-6 border-t">
                            <input type="hidden" id="payment-po-id" name="po_id" value="<?= $purchaseOrder['id'] ?? '' ?>">
                            <input type="hidden" id="payment-vendor-id" name="vendor_id" value="<?= $purchaseOrder['vendor_id'] ?? '' ?>">

                            <input type="hidden" id="payment-id" name="id" >
                            <button type="button" id="cancel-payment-btn" class="action-btn cancel-btn">Cancel</button>
                            <button type="submit" id="submit-payment-btn" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-btn">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- add delivery challan popup -->
<div id="delivery-challan-popup" class="hidden">
    <!-- Background Overlay -->
    <!-- <div id="popup-overlay" class="fixed inset-0 bg-black bg-opacity-25 z-40"></div> -->

    <!-- Sliding Container -->
    <div id="challan-modal-slider" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: calc(45% + 61px); min-width: 661px;">

        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-challan-popup-btn" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Popup Panel -->
        <div id="challan-popup-panel" class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Add / Edit Challan Details</h2>

                    <div id="challan-error" class="hidden mb-4">
                        <p class="text-red-600 text-sm"></p>
                    </div>
                    <form id="challan-form" enctype="multipart/form-data">
                      <!-- <h5 class="font-bold text-gray-800 mb-4 pb-4 border-b">Challan Details</h5> -->
                      <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
                        <div>
                            <label for="po_no" class="text-sm font-medium text-gray-700">PO Number:<i class="text-red-500">*</i></label>
                            <input type="text" id="po_no" name="po_id" class="form-input w-full mt-1" value="<?= $purchaseOrder['po_number'] ?? '' ?>" readonly>
                        </div>
                        <div>
                            <label for="challan_invoice_id" class="text-sm font-medium text-gray-700">Invoice No:<i class="text-red-500">*</i></label>
                            <select id="challan_invoice_id" name="invoice_id" class="form-input w-full bg-white mt-1">
                                <!-- <option value="" disabled selected>Select Invoice</option> -->
                                <?php
                                //print_r($invoice);
                                //foreach ($invoice as $inv): ?>
                                    <option value="<?= htmlspecialchars($invoice['id'] ?? '') ?>"><?= htmlspecialchars($invoice['invoice_no'] ?? '') ?></option>
                                <?php //endforeach; ?>
                            </select>
                        </div>  
                        <div>
                            <label for="delivery_challan_no" class="text-sm font-medium text-gray-700">Delivery Challan Number:<i class="text-red-500">*</i></label>
                            <input type="text" id="delivery_challan_no" name="delivery_challan_no" class="form-input w-full mt-1" placeholder="Enter Delivery Challan Number">
                        </div>  
                        <div>
                            <label for="delivery_challan_date" class="text-sm font-medium text-gray-700">Delivery Challan Date:<i class="text-red-500">*</i></label>
                            <input type="date" id="delivery_challan_date" name="delivery_challan_date" class="form-input w-full mt-1" max="<?= date('Y-m-d') ?>">
                        </div>
                        <div>
                            <label for="mode_of_transport" class="text-sm font-medium text-gray-700">Mode of Transport:<i class="text-red-500">*</i></label>
                            <input type="text" id="mode_of_transport" name="mode_of_transport" class="form-input w-full mt-1" placeholder="Enter Mode of Transport">
                        </div>
                        <div>
                            <label for="vehicle_no" class="text-sm font-medium text-gray-700">Vehicle No:<i class="text-red-500">*</i></label>
                            <input type="text" id="vehicle_no" name="vehicle_no" class="form-input w-full mt-1" placeholder="Enter Vehicle No">
                        </div>                        
                      </div>
                        <div>
                            <label for="transport_purpose" class="text-sm font-medium text-gray-700">Purpose of Transport:</label>
                            <textarea id="transport_purpose" name="transport_purpose" class="form-input w-full mt-1 h-24 p-2" rows="4" maxlength="500" placeholder="Enter Purpose of Transport"></textarea>
                        </div>                        
                        <div class="mb-6">
                            <label class="text-sm font-medium text-gray-700 mb-1 block"><span id="delivery-challan-pdf-label">Delivery Challan Copy: </span><span class="text-red-500">*</span></label>
                            <div id="file-drop-area" class="file-drop-area">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-500" id="file-drop-area-text">Drag & Drop your delivery challan file here or</p>
                                <button type="button" id="choose-file-btn" class="mt-2 bg-white border border-gray-300 text-gray-700 px-4 py-1 rounded-md text-sm hover:bg-gray-50">Choose file</button>
                                <input type="file" id="delivery_challan_copy" name="delivery_challan_copy" style="display:none;" accept=".pdf,.jpg,.jpeg,.png">
                                <p class="text-xs text-gray-400 mt-2">Only PDF, JPG, PNG</p>
                            </div>
                        </div>

                        <div id="uploaded-file-section" class="hidden mb-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-2" id="uploaded-file-label">Uploaded Delivery Challan Copy:</h3>
                            <div id="file-info" class="border rounded-md p-3 flex items-center justify-between">
                                <!-- File info will be injected here by JS -->
                            </div>
                        </div>
                        <div class="flex justify-end items-center gap-4 pt-6 border-t">
                            <input type="hidden" id="challan-po-id" name="po_id" value="<?= $purchaseOrder['id'] ?? '' ?>">
                            <input type="hidden" id="challan-vendor-id" name="vendor_id" value="<?= $purchaseOrder['vendor_id'] ?? '' ?>">

                            <input type="hidden" id="challan-id" name="id" >
                            <button type="button" id="cancel-challan-btn" class="action-btn cancel-btn">Cancel</button>
                            <button type="submit" id="submit-challan-btn" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-btn">Submit</button>
                        </div>
                      
                    </form>
                </div>
            </div>
        </div>
    </div>
   
</div>

<script>
 // Toggle menu visibility
function toggleMenu(button) {
  const popup = button.nextElementSibling;
  popup.style.display = popup.style.display === 'block' ? 'none' : 'block';

  // Close other open menus
  document.querySelectorAll('.menu-popup').forEach(menu => {
    if (menu !== popup) menu.style.display = 'none';
  });
}
// Close menu when clicking outside
document.addEventListener('click', function(event) {
  if (!event.target.closest('.menu-wrapper')) {
    document.querySelectorAll('.menu-popup').forEach(menu => {
      menu.style.display = 'none';
    });
  }
});
// Delivery Challan Popup Logic
document.addEventListener('DOMContentLoaded', function() {
  const openChallanPopupBtn = document.getElementById('open-challan-popup-btn');
  const closeChallanPopupBtn = document.getElementById('close-challan-popup-btn');
  const cancelChallanBtn = document.getElementById('cancel-challan-btn');
  const challanPopup = document.getElementById('delivery-challan-popup');
  const challanModalSlider = document.getElementById('challan-modal-slider');
  const challanForm = document.getElementById('challan-form');
  const challanError = document.getElementById('challan-error');
  const chooseFileBtn = document.getElementById('choose-file-btn');

  function openChallanPopup() {
    challanPopup.classList.remove('hidden');
    toggleMenu(document.querySelector('.menu-button')); // Close the menu if open
    setTimeout(() => {
      challanModalSlider.classList.remove('translate-x-full');
    }, 10);
  }

  function closeChallanPopup() {
    challanModalSlider.classList.add('translate-x-full');
    setTimeout(() => {
      challanPopup.classList.add('hidden');
      challanForm.reset();
      document.getElementById('challan-id').value = '';
    }, 300);
  }

  if (openChallanPopupBtn) {
    openChallanPopupBtn.addEventListener('click', openChallanPopup);
  }
  if (closeChallanPopupBtn) {
    closeChallanPopupBtn.addEventListener('click', closeChallanPopup);
  }
  if (cancelChallanBtn) {
    cancelChallanBtn.addEventListener('click', closeChallanPopup);
  }
  // Trigger file input when button is clicked
  chooseFileBtn.addEventListener('click', function() {
    document.getElementById('delivery_challan_copy').click();
  });
  // handle file input and drag-drop here if needed
  const deliveryChallanCopyInput = document.getElementById('delivery_challan_copy');
  const fileDropArea = document.getElementById('file-drop-area');

  // Handle file selection
  deliveryChallanCopyInput.addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
      // Show file name or preview
      document.getElementById('file-drop-area-text').textContent = file.name;
    }
  });

  // Handle drag-and-drop
  fileDropArea.addEventListener('dragover', function(event) {
    event.preventDefault();
    fileDropArea.classList.add('bg-gray-100');
  });

  fileDropArea.addEventListener('dragleave', function(event) {
    fileDropArea.classList.remove('bg-gray-100');
  });

  fileDropArea.addEventListener('drop', function(event) {
    event.preventDefault();
    fileDropArea.classList.remove('bg-gray-100');
    const files = event.dataTransfer.files;
    if (files.length > 0) {
      deliveryChallanCopyInput.files = files;
      document.getElementById('file-drop-area-text').textContent = files[0].name;
    }
  });

  // Handle form submission
  challanForm.addEventListener('submit', function(event) {
    event.preventDefault();
    challanError.classList.add('hidden');
    challanError.querySelector('p').textContent = '';

    const formData = new FormData(challanForm);

    fetch('<?= base_url("?page=purchase_orders&action=add_challan") ?>', {
      method: 'POST',
      body: formData
      // Do NOT set Content-Type header here!
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Challan added successfully!');
        closeChallanPopup();
        location.reload(); // Reload the page to reflect changes
      } else {
        challanError.classList.remove('hidden');
        challanError.querySelector('p').textContent = data.message || 'An error occurred. Please try again.';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      challanError.classList.remove('hidden');
      challanError.querySelector('p').textContent = 'An error occurred. Please try again.';
    });
  });  

  // challan edit and delete logic
  const editChallanBtn = document.getElementById('editChallan<?php echo $challan['id'] ?? ''; ?>');
  if (editChallanBtn) {
    editChallanBtn.addEventListener('click', function() {
      const challanId = this.id.replace('editChallan', '');
      fetch(`<?= base_url("?page=purchase_orders&action=get_challan&id=") ?>${challanId}`)
        .then(response => response.json())
        .then(data => {          
          if (data.success) {
            const challan = data.data[0];
            document.getElementById('challan-id').value = challan.id;
            document.getElementById('challan_invoice_id').value = challan.invoice_id;
            document.getElementById('delivery_challan_no').value = challan.delivery_challan_no;
            document.getElementById('delivery_challan_date').value = challan.delivery_challan_date;
            document.getElementById('mode_of_transport').value = challan.mode_of_transport;
            document.getElementById('vehicle_no').value = challan.vehicle_no;
            document.getElementById('transport_purpose').value = challan.transport_purpose;
            // If you want to show the existing file name, you can do so here
            if (challan.delivery_challan_copy) {  
              document.getElementById('file-drop-area-text').textContent = challan.delivery_challan_copy.split('/').pop();
            } else {
              document.getElementById('file-drop-area-text').textContent = 'Drag & Drop your delivery challan file here or';
            }
            // Note: File input cannot be pre-filled for security reasons
            openChallanPopup();
          } else {
            alert(data.message || 'Failed to fetch challan details.');
          }
        })
        .catch(error => {
          console.error('Error fetching challan details:', error);
          alert('An error occurred while fetching challan details.');
        });
    });
  }
  const removeChallanBtn = document.getElementById('removeChallan<?php echo $challan['id'] ?? ''; ?>');
  if (removeChallanBtn) {
    removeChallanBtn.addEventListener('click', function() {
      const challanId = this.id.replace('removeChallan', '');
      if (confirm('Are you sure you want to remove this challan?')) {
        fetch(`<?= base_url("?page=purchase_orders&action=remove_challan&id=") ?>${challanId}`, {
          method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Challan removed successfully!');
            location.reload(); // Reload the page to reflect changes
          } else {
            alert(data.message || 'Failed to remove challan.');
          }
        })
        .catch(error => {
          console.error('Error removing challan:', error);
          alert('An error occurred while removing the challan.');
        });
      }
    });
  }


});

// payment popup
document.addEventListener('DOMContentLoaded', function() {
    const openPaymentPopupBtn = document.getElementById('open-payment-popup-btn');
    const closePaymentPopupBtn = document.getElementById('close-payment-popup-btn');
    const cancelPaymentBtn = document.getElementById('cancel-payment-btn');
    const paymentPopup = document.getElementById('payment-popup');
    const paymentModalSlider = document.getElementById('payment-modal-slider');
    const paymentForm = document.getElementById('payment-form');
    const paymentError = document.getElementById('payment-error');
    const paymentErrorMsg = paymentError.querySelector('p');
    const editPaymentBtns = document.querySelectorAll('.edit-payment');
    const removePaymentBtns = document.querySelectorAll('.remove-payment');

    // Function to open the popup
    function openPaymentPopup() {
        paymentPopup.classList.remove('hidden');
        toggleMenu(document.querySelector('.menu-button')); // Close the menu if open
        setTimeout(() => {
            paymentModalSlider.classList.remove('translate-x-full');
        }, 10); // Slight delay to allow transition
    }

    // Function to close the popup
    function closePaymentPopup() {
        paymentModalSlider.classList.add('translate-x-full');
        setTimeout(() => {
            paymentPopup.classList.add('hidden');
            clearPaymentForm();
        }, 300); // Match the duration of the CSS transition
    }

    // Function to clear the form and error messages
    function clearPaymentForm() {
        paymentForm.reset();
        paymentError.classList.add('hidden');
        paymentErrorMsg.textContent = '';
        document.getElementById('payment-id').value = '';
        document.getElementById('payment-po-id').value = '';
    }

    // Event listeners for opening and closing the popup
    if (openPaymentPopupBtn) {
        openPaymentPopupBtn.addEventListener('click', openPaymentPopup);
    }
    // Event listeners for edit buttons
    editPaymentBtns.forEach(button => {
        button.addEventListener('click', function() {
            const paymentId = this.id.replace('editPayment', '');
            // Fetch payment details via AJAX
            fetch(`<?= base_url("?page=purchase_orders&action=get_payment&id=") ?>${paymentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const payment = data.data;
                        //console.log(payment.invoice_id);
                        // Populate the form fields
                        document.getElementById('payment-id').value = payment.id;
                        document.getElementById('invoice_id').value = payment.invoice_id;
                        document.getElementById('payment_date').value = payment.payment_date;
                        document.getElementById('payment_mode').value = payment.payment_mode;
                        document.getElementById('payment_type').value = payment.payment_type;
                        document.getElementById('amount_paid').value = payment.amount_paid;
                        //document.getElementById('balance_due').value = payment.balance_due;
                        document.getElementById('vendor_bank_account_name').value = payment.vendor_bank_account_name;
                        document.getElementById('vendor_bank_account_number').value = payment.vendor_bank_account_number;
                        document.getElementById('vendor_bank_ifsc_code').value = payment.vendor_bank_ifsc_code;
                        document.getElementById('vendor_branch_name').value = payment.vendor_branch_name;
                        document.getElementById('vendor_bank_name').value = payment.vendor_bank_name;
                        document.getElementById('bank_transaction_reference_no').value = payment.bank_transaction_reference_no;
                        document.getElementById('payment_note').value = payment.payment_note;
                        document.getElementById('payment-po-id').value = payment.po_id;
                        document.getElementById('payment-vendor-id').value = payment.vendor_id;

                        // Open the popup
                        openPaymentPopup();
                    } else {
                        alert(data.message || 'Failed to fetch payment details.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching payment details:', error);
                    alert('An error occurred while fetching payment details.');
                });
        });
    });
    closePaymentPopupBtn.addEventListener('click', closePaymentPopup);
    cancelPaymentBtn.addEventListener('click', closePaymentPopup);

    // Handle form submission
    paymentForm.addEventListener('submit', function(event) {
        event.preventDefault();

        // Clear previous error messages
        paymentError.classList.add('hidden');
        paymentErrorMsg.textContent = '';

        const formData = new FormData(paymentForm);

        fetch('<?= base_url("?page=purchase_orders&action=add_payment") ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Payment added successfully, you can show a success message or update the UI
                alert('Payment added successfully!');
                closePaymentPopup();
                location.reload(); // Reload the page to reflect changes
            } else {
                // Show error message
                paymentError.classList.remove('hidden');
                paymentErrorMsg.textContent = data.message || 'An error occurred. Please try again.';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            paymentError.classList.remove('hidden');
            paymentErrorMsg.textContent = 'An error occurred. Please try again.';
        });
    });
    
});

// Handle remove payment
document.querySelectorAll('.remove-payment').forEach(button => {
    button.addEventListener('click', function() {
        const paymentId = this.id.replace('removePayment', '');
        if (confirm('Are you sure you want to remove this payment?')) {
            fetch(`<?= base_url("?page=purchase_orders&action=remove_payment&id=") ?>${paymentId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: paymentId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Payment removed successfully!');
                    location.reload(); // Reload the page to reflect changes
                } else {
                    alert(data.message || 'Failed to remove payment.');
                }
            })
            .catch(error => {
                console.error('Error removing payment:', error);
                alert('An error occurred while removing the payment.');
            });
        }
    });
});

// Balance due calculation
document.getElementById('amount_paid').addEventListener('input', function() {
    const amountPaid = parseFloat(this.value) || 0;
    const totalCost = parseFloat(<?= $purchaseOrder['total_cost'] ?? 0; ?>);
    const totalAmountPaid = parseFloat(<?= $data['total_amount_paid'] ?? 0; ?>);
    const balanceDueField = document.getElementById('balance_due');

    const newBalanceDue = totalCost - (totalAmountPaid + amountPaid);
    balanceDueField.value = newBalanceDue.toFixed(2);
});

</script>

