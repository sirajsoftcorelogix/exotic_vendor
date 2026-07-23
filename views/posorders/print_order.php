<?php
/**
 * Customer handover print — order lines + payment summary (not tax invoice).
 *
 * @var list<array<string, mixed>> $order
 * @var array<string, mixed> $orderremarks
 * @var array<string, mixed> $customerdetails
 * @var array<string, mixed>|null $invoiceDisplay
 * @var array<string, mixed> $paymentSummary
 * @var array<int, array<string, mixed>> $linePricingByLineId
 * @var string $orderNumber
 * @var array<string, mixed> $firmDetails
 * @var array{title: string, lines: list<string>} $storeAddress
 */
$h = static function ($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$fmt = static function ($n, int $dec = 2): string {
    return number_format((float)$n, $dec, '.', ',');
};

$orderNumber = trim((string)($orderNumber ?? ''));
$customer = is_array($customerdetails ?? null) ? $customerdetails : [];
$remarks = is_array($orderremarks ?? null) ? $orderremarks : [];
$firm = is_array($firmDetails ?? null) ? $firmDetails : [];
$store = is_array($storeAddress ?? null) ? $storeAddress : ['title' => '', 'lines' => []];
$paymentSummary = is_array($paymentSummary ?? null) ? $paymentSummary : [];
$paymentRows = is_array($paymentSummary['payments'] ?? null) ? $paymentSummary['payments'] : [];
$invoiceDisplay = is_array($invoiceDisplay ?? null) ? $invoiceDisplay : null;

$firmName = trim((string)($firm['firm_name'] ?? ''));
if ($firmName === '') {
    $firmName = 'EXOTIC INDIA ART PVT LTD';
}
$firmGst = trim((string)($firm['gstin'] ?? $firm['gst'] ?? ''));
$storeTitle = trim((string)($store['title'] ?? ''));
$storeLines = is_array($store['lines'] ?? null) ? $store['lines'] : [];

$customerName = trim((string)($customer['customer_name'] ?? ''));
$customerPhone = trim((string)($customer['customer_phone'] ?? ''));
$customerEmail = trim((string)($customer['customer_email'] ?? ''));

$orderDateRaw = trim((string)($remarks['created_at'] ?? ($order[0]['order_date'] ?? '')));
$orderDateLabel = $orderDateRaw !== '' ? date('d M Y', strtotime($orderDateRaw)) : '—';

$invoiceNumber = $invoiceDisplay !== null ? trim((string)($invoiceDisplay['invoice_number'] ?? '')) : '';
$invoiceStatus = $invoiceDisplay !== null ? strtolower(trim((string)($invoiceDisplay['status'] ?? ''))) : '';

$summaryRows = ($invoiceDisplay !== null && is_array($invoiceDisplay['summary_rows'] ?? null))
    ? $invoiceDisplay['summary_rows']
    : [];

$orderTotal = (float)($paymentSummary['order_total'] ?? 0);
$paidTotal = (float)($paymentSummary['paid_total'] ?? 0);
$pendingTotal = (float)($paymentSummary['pending'] ?? 0);
$isFullyPaid = !empty($paymentSummary['is_fully_paid']);

$codTotal = 0.0;
foreach ($paymentRows as $payRow) {
    if (strtolower(trim((string)($payRow['payment_mode'] ?? ''))) === 'cod') {
        $codTotal += (float)($payRow['payment_amount'] ?? 0);
    }
}

$modeLabels = [
    'cash' => 'Cash',
    'cod' => 'Cash on Delivery',
    'upi' => 'UPI',
    'bank_transfer' => 'Bank transfer',
    'pos_machine' => 'Card / POS',
    'razorpay' => 'Online (Razorpay)',
    'cheque' => 'Cheque',
];

$formatAddressBlock = static function (array $parts) use ($h): string {
    $lines = array_values(array_filter(array_map(static function ($line) {
        return trim((string)$line);
    }, $parts), static function ($line) {
        return $line !== '';
    }));
    if ($lines === []) {
        return '—';
    }

    return implode('<br>', array_map($h, $lines));
};

$shippingHtml = $formatAddressBlock([
    $customerName,
    trim((string)($remarks['shipping_address_line1'] ?? '')),
    trim((string)($remarks['shipping_address_line2'] ?? '')),
    implode(', ', array_filter([
        trim((string)($remarks['shipping_city'] ?? '')),
        trim((string)($remarks['shipping_state'] ?? '')),
        trim((string)($remarks['shipping_zipcode'] ?? '')),
    ])),
    trim((string)($remarks['shipping_country'] ?? '')),
    $customerPhone !== '' ? 'Phone: ' . $customerPhone : '',
    $customerEmail !== '' ? 'Email: ' . $customerEmail : '',
]);

$lines = is_array($order ?? null) ? $order : [];
$linePricingMap = is_array($linePricingByLineId ?? null) ? $linePricingByLineId : [];
?>
<div class="no-print fixed inset-x-0 top-0 z-50 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
    <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
        <div>
            <div class="text-sm font-semibold text-slate-900">Order <?= $h($orderNumber) ?></div>
            <div class="text-xs text-slate-500">Customer copy — order summary and payments. Use Print Invoice for the tax invoice.</div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="window.print()" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Print</button>
            <button type="button" onclick="window.close()" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Close</button>
        </div>
    </div>
</div>

<article class="order-print-sheet mx-auto max-w-[210mm] px-4 pb-10 pt-20 text-black print:max-w-none print:px-0 print:pt-0">
    <header class="border-b-2 border-black pb-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <img src="images/EI_Logo_130x27_SVG_1.svg" width="220" height="46" alt="Exotic India" class="h-11 w-auto max-w-full object-contain object-left" />
                <div class="mt-2 text-[11px] leading-relaxed text-neutral-700">
                    <div class="font-bold uppercase tracking-wide text-neutral-900"><?= $h($firmName) ?></div>
                    <?php if ($firmGst !== ''): ?>
                        <div><span class="font-semibold">GSTIN:</span> <?= $h($firmGst) ?></div>
                    <?php endif; ?>
                    <?php if ($storeTitle !== ''): ?>
                        <div class="mt-1 font-medium text-neutral-800"><?= $h($storeTitle) ?></div>
                    <?php endif; ?>
                    <?php foreach ($storeLines as $storeLine): ?>
                        <div><?= $h($storeLine) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="text-right">
                <div class="text-lg font-black uppercase tracking-tight text-neutral-900">Order Summary</div>
                <dl class="mt-3 space-y-1 text-[11px] text-neutral-800">
                    <div><dt class="inline font-semibold">Order No:</dt> <dd class="inline"><?= $h($orderNumber) ?></dd></div>
                    <div><dt class="inline font-semibold">Order Date:</dt> <dd class="inline"><?= $h($orderDateLabel) ?></dd></div>
                    <?php if ($invoiceNumber !== '' && $invoiceStatus === 'final'): ?>
                        <div><dt class="inline font-semibold">Tax Invoice:</dt> <dd class="inline font-mono"><?= $h($invoiceNumber) ?></dd></div>
                    <?php endif; ?>
                    <div>
                        <dt class="inline font-semibold">Payment Status:</dt>
                        <dd class="inline font-semibold <?= $isFullyPaid ? 'text-emerald-700' : ($paidTotal > 0 ? 'text-amber-700' : 'text-neutral-600') ?>">
                            <?= $isFullyPaid ? 'Fully paid' : ($paidTotal > 0 ? 'Partially paid' : 'Payment pending') ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </header>

    <section class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded border border-neutral-300 p-3">
            <h2 class="text-[10px] font-bold uppercase tracking-wider text-neutral-900">Ship To</h2>
            <address class="mt-2 text-[11px] not-italic leading-relaxed text-neutral-800"><?= $shippingHtml ?></address>
        </div>
        <div class="rounded border border-neutral-300 p-3">
            <h2 class="text-[10px] font-bold uppercase tracking-wider text-neutral-900">Bill To</h2>
            <address class="mt-2 text-[11px] not-italic leading-relaxed text-neutral-800">
                <?= $formatAddressBlock([
                    $customerName,
                    trim((string)($remarks['address_line1'] ?? '')),
                    trim((string)($remarks['address_line2'] ?? '')),
                    implode(', ', array_filter([
                        trim((string)($remarks['city'] ?? '')),
                        trim((string)($remarks['state'] ?? '')),
                        trim((string)($remarks['zipcode'] ?? '')),
                    ])),
                    trim((string)($remarks['country'] ?? '')),
                ]) ?>
            </address>
        </div>
    </section>

    <section class="mt-6">
        <h2 class="mb-2 text-[10px] font-bold uppercase tracking-wider text-neutral-900">Items Ordered</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse text-[10px]">
                <thead>
                    <tr class="border-b border-neutral-800 bg-neutral-100">
                        <th class="border border-neutral-300 px-2 py-1.5 text-left font-semibold">#</th>
                        <th class="border border-neutral-300 px-2 py-1.5 text-left font-semibold">Description</th>
                        <th class="border border-neutral-300 px-2 py-1.5 text-left font-semibold">SKU</th>
                        <th class="border border-neutral-300 px-2 py-1.5 text-right font-semibold">Qty</th>
                        <th class="border border-neutral-300 px-2 py-1.5 text-right font-semibold">Unit Price (₹)</th>
                        <th class="border border-neutral-300 px-2 py-1.5 text-right font-semibold">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sn = 0;
                    foreach ($lines as $item):
                        $sn++;
                        $lineId = (int)($item['id'] ?? 0);
                        $linePricing = $linePricingMap[$lineId] ?? null;
                        $qty = max(1, (int)($item['quantity'] ?? 1));
                        $netLineAmount = is_array($linePricing)
                            ? (float)($linePricing['chargeable_value'] ?? 0)
                            : (float)($item['finalprice'] ?? 0) * $qty;
                        $unitPrice = $qty > 0 ? $netLineAmount / $qty : 0.0;
                        $title = trim((string)($item['title'] ?? ''));
                        $addons = order_line_addons_for_display($item['addons'] ?? null);
                        if ($addons !== []) {
                            $addonNames = array_map(static function ($row) {
                                return trim((string)($row['name'] ?? ''));
                            }, $addons);
                            $addonNames = array_values(array_filter($addonNames));
                            if ($addonNames !== []) {
                                $title .= ' (' . implode(', ', $addonNames) . ')';
                            }
                        }
                    ?>
                        <tr class="align-top text-neutral-800">
                            <td class="border border-neutral-300 px-2 py-2 text-center"><?= $sn ?></td>
                            <td class="border border-neutral-300 px-2 py-2"><?= $h($title) ?></td>
                            <td class="border border-neutral-300 px-2 py-2 font-mono text-[9px]"><?= $h((string)($item['sku'] ?? '')) ?></td>
                            <td class="border border-neutral-300 px-2 py-2 text-right tabular-nums"><?= $fmt($qty, 0) ?></td>
                            <td class="border border-neutral-300 px-2 py-2 text-right tabular-nums"><?= $fmt($unitPrice) ?></td>
                            <td class="border border-neutral-300 px-2 py-2 text-right tabular-nums font-semibold"><?= $fmt($netLineAmount) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <h2 class="mb-2 text-[10px] font-bold uppercase tracking-wider text-neutral-900">Order Total</h2>
            <div class="rounded border border-neutral-300">
                <?php if ($summaryRows !== []): ?>
                    <div class="divide-y divide-neutral-200 text-[11px]">
                        <?php foreach ($summaryRows as $summaryRow):
                            $label = trim((string)($summaryRow['label'] ?? ''));
                            if ($label === 'GRAND Total') {
                                $label = 'Net payable amount';
                            }
                            $amount = (float)($summaryRow['amount'] ?? 0);
                            $isGrand = !empty($summaryRow['is_grand']);
                        ?>
                            <div class="flex items-center justify-between gap-3 px-3 py-2 <?= $isGrand ? 'bg-neutral-100 font-bold' : '' ?>">
                                <span><?= $h($label) ?></span>
                                <span class="tabular-nums">₹ <?= $fmt($amount) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="flex items-center justify-between gap-3 px-3 py-3 text-[12px] font-bold">
                        <span>Order total</span>
                        <span class="tabular-nums">₹ <?= $fmt($orderTotal > 0 ? $orderTotal : (float)($invoiceDisplay['pdf_grand_total'] ?? $invoiceDisplay['grand_total'] ?? 0)) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <h2 class="mb-2 text-[10px] font-bold uppercase tracking-wider text-neutral-900">Payment Details</h2>
            <div class="rounded border border-neutral-300">
                <div class="grid grid-cols-3 gap-px bg-neutral-200 text-center text-[10px]">
                    <div class="bg-white px-2 py-2.5">
                        <div class="font-semibold uppercase tracking-wide text-neutral-500">Order Total</div>
                        <div class="mt-1 text-sm font-bold tabular-nums">₹ <?= $fmt($orderTotal) ?></div>
                    </div>
                    <div class="bg-emerald-50 px-2 py-2.5">
                        <div class="font-semibold uppercase tracking-wide text-emerald-700">Received</div>
                        <div class="mt-1 text-sm font-bold tabular-nums text-emerald-800">₹ <?= $fmt($paidTotal) ?></div>
                    </div>
                    <div class="bg-white px-2 py-2.5">
                        <div class="font-semibold uppercase tracking-wide text-neutral-500">Balance</div>
                        <div class="mt-1 text-sm font-bold tabular-nums <?= $pendingTotal > 0.02 ? 'text-red-700' : 'text-neutral-900' ?>">₹ <?= $fmt($pendingTotal) ?></div>
                    </div>
                </div>

                <?php if ($paymentRows === []): ?>
                    <p class="px-3 py-4 text-center text-[11px] text-neutral-500">No payment recorded yet.</p>
                <?php else: ?>
                    <table class="w-full border-collapse text-[10px]">
                        <thead>
                            <tr class="border-t border-neutral-300 bg-neutral-50">
                                <th class="px-2 py-1.5 text-left font-semibold">Receipt</th>
                                <th class="px-2 py-1.5 text-left font-semibold">Date</th>
                                <th class="px-2 py-1.5 text-left font-semibold">Mode</th>
                                <th class="px-2 py-1.5 text-right font-semibold">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paymentRows as $paymentRow):
                                $receiptLabel = trim((string)($paymentRow['receipt_number'] ?? ''));
                                if ($receiptLabel === '') {
                                    $receiptLabel = '#' . (int)($paymentRow['id'] ?? 0);
                                }
                                $payDateRaw = trim((string)($paymentRow['payment_date'] ?? ''));
                                $payDateLabel = $payDateRaw !== '' ? date('d M Y', strtotime($payDateRaw)) : '—';
                                $modeRaw = strtolower(trim((string)($paymentRow['payment_mode'] ?? '')));
                                $modeLabel = $modeLabels[$modeRaw] ?? ucwords(str_replace('_', ' ', $modeRaw));
                                $payAmount = (float)($paymentRow['payment_amount'] ?? 0);
                            ?>
                                <tr class="border-t border-neutral-200">
                                    <td class="px-2 py-2 font-mono"><?= $h($receiptLabel) ?></td>
                                    <td class="px-2 py-2"><?= $h($payDateLabel) ?></td>
                                    <td class="px-2 py-2"><?= $h($modeLabel) ?></td>
                                    <td class="px-2 py-2 text-right tabular-nums font-semibold"><?= $fmt($payAmount) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <?php if ($codTotal > 0.009 && !$isFullyPaid): ?>
                    <p class="border-t border-neutral-200 px-3 py-2 text-[10px] text-neutral-700">
                        <span class="font-semibold">Note:</span> ₹ <?= $fmt($codTotal) ?> is payable on delivery (Cash on Delivery).
                    </p>
                <?php elseif ($pendingTotal > 0.02 && $codTotal <= 0.009): ?>
                    <p class="border-t border-neutral-200 px-3 py-2 text-[10px] text-neutral-700">
                        <span class="font-semibold">Note:</span> Balance of ₹ <?= $fmt($pendingTotal) ?> is pending.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer class="mt-8 border-t border-neutral-300 pt-4 text-center text-[10px] text-neutral-600">
        <p>Thank you for shopping with Exotic India.</p>
        <p class="mt-1">This is an order summary for your reference. Your tax invoice will be issued separately where applicable.</p>
    </footer>
</article>

<style>
    @page {
        size: A4 portrait;
        margin: 12mm 14mm;
    }
    @media print {
        .no-print {
            display: none !important;
        }
        body {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .order-print-sheet {
            padding: 0 !important;
            max-width: none !important;
        }
    }
</style>

<script>
    (function () {
        var params = new URLSearchParams(window.location.search);
        if (params.get('autoprint') === '1') {
            window.addEventListener('load', function () {
                window.setTimeout(function () { window.print(); }, 400);
            });
        }
    })();
</script>
