<?php

/**
 * Build Aramex CreateShipments payloads aligned with the legacy ERP template.
 *
 * ERP placeholders are mapped to vendor-portal dispatch / invoice fields.
 */
class AramexShipmentBuilder
{
    /** @param array<string, mixed> $credentials From courier_partner_accounts.credentials_json */
    public static function clientInfoFromCredentials(array $credentials): array
    {
        return [
            'UserName' => (string) ($credentials['username'] ?? ''),
            'Password' => (string) ($credentials['password'] ?? ''),
            'Version' => (string) ($credentials['version'] ?? 'v1.0'),
            'AccountNumber' => (string) ($credentials['account_number'] ?? ''),
            'AccountPin' => (string) ($credentials['account_pin'] ?? ''),
            'AccountEntity' => (string) ($credentials['account_entity'] ?? $credentials['entity'] ?? 'DEL'),
            'AccountCountryCode' => (string) ($credentials['account_country_code'] ?? $credentials['country_code'] ?? 'IN'),
            'Source' => (int) ($credentials['client_source'] ?? 24),
        ];
    }

    /** @param array<string, mixed> $credentials */
    public static function labelInfoFromCredentials(array $credentials): array
    {
        $label = is_array($credentials['label_info'] ?? null) ? $credentials['label_info'] : [];
        return [
            'ReportID' => (int) ($label['report_id'] ?? 9729),
            'ReportType' => (string) ($label['report_type'] ?? 'URL'),
        ];
    }

