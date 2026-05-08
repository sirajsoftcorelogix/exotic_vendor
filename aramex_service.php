<?php

class AramexService
{
    private $config;
    private $client;

    public function __construct()
    {
        $this->config = [
            'username' => 'vipin@exoticindia.com',
            'password' => 'Exotic@2108',
            'account_number' => '60524328',
            'account_pin' => 'YOUR_PIN',
            'entity' => 'DEL',
            'country_code' => 'IN',
            'wsdl' => 'https://ws.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc?wsdl',
            'tracking_wsdl' => 'https://ws.aramex.net/ShippingAPI.V2/Tracking/Service_1_0.svc?wsdl'
        ];

        $this->client = new SoapClient($this->config['wsdl'], [
            'trace' => 1,
            'exceptions' => true
        ]);
    }

    private function clientInfo()
    {
        return [
            'UserName' => $this->config['username'],
            'Password' => $this->config['password'],
            'Version' => 'v1.0',
            'AccountNumber' => $this->config['account_number'],
            'AccountPin' => $this->config['account_pin'],
            'AccountEntity' => $this->config['entity'],
            'AccountCountryCode' => $this->config['country_code'],
        ];
    }

    /* ===================== CREATE SHIPMENT ===================== */
    public function createShipment($shipmentData)
    {
        try {
            $params = [
                'ClientInfo' => $this->clientInfo(),
                'Shipments' => [$shipmentData],
            ];

            $response = $this->client->CreateShipments($params);

            return $this->formatResponse($response);

        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /* ===================== CALCULATE RATE ===================== */
    public function calculateRate($origin, $destination, $weight)
    {
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
                    'ProductGroup' => 'DOM',
                    'ProductType' => 'OND',
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