<?php
require_once dirname(dirname(__DIR__)) . '/helpers/direct_purchase_currency.php';

$purchase = $data['purchase'] ?? [];
$warehouseName = trim((string) ($data['warehouse_name'] ?? ''));
$putawayItems = $data['putaway_items'] ?? [];
$fulfillmentItems = $data['fulfillment_items'] ?? [];

$flash = $_SESSION['direct_purchase_flash'] ?? null;
if ($flash) {
    unset($_SESSION['direct_purchase_flash']);
}

$dpId = (int) ($purchase['id'] ?? 0);

$dpFormatDate = static function ($value): string {
    if (empty($value)) {
        return '—';
    }
    return date('j M Y', strtotime((string) $value));
};

$dpFormatQty = static function (float $qty): string {
    $formatted = number_format($qty, 3, '.', '');
    return rtrim(rtrim($formatted, '0'), '.') ?: '0';
};

$dpStatusLabel = static function (string $status, int $backorderStatus): string {
    if ($status === 'pending') {
        return 'Pending';
    }
    if ($backorderStatus > 0) {
        return 'Backordered';
    }
    if ($status === 'ready_for_dispatch') {
        return 'Partially shipped';
    }
    return 'Unfulfilled';
};
?>
<div class="w-full px-4 sm:px-6 py-8 dp-post-save-page">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04] mb-6 dp-page-chrome">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-8">
            <div class="min-w-0 flex-1">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-boxes text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Purchasing · Direct purchase</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">Stock putaway &amp; order fulfillment</h1>
                <p class="mt-3 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm sm:text-base text-gray-600 leading-relaxed">
                    <span>Invoice <span class="font-mono font-semibold text-gray-900"><?= htmlspecialchars((string) ($purchase['invoice_number'] ?? '')) ?></span></span>
                    <span class="text-gray-400" aria-hidden="true">·</span>
                    <span><?= htmlspecialchars((string) ($purchase['vendor_name'] ?? '')) ?></span>
                    <span class="text-gray-400" aria-hidden="true">·</span>
                    <span><span class="font-semibold text-gray-700">Warehouse</span> <?= htmlspecialchars($warehouseName !== '' ? $warehouseName : '—') ?></span>
                    <span class="text-gray-400" aria-hidden="true">·</span>
                    <span><span class="font-semibold text-gray-700">Invoice date</span> <?= htmlspecialchars($dpFormatDate($purchase['invoice_date'] ?? '')) ?></span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0 lg:pt-1">
                <a href="?page=direct_purchase&action=add"
                    class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 shadow-sm">
                    <i class="fas fa-plus text-xs" aria-hidden="true"></i>
                    New purchase
                </a>
                <a href="?page=direct_purchase&action=edit&amp;id=<?= $dpId ?>"
                    class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50">
                    View purchase
                </a>
                <a href="?page=direct_purchase&action=list"
                    class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50">
                    All purchases
                </a>
            </div>
        </div>
    </div>

    <?php if (is_array($flash) && trim((string) ($flash['text'] ?? '')) !== ''): ?>
        <?php $ft = ($flash['type'] ?? '') === 'success' ? 'success' : 'error';
        $ring = $ft === 'success' ? 'border-emerald-200/80 bg-emerald-50/90 text-emerald-900' : 'border-red-200/80 bg-red-50/90 text-red-900';
        ?>
        <div class="mb-6 rounded-xl border px-4 py-3 text-sm font-medium shadow-sm <?= $ring ?> dp-page-chrome" role="status">
            <?= htmlspecialchars((string) $flash['text']) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section id="dp-putaway-section" class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
            <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50/60 via-white to-white flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-gray-900">Stock putaway</h2>
                    <p class="mt-1 text-sm text-gray-600">Remaining qty to shelve in <span class="font-medium text-gray-800"><?= htmlspecialchars($warehouseName !== '' ? $warehouseName : 'warehouse') ?></span> after order allocation.</p>
                </div>
                <button type="button"
                    class="dp-print-btn shrink-0 inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 shadow-sm"
                    data-dp-print-target="dp-putaway-section"
                    title="Print stock putaway list" aria-label="Print stock putaway list">
                    <i class="fas fa-print text-xs" aria-hidden="true"></i>
                    Print
                </button>
            </div>
            <div class="dp-print-only px-5 pt-4 text-xs text-gray-700 border-b border-gray-100">
                <strong>Stock putaway</strong>
                · Invoice <?= htmlspecialchars((string) ($purchase['invoice_number'] ?? '')) ?>
                · <?= htmlspecialchars((string) ($purchase['vendor_name'] ?? '')) ?>
                · Warehouse <?= htmlspecialchars($warehouseName !== '' ? $warehouseName : '—') ?>
                · <?= htmlspecialchars($dpFormatDate($purchase['invoice_date'] ?? '')) ?>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <th class="px-5 py-3.5 w-16">Image</th>
                            <th class="px-5 py-3.5">SKU</th>
                            <th class="px-5 py-3.5 text-right w-24">QTY</th>
                            <th class="px-5 py-3.5">Location</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($putawayItems === []): ?>
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-gray-500">
                                    No putaway qty — purchased stock is fully allocated to pending orders.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($putawayItems as $row): ?>
                                <?php
                                $img = trim((string) ($row['image'] ?? ''));
                                $sku = (string) ($row['sku'] ?? '');
                                $location = trim((string) ($row['location'] ?? ''));
                                ?>
                                <tr class="hover:bg-emerald-50/20">
                                    <td class="px-5 py-4">
                                        <?php if ($img !== ''): ?>
                                            <img src="<?= htmlspecialchars($img) ?>" alt="" class="h-12 w-12 rounded-lg border border-gray-200 object-cover bg-gray-50" loading="lazy">
                                        <?php else: ?>
                                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-400">
                                                <i class="fas fa-image text-sm" aria-hidden="true"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="font-mono font-medium text-gray-900"><?= htmlspecialchars($sku) ?></span>
                                        <?php if (!empty($row['item_code'])): ?>
                                            <div class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars((string) $row['item_code']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-4 text-right font-semibold tabular-nums text-gray-900">
                                        <?= htmlspecialchars($dpFormatQty((float) ($row['qty'] ?? 0))) ?>
                                    </td>
                                    <td class="px-5 py-4 text-gray-700">
                                        <?= $location !== '' ? htmlspecialchars($location) : '<span class="text-gray-400">—</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="dp-fulfillment-section" class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
            <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-sky-50/60 via-white to-white flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-gray-900">Order fulfillment</h2>
                    <p class="mt-1 text-sm text-gray-600">Pending orders matched from this purchase (pending, unfulfilled, partially shipped, backordered).</p>
                </div>
                <button type="button"
                    class="dp-print-btn shrink-0 inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 shadow-sm"
                    data-dp-print-target="dp-fulfillment-section"
                    title="Print order fulfillment list" aria-label="Print order fulfillment list">
                    <i class="fas fa-print text-xs" aria-hidden="true"></i>
                    Print
                </button>
            </div>
            <div class="dp-print-only px-5 pt-4 text-xs text-gray-700 border-b border-gray-100">
                <strong>Order fulfillment</strong>
                · Invoice <?= htmlspecialchars((string) ($purchase['invoice_number'] ?? '')) ?>
                · <?= htmlspecialchars((string) ($purchase['vendor_name'] ?? '')) ?>
                · Warehouse <?= htmlspecialchars($warehouseName !== '' ? $warehouseName : '—') ?>
                · <?= htmlspecialchars($dpFormatDate($purchase['invoice_date'] ?? '')) ?>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <th class="px-5 py-3.5 w-16">Image</th>
                            <th class="px-5 py-3.5">SKU</th>
                            <th class="px-5 py-3.5 text-right w-28">Order QTY</th>
                            <th class="px-5 py-3.5 text-right w-28">Fulfill QTY</th>
                            <th class="px-5 py-3.5 text-right w-32">Still pending</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($fulfillmentItems === []): ?>
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-gray-500">
                                    No pending orders found for items in this purchase.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fulfillmentItems as $row): ?>
                                <?php
                                $img = trim((string) ($row['image'] ?? ''));
                                $sku = (string) ($row['sku'] ?? '');
                                $orders = $row['orders'] ?? [];
                                ?>
                                <tr class="hover:bg-sky-50/20 align-top">
                                    <td class="px-5 py-4">
                                        <?php if ($img !== ''): ?>
                                            <img src="<?= htmlspecialchars($img) ?>" alt="" class="h-12 w-12 rounded-lg border border-gray-200 object-cover bg-gray-50" loading="lazy">
                                        <?php else: ?>
                                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-400">
                                                <i class="fas fa-image text-sm" aria-hidden="true"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="font-mono font-medium text-gray-900"><?= htmlspecialchars($sku) ?></span>
                                        <?php if (!empty($row['item_code'])): ?>
                                            <div class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars((string) $row['item_code']) ?></div>
                                        <?php endif; ?>
                                        <?php if (is_array($orders) && $orders !== []): ?>
                                            <ul class="mt-2 space-y-1 text-xs text-gray-600">
                                                <?php foreach ($orders as $orderRow): ?>
                                                    <?php
                                                    $orderNo = trim((string) ($orderRow['order_number'] ?? ''));
                                                    $orderQty = (float) ($orderRow['quantity'] ?? 0);
                                                    $orderStatus = strtolower(trim((string) ($orderRow['status'] ?? '')));
                                                    $backorderStatus = (int) ($orderRow['backorder_status'] ?? 0);
                                                    ?>
                                                    <li>
                                                        <a href="?page=orders&amp;action=list&amp;order_number=<?= urlencode($orderNo) ?>"
                                                            class="font-mono text-sky-700 hover:text-sky-900 hover:underline"><?= htmlspecialchars($orderNo) ?></a>
                                                        <span class="text-gray-400">·</span>
                                                        <?= htmlspecialchars($dpFormatQty($orderQty)) ?>
                                                        <span class="text-gray-400">·</span>
                                                        <span class="text-gray-500"><?= htmlspecialchars($dpStatusLabel($orderStatus, $backorderStatus)) ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-4 text-right tabular-nums text-gray-800">
                                        <?= htmlspecialchars($dpFormatQty((float) ($row['order_qty'] ?? 0))) ?>
                                    </td>
                                    <td class="px-5 py-4 text-right font-semibold tabular-nums text-sky-800">
                                        <?= htmlspecialchars($dpFormatQty((float) ($row['fulfill_qty'] ?? 0))) ?>
                                    </td>
                                    <td class="px-5 py-4 text-right tabular-nums text-amber-800">
                                        <?= htmlspecialchars($dpFormatQty((float) ($row['remaining_order_qty'] ?? 0))) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<style>
    .dp-print-only {
        display: none;
    }

    @media print {
        html, body {
            height: auto !important;
            overflow: visible !important;
            background: #fff !important;
        }

        body > .flex,
        main,
        .dp-post-save-page {
            display: block !important;
            height: auto !important;
            overflow: visible !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        #sidebar,
        header,
        footer,
        .dp-page-chrome,
        .dp-print-btn,
        .no-print {
            display: none !important;
        }

        body.dp-print-putaway #dp-fulfillment-section,
        body.dp-print-fulfillment #dp-putaway-section {
            display: none !important;
        }

        .dp-post-save-page .grid {
            display: block !important;
        }

        #dp-putaway-section,
        #dp-fulfillment-section {
            break-inside: avoid;
            box-shadow: none !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0 !important;
            margin: 0 !important;
        }

        .dp-print-only {
            display: block !important;
        }

        #dp-putaway-section .bg-gradient-to-r,
        #dp-fulfillment-section .bg-gradient-to-r {
            background: #fff !important;
        }

        #dp-fulfillment-section a {
            color: inherit !important;
            text-decoration: none !important;
        }

        img {
            max-width: 48px;
            max-height: 48px;
        }
    }
</style>

<script>
(function () {
    function dpCleanupPrintMode() {
        document.body.classList.remove('dp-print-putaway', 'dp-print-fulfillment');
    }

    window.addEventListener('afterprint', dpCleanupPrintMode);

    document.querySelectorAll('[data-dp-print-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = btn.getAttribute('data-dp-print-target');
            dpCleanupPrintMode();
            if (target === 'dp-putaway-section') {
                document.body.classList.add('dp-print-putaway');
            } else if (target === 'dp-fulfillment-section') {
                document.body.classList.add('dp-print-fulfillment');
            }
            window.print();
        });
    });
})();
</script>
