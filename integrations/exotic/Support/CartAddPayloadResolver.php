<?php

/**
 * Resolve Exotic POST /cart/add parameters from vp_orders lines or cart JSON rows.
 *
 * Mirrors POS register logic in assets/js/pos.js (resolveCartPayload) and
 * assets/js/pos_cart_hooks.js (buildCartAddPayloadFromProduct).
 *
 * @see docs/exotic-order-create-api.md
 */

/**
 * @param array<string, mixed> $row
 * @param list<string>         $keys
 */
function exotic_cart_pick_line_field(array $row, array $keys): string
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $row)) {
            continue;
        }
        $val = trim((string)$row[$key]);
        if ($val !== '') {
            return $val;
        }
    }

    return '';
}

function exotic_cart_normalize_facet(?string $val): string
{
    $s = trim((string)$val);
    if ($s === '' || $s === '0' || strcasecmp($s, 'n/a') === 0) {
        return '';
    }

    return $s;
}

function exotic_cart_build_variation_from_size_color(string $size, string $color): string
{
    $size = exotic_cart_normalize_facet($size);
    $color = exotic_cart_normalize_facet($color);
    if ($size === '' && $color === '') {
        return '';
    }
    if ($size === '' && $color !== '') {
        return ':' . $color;
    }
    if ($size !== '' && $color === '') {
        return $size . ':';
    }

    return $size . ':' . $color;
}

/**
 * @return array<string, mixed>|null
 */
