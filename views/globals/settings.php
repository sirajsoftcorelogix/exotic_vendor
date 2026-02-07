<div class="bg-white p-4 md:p-8">
     <form action="<?php echo base_url('?page=globals&action=update_settings'); ?>" id="update_settings" method="post">
    <!--	invoice_prefix invoice_series terms_and_conditions-->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 gap-4 mb-6 w-60">
            <div>
                <label for="invoice_prefix" class="block text-gray-700 form-label text-sm">Invoice Prefix</label>
                <input type="text" name="invoice_prefix" id="invoice_prefix" value="<?php echo htmlspecialchars($data['invoice_prefix'] ?? ''); ?>" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md form-input px-3 w-full">
            </div>
            <div>
                <label for="invoice_series" class="block text-gray-700 form-label text-sm">Invoice Series</label>
                <input type="text" name="invoice_series" id="invoice_series" value="<?php echo htmlspecialchars($data['invoice_series'] ?? ''); ?>" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md form-input px-3 w-full">
            </div>
            <div>
                <label for="terms_and_conditions" class="block text-gray-700 form-label text-sm">Terms and Conditions</label>
                <textarea name="terms_and_conditions" id="terms_and_conditions" rows="8" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md form-input px-3 w-full h-32"><?php echo htmlspecialchars($data['terms_and_conditions'] ?? ''); ?></textarea>
            </div>
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Save Settings</button>
<?php 
