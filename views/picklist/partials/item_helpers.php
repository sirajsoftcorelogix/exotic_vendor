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
