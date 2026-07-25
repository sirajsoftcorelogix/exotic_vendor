<?php
$posPaymentModalTitle = trim((string)($posPaymentModalTitle ?? 'Checkout & payment'));
$posPaymentModalIntro = trim((string)($posPaymentModalIntro ?? 'Add one or more payment lines. Each row is saved as a separate payment entry (same receipt).'));
$posPaymentModalSubmitLabel = trim((string)($posPaymentModalSubmitLabel ?? 'Confirm payment'));
$posPaymentModalSubmitId = trim((string)($posPaymentModalSubmitId ?? 'posPaymentModalSubmitBtn'));
$posPaymentModalShowCustomInvoice = !empty($posPaymentModalShowCustomInvoice);
$posPaymentModalShowApiDebug = !empty($posPaymentModalShowApiDebug);
$posPaymentModalCloseHandler = trim((string)($posPaymentModalCloseHandler ?? 'PosPaymentSplit.closeModal()'));
?>
<div id="paymentModal" class="fixed inset-0 z-[9999] hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="<?php echo htmlspecialchars($posPaymentModalCloseHandler, ENT_QUOTES, 'UTF-8'); ?>"></div>
    <div class="relative mx-auto mt-12 w-[95%] max-w-2xl rounded-2xl bg-white shadow-2xl flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between border-b px-5 py-3 shrink-0">
            <h2 class="text-base font-semibold text-slate-800"><?php echo htmlspecialchars($posPaymentModalTitle); ?></h2>
            <button type="button" onclick="<?php echo htmlspecialchars($posPaymentModalCloseHandler, ENT_QUOTES, 'UTF-8'); ?>" class="text-slate-400 hover:text-slate-700 text-xl leading-none" aria-label="Close">✕</button>
        </div>
        <div class="overflow-y-auto p-5 space-y-4 text-sm">
            <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                <?php echo htmlspecialchars($posPaymentModalIntro); ?>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-slate-500">Payment stage <span class="text-red-600">*</span></label>
                    <select id="payment_stage" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" required>
                        <option value="final">Final</option>
                        <option value="partial">Partial</option>
                        <option value="advance">Advance</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-slate-500">Payment date <span class="text-red-600">*</span></label>
                    <input type="date" id="payment_date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" title="Today or earlier only" required>
                </div>
            </div>

            <div class="rounded-xl border border-orange-200 bg-gradient-to-r from-orange-50 to-amber-50 p-4">
                <div class="grid grid-cols-3 gap-3 text-center sm:text-left">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Order total</div>
                        <div id="payment_summary_order" class="mt-0.5 text-lg font-bold text-slate-900 tabular-nums">₹ 0.00</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Collecting now</div>
                        <div id="payment_summary_paid" class="mt-0.5 text-lg font-bold text-orange-700 tabular-nums">₹ 0.00</div>
                    </div>
                    <div>
                        <div id="payment_summary_balance_label" class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Balance</div>
                        <div id="payment_summary_balance" class="mt-0.5 text-lg font-bold text-emerald-700 tabular-nums">₹ 0.00</div>
                    </div>
                </div>
                <p id="payment_summary_hint" class="mt-2 hidden text-[11px] text-slate-600"></p>
            </div>

            <div class="rounded-xl border border-slate-200 overflow-hidden">
                <div class="flex items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-4 py-2.5">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800">Payment split</h3>
                        <p class="text-[11px] text-slate-500">Each row is saved as a separate payment entry (same receipt)</p>
                    </div>
                    <button type="button" id="payment_split_add_btn" class="inline-flex items-center gap-1.5 rounded-lg border border-orange-300 bg-white px-3 py-1.5 text-xs font-semibold text-orange-700 hover:bg-orange-50 shadow-sm">
                        <span class="text-base leading-none">+</span> Add mode
                    </button>
                </div>
                <div class="hidden sm:grid sm:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)_minmax(0,1.2fr)_2.5rem] gap-2 px-4 py-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500 bg-white border-b border-slate-100">
                    <span>Mode</span>
                    <span>Amount (₹)</span>
                    <span>Transaction / ref</span>
                    <span></span>
                </div>
                <div id="payment_split_rows" class="divide-y divide-slate-100 bg-white"></div>
                <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-4 py-2.5 text-xs">
                    <span class="text-slate-600"><span id="payment_split_count">0</span> payment line(s)</span>
                    <span class="font-semibold text-slate-800">Split total: <span id="payment_split_total" class="text-orange-700 tabular-nums">₹ 0.00</span></span>
                </div>
            </div>
            <div id="payment_split_validation" class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"></div>

            <input type="hidden" id="payment_amount" value="">
            <input type="hidden" id="payment_mode" value="cash">
            <input type="hidden" id="transaction_id" value="">
            <?php if ($posPaymentModalShowCustomInvoice): ?>
                <div id="customInvoiceNumberWrap" class="hidden rounded-xl border border-emerald-100 bg-emerald-50/60 p-3">
                    <label class="text-xs font-medium text-emerald-900">Override invoice number (optional)</label>
                    <input type="text" id="custom_invoice_number" maxlength="50" class="mt-1 w-full rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm" placeholder="Auto-generated if left blank">
                </div>
            <?php endif; ?>
            <div>
                <label class="text-xs text-slate-500">Note (optional)</label>
                <textarea id="payment_note" rows="2" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2"></textarea>
            </div>
            <?php if ($posPaymentModalShowApiDebug): ?>
                <div id="paymentModalOrderApiPanel" class="hidden rounded-lg border border-slate-200 bg-slate-900 p-3">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-white">Last order-create API</span>
                        <button type="button" id="paymentModalOrderApiFullBtn" class="text-[11px] text-orange-300 hover:text-white">Open in debug</button>
                    </div>
                    <pre id="paymentModalOrderApiPre" class="max-h-40 overflow-auto text-[10px] leading-snug text-slate-100 whitespace-pre-wrap break-words"></pre>
                </div>
            <?php endif; ?>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-3 bg-slate-50 rounded-b-2xl shrink-0">
            <button type="button" onclick="<?php echo htmlspecialchars($posPaymentModalCloseHandler, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancel</button>
            <button type="button" id="<?php echo htmlspecialchars($posPaymentModalSubmitId, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"><?php echo htmlspecialchars($posPaymentModalSubmitLabel); ?></button>
        </div>
    </div>
