<?php
/**
 * App-layout page for syncing non-terminal order statuses.
 *
 * @var string $ajaxUrl
 * @var int $prefillLimit
 * @var string $prefillMode all|partial|specific
 * @var bool $prefillDryRun
 * @var string $prefillOrders
 * @var string $ordersListUrl
 */
$ajaxUrl = (string) ($ajaxUrl ?? base_url('index.php?page=orders&action=sync_order_statuses_ajax'));
$prefillLimit = (int) ($prefillLimit ?? 250);
$prefillMode = in_array(($prefillMode ?? 'partial'), ['all', 'partial', 'specific'], true) ? $prefillMode : 'partial';
$prefillDryRun = !empty($prefillDryRun);
$prefillOrders = (string) ($prefillOrders ?? '');
$ordersListUrl = (string) ($ordersListUrl ?? base_url('?page=orders&action=list'));
$allowedLimits = [50, 100, 250, 500, 1000, 2500, 5000];
if (!in_array($prefillLimit, $allowedLimits, true)) {
    $allowedLimits[] = $prefillLimit;
    sort($allowedLimits);
}
$inputClass = 'w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500';
?>
<style>
    .oss-log-line { margin: 2px 0; }
    .oss-log-time { color: #94a3b8; }
    .oss-log-ok { color: #86efac; }
    .oss-log-warn { color: #fcd34d; }
    .oss-log-error { color: #fca5a5; }
    .oss-badge { font-size: 11px; font-weight: 800; border-radius: 999px; padding: 3px 8px; }
    .oss-badge-status { background: #ecfdf5; color: #047857; }
    .oss-badge-blue { background: #dbeafe; color: #1d4ed8; }
    .oss-badge-green { background: #dcfce7; color: #166534; }
    .oss-badge-gray { background: #f3f4f6; color: #374151; }
    .oss-badge-amber { background: #fef3c7; color: #92400e; }
    .oss-old { color: #dc2626; }
    .oss-new { color: #059669; font-weight: 800; }
    .oss-muted { color: #6b7280; font-style: italic; }
    .oss-mode:has(input:checked) { border-color: #d9822b; background: #fffbeb; }
</style>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-sync-alt text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Orders · Status sync</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Sync order <span class="text-amber-800">statuses</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Updates local orders that are not Cancelled, Returned, or Shipped.
                    Vendor API is queried oldest-first with <code class="text-xs bg-gray-100 px-1 rounded">only_status=1</code>.
                    Run all pending orders or a partial set, watch the progress bar, and stop at any time.
                </p>
                <p class="mt-3 text-sm font-semibold text-gray-800">
                    Pending non-terminal orders:
                    <span id="ossPendingCount" class="text-amber-800">…</span>
                </p>
            </div>
            <div class="flex shrink-0 lg:pl-4 lg:self-center">
                <a href="<?= htmlspecialchars($ordersListUrl, ENT_QUOTES, 'UTF-8') ?>"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl border border-amber-300 bg-white text-amber-900 text-sm font-semibold shadow-sm hover:bg-amber-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-list text-xs opacity-95" aria-hidden="true"></i>
                    Back to orders
                </a>
            </div>
        </div>
    </div>

    <form id="ossForm" class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <div class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 border-b border-amber-100/80">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                    <i class="fas fa-sliders-h text-sm" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900">Run options</h2>
                    <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Choose All, Partial, or Specific order numbers. One vendor-API batch at a time.</p>
                </div>
            </div>
        </div>

        <div class="p-5 sm:p-6 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <label class="oss-mode block border-2 border-gray-200 rounded-xl p-3 cursor-pointer">
                    <input class="oss-field mr-1" type="radio" name="oss_mode" value="all" <?= $prefillMode === 'all' ? 'checked' : '' ?>>
                    <strong class="text-sm text-gray-900">All pending</strong>
                    <span class="block text-xs text-gray-500 mt-1">Queue every non-terminal order, oldest first.</span>
                </label>
                <label class="oss-mode block border-2 border-gray-200 rounded-xl p-3 cursor-pointer">
                    <input class="oss-field mr-1" type="radio" name="oss_mode" value="partial" <?= $prefillMode === 'partial' ? 'checked' : '' ?>>
                    <strong class="text-sm text-gray-900">Partial</strong>
                    <span class="block text-xs text-gray-500 mt-1">Only the oldest N orders for a controlled run.</span>
                </label>
                <label class="oss-mode block border-2 border-gray-200 rounded-xl p-3 cursor-pointer">
                    <input class="oss-field mr-1" type="radio" name="oss_mode" value="specific" <?= $prefillMode === 'specific' ? 'checked' : '' ?>>
                    <strong class="text-sm text-gray-900">Specific orders</strong>
                    <span class="block text-xs text-gray-500 mt-1">Sync only the order numbers you enter.</span>
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div id="ossPartialFields" <?= $prefillMode === 'partial' ? '' : 'hidden' ?>>
                    <label class="block text-xs font-bold text-gray-700 mb-1" for="ossLimit">Partial size (oldest first)</label>
                    <select id="ossLimit" class="oss-field <?= $inputClass ?>">
                        <?php foreach ($allowedLimits as $n): ?>
                            <option value="<?= (int) $n ?>" <?= (int) $n === $prefillLimit ? 'selected' : '' ?>><?= (int) $n ?> orders</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1" for="ossBatchSize">API batch size</label>
                    <select id="ossBatchSize" class="oss-field <?= $inputClass ?>">
                        <option value="20">20 orders / request</option>
                        <option value="50" selected>50 orders / request</option>
                        <option value="100">100 orders / request</option>
                    </select>
                </div>
            </div>

            <div id="ossSpecificFields" <?= $prefillMode === 'specific' ? '' : 'hidden' ?>>
                <label class="block text-xs font-bold text-gray-700 mb-1" for="ossOrderIds">Order numbers</label>
                <textarea id="ossOrderIds" class="oss-field <?= $inputClass ?>" rows="3" placeholder="3114463, 3114147"><?= htmlspecialchars($prefillOrders, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <label class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                <input id="ossDryRun" class="oss-field w-4 h-4 text-amber-600 rounded border-gray-300" type="checkbox" <?= $prefillDryRun ? 'checked' : '' ?>>
                Dry run — preview status changes without writing to the database
            </label>

            <div class="flex flex-wrap justify-end gap-3 pt-2 border-t border-gray-100">
                <button type="button" id="ossStopBtn" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    <i class="fas fa-stop"></i> Stop
                </button>
                <button type="submit" id="ossStartBtn" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] hover:from-[#c57526] hover:to-[#b86a22] text-white text-sm font-semibold shadow-sm">
                    <i class="fas fa-play"></i> Start sync
                </button>
            </div>
        </div>
    </form>

    <div id="ossProgressWrap" class="hidden bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
            <span id="ossProgressLabel" class="text-sm font-semibold text-gray-800">Waiting…</span>
            <span class="text-xs font-bold text-gray-600">
                <span id="ossProgressCount">0 / 0</span> · <span id="ossProgressPercent">0%</span>
            </span>
        </div>
        <div class="p-5 space-y-3">
            <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                <div id="ossProgressBar" class="bg-emerald-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
            <div id="ossLog" class="max-h-60 overflow-y-auto bg-slate-900 text-slate-100 rounded-xl p-3 text-xs font-mono leading-relaxed"></div>
        </div>
    </div>

    <div id="ossResultWrap" class="hidden bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Results</h2>
        </div>
        <div class="p-5 space-y-3">
            <div id="ossBadges" class="flex flex-wrap gap-2"></div>
            <div id="ossDetails" class="max-h-60 overflow-y-auto border border-gray-200 rounded-xl p-3 bg-gray-50 text-xs font-mono space-y-1"></div>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('assets/js/sync_order_statuses.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    createOrderStatusSyncController({
        endpoint: <?= json_encode($ajaxUrl) ?>,
        formSelector: '.oss-field',
        modeSelector: 'input[name="oss_mode"]',
        ids: {
            form: 'ossForm',
            startBtn: 'ossStartBtn',
            stopBtn: 'ossStopBtn',
            limit: 'ossLimit',
            batchSize: 'ossBatchSize',
            orderIds: 'ossOrderIds',
            dryRun: 'ossDryRun',
            pendingCount: 'ossPendingCount',
            progressWrap: 'ossProgressWrap',
            progressBar: 'ossProgressBar',
            progressPercent: 'ossProgressPercent',
            progressLabel: 'ossProgressLabel',
            progressCount: 'ossProgressCount',
            log: 'ossLog',
            badges: 'ossBadges',
            details: 'ossDetails',
            resultWrap: 'ossResultWrap',
            partialFields: 'ossPartialFields',
            specificFields: 'ossSpecificFields'
        }
    }).bind();
</script>
