<?php
$h = static function ($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$rfmt = static function ($n, int $dec = 2): string {
    return number_format((float)$n, $dec, '.', ',');
};

$orderInfo = $order_info ?? [];
$invoiceData = $invoice ?? [];
$firmData = $firm ?? [];
$record = $existing_record ?? [];
$elig = $eligibility ?? [];

$orderNumber = $order_number ?? '';
$submitUrl = trim((string)($submit_url ?? ''));
if ($submitUrl === '') {
  $submitUrl = 'index.php?page=pos_register&action=ewaybill-submit';
}
$backUrl = trim((string)($back_url ?? ''));
if ($backUrl === '') {
  $backUrl = 'index.php?page=pos_register&action=checkout-receipt&order_number=' . rawurlencode($orderNumber);
}
$isExport = !empty($elig['is_export']);
$isB2b = !empty($elig['is_b2b']);
$scenario = $elig['scenario'] ?? ($isExport ? 'Export' : ($isB2b ? 'Domestic B2B' : 'B2C'));

$existingIrn = $record['irn'] ?? $invoiceData['irn'] ?? '';
$existingEwb = $record['ewb_no'] ?? $record['ewb'] ?? $invoiceData['ewb_number'] ?? '';
$existingEwbDate = $record['ewb_date'] ?? $invoiceData['ewb_date'] ?? '';
$existingEwbValid = $record['ewb_valid_till'] ?? '';
$existingTransId = $record['trans_id'] ?? '';
$existingTransName = $record['trans_name'] ?? '';

$invoiceCurrency = strtoupper(trim((string)($invoiceData['currency'] ?? $orderInfo['currency'] ?? 'INR')));
if ($invoiceCurrency === '') {
  $invoiceCurrency = 'INR';
}
$currencyPrefix = $invoiceCurrency === 'INR' ? '₹' : $invoiceCurrency . ' ';
?>

  <div class="max-w-5xl mx-auto px-4 py-8">
    
    <!-- Top Nav / Back -->
    <div class="mb-6 flex items-center justify-between">
      <a href="<?= $h($backUrl) ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900 transition">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        <span>Back to Payment Receipt</span>
      </a>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full border border-emerald-200 bg-emerald-50 text-emerald-800">
          Scenario: <?= $h($scenario) ?>
        </span>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full border border-slate-200 bg-white text-slate-700">
          Total: <?= $h($currencyPrefix) ?><?= $rfmt($elig['grand_total'] ?? 0) ?>
        </span>
      </div>
    </div>

    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 mb-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-600"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
          <span>E-Way Bill (EWB) Generation</span>
        </h1>
        <p class="text-sm text-slate-500 mt-1">Collect transport mode, vehicle details, distance, and dispatch info to generate GST E-Way Bill via Alankit API.</p>
      </div>
      <div>
        <span class="text-xs text-slate-500 font-medium block">Order Reference:</span>
        <span class="text-base font-bold text-slate-800">#<?= $h($orderNumber) ?></span>
      </div>
    </div>

    <!-- Existing EWB Success Banner if already generated -->
    <?php if (!empty($existingEwb)): ?>
      <div class="bg-emerald-50 border border-emerald-300 rounded-2xl p-6 mb-6">
        <div class="flex items-start gap-3">
          <div class="p-2 bg-emerald-100 text-emerald-700 rounded-lg shrink-0">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          </div>
          <div class="flex-1">
            <h3 class="text-base font-bold text-emerald-900">E-Way Bill Already Generated</h3>
            <p class="text-xs text-emerald-700 mt-0.5">E-Way Bill is active for this shipment.</p>
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs text-emerald-900 font-mono bg-white/70 p-3 rounded-xl border border-emerald-200">
              <div><span class="font-sans font-semibold text-emerald-700">EWB No:</span> <?= $h($existingEwb) ?></div>
              <?php if (!empty($existingEwbDate)): ?><div><span class="font-sans font-semibold text-emerald-700">Date:</span> <?= $h($existingEwbDate) ?></div><?php endif; ?>
              <?php if (!empty($existingEwbValid)): ?><div><span class="font-sans font-semibold text-emerald-700">Valid Till:</span> <?= $h($existingEwbValid) ?></div><?php endif; ?>
            </div>
            <div class="mt-4 flex flex-wrap gap-3">
              <a href="<?= $h($backUrl) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-700 text-white font-semibold text-xs hover:bg-emerald-800 transition">
                <span>Return to Payment Receipt</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Result / Alert Container for AJAX -->
    <div id="resultContainer" class="hidden mb-6"></div>

    <!-- Main Form -->
    <form id="ewaybillForm" action="<?= $h($submitUrl) ?>" method="POST" class="space-y-6">
      <input type="hidden" name="order_number" value="<?= $h($orderNumber) ?>" />
      <input type="hidden" name="invoice_id" value="<?= (int)($invoiceData['id'] ?? 0) ?>" />

      <!-- Section 1: IRN / Document Reference -->
      <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 shadow-sm">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 mb-4 pb-2 border-b border-slate-200 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
          1. IRN / Document Reference (Locked - Auto-filled from Invoice)
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">IRN Number (If Generated)</label>
            <input type="text" name="irn" value="<?= $h($existingIrn) ?>" placeholder="IRN String" readonly class="w-full h-9 rounded-lg border border-slate-200 bg-slate-100/70 px-3 font-mono text-slate-700 cursor-not-allowed" />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Document / Invoice Number</label>
            <input type="text" name="doc_no" value="<?= $h($invoiceData['invoice_number'] ?? $orderNumber) ?>" readonly class="w-full h-9 rounded-lg border border-slate-200 bg-slate-100/70 px-3 font-medium text-slate-700 cursor-not-allowed" />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Document Date (DD/MM/YYYY)</label>
            <?php
            $docDateFormatted = !empty($invoiceData['invoice_date']) ? date('d/m/Y', strtotime($invoiceData['invoice_date'])) : date('d/m/Y');
            ?>
            <input type="text" name="doc_dt" value="<?= $h($docDateFormatted) ?>" readonly class="w-full h-9 rounded-lg border border-slate-200 bg-slate-100/70 px-3 font-medium text-slate-700 cursor-not-allowed" />
          </div>
        </div>
      </div>

      <!-- Section 2: Transport & Vehicle Information -->
      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="mb-4 pb-2 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
            2. Transport &amp; Vehicle Information
          </h2>
          <!-- Selection Toggle -->
          <div class="inline-flex rounded-lg bg-slate-100 p-1 text-xs">
            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-md px-3 py-1 has-[:checked]:bg-white has-[:checked]:font-bold has-[:checked]:shadow-sm text-slate-700">
              <input type="radio" name="transport_selection" value="courier" <?= (trim((string)$existingTransId) !== '' || empty($record['veh_no'])) ? 'checked' : '' ?> class="h-3.5 w-3.5 text-emerald-600" data-ewb-transport-selection />
              <span>Transporter / Courier (Trans ID)</span>
            </label>
            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-md px-3 py-1 has-[:checked]:bg-white has-[:checked]:font-bold has-[:checked]:shadow-sm text-slate-700">
              <input type="radio" name="transport_selection" value="vehicle" <?= (trim((string)$existingTransId) === '' && !empty($record['veh_no'])) ? 'checked' : '' ?> class="h-3.5 w-3.5 text-emerald-600" data-ewb-transport-selection />
              <span>Vehicle / Self Transport</span>
            </label>
          </div>
        </div>

        <!-- Transporter / Courier Fields -->
        <div id="ewbTransporterFields" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Registered Transporter / Courier</label>
            <select id="ewbTransporterSelect" class="w-full h-9 rounded-lg border border-slate-300 px-3 bg-slate-50 font-medium text-slate-800 focus:bg-white focus:border-emerald-500 focus:outline-none">
              <option value="">-- Select Courier / Transporter --</option>
              <?php foreach (($eway_transporters ?? []) as $ewayTrans): ?>
                <?php
                $tName = trim((string)($ewayTrans['trans_name'] ?? ''));
                $tGstin = strtoupper(trim((string)($ewayTrans['gstin'] ?? '')));
                if ($tName === '' || $tGstin === '') continue;
                $tSelected = (strcasecmp($existingTransId, $tGstin) === 0);
                ?>
                <option value="<?= $h($tGstin) ?>" data-trans-name="<?= $h($tName) ?>" <?= $tSelected ? 'selected' : '' ?>>
                  <?= $h($tName) ?> (<?= $h($tGstin) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Transporter ID (GSTIN / TransID)</label>
            <input type="text" id="ewbTransIdInput" name="trans_id" value="<?= $h($existingTransId) ?>" placeholder="e.g. 07AADCE1400C1ZJ" class="w-full h-9 rounded-lg border border-slate-300 px-3 font-mono font-semibold text-slate-800 focus:border-emerald-500 focus:outline-none" />
          </div>
          <div class="sm:col-span-2">
            <label class="block font-semibold text-slate-700 mb-1">Transporter Name</label>
            <input type="text" id="ewbTransNameInput" name="trans_name" value="<?= $h($existingTransName) ?>" placeholder="e.g. BlueDart / Delhivery / DHL" class="w-full h-9 rounded-lg border border-slate-300 px-3 text-slate-800 focus:border-emerald-500 focus:outline-none" />
          </div>
        </div>

        <!-- Vehicle / Self Transport Fields -->
        <div id="ewbVehicleFields" class="hidden grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Transport Mode</label>
            <select name="trans_mode" class="w-full h-9 rounded-lg border border-slate-300 px-3 bg-slate-50 font-medium text-slate-800 focus:bg-white focus:border-emerald-500 focus:outline-none">
              <option value="1">1 - Road</option>
              <option value="2">2 - Rail</option>
              <option value="3">3 - Air</option>
              <option value="4">4 - Ship / Road cum Ship</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Vehicle Number (VehNo)</label>
            <input type="text" name="veh_no" value="<?= $h($record['veh_no'] ?? '') ?>" placeholder="e.g. DL01AB1234" class="w-full h-9 rounded-lg border border-slate-300 px-3 font-mono font-semibold text-slate-800 focus:border-emerald-500 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Vehicle Type (VehType)</label>
            <select name="veh_type" class="w-full h-9 rounded-lg border border-slate-300 px-3 bg-slate-50 font-medium text-slate-800 focus:bg-white focus:border-emerald-500 focus:outline-none">
              <option value="R">R - Regular</option>
              <option value="O">O - Over Dimensional Cargo (ODC)</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Transport Doc / LR No</label>
            <input type="text" name="trans_doc_no" value="<?= $h($record['trans_doc_no'] ?? '') ?>" placeholder="LR / AWB Number" class="w-full h-9 rounded-lg border border-slate-300 px-3 font-mono text-slate-800" />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Transport Doc Date</label>
            <input type="text" name="trans_doc_dt" value="<?= $h(!empty($record['trans_doc_dt']) ? $record['trans_doc_dt'] : date('d/m/Y')) ?>" class="w-full h-9 rounded-lg border border-slate-300 px-3 text-slate-800" />
          </div>
        </div>
      </div>

      <!-- Section 3: Dispatch & Ship-To Addresses -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Dispatch From -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
            Dispatch From (DispDtls)
          </h2>
          <div class="space-y-3 text-xs">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Dispatcher Name</label>
              <input type="text" name="disp_name" value="<?= $h($firmData['firm_name'] ?? 'EXOTIC INDIA ART PVT LTD') ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 text-slate-800" />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Address Line 1</label>
              <input type="text" name="disp_address" value="<?= $h($firmData['address'] ?? '') ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 text-slate-800" />
            </div>
            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Location / City</label>
                <input type="text" name="disp_location" value="<?= $h($firmData['city'] ?? 'New Delhi') ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 text-slate-800" />
              </div>
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Pincode</label>
                <input type="text" name="disp_pincode" value="<?= $h($firmData['pin'] ?? '110055') ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 font-mono text-slate-800" />
              </div>
              <div>
                <label class="block font-semibold text-slate-700 mb-1">State Code</label>
                <?php
                $dispGstin = trim((string)($firmData['gst'] ?? $firmData['gstin'] ?? '07AADCE1400C1ZJ'));
                $dispStcd = '';
                if (strlen($dispGstin) >= 2 && ctype_digit(substr($dispGstin, 0, 2)) && (int)substr($dispGstin, 0, 2) > 0) {
                    $dispStcd = sprintf('%02d', (int)substr($dispGstin, 0, 2));
                } else {
                    $rawSt = (int)($firmData['state_code'] ?? 7);
                    $dispStcd = sprintf('%02d', $rawSt > 0 ? $rawSt : 7);
                }
                ?>
                <input type="text" name="disp_state_code" value="<?= $h($dispStcd) ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 font-mono text-slate-800" />
              </div>
            </div>
          </div>
        </div>

        <!-- Ship To -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
            Ship To Recipient (ExpShipDtls)
          </h2>
          <div class="space-y-3 text-xs">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Recipient Name</label>
              <input type="text" name="ship_name" value="<?= $h(trim(($orderInfo['shipping_first_name'] ?? $orderInfo['first_name'] ?? '') . ' ' . ($orderInfo['shipping_last_name'] ?? $orderInfo['last_name'] ?? ''))) ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 text-slate-800" />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Address Line 1</label>
              <input type="text" name="ship_address" value="<?= $h(trim(($orderInfo['shipping_address_line1'] ?? $orderInfo['address_line1'] ?? '') . ' ' . ($orderInfo['shipping_address_line2'] ?? $orderInfo['address_line2'] ?? ''))) ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 text-slate-800" />
            </div>
            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Location / City</label>
                <input type="text" name="ship_location" value="<?= $h($orderInfo['shipping_city'] ?? $orderInfo['city'] ?? '') ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 text-slate-800" />
              </div>
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Pincode</label>
                <input type="text" name="ship_pincode" value="<?= $h(!empty($orderInfo['shipping_zipcode']) ? $orderInfo['shipping_zipcode'] : (!empty($orderInfo['zipcode']) ? $orderInfo['zipcode'] : ($isExport ? '999999' : '110001'))) ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 font-mono text-slate-800" />
              </div>
              <div>
                <label class="block font-semibold text-slate-700 mb-1">State Code</label>
                <input type="text" name="ship_state_code" value="<?= $h(!empty($orderInfo['shipping_state_code']) ? sprintf('%02d', (int)$orderInfo['shipping_state_code']) : (!empty($orderInfo['state_code']) ? sprintf('%02d', (int)$orderInfo['state_code']) : ($isExport ? '96' : '07'))) ?>" required class="w-full h-9 rounded-lg border border-slate-300 px-3 font-mono text-slate-800" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-slate-200">
        <a href="<?= $h($backUrl) ?>" class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 font-semibold text-xs hover:bg-slate-100 transition">
          Cancel &amp; Return to Receipt
        </a>
        <button type="submit" id="submitBtn" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-emerald-600 text-white font-bold text-sm shadow-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
          <span id="submitBtnText">Submit &amp; Generate E-Way Bill</span>
        </button>
      </div>

    </form>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const ewbTransporterFields = document.getElementById('ewbTransporterFields');
      const ewbVehicleFields = document.getElementById('ewbVehicleFields');
      const ewbTransporterSelect = document.getElementById('ewbTransporterSelect');
      const ewbTransIdInput = document.getElementById('ewbTransIdInput');
      const ewbTransNameInput = document.getElementById('ewbTransNameInput');

      function applyEwbTransportSelection(mode) {
        const isCourier = (mode === 'courier');
        if (ewbTransporterFields) {
          ewbTransporterFields.classList.toggle('hidden', !isCourier);
          ewbTransporterFields.querySelectorAll('input, select').forEach(el => el.disabled = !isCourier);
        }
        if (ewbVehicleFields) {
          ewbVehicleFields.classList.toggle('hidden', isCourier);
          ewbVehicleFields.querySelectorAll('input, select').forEach(el => el.disabled = isCourier);
        }
      }

      document.querySelectorAll('[data-ewb-transport-selection]').forEach(radio => {
        radio.addEventListener('change', function () {
          applyEwbTransportSelection(this.value);
        });
      });

      const activeRadio = document.querySelector('[data-ewb-transport-selection]:checked');
      applyEwbTransportSelection(activeRadio ? activeRadio.value : 'courier');

      ewbTransporterSelect?.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        if (selected && selected.value) {
          if (ewbTransIdInput) ewbTransIdInput.value = selected.value;
          if (ewbTransNameInput) ewbTransNameInput.value = selected.dataset.transName || '';
        }
      });
    });

    document.getElementById('ewaybillForm').addEventListener('submit', async function(e) {
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
      submitBtnText.textContent = 'Generating E-Way Bill via Alankit API...';
      resultContainer.classList.add('hidden');

      try {
        const formData = new FormData(form);
        const res = await fetch(form.action, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        let data;
        const text = await res.text();
        try {
          data = JSON.parse(text);
        } catch (jsonErr) {
          data = {
            success: false,
            message: 'Server returned a non-JSON response.',
            error_details: text || ('HTTP ' + res.status + ' ' + res.statusText)
          };
        }

        if (data.success) {
          resultContainer.className = 'bg-emerald-50 border border-emerald-300 rounded-2xl p-6 mb-6';
          resultContainer.innerHTML = `
            <div class="flex items-start gap-3">
              <div class="p-2 bg-emerald-100 text-emerald-700 rounded-lg shrink-0">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
              </div>
              <div class="flex-1">
                <h3 class="text-base font-bold text-emerald-900">${data.message || 'E-Way Bill Generated Successfully!'}</h3>
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs text-emerald-900 font-mono bg-white/70 p-3 rounded-xl border border-emerald-200">
                  <div><span class="font-sans font-semibold text-emerald-700">EWB No:</span> ${data.ewb_number || 'N/A'}</div>
                  <div><span class="font-sans font-semibold text-emerald-700">Generated Date:</span> ${data.ewb_date || 'N/A'}</div>
                  <div><span class="font-sans font-semibold text-emerald-700">Valid Till:</span> ${data.ewb_valid_till || 'N/A'}</div>
                </div>
                <div class="mt-4 flex flex-wrap gap-3">
                  <a href="<?= $h($backUrl) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-700 text-white font-semibold text-xs hover:bg-emerald-800 transition">
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
          const errDetail = data.error_details || data.errors || '';
          const detailStr = typeof errDetail === 'object' ? JSON.stringify(errDetail, null, 2) : String(errDetail || '');
          resultContainer.innerHTML = `
            <div class="flex items-start gap-3">
              <div class="p-2 bg-rose-100 text-rose-700 rounded-lg shrink-0">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-rose-900">E-Way Bill Generation Failed</h3>
                <p class="text-xs text-rose-700 mt-1">${escapeResultHtml(data.message || 'Error occurred while contacting Alankit API.')}</p>
                ${detailStr ? `<div class="mt-3 rounded-lg border border-rose-200 bg-white/70 p-3"><div class="text-xs font-semibold text-rose-800">Alankit ErrorDetails</div><pre class="mt-1 whitespace-pre-wrap break-words text-xs text-rose-900">${escapeResultHtml(detailStr)}</pre></div>` : ''}
              </div>
            </div>
          `;
          resultContainer.classList.remove('hidden');
          scrollToResult();
        }
      } catch (err) {
        resultContainer.className = 'bg-rose-50 border border-rose-300 rounded-2xl p-6 mb-6';
        const errMsg = err && err.message ? err.message : String(err);
        resultContainer.innerHTML = `
          <div class="flex items-start gap-3">
            <div class="p-2 bg-rose-100 text-rose-700 rounded-lg shrink-0">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <div>
              <h3 class="text-base font-bold text-rose-900">Request Error</h3>
              <p class="text-xs text-rose-700 mt-1">${escapeResultHtml(errMsg)}</p>
            </div>
          </div>
        `;
        resultContainer.classList.remove('hidden');
        scrollToResult();
      } finally {
        submitBtn.disabled = false;
        submitBtnText.textContent = 'Submit & Generate E-Way Bill';
      }
    });
  </script>