</div>

<template id="payment_split_row_template">
    <div class="payment-split-row px-4 py-3 sm:grid sm:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)_minmax(0,1.2fr)_2.5rem] sm:gap-2 sm:items-start space-y-2 sm:space-y-0">
        <div>
            <label class="sm:hidden text-[10px] font-semibold text-slate-500 uppercase">Mode</label>
            <select class="payment-split-mode mt-0.5 sm:mt-0 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></select>
        </div>
        <div>
            <label class="sm:hidden text-[10px] font-semibold text-slate-500 uppercase">Amount (₹)</label>
            <input type="number" step="0.01" min="0" class="payment-split-amount mt-0.5 sm:mt-0 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm tabular-nums" placeholder="0.00" />
        </div>
        <div>
            <label class="sm:hidden text-[10px] font-semibold text-slate-500 uppercase">Transaction / ref</label>
            <input type="text" class="payment-split-txn mt-0.5 sm:mt-0 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Optional" />
            <p class="payment-split-txn-hint hidden mt-1 text-[10px] text-amber-700">Required for Razorpay / Cheque</p>
        </div>
        <div class="flex sm:justify-center sm:pt-2">
            <button type="button" class="payment-split-remove rounded-lg border border-red-200 bg-red-50 px-2 py-1.5 text-red-600 hover:bg-red-100 text-sm" title="Remove line">✕</button>
        </div>
    </div>
</template>
