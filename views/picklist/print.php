<?php
/** @var array $data */
$picklist = $data['picklist'] ?? [];
$items = $data['items'] ?? [];
$plNumber = (string) ($picklist['picklist_number'] ?? '');
$picker = (string) ($picklist['picker_name'] ?? 'Unassigned');
$created = !empty($picklist['created_at']) ? date('d M Y H:i', strtotime($picklist['created_at'])) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print — <?= htmlspecialchars($plNumber) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { margin-bottom: 16px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .loc { font-weight: bold; }
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
                <th>Order #</th>
                <th>SKU / Code</th>
                <th>Size / Color</th>
                <th>Title</th>
                <th>Qty</th>
                <th>Picked</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $idx => $item): ?>
                <tr>
                    <td><?= $idx + 1 ?></td>
                    <td class="loc"><?= htmlspecialchars((string) ($item['warehouse_location'] ?: '—')) ?></td>
                    <td><?= htmlspecialchars((string) ($item['order_number'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string) ($item['sku'] ?: $item['item_code'] ?: '')) ?></td>
                    <td><?= htmlspecialchars(trim(($item['size'] ?? '') . ' / ' . ($item['color'] ?? ''), ' /')) ?></td>
                    <td><?= htmlspecialchars((string) ($item['title'] ?? '')) ?></td>
                    <td><?= (int) ($item['quantity'] ?? 1) ?></td>
                    <td style="width: 48px;">&nbsp;</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <script>window.onload = function() { window.print(); };</script>
</body>
</html>
<?php exit; ?>