    /** @param array<string, mixed> $credentials */
    public static function shipperFromCredentials(array $credentials, ?string $reference1 = null): array
    {
        $s = is_array($credentials['shipper'] ?? null) ? $credentials['shipper'] : [];
        $accountNumber = (string) ($credentials['account_number'] ?? '');

        return [
            'Reference1' => $reference1 ?? '',
            'Reference2' => '',
            'AccountNumber' => $accountNumber,
            'PartyAddress' => [
                'Line1' => (string) ($s['line1'] ?? ''),
                'Line2' => (string) ($s['line2'] ?? ''),
                'Line3' => '',
                'City' => (string) ($s['city'] ?? 'Delhi'),
                'StateOrProvinceCode' => (string) ($s['state'] ?? ''),
                'PostCode' => (string) ($s['postcode'] ?? '110052'),
                'CountryCode' => (string) ($s['country_code'] ?? 'IN'),
                'Longitude' => 0,
                'Latitude' => 0,
            ],
            'Contact' => [
                'Department' => '',
                'PersonName' => (string) ($s['full_name'] ?? ''),
                'Title' => '',
                'CompanyName' => (string) ($s['company_name'] ?? 'Exotic India Art Pvt Ltd'),
                'PhoneNumber1' => (string) ($s['phone'] ?? ''),
                'PhoneNumber1Ext' => '',
                'PhoneNumber2' => '',
                'PhoneNumber2Ext' => '',
                'FaxNumber' => '',
                'CellPhone' => (string) ($s['phone'] ?? ''),
                'EmailAddress' => (string) ($s['email'] ?? ''),
                'Type' => '',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $address Consignee address (shipping)
     * @return array<string, mixed>
     */
    public static function consigneeFromAddress(array $address): array
    {
        $first = trim((string) ($address['shipping_first_name'] ?? $address['first_name'] ?? ''));
        $last = trim((string) ($address['shipping_last_name'] ?? $address['last_name'] ?? ''));
        $person = trim($first . ' ' . $last);
        $company = trim((string) ($address['shipping_company'] ?? $address['company'] ?? ''));
        if ($company === '') {
            $company = $person;
        }

        return [
            'Reference1' => (string) ($address['reference1'] ?? 'CSB V'),
            'Reference2' => '',
            'AccountNumber' => '',
            'PartyAddress' => [
                'Line1' => (string) ($address['shipping_address_line1'] ?? $address['address_line1'] ?? ''),
                'Line2' => (string) ($address['shipping_address_line2'] ?? $address['address_line2'] ?? ''),
                'Line3' => '',
                'City' => trim((string) ($address['shipping_city'] ?? $address['city'] ?? '')),
                'StateOrProvinceCode' => (string) ($address['shipping_state'] ?? $address['state'] ?? ''),
                'PostCode' => (string) ($address['shipping_zipcode'] ?? $address['zipcode'] ?? ''),
                'CountryCode' => (string) ($address['shipping_country_code'] ?? $address['country_code'] ?? $address['shipping_country'] ?? ''),
                'Longitude' => 0,
                'Latitude' => 0,
                'BuildingNumber' => '',
                'BuildingName' => '',
                'Floor' => '',
                'Apartment' => '',
                'POBox' => null,
                'Description' => '',
            ],
            'Contact' => [
                'Department' => '',
                'PersonName' => $person,
                'Title' => '',
                'CompanyName' => $company,
                'PhoneNumber1' => self::extractPhone($address),
                'PhoneNumber1Ext' => '',
                'PhoneNumber2' => '',
                'PhoneNumber2Ext' => '',
                'FaxNumber' => '',
                'CellPhone' => self::extractPhone($address),
                'EmailAddress' => (string) ($address['shipping_email'] ?? $address['email'] ?? ''),
                'Type' => '',
            ],
        ];
    }

    private static function extractPhone(array $address): string
    {
        // Try various phone field names in order of preference
        $phone = 
            $address['shipping_phone'] ?? 
            $address['shipping_mobile'] ?? 
            $address['shipping_cell'] ?? 
            $address['shipping_telephone'] ?? 
            $address['phone'] ?? 
            $address['mobile'] ?? 
            $address['cell'] ?? 
            $address['telephone'] ?? 
            '';
        
        return (string) $phone;
    }

    private static function parseAndFormatDate($date, $format = 'm/d/Y'): string
    {
        if (empty($date)) {
            return date($format);
        }

        $date = trim((string) $date);

        // Already in correct format? (Y-m-d)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            list($year, $month, $day) = explode('-', $date);
            return date($format, mktime(0, 0, 0, (int) $month, (int) $day, (int) $year));
        }

        // Already in correct format? (m/d/Y)
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            return $date;
        }

        // Unix timestamp
        if (is_numeric($date) || ctype_digit($date)) {
            return date($format, (int) $date);
        }

        // Try strtotime
        $parsed = strtotime($date);
        if ($parsed !== false) {
            return date($format, $parsed);
        }

        // Try common date formats manually
        // DD/MM/YYYY (e.g., 15/06/2026)
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $m)) {
            $ts = mktime(0, 0, 0, (int) $m[2], (int) $m[1], (int) $m[3]);
            // Check if it's a valid date
            if ($ts !== false && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                return date($format, $ts);
            }
        }

