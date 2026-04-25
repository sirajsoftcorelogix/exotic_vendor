<div class="min-h-screen bg-slate-50">
  <div class="mx-auto max-w-4xl px-4 py-8">
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-200 px-6 py-5">
        <h1 class="text-xl font-semibold text-slate-900">Order Confirmation</h1>
        <p class="mt-1 text-sm text-slate-600">Payment is completed and order is created successfully.</p>
      </div>

      <div class="p-6 space-y-6">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
          <?php if (($import_status ?? '') === 'success'): ?>
            Order imported successfully and invoice is ready.
          <?php elseif (($import_status ?? '') === 'failed'): ?>
            Order created, but import/invoice creation did not confirm. You can still open invoice preview.
          <?php else: ?>
            Order created successfully.
          <?php endif; ?>
        </div>

        <div id="paymentReceiptSection" class="rounded-xl border border-slate-200">
          <div class="border-b border-slate-200 px-4 py-3">
            <h2 class="text-base font-semibold text-slate-900">Payment Receipt</h2>
          </div>
          <div class="grid grid-cols-1 gap-3 px-4 py-4 text-sm sm:grid-cols-2">
            <div>
              <div class="text-slate-500">Order ID</div>
              <div class="font-semibold text-slate-900"><?= htmlspecialchars((string)($order_id ?? '-')) ?></div>
            </div>
            <div>
              <div class="text-slate-500">Payment Mode</div>
              <div class="font-semibold text-slate-900"><?= htmlspecialchars((string)($payment_type ?? '-')) ?></div>
            </div>
            <div>
              <div class="text-slate-500">Payment Stage</div>
              <div class="font-semibold text-slate-900"><?= htmlspecialchars((string)($payment_stage ?? '-')) ?></div>
            </div>
            <div>
              <div class="text-slate-500">Amount</div>
              <div class="font-semibold text-slate-900"><?= htmlspecialchars((string)($amount ?? '-')) ?></div>
            </div>
            <div class="sm:col-span-2">
              <div class="text-slate-500">Transaction ID</div>
              <div class="font-semibold text-slate-900 break-all"><?= htmlspecialchars((string)($transaction_id ?? '-')) ?></div>
            </div>
          </div>
        </div>

        <div class="no-print flex flex-wrap gap-3">
          <button
            type="button"
            onclick="printPaymentReceipt()"
            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            Print Payment Receipt
          </button>

          <a
            href="<?= htmlspecialchars((string)($payment_history_url ?? 'index.php?page=orders&action=list')) ?>"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Payment History
          </a>

          <a
            href="<?= htmlspecialchars((string)($invoice_preview_url ?? '#')) ?>"
            target="_blank"
            class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
            Print Invoice
          </a>

          <a
            href="index.php?page=pos_register&action=list"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Back to POS
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  @media print {
    .no-print {
      display: none !important;
    }
    body * {
      visibility: hidden;
    }
    #paymentReceiptSection,
    #paymentReceiptSection * {
      visibility: visible;
    }
    #paymentReceiptSection {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
      border: none;
    }
  }
</style>

<script>
  function printPaymentReceipt() {
    window.print();
  }
</script>
