<?php

declare(strict_types=1);

/**
 * Format the Box No / Variant (color & size) table cell for invoice PDF rendering.
 *
 * @param array<string, mixed> $item
 * @param \mysqli|null $conn
 */
function invoice_format_box_variant_cell(array $item, ?\mysqli $conn = null): string
{
    $boxNo = trim((string)($item['box_no'] ?? ''));
    $color = trim((string)($item['color'] ?? ''));
    $size = trim((string)($item['size'] ?? ''));

    $invalidVals = ['', '-', 'n/a', 'none', 'null'];
    if (in_array(strtolower($color), $invalidVals, true)) {
        $color = '';
    }
    if (in_array(strtolower($size), $invalidVals, true)) {
        $size = '';
    }

    if (($color === '' || $size === '') && !empty($conn)) {
        $orderNumber = trim((string)($item['order_number'] ?? ''));
        $itemCode = trim((string)($item['item_code'] ?? ''));
        $productId = (int)($item['product_id'] ?? 0);

        if ($orderNumber !== '' && $itemCode !== '') {
            $stmt = $conn->prepare("SELECT size, color FROM vp_orders WHERE order_number = ? AND item_code = ? ORDER BY id ASC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('ss', $orderNumber, $itemCode);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $row = $res->fetch_assoc()) {
                    if ($size === '' && !empty($row['size'])) {
                        $size = trim((string)$row['size']);
                    }
                    if ($color === '' && !empty($row['color'])) {
                        $color = trim((string)$row['color']);
                    }
                }
                $stmt->close();
            }
        }

        if (($color === '' || $size === '') && $productId > 0) {
            $stmt = $conn->prepare("SELECT size, color FROM vp_products WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('i', $productId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $row = $res->fetch_assoc()) {
                    if ($size === '' && !empty($row['size'])) {
                        $size = trim((string)$row['size']);
                    }
                    if ($color === '' && !empty($row['color'])) {
                        $color = trim((string)$row['color']);
                    }
                }
                $stmt->close();
            }
        }

        if (in_array(strtolower($color), $invalidVals, true)) {
            $color = '';
        }
        if (in_array(strtolower($size), $invalidVals, true)) {
            $size = '';
        }
    }

    $cellParts = [];
    if ($boxNo !== '') {
        $cellParts[] = (stripos($boxNo, 'box') === false && is_numeric($boxNo))
            ? 'Box ' . htmlspecialchars($boxNo)
            : htmlspecialchars($boxNo);
    }

    $variantLabels = [];
    if ($color !== '') {
        $variantLabels[] = 'Color: ' . htmlspecialchars($color);
    }
    if ($size !== '') {
        $variantLabels[] = 'Size: ' . htmlspecialchars($size);
    }

    if (!empty($variantLabels)) {
        $cellParts[] = '<span style="font-size:11px;color:#444;">' . implode(', ', $variantLabels) . '</span>';
    }

    return !empty($cellParts) ? implode('<br>', $cellParts) : '&nbsp;';
}
