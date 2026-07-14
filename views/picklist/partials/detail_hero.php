<?php
/** @var array $picklist */
/** @var int $picked */
/** @var int $total */
/** @var int $pct */
/** @var int $plId */
/** @var string $mode desktop|tablet */
require_once __DIR__ . '/ui_constants.php';

$st = (string) ($picklist['status'] ?? 'pending');
$statusClass = $picklistStatusStyles[$st] ?? 'bg-gray-100 text-gray-700 border-gray-200';
$statusLabel = $picklistStatusLabels[$st] ?? $st;
$plNumber = (string) ($picklist['picklist_number'] ?? '');
$pickerName = (string) ($picklist['picker_name'] ?? 'Unassigned');
$createdAt = !empty($picklist['created_at']) ? date('d M Y, H:i', strtotime($picklist['created_at'])) : '—';
$isTablet = ($mode ?? 'desktop') === 'tablet';
?>
<nav class="mb-3" aria-label="Picklist navigation">
    <a href="?page=picklist&action=list"
       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold shadow-sm hover:bg-gray-50 hover:border-gray-400 hover:text-amber-900 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">
        <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i>
        Back to picklists
    </a>
</nav>
<div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04] mb-4">
    <div class="relative px-4 py-5 sm:px-5 sm:py-6">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90">
                        <i class="fas fa-clipboard-list text-amber-700" aria-hidden="true"></i>
                        <span>Warehouse · Picklist<?= $isTablet ? ' · Tablet' : ' · Detail' ?></span>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border <?= $statusClass ?>">
                        <?= htmlspecialchars($statusLabel) ?>
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 font-mono tracking-tight"><?= htmlspecialchars($plNumber) ?></h1>
                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-600">
                    <span><i class="fas fa-user text-gray-400 mr-1" aria-hidden="true"></i> Picker: <span class="font-medium text-gray-800"><?= htmlspecialchars($pickerName) ?></span></span>
                    <span><i class="fas fa-clock text-gray-400 mr-1" aria-hidden="true"></i> Created: <?= htmlspecialchars($createdAt) ?></span>
                </div>
                <div class="mt-4 max-w-md">
                    <div class="flex items-center justify-between text-xs text-gray-600 tabular-nums mb-1.5">
                        <span class="font-medium text-gray-800"><?= (int) $picked ?> / <?= (int) $total ?> picked</span>
                        <span class="font-semibold text-gray-900"><?= (int) $pct ?>%</span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-gray-200 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-amber-600 transition-all" style="width: <?= min(100, max(0, (int) $pct)) ?>%"></div>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <?php if ($isTablet): ?>
                    <a href="?page=picklist&action=view&id=<?= (int) $plId ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-blue-200 bg-blue-50 text-blue-800 text-xs font-semibold shadow-sm hover:bg-blue-100 hover:border-blue-300 transition"
                       title="Switch to desktop detail view">
                        <i class="fas fa-desktop text-[11px] opacity-90" aria-hidden="true"></i>
                        <span>Desktop</span>
                    </a>
                    <a href="?page=picklist&action=view&id=<?= (int) $plId ?>&print=1" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-800 text-xs font-semibold shadow-sm hover:bg-gray-50 hover:border-gray-400 transition"
                       title="Print picklist">
                        <i class="fas fa-print text-[11px] opacity-90" aria-hidden="true"></i>
                        <span>Print</span>
                    </a>
                    <?php if ((int) $total > 0): ?>
                    <a href="?page=picklist&action=print_labels&amp;id=<?= (int) $plId ?>" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 text-xs font-semibold shadow-sm hover:bg-slate-100 hover:border-slate-400 transition"
                       title="Print order labels (Lotus A4 ST-65 sheet)">
                        <i class="fas fa-barcode text-[11px] opacity-90" aria-hidden="true"></i>
                        <span>Print labels</span>
                    </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="?page=picklist&action=tablet&id=<?= (int) $plId ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold shadow-sm hover:bg-emerald-100 hover:border-emerald-300 transition"
                       title="Open tablet picking mode">
                        <i class="fas fa-tablet-alt text-[11px] opacity-90" aria-hidden="true"></i>
                        <span>Tablet</span>
                    </a>
                    <a href="?page=picklist&action=view&id=<?= (int) $plId ?>&print=1" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-800 text-xs font-semibold shadow-sm hover:bg-gray-50 hover:border-gray-400 transition"
                       title="Print picklist">
                        <i class="fas fa-print text-[11px] opacity-90" aria-hidden="true"></i>
                        <span>Print</span>
                    </a>
                    <?php if ((int) $total > 0): ?>
                    <a href="?page=picklist&action=print_labels&amp;id=<?= (int) $plId ?>" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 text-xs font-semibold shadow-sm hover:bg-slate-100 hover:border-slate-400 transition"
                       title="Print order labels (Lotus A4 ST-65 sheet)">
                        <i class="fas fa-barcode text-[11px] opacity-90" aria-hidden="true"></i>
                        <span>Print labels</span>
                    </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
