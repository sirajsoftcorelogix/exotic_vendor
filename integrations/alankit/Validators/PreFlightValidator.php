<?php

namespace Integrations\Alankit\Validators;

/**
 * Pre-Flight Validator for Alankit E-Invoice and E-Way Bill requests.
 * Catches schema violations, invalid GSTINs, pincodes, and amounts before sending network calls.
 */
class PreFlightValidator
{
    public const GSTIN_REGEX = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/';
    public const PINCODE_REGEX = '/^[1-9][0-9]{5}$/';
    public const VEHICLE_REGEX = '/^[A-Z]{2}[0-9]{1,2}[A-Z]{0,3}[0-9]{4}$/i';

    /**
     * Validate IRN Generation Request Data.
     *
     * @param array<string, mixed> $invoice
     * @param list<array<string, mixed>> $items
     * @param array<string, mixed> $customer
     * @param array<string, mixed> $firm
     * @return array{valid:bool, errors:list<string>}
     */
    public static function validateIrnRequest(
        array $invoice,
        array $items,
        array $customer,
        array $firm
    ): array {
        $errors = [];

        // 1. Seller GSTIN
        $sellerGstin = strtoupper(trim((string) ($firm['gst'] ?? $firm['gstin'] ?? '')));
        if ($sellerGstin === '') {
            $errors[] = 'Seller GSTIN is required.';
        } elseif (!preg_match(self::GSTIN_REGEX, $sellerGstin)) {
            $errors[] = "Seller GSTIN '{$sellerGstin}' is invalid.";
        }

        // 2. Buyer GSTIN (optional if URP or Export)
        $country = strtoupper(trim((string) ($customer['country'] ?? $invoice['country'] ?? 'IN')));
        $isExport = ($country !== 'IN' && $country !== 'INDIA');
        $buyerGstin = strtoupper(trim((string) ($customer['gstin'] ?? $invoice['gstin'] ?? 'URP')));

        if (!$isExport && $buyerGstin !== 'URP' && $buyerGstin !== '') {
            if (!preg_match(self::GSTIN_REGEX, $buyerGstin)) {
                $errors[] = "Buyer GSTIN '{$buyerGstin}' is invalid.";
            }
        }

        // 3. Pincodes
        $sellerPin = trim((string) ($firm['pin'] ?? $firm['pincode'] ?? ''));
        if ($sellerPin === '' || !preg_match('/^[0-9]{6}$/', $sellerPin)) {
            $errors[] = "Seller pincode '{$sellerPin}' must be a 6-digit number.";
        }

        if (!$isExport) {
            $buyerPin = trim((string) ($customer['pin'] ?? $customer['zipcode'] ?? ''));
            if ($buyerPin === '' || !preg_match('/^[0-9]{6}$/', $buyerPin)) {
                $errors[] = "Buyer pincode '{$buyerPin}' must be a 6-digit number.";
            }
        }

        // 4. Line items
        if ($items === []) {
            $errors[] = 'At least one line item is required for E-Invoice generation.';
        } else {
            foreach ($items as $idx => $item) {
                $sn = $idx + 1;
                $qty = (float) ($item['quantity'] ?? $item['qty'] ?? 0);
                if ($qty <= 0) {
                    $errors[] = "Line item #{$sn} has invalid quantity ({$qty}).";
                }

                $hsn = preg_replace('/[^0-9]/', '', (string) ($item['hsn'] ?? ''));
                if (strlen($hsn) < 4) {
                    $errors[] = "Line item #{$sn} requires a valid HSN code (at least 4 digits).";
                }
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * Validate E-Way Bill Transport Data.
     *
     * @param array<string, mixed> $ewbData
     * @return array{valid:bool, errors:list<string>}
     */
    public static function validateEwbData(array $ewbData): array
    {
        $errors = [];

        $vehNo = strtoupper(trim((string) ($ewbData['veh_no'] ?? '')));
        if ($vehNo === '') {
            $errors[] = 'Vehicle Number is required for E-Way Bill generation.';
        }

        $distance = (int) ($ewbData['distance'] ?? 0);
        if ($distance <= 0) {
            $errors[] = 'Transport distance must be greater than 0 KM.';
        }

        $vehType = strtoupper(trim((string) ($ewbData['veh_type'] ?? 'R')));
        if ($vehType !== 'R' && $vehType !== 'O') {
            $errors[] = "Vehicle Type must be 'R' (Regular) or 'O' (ODC).";
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }
}
