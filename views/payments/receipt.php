<?php
/**
 * Standalone print receipt (page=payments&action=receipt&id=…)
 * @var array<string, mixed> $payment
 */
$payment = is_array($payment ?? null) ? $payment : [];
$h = static function ($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$fmt = static function ($n): string {
    return number_format((float)$n, 2, '.', ',');
};

$receiptNo = trim((string)($payment['receipt_number'] ?? ''));
if ($receiptNo === '') {
    $receiptNo = '#' . (int)($payment['id'] ?? 0);
}

$modeRaw = strtolower(trim((string)($payment['payment_mode'] ?? '')));
$modeLabels = [
    'cash' => 'Cash',
    'cod' => 'Cash',
    'upi' => 'UPI',
    'bank_transfer' => 'Bank transfer',
    'pos_machine' => 'POS machine',
    'razorpay' => 'Razorpay',
    'cheque' => 'Cheque',
    'offline' => 'Offline',
];
$modeDisplay = $modeLabels[$modeRaw] ?? strtoupper($modeRaw ?: '—');

$stageRaw = trim((string)($payment['payment_stage'] ?? ''));
$stageDisplay = $stageRaw !== '' ? ucfirst(strtolower($stageRaw)) : '—';

$txn = trim((string)($payment['transaction_id'] ?? ''));
$txnDisplay = $txn !== '' ? $txn : '—';

$amount = (float)($payment['payment_amount'] ?? $payment['amount'] ?? 0);
$orderAmt = isset($payment['order_amount']) ? (float)$payment['order_amount'] : null;
$pendingAmt = isset($payment['pending_amount']) ? (float)$payment['pending_amount'] : null;

$warehouse = trim((string)($payment['warehouse'] ?? ''));
$userName = trim((string)($payment['user_name'] ?? ''));
$payDate = trim((string)($payment['payment_date'] ?? ''));
$orderNum = trim((string)($payment['order_number'] ?? ''));
$note = trim((string)($payment['note'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $h('Receipt ' . $receiptNo) ?> · Payment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['DM Sans', 'system-ui', 'sans-serif'] },
                    colors: {
                        receipt: { ink: '#0f172a', muted: '#64748b', accent: '#ea580c' },
                    },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'DM Sans', system-ui, sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; padding: 0 !important; }
            .receipt-sheet {
                box-shadow: none !important;
                border-radius: 0 !important;
                max-width: 100% !important;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <!-- Toolbar: screen only -->
    <div class="no-print sticky top-0 z-10 border-b border-slate-200/80 bg-white/95 px-4 py-3 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-white/80">
        <div class="mx-auto flex max-w-lg flex-wrap items-center justify-between gap-3">
            <p class="text-xs font-medium text-slate-600">Payment receipt preview</p>
            <div class="flex items-center gap-2">
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>
                <button type="button" onclick="window.close()" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Close
                </button>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-lg px-4 py-8 print:max-w-none print:px-0 print:py-0">
        <article class="receipt-sheet overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xl shadow-slate-900/5 print:border-0 print:shadow-none">
            <!-- Brand strip -->
            <div class="border-b border-slate-100 bg-white px-6 pt-6 pb-4">
                <div class="flex items-end gap-4">
                    <div class="min-w-0 flex-1">
                        <img src="images/EI_Logo_130x27_SVG_1.svg" width="260" height="54" alt="Exotic India" class="h-11 w-auto max-w-full object-contain object-left" />
                        <p class="mt-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Authentic · Curated · Heritage</p>
                    </div>
                    <div class="hidden h-px min-w-[3rem] flex-1 bg-gradient-to-r from-orange-500 to-transparent sm:block print:block" aria-hidden="true"></div>
                </div>
            </div>

            <div class="px-6 pb-6 pt-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-orange-600">Payment receipt</p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900"><?= $h($receiptNo) ?></h1>
                    </div>
                    <div class="text-right text-sm">
                        <?php if ($warehouse !== ''): ?>
                            <p class="font-semibold text-slate-800"><?= $h($warehouse) ?></p>
                        <?php endif; ?>
                        <p class="mt-1 text-slate-600">
                            <span class="text-slate-500">Date</span><br />
                            <span class="font-medium text-slate-800"><?= $h($payDate !== '' ? $payDate : '—') ?></span>
                        </p>
                    </div>
                </div>

                <!-- Amount highlight -->
                <div class="mt-6 rounded-xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-5 py-5 text-center text-white ring-1 ring-slate-900/10">
                    <p class="text-xs font-medium uppercase tracking-wider text-white/70">Amount received</p>
                    <p class="mt-2 text-4xl font-bold tabular-nums tracking-tight">₹ <?= $h($fmt($amount)) ?></p>
                    <?php if ($orderAmt !== null && $orderAmt > 0): ?>
                        <p class="mt-3 border-t border-white/10 pt-3 text-xs text-white/80">
                            Order total <span class="font-semibold tabular-nums">₹ <?= $h($fmt($orderAmt)) ?></span>
                            <?php if ($pendingAmt !== null && $pendingAmt > 0.009): ?>
                                <span class="text-white/60"> · </span>
                                Balance <span class="font-semibold text-amber-300 tabular-nums">₹ <?= $h($fmt($pendingAmt)) ?></span>
                            <?php elseif ($pendingAmt !== null && abs($pendingAmt) < 0.01): ?>
                                <span class="block pt-1 text-emerald-300/90">Fully settled</span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Details grid -->
                <dl class="mt-6 space-y-0 divide-y divide-slate-100 rounded-xl border border-slate-100 bg-slate-50/50">
                    <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3 sm:items-center">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Order number</dt>
                        <dd class="font-semibold text-slate-900 sm:col-span-2"><?= $h($orderNum !== '' ? $orderNum : '—') ?></dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3 sm:items-center">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payment mode</dt>
                        <dd class="font-medium text-slate-900 sm:col-span-2"><?= $h($modeDisplay) ?></dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3 sm:items-center">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stage</dt>
                        <dd class="font-medium text-slate-900 sm:col-span-2"><?= $h($stageDisplay) ?></dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3 sm:items-center">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Transaction ID</dt>
                        <dd class="break-all font-mono text-sm text-slate-800 sm:col-span-2"><?= $h($txnDisplay) ?></dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3 sm:items-center">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Collected by</dt>
                        <dd class="text-slate-900 sm:col-span-2"><?= $h($userName !== '' ? $userName : '—') ?></dd>
                    </div>
                </dl>

                <?php if ($note !== ''): ?>
                    <div class="mt-5 rounded-lg border border-amber-100 bg-amber-50/80 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-900/70">Note</p>
                        <p class="mt-1 text-sm text-amber-950/90"><?= nl2br($h($note)) ?></p>
                    </div>
                <?php endif; ?>

                <footer class="mt-8 border-t border-slate-100 pt-6 text-center">
                    <p class="text-xs leading-relaxed text-slate-500">
                        This is a system-generated receipt.<br class="hidden sm:inline" />
                        Thank you for your business.
                    </p>
                    <p class="mt-4 text-[10px] text-slate-400">Exotic India · POS payment record</p>
                </footer>
            </div>
        </article>
    </div>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 350);
        });
    </script>
</body>
</html>
