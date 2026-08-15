<?php
/** @var array $data */
require_once __DIR__ . '/partials/item_helpers.php';
require_once __DIR__ . '/partials/ui_constants.php';

$picklist = $data['picklist'] ?? [];
$items = $data['items'] ?? [];
$plId = (int) ($picklist['id'] ?? 0);
$showBookColumns = picklist_any_book_items($items);

$split = picklist_split_items_for_print($items);
$fullItems = $split['full'];
$shortItems = $split['short'];

$picked = 0;
$pending = 0;
$notAvailable = 0;
$partiallyAvailable = 0;
foreach ($items as $it) {
    $itemStatus = (string) ($it['status'] ?? 'pending');
    if ($itemStatus === 'picked') {
        $picked++;
    } elseif ($itemStatus === 'not_available') {
        $notAvailable++;
    } elseif ($itemStatus === 'partially_available') {
        $partiallyAvailable++;
    } else {
        $pending++;
    }
}
$total = count($items);
$resolved = $total - $pending;
$pct = $total > 0 ? round(($resolved / $total) * 100) : 0;

$flash = $_SESSION['picklist_flash'] ?? null;
if ($flash) {
    unset($_SESSION['picklist_flash']);
}
?>
<div class="w-full px-2 py-4 sm:px-3">
<?php
$mode = 'desktop';
include __DIR__ . '/partials/detail_hero.php';
?>
<?php if (is_array($flash) && trim((string) ($flash['text'] ?? '')) !== ''): ?>
    <?php $ok = ($flash['type'] ?? '') === 'success'; ?>
    <div class="mb-4 rounded-xl border px-4 py-3 text-sm <?= $ok ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-red-200 bg-red-50 text-red-900' ?>">
        <?= htmlspecialchars((string) $flash['text']) ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden" id="picklist-tabs-container">
    <!-- Tabs Navigation Header -->
    <div class="border-b border-gray-200 bg-gray-50/70 px-4 pt-3 pb-0 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 border-b-0 -mb-px" role="tablist" aria-label="Picklist split sections">
            <button type="button"
                    role="tab"
                    id="tab-btn-full"
                    aria-selected="true"
                    aria-controls="tab-panel-full"
                    class="js-picklist-tab-btn inline-flex items-center gap-2 px-4 py-2.5 font-semibold text-sm rounded-t-xl border-t border-l border-r transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 border-gray-300 bg-white text-emerald-900 shadow-xs"
                    data-tab="full">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold">A</span>
                <span>Full Quantity Available</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100/80 text-emerald-800 tabular-nums">
                    <?= count($fullItems) ?>
                </span>
            </button>

            <button type="button"
                    role="tab"
                    id="tab-btn-short"
                    aria-selected="false"
                    aria-controls="tab-panel-short"
                    class="js-picklist-tab-btn inline-flex items-center gap-2 px-4 py-2.5 font-medium text-sm rounded-t-xl border-t border-l border-r border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-100/60 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                    data-tab="short">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-800 text-xs font-bold">B</span>
                <span>Partially Available &amp; Not Available</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100/80 text-amber-800 tabular-nums">
                    <?= count($shortItems) ?>
                </span>
            </button>
        </div>
    </div>

    <?php if ($items !== []): ?>
        <div class="px-3 py-3 border-b border-gray-100 bg-gray-50/50 flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none text-sm font-semibold text-gray-800 shrink-0">
                    <input type="checkbox" id="picklist-select-all" class="w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500" aria-label="Select all items in active tab">
                    <span class="whitespace-nowrap">Select all</span>
                </label>
                <button type="button"
                        id="picklist-bulk-pick-btn"
                        disabled
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold shadow-sm hover:bg-emerald-100 disabled:opacity-50 disabled:cursor-not-allowed transition whitespace-nowrap">
                    <i class="fas fa-check text-[11px]" aria-hidden="true"></i> Mark picked
                </button>
                <button type="button"
                        id="picklist-bulk-unpick-btn"
                        disabled
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-xs font-semibold shadow-sm hover:bg-amber-100 disabled:opacity-50 disabled:cursor-not-allowed transition whitespace-nowrap">
                    <i class="fas fa-undo text-[11px]" aria-hidden="true"></i> Revert status
                </button>
                <span id="picklist-selected-count" class="text-xs font-medium text-gray-600 tabular-nums whitespace-nowrap">0 selected</span>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <a href="?page=picklist&action=view&id=<?= (int) $plId ?>&print=full"
                   target="_blank"
                   rel="noopener noreferrer"
                   id="tab-print-full-btn"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-900 text-xs font-semibold shadow-xs hover:bg-emerald-100 transition"
                   title="Print List A (Full Quantity Available)">
                    <i class="fas fa-print text-emerald-700" aria-hidden="true"></i>
                    <span>Print Full List</span>
                </a>

                <a href="?page=picklist&action=view&id=<?= (int) $plId ?>&print=short"
                   target="_blank"
                   rel="noopener noreferrer"
                   id="tab-print-short-btn"
                   class="hidden inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-amber-300 bg-amber-50 text-amber-900 text-xs font-semibold shadow-xs hover:bg-amber-100 transition"
                   title="Print List B (Partially Available & Not Available)">
                    <i class="fas fa-print text-amber-700" aria-hidden="true"></i>
                    <span>Print Short/Unavailable List</span>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tab Panels -->
    <div id="tab-panel-full" role="tabpanel" aria-labelledby="tab-btn-full" class="js-picklist-tab-panel">
        <?php
        $items = $fullItems;
        $tabType = 'full';
        include __DIR__ . '/partials/items_table.php';
        ?>
    </div>

    <div id="tab-panel-short" role="tabpanel" aria-labelledby="tab-btn-short" class="js-picklist-tab-panel hidden">
        <?php
        $items = $shortItems;
        $tabType = 'short';
        include __DIR__ . '/partials/items_table.php';
        ?>
    </div>
