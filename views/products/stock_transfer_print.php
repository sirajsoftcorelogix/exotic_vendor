<?php
/** @var array $transfer @var array $items @var int $total_qty @var int $line_count @var bool $autoprint */
$transfer = $transfer ?? [];
$items = $items ?? [];
$totalQty = isset($total_qty) ? (int) $total_qty : 0;
$lineCount = isset($line_count) ? (int) $line_count : count($items);
$autoprint = ! empty($autoprint);
?>
<div class="max-w-4xl mx-auto px-4 py-8 print:py-4 print:max-w-none">
    <div class="no-print flex flex-wrap items-center gap-3 mb-6">
        <button type="button" onclick="window.print()"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800 shadow-sm">
            <i class="fas fa-print text-xs" aria-hidden="true"></i>
            Print
        </button>
        <a href="?page=products&action=stock_transfer_items&amp;transfer_id=<?php echo urlencode((string) ($transfer['id'] ?? '')); ?>"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-800 text-sm font-medium hover:bg-gray-50">
            <i class="fas fa-arrow-left text-xs text-gray-500" aria-hidden="true"></i>
            Back to items
        </a>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 shadow-sm print:shadow-none print:border-gray-400 print:rounded-none">
        <header class="border-b border-gray-200 pb-6 mb-6 print:pb-4 print:mb-4">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Stock transfer order</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                <?php echo htmlspecialchars((string) ($transfer['transfer_order_no'] ?? '—')); ?>
            </h1>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">From</p>
                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars((string) ($transfer['source_name'] ?? '—')); ?></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">To</p>
                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars((string) ($transfer['dest_name'] ?? '—')); ?></p>
                </div>
                <?php if (! empty($transfer['dispatch_date'])): ?>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Dispatch date</p>
                        <p><?php echo htmlspecialchars(date('j M Y', strtotime((string) $transfer['dispatch_date']))); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (! empty($transfer['est_delivery_date'])): ?>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Est. delivery</p>
                        <p><?php echo htmlspecialchars(date('j M Y', strtotime((string) $transfer['est_delivery_date']))); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (! empty($transfer['status'])): ?>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Status</p>
                        <p><?php echo htmlspecialchars((string) $transfer['status']); ?></p>
                    </div>
                <?php endif; ?>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Requested by</p>
                    <p><?php echo htmlspecialchars(trim((string) ($transfer['requested_by_name'] ?? '')) ?: '—'); ?></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Dispatched by</p>
                    <p><?php echo htmlspecialchars(trim((string) ($transfer['dispatch_by_name'] ?? '')) ?: '—'); ?></p>
                </div>
            </div>
            <?php
                $vehicleBlock = array_filter([
                    trim((string) ($transfer['booking_no'] ?? '')) !== '' ? 'Booking: ' . $transfer['booking_no'] : '',
                    trim((string) ($transfer['vehicle_no'] ?? '')) !== '' ? 'Vehicle: ' . $transfer['vehicle_no'] : '',
                    trim((string) ($transfer['vehicle_type'] ?? '')) !== '' ? 'Type: ' . $transfer['vehicle_type'] : '',
                    trim((string) ($transfer['driver_name'] ?? '')) !== '' ? 'Driver: ' . $transfer['driver_name'] : '',
                    trim((string) ($transfer['driver_mobile'] ?? '')) !== '' ? 'Mobile: ' . $transfer['driver_mobile'] : '',
                ]);
            ?>
            <?php if ($vehicleBlock !== []): ?>
                <div class="mt-4 pt-4 border-t border-gray-100 text-sm text-gray-700">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Transport</p>
                    <p><?php echo htmlspecialchars(implode(' · ', $vehicleBlock)); ?></p>
                </div>
            <?php endif; ?>
        </header>

        <div class="flex flex-wrap items-baseline justify-between gap-3 mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Items</h2>
            <p class="text-sm text-gray-600 tabular-nums">
                <span class="font-semibold text-gray-900"><?php echo number_format($lineCount); ?></span> line<?php echo $lineCount === 1 ? '' : 's'; ?>
                <span class="text-gray-300 mx-1">·</span>
                <span class="font-semibold text-gray-900"><?php echo number_format($totalQty); ?></span> pcs total
            </p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 print:border-gray-400">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <th scope="col" class="px-3 py-3 whitespace-nowrap w-12">#</th>
                        <th scope="col" class="px-3 py-3 whitespace-nowrap">SKU</th>
                        <th scope="col" class="px-3 py-3 whitespace-nowrap">Item code</th>
                        <th scope="col" class="px-3 py-3 min-w-[12rem]">Description</th>
                        <th scope="col" class="px-3 py-3 text-right whitespace-nowrap">Qty</th>
                        <th scope="col" class="px-3 py-3 min-w-[8rem]">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-gray-500">No items on this transfer.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $idx => $item): ?>
                            <?php
                                $prod = is_array($item['product'] ?? null) ? $item['product'] : [];
                                $title = trim((string) ($prod['title'] ?? ''));
                            ?>
                            <tr class="align-top">
                                <td class="px-3 py-2.5 text-gray-500 tabular-nums"><?php echo $idx + 1; ?></td>
                                <td class="px-3 py-2.5 font-medium text-gray-900 whitespace-nowrap"><?php echo htmlspecialchars(trim((string) ($item['sku'] ?? '')) ?: '—'); ?></td>
                                <td class="px-3 py-2.5 text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars(trim((string) ($item['item_code'] ?? '')) ?: '—'); ?></td>
                                <td class="px-3 py-2.5 text-gray-800"><?php echo htmlspecialchars($title ?: '—'); ?></td>
                                <td class="px-3 py-2.5 text-right font-semibold tabular-nums text-gray-900"><?php echo number_format((int) ($item['transfer_qty'] ?? 0)); ?></td>
                                <td class="px-3 py-2.5 text-gray-600"><?php echo htmlspecialchars(trim((string) ($item['item_notes'] ?? '')) ?: '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (! empty($items)): ?>
                    <tfoot>
                        <tr class="bg-gray-50/80 border-t-2 border-gray-300 font-semibold text-gray-900">
                            <td colspan="4" class="px-3 py-3 text-right text-sm uppercase tracking-wide text-gray-600">Total quantity</td>
                            <td class="px-3 py-3 text-right tabular-nums"><?php echo number_format($totalQty); ?></td>
                            <td class="px-3 py-3"></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <p class="mt-8 pt-6 border-t border-gray-100 text-xs text-gray-400 print:mt-6">
            Printed <?php echo htmlspecialchars(date('j M Y, H:i')); ?>
        </p>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        body { background: #fff !important; }
    }
</style>
<?php if ($autoprint): ?>
<script>
    window.addEventListener('load', function () { window.print(); });
</script>
<?php endif; ?>
