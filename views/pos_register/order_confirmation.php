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

        <div id="paymentReceiptSection" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-md print:shadow-none">
          <!-- Branded header -->
          <div class="relative bg-gradient-to-br from-emerald-800 via-emerald-700 to-teal-800 px-6 py-6 text-white print:bg-emerald-800">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
              <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-100/90">Exotic India</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-white">Payment Receipt</h2>
                <p class="mt-1 text-sm text-emerald-100">Seller POS · Official payment acknowledgement</p>
              </div>
              <div class="shrink-0 rounded-xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur-sm">
                <div class="text-[10px] font-semibold uppercase tracking-wide text-emerald-100">Payment mode</div>
                <div class="mt-0.5 text-lg font-bold text-white"><?= htmlspecialchars((string)($payment_mode_label ?? $payment_type ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="mt-2 text-[10px] uppercase tracking-wide text-emerald-100">Code</div>
                <div class="font-mono text-xs text-emerald-50"><?= htmlspecialchars((string)($payment_type ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>
            <div class="pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white/5"></div>
          </div>

          <!-- Receipt meta strip -->
          <div class="grid gap-4 border-b border-slate-100 bg-slate-50/80 px-6 py-4 sm:grid-cols-3">
            <div>
              <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Receipt number</div>
              <div class="mt-1 font-mono text-sm font-bold text-slate-900"><?= htmlspecialchars((string)($receipt_number ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
              <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Receipt date (IST)</div>
              <div class="mt-1 font-semibold text-slate-900"><?= htmlspecialchars((string)($receipt_date_formatted ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="sm:text-right">
              <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Receipt location</div>
              <div class="mt-1 font-semibold text-slate-900"><?= htmlspecialchars((string)($warehouse_name ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
          </div>

          <!-- Details grid -->
          <div class="grid grid-cols-1 gap-0 divide-y divide-slate-100 sm:grid-cols-2 sm:divide-y-0">
            <div class="flex items-center justify-between gap-4 px-6 py-4 sm:border-r sm:border-slate-100">
              <span class="text-sm text-slate-500">Order ID</span>
              <span class="text-right font-semibold text-slate-900"><?= htmlspecialchars((string)($order_id ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="flex items-center justify-between gap-4 px-6 py-4">
              <span class="text-sm text-slate-500">Amount</span>
              <span class="text-right text-lg font-bold text-emerald-800">
                <?php
                  $amtRaw = trim((string)($amount ?? ''));
                  if ($amtRaw !== '' && is_numeric($amtRaw)) {
                      echo '₹' . htmlspecialchars(number_format((float)$amtRaw, 2, '.', ','), ENT_QUOTES, 'UTF-8');
                  } else {
                      echo htmlspecialchars($amtRaw !== '' ? $amtRaw : '—', ENT_QUOTES, 'UTF-8');
                  }
                ?>
              </span>
            </div>
            <div class="flex items-center justify-between gap-4 px-6 py-4 sm:border-r sm:border-t sm:border-slate-100">
              <span class="text-sm text-slate-500">Payment stage</span>
              <span class="text-right font-semibold capitalize text-slate-900"><?= htmlspecialchars((string)($payment_stage ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="flex items-center justify-between gap-4 px-6 py-4 sm:border-t sm:border-slate-100">
              <span class="text-sm text-slate-500">Transaction ID</span>
              <span class="max-w-[55%] break-all text-right font-mono text-xs font-semibold text-slate-900">
                <?= htmlspecialchars((string)(trim((string)($transaction_id ?? '')) !== '' ? $transaction_id : '—'), ENT_QUOTES, 'UTF-8') ?>
              </span>
            </div>
          </div>

          <div class="border-t border-slate-100 bg-slate-50/50 px-6 py-3 text-center text-[11px] text-slate-500">
            Thank you for your purchase · Exotic India
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
      box-shadow: none !important;
    }
  }
</style>

<script>
  function printPaymentReceipt() {
    window.print();
  }
</script>
