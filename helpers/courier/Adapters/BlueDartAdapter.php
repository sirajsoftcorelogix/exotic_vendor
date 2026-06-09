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

                    'price_source' => $row['price_source'] ?? '',

                    'price_configured' => $price !== null,

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

        return [

            'success' => false,

            'message' => 'Blue Dart createShipment not implemented yet.',

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

