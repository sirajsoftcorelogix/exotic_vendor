<?php

/**
 * Normalizes adapter rate quotes into the bulk/single dispatch UI contract.
 *
 * Every adapter should return couriers[] in this shape (or pass through formatQuotes()).
 */
class CourierUiFormat
{
    /**
     * @param list<array<string, mixed>> $quotes
     * @return list<array<string, mixed>>
     */
    public static function formatQuotes(array $quotes): array
    {
        $rows = [];
        foreach ($quotes as $quote) {
            if (!is_array($quote)) {
                continue;
            }
            $rows[] = [
                'id' => (string) ($quote['id'] ?? ''),
                'name' => (string) ($quote['name'] ?? 'Courier'),
                'price' => array_key_exists('price', $quote) && $quote['price'] === null
                    ? null
                    : (float) ($quote['price'] ?? 0),
                'currency' => strtoupper((string) ($quote['currency'] ?? 'INR')),
                'etd' => (string) ($quote['etd'] ?? 'N/A'),
                'rating' => (float) ($quote['rating'] ?? 0),
                'partner_code' => strtolower((string) ($quote['partner_code'] ?? '')),
                'partner_account_id' => (int) ($quote['partner_account_id'] ?? 0),
                'product_group' => (string) ($quote['product_group'] ?? ''),
                'product_type' => (string) ($quote['product_type'] ?? ''),
                'service_code' => (string) ($quote['service_code'] ?? $quote['product_type'] ?? ''),
                'metadata' => is_array($quote['metadata'] ?? null) ? $quote['metadata'] : [],
            ];
        }
        return $rows;
    }

    /**
     * Standard JSON response for ?page=dispatch&action=getCourierServiceability
     *
     * @param array<string, mixed> $gatewayResult
     * @return array<string, mixed>
     */
    public static function serviceabilityResponse(array $gatewayResult): array
    {
        if (!empty($gatewayResult['use_shiprocket'])) {
            return [
                'success' => false,
                'use_shiprocket' => true,
                'message' => (string) ($gatewayResult['message'] ?? 'Use legacy Shiprocket path.'),
            ];
        }

        if (empty($gatewayResult['success'])) {
            return [
                'success' => false,
                'message' => (string) ($gatewayResult['message'] ?? 'Could not fetch courier rates.'),
                'provider' => $gatewayResult['provider'] ?? null,
                'debug' => $gatewayResult['debug'] ?? null,
            ];
        }

        $couriers = self::formatQuotes($gatewayResult['couriers'] ?? []);

        return [
            'success' => true,
            'provider' => $gatewayResult['provider'] ?? null,
            'international' => !empty($gatewayResult['international']),
            'couriers' => $couriers,
            'rejected_couriers' => $gatewayResult['rejected_couriers'] ?? [],
            'selected' => $couriers[0] ?? null,
            'debug' => $gatewayResult['debug'] ?? null,
            'message' => (string) ($gatewayResult['message'] ?? 'Couriers fetched successfully'),
        ];
    }
}
