<?php



require_once __DIR__ . '/../Contracts/CourierAdapterInterface.php';

require_once __DIR__ . '/../country_codes.php';

require_once __DIR__ . '/../Support/CourierUiFormat.php';

require_once __DIR__ . '/../../../models/courier/CourierAccount.php';

require_once __DIR__ . '/../../../models/courier/CourierShipment.php';

require_once __DIR__ . '/../../../bluedart_service.php';



/**

 * Blue Dart domestic adapter — rates via Location Finder + optional Transit Time.

 *

 * Bulk dispatch (IN → IN). Credentials in courier_partner_accounts.credentials_json (partner_code = bluedart).

 */

class BlueDartAdapter implements CourierAdapterInterface

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

        return 'bluedart';

    }



    public function getRates(array $request): array

    {

        $destinationCountry = normalizeCountryIso2(

            $request['destination_country']

                ?? ($request['destination']['country_code'] ?? 'IN'),

            $GLOBALS['conn'] ?? null

        );

        if (isInternationalShipmentCountry($destinationCountry, $GLOBALS['conn'] ?? null)) {

            return [

                'success' => false,

                'message' => 'Blue Dart adapter is domestic only (IN → IN).',

            ];

        }



        $accounts = $this->accountModel->listActiveAccountsByPartnerCode('bluedart');

        if (!$accounts) {

            return [

                'success' => false,

                'message' => 'No active Blue Dart account configured. Add one under Courier accounts.',

            ];

        }



        $accountId = (int) ($request['partner_account_id'] ?? 0);

        $accountRow = $this->pickAccount($accounts, $accountId);

        $accountId = (int) ($accountRow['id'] ?? 0);

        $credentials = $this->accountModel->getCredentialsJson($accountId);



        $originPin = trim((string) ($request['pickup']['postcode'] ?? $request['pickup_postcode'] ?? ''));

        if ($originPin === '') {

            $originPin = trim((string) (($credentials['shipper']['postcode'] ?? null) ?? ($credentials['customer_pincode'] ?? '')));

        }

        $destPin = trim((string) ($request['destination']['postcode'] ?? $request['delivery_postcode'] ?? ''));

        if ($originPin === '' || $destPin === '') {

            return [

                'success' => false,

                'message' => 'Origin and destination pincodes are required for Blue Dart rates.',

            ];

        }



        $actualKg = (float) ($request['weight'] ?? 0);

        $billableKg = (float) ($request['chargeable_weight_kg'] ?? $actualKg);

        if ($billableKg <= 0 && $actualKg > 0) {

            $billableKg = $actualKg;

        }

        if ($billableKg <= 0) {

            return [

                'success' => false,

                'message' => 'Box weight is required for Blue Dart rates.',

            ];

        }



        $service = new BlueDartService($credentials);

        $result = $service->estimateRates([

            'origin_pin' => $originPin,

            'dest_pin' => $destPin,

            'weight_kg' => $actualKg,

            'billable_weight_kg' => $billableKg,

            'cod' => !empty($request['cod']),

        ]);



        if ($this->shipmentModel) {

            $this->shipmentModel->logApiCall(

                'bluedart',

                'get_rates',

                $accountId,

                (string) ($request['order_number'] ?? ''),

                $request,

                $result['debug'] ?? $result,

                !empty($result['success']),

                !empty($result['success']) ? 'Blue Dart rates fetched' : ($result['error'] ?? 'Blue Dart rates failed')

            );

        }



        if (empty($result['success'])) {

            return [

                'success' => false,

                'message' => (string) ($result['error'] ?? 'Blue Dart API returned no rates.'),

                'debug' => [

                    'partner' => 'bluedart',

                    'account_id' => $accountId,

                    'origin_pin' => $originPin,

                    'dest_pin' => $destPin,

                    'billable_weight_kg' => $billableKg,

                    'responses' => $result['debug'] ?? null,

                ],

            ];

        }



        $quotes = [];

        foreach (($result['quotes'] ?? []) as $row) {

            if (!is_array($row)) {

                continue;

            }

            $productCode = (string) ($row['product_code'] ?? '');

            $subProductCode = (string) ($row['sub_product_code'] ?? '');

            $serviceCode = $productCode . '_' . $subProductCode;

            $price = $row['price'] ?? null;

            $quotes[] = [

                'id' => 'bluedart_' . $accountId . '_' . $serviceCode,

                'name' => (string) ($row['label'] ?? ('Blue Dart ' . $serviceCode)),

                'price' => $price !== null ? (float) $price : null,

                'currency' => (string) ($row['currency'] ?? 'INR'),

                'etd' => (string) ($row['etd'] ?? 'N/A'),

                'rating' => 0,

                'partner_code' => 'bluedart',

                'partner_account_id' => $accountId,

                'product_type' => $serviceCode,

                'service_code' => $serviceCode,

                'metadata' => [

                    'product_code' => $productCode,

                    'sub_product_code' => $subProductCode,

                    'pack_type' => $row['pack_type'] ?? '',

                    'feature' => $row['feature'] ?? '',

                    'origin_pin' => $originPin,

                    'dest_pin' => $destPin,

                    'actual_weight_kg' => $actualKg,

                    'billable_weight_kg' => $billableKg,

                    'serviceable' => !empty($row['serviceable']),

                    'etd_source' => (string) ($row['etd_source'] ?? (($row['etd'] ?? 'N/A') !== 'N/A' ? 'bluedart' : '')),

                ],

            ];

        }



        if ($quotes === []) {

            return [

                'success' => false,

                'message' => 'Blue Dart returned no rate quotes for this shipment.',

                'debug' => $result['debug'] ?? null,

            ];

        }



        return [

            'success' => true,

            'provider' => 'bluedart',

            'couriers' => CourierUiFormat::formatQuotes($quotes),

            'debug' => [

                'partner' => 'bluedart',

                'account_id' => $accountId,

                'rate_request' => [

                    'origin_pin' => $originPin,

                    'dest_pin' => $destPin,

                    'billable_weight_kg' => $billableKg,

                    'actual_weight_kg' => $actualKg,

                ],

                'responses' => $result['debug'] ?? null,

            ],

        ];

    }



    public function createShipment(array $request): array
    {
        $destinationCountry = normalizeCountryIso2(
            $request['destination_country']
                ?? ($request['destination']['country_code'] ?? 'IN'),
            $GLOBALS['conn'] ?? null
        );
        if (isInternationalShipmentCountry($destinationCountry, $GLOBALS['conn'] ?? null)) {
            return [
                'success' => false,
                'message' => 'Blue Dart adapter is domestic only (IN → IN).',
            ];
        }

        $accounts = $this->accountModel->listActiveAccountsByPartnerCode('bluedart');
        if (!$accounts) {
            return [
                'success' => false,
                'message' => 'No active Blue Dart courier account configured.',
            ];
        }

        $accountId = (int) ($request['partner_account_id'] ?? 0);
        $accountRow = $this->pickAccount($accounts, $accountId);
        $accountId = (int) ($accountRow['id'] ?? 0);
        $credentials = $this->accountModel->getCredentialsJson($accountId);

        $orderNumber = trim((string) ($request['order_number'] ?? ''));
        $weightKg = (float) ($request['weight'] ?? 0);
        if ($orderNumber === '' || $weightKg <= 0) {
            return [
                'success' => false,
                'message' => 'Order number and weight are required for Blue Dart shipment creation.',
            ];
        }

        $destination = is_array($request['destination'] ?? null) ? $request['destination'] : [];
        $address = is_array($request['address'] ?? null) ? $request['address'] : [];
        $shipper = is_array($credentials['shipper'] ?? null) ? $credentials['shipper'] : [];

        $consigneeName = trim((string) (
            $destination['name']
            ?? (($address['shipping_first_name'] ?? '') . ' ' . ($address['shipping_last_name'] ?? ''))
            ?? (($address['first_name'] ?? '') . ' ' . ($address['last_name'] ?? ''))
        ));
        $consigneeName = preg_replace('/\s+/', ' ', $consigneeName);
        if ($consigneeName === '') {
            $consigneeName = 'Customer';
        }

        $consigneeMobile = preg_replace('/\D/', '', (string) (
            $destination['phone']
            ?? $address['shipping_mobile']
            ?? $address['mobile']
            ?? ''
        ));
        if (strlen($consigneeMobile) > 10) {
            $consigneeMobile = substr($consigneeMobile, -10);
        }
        if ($consigneeMobile === '') {
            return ['success' => false, 'message' => 'Consignee mobile is required for Blue Dart waybill.'];
        }

        $consigneePin = preg_replace('/\D/', '', (string) (
            $destination['postcode']
            ?? $address['shipping_zipcode']
            ?? $address['zipcode']
            ?? ''
        ));
        $consigneeAddress = trim((string) (
            $destination['line1']
            ?? $address['shipping_address_line1']
            ?? $address['address_line1']
            ?? ''
        ));
        if ($consigneePin === '' || $consigneeAddress === '') {
            return ['success' => false, 'message' => 'Consignee pin and address are required for Blue Dart.'];
        }

        $shipperPin = preg_replace('/\D/', '', (string) (
            $shipper['postcode']
            ?? $credentials['customer_pincode']
            ?? ''
        ));
        $shipperAddress = trim((string) ($shipper['line1'] ?? ''));
        $shipperName = trim((string) ($credentials['registered_name'] ?? $shipper['company_name'] ?? $shipper['full_name'] ?? 'Seller'));
        $shipperMobile = preg_replace('/\D/', '', (string) ($shipper['phone'] ?? ''));
        if ($shipperPin === '' || $shipperAddress === '') {
            return [
                'success' => false,
                'message' => 'Shipper address and pincode are required in Blue Dart Courier account credentials (shipper.postcode, shipper.line1).',
            ];
        }

        $isCod = !empty($request['cod']);
        $authStatus = bluedartDescribeGatewayAuthStatus($credentials);
        if (!bluedartCanCreateWaybill($credentials)) {
            $accountLabel = trim((string) ($accountRow['account_code'] ?? ''));
            if ($accountLabel === '') {
                $accountLabel = 'id ' . $accountId;
            }
            $message = bluedartSanitizeErrorMessage(
                (string) ($authStatus['message'] ?? 'Blue Dart API authentication is not configured.')
            );
            if (!empty($authStatus['hints'])) {
                $message .= ' ' . implode(' ', array_map('bluedartSanitizeErrorMessage', $authStatus['hints']));
            }
            return [
                'success' => false,
                'message' => 'Blue Dart account "' . $accountLabel . '": ' . $message,
                'debug' => ['auth_hints' => $authStatus['hints'] ?? []],
            ];
        }

        $service = new BlueDartService($credentials);
        $shipperCodes = bluedartResolveWaybillCredentials($service, $credentials, $shipperPin, $isCod);
        if (!empty($shipperCodes['errors'])) {
            $accountLabel = trim((string) ($accountRow['account_code'] ?? ''));
            if ($accountLabel === '') {
                $accountLabel = 'id ' . $accountId;
            }
            return [
                'success' => false,
                'message' => 'Blue Dart Courier account "' . $accountLabel . '" is missing: '
                    . implode('; ', $shipperCodes['errors'])
                    . '. Edit Courier accounts → Blue Dart → Credentials JSON and add these fields (see partner template reference).',
                'debug' => [
                    'missing' => $shipperCodes['errors'],
                    'shipper_pincode' => $shipperPin,
                    'origin_area_source' => $shipperCodes['origin_area_source'] ?? '',
                ],
            ];
        }

        $customerCode = (string) $shipperCodes['customer_code'];
        $originArea = (string) $shipperCodes['origin_area'];

        $items = is_array($request['items'] ?? null) ? $request['items'] : [];
        $declaredValue = 0.0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $qty = (int) ($item['quantity'] ?? $item['units'] ?? 1);
            $price = (float) ($item['unit_price'] ?? $item['selling_price'] ?? 0);
            $declaredValue += max(0, $qty) * max(0.0, $price);
        }
        if ($declaredValue <= 0) {
            $declaredValue = (float) ($request['invoice']['total_amount'] ?? $request['sub_total'] ?? 0.01);
        }
        if ($declaredValue <= 0) {
            $declaredValue = 0.01;
        }

        $codAmount = $isCod ? round((float) ($request['cod_amount'] ?? $declaredValue), 2) : 0.0;
        $productCodes = $this->resolveProductCodes($request, $isCod);

        $boxNo = (int) ($request['box_no'] ?? 0);
        $creditReference = strtoupper(preg_replace('/[^A-Z0-9]/', '', $orderNumber));
        if ($boxNo > 0) {
            $creditReference .= 'B' . $boxNo;
        }
        $creditReference .= substr((string) time(), -4);

        $shipmentPayload = $service->buildWaybillShipmentPayload([
            'product_code' => $productCodes['product_code'],
            'sub_product_code' => $productCodes['sub_product_code'],
            'pack_type' => $productCodes['pack_type'],
            'weight_kg' => $weightKg,
            'length_cm' => (float) ($request['length_cm'] ?? 1),
            'width_cm' => (float) ($request['width_cm'] ?? 1),
            'height_cm' => (float) ($request['height_cm'] ?? 1),
            'cod' => $isCod,
            'cod_amount' => $codAmount,
            'declared_value' => $declaredValue,
            'credit_reference' => $creditReference,
            'invoice_number' => (string) ($request['invoice']['invoice_number'] ?? ''),
            'consignee_name' => $consigneeName,
            'consignee_address' => $consigneeAddress,
            'consignee_pincode' => $consigneePin,
            'consignee_email' => (string) ($address['shipping_email'] ?? $address['email'] ?? ''),
            'consignee_mobile' => $consigneeMobile,
            'shipper_name' => $shipperName,
            'shipper_address' => $shipperAddress,
            'shipper_pincode' => $shipperPin,
            'shipper_mobile' => $shipperMobile,
            'shipper_email' => (string) ($shipper['email'] ?? ''),
            'return_address' => $shipperAddress,
            'return_contact' => $shipperName,
            'return_mobile' => $shipperMobile,
            'return_pincode' => $shipperPin,
            'customer_code' => $customerCode,
            'origin_area' => $originArea,
            'sender' => $shipperName,
            'register_pickup' => !empty($credentials['register_pickup_on_waybill']),
        ]);

        $createResp = $service->generateWaybill($shipmentPayload);

        if ($this->shipmentModel) {
            $this->shipmentModel->logApiCall(
                'bluedart',
                'create_shipment',
                $accountId,
                $orderNumber,
                $shipmentPayload,
                $createResp,
                !empty($createResp['success']),
                !empty($createResp['success']) ? null : (string) ($createResp['error'] ?? 'Create failed'),
                isset($createResp['http_code']) ? (int) $createResp['http_code'] : null
            );
        }

        if (empty($createResp['success'])) {
            $error = bluedartSanitizeErrorMessage(
                trim((string) ($createResp['error'] ?? 'Blue Dart waybill generation failed.'))
            );
            $httpCode = (int) ($createResp['http_code'] ?? 0);
            if (
                $httpCode === 401
                && !bluedartUsesLegacyApi($credentials)
                && !str_contains(strtolower($error), 'developer.dhl.com')
            ) {
                $error .= ' ' . bluedartDhlPortalSetupHint();
            }

            $debug = [
                'http_code' => $httpCode > 0 ? $httpCode : null,
                'endpoint' => $createResp['endpoint'] ?? null,
                'soap_variant' => $createResp['soap_variant'] ?? null,
                'product_code' => $productCodes['product_code'],
                'sub_product_code' => $productCodes['sub_product_code'],
            ];
            if (!empty($createResp['request_xml'])) {
                $debug['request'] = (string) $createResp['request_xml'];
            }
            if (array_key_exists('response_raw', $createResp)) {
                $debug['response'] = (string) $createResp['response_raw'];
            }

            return [
                'success' => false,
                'message' => $error,
                'debug' => array_filter(
                    $debug,
                    static function ($value, $key) {
                        if ($key === 'response') {
                            return true;
                        }

                        return $value !== null && $value !== '';
                    },
                    ARRAY_FILTER_USE_BOTH
                ),
            ];
        }

        $awb = (string) ($createResp['awb'] ?? '');
        $pdfBinary = (string) ($createResp['pdf_binary'] ?? '');
        $labelPath = $service->saveWaybillLabelPdf($awb, $pdfBinary);
        if ($labelPath === null) {
            return [
                'success' => false,
                'message' => 'Blue Dart AWB ' . $awb . ' created but label PDF could not be saved.',
                'awb' => $awb,
            ];
        }

        $dispatchId = (int) ($request['dispatch_id'] ?? 0);
        if ($dispatchId > 0) {
            $labelUrl = base_url('index.php?page=dispatch&action=bluedart_label&dispatch_id=' . $dispatchId);
        } else {
            $labelUrl = base_url('index.php?page=dispatch&action=bluedart_label&awb=' . rawurlencode($awb));
        }

        $trackingUrl = 'https://www.bluedart.com/web/guest/trackdartresult?trackFor=0&trackNo=' . rawurlencode($awb);

        return [
            'success' => true,
            'message' => 'Blue Dart shipment created.',
            'awb' => $awb,
            'awb_code' => $awb,
            'partner_shipment_id' => $awb,
            'shipment_id' => $awb,
            'order_id' => $creditReference,
            'label_url' => $labelUrl,
            'tracking_url' => $trackingUrl,
            'status' => 'created',
            'metadata' => [
                'awb' => $awb,
                'label_file' => basename($labelPath),
                'product_code' => $productCodes['product_code'],
                'sub_product_code' => $productCodes['sub_product_code'],
                'service_code' => $productCodes['product_code'] . '_' . $productCodes['sub_product_code'],
                'destination_area' => (string) ($createResp['destination_area'] ?? ''),
                'customer_code' => $customerCode,
                'origin_area' => $originArea,
                'origin_area_source' => (string) ($shipperCodes['origin_area_source'] ?? ''),
            ],
            'debug' => [
                'partner' => 'bluedart',
                'account_id' => $accountId,
                'awb' => $awb,
            ],
        ];
    }

    /** @return array{product_code:string,sub_product_code:string,pack_type:string} */
    private function resolveProductCodes(array $request, bool $isCod): array
    {
        $metadata = is_array($request['metadata'] ?? null) ? $request['metadata'] : [];
        $productCode = strtoupper(trim((string) ($metadata['product_code'] ?? '')));
        $subProductCode = strtoupper(trim((string) ($metadata['sub_product_code'] ?? '')));
        $packType = strtoupper(trim((string) ($metadata['pack_type'] ?? 'L')));

        $productType = trim((string) ($request['product_type'] ?? ''));
        $courierId = trim((string) ($request['courier_id'] ?? ''));
        if ($productType === '' && preg_match('/bluedart_\d+_(.+)$/i', $courierId, $m)) {
            $productType = trim((string) ($m[1] ?? ''));
        }
        if ($productType !== '' && str_contains($productType, '_')) {
            [$pc, $spc] = explode('_', $productType, 2);
            if ($productCode === '') {
                $productCode = strtoupper(trim($pc));
            }
            if ($subProductCode === '') {
                $subProductCode = strtoupper(trim($spc));
            }
        }

        if ($productCode === '') {
            $productCode = 'A';
        }
        if ($subProductCode === '') {
            $subProductCode = $isCod ? 'C' : 'P';
        }
        if ($packType === '') {
            $packType = 'L';
        }

        return [
            'product_code' => $productCode,
            'sub_product_code' => $subProductCode,
            'pack_type' => $packType,
        ];
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

