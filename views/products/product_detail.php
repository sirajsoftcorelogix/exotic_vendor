<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<div class="max-w-7xl mx-auto p-4 space-y-6">

  <!-- PRODUCT HEADER -->
  <div class="bg-white rounded-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="flex gap-4">
      <!-- <div class="w-24 h-32 bg-white rounded-[10px] outline outline-2 outline-offset-[-2px] outline-amber-600 ">
        <img onclick="openImagePopup('<?php //echo $products['image']; ?>')" src="<?php //echo htmlspecialchars($products['image'] ?? 'https://placehold.co/90x120'); ?>" class="w-full h-full px-3 py-3 cursor-pointer" />
      </div> -->
      <div onclick="openImagePopup('<?php echo $products['image']; ?>')" class="w-24 h-32 bg-white rounded-[10px] outline outline-2 outline-offset-[-2px] outline-amber-600 cursor-pointer">
        <img src="<?php echo htmlspecialchars($products['image'] ?? 'https://placehold.co/90x120'); ?>" class="max-w-full max-h-full px-3 py-3 object-contain cursor-pointer" />
      </div>
      <div>
        <span class="text-xs bg-orange-100 text-orange-600 px-2 py-1 rounded">
          <?php echo $products['groupname'] ?? 'Default Group'; ?>
        </span> 
        <span class="text-sm ml-2"> <?php echo $products['item_code'] ?? ''; ?></span>
        
        <h2 class="font-semibold mt-2 text-lg">
          <?php echo htmlspecialchars($products['title'] ?? 'Product Title'); ?>
        </h2>
        <p class="text-sm text-gray-500">SKU: <?php echo htmlspecialchars($products['sku'] ?? ''); ?></p>

        <div class="flex flex-wrap gap-2 mt-2">
          <?php foreach ($products['variants'] as $variant): 
            if(isset($variant['sku']) && !empty($variant['sku'])): ?>
            <span class="px-2 py-1 border rounded text-xs"><a href="<?php echo base_url('?page=products&action=detail&id='.$variant['id']); ?>"><?php echo $variant['sku']; ?></a></span>
          <?php endif; endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Measures -->
    <div class="bg-orange-50 rounded-lg p-4">
      <h3 class="font-semibold mb-2">Measures</h3>
      <div class="grid grid-cols-2 gap-2 text-sm">
        <div><i class="fas fa-ruler-horizontal mr-1 text-orange-600"></i>Length: <b><?php echo htmlspecialchars($products['prod_length'] ?  $products['prod_length'].' '.$products['length_unit'] : ''); ?> </b></div>
        <div><i class="fas fa-ruler-vertical mr-2 text-orange-600"></i>Height: <b><?php echo htmlspecialchars($products['prod_height'] ? $products['prod_height'].' '.$products['length_unit'] : ''); ?></b></div>
        <div><i class="fas fa-arrows-alt-h mr-1.5 text-orange-600"></i>Width: <b><?php echo htmlspecialchars($products['prod_width'] ? $products['prod_width'].' '.$products['length_unit'] : ''); ?></b></div>
        <div><i class="fas fa-weight mr-1 text-orange-600"></i>Weight: <b><?php echo htmlspecialchars($products['product_weight'] ?  $products['product_weight'] .' ' .$products['product_weight_unit'] : ''); ?></b></div>
      </div>
    </div>

    
  </div>

  <!-- INVENTORY -->
  <!-- <div class="bg-white rounded-lg p-4 grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
    <div>
      <p class="text-gray-500 text-sm">Local Stock</p>
      <p class="text-xl font-semibold"><?php //echo htmlspecialchars($products['local_stock'] ?? '0'); ?></p>
    </div>
    <div>
      <p class="text-gray-500 text-sm">Committed</p>
      <p class="text-xl font-semibold">0</p>
    </div>
    <div>
      <p class="text-gray-500 text-sm">Available</p>
      <p class="text-xl font-semibold"><?php //echo htmlspecialchars($products['stocks']['current_stock'] ?? '0'); ?></p>
    </div>
    <div>
      <p class="text-gray-500 text-sm">In Purchase</p>
      <p class="text-xl font-semibold">0</p>
    </div>
    <div>
      <p class="text-gray-500 text-sm">Sold</p>
      <p class="text-xl font-semibold"></p>
    </div>
  </div> -->
  <!-- Inventory -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
  <div class="bg-white rounded-lg p-4 shadow-sm space-y-4 col-span-2">

    <h3 class="font-semibold text-gray-700">Inventory</h3>

      <!-- Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

        <!-- Local Stock -->
        <div class="flex items-center justify-between border rounded-lg p-4">
          <div>
            <p class="text-sm text-gray-500">Local Stock</p>
            <p class="text-xl font-semibold"><?php echo htmlspecialchars($products['local_stock'] ?? '0'); ?></p>
          </div>
          <div class="bg-blue-100 text-blue-600 p-2 rounded-lg">
            📦
          </div>
        </div>

        <!-- Committed -->
        <div class="flex items-center justify-between border rounded-lg p-4">
          <div>
            <p class="text-sm text-gray-500">Committed</p>
            <p class="text-xl font-semibold"><?php echo htmlspecialchars($products['committed_stock'] ?? '0'); ?></p>
          </div>
          <div class="bg-purple-100 text-purple-600 p-2 rounded-lg">
            ⏱️
          </div>
        </div>

        <!-- Available -->
        <div class="flex items-center justify-between border rounded-lg p-4">
          <div>
            <p class="text-sm text-gray-500">Available</p>
            <p class="text-xl font-semibold"><?php echo htmlspecialchars($products['available_stock'] ?? '0'); ?></p>
          </div>
          <div class="bg-green-100 text-green-600 p-2 rounded-lg">
            📈
          </div>
        </div>

        <!-- In Purchase -->
        <div class="flex items-center justify-between border rounded-lg p-4">
          <div>
            <p class="text-sm text-gray-500">In Purchase</p>
            <p class="text-xl font-semibold"><?php echo count($products['in_purchase_list']); ?></p>
          </div>
          <div class="bg-orange-100 text-orange-600 p-2 rounded-lg">
            🛒
          </div>
        </div>

      </div>

      <!-- Number Sold -->
      <div class="flex justify-between items-center border rounded-lg p-4">
        <span class="text-sm text-gray-500">Number Sold</span>
        <span class="font-semibold text-lg"><?php echo htmlspecialchars($products['numsold'] ?? '0'); ?></span>
      </div>

  </div>
  <!-- Price -->
    <div class="bg-white border rounded-lg p-4">
      <h3 class="font-semibold mb-3">Price</h3>
      <div class="space-y-2 text-sm">
        <div class="flex justify-between bg-green-50 p-2 rounded">
          <span><i class="fas fa-dollar px-2 py-1 rounded text-xs mr-1 text-green-600 bg-green-100"></i>Cost Price</span><span>₹<?php echo htmlspecialchars($products['cost_price'] ?? '0'); ?></span>
        </div>
        <div class="flex justify-between bg-green-50 p-2 rounded">
          <span><i class="fas fa-tag  mr-1 px-2 py-1 rounded text-xs mr-1 text-green-600 bg-green-100"></i>Item Price</span><span>₹<?php echo htmlspecialchars($products['itemprice'] ?? '0'); ?></span>
        </div>
        <div class="flex justify-between bg-green-50 p-2 rounded">
          <span><i class="fas fa-rupee-sign px-2 py-1 rounded text-xs mr-1 text-green-600 bg-green-100"></i>Stock Value</span><span>₹<?php echo htmlspecialchars($products['stock_value'] ?? '0'); ?></span>
        </div>
        
        <hr class="border-t">
        <div class="text-xs text-gray-500 mt-2 text-center">
          HSN: <?php echo htmlspecialchars($products['hsn'] ?? ''); ?> | GST: <?php echo htmlspecialchars($products['gst'] ?? ''); ?>%
        </div>
      </div>
    </div>
   </div>


  <!-- VENDORS + NOTES -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

    <!-- Vendors -->
    <div class="bg-white rounded-lg p-4 md:col-span-2">
      <h3 class="font-semibold mb-3">Vendors</h3>
      <div class="space-y-3 text-sm">
        <?php 
          if(empty($products['vendors'])) {
            echo '<p class="text-gray-500">No vendors associated with this product.</p>';
          }
          else{
          foreach ($products['vendors'] as $vendor): ?>
          <div class="border rounded p-3">
            <div class="flex items-center gap-3 mb-2">
              <div class="w-10 h-10 bg-orange-200 rounded-full overflow-hidden flex items-center justify-center text-white">
                <i class="fas fa-store text-orange-500 text-lg"></i>
              </div>             
              <div class="ml-13 ">
                <b><?php echo htmlspecialchars($vendor['vendor_name'] ?? ''); ?></b>
                <p class="text-gray-500"><i class="fas fa-map-marker-alt text-xs mr-1"></i><?php echo htmlspecialchars($vendor['city'] ?? ''); ?>, <?php echo htmlspecialchars($vendor['state'] ?? ''); ?> <i class="fas fa-phone text-xs ml-2 mr-1"></i><?php echo htmlspecialchars($vendor['vendor_phone'] ?? ''); ?></p>
             </div>
            </div>
          </div>
        <?php endforeach;
        } ?>
        <!-- in purchase list -->
        <div class="border border-amber-500 rounded p-3 bg-yellow-50">
          <?php if(!empty($products['in_purchase_list'])): ?>
            <h4 class="font-semibold mb-2">Pending Purchase</h4>   
            <div class="flex flex-wrap gap-2 mb-2">         
            <?php foreach($products['in_purchase_list'] as $key => $purchase): ?>
              <a class="hover:bg-yellow-100 text-blue-600 cursor-pointer" href="<?php echo base_url('?page=purchase_orders&action=view&po_id=' . htmlspecialchars($key ?? '')); ?>"> <?php echo htmlspecialchars($purchase ?? ''); ?> </a>
            <?php endforeach; ?>   
            </div>        
          <?php else: ?>
            <p class="text-gray-500">No purchases are currently in progress for this product.</p>
          <?php endif; ?>
            
        </div>
      </div>
    </div>

    <!-- Notes -->
    <div class="bg-white rounded-lg p-4">
      <h3 class="font-semibold mb-3">Notes</h3>
      <textarea id="product-notes" class="w-full border rounded p-2 text-sm resize-none" rows="8"
        placeholder="Add notes here..."><?php echo htmlspecialchars($products['notes'] ?? ''); ?></textarea>
      <button class="mt-2 px-4 py-2 bg-blue-600 text-white rounded text-sm" onclick="saveProductNotes(<?php echo htmlspecialchars($products['id'] ?? 0); ?>)">Save Notes</button>
    </div>

  </div>

  <!-- STOCK TRANSACTIONS -->
  <div class="bg-white rounded-lg p-4 overflow-x-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold">Stock Transactions</h3>
          <a target="_blank" href="<?php echo base_url('?page=products&action=inventory_ledger&sku=' . htmlspecialchars($products['sku'] ?? '')); ?>"><i title="View stock movement history for this product" class="fas fa-exchange-alt text-orange-500"></i></a>
        </div>
    
    
    <!--search fileds-->
    <div class="flex flex-wrap gap-4 mb-4">
      <!-- <input type="text" id="searchRefId" placeholder="Search by Ref ID" class="border rounded p-2 text-sm"> -->
       <div>
            <label for="date" class="block text-sm font-medium text-gray-600 mb-1">Date Range</label>
            <input type="text" id="dateRange" name="dateRange" class="border rounded p-2 text-sm" placeholder="Select date range">
       </div>
        <script>
            $(function() {
                // Initialize date range picker: display format 'DD MMM YYYY' (e.g., 25 Dec 2015)
                $('#dateRange').daterangepicker({
                    autoUpdateInput: false,
                    locale: {
                        cancelLabel: 'Clear',
                        format: 'DD MMM YYYY'
                    }
                });
                $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format('DD MMM YYYY') + ' - ' + picker.endDate.format('DD MMM YYYY'));
                });
                $('#dateRange').on('cancel.daterangepicker', function(ev, picker) {
                    $(this).val('');
                });
            });
        </script>

       <div>
          <label for="searchType" class="block text-sm font-medium text-gray-600 mb-1">Transaction Type</label>  
          <select id="searchType" class="border rounded p-2 text-sm">
            <option value="">All Types</option>
            <option value="IN">Purchase</option>
            <option value="OUT">Sale</option>
            <option value="TRANSFER_IN">Transfer In</option>
            <option value="TRANSFER_OUT">Transfer Out</option>
          </select>
      </div>   
      <div> 
          <label for="searchWarehouse" class="block text-sm font-medium text-gray-600 mb-1">Warehouse</label>  
          <select id="searchWarehouse" class="border rounded p-2 text-sm">
            <option value="">All Warehouses</option>
            <?php 
              if(!empty($products['warehouses'])) {
                foreach($products['warehouses'] as $warehouse) {
                  echo '<option value="' . htmlspecialchars($warehouse['id']) . '">' . htmlspecialchars($warehouse['name']) . '</option>';
                }
              }
            ?>
          </select>
      </div>
      <div class="flex items-end">
        <label for="searchType" class="block text-sm font-medium text-gray-600 mb-1 invisible"> </label>
        <button class="px-4 py-2 bg-orange-500 hover:bg-orange-700 text-white rounded text-sm" onclick="filterStockHistory()">Search</button>
      </div>
    </div>
    <table id="stockHistoryTable" class="min-w-full text-sm border">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 border">Date</th>
          <th class="p-2 border">Ref ID</th>
          <th class="p-2 border">Type</th>
          <th class="p-2 border">Stock In</th>
          <th class="p-2 border">Stock Out</th>
          <th class="p-2 border">Balance</th>
          <th class="p-2 border">Warehouse</th>
        </tr>
      </thead>
      <tbody>
        <?php //print_r($products['warehouses']);
        if(!empty($products['stock_history'])) {
          foreach($products['stock_history'] as $history) {
            $type = ['IN' => 'Purchase', 'OUT' => 'Sale', 'TRANSFER_IN' => 'Transfer In', 'TRANSFER_OUT' => 'Transfer Out'];
            $fontawesomeIcon = ['IN' => 'fa-arrow-up', 'OUT' => 'fa-arrow-down', 'TRANSFER_IN' => 'fa-exchange-alt', 'TRANSFER_OUT' => 'fa-exchange-alt'];
            $textColor = ['IN' => 'text-green-600', 'OUT' => 'text-red-600', 'TRANSFER_IN' => 'text-blue-600', 'TRANSFER_OUT' => 'text-blue-600'];
            ?>
            <tr class="text-center">
              <td class="p-2 border"><?php echo htmlspecialchars(date('d M Y', strtotime($history['created_at'] ?? ''))); ?></td>
              <td class="p-2 border"><?php echo htmlspecialchars($history['ref_id'] ?? ''); ?></td>
              <td class="p-2 border <?php echo htmlspecialchars($textColor[$history['movement_type']] ?? ''); ?>">
                <i class="fas <?php echo htmlspecialchars($fontawesomeIcon[$history['movement_type']] ?? ''); ?>"></i>
                <?php echo htmlspecialchars($type[$history['movement_type']] ?? ''); ?>
              </td>
              <td class="p-2 border"><?php echo htmlspecialchars($history['movement_type'] == 'IN' ? $history['quantity'] : ''); ?></td>
              <td class="p-2 border"><?php echo htmlspecialchars($history['movement_type'] == 'OUT' ? $history['quantity'] : ''); ?></td>
              <td class="p-2 border"><?php echo htmlspecialchars($history['running_stock'] ?? '0'); ?></td>
              <td class="p-2 border"><?php echo htmlspecialchars($history['warehouse_name'] ?? ''); ?></td>
            </tr> 
            <?php
          }
        } else {
          echo '<tr><td colspan="8" class="p-4 text-center text-gray-500">No stock transactions found.</td></tr>';
        }
        ?>
        
      </tbody>
    </table>
    <!-- Pagination -->
    <div id="paginationContainer" class="flex justify-center items-center gap-2 mt-4">
      <button id="prevBtn" class="px-3 py-1 bg-gray-300 text-gray-700 rounded disabled:opacity-50" onclick="previousPage()">Previous</button>
      <span id="pageInfo" class="text-sm text-gray-600">Page 1</span>
      <button id="nextBtn" class="px-3 py-1 bg-gray-300 text-gray-700 rounded disabled:opacity-50" onclick="nextPage()">Next</button>
    </div>
  </div>