function exotic_cart_lookup_product_row(mysqli $conn, string $itemCode, string $sku, string $size = '', string $color = ''): ?array
{
    $itemCode = trim($itemCode);
    $sku = trim($sku);
    $size = exotic_cart_normalize_facet($size);
    $color = exotic_cart_normalize_facet($color);
    if ($itemCode === '' && $sku === '') {
        return null;
    }

    if ($sku !== '') {
        $stmt = $conn->prepare(
            'SELECT item_code, sku, size, color, item_level
             FROM vp_products
             WHERE is_active = 1 AND sku = ?
             ORDER BY id DESC
             LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('s', $sku);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (is_array($row)) {
                return $row;
            }
        }
    }

    if ($itemCode !== '' && ($size !== '' || $color !== '')) {
        $stmt = $conn->prepare(
            'SELECT item_code, sku, size, color, item_level
             FROM vp_products
             WHERE is_active = 1 AND item_code = ?
               AND IFNULL(size, \'\') = ? AND IFNULL(color, \'\') = ?
             ORDER BY id DESC
             LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('sss', $itemCode, $size, $color);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (is_array($row)) {
                return $row;
            }
        }
    }

    if ($itemCode !== '') {
        $stmt = $conn->prepare(
            'SELECT item_code, sku, size, color, item_level
             FROM vp_products
             WHERE is_active = 1 AND item_code = ?
             ORDER BY CASE
                 WHEN LOWER(TRIM(IFNULL(item_level, \'\'))) = \'variation\' THEN 0
                 WHEN LOWER(TRIM(IFNULL(item_level, \'\'))) = \'parent\' THEN 2
                 ELSE 1
             END,
             id DESC
             LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('s', $itemCode);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (is_array($row)) {
                return $row;
            }
        }
    }

    if ($sku !== '' && $itemCode === '') {
        $stmt = $conn->prepare(
            'SELECT item_code, sku, size, color, item_level
             FROM vp_products
             WHERE is_active = 1 AND item_code = ?
             ORDER BY id DESC
             LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('s', $sku);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (is_array($row)) {
                return $row;
            }
        }
    }

    return null;
}

function exotic_cart_item_code_has_variation_rows(mysqli $conn, string $itemCode): bool
{
    $itemCode = trim($itemCode);
    if ($itemCode === '') {
        return false;
    }

    $stmt = $conn->prepare(
        'SELECT 1 FROM vp_products
         WHERE is_active = 1 AND item_code = ?
           AND LOWER(TRIM(IFNULL(item_level, \'\'))) = \'variation\'
         LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $itemCode);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return is_array($row);
}

/**
 * @param array<string, mixed> $row vp_orders line or cart retrieve item
 *
 * @return array{
 *   code:string,
 *   variation:string,
 *   options:string,
 *   item_code:string,
 *   sku:string,
 *   size:string,
 *   color:string,
 *   item_level:string,
 *   requires_variation:bool
 * }
 */
function exotic_cart_resolve_add_params(?mysqli $conn, array $row): array
{
    $itemCode = exotic_cart_normalize_facet(exotic_cart_pick_line_field($row, [
        'item_code', 'itemcode', 'product_code',
    ]));
    $sku = exotic_cart_normalize_facet(exotic_cart_pick_line_field($row, ['sku']));
    $size = exotic_cart_normalize_facet(exotic_cart_pick_line_field($row, [
        'size', 'Size', 'variationsize', 'variation_size',
    ]));
    $color = exotic_cart_normalize_facet(exotic_cart_pick_line_field($row, [
        'color', 'Color', 'colour', 'variationcolor', 'variation_color',
    ]));

    $variationRaw = exotic_cart_pick_line_field($row, ['variation', 'Variation', 'variant']);
    if ($size === '' && $color === '' && $variationRaw !== '' && str_contains($variationRaw, ':')) {
        $parts = explode(':', $variationRaw, 2);
        $size = exotic_cart_normalize_facet($parts[0] ?? '');
        $color = exotic_cart_normalize_facet($parts[1] ?? '');
    }

    $product = ($conn instanceof mysqli)
        ? exotic_cart_lookup_product_row($conn, $itemCode, $sku, $size, $color)
        : null;
    if (is_array($product)) {
        if ($itemCode === '') {
            $itemCode = exotic_cart_normalize_facet((string)($product['item_code'] ?? ''));
        }
        if ($sku === '') {
            $sku = exotic_cart_normalize_facet((string)($product['sku'] ?? ''));
        }
        if ($size === '') {
            $size = exotic_cart_normalize_facet((string)($product['size'] ?? ''));
        }
        if ($color === '') {
            $color = exotic_cart_normalize_facet((string)($product['color'] ?? ''));
        }
    }

    $level = strtolower(trim((string)($row['item_level'] ?? ($product['item_level'] ?? ''))));
    $variation = exotic_cart_build_variation_from_size_color($size, $color);

    if ($level === 'parent') {
        $cartCode = $itemCode;
        $variation = '';
    } elseif ($level === 'variation' || ($itemCode !== '' && $variation !== '')) {
        $cartCode = $itemCode;
    } else {
        $cartCode = $itemCode !== '' ? $itemCode : $sku;
        if ($cartCode === '') {
            $cartCode = exotic_cart_normalize_facet(exotic_cart_pick_line_field($row, ['code']));
        }
        $variation = '';
    }

    if ($level === 'variation' && $variation === '') {
        $requiresVariation = true;
    } elseif ($level === 'parent' && $conn instanceof mysqli && $itemCode !== ''
        && exotic_cart_item_code_has_variation_rows($conn, $itemCode)) {
        $requiresVariation = true;
    } elseif ($level === 'variation') {
        $requiresVariation = false;
    } else {
        $requiresVariation = false;
    }

    $options = $row['options'] ?? '';
    if (is_array($options)) {
        $options = json_encode($options, JSON_UNESCAPED_UNICODE);
    }
    $options = trim((string)$options);
    if ($options === '0') {
        $options = '';
    }

    $displayItemCode = $itemCode !== '' ? $itemCode : ($sku !== '' ? $sku : $cartCode);

    return [
        'code' => $cartCode,
        'variation' => $variation,
        'options' => $options,
        'item_code' => $displayItemCode,
        'sku' => $sku,
        'size' => $size,
        'color' => $color,
        'item_level' => $level,
        'requires_variation' => $requiresVariation,
    ];
}

/**
 * Normalize a cart retrieve row before persisting to vp_orders.
 *
 * @param array<string, mixed> $cartRow
 *
 * @return array<string, mixed>
 */
function exotic_cart_normalize_line_for_local_persist(?mysqli $conn, array $cartRow): array
{
    $resolved = exotic_cart_resolve_add_params($conn, $cartRow);

    $cartRow['item_code'] = $resolved['item_code'] !== ''
        ? $resolved['item_code']
        : exotic_cart_pick_line_field($cartRow, ['code', 'itemcode', 'item_code']);
    if ($resolved['sku'] !== '') {
        $cartRow['sku'] = $resolved['sku'];
    }
    if ($resolved['size'] !== '') {
        $cartRow['size'] = $resolved['size'];
    }
    if ($resolved['color'] !== '') {
        $cartRow['color'] = $resolved['color'];
    }
    if ($resolved['item_level'] !== '') {
        $cartRow['item_level'] = $resolved['item_level'];
    }
    $cartRow['exotic_cart_code'] = $resolved['code'];
    $cartRow['exotic_cart_variation'] = $resolved['variation'];

    return $cartRow;
}

/**
 * Build POST body fields for /cart/add from a vp_orders line.
 *
 * @param array<string, mixed> $orderLine
 *
 * @return array{success:bool,message?:string,post?:array<string,string>,label?:string}
 */
function exotic_cart_build_add_post_from_order_line(?mysqli $conn, array $orderLine, int $qty): array
{
    $resolved = exotic_cart_resolve_add_params($conn, $orderLine);
    $code = trim((string)($resolved['code'] ?? ''));
    if ($code === '') {
        return [
            'success' => false,
            'message' => 'Order line is missing item code / SKU — cannot rebuild Exotic cart.',
        ];
    }

    if (!empty($resolved['requires_variation']) && trim((string)($resolved['variation'] ?? '')) === '') {
        $label = trim((string)($resolved['item_code'] ?? $code));

        return [
            'success' => false,
            'message' => 'Variation (size/color) is required for ' . $label
                . ' but missing on the saved order line. Re-add the product from POS or edit size/color in catalog.',
            'label' => $label,
        ];
    }

    $qty = max(1, $qty);
    $post = [
        'buynow' => '0',
        'code' => $code,
        'qty' => (string)$qty,
    ];
    $variation = trim((string)($resolved['variation'] ?? ''));
    if ($variation !== '') {
        $post['variation'] = $variation;
    }
    $options = trim((string)($resolved['options'] ?? ''));
    if ($options !== '') {
        $post['options'] = $options;
    }

    return [
        'success' => true,
        'post' => $post,
        'label' => trim((string)($resolved['item_code'] ?? $code)),
    ];
}
