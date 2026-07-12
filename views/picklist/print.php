<?php
/** @var array $data */
require_once __DIR__ . '/partials/item_helpers.php';

$picklist = $data['picklist'] ?? [];
$items = $data['items'] ?? [];
$plNumber = (string) ($picklist['picklist_number'] ?? '');
$picker = (string) ($picklist['picker_name'] ?? 'Unassigned');
$created = !empty($picklist['created_at']) ? date('d M Y H:i', strtotime($picklist['created_at'])) : '';
$showBookColumns = picklist_any_book_items($items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print — <?= htmlspecialchars($plNumber) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { margin-bottom: 16px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .loc { font-weight: bold; }
        .item-img { width: 56px; height: 56px; object-fit: contain; }
        @media print {
            body { margin: 12px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 16px;">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
    <h1>Picklist: <?= htmlspecialchars($plNumber) ?></h1>
    <div class="meta">
        Picker: <?= htmlspecialchars($picker) ?> · Created: <?= htmlspecialchars($created) ?> · Items: <?= count($items) ?>
    </div>
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
                <?php if ($showBookColumns): ?>
                    <th>Publisher</th>
                    <th>Cover Type</th>
                <?php endif; ?>
                <th>Picked</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $idx => $item): ?>
                <?php
                $isBook = picklist_item_is_book($item);
                $imageUrl = picklist_item_image_url($item);
                ?>
                <tr>
                    <td><?= $idx + 1 ?></td>
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
                    <td><?= (int) ($item['physical_qty'] ?? 0) ?></td>
                    <?php if ($showBookColumns): ?>
                        <td><?= $isBook ? htmlspecialchars((string) ($item['publisher'] ?? '—')) : '—' ?></td>
                        <td><?= $isBook ? htmlspecialchars((string) ($item['cover_type'] ?? '—')) : '—' ?></td>
                    <?php endif; ?>
                    <td style="width: 48px;">&nbsp;</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <script>window.onload = function() { window.print(); };</script>
</body>
</html>
<?php exit; ?>
