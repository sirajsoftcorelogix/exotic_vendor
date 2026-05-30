<?php

require_once __DIR__ . '/helpers/courier/credential_urls.php';
require_once __DIR__ . '/helpers/courier/AramexShipmentBuilder.php';

class AramexService
{
    private $config;
    private $client;
    private array $fullConfig;

    public function __construct(?array $config = null)
    {
        $this->fullConfig = is_array($config) ? $config : [];
        $defaults = [
            'username' => '',
            'password' => '',
            'account_number' => '',
            'account_pin' => '',
            'entity' => 'DEL',
            'country_code' => 'IN',
            'version' => 'v1.0',
            'api_base_url' => 'https://ws.aramex.net/ShippingAPI.V2',
            'wsdl' => 'https://ws.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc?wsdl',
            'tracking_wsdl' => 'https://ws.aramex.net/ShippingAPI.V2/Tracking/Service_1_0.svc?wsdl',
        ];

        if (is_array($config)) {
            $urls = resolveCourierCredentialUrls($config);
            $this->config = array_merge($defaults, [
                'username' => (string) ($config['username'] ?? $defaults['username']),
                'password' => (string) ($config['password'] ?? $defaults['password']),
                'account_number' => (string) ($config['account_number'] ?? $defaults['account_number']),
                'account_pin' => (string) ($config['account_pin'] ?? $defaults['account_pin']),
                'entity' => (string) ($config['account_entity'] ?? $config['entity'] ?? $defaults['entity']),
                'country_code' => (string) ($config['account_country_code'] ?? $config['country_code'] ?? $defaults['country_code']),
                'version' => (string) ($config['version'] ?? $defaults['version']),
                'api_base_url' => $urls['api_base_url'] !== '' ? $urls['api_base_url'] : $defaults['api_base_url'],
                'wsdl' => $urls['shipping_wsdl'] !== '' ? $urls['shipping_wsdl'] : $defaults['wsdl'],
                'tracking_wsdl' => $urls['tracking_wsdl'] !== '' ? $urls['tracking_wsdl'] : $defaults['tracking_wsdl'],
            ]);
        } else {
            $this->config = $defaults;
        }

        $this->client = new SoapClient($this->config['wsdl'], [
            'trace' => 1,
            'exceptions' => true
        ]);
    }

    private function clientInfo()
    {
        return AramexShipmentBuilder::clientInfoFromCredentials(array_merge($this->fullConfig, [
            'username' => $this->config['username'],
            'password' => $this->config['password'],
            'version' => $this->config['version'] ?? 'v1.0',
            'account_number' => $this->config['account_number'],
            'account_pin' => $this->config['account_pin'],
            'account_entity' => $this->config['entity'],
            'account_country_code' => $this->config['country_code'],
        ]));
    }

    /* ===================== CREATE SHIPMENT ===================== */
    public function createShipment($shipmentData)
    {
        try {
            $params = [
                'ClientInfo' => $this->clientInfo(),
                'Shipments' => [is_array($shipmentData) ? $shipmentData : $shipmentData],
            ];

            $response = $this->client->CreateShipments($params);

            return $this->formatResponse($response);

        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Full CreateShipments call with LabelInfo (ReportID 9729 / URL label).
     *
     * @param array<string, mixed> $context Passed to AramexShipmentBuilder::buildCreateShipmentsRequest
     */
    public function createInternationalShipment(array $context): array
    {
        try {
            $params = AramexShipmentBuilder::buildCreateShipmentsRequest($this->fullConfig, $context);
            $response = $this->client->CreateShipments($params);
            return $this->formatResponse($response);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /* ===================== CALCULATE RATE ===================== */
    public function calculateRate($origin, $destination, $weight, ?array $options = null)
    {
        $options = $options ?? [];
        $productGroup = (string) ($options['product_group'] ?? $this->fullConfig['default_product_group'] ?? 'EXP');
        $productType = (string) ($options['product_type'] ?? $this->fullConfig['default_product_type'] ?? '');
        if ($productType === '') {
            $productType = 'PPX';
        }

        try {
            $params = [
                'ClientInfo' => $this->clientInfo(),
                'OriginAddress' => $origin,
                'DestinationAddress' => $destination,
                'ShipmentDetails' => [
                    'ActualWeight' => [
                        'Value' => $weight,
                        'Unit' => 'KG'
                    ],
                    'ProductGroup' => $productGroup,
                    'ProductType' => $productType,
                    'PaymentType' => 'P'
                ]
            ];

            $response = $this->client->CalculateRate($params);

            return $this->formatResponse($response);

        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /* ===================== TRACK SHIPMENT ===================== */
    public function trackShipment($awb)
    {
        try {
            $trackingClient = new SoapClient($this->config['tracking_wsdl'], [
                'trace' => 1,
                'exceptions' => true
            ]);

            $params = [
                'ClientInfo' => $this->clientInfo(),
                'Shipments' => [$awb]
            ];

            $response = $trackingClient->TrackShipments($params);

            return $this->formatResponse($response);

        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /* ===================== CREATE PICKUP ===================== */
    public function createPickup($pickupData)
    {
        try {
            $params = [
                'ClientInfo' => $this->clientInfo(),
                'Pickup' => $pickupData
            ];

            $response = $this->client->CreatePickup($params);

            return $this->formatResponse($response);

        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /* ===================== HELPERS ===================== */

    private function formatResponse($response)
    {
        if (isset($response->HasErrors) && $response->HasErrors) {
            return [
                'success' => false,
                'errors' => $response->Notifications ?? []
            ];
        }

        return [
            'success' => true,
            'data' => $response
        ];
    }

    private function error($message)
    {
        return [
            'success' => false,
            'error' => $message
        ];
    }
}