<?php
/**
 * @var array<int, array<string, mixed>> $items
 * @var bool $showBookColumns
 * @var array<string, string> $itemStatusStyles
 * @var array<string, string> $itemStatusLabels
 * @var string $tabType 'full' | 'short'
 */
$items = $items ?? [];
$showBookColumns = !empty($showBookColumns);
$tabType = $tabType ?? 'full';
?>
<div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
        <thead>
            <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                <th class="px-3 py-3 w-10"></th>
                <th class="px-3 py-3 whitespace-nowrap">#</th>
                <th class="px-2 py-3 whitespace-nowrap w-44 max-w-44">Location</th>
                <th class="px-3 py-3 whitespace-nowrap">
                    <span class="inline-flex items-center gap-1.5">
                        Order #
                        <button type="button"
                                class="js-picklist-copy-column shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-md border border-gray-200 bg-white text-gray-500 hover:text-amber-700 hover:border-amber-300 hover:bg-amber-50/50 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                                data-copy-column="order"
                                data-copy-label="Order numbers"
                                title="Copy all order numbers"
                                aria-label="Copy all order numbers">
                            <i class="fas fa-copy text-[10px]" aria-hidden="true"></i>
                        </button>
                    </span>
                </th>
                <th class="px-3 py-3 whitespace-nowrap">
                    <span class="inline-flex items-center gap-1.5">
                        SKU
                        <button type="button"
                                class="js-picklist-copy-column shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-md border border-gray-200 bg-white text-gray-500 hover:text-amber-700 hover:border-amber-300 hover:bg-amber-50/50 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                                data-copy-column="sku"
                                data-copy-label="SKUs"
                                title="Copy all SKUs"
                                aria-label="Copy all SKUs">
                            <i class="fas fa-copy text-[10px]" aria-hidden="true"></i>
                        </button>
                    </span>
                </th>
                <th class="px-3 py-3 min-w-[12rem]">Item Title</th>
                <th class="px-3 py-3 whitespace-nowrap">Image</th>
                <th class="px-3 py-3 whitespace-nowrap">Phys Qty</th>
                <th class="px-3 py-3 whitespace-nowrap">Order Qty</th>
                <?php if ($tabType === 'short'): ?>
                    <th class="px-3 py-3 whitespace-nowrap text-amber-800">Shortfall</th>
                <?php endif; ?>
                <?php if ($showBookColumns): ?>
                    <th class="px-3 py-3 whitespace-nowrap">Publisher</th>
                <?php endif; ?>
                <th class="px-3 py-3 whitespace-nowrap">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($items as $idx => $item): ?>
                <?php
                $itemStatus = (string) ($item['status'] ?? 'pending');
                $isPicked = $itemStatus === 'picked';
                $isPending = $itemStatus === 'pending';
                $isNotAvailable = $itemStatus === 'not_available';
                $isPartiallyAvailable = $itemStatus === 'partially_available';
                $canRevert = in_array($itemStatus, ['picked', 'not_available', 'partially_available'], true);
                $isBook = picklist_item_is_book($item);
                $imageUrl = picklist_item_image_url($item);
                $itemStatusClass = $itemStatusStyles[$itemStatus] ?? $itemStatusStyles['pending'];
                $itemStatusLabel = $itemStatusLabels[$itemStatus] ?? ucfirst(str_replace('_', ' ', $itemStatus));
                $rowToneClass = $isPicked
                    ? 'bg-emerald-50/25'
                    : ($isNotAvailable ? 'bg-red-50/20' : ($isPartiallyAvailable ? 'bg-orange-50/20' : ''));
                $shortfallQty = picklist_item_shortfall_qty($item);
                ?>
                <tr class="picklist-select-row cursor-pointer hover:bg-amber-50/40 transition-colors <?= $rowToneClass ?>">
                    <td class="px-3 py-3 align-middle">
                        <input type="checkbox"
                               class="picklist-item-cb w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500 pointer-events-none"
                               value="<?= (int) ($item['id'] ?? 0) ?>"
                               data-status="<?= htmlspecialchars($itemStatus, ENT_QUOTES, 'UTF-8') ?>"
                               aria-label="Select item">
                    </td>
                    <td class="px-3 py-3 align-middle tabular-nums text-gray-500"><?= $idx + 1 ?></td>
                    <?php $locationText = (string) ($item['warehouse_location'] ?: '—'); ?>
                    <?php $removeConfirm = 'Remove this item from the picklist? The order will be set back to Item Received where applicable.'; ?>
                    <td class="picklist-row-actions px-2 py-3 align-middle max-w-44">
                        <div class="flex items-center gap-1 min-w-0">
                            <div class="relative shrink-0 picklist-row-menu">
                                <button type="button"
                                        class="picklist-row-menu-btn inline-flex items-center justify-center w-7 h-7 rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900 hover:border-gray-300 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                        title="Item actions">
                                    <i class="fas fa-ellipsis-v text-xs" aria-hidden="true"></i>
                                </button>
                                <div class="picklist-row-menu-panel hidden absolute left-0 mt-1 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-20 py-1 text-left">
                                    <?php if ($isPending): ?>
                                        <button type="button"
                                                class="js-picklist-set-availability w-full text-left px-4 py-2 text-sm text-orange-800 hover:bg-orange-50 flex items-center gap-2"
                                                data-item-id="<?= (int) ($item['id'] ?? 0) ?>"
                                                data-status="partially_available">
                                            <i class="fas fa-adjust w-4 text-center text-orange-600" aria-hidden="true"></i>
                                            Mark partially available
                                        </button>
                                        <button type="button"
                                                class="js-picklist-set-availability w-full text-left px-4 py-2 text-sm text-red-800 hover:bg-red-50 flex items-center gap-2"
                                                data-item-id="<?= (int) ($item['id'] ?? 0) ?>"
                                                data-status="not_available">
                                            <i class="fas fa-ban w-4 text-center text-red-600" aria-hidden="true"></i>
                                            Mark not available
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($canRevert): ?>
                                        <?php if ($isPending): ?><div class="my-1 border-t border-gray-100"></div><?php endif; ?>
                                        <button type="button"
                                                class="js-picklist-unpick-item w-full text-left px-4 py-2 text-sm text-amber-800 hover:bg-amber-50 flex items-center gap-2"
                                                data-item-id="<?= (int) ($item['id'] ?? 0) ?>">
                                            <i class="fas fa-undo w-4 text-center text-amber-600" aria-hidden="true"></i>
                                            Revert to pending
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($isPending || $canRevert): ?><div class="my-1 border-t border-gray-100"></div><?php endif; ?>
                                    <a href="#"
                                       class="js-picklist-confirm-action block px-4 py-2 text-sm text-red-700 hover:bg-red-50 flex items-center gap-2"
                                       data-confirm="<?= htmlspecialchars($removeConfirm, ENT_QUOTES, 'UTF-8') ?>"
                                       data-item-id="<?= (int) ($item['id'] ?? 0) ?>">
                                        <i class="fas fa-times w-4 text-center text-red-600" aria-hidden="true"></i>
                                        Remove from picklist
                                    </a>
                                </div>
                            </div>
                            <span class="font-semibold text-amber-800 truncate min-w-0" title="<?= htmlspecialchars($locationText, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($locationText) ?></span>
                        </div>
                    </td>
                    <?php $orderNumber = trim((string) ($item['order_number'] ?? '')); ?>
                    <td class="px-3 py-3 align-middle whitespace-nowrap font-mono text-xs text-gray-800"
                        data-picklist-column="order"
                        data-picklist-column-value="<?= htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($orderNumber !== ''): ?>
                            <span class="inline-flex items-center gap-1.5">
                                <span><?= htmlspecialchars($orderNumber) ?></span>
                                <button type="button"
                                        class="js-picklist-copy-text shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-md border border-gray-200 bg-white text-gray-500 hover:text-amber-700 hover:border-amber-300 hover:bg-amber-50/50 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                                        data-copy-text="<?= htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') ?>"
                                        data-copy-label="Order number"
                                        title="Copy order number"
                                        aria-label="Copy order number <?= htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-copy text-[10px]" aria-hidden="true"></i>
                                </button>
                            </span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <?php $skuText = picklist_item_sku($item); ?>
                    <td class="px-3 py-3 align-middle whitespace-nowrap text-gray-700"
                        data-picklist-column="sku"
                        data-picklist-column-value="<?= htmlspecialchars($skuText, ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($skuText !== ''): ?>
                            <span class="inline-flex items-center gap-1.5 max-w-[12rem]">
                                <span class="truncate font-medium"><?= htmlspecialchars($skuText) ?></span>
                                <button type="button"
                                        class="js-picklist-copy-text shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-md border border-gray-200 bg-white text-gray-500 hover:text-amber-700 hover:border-amber-300 hover:bg-amber-50/50 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                                        data-copy-text="<?= htmlspecialchars($skuText, ENT_QUOTES, 'UTF-8') ?>"
                                        data-copy-label="SKU"
                                        title="Copy SKU"
                                        aria-label="Copy SKU <?= htmlspecialchars($skuText, ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-copy text-[10px]" aria-hidden="true"></i>
                                </button>
                            </span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-3 align-middle">
                        <span class="line-clamp-2 text-gray-900"><?= htmlspecialchars((string) ($item['title'] ?? '')) ?></span>
                        <?php $titleMetaLines = picklist_item_title_meta_lines($item, $isBook); ?>
                        <?php if ($titleMetaLines !== []): ?>
                            <div class="mt-1 space-y-0.5 text-[11px] leading-snug text-gray-500">
                                <?php foreach ($titleMetaLines as $metaLine): ?>
                                    <div><?= htmlspecialchars($metaLine) ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-3 align-middle">
                        <?php if ($imageUrl !== ''): ?>
                            <button type="button"
                                    class="js-picklist-expand-image block p-0 border-0 bg-transparent cursor-zoom-in rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                                    data-full-src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                                    data-image-alt="<?= htmlspecialchars((string) ($item['title'] ?? 'Product image'), ENT_QUOTES, 'UTF-8') ?>"
                                    title="Click to enlarge">
                                <img src="<?= htmlspecialchars($imageUrl) ?>" alt="" class="w-14 h-14 object-contain border border-gray-200 rounded-lg bg-white pointer-events-none">
                            </button>
                        <?php else: ?>
                            <span class="inline-flex h-14 w-14 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-xs text-gray-400">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-3 align-middle font-semibold tabular-nums"><?= (int) ($item['physical_qty'] ?? 0) ?></td>
                    <td class="px-3 py-3 align-middle font-semibold tabular-nums"><?= (int) ($item['quantity'] ?? 1) ?></td>
                    <?php if ($tabType === 'short'): ?>
                        <td class="px-3 py-3 align-middle font-bold text-amber-900 tabular-nums"><?= $shortfallQty ?></td>
                    <?php endif; ?>
                    <?php if ($showBookColumns): ?>
                        <td class="px-3 py-3 align-middle text-gray-700"><?= $isBook ? htmlspecialchars((string) ($item['publisher'] ?? '—')) : '—' ?></td>
                    <?php endif; ?>
                    <td class="px-3 py-3 align-middle whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border <?= $itemStatusClass ?>">
                            <?= htmlspecialchars($itemStatusLabel) ?>
                        </span>
                        <?php if ($isPicked && !empty($item['picked_at'])): ?>
                            <div class="text-[11px] text-gray-500 mt-1 tabular-nums"><?= date('d M, H:i', strtotime($item['picked_at'])) ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($items === []): ?>
                <?php $emptyColspan = 10 + ($tabType === 'short' ? 1 : 0) + ($showBookColumns ? 1 : 0); ?>
                <tr>
                    <td colspan="<?= $emptyColspan ?>" class="px-3 py-16 text-center">
                        <div class="mx-auto flex max-w-sm flex-col items-center">
                            <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 text-xl mb-4">
                                <i class="fas fa-box-open" aria-hidden="true"></i>
                            </span>
                            <p class="text-base font-medium text-gray-900">
                                <?= $tabType === 'full' ? 'No full quantity available items' : 'No partially available or unavailable items' ?>
                            </p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