</div>
</div>

<script>
(function() {
    const tabBtns = document.querySelectorAll('.js-picklist-tab-btn');
    const tabPanels = document.querySelectorAll('.js-picklist-tab-panel');
    const printFullBtn = document.getElementById('tab-print-full-btn');
    const printShortBtn = document.getElementById('tab-print-short-btn');

    function switchTab(targetTab) {
        tabBtns.forEach(btn => {
            const isTarget = btn.getAttribute('data-tab') === targetTab;
            btn.setAttribute('aria-selected', isTarget ? 'true' : 'false');
            if (isTarget) {
                btn.className = 'js-picklist-tab-btn inline-flex items-center gap-2 px-4 py-2.5 font-semibold text-sm rounded-t-xl border-t border-l border-r transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 border-gray-300 bg-white ' +
                    (targetTab === 'full' ? 'text-emerald-900' : 'text-amber-900') + ' shadow-xs';
            } else {
                btn.className = 'js-picklist-tab-btn inline-flex items-center gap-2 px-4 py-2.5 font-medium text-sm rounded-t-xl border-t border-l border-r border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-100/60 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500';
            }
        });

        tabPanels.forEach(panel => {
            if (panel.id === 'tab-panel-' + targetTab) {
                panel.classList.remove('hidden');
            } else {
                panel.classList.add('hidden');
            }
        });

        if (printFullBtn && printShortBtn) {
            if (targetTab === 'full') {
                printFullBtn.classList.remove('hidden');
                printShortBtn.classList.add('hidden');
            } else {
                printFullBtn.classList.add('hidden');
                printShortBtn.classList.remove('hidden');
            }
        }

        if (window.updatePicklistBulkBar) {
            window.updatePicklistBulkBar();
        }
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.getAttribute('data-tab');
            if (tab) {
                switchTab(tab);
            }
        });
    });
})();
</script>

<?php require_once __DIR__ . '/partials/confirm_modal.php'; ?>
<?php require_once __DIR__ . '/partials/confirm_delete_script.php'; ?>
<?php require_once __DIR__ . '/partials/unpick_script.php'; ?>
<?php require_once __DIR__ . '/partials/bulk_actions_script.php'; ?>
<?php require_once __DIR__ . '/partials/image_lightbox.php'; ?>
<?php require_once __DIR__ . '/partials/copy_sku_script.php'; ?>
<?php require_once __DIR__ . '/partials/availability_script.php'; ?>
<?php require_once __DIR__ . '/partials/row_menu_script.php'; ?>
