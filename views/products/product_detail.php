<div class="max-w-7xl mx-auto p-4 space-y-6">

  <!-- PRODUCT HEADER -->
  <div class="bg-white rounded-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="flex gap-4">
      <div class="w-24 h-32"><img onclick="openImagePopup('<?php echo $products['image']; ?>')" src="<?php echo htmlspecialchars($products['image'] ?? 'https://placehold.co/90x120'); ?>" class="rounded border w-full h-32 object-cover cursor-pointer" /></div>
      
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
          <?php foreach ($products['variants'] as $variant): ?>
            <span class="px-2 py-1 border rounded text-xs"><?php echo htmlspecialchars($variant['sku'] ?? ''); ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Measures -->
    <div class="bg-orange-50 rounded-lg p-4">
      <h3 class="font-semibold mb-2">Measures</h3>
      <div class="grid grid-cols-2 gap-2 text-sm">
        <div><i class="fas fa-ruler-horizontal mr-1 text-orange-600"></i>Length: <b><?php echo htmlspecialchars($products['prod_length'] ?  $products['prod_length'].' '.$products['length_unit'] : ''); ?> </b></div>
        <div><i class="fas fa-ruler-vertical mr-1 text-orange-600"></i>Height: <b><?php echo htmlspecialchars($products['prod_height'] ? $products['prod_height'].' '.$products['length_unit'] : ''); ?></b></div>
        <div><i class="fas fa-arrows-alt-h mr-1 text-orange-600"></i>Width: <b><?php echo htmlspecialchars($products['prod_width'] ? $products['prod_width'].' '.$products['length_unit'] : ''); ?></b></div>
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
  <div class="bg-white rounded-xl p-4 shadow-sm space-y-4 col-span-2">

    <h3 class="font-semibold text-gray-700">Inventory</h3>

      <!-- Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

        <!-- Local Stock -->
        <div class="flex items-center justify-between border rounded-lg p-4">
          <div>
            <p class="text-sm text-gray-500">Local Stock</p>
            <p class="text-xl font-semibold"><?php echo htmlspecialchars($products['stocks']['local_stock'] ?? '0'); ?></p>
          </div>
          <div class="bg-blue-100 text-blue-600 p-2 rounded-lg">
            📦
          </div>
        </div>

        <!-- Committed -->
        <div class="flex items-center justify-between border rounded-lg p-4">
          <div>
            <p class="text-sm text-gray-500">Committed</p>
            <p class="text-xl font-semibold"><?php echo htmlspecialchars($products['stocks']['committed'] ?? '0'); ?></p>
          </div>
          <div class="bg-purple-100 text-purple-600 p-2 rounded-lg">
            ⏱️
          </div>
        </div>

        <!-- Available -->
        <div class="flex items-center justify-between border rounded-lg p-4">
          <div>
            <p class="text-sm text-gray-500">Available</p>
            <p class="text-xl font-semibold"><?php echo htmlspecialchars($products['stocks']['current_stock'] ?? '0'); ?></p>
          </div>
          <div class="bg-green-100 text-green-600 p-2 rounded-lg">
            📈
          </div>
        </div>

        <!-- In Purchase -->
        <div class="flex items-center justify-between border rounded-lg p-4">
          <div>
            <p class="text-sm text-gray-500">In Purchase</p>
            <p class="text-xl font-semibold"><?php echo htmlspecialchars($products['in_purchase'] ?? '0'); ?></p>
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
        <div class="flex justify-between">
          <span><i class="fas fa-dollar px-2 py-1 rounded text-xs mr-1 text-green-600 bg-green-100"></i>Cost Price</span><span>₹<?php echo htmlspecialchars($products['cost_price'] ?? '0'); ?></span>
        </div>
        <div class="flex justify-between">
          <span><i class="fas fa-tag  mr-1 px-2 py-1 rounded text-xs mr-1 text-green-600 bg-green-100"></i>Item Price</span><span>₹<?php echo htmlspecialchars($products['item_price'] ?? '0'); ?></span>
        </div>
        <div class="flex justify-between">
          <span><i class="fas fa-rupee-sign px-2 py-1 rounded text-xs mr-1 text-green-600 bg-green-100"></i>Stock Value</span><span>₹<?php echo htmlspecialchars($products['stock_value'] ?? '0'); ?></span>
        </div>
        <div class="flex justify-between">
          <span><i class="fas fa-chart-line px-2 py-1 rounded text-xs mr-1 text-green-600 bg-green-100"></i>Potential Sales Value</span><span>₹<?php echo htmlspecialchars($products['potential_sales_value'] ?? '0'); ?></span>  
        </div>
        <hr class="border-t">
        <div class="text-xs text-gray-500 mt-2">
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
            <b><?php echo htmlspecialchars($vendor['vendor_name'] ?? ''); ?></b>
            <p class="text-gray-500"><?php echo htmlspecialchars($vendor['city'] ?? ''); ?>, <?php echo htmlspecialchars($vendor['state'] ?? ''); ?></p>
          </div>
        <?php endforeach;
        } ?>
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
    <h3 class="font-semibold mb-3">Stock Transactions</h3>
    <table class="min-w-full text-sm border">
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
  </div>

</div>
<!-- Image Popup -->
<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
  <div class="bg-white p-4 rounded">
    <img id="popupImage" src="" alt="Product Image" class="max-w-full max-h-screen" />
    <button onclick="document.getElementById('imagePopup').classList.add('hidden')" class="mt-2 px-4 py-2 bg-red-600 text-white rounded">Close</button>
  </div>
</div>
<script>
  function openImagePopup(imageUrl) {
    const popup = document.getElementById('imagePopup');
    const popupImage = document.getElementById('popupImage');
    popupImage.src = imageUrl;
    popup.classList.remove('hidden');
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
</script>