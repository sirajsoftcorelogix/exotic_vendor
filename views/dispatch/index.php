<div class="container mx-auto px-4 py-8">
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
          <input type="text" name="date_range" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-purple-400 outline-none" value="<?= htmlspecialchars($_GET['date_range'] ?? '') ?>">
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
            <option value="dispatched" <?= ($_GET['status'] ?? '') === 'dispatched' ? 'selected' : '' ?>>Dispatched</option>
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
      <button id="bulk-print-labels-btn" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md transition">
        Print Label
      </button>
      <button class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md transition">
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
          <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md transition">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
              <!-- LEFT -->
              <div class="flex gap-4">
                <input type="checkbox" class="mt-1 w-5 h-5 label-checkbox" value="<?= htmlspecialchars($invoice['id']); ?>">
                <div class="flex gap-8 flex-wrap">
                  <div>
                    <p class="text-xs text-gray-500">Inv No.</p>
                    <p class="text-blue-600 font-semibold"><a href="<?php echo base_url('?page=invoices&action=generate_pdf&invoice_id=' . $invoice['id']); ?>"><?php echo htmlspecialchars($invoice['invoice_number'] ?? $invoice['id']); ?></a></p>
                    <p class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($invoice['invoice_date'] ?? '')); ?></p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Order No.</p>
                    <p class="text-blue-600 font-semibold"><?php foreach ($invoice['items'] ?? [] as $item) {
                      echo '<a href="' . base_url('?page=orders&action=get_order_details_html&type=outer&order_number=' . htmlspecialchars($item['order_number'] ?? '')) . '">' . htmlspecialchars($item['order_number'] ?? '') . '</a><br>';
                    } ?></p>
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
                    <?php //echo count($invoice['items'] ?? []); ?>
                    <?php foreach ($invoice['items'] ?? [] as $item) {
                      echo htmlspecialchars($item['item_code'] ?? '') . '<br>';
                    } ?>
                  </p>
                </div>
                <div>
                  <p class="text-xs text-gray-500">AWB:</p>
                  <p class="text-blue-600 font-medium text-sm">
                    <?php 
                      $awbs = [];
                      if (!empty($invoice_dispatch[$invoice['id']])) {
                        foreach ($invoice_dispatch[$invoice['id']] as $dispatch) {
                          if (!empty($dispatch['awb_code'])) {
                            $link = !empty($dispatch['label_url']) ? '<a href="' . htmlspecialchars($dispatch['label_url']) . '" target="_blank">' . htmlspecialchars($dispatch['awb_code']) . '</a>' : htmlspecialchars($dispatch['awb_code']);
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
                <div class="relative">
                  <button class="text-gray-600 hover:bg-gray-100 rounded-full p-2 text-lg" onclick="toggleMenu(this)">
                    ⋮
                  </button>
                  <div class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                    <a href="<?php echo base_url('?page=invoices&action=generate_pdf&invoice_id=' . $invoice['id']); ?>" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Download invoice</a>
                    <?php if (!empty($invoice_dispatch[$invoice['id']])): ?>
                      <?php foreach ($invoice_dispatch[$invoice['id']] as $dispatch): ?>
                        <a href="<?php echo $dispatch['label_url']; ?>" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Download <Label><?php echo htmlspecialchars($dispatch['awb_code']); ?></Label></a>
                      <?php endforeach; ?>
                    <?php endif; ?>
                    <?php
                      $needsRetry = false;
                      if (!empty($invoice_dispatch[$invoice['id']])) {
                        foreach ($invoice_dispatch[$invoice['id']] as $dispatch) {
                          if (empty($dispatch['awb_code'])) {
                            $needsRetry = true;
                            break;
                          }
                        }
                      }
                    ?>
                    <?php if ($needsRetry): ?>
                      <button class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 border-none bg-transparent cursor-pointer" onclick="retryDispatchAjax(<?php echo htmlspecialchars($invoice['id']); ?>)" style="padding: 0.5rem 1rem;">Retry Dispatch</button>
                      
                    <?php endif; ?>
                    <button class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 border-none bg-transparent cursor-pointer" onclick="cancelDispatchAjax(<?php echo htmlspecialchars($invoice['id']); ?>)" style="padding: 0.5rem 1rem;">Cancel Dispatch</button>
                  </div>
                </div>
                
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

<script>
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
        bulkPrintBtn.textContent = '<span class="animate-spin">⏳</span> Processing...';
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
              showAlert('Retry initiated successfully. Reloading...', 'success');
              location.reload();
          } else {
              alert('Error: ' + (data.message || 'Failed to retry dispatch'));
          }
      })
      .catch(err => {
          console.error(err);
          alert('Error retrying dispatch');
      });
    }
    document.addEventListener('click', function(event) {
      if (!event.target.closest('.relative')) {
        document.querySelectorAll('.relative > div').forEach(menu => {
          menu.classList.add('hidden');
        });
      }
    });
    function cancelDispatchAjax(invoiceId) {
      customConfirm('Are you sure you want to cancel the dispatch for this invoice? This action cannot be undone.').then(confirmed => {
        if (confirmed) {
          fetch('?page=dispatch&action=cancel_dispatch', {
              method: 'POST',
              headers: {'Content-Type': 'application/json'},
              body: JSON.stringify({ invoice_id: invoiceId })
          })
          .then(res => res.json())
          .then(data => {
              if (data.success) {
                  showAlert('Cancel initiated successfully. Reloading...', 'success');
                  location.reload();
              } else {
                  showAlert('Error: ' + (data.message || 'Failed to cancel dispatch'), 'error');
              }
          })
          .catch(err => {
              console.error(err);
              alert('Error canceling dispatch');
          });
        }
      });
      //if (!confirm('Cancel dispatch for this invoice?')) return;
      
      
    }
</script>