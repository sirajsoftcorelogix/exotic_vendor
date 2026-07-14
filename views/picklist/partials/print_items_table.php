<?php
/**
 * @var array<int, array<string, mixed>> $printItems
 * @var bool $showBookColumns
 * @var bool $showShortfallColumns
 * @var int $startIndex
 */
$printItems = $printItems ?? [];
$showBookColumns = !empty($showBookColumns);
$showShortfallColumns = !empty($showShortfallColumns);
$startIndex = (int) ($startIndex ?? 0);
?>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Location</th>
            <th>Order Number</th>
            <th>SKU</th>
            <th>Item Title</th>
            <th>Item Image</th>
            <th>Physical Qty</th>
            <th>Order Qty</th>
            <?php if ($showShortfallColumns): ?>
                <th>Shortfall</th>
            <?php endif; ?>
            <?php if ($showBookColumns): ?>
                <th>Publisher</th>
                <th>Cover Type</th>
            <?php endif; ?>
            <th>Picked</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($printItems === []): ?>
            <?php $emptyColspan = 9 + ($showShortfallColumns ? 1 : 0) + ($showBookColumns ? 2 : 0); ?>
            <tr>
                <td colspan="<?= $emptyColspan ?>" style="text-align:center;color:#666;">No items in this section.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($printItems as $idx => $item): ?>
            <?php
            $isBook = picklist_item_is_book($item);
            $imageUrl = picklist_item_image_url($item);
            ?>
            <tr>
                <td><?= $startIndex + $idx + 1 ?></td>
                <td class="loc"><?= htmlspecialchars((string) ($item['warehouse_location'] ?: '—')) ?></td>
                <td><?= htmlspecialchars((string) ($item['order_number'] ?? '')) ?></td>
                <td><?= htmlspecialchars(picklist_item_sku($item) ?: '—') ?></td>
                <td><?= htmlspecialchars((string) ($item['title'] ?? '')) ?></td>
                <td>
                    <?php if ($imageUrl !== ''): ?>
                        <img src="<?= htmlspecialchars($imageUrl) ?>" alt="" class="item-img">
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td><?= picklist_item_physical_qty($item) ?></td>
                <td><?= picklist_item_order_qty($item) ?></td>
                <?php if ($showShortfallColumns): ?>
                    <td><?= picklist_item_shortfall_qty($item) ?></td>
                <?php endif; ?>
                <?php if ($showBookColumns): ?>
                    <td><?= $isBook ? htmlspecialchars((string) ($item['publisher'] ?? '—')) : '—' ?></td>
                    <td><?= $isBook ? htmlspecialchars((string) ($item['cover_type'] ?? '—')) : '—' ?></td>
                <?php endif; ?>
                <td style="width: 48px;">&nbsp;</td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
