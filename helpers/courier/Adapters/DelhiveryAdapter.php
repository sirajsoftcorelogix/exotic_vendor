<?php

require_once __DIR__ . '/../Contracts/CourierAdapterInterface.php';
require_once __DIR__ . '/../country_codes.php';
require_once __DIR__ . '/../Support/CourierUiFormat.php';
require_once __DIR__ . '/../credential_urls.php';
require_once __DIR__ . '/../../../models/courier/CourierAccount.php';
require_once __DIR__ . '/../../../models/courier/CourierShipment.php';
require_once __DIR__ . '/../../../delhivery_service.php';

/**
 * Delhivery domestic adapter (skeleton — implement API calls here).
 *
 * Teammate: fill getRates() / createShipment() using credentials from
 * courier_partner_accounts.credentials_json (partner_code = delhivery).
 */
class DelhiveryAdapter implements CourierAdapterInterface
{
    private CourierAccount $accountModel;
    private ?CourierShipment $shipmentModel;

    public function __construct(CourierAccount $accountModel, ?CourierShipment $shipmentModel = null)
    {
        $this->accountModel = $accountModel;
        $this->shipmentModel = $shipmentModel;
    }

    public function partnerCode(): string
    {
        return 'delhivery';
    }

    public function getRates(array $request): array
    {
        $destinationCountry = normalizeCountryIso2(
            $request['destination_country']
                ?? ($request['destination']['country_code'] ?? 'IN')
        );
        if (isInternationalShipmentCountry($destinationCountry)) {
            return [
                'success' => false,
                'message' => 'Delhivery adapter is domestic only (IN → IN).',
            ];
        }

        $accounts = $this->accountModel->listActiveAccountsByPartnerCode('delhivery');
        if (!$accounts) {
            return [
                'success' => false,
                'message' => 'No active Delhivery courier account configured. Add one under Courier accounts.',
            ];
        }

        $accountId = (int) ($request['partner_account_id'] ?? 0);
        $accountRow = $this->pickAccount($accounts, $accountId);
        $accountId = (int) ($accountRow['id'] ?? 0);
        $credentials = $this->accountModel->getCredentialsJson($accountId);

        $apiKey = trim((string) ($credentials['api_key'] ?? $credentials['token'] ?? $credentials['api_token'] ?? ''));
        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => 'Delhivery api_token is missing. Save credentials in Courier accounts.',
            ];
        }

        $originPin = trim((string) ($request['pickup']['postcode'] ?? $request['pickup_postcode'] ?? ''));
        if ($originPin === '') {
            $originPin = trim((string) (($credentials['shipper']['postcode'] ?? null) ?? ($credentials['customer_pincode'] ?? '')));
        }
        $destPin = trim((string) ($request['destination']['postcode'] ?? $request['delivery_postcode'] ?? ''));
        if ($originPin === '' || $destPin === '') {
            return [
                'success' => false,
                'message' => 'Origin and destination pincodes are required for Delhivery rates.',
            ];
        }

        // Delhivery cgm is grams, weight-only (API has no dimensions). Use the same actual
        // box weight (kg) sent to Shiprocket serviceability, not volumetric chargeable weight.
        $actualKg = (float) ($request['weight'] ?? 0);
        $billableKg = (float) ($request['chargeable_weight_kg'] ?? $actualKg);
        $chargeableGrams = (int) ceil(max(0.0, $actualKg) * 1000);
        if ($chargeableGrams <= 0) {
            return [
                'success' => false,
                'message' => 'Box weight is required for Delhivery rates.',
            ];
        }

        $urlInfo = resolveCourierCredentialUrls($credentials);
        $environment = (string) ($urlInfo['environment'] ?? 'sandbox');
        $baseUrlOverride = trim((string) ($urlInfo['api_base_url'] ?? ''));
        $clientName = trim((string) ($credentials['client_name'] ?? $credentials['cl'] ?? ''));
        $isCod = !empty($request['cod']);
        $pt = $isCod ? 'COD' : 'Pre-paid';
        $ss = 'Delivered';
        $rateApiPath = trim((string) ($credentials['rate_api_path'] ?? $credentials['calculate_shipping_cost_path'] ?? ''));

        $service = new DelhiveryService($apiKey, $environment, $baseUrlOverride);

        // Match Delhivery One developer portal: md, ss, o_pin, d_pin, cgm, pt, cod (optional cl).
        $baseParams = [
            'o_pin' => $originPin,
            'd_pin' => $destPin,
            'ss' => $ss,
            'pt' => $pt,
            'cod' => $isCod ? 1 : 0,
        ];
        if ($clientName !== '') {
            $baseParams['cl'] = $clientName;
        }

        $quotes = [];
        $debug = [];

        foreach (['S' => 'Surface', 'E' => 'Express'] as $md => $label) {
            $params = array_merge($baseParams, [
                'md' => $md,
                'cgm' => $chargeableGrams,
            ]);

            $resp = $service->estimateFreightCharges($params, $rateApiPath);
            $debug[$md] = $resp;

            if (empty($resp['success'])) {
                continue;
            }

            $breakdown = $this->parseChargeBreakdown($resp['data'] ?? null, $resp['raw'] ?? null);
            $price = $this->resolveDisplayAmount($breakdown);
            if ($price === null || $price <= 0) {
                continue;
            }

            $quotes[] = [
                'id' => 'delhivery_' . $accountId . '_' . ($md === 'S' ? 'SURFACE' : 'EXPRESS'),
                'name' => 'Delhivery - ' . $label,
                'price' => $price,
                'currency' => 'INR',
                'etd' => 'N/A',
                'rating' => 0,
                'partner_code' => 'delhivery',
                'partner_account_id' => $accountId,
                'product_type' => $md === 'S' ? 'SURFACE' : 'EXPRESS',
                'service_code' => $md === 'S' ? 'SURFACE' : 'EXPRESS',
                'metadata' => [
                    'cgm' => $chargeableGrams,
                    'actual_weight_kg' => $actualKg,
                    'billable_weight_kg' => $billableKg,
                    'gross_amount' => $breakdown['gross_amount'],
                    'total_amount' => $breakdown['total_amount'],
                    'origin_pin' => $originPin,
                    'dest_pin' => $destPin,
                    'payment_type' => $pt,
                    'cod' => $isCod ? 1 : 0,
                ],
            ];
        }

        if ($this->shipmentModel) {
            $this->shipmentModel->logApiCall(
                'delhivery',
                'get_rates',
                $accountId,
                (string) ($request['order_number'] ?? ''),
                $request,
                $debug,
                !empty($quotes),
                !empty($quotes) ? 'Delhivery rates fetched' : 'No rates returned from Delhivery'
            );
        }

        if (empty($quotes)) {
            $hint = $environment === 'sandbox'
                ? 'Account environment is sandbox — switch to production if this is a live token.'
                : 'Verify client_name (cl), token, and pincodes in Courier accounts.';
            return [
                'success' => false,
                'message' => 'Delhivery API returned no rates. ' . $hint,
                'debug' => [
                    'partner' => 'delhivery',
                    'account_id' => $accountId,
                    'environment' => $environment,
                    'client_name' => $clientName,
                    'origin_pin' => $originPin,
                    'dest_pin' => $destPin,
                    'cgm' => $chargeableGrams,
                    'actual_weight_kg' => $actualKg,
                    'responses' => $debug,
                ],
            ];
        }

        return [
            'success' => true,
            'provider' => 'delhivery',
            'couriers' => CourierUiFormat::formatQuotes($quotes),
            'debug' => [
                'partner' => 'delhivery',
                'account_id' => $accountId,
                'environment' => $environment,
                'rate_request' => [
                    'origin_pin' => $originPin,
                    'dest_pin' => $destPin,
                    'cgm' => $chargeableGrams,
                    'actual_weight_kg' => $actualKg,
                    'billable_weight_kg' => $billableKg,
                    'client_name' => $clientName !== '' ? $clientName : null,
                    'payment_type' => $pt,
                    'cod' => $isCod ? 1 : 0,
                    'rate_api_path' => $rateApiPath !== '' ? $rateApiPath : 'kinko/v1/invoice/charges/.json',
                ],
                'responses' => $debug,
            ],
        ];
    }

    /**
     * Delhivery One portal often returns gross_amount=0 and total_amount as the estimate (e.g. 129).
     * When gross is present, prefer it (pre-tax); otherwise use total_amount.
     *
     * @param array{gross_amount:?float,total_amount:?float,charged_weight_g:?int} $breakdown
     */
    private function resolveDisplayAmount(array $breakdown): ?float
    {
        $gross = $breakdown['gross_amount'];
        $total = $breakdown['total_amount'];
        if ($gross !== null && $gross > 0) {
            return (float) $gross;
        }
        if ($total !== null && $total > 0) {
            return (float) $total;
        }
        return null;
    }

    /**
     * @param mixed $data
     * @return array{gross_amount:?float,total_amount:?float,charged_weight_g:?int}
     */
    private function parseChargeBreakdown($data, ?string $raw = null): array
    {
        $row = $this->findChargeRow($data);
        if ($row !== null) {
            return [
                'gross_amount' => isset($row['gross_amount']) && is_numeric($row['gross_amount'])
                    ? (float) $row['gross_amount']
                    : null,
                'total_amount' => isset($row['total_amount']) && is_numeric($row['total_amount'])
                    ? (float) $row['total_amount']
                    : null,
                'charged_weight_g' => isset($row['charged_weight']) && is_numeric($row['charged_weight'])
                    ? (int) $row['charged_weight']
                    : null,
            ];
        }

        $raw = trim((string) ($raw ?? (is_string($data) ? $data : '')));
        $gross = null;
        $total = null;
        if ($raw !== '') {
            if (preg_match('/<gross_amount[^>]*>([\d.]+)/i', $raw, $m)) {
                $gross = (float) $m[1];
            }
            if (preg_match('/<total_amount[^>]*>([\d.]+)/i', $raw, $m)) {
                $total = (float) $m[1];
            }
        }

        return [
            'gross_amount' => $gross,
            'total_amount' => $total,
            'charged_weight_g' => null,
        ];
    }

    /**
     * @param mixed $data
     * @return array<string, mixed>|null
     */
    private function findChargeRow($data): ?array
    {
        if (!is_array($data)) {
            return null;
        }
        if (isset($data['gross_amount']) || isset($data['total_amount'])) {
            return $data;
        }
        foreach ($data as $value) {
            if (!is_array($value)) {
                continue;
            }
            if (isset($value['gross_amount']) || isset($value['total_amount'])) {
                return $value;
            }
        }
        return null;
    }

    public function createShipment(array $request): array
    {
        $destinationCountry = normalizeCountryIso2(
            $request['destination_country']
                ?? ($request['destination']['country_code'] ?? 'IN')
        );
        if (isInternationalShipmentCountry($destinationCountry)) {
            return [
                'success' => false,
                'message' => 'Delhivery adapter is domestic only (IN → IN).',
            ];
        }

        $accounts = $this->accountModel->listActiveAccountsByPartnerCode('delhivery');
        if (!$accounts) {
            return [
                'success' => false,
                'message' => 'No active Delhivery courier account configured.',
            ];
        }

        $accountId = (int) ($request['partner_account_id'] ?? 0);
        $accountRow = $this->pickAccount($accounts, $accountId);
        $accountId = (int) ($accountRow['id'] ?? 0);
        $credentials = $this->accountModel->getCredentialsJson($accountId);

        $apiKey = trim((string) ($credentials['api_key'] ?? $credentials['token'] ?? $credentials['api_token'] ?? ''));
        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => 'Delhivery api_token is missing in Courier accounts.',
            ];
        }

        $orderNumber = trim((string) ($request['order_number'] ?? ''));
        $weightKg = (float) ($request['weight'] ?? 0);
        if ($orderNumber === '' || $weightKg <= 0) {
            return [
                'success' => false,
                'message' => 'Order number and weight are required for Delhivery shipment creation.',
            ];
        }

        $destination = is_array($request['destination'] ?? null) ? $request['destination'] : [];
        $address = is_array($request['address'] ?? null) ? $request['address'] : [];
        $consigneeName = trim((string) (
            $destination['name']
            ?? (($address['shipping_first_name'] ?? '') . ' ' . ($address['shipping_last_name'] ?? ''))
            ?? (($address['first_name'] ?? '') . ' ' . ($address['last_name'] ?? ''))
        ));
        $consigneeName = preg_replace('/\s+/', ' ', $consigneeName);
        if ($consigneeName === '') {
            $consigneeName = 'Customer';
        }

        $consigneePhone = preg_replace('/\D/', '', (string) (
            $destination['phone']
            ?? $address['shipping_mobile']
            ?? $address['mobile']
            ?? ''
        ));
        if (strlen($consigneePhone) > 10) {
            $consigneePhone = substr($consigneePhone, -10);
        }
        if ($consigneePhone === '') {
            return ['success' => false, 'message' => 'Consignee phone is required for Delhivery order creation.'];
        }

        $consigneePin = trim((string) ($destination['postcode'] ?? $address['shipping_zipcode'] ?? $address['zipcode'] ?? ''));
        $consigneeAdd = trim((string) ($destination['line1'] ?? $address['shipping_address_line1'] ?? $address['address_line1'] ?? ''));
        if ($consigneePin === '' || $consigneeAdd === '') {
            return ['success' => false, 'message' => 'Consignee pin and address are required for Delhivery.'];
        }

        $consigneeCity = trim((string) ($destination['city'] ?? $address['shipping_city'] ?? $address['city'] ?? ''));
        $consigneeState = trim((string) ($destination['state'] ?? $address['shipping_state'] ?? $address['state'] ?? ''));

        $shipper = is_array($credentials['shipper'] ?? null) ? $credentials['shipper'] : [];
        $sellerName = trim((string) ($credentials['registered_name'] ?? $shipper['company_name'] ?? 'Seller'));
        $sellerAdd = trim((string) ($shipper['line1'] ?? ''));
        $sellerGst = trim((string) ($credentials['seller_gst_tin'] ?? $credentials['gstin'] ?? $address['gstin'] ?? ''));

        $items = is_array($request['items'] ?? null) ? $request['items'] : [];
        $productsDesc = trim((string) ($request['description'] ?? $request['products_desc'] ?? ''));
        $hsnCode = '';
        $totalAmount = 0.0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            if ($productsDesc === '') {
                $productsDesc = trim((string) ($item['name'] ?? ''));
            }
            if ($hsnCode === '' && !empty($item['hsn'])) {
                $hsnCode = substr(preg_replace('/\D/', '', (string) $item['hsn']), 0, 8);
            }
            $qty = (int) ($item['quantity'] ?? $item['units'] ?? 1);
            $price = (float) ($item['unit_price'] ?? $item['selling_price'] ?? 0);
            $totalAmount += max(0, $qty) * max(0.0, $price);
        }
        if ($productsDesc === '') {
            $productsDesc = 'Goods';
        }
        if ($totalAmount <= 0) {
            $totalAmount = (float) ($request['invoice']['total_amount'] ?? $request['sub_total'] ?? 0.01);
        }
        if ($totalAmount <= 0) {
            $totalAmount = 0.01;
        }
        if ($hsnCode === '') {
            $hsnCode = '9999';
        }
        if ($sellerGst === '') {
            $sellerGst = 'NA';
        }

        $isCod = !empty($request['cod']);
        $paymentMode = $isCod ? 'COD' : 'Pre-paid';
        $codAmount = $isCod ? round((float) ($request['cod_amount'] ?? $totalAmount), 2) : 0;

        $pickupName = $this->resolveDelhiveryPickupName($request, $credentials);
        if ($pickupName === '') {
            $accountLabel = trim((string) ($accountRow['account_code'] ?? ''));
            if ($accountLabel === '') {
                $accountLabel = 'id ' . $accountId;
            } else {
                $accountLabel = $accountLabel . ' (id ' . $accountId . ')';
            }
            $requestedPickup = trim((string) ($request['pickup_location'] ?? ''));
            $hint = ' Edit Courier accounts → Delhivery account "' . $accountLabel . '" → Credentials JSON'
                . ' and set "pickup_location_name" to the exact facility name from Delhivery One → Settings → Pickup Locations (case-sensitive).';
            if ($requestedPickup !== '') {
                $hint .= ' Shiprocket pickup "' . $requestedPickup . '" cannot be used directly; add pickup_location_aliases or set pickup_location_name.';
            }
            return [
                'success' => false,
                'message' => 'Delhivery pickup_location_name is missing in Courier accounts.' . $hint,
            ];
        }

        $requestedPickup = trim((string)($request['pickup_location'] ?? ''));
        $serviceCode = $this->resolveServiceCode($request);
        $shippingMode = $serviceCode === 'EXPRESS' ? 'Express' : 'Surface';

        $uniqueOrderId = trim((string) ($request['delhivery_order_id'] ?? ($orderNumber . '_box_' . (int) ($request['box_no'] ?? 0) . '_' . time())));

        $shipmentRow = [
            'name' => $this->sanitizeDelhiveryText($consigneeName),
            'add' => $this->sanitizeDelhiveryText($consigneeAdd),
            'pin' => $consigneePin,
            'city' => $this->sanitizeDelhiveryText($consigneeCity),
            'state' => $this->sanitizeDelhiveryText($consigneeState),
            'country' => 'India',
            'phone' => $consigneePhone,
            'order' => $uniqueOrderId,
            'payment_mode' => $paymentMode,
            'cod_amount' => $codAmount,
            'weight' => round(max(0.01, $weightKg), 3),
            'quantity' => max(1, (int) ($request['quantity'] ?? 1)),
            'products_desc' => $this->sanitizeDelhiveryText($productsDesc),
            'seller_name' => $this->sanitizeDelhiveryText($sellerName),
            'seller_add' => $this->sanitizeDelhiveryText($sellerAdd !== '' ? $sellerAdd : $sellerName),
            'seller_gst_tin' => $sellerGst,
            'hsn_code' => $hsnCode,
            'total_amount' => round($totalAmount, 2),
            'shipping_mode' => $shippingMode,
        ];

        $lengthCm = (float) ($request['length_cm'] ?? 0);
        $widthCm = (float) ($request['width_cm'] ?? 0);
        $heightCm = (float) ($request['height_cm'] ?? 0);
        if ($lengthCm > 0) {
            $shipmentRow['shipment_length'] = round($lengthCm, 1);
        }
        if ($widthCm > 0) {
            $shipmentRow['shipment_width'] = round($widthCm, 1);
        }
        if ($heightCm > 0) {
            $shipmentRow['shipment_height'] = round($heightCm, 1);
        }

        $invoiceRef = trim((string) ($request['invoice']['invoice_number'] ?? ''));
        if ($invoiceRef !== '') {
            $shipmentRow['invoice_reference'] = $invoiceRef;
        }

        $createPayload = [
            'pickup_location' => [
                'name' => $pickupName,
            ],
            'shipments' => [$shipmentRow],
        ];

        $urlInfo = resolveCourierCredentialUrls($credentials);
        $environment = (string) ($urlInfo['environment'] ?? 'sandbox');
        $baseUrlOverride = trim((string) ($urlInfo['api_base_url'] ?? ''));
        $createApiPath = trim((string) ($credentials['order_create_api_path'] ?? $credentials['create_api_path'] ?? ''));
        $packingSlipPath = trim((string) ($credentials['packing_slip_api_path'] ?? $credentials['label_api_path'] ?? ''));

        $service = new DelhiveryService($apiKey, $environment, $baseUrlOverride);
        $createResp = $service->createPackageOrder($createPayload, $createApiPath);

        if ($this->shipmentModel) {
            $this->shipmentModel->logApiCall(
                'delhivery',
                'create_shipment',
                $accountId,
                $orderNumber,
                $createPayload,
                $createResp,
                !empty($createResp['success']),
                !empty($createResp['success']) ? null : (string) ($createResp['message'] ?? 'Create failed'),
                isset($createResp['http_code']) ? (int) $createResp['http_code'] : null
            );
        }

        if (empty($createResp['success'])) {
            $message = (string) ($createResp['message'] ?? 'Delhivery order creation failed.');
            if (stripos($message, 'ClientWarehouse') !== false || stripos($message, 'warehouse') !== false) {
                $message .= ' Pickup name sent: "' . $pickupName . '".'
                    . ' Update Courier accounts → Delhivery → pickup_location_name to match Delhivery One pickup location exactly'
                    . ($requestedPickup !== '' && strcasecmp($requestedPickup, $pickupName) !== 0
                        ? ' (UI had Shiprocket pickup "' . $requestedPickup . '", which Delhivery does not recognize).'
                        : '.');
            }
            return [
                'success' => false,
                'message' => $message,
                'debug' => [
                    'http_code' => $createResp['http_code'] ?? null,
                    'response' => $createResp['data'] ?? null,
                    'pickup_location_name' => $pickupName,
                ],
            ];
        }

        $waybill = $this->extractWaybillFromCreateResponse($createResp['data'] ?? null);
        if ($waybill === '') {
            return [
                'success' => false,
                'message' => 'Delhivery order created but no waybill returned.',
                'debug' => ['response' => $createResp['data'] ?? null],
            ];
        }

        $packingResp = $service->getPackingSlip($waybill, $packingSlipPath);
        if ($this->shipmentModel) {
            $this->shipmentModel->logApiCall(
                'delhivery',
                'generate_label',
                $accountId,
                $orderNumber,
                ['waybill' => $waybill],
                $packingResp,
                !empty($packingResp['success']),
                !empty($packingResp['success']) ? null : (string) ($packingResp['message'] ?? 'Label fetch failed'),
                isset($packingResp['http_code']) ? (int) $packingResp['http_code'] : null
            );
        }

        $dispatchId = (int) ($request['dispatch_id'] ?? 0);
        $labelUrl = $dispatchId > 0
            ? base_url('index.php?page=dispatch&action=delhivery_label&dispatch_id=' . $dispatchId)
            : base_url('index.php?page=dispatch&action=delhivery_label&awb=' . rawurlencode($waybill));

        $trackingUrl = 'https://www.delhivery.com/track/package/' . rawurlencode($waybill);

        return [
            'success' => true,
            'message' => 'Delhivery shipment created.',
            'awb' => $waybill,
            'awb_code' => $waybill,
            'partner_shipment_id' => $waybill,
            'shipment_id' => $waybill,
            'order_id' => $uniqueOrderId,
            'label_url' => $labelUrl,
            'tracking_url' => $trackingUrl,
            'status' => 'created',
            'metadata' => [
                'packing_slip' => $packingResp['data'] ?? null,
                'create_response' => $createResp['data'] ?? null,
                'service_code' => $serviceCode,
                'shipping_mode' => $shippingMode,
            ],
            'debug' => [
                'partner' => 'delhivery',
                'account_id' => $accountId,
                'environment' => $environment,
                'waybill' => $waybill,
            ],
        ];
    }

    /** @param array<string, mixed> $request */
    private function resolveServiceCode(array $request): string
    {
        $serviceCode = strtoupper(trim((string) ($request['service_code'] ?? $request['product_type'] ?? '')));
        if (in_array($serviceCode, ['SURFACE', 'EXPRESS'], true)) {
            return $serviceCode;
        }

        $courierId = (string) ($request['courier_id'] ?? $request['selected_courier_id'] ?? '');
        if (preg_match('/_EXPRESS$/i', $courierId)) {
            return 'EXPRESS';
        }
        if (preg_match('/_SURFACE$/i', $courierId)) {
            return 'SURFACE';
        }

        return 'SURFACE';
    }

    /**
     * Delhivery warehouse name — never use Shiprocket pickup labels (e.g. "Head Off") directly.
     *
     * @param array<string, mixed> $request
     * @param array<string, mixed> $credentials
     */
    private function resolveDelhiveryPickupName(array $request, array $credentials): string
    {
        foreach ([
            'pickup_location_name',
            'warehouse_name',
            'facility_name',
            'registered_pickup_name',
        ] as $key) {
            $value = trim((string) ($credentials[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $aliases = is_array($credentials['pickup_location_aliases'] ?? null)
            ? $credentials['pickup_location_aliases']
            : [];
        $requested = trim((string) ($request['pickup_location'] ?? ''));
        if ($requested !== '') {
            if (!empty($aliases[$requested])) {
                return trim((string) $aliases[$requested]);
            }
            foreach ($aliases as $alias => $facilityName) {
                if (strcasecmp(trim((string) $alias), $requested) === 0) {
                    return trim((string) $facilityName);
                }
            }
        }

        return '';
    }

    /** @param mixed $data */
    private function extractWaybillFromCreateResponse($data): string
    {
        if (!is_array($data)) {
            return '';
        }

        if (!empty($data['packages']) && is_array($data['packages'])) {
            foreach ($data['packages'] as $pkg) {
                if (!is_array($pkg)) {
                    continue;
                }
                foreach (['waybill', 'wbn', 'awb'] as $key) {
                    $val = trim((string) ($pkg[$key] ?? ''));
                    if ($val !== '') {
                        return $val;
                    }
                }
            }
        }

        foreach (['waybill', 'wbn', 'awb'] as $key) {
            $val = trim((string) ($data[$key] ?? ''));
            if ($val !== '') {
                return $val;
            }
        }

        return '';
    }

    private function sanitizeDelhiveryText(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return $value;
        }
        $value = str_replace(['&', '#', '%', ';', '\\'], [' and ', '', '', ',', '/'], $value);
        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    /** @param list<array<string, mixed>> $accounts */
    private function pickAccount(array $accounts, int $requestedAccountId): array
    {
        if ($requestedAccountId > 0) {
            foreach ($accounts as $account) {
                if ((int) ($account['id'] ?? 0) === $requestedAccountId) {
                    return $account;
                }
            }
        }
        return $accounts[0];
    }
}