</div>
<!-- Image Popup -->
<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeImagePopup(event)">
    <div class="bg-white p-4 rounded-md max-w-3xl max-h-3xl relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <img id="popupImage" class="max-w-full max-h-[80vh] rounded" src="" alt="Image Preview">
    </div>
</div>
<!-- <div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
  <div class="bg-white p-4 rounded max-h-screen max-w-lg flex flex-col items-center">
    <img id="popupImage" src="" alt="Product Image" class="max-w-full h-full object-contain" />
    <button onclick="document.getElementById('imagePopup').classList.add('hidden')" class="mt-2 px-4 py-2 bg-red-600 text-white rounded">Close</button>
  </div>
</div> -->
<script>
function openImagePopup(imageUrl) {
    const popup = document.getElementById('imagePopup');
    const popupImage = document.getElementById('popupImage');
    popupImage.src = imageUrl;
    popup.classList.remove('hidden');
}
function closeImagePopup() {
    const popup = document.getElementById('imagePopup');
    popup.classList.add('hidden');
}
  function saveProductNotes(productId) {
    const notes = document.getElementById('product-notes').value;
    fetch(`index.php?page=products&action=save_product_notes`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ product_id: productId, notes: notes })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showAlert('Notes saved successfully!');
      } else {
        alert('Failed to save notes.');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while saving notes.');
    });
  }
  let currentPage = 1;
  let totalPages = 1;
  const itemsPerPage = 10;
  let lastFilterParams = {};

  function filterStockHistory(page = 1) {
    const dateRange = document.getElementById('dateRange').value;
    const type = document.getElementById('searchType').value;
    const warehouse = document.getElementById('searchWarehouse').value;

    // Parse date range
    let startDate = '';
    let endDate = '';
    if (dateRange) {
      const [start, end] = dateRange.split(' - ');
      // Convert 'DD MMM YYYY' to 'YYYY-MM-DD'
      const startMoment = moment(start, 'DD MMM YYYY');
      const endMoment = moment(end, 'DD MMM YYYY');
      startDate = startMoment.format('YYYY-MM-DD');
      endDate = endMoment.format('YYYY-MM-DD');
    }

    // Store filter params for pagination (use page_no to avoid colliding with router 'page')
    lastFilterParams = {
      product_id: <?php echo htmlspecialchars($products['id'] ?? 0); ?>,
      sku: '<?php echo htmlspecialchars($products['sku'] ?? ''); ?>',
      start_date: startDate,
      end_date: endDate,
      type: type,
      warehouse: warehouse,
      page_no: page,
      limit: itemsPerPage
    };

    const params = new URLSearchParams(lastFilterParams);
    const url = `index.php?page=products&action=get_filtered_stock_history&${params.toString()}`;
    console.log('Fetching stock history from:', url);

    fetch(url)
      .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text().then(text => {
          console.log('Raw response:', text);
          return JSON.parse(text);
        });
      })
      .then(data => {
        if (data.success && data.records) {
          const tbody = document.querySelector('#stockHistoryTable tbody');
          tbody.innerHTML = '';

          if (data.records.length > 0) {
            data.records.forEach(history => {
              const row = document.createElement('tr');
              row.className = 'text-center';
              
              row.innerHTML = `
                <td class="p-2 border">${history.formatted_date || history.created_at}</td>
                <td class="p-2 border">${history.ref_id || ''}</td>
                <td class="p-2 border ${history.textColor}">
                  <i class="fas ${history.icon}"></i>
                  ${history.type}
                </td>
                <td class="p-2 border">${history.movement_type === 'IN' ? history.quantity : ''}</td>
                <td class="p-2 border">${history.movement_type === 'OUT' ? history.quantity : ''}</td>
                <td class="p-2 border">${history.running_stock || '0'}</td>
                <td class="p-2 border">${history.warehouse_name || ''}</td>
              `;
              tbody.appendChild(row);
            });
          } else {
            tbody.innerHTML = '<tr><td colspan="7" class="p-4 text-center text-gray-500">No stock transactions found.</td></tr>';
          }

          // Update pagination
          currentPage = page;
          totalPages = Math.ceil((data.total || 0) / itemsPerPage);
          updatePaginationButtons();
          document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
        } else {
          console.error('API Error:', data);
          alert('Error fetching data: ' + (data.message || 'Unknown error'));
        }
      })
      .catch(error => {
        //console.error('Error fetching filtered stock history:', error);
        alert('Failed to fetch stock history: ' + error.message);
      });
  }

  function updatePaginationButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages || totalPages === 0;
  }

  function previousPage() {
    if (currentPage > 1) {
      filterStockHistory(currentPage - 1);
    }
  }

  function nextPage() {
    if (currentPage < totalPages) {
      filterStockHistory(currentPage + 1);
    }
  }

  // Load initial data on page load
  document.addEventListener('DOMContentLoaded', function() {
    filterStockHistory(1);
  });

  
</script>