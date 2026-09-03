<?php
$h = static function ($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$rfmt = static function ($n, int $dec = 2): string {
    return number_format((float)$n, $dec, '.', ',');
};

$orderInfo = $order_info ?? [];
$invoiceData = $invoice ?? [];
$itemsList = $items ?? [];
$firmData = $firm ?? [];
$record = $existing_record ?? [];
$elig = $eligibility ?? [];

$orderNumber = $order_number ?? '';
$submitUrl = trim((string)($submit_url ?? ''));
if ($submitUrl === '') {
  $submitUrl = 'index.php?page=pos_register&action=einvoice-submit';
}
$backUrl = trim((string)($back_url ?? ''));
if ($backUrl === '') {
  $backUrl = 'index.php?page=pos_register&action=checkout-receipt&order_number=' . rawurlencode($orderNumber);
}
$ewaybillInputUrl = trim((string)($ewaybill_input_url ?? ''));
if ($ewaybillInputUrl === '') {
  $ewaybillInputUrl = 'index.php?page=pos_register&action=ewaybill-input&order_number=' . rawurlencode($orderNumber);
}
$isExport = !empty($elig['is_export']);
$isB2b = !empty($elig['is_b2b']);
$scenario = $elig['scenario'] ?? ($isExport ? 'Export' : ($isB2b ? 'Domestic B2B' : 'B2C'));

$existingIrn = $record['irn'] ?? $invoiceData['irn'] ?? '';
$existingAckNo = $record['ack_number'] ?? $invoiceData['ack_number'] ?? '';
$existingAckDate = $record['ack_date'] ?? $invoiceData['ack_date'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Generate E-Invoice (IRN) - Order #<?= $h($orderNumber) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    html, body { min-height: 100%; height: auto; }
    body {
      font-family: 'Inter', system-ui, sans-serif;
      overflow-y: auto !important;
      -webkit-overflow-scrolling: touch;
      touch-action: pan-y;
    }
  </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen pb-12">
  <div class="max-w-5xl mx-auto px-4 pt-8 pb-28 overflow-y-auto">
    
    <!-- Top Nav / Back -->
    <div class="mb-6 flex items-center justify-between">
      <a href="<?= $h($backUrl) ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900 transition">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        <span>Back to Payment Receipt</span>
      </a>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full border border-blue-200 bg-blue-50 text-blue-800">
          Scenario: <?= $h($scenario) ?>
        </span>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full border border-slate-200 bg-white text-slate-700">
          Total: ₹<?= $rfmt($elig['grand_total'] ?? 0) ?>
        </span>
      </div>
    </div>

    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 mb-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
          <span>E-Invoice (IRN) Generation</span>
        </h1>
        <p class="text-sm text-slate-500 mt-1">Review and collect required tax &amp; customer information to generate GST E-Invoice IRN via Alankit API.</p>
      </div>
      <div>
        <span class="text-xs text-slate-500 font-medium block">Order Reference:</span>
        <span class="text-base font-bold text-slate-800">#<?= $h($orderNumber) ?></span>
      </div>
    </div>

    <!-- Existing IRN Success Banner if already generated -->
    <?php if (!empty($existingIrn)): ?>
      <div class="bg-emerald-50 border border-emerald-300 rounded-2xl p-6 mb-6">
        <div class="flex items-start gap-3">
          <div class="p-2 bg-emerald-100 text-emerald-700 rounded-lg shrink-0">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          </div>
          <div class="flex-1">
            <h3 class="text-base font-bold text-emerald-900">E-Invoice (IRN) Already Generated</h3>
            <p class="text-xs text-emerald-700 mt-0.5">IRN is active for this order. You can review details or proceed to Generate E-Way Bill.</p>
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-emerald-900 font-mono bg-white/70 p-3 rounded-xl border border-emerald-200">
              <div><span class="font-sans font-semibold text-emerald-700">IRN:</span> <span class="break-all"><?= $h($existingIrn) ?></span></div>
              <?php if (!empty($existingAckNo)): ?><div><span class="font-sans font-semibold text-emerald-700">Ack No:</span> <?= $h($existingAckNo) ?></div><?php endif; ?>
              <?php if (!empty($existingAckDate)): ?><div><span class="font-sans font-semibold text-emerald-700">Ack Date:</span> <?= $h($existingAckDate) ?></div><?php endif; ?>
            </div>
            <div class="mt-4 flex flex-wrap gap-3">
              <a href="<?= $h($ewaybillInputUrl) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-700 text-white font-semibold text-xs hover:bg-emerald-800 transition">
                <span>Proceed to Generate E-Way Bill</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
              </a>
              <a href="<?= $h($backUrl) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white border border-emerald-300 text-emerald-800 font-semibold text-xs hover:bg-emerald-50 transition">
                <span>Back to Payment Receipt</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Result / Alert Container for AJAX -->
    <div id="resultContainer" class="hidden mb-6"></div>

    <!-- Main Form -->
    <form id="einvoiceForm" action="<?= $h($submitUrl) ?>" method="POST" class="space-y-6">
      <input type="hidden" name="order_number" value="<?= $h($orderNumber) ?>" />
      <input type="hidden" name="invoice_id" value="<?= (int)($invoiceData['id'] ?? 0) ?>" />

      <!-- Section 1: Transaction & Document Settings -->
      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-blue-600"></span>
          1. Transaction &amp; Document Classification
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Supply Type (SupTyp)</label>
            <select name="sup_typ" class="w-full h-9 rounded-lg border border-slate-300 px-3 bg-slate-50 font-medium text-slate-800 focus:bg-white focus:border-blue-500 focus:outline-none">
              <?php
              $defaultSup = $isExport ? 'EXPWP' : ($isB2b ? 'B2B' : 'B2C');
              $supOptions = ['B2B' => 'B2B - Domestic Business', 'B2C' => 'B2C - Consumer', 'EXPWP' => 'EXPWP - Export With Payment', 'EXPWOP' => 'EXPWOP - Export Without Payment', 'SEZWP' => 'SEZWP - SEZ With Payment', 'SEZWOP' => 'SEZWOP - SEZ Without Payment', 'DEXP' => 'DEXP - Deemed Export'];
              foreach ($supOptions as $k => $lbl):
              ?>
                <option value="<?= $k ?>" <?= $k === $defaultSup ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Document Type</label>
            <input type="text" name="doc_typ" value="INV" readonly class="w-full h-9 rounded-lg border border-slate-200 bg-slate-100 px-3 font-semibold text-slate-600 cursor-not-allowed" />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Document Number</label>
            <input type="text" name="doc_no" value="<?= $h($invoiceData['invoice_number'] ?? $orderNumber) ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 font-medium text-slate-800 focus:border-blue-500 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Document Date (DD/MM/YYYY)</label>
            <input type="text" name="doc_dt" value="<?= date('d/m/Y') ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 font-medium text-slate-800 focus:border-blue-500 focus:outline-none" />
          </div>
        </div>
      </div>

      <!-- Section 2: Seller & Buyer Details -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Seller -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-slate-700"></span>
            Seller (Supplier) Info
          </h2>
          <div class="space-y-3 text-xs">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Seller GSTIN</label>
              <input type="text" name="seller_gstin" value="<?= $h($firmData['gst'] ?? '07AADCE1400C1ZJ') ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 font-mono font-semibold text-slate-800" />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Legal / Trade Name</label>
              <input type="text" name="seller_name" value="<?= $h($firmData['firm_name'] ?? 'EXOTIC INDIA ART PVT LTD') ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 font-medium text-slate-800" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-slate-700 mb-1">City / Location</label>
                <input type="text" name="seller_city" value="<?= $h($firmData['city'] ?? 'New Delhi') ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 text-slate-800" />
              </div>
              <div>
                <label class="block font-semibold text-slate-700 mb-1">State Code (Stcd)</label>
                <input type="text" name="seller_state_code" value="<?= $h($firmData['state_code'] ?? '07') ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 font-mono text-slate-800" />
              </div>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Pincode</label>
              <input type="text" name="seller_pincode" value="<?= $h($firmData['pin'] ?? '110055') ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 font-mono text-slate-800" />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Address</label>
              <input type="text" name="seller_address" value="<?= $h($firmData['address'] ?? '') ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 text-slate-800" />
            </div>
          </div>
        </div>

        <!-- Buyer -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
            Buyer (Recipient) Info
          </h2>
          <div class="space-y-3 text-xs">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Buyer GSTIN (Leave 'URP' for Unregistered/Export)</label>
              <input type="text" name="buyer_gstin" value="<?= $h(!empty($orderInfo['gstin']) ? $orderInfo['gstin'] : 'URP') ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 font-mono font-semibold text-slate-800" />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Buyer Legal Name</label>
              <input type="text" name="buyer_name" value="<?= $h(trim(($orderInfo['first_name'] ?? '') . ' ' . ($orderInfo['last_name'] ?? ''))) ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 font-medium text-slate-800" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Place of Supply State (POS)</label>
                <input type="text" name="pos" value="<?= $h(!empty($orderInfo['state_code']) ? sprintf('%02d', (int)$orderInfo['state_code']) : ($isExport ? '96' : '07')) ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 font-mono text-slate-800" />
              </div>
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Buyer State Code</label>
                <input type="text" name="buyer_state_code" value="<?= $h(!empty($orderInfo['state_code']) ? sprintf('%02d', (int)$orderInfo['state_code']) : ($isExport ? '96' : '07')) ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 font-mono text-slate-800" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-slate-700 mb-1">City / Location</label>
                <input type="text" name="buyer_city" value="<?= $h($orderInfo['city'] ?? '') ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 text-slate-800" />
              </div>
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Pincode</label>
                <input type="text" name="buyer_pincode" value="<?= $h(!empty($orderInfo['zipcode']) ? $orderInfo['zipcode'] : ($isExport ? '999999' : '110001')) ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 font-mono text-slate-800" />
              </div>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Address Line 1</label>
              <input type="text" name="buyer_address" value="<?= $h(trim(($orderInfo['address_line1'] ?? '') . ' ' . ($orderInfo['address_line2'] ?? ''))) ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 text-slate-800" />
            </div>
          </div>
        </div>
      </div>

      <!-- Section 3: Export Details (If Export) -->
      <?php if ($isExport): ?>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 shadow-sm">
          <h2 class="text-sm font-bold uppercase tracking-wider text-amber-900 mb-4 pb-2 border-b border-amber-200 flex items-center gap-2">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
            Export Information (Required for International / SEZ Invoices)
          </h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs">
            <div>
              <label class="block font-semibold text-amber-900 mb-1">Shipping Bill Number</label>
              <input type="text" name="shipping_bill_number" value="<?= $h($invoiceData['shipping_bill_number'] ?? 'SB-' . rand(100000, 999999)) ?>" class="w-full h-9 rounded-lg border border-amber-300 px-3 font-mono bg-white" />
            </div>
            <div>
              <label class="block font-semibold text-amber-900 mb-1">Shipping Bill Date (DD/MM/YYYY)</label>
              <input type="text" name="shipping_bill_date" value="<?= date('d/m/Y') ?>" class="w-full h-9 rounded-lg border border-amber-300 px-3 bg-white" />
            </div>
            <div>
              <label class="block font-semibold text-amber-900 mb-1">Port Code</label>
              <input type="text" name="shipping_port_code" value="<?= $h($invoiceData['shipping_port'] ?? 'INABG1') ?>" class="w-full h-9 rounded-lg border border-amber-300 px-3 font-mono bg-white" />
            </div>
            <div>
              <label class="block font-semibold text-amber-900 mb-1">Foreign Currency Code</label>
              <input type="text" name="shipping_currency" value="<?= $h($invoiceData['currency'] ?? 'USD') ?>" class="w-full h-9 rounded-lg border border-amber-300 px-3 font-mono bg-white" />
            </div>
            <div>
              <label class="block font-semibold text-amber-900 mb-1">Country Code (2 Chars ISO)</label>
              <input type="text" name="shipping_country_code" value="<?= $h(!empty($orderInfo['country']) ? substr(strtoupper($orderInfo['country']), 0, 2) : 'US') ?>" class="w-full h-9 rounded-lg border border-amber-300 px-3 font-mono bg-white" />
            </div>
            <div>
              <label class="block font-semibold text-amber-900 mb-1">Refund Claim (RefClm)</label>
              <select name="shipping_ref_clm" class="w-full h-9 rounded-lg border border-amber-300 px-3 bg-white">
                <option value="N">N - No Refund Claimed</option>
                <option value="Y">Y - Refund Claimed</option>
              </select>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Section 4: Line Items Review -->
      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-slate-800"></span>
          Line Items &amp; Tax Values
        </h2>
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 uppercase font-semibold">
                <th class="p-2 border border-slate-200">#</th>
                <th class="p-2 border border-slate-200">Description</th>
                <th class="p-2 border border-slate-200">HSN</th>
                <th class="p-2 border border-slate-200 text-right">Qty</th>
                <th class="p-2 border border-slate-200 text-right">Unit Price</th>
                <th class="p-2 border border-slate-200 text-right">GST Rate</th>
                <th class="p-2 border border-slate-200 text-right">Tax Amt</th>
                <th class="p-2 border border-slate-200 text-right">Total (₹)</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $totalTaxable = 0.0;
              $totalTax = 0.0;
              $grandVal = 0.0;
              foreach ($itemsList as $idx => $it):
                  $qty = (float)($it['quantity'] ?? 1);
                  $unitPrice = (float)($it['unit_price'] ?? 0);
                  $taxRate = (float)($it['tax_rate'] ?? 0);
                  $lineTaxable = $qty * $unitPrice;
                  $lineTax = (float)($it['tax_amount'] ?? ($lineTaxable * $taxRate / 100));
                  $lineTotal = (float)($it['line_total'] ?? ($lineTaxable + $lineTax));
                  $totalTaxable += $lineTaxable;
                  $totalTax += $lineTax;
                  $grandVal += $lineTotal;
              ?>
                <tr class="border-b border-slate-200 hover:bg-slate-50">
                  <td class="p-2 border border-slate-200 text-center font-medium"><?= $idx + 1 ?></td>
                  <td class="p-2 border border-slate-200 font-medium"><?= $h($it['item_name'] ?? $it['title'] ?? 'Product Item') ?></td>
                  <td class="p-2 border border-slate-200 font-mono"><?= $h(substr($it['hsn'] ?? '1001', 0, 8)) ?></td>
                  <td class="p-2 border border-slate-200 text-right font-mono"><?= $qty ?></td>
                  <td class="p-2 border border-slate-200 text-right font-mono">₹<?= $rfmt($unitPrice) ?></td>
                  <td class="p-2 border border-slate-200 text-right font-mono"><?= $taxRate ?>%</td>
                  <td class="p-2 border border-slate-200 text-right font-mono">₹<?= $rfmt($lineTax) ?></td>
                  <td class="p-2 border border-slate-200 text-right font-bold text-slate-900">₹<?= $rfmt($lineTotal) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="bg-slate-50 font-bold text-slate-900 text-xs">
                <td colspan="4" class="p-2 border border-slate-200 text-right">Totals:</td>
                <td class="p-2 border border-slate-200 text-right font-mono">Taxable: ₹<?= $rfmt($totalTaxable) ?></td>
                <td class="p-2 border border-slate-200"></td>
                <td class="p-2 border border-slate-200 text-right font-mono">Tax: ₹<?= $rfmt($totalTax) ?></td>
                <td class="p-2 border border-slate-200 text-right font-mono text-sm text-blue-700">₹<?= $rfmt($elig['grand_total'] ?? $grandVal) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-slate-200">
        <a href="<?= $h($backUrl) ?>" class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 font-semibold text-xs hover:bg-slate-100 transition">
          Cancel &amp; Return to Receipt
        </a>
        <button type="submit" id="submitBtn" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-blue-600 text-white font-bold text-sm shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
          <span id="submitBtnText">Submit &amp; Generate E-Invoice (IRN)</span>
        </button>
      </div>

    </form>
  </div>

  <!-- <div class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-8px_24px_rgba(15,23,42,0.08)] backdrop-blur-sm">
    <!-- floating submit removed -->
    <!--  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
  </div> -->

  <script>
    document.getElementById('einvoiceForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const form = this;
      const submitBtn = document.getElementById('submitBtn');
      const submitBtnText = document.getElementById('submitBtnText');
      const resultContainer = document.getElementById('resultContainer');
      const escapeResultHtml = function(value) {
        return String(value ?? '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      };
      const scrollToResult = function() {
        // Wait a frame so the banner is rendered and offsets are measurable.
        requestAnimationFrame(function() {
          try {
            resultContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
          } catch (e) {
            resultContainer.scrollIntoView();
          }
          // When this page is embedded in an iframe, also scroll the parent
          // window so the banner at the top becomes visible.
          try {
            if (window.parent && window.parent !== window) {
              const rect = resultContainer.getBoundingClientRect();
              const frameTop = window.frameElement ? window.frameElement.getBoundingClientRect().top : 0;
              window.parent.scrollTo({ top: window.parent.scrollY + rect.top + frameTop - 20, behavior: 'smooth' });
            }
          } catch (e) { /* cross-origin parent: ignore */ }
        });
      };

      submitBtn.disabled = true;
      submitBtnText.textContent = 'Generating E-Invoice via API...';
      resultContainer.classList.add('hidden');

      try {
        const formData = new FormData(form);
        const res = await fetch(form.action, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (data.success) {
          resultContainer.className = 'bg-emerald-50 border border-emerald-300 rounded-2xl p-6 mb-6';
          resultContainer.innerHTML = `
            <div class="flex items-start gap-3">
              <div class="p-2 bg-emerald-100 text-emerald-700 rounded-lg shrink-0">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
              </div>
              <div class="flex-1">
                <h3 class="text-base font-bold text-emerald-900">${data.message || 'E-Invoice (IRN) Generated Successfully!'}</h3>
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-emerald-900 font-mono bg-white/70 p-3 rounded-xl border border-emerald-200">
                  <div><span class="font-sans font-semibold text-emerald-700">IRN:</span> <span class="break-all">${data.irn || 'N/A'}</span></div>
                  <div><span class="font-sans font-semibold text-emerald-700">Ack No:</span> ${data.ack_number || 'N/A'}</div>
                  <div><span class="font-sans font-semibold text-emerald-700">Ack Date:</span> ${data.ack_date || 'N/A'}</div>
                </div>
                <div class="mt-4 flex flex-wrap gap-3">
                  <a href="${data.ewaybill_url || '#'}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-700 text-white font-semibold text-xs hover:bg-emerald-800 transition">
                    <span>Proceed to Generate E-Way Bill</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                  </a>
                  <a href="<?= $h($backUrl) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white border border-emerald-300 text-emerald-800 font-semibold text-xs hover:bg-emerald-50 transition">
                    <span>Return to Payment Receipt</span>
                  </a>
                </div>
              </div>
            </div>
          `;
          resultContainer.classList.remove('hidden');
          scrollToResult();
        } else {
          resultContainer.className = 'bg-rose-50 border border-rose-300 rounded-2xl p-6 mb-6';
          resultContainer.innerHTML = `
            <div class="flex items-start gap-3">
              <div class="p-2 bg-rose-100 text-rose-700 rounded-lg shrink-0">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-rose-900">E-Invoice Generation Failed</h3>
                <p class="text-xs text-rose-700 mt-1">${escapeResultHtml(data.message || 'Error occurred while contacting Alankit API.')}</p>
                ${data.error_details ? `<div class="mt-3 rounded-lg border border-rose-200 bg-white/70 p-3"><div class="text-xs font-semibold text-rose-800">Alankit ErrorDetails</div><pre class="mt-1 whitespace-pre-wrap break-words text-xs text-rose-900">${escapeResultHtml(data.error_details)}</pre></div>` : ''}
              </div>
            </div>
          `;
          resultContainer.classList.remove('hidden');
          scrollToResult();
        }
      } catch (err) {
        resultContainer.className = 'bg-rose-50 border border-rose-300 rounded-2xl p-6 mb-6';
        resultContainer.innerHTML = `
          <div class="text-xs text-rose-800 font-semibold">Network error during E-Invoice generation. Please check server logs and try again.</div>
        `;
        resultContainer.classList.remove('hidden');
        scrollToResult();
      } finally {
        submitBtn.disabled = false;
        submitBtnText.textContent = 'Submit & Generate E-Invoice (IRN)';
      }
    });
  </script>
</body>
</html>
