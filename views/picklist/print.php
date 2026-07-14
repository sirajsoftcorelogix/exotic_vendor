<?php
/** @var array $data */
require_once __DIR__ . '/partials/item_helpers.php';

$picklist = $data['picklist'] ?? [];
$items = $data['items'] ?? [];
$split = picklist_split_items_for_print($items);
$fullItems = $split['full'];
$shortItems = $split['short'];
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
        h2 { font-size: 14px; margin: 0 0 8px; color: #111; }
        .meta { margin-bottom: 16px; color: #444; }
        .section { margin-bottom: 28px; }
        .section-desc { margin: 0 0 10px; color: #555; font-size: 10px; }
        .section-a h2 { color: #166534; }
        .section-b h2 { color: #b45309; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .loc { font-weight: bold; }
        .item-img { width: 56px; height: 56px; object-fit: contain; }
        .status-partial { color: #b45309; font-weight: bold; }
        .status-none { color: #b91c1c; font-weight: bold; }
        .section-count { font-weight: normal; color: #666; font-size: 12px; }
        @media print {
            body { margin: 12px; }
            .no-print { display: none; }
            .section { page-break-inside: avoid; }
            .section-b { page-break-before: auto; }
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
        (Full: <?= count($fullItems) ?> · Short/Unavailable: <?= count($shortItems) ?>)
    </div>

    <div class="section section-a">
        <h2>A) Full quantity available <span class="section-count">(<?= count($fullItems) ?> item<?= count($fullItems) === 1 ? '' : 's' ?>)</span></h2>
        <p class="section-desc">Physical stock meets or exceeds order quantity — pick the full order qty.</p>
        <?php
        $printItems = $fullItems;
        $showShortfallColumns = false;
        $startIndex = 0;
        include __DIR__ . '/partials/print_items_table.php';
        ?>
    </div>

    <div class="section section-b">
        <h2>B) Partially available &amp; not available <span class="section-count">(<?= count($shortItems) ?> item<?= count($shortItems) === 1 ? '' : 's' ?>)</span></h2>
        <p class="section-desc">Physical stock is less than order quantity — pick what is available; shortfall shown for follow-up.</p>
        <?php
        $printItems = $shortItems;
        $showShortfallColumns = true;
        $startIndex = count($fullItems);
        include __DIR__ . '/partials/print_items_table.php';
        ?>
    </div>

    <script>window.onload = function() { window.print(); };</script>
</body>
</html>
<?php exit; ?>