        // DD-MM-YYYY (e.g., 15-06-2026)
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date, $m)) {
            $ts = mktime(0, 0, 0, (int) $m[2], (int) $m[1], (int) $m[3]);
            if ($ts !== false && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                return date($format, $ts);
            }
        }

        // YYYY/MM/DD (e.g., 2026/06/15)
        if (preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $date, $m)) {
            $ts = mktime(0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1]);
            if ($ts !== false && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                return date($format, $ts);
            }
        }

        // YYYYMMDD (e.g., 20260615)
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $date, $m)) {
            $ts = mktime(0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1]);
            if ($ts !== false && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                return date($format, $ts);
            }
        }

        // If all else fails, use today
        error_log("Warning: Could not parse invoice date '$date', using today's date");
        return date($format);
    }

    public static function normalizeCurrencyCode(string $code): string
    {
        $code = strtoupper(trim($code));
        if ($code === 'YEN') {
            return 'JPY';
        }
        return $code !== '' ? $code : 'USD';
    }

    /** Chargeable weight = max(actual, volumetric) per ERP script. */
    public static function chargeableWeightKg(float $actualKg, float $volumetricKg): float
    {
        return $actualKg > $volumetricKg ? $actualKg : $volumetricKg;
    }

    public static function commodityCodeFromHsn(?string $hsn): string
    {
        $digits = preg_replace('/\D/', '', (string) $hsn);
        return substr((string) $digits, 0, 8);
    }

    /**
     * @param array<string, mixed> $credentials
     * @param array<string, mixed> $context Keys: order_number, address, box, invoice, items, product_type, description, customs_value, currency_code, shipping_date
     * @return array<string, mixed> Full CreateShipments request body (without ClientInfo if caller adds via service)
     */
    public static function buildShipment(array $credentials, array $context): array
    {
        $orderNumber = (string) ($context['order_number'] ?? '');
        $address = is_array($context['address'] ?? null) ? $context['address'] : [];
        $box = is_array($context['box'] ?? null) ? $context['box'] : [];
        $invoice = is_array($context['invoice'] ?? null) ? $context['invoice'] : [];
        $items = is_array($context['items'] ?? null) ? $context['items'] : [];

        $actual = (float) ($box['weight'] ?? 0);
        $volumetric = (float) ($box['volumetric_weight'] ?? 0);
        $weight = self::chargeableWeightKg($actual, $volumetric);
        if ($weight <= 0) {
            $weight = 0.1;
        }

        $currency = self::normalizeCurrencyCode(
            (string) ($context['currency_code'] ?? $invoice['shipping_currency'] ?? $credentials['rating_currency'] ?? 'USD')
        );
        $customsValue = (float) ($context['customs_value'] ?? $invoice['total_amount'] ?? 0);
        if ($customsValue <= 0) {
            $customsValue = 0.01;
        }

        $productGroup = (string) ($context['product_group'] ?? $credentials['default_product_group'] ?? 'EXP');
        $productType = (string) ($context['product_type'] ?? $credentials['default_product_type'] ?? '');
        $description = (string) ($context['description'] ?? $invoice['goods_description'] ?? 'Goods');
        $hsn = self::commodityCodeFromHsn(
            (string) ($context['commodity_code'] ?? ($items[0]['hsn'] ?? $items[0]['hs_code'] ?? ''))
        );

        $shipperGst = (string) ($credentials['shipper_gstin'] ?? '07AADCE1400C1ZJ');
        $consigneeGst = trim((string) ($address['gst_number'] ?? $address['shipping_gstin'] ?? ''));
        if ($consigneeGst === '') {
            $consigneeGst = 'null';
        }

        $invoiceNumber = (string) ($invoice['invoice_number'] ?? $context['invoice_number'] ?? '');
        
        // Invoice date MUST be in MM/DD/YYYY format for Aramex ERR48 compliance
        // (Even though ISO 8601 would be standard, Aramex API requires American date format)
        $invDateRaw = $invoice['invoice_date'] ?? $context['invoice_date'] ?? date('Y-m-d');
        $invoiceDate = self::parseAndFormatDate($invDateRaw, 'm/d/Y');
        
        $taxAmount = round((float) ($context['tax_amount'] ?? $invoice['tax_amount'] ?? 0), 2);

        $shippingTs = $context['shipping_date'] ?? time();
        // Convert to ISO 8601 format for SOAP/XML (not JavaScript /Date()/ format)
        if (is_numeric($shippingTs)) {
            $shippingDateTime = date('Y-m-d\TH:i:s', (int) $shippingTs);
        } else {
            $shippingDateTime = (string) $shippingTs;
        }

        $additionalProperties = [
            [
                'CategoryName' => 'CustomsClearance',
                'Name' => 'ShipperTaxIdVATEINNumber',
                'Value' => $shipperGst,
            ],
            [
                'CategoryName' => 'CustomsClearance',
                'Name' => 'ConsigneeTaxIdVATEINNumber',
                'Value' => $consigneeGst,
            ],
            [
                'CategoryName' => 'CustomsClearance',
                'Name' => 'TaxPaid',
                'Value' => '1',
            ],
            [
                'CategoryName' => 'CustomsClearance',
                'Name' => 'InvoiceDate',
                'Value' => $invoiceDate,
            ],
            [
                'CategoryName' => 'CustomsClearance',
                'Name' => 'InvoiceNumber',
                'Value' => $invoiceNumber,
            ],
            [
                'CategoryName' => 'CustomsClearance',
                'Name' => 'TaxAmount',
                'Value' => (string) $taxAmount,
            ],
            [
                'CategoryName' => 'CustomsClearance',
                'Name' => 'ExporterType',
                'Value' => (string) ($credentials['exporter_type'] ?? 'UT'),
            ],
        ];

        return [
            'Reference1' => '',
            'Reference2' => '',
            'Reference3' => '',
            'Shipper' => self::shipperFromCredentials($credentials, $orderNumber),
            'Consignee' => self::consigneeFromAddress($address),
            'ThirdParty' => self::emptyThirdParty(),
            'ShippingDateTime' => $shippingDateTime,
            'DueDate' => $shippingDateTime,
            'Comments' => '',
            'PickupLocation' => '',
            'OperationsInstructions' => '',
            'AccountingInstrcutions' => '',
            'Details' => [
                'Dimensions' => null,
                'ActualWeight' => ['Unit' => 'KG', 'Value' => $weight],
                'ChargeableWeight' => null,
                'DescriptionOfGoods' => $description,
                'GoodsOriginCountry' => 'IN',
                'NumberOfPieces' => (int) ($box['pieces'] ?? 1),
                'ProductGroup' => $productGroup,
                'ProductType' => $productType,
                'PaymentType' => 'P',
                'PaymentOptions' => '',
                'CustomsValueAmount' => [
                    'CurrencyCode' => $currency,
                    'Value' => $customsValue,
                ],
                'CashOnDeliveryAmount' => null,
                'InsuranceAmount' => null,
                'CashAdditionalAmount' => null,
                'CashAdditionalAmountDescription' => '',
                'CollectAmount' => null,
                'Services' => '',
                'Items' => [[
                    'PackageType' => 'Box',
                    'Quantity' => '1',
                    'Weight' => null,
                    'Comments' => '',
                    'GoodsDescription' => $description,
                    'CustomsValue' => [
                        'CurrencyCode' => $currency,
                        'Value' => $customsValue,
                    ],
                    'Reference' => '',
                    'CommodityCode' => $hsn,
                ]],
                'AdditionalProperties' => $additionalProperties,
            ],
            'Attachments' => [],
            'ForeignHAWB' => '',
            'TransportType ' => 0,
            'PickupGUID' => '',
            'Number' => null,
            'ScheduledDelivery' => null,
        ];
    }

    /** @return array<string, mixed> */
    public static function buildCreateShipmentsRequest(array $credentials, array $context): array
    {
        return [
            'ClientInfo' => self::clientInfoFromCredentials($credentials),
            'LabelInfo' => self::labelInfoFromCredentials($credentials),
            'Shipments' => [self::buildShipment($credentials, $context)],
            'Transaction' => [
                'Reference1' => '',
                'Reference2' => '',
                'Reference3' => '',
                'Reference4' => '',
                'Reference5' => '',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function emptyThirdParty(): array
    {
        return [
            'Reference1' => '',
            'Reference2' => '',
            'AccountNumber' => '',
            'PartyAddress' => [
                'Line1' => 'null',
                'Line2' => 'null',
                'Line3' => '',
                'City' => 'null',
                'StateOrProvinceCode' => '',
                'PostCode' => 'null',
                'CountryCode' => 'null',
                'Longitude' => 0,
                'Latitude' => 0,
            ],
            'Contact' => [
                'Department' => '',
                'PersonName' => 'null',
                'Title' => 'null',
                'CompanyName' => 'null',
                'PhoneNumber1' => 'null',
                'PhoneNumber1Ext' => 'null',
                'PhoneNumber2' => 'null',
                'PhoneNumber2Ext' => '',
                'FaxNumber' => 'null',
                'CellPhone' => 'null',
                'EmailAddress' => 'null',
                'Type' => '',
            ],
        ];
    }
}
