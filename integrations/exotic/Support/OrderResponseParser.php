<?php

/**
 * Parse Exotic retail API order/create responses.
 */
class OrderResponseParser
{
    /**
     * @param array<string, mixed> $data
     */
    public static function extractOrderNumber(array $data): string
    {
        foreach (['orderid', 'order_id', 'OrderId', 'ordernumber', 'order_number', 'order_no', 'orderNo'] as $k) {
            if (!empty($data[$k])) {
                return trim((string) $data[$k]);
            }
        }
        if (!empty($data['order']) && is_array($data['order'])) {
            return self::extractOrderNumber($data['order']);
        }
        if (!empty($data['data']) && is_array($data['data'])) {
            return self::extractOrderNumber($data['data']);
        }

        return '';
    }
}
