<?php

/**
 * Parse vendor-api/order/fetch responses (list vs id-keyed orders map).
 */
class VendorOrderFetchParser
{
    /**
     * @param array<int|string, array<string, mixed>> $orders
     * @return list<array<string, mixed>>
     */
    public static function normalizeOrdersList(array $orders): array
    {
        if ($orders === []) {
            return [];
        }

        if (function_exists('array_is_list') && array_is_list($orders)) {
            return $orders;
        }

        $keys = array_keys($orders);
        if ($keys === range(0, count($orders) - 1)) {
            return array_values($orders);
        }

        return array_values($orders);
    }

    /**
     * @param array<string, mixed>|null $decoded Full vendor-api /order/fetch response
     * @return array<string, mixed>|null
     */
    public static function findOrder(?array $decoded, string $orderNumber): ?array
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '' || !is_array($decoded)) {
            return null;
        }

        $orders = $decoded['orders'] ?? null;
        if (!is_array($orders) || $orders === []) {
            return null;
        }

        if (isset($orders[$orderNumber]) && is_array($orders[$orderNumber])) {
            return $orders[$orderNumber];
        }

        foreach (self::normalizeOrdersList($orders) as $order) {
            if ((string) ($order['orderid'] ?? '') === $orderNumber) {
                return $order;
            }
        }

        $normalized = self::normalizeOrdersList($orders);

        return $normalized[0] ?? null;
    }
}
