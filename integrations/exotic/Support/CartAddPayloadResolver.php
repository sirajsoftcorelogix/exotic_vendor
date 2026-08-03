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

const EXOTIC_CART_CUSTOM_ADDON_MARKER = '_blank_';

/**
 * @return list<string>
 */
function exotic_cart_parse_options_segments(array $row): array
{
    $raw = $row['options'] ?? $row['option'] ?? $row['selected_options'] ?? '';
    if ($raw === null || $raw === '' || $raw === 0 || $raw === '0') {
        return [];
    }

    if (is_array($raw)) {
        $parts = $raw;
    } else {
        $rawStr = trim((string) $raw);
        if ($rawStr === '') {
            return [];
        }
        if ($rawStr !== '' && $rawStr[0] === '[') {
            $decoded = json_decode($rawStr, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $parts = $decoded;
            } else {
                $parts = explode('|', $rawStr);
            }
        } else {
            $parts = explode('|', $rawStr);
        }
    }

    $segments = [];
    foreach ($parts as $part) {
        $segment = trim((string) $part);
        if ($segment !== '') {
            $segments[] = $segment;
        }
    }

    return $segments;
}

function exotic_cart_normalize_addon_match_name(string $name): string
{
    $normalized = strtolower(trim($name));
    if (str_starts_with($normalized, 'add on ')) {
        $normalized = substr($normalized, 7);
    }
    $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

    return trim(str_replace('_', ' ', $normalized));
}

function exotic_cart_option_segment_price_marker_index(string $segment): ?array
{
    $markers = [':_blank_:', ':blank:'];
    $foundIdx = -1;
    $foundLen = 0;
    foreach ($markers as $marker) {
        $idx = strpos($segment, $marker);
        if ($idx !== false && $idx > 0 && ($foundIdx === -1 || $idx < $foundIdx)) {
            $foundIdx = $idx;
            $foundLen = strlen($marker);
        }
    }

    return $foundIdx > 0 ? ['idx' => $foundIdx, 'len' => $foundLen] : null;
}

function exotic_cart_option_segment_display_name(string $segment): string
{
    $segment = trim($segment);
    if ($segment === '') {
        return '';
    }

    $markerInfo = exotic_cart_option_segment_price_marker_index($segment);
    if ($markerInfo !== null) {
        $customName = substr($segment, 0, $markerInfo['idx']);
        if (preg_match('/^[A-Za-z0-9_]+$/', $customName)) {
            $label = preg_replace('/^OPTIONALS_/i', '', $customName) ?? $customName;
            $label = trim(str_replace('_', ' ', $label));

            return $label !== '' ? $label : $customName;
        }
    }

    $colonIdx = strpos($segment, ':');
    $prefix = $colonIdx !== false && $colonIdx > 0 ? substr($segment, 0, $colonIdx) : $segment;
    $label = preg_replace('/^OPTIONALS_/i', '', $prefix) ?? $prefix;
    $label = trim(str_replace('_', ' ', $label));

    return $label !== '' ? $label : $prefix;
}

function exotic_cart_option_segment_price(string $segment): ?float
{
    $segment = trim($segment);
    if ($segment === '') {
        return null;
    }

    $markerInfo = exotic_cart_option_segment_price_marker_index($segment);
    if ($markerInfo !== null) {
        $priceRaw = substr($segment, $markerInfo['idx'] + $markerInfo['len']);
        $priceRaw = str_replace(',', '', trim($priceRaw));

        return is_numeric($priceRaw) ? (float) $priceRaw : null;
    }

    $parts = explode(':', $segment);
    if (count($parts) >= 2) {
        $priceRaw = str_replace(',', '', trim((string) end($parts)));

        return is_numeric($priceRaw) ? (float) $priceRaw : null;
    }

    return null;
}

function exotic_cart_option_segment_matches_addon(string $segment, string $name, ?float $price): bool
{
    $targetName = exotic_cart_normalize_addon_match_name($name);
    $nameUnderscore = str_replace(' ', '_', $targetName);
    $segmentLower = strtolower($segment);
    $displayName = exotic_cart_normalize_addon_match_name(exotic_cart_option_segment_display_name($segment));

    if (
        $displayName === $targetName
        || ($nameUnderscore !== '' && str_contains($segmentLower, $nameUnderscore))
        || ($targetName !== '' && str_contains($targetName, $displayName))
        || ($displayName !== '' && str_contains($displayName, $targetName))
    ) {
        return true;
    }

    if ($price !== null && $price > 0) {
        $segmentPrice = exotic_cart_option_segment_price($segment);
        if ($segmentPrice !== null && abs($segmentPrice - $price) < 0.01) {
            return true;
        }
    }

    return false;
}

function exotic_cart_build_custom_addon_option_segment(string $name, float $price): string
{
    $normalized = trim(preg_replace('/\s+/', '_', $name) ?? $name);
    $normalized = preg_replace('/^OPTIONALS_/i', '', $normalized) ?? $normalized;
    if ($normalized === '' || !preg_match('/^[A-Za-z_]+$/', $normalized)) {
        return '';
    }
    if ($price < 0) {
        return '';
    }

    $priceStr = abs($price - round($price)) < 0.000001
        ? (string) (int) round($price)
        : number_format($price, 2, '.', '');

    return $normalized . ':' . EXOTIC_CART_CUSTOM_ADDON_MARKER . ':' . $priceStr;
}

/**
 * Merge vp_orders.addons JSON into pipe-separated /cart/add options when missing.
 *
 * @param array<string, mixed> $row
 */
function exotic_cart_merge_line_addons_into_options(array $row): string
{
    $segments = exotic_cart_parse_options_segments($row);
    require_once dirname(__DIR__, 3) . '/models/order/order.php';

    $addonRows = Order::parseVendorOrderLineAddonsList($row['addons'] ?? null);
    foreach ($addonRows as $addon) {
        $name = trim((string) ($addon['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $price = (float) ($addon['price'] ?? 0);

        $alreadyPresent = false;
        foreach ($segments as $segment) {
            if (exotic_cart_option_segment_matches_addon($segment, $name, $price > 0 ? $price : null)) {
                $alreadyPresent = true;
                break;
            }
        }
        if ($alreadyPresent) {
            continue;
        }

        $segment = exotic_cart_build_custom_addon_option_segment($name, $price);
        if ($segment !== '') {
            $segments[] = $segment;
        }
    }

    return implode('|', $segments);
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

    $options = exotic_cart_merge_line_addons_into_options($row);

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
