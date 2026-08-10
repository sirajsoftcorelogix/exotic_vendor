<?php

/**
 * Central High-Value Transaction Compliance Validator
 *
 * Enforces Indian Income Tax Rule 114B / statutory compliance requirements
 * (PAN Card, Passport Number, Country of Residence, GSTIN)
 * at the time of Tax Invoice creation rather than Order Creation.
 */
class HighValueComplianceValidator
{
    /**
     * Get high-value transaction limit threshold (default: ₹2,00,000.00).
     */
    public static function getLimit(mysqli $conn): float
    {
        if (function_exists('app_setting')) {
            $configured = (float) app_setting('high_value_transaction_limit', 200000.00);
            if ($configured > 0) {
                return $configured;
            }
        }

        $res = $conn->query("SELECT setting_value FROM app_settings WHERE setting_key = 'high_value_transaction_limit' LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $val = (float) $row['setting_value'];
            if ($val > 0) {
                return $val;
            }
        }

        return 200000.00;
    }

    /**
     * Normalize PAN number (uppercase, 10 characters).
     */
    public static function normalizePan(string $pan): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($pan)));
    }

    /**
     * Normalize Passport number.
     */
    public static function normalizePassport(string $passport): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($passport)));
    }

    /**
     * Normalize Residency Status.
     */
    public static function normalizeResidencyStatus(string $status): string
    {
        $upper = strtoupper(trim($status));
        if (in_array($upper, ['INDIAN_RESIDENT', 'NRI', 'FOREIGN_NATIONAL'], true)) {
            return $upper;
        }

        return 'INDIAN_RESIDENT';
    }

    /**
     * Convert an amount in a given currency to INR using currency_master export rate.
     */
    public static function convertToInr(mysqli $conn, float $amount, string $currency, ?float $exchangeRate = null): float
    {
        $currency = strtoupper(trim($currency));
        if ($currency === '' || $currency === 'INR') {
            return $amount;
        }

        if ($exchangeRate !== null && $exchangeRate > 0) {
            return $amount * $exchangeRate;
        }

        $stmt = $conn->prepare("SELECT rate_export FROM currency_master WHERE currency_code = ? AND is_active = 1 LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $currency);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($row && !empty($row['rate_export']) && (float)$row['rate_export'] > 0) {
                return $amount * (float)$row['rate_export'];
            }
        }

        return $amount;
    }

    /**
     * Validate customer compliance documents for invoice creation.
     *
     * @param mysqli $conn
     * @param int $customerId
     * @param float $invoiceTotal
     * @param array<string, mixed> $payload Optional explicit compliance fields provided in current request
     * @return array{
     *   ok: bool,
     *   is_high_value: bool,
     *   limit: float,
     *   code: string,
     *   message: string,
     *   customer_id: int,
     *   customer_name: string,
     *   residency_status: string,
     *   pan: string,
     *   passport: string,
     *   country_of_residence: string,
     *   gstin: string,
     *   missing_fields: list<string>
     * }
     */
    public static function validateCustomerCompliance(mysqli $conn, int $customerId, float $invoiceTotal, array $payload = []): array
    {
        $limit = self::getLimit($conn);
        $currency = strtoupper(trim((string)($payload['currency'] ?? $payload['header']['currency'] ?? 'INR')));

        if (isset($payload['converted_amount']) && (float)$payload['converted_amount'] > 0) {
            $inrTotal = (float)$payload['converted_amount'];
        } elseif (isset($payload['inr_amount']) && (float)$payload['inr_amount'] > 0) {
            $inrTotal = (float)$payload['inr_amount'];
        } else {
            $exchangeRate = isset($payload['exchange_rate']) ? (float)$payload['exchange_rate'] : (isset($payload['rate_export']) ? (float)$payload['rate_export'] : null);
            $inrTotal = self::convertToInr($conn, $invoiceTotal, $currency, $exchangeRate);
        }

        $isHighValue = $inrTotal >= $limit;

        $customer = self::fetchCustomerRow($conn, $customerId);
        $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        if ($customerName === '') {
            $customerName = 'Customer #' . $customerId;
        }

        $residency = self::normalizeResidencyStatus(
            (string)($payload['customer_residency_status'] ?? $customer['customer_residency_status'] ?? 'INDIAN_RESIDENT')
        );
        $pan = self::normalizePan(
            (string)($payload['customer_pan'] ?? $customer['customer_pan'] ?? '')
        );
        $passport = self::normalizePassport(
            (string)($payload['passport_number'] ?? $customer['passport_number'] ?? '')
        );
        $countryOfResidence = trim(
            (string)($payload['country_of_residence'] ?? $customer['country_of_residence'] ?? '')
        );
        $gstin = strtoupper(trim(
            (string)($payload['confirm_gstin'] ?? $payload['gstin'] ?? $customer['gstin'] ?? '')
        ));

        // GSTIN auto-derives PAN if present
        if ($gstin !== '' && preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gstin)) {
            $derivedPan = substr($gstin, 2, 10);
            if ($pan === '') {
                $pan = $derivedPan;
            }
        }

        $baseResponse = [
            'is_high_value' => $isHighValue,
            'limit' => $limit,
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'residency_status' => $residency,
            'pan' => $pan,
            'passport' => $passport,
            'country_of_residence' => $countryOfResidence,
            'gstin' => $gstin,
            'missing_fields' => [],
        ];

        if (!$isHighValue) {
            return array_merge($baseResponse, [
                'ok' => true,
                'code' => 'NOT_HIGH_VALUE',
                'message' => 'Transaction total is below high value limit.',
            ]);
        }

        // B2B with valid GSTIN is automatically compliant
        if ($gstin !== '' && preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gstin)) {
            return array_merge($baseResponse, [
                'ok' => true,
                'code' => 'GSTIN_PRESENT',
                'message' => 'Compliant via GSTIN.',
            ]);
        }

        $missing = [];
        if ($residency === 'INDIAN_RESIDENT') {
            if ($pan === '' || !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
                $missing[] = 'pan';
                return array_merge($baseResponse, [
                    'ok' => false,
                    'code' => 'PAN_REQUIRED',
                    'message' => 'PAN Card is required for Indian Resident invoices >= ₹' . number_format($limit, 2),
                    'missing_fields' => $missing,
                ]);
            }
        } elseif ($residency === 'NRI') {
            $hasValidPan = ($pan !== '' && preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan));
            $hasValidPassport = (strlen($passport) >= 6 && $countryOfResidence !== '');

            if (!$hasValidPan && !$hasValidPassport) {
                if ($pan !== '' && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
                    $missing[] = 'pan';
                }
                if ($passport !== '' && strlen($passport) < 6) {
                    $missing[] = 'passport';
                }
                if ($countryOfResidence === '') {
                    $missing[] = 'country_of_residence';
                }
                if (empty($missing)) {
                    $missing = ['pan', 'passport', 'country_of_residence'];
                }

                return array_merge($baseResponse, [
                    'ok' => false,
                    'code' => 'PAN_OR_PASSPORT_REQUIRED',
                    'message' => 'NRI customer requires valid PAN Card OR Passport Number with Country of Residence.',
                    'missing_fields' => $missing,
                ]);
            }
        } else { // FOREIGN_NATIONAL
            if (strlen($passport) < 6) {
                $missing[] = 'passport';
            }
            if ($countryOfResidence === '') {
                $missing[] = 'country_of_residence';
            }

            if (!empty($missing)) {
                return array_merge($baseResponse, [
                    'ok' => false,
                    'code' => 'PASSPORT_REQUIRED',
                    'message' => 'Foreign National customer requires Passport Number and Country of Residence.',
                    'missing_fields' => $missing,
                ]);
            }
        }

        return array_merge($baseResponse, [
            'ok' => true,
            'code' => 'COMPLIANT',
            'message' => 'Customer compliance validation passed.',
        ]);
    }

    /**
     * Persist compliance details to vp_customers row.
     */
    public static function saveCustomerCompliance(mysqli $conn, int $customerId, array $data): array
    {
        if ($customerId <= 0) {
            return ['success' => false, 'message' => 'Invalid customer ID'];
        }

        $residency = self::normalizeResidencyStatus((string)($data['customer_residency_status'] ?? 'INDIAN_RESIDENT'));
        $pan = self::normalizePan((string)($data['customer_pan'] ?? ''));
        $passport = self::normalizePassport((string)($data['passport_number'] ?? ''));
        $country = trim((string)($data['country_of_residence'] ?? ''));
        $gstin = strtoupper(trim((string)($data['confirm_gstin'] ?? $data['gstin'] ?? '')));

        if ($gstin !== '' && preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gstin)) {
            $derivedPan = substr($gstin, 2, 10);
            if ($pan === '') {
                $pan = $derivedPan;
            }
        }

        $stmt = $conn->prepare(
            'UPDATE vp_customers
             SET customer_residency_status = ?, customer_pan = ?, passport_number = ?, country_of_residence = ?' .
             ($gstin !== '' ? ', gstin = ?' : '') .
             ' WHERE id = ?'
        );

        if (!$stmt) {
            return ['success' => false, 'message' => 'Database prepare failed: ' . $conn->error];
        }

        if ($gstin !== '') {
            $stmt->bind_param('sssssi', $residency, $pan, $passport, $country, $gstin, $customerId);
        } else {
            $stmt->bind_param('ssssi', $residency, $pan, $passport, $country, $customerId);
        }

        $executed = $stmt->execute();
        $stmt->close();

        if (!$executed) {
            return ['success' => false, 'message' => 'Failed to save customer compliance details.'];
        }

        return ['success' => true, 'message' => 'Customer compliance details saved successfully.'];
    }

    /**
     * Fetch customer row helper.
     */
    private static function fetchCustomerRow(mysqli $conn, int $customerId): array
    {
        if ($customerId <= 0) {
            return [];
        }

        $stmt = $conn->prepare('SELECT * FROM vp_customers WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return is_array($row) ? $row : [];
    }
}
