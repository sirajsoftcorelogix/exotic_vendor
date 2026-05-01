<?php
$rfmt = static function ($n, int $dec = 2): string {
    return number_format((float)$n, $dec, '.', ',');
};
$h = static function ($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$lines = isset($receipt_lines) && is_array($receipt_lines) ? $receipt_lines : [];
?>
<div class="min-h-screen bg-slate-50">
  <div class="mx-auto max-w-5xl px-4 py-8">
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-200 px-6 py-5 no-print">
        <h1 class="text-xl font-semibold text-slate-900">Order Confirmation</h1>
        <p class="mt-1 text-sm text-slate-600">Payment is completed and order is created successfully.</p>
      </div>

      <div class="p-6 space-y-6">
        <div class="no-print rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
          <?php if (($import_status ?? '') === 'success'): ?>
            Order imported successfully and invoice is ready.
          <?php elseif (($import_status ?? '') === 'failed'): ?>
            Order created, but import/invoice creation did not confirm. You can still open invoice preview — receipt line items may be incomplete until import succeeds.
          <?php else: ?>
            Order created successfully.
          <?php endif; ?>
        </div>

        <!-- Printable receipt -->
        <div id="paymentReceiptSection" class="receipt-sheet mx-auto w-full max-w-[210mm] overflow-hidden rounded-lg border border-slate-900 bg-white text-black shadow-lg print:mx-0 print:max-w-none print:w-full print:overflow-visible print:rounded-none print:border-0 print:shadow-none">
          <!-- Row 1: logo + thick rule -->
          <div class="flex items-center gap-3 px-4 pt-4 pb-3 sm:px-6">
            <div class="flex shrink-0 flex-col gap-0.5">
              <img src="images/EI_Logo_130x27_SVG_1.svg" width="260" height="54" alt="Exotic India" class="h-[54px] w-[260px] max-w-full object-contain object-left" />
              <div class="text-[9px] font-medium uppercase tracking-[0.18em] text-neutral-500"><?= $h($receipt_company_tagline ?? '') ?></div>
            </div>
            <div class="h-[3px] min-w-0 flex-1 bg-black"></div>
          </div>

          <!-- Row 2 -->
          <div class="grid grid-cols-1 gap-6 border-b border-neutral-300 px-4 pb-5 sm:grid-cols-2 sm:px-6">
            <div class="text-[11px] leading-relaxed">
              <div class="font-bold uppercase tracking-wide text-neutral-900"><?= $h($receipt_company_legal_name ?? 'EXOTIC INDIA ART PVT LTD') ?></div>
              <div class="mt-1"><span class="font-semibold">GST No:</span> <?= $h($receipt_company_gstin ?? '') ?></div>
              <div><span class="font-semibold">PAN:</span> <?= $h($receipt_company_pan ?? '') ?></div>
            </div>
            <div class="sm:text-right">
              <div class="text-xl font-black uppercase tracking-tight text-neutral-900"><?= $h($receipt_title_main ?? 'PAYMENT RECEIPT') ?></div>
              <div class="mt-2 space-y-0.5 text-[11px] text-neutral-800">
                <div><span class="font-semibold">Receipt No. :</span> <?= $h($receipt_number ?? '') ?></div>
                <div><span class="font-semibold">Dated :</span> <?= $h($receipt_date_formatted ?? '') ?></div>
                <div><span class="font-semibold">Place of Supply :</span> <?= $h($receipt_place_of_supply ?? '') ?></div>
                <div class="sm:hidden"><span class="font-semibold">Order ID :</span> <?= $h($order_id ?? '') ?></div>
              </div>
            </div>
          </div>

          <!-- Row 3: Bill To / Ship To -->
          <div class="grid grid-cols-1 gap-px bg-neutral-300 sm:grid-cols-2">
            <div class="bg-white px-4 py-3 sm:px-6">
              <div class="flex items-stretch gap-2">
                <div class="w-1 shrink-0 bg-orange-500"></div>
                <div class="min-w-0 flex-1">
                  <div class="text-[10px] font-bold uppercase tracking-wider text-neutral-900">Bill To:</div>
                  <div class="mt-2 space-y-1 text-[10px] leading-snug text-neutral-800">
                    <?php foreach ($receipt_billing_block ?? ['—'] as $bl): ?>
                      <div><?= $h($bl) ?></div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-white px-4 py-3 sm:px-6">
              <div class="flex items-stretch gap-2">
                <div class="w-1 shrink-0 bg-orange-500"></div>
                <div class="min-w-0 flex-1">
                  <div class="text-[10px] font-bold uppercase tracking-wider text-neutral-900">Ship To:</div>
                  <div class="mt-2 space-y-1 text-[10px] leading-snug text-neutral-800">
                    <?php foreach ($receipt_shipping_block ?? ['—'] as $sl): ?>
                      <div><?= $h($sl) ?></div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Row 4: payment banner -->
          <div class="border-y border-neutral-900 bg-neutral-900 px-4 py-3 text-center text-[11px] font-semibold leading-snug text-white sm:text-sm">
            <?= $h($receipt_banner_text ?? '') ?> <span class="font-normal text-neutral-300">Stage: <?= $h(ucfirst(trim((string)($payment_stage ?? 'final')))) ?> · <?= $h($payment_mode_label ?? '') ?></span>
          </div>

          <!-- Items -->
          <div class="overflow-x-auto px-2 pb-4 sm:px-4">
            <?php if (empty($lines)): ?>
              <p class="px-3 py-4 text-center text-[11px] text-neutral-600">
                No line items loaded yet — run order import first, then refresh this page after import succeeds.
              </p>
            <?php else: ?>
              <table class="w-full min-w-[760px] border-collapse text-[10px]">
                <thead>
                  <tr class="border-b border-neutral-800 bg-neutral-100 text-neutral-900">
                    <th class="border border-neutral-300 px-1 py-1 font-semibold" rowspan="2">S.No.</th>
                    <th class="border border-neutral-300 px-1 py-1 font-semibold" rowspan="2">Description of Goods</th>
                    <th class="border border-neutral-300 px-1 py-1 font-semibold" rowspan="2">HSN</th>
                    <th class="border border-neutral-300 px-1 py-1 font-semibold" rowspan="2">Qty</th>
                    <th class="border border-neutral-300 px-1 py-1 font-semibold" rowspan="2">Price (₹)</th>
                    <th class="border border-neutral-300 px-1 py-1 text-center font-semibold" colspan="2">SGST</th>
                    <th class="border border-neutral-300 px-1 py-1 text-center font-semibold" colspan="2">CGST</th>
                    <th class="border border-neutral-300 px-1 py-1 text-center font-semibold" colspan="2">IGST</th>
                    <th class="border border-neutral-300 px-1 py-1 font-semibold" rowspan="2">Total ₹</th>
                  </tr>
                  <tr class="border-b border-neutral-800 bg-neutral-50 text-neutral-900">
                    <th class="border border-neutral-300 px-1 py-0.5 font-medium">%</th>
                    <th class="border border-neutral-300 px-1 py-0.5 font-medium">Amt</th>
                    <th class="border border-neutral-300 px-1 py-0.5 font-medium">%</th>
                    <th class="border border-neutral-300 px-1 py-0.5 font-medium">Amt</th>
                    <th class="border border-neutral-300 px-1 py-0.5 font-medium">%</th>
                    <th class="border border-neutral-300 px-1 py-0.5 font-medium">Amt</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($lines as $row): ?>
                    <tr class="align-top text-neutral-800">
                      <td class="border border-neutral-300 px-1 py-1 text-center"><?= $h((string)($row['sn'] ?? '')) ?></td>
                      <td class="border border-neutral-300 px-1 py-1"><?= $h((string)($row['title'] ?? '')) ?></td>
                      <td class="border border-neutral-300 px-1 py-1"><?= $h((string)($row['hsn'] ?? '')) ?></td>
                      <td class="border border-neutral-300 px-1 py-1 text-right"><?= $rfmt($row['qty'] ?? 0, 2) ?></td>
                      <td class="border border-neutral-300 px-1 py-1 text-right"><?= $rfmt($row['unit_price'] ?? 0) ?></td>
                      <td class="border border-neutral-300 px-1 py-1 text-right"><?= $rfmt($row['sgst_rate'] ?? 0, 3) ?></td>
                      <td class="border border-neutral-300 px-1 py-1 text-right"><?= $rfmt($row['sgst_amt'] ?? 0) ?></td>
                      <td class="border border-neutral-300 px-1 py-1 text-right"><?= $rfmt($row['cgst_rate'] ?? 0, 3) ?></td>
                      <td class="border border-neutral-300 px-1 py-1 text-right"><?= $rfmt($row['cgst_amt'] ?? 0) ?></td>
                      <td class="border border-neutral-300 px-1 py-1 text-right"><?= $rfmt($row['igst_rate'] ?? 0, 3) ?></td>
                      <td class="border border-neutral-300 px-1 py-1 text-right"><?= $rfmt($row['igst_amt'] ?? 0) ?></td>
                      <td class="border border-neutral-300 px-1 py-1 text-right font-semibold"><?= $rfmt($row['line_total'] ?? 0) ?></td>
                    </tr>
                  <?php endforeach; ?>
                  <tr class="bg-neutral-50 font-semibold text-neutral-900">
                    <td class="border border-neutral-300 px-1 py-1 text-center" colspan="3">Total</td>
                    <td class="border border-neutral-300 px-1 py-1 text-right"><?= $rfmt($receipt_qty_total ?? 0, 2) ?></td>
                    <td class="border border-neutral-300 px-1 py-1"></td>
                    <td class="border border-neutral-300 px-1 py-1"></td>
                    <td class="border border-neutral-300 px-1 py-1 text-right"><?= $rfmt($receipt_agg_sgst ?? 0) ?></td>
                    <td class="border border-neutral-300 px-1 py-1"></td>
                    <td class="border border-neutral-300 px-1 py-1 text-right"><?= $rfmt($receipt_agg_cgst ?? 0) ?></td>
                    <td class="border border-neutral-300 px-1 py-1"></td>
                    <td class="border border-neutral-300 px-1 py-1 text-right"><?= $rfmt($receipt_agg_igst ?? 0) ?></td>
                    <td class="border border-neutral-300 px-1 py-1 text-right"><?= $rfmt($receipt_subtotal_goods ?? 0) ?></td>
                  </tr>
                </tbody>
              </table>
            <?php endif; ?>
          </div>

          <!-- Summary -->
          <div class="border-t border-neutral-300 px-4 py-3 sm:px-6">
            <div class="grid gap-6 sm:grid-cols-2">
              <div class="space-y-2 text-[10px] text-neutral-800">
                <?php $cd = $receipt_cash_discount ?? 0; ?>
                <div class="flex justify-between border-b border-dotted border-neutral-300 pb-1"><span>Sub Total (Goods)</span><span class="font-semibold"><?= $rfmt($receipt_subtotal_goods ?? 0) ?></span></div>
                <div class="flex justify-between border-b border-dotted border-neutral-300 pb-1"><span>Total GST (computed)</span><span class="font-semibold"><?= $rfmt($receipt_gst_total ?? 0) ?></span></div>
                <div class="flex justify-between border-b border-dotted border-neutral-300 pb-1"><span>Coupon Discount</span><span class="font-semibold"><?= $rfmt($receipt_coupon_discount ?? 0) ?></span></div>
                <div class="flex justify-between border-b border-dotted border-neutral-300 pb-1"><span>Cash Discount</span><span class="font-semibold"><?= ($cd > 0 ? $rfmt($cd) : '—') ?></span></div>
                <div class="flex justify-between border-b border-dotted border-neutral-300 pb-1"><span>Gift Voucher Discount</span><span class="font-semibold"><?= $rfmt($receipt_gift_discount ?? 0) ?></span></div>
                <div class="flex justify-between pt-1 text-[12px] font-black text-neutral-900"><span>Grand Total</span><span>₹<?= $rfmt($receipt_grand_total ?? 0) ?></span></div>
                <div class="pt-3 text-[10px] font-semibold text-neutral-700">Amount in words</div>
                <div class="text-[10px] italic text-neutral-800"><?= $h($receipt_amount_in_words ?? '') ?></div>
                <div class="flex justify-between pt-3 border-t border-neutral-300 text-[11px]"><span class="font-semibold">Amount Received</span><span class="font-bold">₹<?= $rfmt($receipt_amount_received ?? 0) ?></span></div>
                <div class="flex justify-between text-[11px]"><span class="font-semibold">Pending Amount</span><span class="font-bold">₹<?= $rfmt($receipt_pending_amount ?? 0) ?></span></div>
                <?php if (trim((string)($transaction_id ?? '')) !== ''): ?>
                  <div class="pt-1 text-[10px] text-neutral-600"><span class="font-semibold">Transaction ID:</span> <?= $h((string)$transaction_id) ?></div>
                <?php endif; ?>
              </div>
              <div>
                <div class="rounded border border-neutral-300 bg-neutral-50 px-3 py-3">
                  <div class="text-[10px] font-bold uppercase tracking-wide text-neutral-900">E. &amp; O. E.</div>
                  <ol class="mt-2 list-decimal space-y-1 pl-4 text-[9px] leading-relaxed text-neutral-700">
                    <?php foreach (($receipt_terms ?? []) as $t): ?>
                      <li><?= $h((string)$t) ?></li>
                    <?php endforeach; ?>
                  </ol>
                  <div class="mt-4 border-t border-neutral-300 pt-3 text-center text-[9px] text-neutral-600">
                    This is an electronically generated advice and does not require signature.
                  </div>
                  <div class="mt-6 flex justify-between text-[10px] text-neutral-800">
                    <div>
                      <div class="font-semibold">Date</div>
                      <div><?= $h($receipt_signature_date ?? '') ?></div>
                    </div>
                    <div class="text-right">
                      <div class="font-semibold">Authorised Signatory</div>
                      <div class="pt-8 text-neutral-400">____________________</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="border-t border-neutral-300 px-4 py-4 sm:px-6">
            <div class="flex flex-col items-start gap-4 sm:flex-row sm:justify-between sm:gap-8">
              <div class="flex flex-col gap-0.5">
                <img src="images/EI_Logo_130x27_SVG_1.svg" width="104" height="22" alt="Exotic India" class="h-[22px] w-[104px] max-w-full object-contain object-left" />
                <div class="text-[8px] uppercase tracking-[0.2em] text-neutral-500"><?= $h($receipt_company_tagline ?? '') ?></div>
              </div>
              <div class="max-w-md text-[10px] leading-relaxed text-neutral-700">
                <span class="font-bold text-neutral-900">Head Office</span><br>
                <?= $h($receipt_office_footer ?? '') ?>
              </div>
            </div>
          </div>
        </div>

        <div class="no-print flex flex-wrap gap-3">
          <button type="button" onclick="printPaymentReceipt()" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Print Payment Receipt</button>
          <a href="<?= $h((string)($payment_history_url ?? 'index.php?page=orders&action=list')) ?>" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Payment History</a>
          <a href="<?= $h((string)($invoice_preview_url ?? '#')) ?>" target="_blank" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Print Invoice</a>
          <a href="index.php?page=pos_register&action=list" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to POS</a>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  /* A4 portrait (210 × 297 mm); printer margins inset content */
  @page {
    size: A4 portrait;
    margin: 14mm 16mm;
  }

  @media print {
    .no-print {
      display: none !important;
    }

    html,
    body {
      margin: 0 !important;
      padding: 0 !important;
      background: #fff !important;
      font-size: 10pt;
      line-height: 1.35;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
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
      max-width: none;
      box-sizing: border-box;
      border: none !important;
      box-shadow: none !important;
    }

    /* Table fits printable width (no forced horizontal scroll bar) */
    #paymentReceiptSection .overflow-x-auto {
      overflow: visible !important;
    }

    #paymentReceiptSection table {
      width: 100% !important;
      min-width: 0 !important;
      font-size: 8.5pt;
      table-layout: auto;
    }

    #paymentReceiptSection th,
    #paymentReceiptSection td {
      padding: 0.2rem 0.15rem !important;
    }

    #paymentReceiptSection img {
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
  }

  /* Screen preview: inner padding echoes ~16 mm side rhythm */
  @media screen {
    #paymentReceiptSection.receipt-sheet {
      box-sizing: border-box;
    }
  }
</style>

<script>
  function printPaymentReceipt() {
    window.print();
  }
</script>
