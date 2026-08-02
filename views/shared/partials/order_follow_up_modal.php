<?php
/** @var string $displayOrderNumber */
/** @var string $orderStatusPage */
/** @var list<array<string,mixed>> $order */
/** @var bool $canFollowUpOrder */

require_once dirname(__DIR__, 3) . '/helpers/order_follow_up.php';

$displayOrderNumber = trim((string) ($displayOrderNumber ?? ''));
$orderStatusPage = in_array(trim((string) ($orderStatusPage ?? '')), ['orders', 'posorders'], true)
    ? trim((string) $orderStatusPage)
    : 'posorders';
$canFollowUpOrder = !empty($canFollowUpOrder);
$orderLines = is_array($order ?? null) ? $order : [];
$startUrl = base_url('index.php?page=order_follow_up&action=start');

$connObj = $conn ?? $GLOBALS['conn'] ?? null;
$returnedLineIds = ($connObj instanceof mysqli && $displayOrderNumber !== '')
    ? order_follow_up_get_returned_line_ids($connObj, $displayOrderNumber, $orderLines)
    : [];
$returnedLookup = array_fill_keys($returnedLineIds, true);
?>
<?php if ($canFollowUpOrder && $displayOrderNumber !== ''): ?>
<div id="orderFollowUpModal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-black/40 p-4">
    <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-gray-200 bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Follow-up order</h2>
                <p class="mt-1 text-sm text-gray-600">Create a linked order from <?= htmlspecialchars($displayOrderNumber, ENT_QUOTES, 'UTF-8') ?>.</p>
            </div>
            <button type="button" id="orderFollowUpModalClose" class="text-gray-400 hover:text-gray-700" aria-label="Close">&times;</button>
        </div>
        <form id="orderFollowUpForm" method="post" action="<?= htmlspecialchars($startUrl, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="source_order_number" value="<?= htmlspecialchars($displayOrderNumber, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="return_page" value="<?= htmlspecialchars($orderStatusPage, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="follow_up_type" id="orderFollowUpType" value="copy">

            <div class="mb-4">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Type</label>
                <div class="flex flex-wrap gap-2">
                    <button type="button" data-follow-up-type="reship" class="order-follow-up-type-btn rounded border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50">Reship</button>
                    <button type="button" data-follow-up-type="replace" class="order-follow-up-type-btn rounded border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50">Replace</button>
                    <button type="button" data-follow-up-type="copy" class="order-follow-up-type-btn rounded border border-indigo-600 bg-indigo-50 px-3 py-1.5 text-sm font-medium text-indigo-900">Copy</button>
                </div>
            </div>

            <div class="mb-4" id="orderFollowUpPricingWrap">
                <label for="orderFollowUpPricingMode" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Pricing</label>
                <select name="pricing_mode" id="orderFollowUpPricingMode" class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
                    <option value="catalog">Current catalog prices</option>
                    <option value="same_as_original">Same prices as last order</option>
                    <option value="waived">Waived (₹0)</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">Order Lines</label>
                <div class="max-h-48 space-y-2 overflow-y-auto rounded border border-gray-200 p-3">
                    <?php foreach ($orderLines as $line): ?>
                        <?php if (!is_array($line)) { continue; } ?>
                        <?php
                        $lineId = (int) ($line['id'] ?? 0);
                        $label = trim((string) ($line['item_code'] ?? ''));
                        $name = trim((string) ($line['itemname'] ?? $line['title'] ?? ''));
                        $qty = (int) ($line['quantity'] ?? 1);
                        if ($lineId <= 0) { continue; }
                        $isReturned = !empty($returnedLookup[$lineId]);
                        ?>
                        <label class="flex items-center justify-between gap-2 text-sm order-follow-up-line-label transition-opacity <?= $isReturned ? 'is-returned' : 'is-not-returned' ?>">
                            <span class="flex items-start gap-2">
                                <input type="checkbox" name="line_ids[]" value="<?= $lineId ?>" data-is-returned="<?= $isReturned ? '1' : '0' ?>" checked class="mt-1 order-follow-up-line-checkbox">
                                <span>
                                    <span class="font-medium"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($name !== ''): ?>
                                        <span class="text-gray-600"> — <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <span class="text-gray-500"> × <?= $qty ?></span>
                                </span>
                            </span>
                            <?php if ($isReturned): ?>
                                <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20">Returned</span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <p id="orderFollowUpFormError" class="mr-auto hidden self-center text-sm text-red-600"></p>
                <button type="button" id="orderFollowUpCancel" class="rounded border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">Cancel</button>
                <button type="submit" class="rounded bg-indigo-700 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-800">Open in POS</button>
            </div>
        </form>
    </div>
</div>
<script src="<?= htmlspecialchars(base_url('assets/js/order_follow_up_modal.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endif; ?>
