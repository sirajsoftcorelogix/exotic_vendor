<!-- CUSTOMER MODAL -->
<div id="customerModal" class="fixed inset-0 z-[9999] hidden">

  <div class="absolute inset-0 z-0 bg-black/40" onclick="closeCustomerModal()"></div>

  <div class="relative z-10 mx-auto mt-10 w-[95%] max-w-2xl rounded-2xl bg-white shadow-xl max-h-[85vh] flex flex-col">

    <div class="flex items-center justify-between border-b px-5 py-3">
      <h2 class="text-sm font-semibold" id="customerModalTitle">Add Customer</h2>
      <button type="button" onclick="closeCustomerModal()" class="text-gray-500 text-lg" aria-label="Close">✕</button>
    </div>

    <form id="customerForm" class="p-5 space-y-4 text-xs overflow-y-auto" method="POST">
      <input type="hidden" name="customer_id" id="customer_modal_id" value="">

      <div class="font-semibold text-gray-700">Billing Details</div>

      <div class="grid grid-cols-2 gap-3">

        <div>
          <label class="text-gray-500">First Name <span class="text-red-600">*</span></label>
          <input name="first_name" required class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Last Name</label>
          <input name="last_name" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Mobile</label>
          <input name="mobile" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Email</label>
          <input name="cus_email" class="w-full border rounded px-2 py-1.5">
        </div>

        <div class="col-span-2">
          <label class="text-gray-500">Address 1 <span class="text-red-600">*</span></label>
          <input name="address_line1" required class="w-full border rounded px-2 py-1.5">
        </div>
        <div class="col-span-2">
          <label class="text-gray-500">Address 2</label>
          <input name="address_line2" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">City</label>
          <input name="city" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Country</label>
          <select name="country" id="customer_country" class="w-full border rounded px-2 py-1.5 bg-white">
            <?php
            $selected_iso = 'IN';
            include __DIR__ . '/iso_country_options.php';
            ?>
          </select>
        </div>

        <div>
          <label class="text-gray-500">State</label>
          <select name="state" id="customer_state" class="w-full border rounded px-2 py-1.5 bg-white">
            <option value="">Select state</option>
          </select>
          <input id="customer_state_text" class="hidden w-full border rounded px-2 py-1.5" placeholder="State">
        </div>

        <div>
          <label class="text-gray-500">Zipcode</label>
          <input name="zipcode" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">GSTIN</label>
          <input name="gstin" class="w-full border rounded px-2 py-1.5">
        </div>

      </div>

      <div class="flex items-center gap-2 mt-2">
        <input type="checkbox" id="sameAddress" onchange="copyBilling()">
        <label class="text-xs text-gray-600">Shipping same as billing</label>
      </div>

      <div class="font-semibold text-gray-700">Shipping Details</div>

      <div class="grid grid-cols-2 gap-3">

        <div>
          <label class="text-gray-500">First Name</label>
          <input name="shipping_first_name" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Last Name</label>
          <input name="shipping_last_name" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Mobile</label>
          <input name="shipping_mobile" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Email</label>
          <input name="shipping_email" class="w-full border rounded px-2 py-1.5">
        </div>

        <div class="col-span-2">
          <label class="text-gray-500">Address 1</label>
          <input name="shipping_address_line1" class="w-full border rounded px-2 py-1.5">
        </div>
        <div class="col-span-2">
          <label class="text-gray-500">Address 2</label>
          <input name="shipping_address_line2" class="w-full border rounded px-2 py-1.5">
        </div>
        <div>
          <label class="text-gray-500">City</label>
          <input name="shipping_city" class="w-full border rounded px-2 py-1.5">
        </div>

        <div>
          <label class="text-gray-500">Country</label>
          <select name="shipping_country" id="customer_shipping_country" class="w-full border rounded px-2 py-1.5 bg-white">
            <?php
            $selected_iso = 'IN';
            include __DIR__ . '/iso_country_options.php';
            ?>
          </select>
        </div>

        <div>
          <label class="text-gray-500">State</label>
          <select name="shipping_state" id="customer_shipping_state" class="w-full border rounded px-2 py-1.5 bg-white">
            <option value="">Select state</option>
          </select>
          <input id="customer_shipping_state_text" class="hidden w-full border rounded px-2 py-1.5" placeholder="State">
        </div>

        <div>
          <label class="text-gray-500">Zipcode</label>
          <input name="shipping_zipcode" class="w-full border rounded px-2 py-1.5">
        </div>

      </div>

      <div class="flex justify-end gap-3 border-t pt-4">
        <button type="button" onclick="closeCustomerModal()" class="px-4 py-1.5 rounded bg-gray-300 text-gray-700">
          Cancel
        </button>
        <button type="submit" id="customerModalSubmitBtn" class="px-4 py-1.5 rounded bg-orange-600 text-white">
          Save Customer
        </button>
      </div>

    </form>

  </div>

</div>
