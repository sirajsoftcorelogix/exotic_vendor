<?php

class CurrencyModel
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
        $this->ensureMappedCountriesColumn();
    }

    /**
     * Ensure mapped_countries column exists in currency_master
     */
    private function ensureMappedCountriesColumn(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        if ($this->db) {
            $check = @$this->db->query("SHOW COLUMNS FROM currency_master LIKE 'mapped_countries'");
            if ($check && $check->num_rows === 0) {
                @$this->db->query("ALTER TABLE currency_master ADD COLUMN mapped_countries TEXT NULL DEFAULT NULL AFTER display_symbol");
            }
        }
    }

    /**
     * Get default mapped countries for standard currency codes
     */
    public static function getDefaultMappedCountries(string $code): string
    {
        $code = strtoupper(trim($code));
        $defaults = [
            'EUR' => 'DE, FR, IT, ES, NL, BE, AT, FI, GR, IE, PT, SK, SI, EE, LV, LT, CY, MT, LU, HR',
            'GBP' => 'GB, UK',
            'DKK' => 'DK',
            'NOK' => 'NO',
            'USD' => 'US',
            'CAD' => 'CA',
            'AUD' => 'AU',
            'NZD' => 'NZ',
            'JPY' => 'JP',
            'INR' => 'IN',
            'CHF' => 'CH',
            'SEK' => 'SE',
            'SGD' => 'SG',
            'HKD' => 'HK',
            'AED' => 'AE',
            'SAR' => 'SA',
        ];

        return $defaults[$code] ?? '';
    }

    /**
     * Get all active currencies
     */
    public function getAllCurrencies(): array
    {
        $query = "SELECT * FROM currency_master WHERE is_active = 1 ORDER BY currency_code";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get currency by ID
     */
    public function getCurrencyById(int $id): ?array
    {
        $query = "SELECT * FROM currency_master WHERE id = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    /**
     * Get currency by Code (active or inactive)
     */
    public function getCurrencyByCode(string $code): ?array
    {
        $code = strtoupper(trim($code));
        $query = "SELECT * FROM currency_master WHERE currency_code = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) return null;
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    /**
     * Add new currency
     */
    public function addCurrency(array $data): array
    {
        $code = strtoupper(trim($data['currency_code'] ?? ''));
        $existing = $this->getCurrencyByCode($code);

        if ($existing) {
            return $this->reactivateCurrency((int)$existing['id'], $data);
        }

        $query = "INSERT INTO currency_master 
                  (currency_code, currency_name, currency_unit, display_symbol, mapped_countries, rate_import, rate_export, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 1)";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];

        $name = trim($data['currency_name'] ?? '');
        $unit = trim($data['currency_unit'] ?? '');
        $symbol = trim($data['display_symbol'] ?? '');
        $mapped_countries = trim($data['mapped_countries'] ?? self::getDefaultMappedCountries($code));
        $rate_import = floatval($data['rate_import'] ?? 0);
        $rate_export = floatval($data['rate_export'] ?? 0);

        $stmt->bind_param('sssssdd', $code, $name, $unit, $symbol, $mapped_countries, $rate_import, $rate_export);
        $result = $stmt->execute();

        if ($result) {
            $currencyId = $this->db->insert_id;
            $this->addRateHistory($code, $rate_import, $rate_export);
            return ['success' => true, 'id' => $currencyId, 'message' => 'Currency added successfully'];
        }
        return ['success' => false, 'message' => 'Failed to add currency'];
    }

    /**
     * Update existing currency
     */
    public function updateCurrency(int $id, array $data): array
    {
        $oldCurrency = $this->getCurrencyById($id);
        if (!$oldCurrency) {
            return ['success' => false, 'message' => 'Currency not found'];
        }

        $query = "UPDATE currency_master SET 
                  currency_name = ?, 
                  currency_unit = ?, 
                  display_symbol = ?,
                  mapped_countries = ?,
                  rate_import = ?, 
                  rate_export = ? 
                  WHERE id = ?";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];

        $code = strtoupper(trim($oldCurrency['currency_code']));
        $name = trim($data['currency_name'] ?? $oldCurrency['currency_name']);
        $unit = trim($data['currency_unit'] ?? $oldCurrency['currency_unit']);
        $symbol = trim($data['display_symbol'] ?? ($oldCurrency['display_symbol'] ?? ''));
        $mapped_countries = isset($data['mapped_countries']) ? trim($data['mapped_countries']) : ($oldCurrency['mapped_countries'] ?? self::getDefaultMappedCountries($code));
        $rate_import = floatval($data['rate_import']);
        $rate_export = floatval($data['rate_export']);

        $stmt->bind_param('ssssddi', $name, $unit, $symbol, $mapped_countries, $rate_import, $rate_export, $id);
        $result = $stmt->execute();

        if ($result) {
            if ($oldCurrency['rate_import'] != $rate_import || $oldCurrency['rate_export'] != $rate_export) {
                $this->addRateHistory($oldCurrency['currency_code'], $rate_import, $rate_export);
            }
            return ['success' => true, 'message' => 'Currency updated successfully'];
        }
        return ['success' => false, 'message' => 'Failed to update currency'];
    }

    /**
     * Reactivate a deactivated currency
     */
    public function reactivateCurrency(int $id, array $data): array
    {
        $query = "UPDATE currency_master SET 
                  currency_name = ?, 
                  currency_unit = ?, 
                  display_symbol = ?,
                  mapped_countries = ?,
                  rate_import = ?, 
                  rate_export = ?,
                  is_active = 1
                  WHERE id = ?";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return ['success' => false, 'message' => 'Prepare failed'];

        $code = strtoupper($data['currency_code'] ?? '');
        $name = trim($data['currency_name'] ?? '');
        $unit = trim($data['currency_unit'] ?? '');
        $symbol = trim($data['display_symbol'] ?? '');
        $mapped_countries = trim($data['mapped_countries'] ?? self::getDefaultMappedCountries($code));
        $rate_import = floatval($data['rate_import'] ?? 0);
        $rate_export = floatval($data['rate_export'] ?? 0);

        $stmt->bind_param('ssssddi', $name, $unit, $symbol, $mapped_countries, $rate_import, $rate_export, $id);
        $result = $stmt->execute();

        if ($result) {
            $this->addRateHistory($code, $rate_import, $rate_export);
            return ['success' => true, 'id' => $id, 'message' => 'Currency reactivated successfully'];
        }
        return ['success' => false, 'message' => 'Failed to reactivate currency'];
    }

    /**
     * Deactivate currency
     */
    public function deactivateCurrency(int $id): array
    {
        $query = "UPDATE currency_master SET is_active = 0 WHERE id = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) return ['success' => false];
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();

        return $result ? ['success' => true, 'message' => 'Currency deactivated'] : ['success' => false];
    }

    /**
     * Add rate history record
     */
    public function addRateHistory(string $currencyCode, float $rateImport, float $rateExport, ?string $rateDate = null): bool
    {
        $currencyCode = strtoupper(trim($currencyCode));
        $date = $rateDate ? $rateDate : date('Y-m-d');

        $query = "INSERT INTO currency_rate_history 
                  (currency_code, rate_import, rate_export, rate_date) 
                  VALUES (?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return false;
        $stmt->bind_param('sdds', $currencyCode, $rateImport, $rateExport, $date);
        return $stmt->execute();
    }

    /**
     * Get rate history for a currency
     */
    public function getRateHistory(string $currencyCode, int $limit = 30): array
    {
        $currencyCode = strtoupper(trim($currencyCode));
        $query = "SELECT * FROM currency_rate_history 
                  WHERE currency_code = ? 
                  ORDER BY rate_date DESC, id DESC 
                  LIMIT ?";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return [];
        $stmt->bind_param('si', $currencyCode, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Find active currency mapped to a specific country code or country name
     * (e.g. 'DE', 'FR', 'ES', 'GB', 'DK', 'NO')
     */
    public function getCurrencyByCountryCode(string $countryInput): ?array
    {
        $countryInput = trim($countryInput);
        if (empty($countryInput)) return null;

        $all = $this->getAllCurrencies();
        
        // 1. Check explicit mapped_countries in database or defaults
        foreach ($all as $curr) {
            $mapped = !empty($curr['mapped_countries']) 
                ? $curr['mapped_countries'] 
                : self::getDefaultMappedCountries($curr['currency_code']);

            if (!empty($mapped)) {
                $tags = array_map('trim', explode(',', $mapped));
                foreach ($tags as $tag) {
                    if (strcasecmp($tag, $countryInput) === 0) {
                        return $curr;
                    }
                }
            }
        }

        // 2. Direct currency code match (e.g. USD, EUR, GBP, DKK, NOK)
        foreach ($all as $curr) {
            if (strcasecmp($curr['currency_code'], $countryInput) === 0) {
                return $curr;
            }
        }

        return null;
    }

    /**
     * Bulk update exchange rates from parsed PDF or ICEGATE table
     */
    public function bulkUpdateExchangeRates(array $ratesData, string $source = 'PDF', ?string $effectiveDate = null, ?string $notificationNo = null): array
    {
        $effectiveDate = $effectiveDate ?: date('Y-m-d');
        $updatedCount = 0;
        $addedCount = 0;
        $errors = [];

        foreach ($ratesData as $item) {
            $code = strtoupper(trim($item['currency_code'] ?? ''));
            if (empty($code)) continue;

            $importRate = floatval($item['rate_import'] ?? 0);
            $exportRate = floatval($item['rate_export'] ?? 0);
            $name = trim($item['currency_name'] ?? '');
            $unit = trim($item['currency_unit'] ?? '');

            $existing = $this->getCurrencyByCode($code);

            if ($existing) {
                // Update currency_master
                $updateQuery = "UPDATE currency_master SET 
                                rate_import = ?, 
                                rate_export = ?, 
                                currency_name = IF(? != '', ?, currency_name),
                                currency_unit = IF(? != '', ?, currency_unit),
                                is_active = 1
                                WHERE currency_code = ?";

                $stmt = $this->db->prepare($updateQuery);
                if ($stmt) {
                    $stmt->bind_param('ddsssss', $importRate, $exportRate, $name, $name, $unit, $unit, $code);
                    if ($stmt->execute()) {
                        $updatedCount++;
                        $this->addRateHistory($code, $importRate, $exportRate, $effectiveDate);
                    } else {
                        $errors[] = "Failed to update {$code}: " . $stmt->error;
                    }
                }
            } else {
                // Insert new currency into currency_master
                if (empty($name)) $name = $code;
                if (empty($unit)) $unit = '1 ' . $code;

                $insertQuery = "INSERT INTO currency_master 
                                (currency_code, currency_name, currency_unit, rate_import, rate_export, is_active) 
                                VALUES (?, ?, ?, ?, ?, 1)";

                $stmt = $this->db->prepare($insertQuery);
                if ($stmt) {
                    $stmt->bind_param('sssdd', $code, $name, $unit, $importRate, $exportRate);
                    if ($stmt->execute()) {
                        $addedCount++;
                        $this->addRateHistory($code, $importRate, $exportRate, $effectiveDate);
                    } else {
                        $errors[] = "Failed to add {$code}: " . $stmt->error;
                    }
                }
            }
        }

        return [
            'success' => count($errors) === 0,
            'updated' => $updatedCount,
            'added'   => $addedCount,
            'errors'  => $errors,
            'message' => "Successfully updated {$updatedCount} currencies and added {$addedCount} new currencies."
        ];
    }
}
