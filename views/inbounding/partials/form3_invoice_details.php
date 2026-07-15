<?php
/** @var string $selectedVendorCode */
/** @var string $invoice_no_value */
/** @var array<int, array<string, mixed>> $vendorsList */
/** @var string $invoiceUrl */
/** @var bool $isInvoicePdf */
/** @var bool $hasInvoicePreview */
$hasInvoicePreview = ($invoiceUrl ?? '') !== '';
?>
<div class="bg-gray-50 border border-gray-300 rounded p-4" id="form3-invoice-details-section">
    <h3 class="font-bold text-sm text-gray-700 mb-3 border-b pb-1">Invoice Details</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-gray-800 font-bold text-xs mb-1">Upload Invoice</label>
            <input type="file" id="form3_invoice_input" name="invoice" accept="image/*,application/pdf"
                   class="w-full border border-gray-400 rounded px-2 py-2 text-sm bg-white focus:border-black outline-none">
            <p class="text-xs text-gray-500 mt-1">JPG, PNG, or PDF (optional)</p>
            <p id="form3-photo-error" class="text-red-600 text-xs mt-1 font-semibold min-h-[1.25rem]"></p>
        </div>

        <div id="form3-invoice-preview-wrap" class="<?php echo $hasInvoicePreview ? '' : 'hidden'; ?>">
            <label class="block text-gray-800 font-bold text-xs mb-1">Invoice Preview</label>
            <div id="form3-invoice-preview-box"
                 class="relative h-28 border border-gray-300 rounded bg-white overflow-hidden cursor-zoom-in"
                 data-invoice-url="<?php echo $hasInvoicePreview ? htmlspecialchars($invoiceUrl, ENT_QUOTES, 'UTF-8') : ''; ?>"
                 data-invoice-pdf="<?php echo !empty($isInvoicePdf) ? '1' : '0'; ?>"
                 title="Click to view invoice">
                <?php if ($hasInvoicePreview && !empty($isInvoicePdf)): ?>
                    <iframe id="form3-invoice-pdf-preview" src="<?php echo htmlspecialchars($invoiceUrl, ENT_QUOTES, 'UTF-8'); ?>#toolbar=0&navpanes=0" class="w-full h-full border-0 pointer-events-none" title="Invoice PDF preview"></iframe>
                <?php else: ?>
                    <img id="form3-invoice-img-preview" src="<?php echo $hasInvoicePreview ? htmlspecialchars($invoiceUrl, ENT_QUOTES, 'UTF-8') : ''; ?>" alt="Invoice preview" class="w-full h-full object-contain pointer-events-none <?php echo $hasInvoicePreview ? '' : 'hidden'; ?>">
                    <iframe id="form3-invoice-pdf-preview" src="" class="hidden w-full h-full border-0 pointer-events-none" title="Invoice PDF preview"></iframe>
                <?php endif; ?>
            </div>
            <button type="button" id="form3-invoice-remove-btn" class="mt-2 text-xs font-bold text-red-600 hover:text-red-800 <?php echo $hasInvoicePreview ? '' : 'hidden'; ?>">Remove preview</button>
        </div>

        <div>
            <label class="block text-gray-800 font-bold text-xs mb-1">Invoice Number</label>
            <input type="text"
                   name="invoice_no"
                   id="form3_invoice_no"
                   value="<?php echo htmlspecialchars($invoice_no_value ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                   autocomplete="off"
                   class="w-full border border-gray-400 rounded px-2 py-2 text-sm focus:border-black outline-none bg-white">
            <p id="form3-invoice-no-error" class="text-red-600 text-xs mt-1 font-semibold hidden"></p>
        </div>

        <div>
            <div class="flex items-center gap-1.5 mb-1">
                <label class="text-gray-800 font-bold text-xs" for="form3_vendor_code">Vendor</label>
                <?php
                $btnId = 'form3-vendor-cache-sync-btn';
                $title = 'Refresh vendors from catalog';
                $srLabel = 'Refresh vendor list';
                $iconType = 'vendor';
                require __DIR__ . '/catalog_refresh_btn.php';
                ?>
            </div>
            <select id="form3_vendor_code" name="vendor_code" class="w-full border border-gray-400 rounded px-2 py-2 text-sm focus:border-black outline-none bg-white">
                <option value="">Select vendor</option>
                <?php if (!empty($vendorsList)): ?>
                    <?php foreach ($vendorsList as $v): ?>
                        <?php
                        $vendorExternalCode = trim((string)($v['vendor_id'] ?? ''));
                        if ($vendorExternalCode === '') {
                            continue;
                        }
                        $isSelected = ($selectedVendorCode ?? '') !== '' && (string)$selectedVendorCode === $vendorExternalCode;
                        $vendorLabel = $vendorExternalCode . ' - ' . ($v['vendor_name'] ?? '');
                        ?>
                        <option value="<?php echo htmlspecialchars($vendorExternalCode, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($vendorLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <p class="text-xs text-gray-500 mt-1">For books, publishers also appear in this list (like Direct Purchase).</p>
            <p id="form3-vendor-error" class="text-red-600 text-xs mt-1 font-semibold hidden"></p>
        </div>
    </div>
</div>
