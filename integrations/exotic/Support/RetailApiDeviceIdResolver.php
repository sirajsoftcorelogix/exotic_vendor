<?php

require_once __DIR__ . '/../../../models/user/user.php';

/**
 * Resolve x-api-deviceid for Exotic retail API (POS terminal / store label).
 */
class RetailApiDeviceIdResolver
{
    public static function resolve(?mysqli $conn, ?int $warehouseId): string
    {
        $fallbackId = (int) ($warehouseId ?? 0);
        if ($fallbackId <= 0) {
            $fallbackId = 1;
        }
        $fallback = 'POS-Store_' . $fallbackId;

        if ($warehouseId === null || $warehouseId <= 0) {
            return $fallback;
        }

        if (!$conn instanceof mysqli) {
            return $fallback;
        }

        try {
            $usersModel = new User($conn);
            $warehouse = $usersModel->getWarehouseById($warehouseId);
            $name = trim((string) ($warehouse['address_title'] ?? ''));
            if ($name === '') {
                return $fallback;
            }
            $name = preg_replace('/\s+/', '_', $name);
            $name = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $name);

            return $name !== '' ? $name : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
}
