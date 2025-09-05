<div class="max-w-7xl mx-auto space-y-6">
    <!-- <div class="p-8">
        
    </div> -->
    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mt-5">
        <!-- Header Section with Filters and Actions -->
        <div class="bg-white rounded-xl shadow-md p-4 flex flex-wrap items-center justify-between gap-4 flex-grow">
           <span class="text-gray-600 font-medium">Purchase orders</span>
        </div>
    </div>
    <!-- PO Table Container -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                    <tr>
                        <th><i class="fa fa-star"></i></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Vendor</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">PO Number</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Expected Delivery Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Delivery Address</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Total GST</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Grand Total</th>
                        <th scope="col" class="relative px-6 py-3"> <span class="table-header-text">Status</span></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <!-- User Row 1 -->
                     <?php if (!empty($purchaseOrders)): ?>
                        <?php foreach ($purchaseOrders as $order): ?>
                    <tr class="table-content-text">
                        <td class=" whitespace-nowrap cursor-pointer text-yellow-400" title="<?= $order['flag_star'] ? 'Unmark as Important' : 'Mark as Important' ?>">
                            <?= $order['flag_star'] 
                                ? '<i class="fa fa-star cursor-pointer" onclick="toggleStar(' . $order['id'] . ')" title=\'Unmark as Important\'></i>' 
                                : '<i class="far fa-star cursor-pointer" onclick="toggleStar(' . $order['id'] . ')" title=\'Mark as Important\'></i>' 
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-600"><?= htmlspecialchars($order['vendor_id']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($order['po_number']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($order['expected_delivery_date']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($order['delivery_address']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span style="width: 75px; height: 25px;" class="px-3 py-1 inline-flex items-center justify-center text-xs leading-5 font-semibold rounded-md bg-black text-white"><?= htmlspecialchars($order['total_gst']) ?></span>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                        <span style="width: 75px; height: 25px;" class="px-3 py-1 inline-flex items-center justify-center text-xs leading-5 font-semibold rounded-md bg-black text-white"><?= htmlspecialchars($order['total_cost']) ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                                $status = strtolower($order['status']);
                                $statusClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'ordered' => 'bg-blue-100 text-blue-800',
                                    'received' => 'bg-green-100 text-green-800',
                                    'delivered' => 'bg-purple-100 text-purple-800',
                                    'cancelled' => 'bg-red-100 text-red-800'
                                ];
                                $badgeClass = $statusClasses[$status] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span style="width: 75px; height: 25px;" class="px-3 py-1 inline-flex items-center justify-center text-xs leading-5 font-semibold rounded-md <?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <!-- Three-dot menu container -->
                            <div class="menu-wrapper">
                            <button class="menu-button" onclick="toggleMenu(this)">
                                &#x22EE; <!-- Vertical ellipsis -->
                            </button>
                            <ul class="menu-popup">
                                <li onclick="handleAction('View', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-eye"></i> View PO</li>
                                <li onclick="handleAction('Edit', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-pencil-alt"></i> Edit PO</li>
                                <li onclick="handleAction('ChangeStatus', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-exchange-alt"></i> Change Status</li>
                                <?php if ($order['status'] == 'cancelled'): ?>
                                <li onclick="handleAction('Delete', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-trash-alt"></i> Delete PO</li>
                                <?php endif; ?>
                                <li onclick="handleAction('Download', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-download"></i> Download PO</li>
                                <li onclick="handleAction('Email', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-envelope"></i> Email PO to Vendor</li>
                                <li id="open-vendor-popup-btn" ><i class="fa fa-upload"></i> Upload Vendor Invoice </li>
                            </ul>
                            </div>

                            <!-- <div class="flex items-center space-x-4">
                                <a title="View Purchase Order" href="<?= base_url('?page=purchase_orders&action=view&po_id=' . htmlspecialchars($order['id'])) ?>" class="text-gray-400 hover:text-black">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a title="Edit Purchase Order" href="<?= base_url('?page=purchase_orders&action=edit&po_id=' . htmlspecialchars($order['id'])) ?>" class="text-gray-400 hover:text-black">
                                    <i class="fa fa-pencil-alt"></i>
                                </a>
                                <a title="Delete Purchase Order" href="<?= base_url('?page=purchase_orders&action=delete&po_id=' . htmlspecialchars($order['id'])) ?>" class="text-gray-400 hover:text-black" onclick="return confirm('Are you sure you want to delete this purchase order?');">
                                    <i class="fa fa-trash-alt"></i>
                                </a>
                                <a title="Download Purchase Order" target="_blank" href="<?= base_url('?page=purchase_orders&action=download&po_id=' . htmlspecialchars($order['id'])) ?>" class="text-gray-400 hover:text-black">
                                    <i class="fa fa-download"></i>
                                </a>
                            </div> -->
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No purchase orders found.</td>
                        </tr>
                    <?php endif; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Pagination -->
        <?php         
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Orders per page, default 20
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 20; // Only allow specific values

        $total_orders = isset($data['total_orders']) ? (int)$data['total_orders'] : 0;
        $total_pages = $limit > 0 ? ceil($total_orders / $limit) : 1;
        
        ?>
    <div class="bg-white rounded-xl shadow-md p-4">
      <div class="flex items-center justify-center">
        <div id="pagination-controls" class="flex items-center gap-4 text-sm text-gray-600">
            <?php if ($total_pages > 1): ?>
            <span >Page</span>
            <button id="prev-page" class="p-2 rounded-full hover:bg-gray-100">
                <a class="page-link" href="?page=purchase_orders&acton=list&page_no=<?= $page-1 ?>&limit=<?= $limit ?>" tabindex="-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </a>
            </button>
            <?php /*for ($i = 1; $i <= $total_pages; $i++): ?>
            <span id="page-number" class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg"><a class="page-link" href="?page=orders&page_no=<?= $i ?>&limit=<?= $limit ?>"><?= $i ?></a></span>
            <?php endfor; */?>
            <span id="page-number" class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg"><?= $page ?></span>
            <?php if ($page < $total_pages): ?>
            <button id="next-page" class="p-2 rounded-full hover:bg-gray-100">
                <a class="page-link" href="?page=purchase_orders&acton=list&page_no=<?= $page+1 ?>&limit=<?= $limit ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>
            </button>
            <span class="text-sm text-gray-600">of <?= $total_pages ?></span>
            <?php endif; ?>
            <?php endif; ?>
            <select id="rows-per-page" class="pagination-select bg-transparent border-b border-gray-400 focus:outline-none focus:border-gray-800 text-gray-600"
                    onchange="location.href='?page=purchase_orders&acton=list&page_no=1&limit=' + this.value;">
                <?php foreach ([10, 20, 50, 100] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $opt === $limit ? 'selected' : '' ?>>
                        <?= $opt ?>
                    </option>
                <?php endforeach; ?>
            </select>

        </div>
      </div>
    </div>    

</div>

<!-- Right Side Popup Wrapper -->
<div id="popup-wrapper" class="hidden">
    <!-- Background Overlay -->
    <!-- <div id="popup-overlay" class="fixed inset-0 bg-black bg-opacity-25 z-40"></div> -->

    <!-- Sliding Container -->
    <div id="modal-slider" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: calc(45% + 61px); min-width: 661px;">

        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-vendor-popup-btn" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Popup Panel -->
        <div id="vendor-popup-panel" class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Vendor Invoice</h2>

                    <div class="flex items-start mb-6 pb-6 border-b">
                        <img src="https://placehold.co/100x80/e2e8f0/4a5568?text=Item" alt="Product Image" class="rounded-md w-24 h-20 object-cover">
                        <div class="ml-6 text-sm text-gray-600 space-y-1">
                            <p><strong>Order ID:</strong> 123456</p>
                            <p><strong>Order Date:</strong> 20th July 25</p>
                            <p><strong>Item:</strong> 12" Painting</p>
                            <p><strong>Vendorr ID:</strong> 47635</p>
                            <p><strong>Vendor Name:</strong> ABC Pvt. Ltd.</p>
                            <p><strong>Vendor Phone:</strong> +9810865978 <i class="fab fa-whatsapp text-green-500 ml-1"></i> <span class="text-blue-600">info@vendor1.com</span></p>
                        </div>
                    </div>

                    <form id="invoice-form">
                        <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
                            <div>
                                <label for="invoice-date" class="text-sm font-medium text-gray-700">Invoice Date:</label>
                                <input type="date" id="invoice-date" class="form-input w-full mt-1">
                            </div>
                            <div>
                                <label for="gst-reg" class="text-sm font-medium text-gray-700">GST Reg:</label>
                                <select id="gst-reg" class="form-input w-full bg-white mt-1">
                                    <option>Yes</option>
                                    <option>No</option>
                                </select>
                                <p class="text-xs text-red-500 text-right mt-1">Advance, Partial, Full</p>
                            </div>
                            <div>
                                <label for="sub-total" class="text-sm font-medium text-gray-700">Sub Total ₹:</label>
                                <input type="number" id="sub-total" value="10000" class="form-input w-full mt-1">
                            </div>
                            <div>
                                <label for="gst-total" class="text-sm font-medium text-gray-700">GST Total:</label>
                                <input type="number" id="gst-total" class="form-input w-full mt-1">
                            </div>
                            <div>
                                <label for="shipping" class="text-sm font-medium text-gray-700">Shipping ₹:</label>
                                <input type="number" id="shipping" value="10000" class="form-input w-full mt-1">
                            </div>
                            <div>
                                <label for="grand-total" class="text-sm font-medium text-gray-700">Grand Total ₹:</label>
                                <input type="number" id="grand-total" value="10000" class="form-input w-full mt-1 bg-gray-100">
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Invoice PDF:</label>
                            <div id="file-drop-area" class="file-drop-area">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-500">Drag & Drop your invoice file here or</p>
                                <button type="button" id="choose-file-btn" class="mt-2 bg-white border border-gray-300 text-gray-700 px-4 py-1 rounded-md text-sm hover:bg-gray-50">Choose file</button>
                                <input type="file" id="file-input" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
                                <p class="text-xs text-gray-400 mt-2">Only PDF, JPG, PNG</p>
                            </div>
                        </div>

                        <div id="uploaded-file-section" class="hidden mb-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Uploaded Invoice:</h3>
                            <div id="file-info" class="border rounded-md p-3 flex items-center justify-between">
                                <!-- File info will be injected here by JS -->
                            </div>
                        </div>

                        <div class="flex justify-end items-center gap-4 pt-6 border-t">
                            <button type="button" id="cancel-vendor-btn" class="action-btn cancel-btn">Cancel</button>
                            <button type="submit" class="action-btn save-btn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Change Status Popup -->
<div id="status-popup-overlay" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden flex items-center justify-center">
  <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-xs">
    <h3 class="text-lg font-bold mb-4">Change Order Status</h3>
    <form id="status-form">
      <input type="hidden" id="status-po-id">
      <label for="status-select" class="block mb-2 text-sm font-medium text-gray-700">Select Status:</label>
      <select id="status-select" class="w-full border rounded-md p-2 mb-4">
        <option value="pending">Pending</option>
        <option value="ordered">Ordered</option>
        <option value="received">Received</option>
        <!-- <option value="delivered">Delivered</option> -->
        <option value="cancelled">Cancelled</option>
      </select>
      <div class="flex justify-end gap-2">
        <button type="button" id="status-cancel-btn" class="bg-gray-200 px-4 py-1 rounded">Cancel</button>
        <button type="submit" class="bg-blue-600 text-white px-4 py-1 rounded">Update</button>
      </div>
    </form>
    <div id="status-msg" class="text-sm mt-2"></div>
  </div>
</div>
<script>
    const openVendorPopupBtn = document.getElementById('open-vendor-popup-btn');
    const popupWrapper = document.getElementById('popup-wrapper');
    const modalSlider = document.getElementById('modal-slider');
    const cancelVendorBtn = document.getElementById('cancel-vendor-btn');
    const closeVendorPopupBtn = document.getElementById('close-vendor-popup-btn');

    function openVendorPopup() {
        popupWrapper.classList.remove('hidden');
        setTimeout(() => {
            modalSlider.classList.remove('translate-x-full');
        }, 10);
    }
    function closeVendorPopup() {
        modalSlider.classList.add('translate-x-full');
    }

    modalSlider.addEventListener('transitionend', (event) => {
        if (event.propertyName === 'transform' && modalSlider.classList.contains('translate-x-full')) {
            popupWrapper.classList.add('hidden');
        }
    });

    openVendorPopupBtn.addEventListener('click', openVendorPopup);
    cancelVendorBtn.addEventListener('click', closeVendorPopup);
    closeVendorPopupBtn.addEventListener('click', closeVendorPopup);

    const fileDropArea = document.getElementById('file-drop-area');
    const fileInput = document.getElementById('file-input');
    const chooseFileBtn = document.getElementById('choose-file-btn');
    const uploadedFileSection = document.getElementById('uploaded-file-section');
    const fileInfoDiv = document.getElementById('file-info');

    chooseFileBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, preventDefaults, false);
    });
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, () => fileDropArea.classList.add('dragover'), false);
    });
    ['dragleave', 'drop'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, () => fileDropArea.classList.remove('dragover'), false);
    });

    fileDropArea.addEventListener('drop', (e) => handleFiles(e.dataTransfer.files), false);

    function handleFiles(files) {
        if (files.length === 0) return;
        const file = files[0];

        const fileType = file.type;
        let iconClass = 'fa-file';
        if (fileType.includes('pdf')) iconClass = 'fa-file-pdf';
        if (fileType.includes('image')) iconClass = 'fa-file-image';

        fileInfoDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${iconClass} text-2xl text-gray-600"></i>
                    <div class="ml-3 text-sm">
                        <p class="font-medium text-gray-800">${file.name}</p>
                        <p class="text-gray-500">${(file.size / 1024).toFixed(1)} KB</p>
                    </div>
                </div>
                <button type="button" id="delete-file-btn" class="text-gray-500 hover:text-red-600">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `;
        uploadedFileSection.classList.remove('hidden');

        document.getElementById('delete-file-btn').addEventListener('click', () => {
            fileInput.value = '';
            uploadedFileSection.classList.add('hidden');
            fileInfoDiv.innerHTML = '';
        });
    }

    document.getElementById('invoice-form').addEventListener('submit', (e) => {
        e.preventDefault();
        console.log('Invoice form submitted');
        closeVendorPopup();
    });

    // Toggle menu visibility
function toggleMenu(button) {
  const popup = button.nextElementSibling;
  popup.style.display = popup.style.display === 'block' ? 'none' : 'block';

  // Close other open menus
  document.querySelectorAll('.menu-popup').forEach(menu => {
    if (menu !== popup) menu.style.display = 'none';
  });
}

// Handle action clicks
function handleAction(action, poId, el) {
  // Close the menu after action
  if (el && el.parentElement && el.parentElement.parentElement && el.parentElement.parentElement.classList.contains('menu-popup')) {
    el.parentElement.parentElement.style.display = 'none';
  } else if (el && el.parentElement && el.parentElement.classList.contains('menu-popup')) {
    el.parentElement.style.display = 'none';
  }
  if (action === 'View') {
      // Redirect to view page
      window.location.href = '?page=purchase_orders&action=view&po_id='+ poId;
  } else if (action === 'Edit') {
      // Redirect to edit page
      window.location.href = '?page=purchase_orders&action=edit&po_id='+ poId;
  } else if (action === 'Delete') {
      // Confirm and redirect to delete action
      if (confirm('Are you sure you want to delete this cancelled purchase order?')) {
          //window.location.href = '?page=purchase_orders&action=delete&po_id='+ poId;
            fetch('?page=purchase_orders&action=delete', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'po_id=' + encodeURIComponent(poId)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Purchase order deleted successfully.');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to delete purchase order.');
                }
            })
            .catch(() => {
                alert('Error deleting purchase order.');
            });
      }
  } else if (action === 'Download') {
      // Redirect to download action
      window.open('?page=purchase_orders&action=download&po_id='+ poId, '_blank');
  } else if (action === 'ChangeStatus') {
        document.getElementById('status-po-id').value = poId;
        document.getElementById('status-popup-overlay').classList.remove('hidden');
        document.getElementById('status-msg').textContent = '';
 
  }
}

// Close menu on outside click
document.addEventListener('click', function (e) {
  if (!e.target.closest('.menu-wrapper')) {
    document.querySelectorAll('.menu-popup').forEach(menu => {
      menu.style.display = 'none';
    });
  }
});
// Close popup
document.getElementById('status-cancel-btn').onclick = function() {
  document.getElementById('status-popup-overlay').classList.add('hidden');
};
// Submit status change
document.getElementById('status-form').onsubmit = function(e) {
  e.preventDefault();
  const poId = document.getElementById('status-po-id').value;
  const status = document.getElementById('status-select').value;
  const msgDiv = document.getElementById('status-msg');
  msgDiv.textContent = 'Updating...';

  fetch('?page=purchase_orders&action=update_status', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'po_id=' + encodeURIComponent(poId) + '&status=' + encodeURIComponent(status)
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      msgDiv.textContent = 'Status updated!';
      setTimeout(() => {
        document.getElementById('status-popup-overlay').classList.add('hidden');
        location.reload();
      }, 800);
    } else {
      msgDiv.textContent = data.message || 'Failed to update status.';
    }
  })
  .catch(() => {
    msgDiv.textContent = 'Error updating status.';
  });
};

// Optional: Close popup on overlay click
document.getElementById('status-popup-overlay').addEventListener('click', function(e) {
  if (e.target === this) this.classList.add('hidden');
});

// Toggle star flag
function toggleStar(poId) {
    fetch('?page=purchase_orders&action=toggle_star', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'po_id=' + encodeURIComponent(poId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update star flag.');
        }
    })
    .catch(() => {
        alert('Error updating star flag.');
    });
}
</script>