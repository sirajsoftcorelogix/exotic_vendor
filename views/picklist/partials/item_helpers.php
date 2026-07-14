<?php

if (!function_exists('picklist_item_is_book')) {
    function picklist_item_is_book(array $item): bool
    {
        if (!empty($item['is_book'])) {
            return true;
        }
        if (!empty($item['author']) || !empty($item['publisher'])) {
            return true;
        }
        foreach (['itemtype', 'groupname'] as $field) {
            $val = strtolower(trim((string) ($item[$field] ?? '')));
            if ($val !== '' && str_contains($val, 'book')) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('picklist_any_book_items')) {
    function picklist_any_book_items(array $items): bool
    {
        foreach ($items as $item) {
            if (picklist_item_is_book($item)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('picklist_item_sku')) {
    function picklist_item_sku(array $item): string
    {
        $sku = trim((string) ($item['sku'] ?? ''));
        if ($sku !== '') {
            return $sku;
        }
        return trim((string) ($item['item_code'] ?? ''));
    }
}

if (!function_exists('picklist_item_image_url')) {
    function picklist_item_image_url(array $item): string
    {
        return trim((string) ($item['image'] ?? ''));
    }
}

if (!function_exists('picklist_item_order_qty')) {
    function picklist_item_order_qty(array $item): int
    {
        $qty = (int) ($item['quantity'] ?? 1);

        return $qty > 0 ? $qty : 1;
    }
}

if (!function_exists('picklist_item_physical_qty')) {
    function picklist_item_physical_qty(array $item): int
    {
        return max(0, (int) ($item['physical_qty'] ?? 0));
    }
}

/**
 * @return 'full'|'partial'|'none'
 */
if (!function_exists('picklist_item_availability')) {
    function picklist_item_availability(array $item): string
    {
        $orderQty = picklist_item_order_qty($item);
        $physicalQty = picklist_item_physical_qty($item);
        if ($physicalQty >= $orderQty) {
            return 'full';
        }
        if ($physicalQty > 0) {
            return 'partial';
        }

        return 'none';
    }
}

/**
 * @return array{full: array<int, array<string, mixed>>, short: array<int, array<string, mixed>>}
 */
if (!function_exists('picklist_split_items_for_print')) {
    function picklist_split_items_for_print(array $items): array
    {
        $full = [];
        $short = [];
        foreach ($items as $item) {
            if (picklist_item_availability($item) === 'full') {
                $full[] = $item;
            } else {
                $short[] = $item;
            }
        }

        return ['full' => $full, 'short' => $short];
    }
}

if (!function_exists('picklist_item_shortfall_qty')) {
    function picklist_item_shortfall_qty(array $item): int
    {
        return max(0, picklist_item_order_qty($item) - picklist_item_physical_qty($item));
    }
}
