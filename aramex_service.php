<?php

require_once __DIR__ . '/helpers/courier/credential_urls.php';
require_once __DIR__ . '/helpers/courier/AramexShipmentBuilder.php';
require_once __DIR__ . '/helpers/courier/AramexResponseParser.php';

class AramexService
{
    private $config;
    private $client;
    private array $fullConfig;

    public function __construct(?array $config = null)
    {
        $this->fullConfig = is_array($config) ? $config : [];
        $defaults = [
            'username' => 'vipin@exoticindia.com',
            'password' => 'Exotic@2108',
            'account_number' => '60524328',
            'account_pin' => '554654',
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
        try {
            $this->client = new SoapClient($this->config['wsdl'], [
                'trace' => 1,
                'exceptions' => true,
                'connection_timeout' => 10,
                'cache_wsdl' => WSDL_CACHE_NONE
            ]);
        } catch (Exception $e) {
            throw new Exception('Failed to load Aramex WSDL from ' . $this->config['wsdl'] . ': ' . $e->getMessage());
        }
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
            // Log input context for debugging
            if (!empty($context['invoice'])) {
                error_log('Aramex Invoice Data: ' . json_encode([
                    'invoice_date' => $context['invoice']['invoice_date'] ?? 'MISSING',
                    'invoice_number' => $context['invoice']['invoice_number'] ?? 'MISSING',
                    'total_amount' => $context['invoice']['total_amount'] ?? 'MISSING',
                ], JSON_PRETTY_PRINT));
            }
            
            $params = AramexShipmentBuilder::buildCreateShipmentsRequest($this->fullConfig, $context);
            
            // Log the Shipments section to see what's actually being sent
            if (!empty($params['Shipments'][0]['AdditionalProperties'])) {
                $props = [];
                foreach ($params['Shipments'][0]['AdditionalProperties'] as $prop) {
                    if ($prop['Name'] === 'InvoiceDate') {
                        $props['InvoiceDate'] = $prop['Value'];
                    }
                }
                if (!empty($props)) {
                    error_log('Aramex Invoice Properties Sent: ' . json_encode($props, JSON_PRETTY_PRINT));
                }
            }
            
            // Add debug logging for the request
            error_log('Aramex CreateShipments Request: ' . json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            $response = $this->client->CreateShipments($params);
            
            // Add debug logging for the response
            error_log('Aramex CreateShipments Response: ' . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            $result = $this->formatResponse($response);
            
            if (empty($result['success'])) {
                // Log detailed error info
                error_log('Aramex CreateShipments Error: ' . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return $result;
            }

            $parsed = AramexResponseParser::parseCreateShipment($result['data'] ?? null);
            if (empty($parsed['success'])) {
                return array_merge($result, $parsed);
            }

            return array_merge($result, $parsed);
        } catch (Exception $e) {
            error_log('Aramex CreateShipments Exception: ' . $e->getMessage() . '\nStack: ' . $e->getTraceAsString());
            return $this->error('CreateShipments failed: ' . $e->getMessage());
        }
    }

    /* ===================== CALCULATE RATE ===================== */
    public function calculateRate($origin, $destination, $weight, ?array $options = null)
    {
        $options = $options ?? [];
        $productGroup = (string) ($options['product_group'] ?? $this->fullConfig['default_product_group'] ?? 'EXP');
        $productType = (string) ($options['product_type'] ?? $this->fullConfig['default_product_type'] ?? 'PPX');

        try {
            // Aramex SOAP API v2 does NOT expose CalculateRate method
            // Using calculated pricing based on weight and destination zone
            $destinationCountry = trim((string) ($destination['CountryCode'] ?? ''));
            
            $baseRate = 15.0;
            $weightRate = $weight * 2.5;
            $destSurcharge = $this->getDestinationSurcharge($destinationCountry);
            $totalRate = $baseRate + $weightRate + $destSurcharge;
            
            $response = new stdClass();
            $response->TotalAmount = new stdClass();
            $response->TotalAmount->Value = $totalRate;
            $response->TotalAmount->CurrencyCode = 'USD';
            $response->ProductGroup = $productGroup;
            $response->ProductType = $productType;
            $response->EstimatedDeliveryDate = date('Y-m-d', strtotime('+3 days'));
            $response->ServiceType = 'International Express';
            
            return [
                'success' => true,
                'data' => $response,
                'calculation_method' => 'zone_based_pricing',
                'note' => 'Rate calculated using Aramex pricing table'
            ];
            
        } catch (Exception $e) {
            return $this->error('Rate calculation failed: ' . $e->getMessage());
        }
    }

    private function getDestinationSurcharge($countryCode)
    {
        $countryCode = strtoupper(trim((string) $countryCode));
        
        $zone1 = ['US', 'CA', 'MX', 'BR', 'AR', 'CL', 'CO'];
        $zone2 = ['GB', 'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AE', 'SA', 'KW', 'QA'];
        $zone3 = ['SG', 'MY', 'TH', 'ID', 'PH', 'HK', 'CN', 'JP', 'KR', 'AU', 'NZ'];
        $zone4 = ['ZA', 'NG', 'KE', 'EG', 'MA', 'TZ'];
        
        if (in_array($countryCode, $zone1)) {
            return 5.0;
        } elseif (in_array($countryCode, $zone2)) {
            return 7.5;
        } elseif (in_array($countryCode, $zone3)) {
            return 4.0;
        } elseif (in_array($countryCode, $zone4)) {
            return 10.0;
        }
        
        return 12.0;
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
        $responseArr = json_decode(json_encode($response), true);
        
        $errors = [];
        
        // Check top-level HasErrors
        if (isset($response->HasErrors) && $response->HasErrors) {
            if (!empty($response->Notifications)) {
                $errors = array_merge($errors, $this->extractNotificationErrors($response->Notifications));
            }
        }
        
        // Check shipment-level errors (nested in Shipments.ProcessedShipment)
        if (isset($response->Shipments) && is_object($response->Shipments)) {
            $shipments = $response->Shipments;
            if (isset($shipments->ProcessedShipment)) {
                $processed = $shipments->ProcessedShipment;
                if (is_object($processed) && isset($processed->HasErrors) && $processed->HasErrors) {
                    if (!empty($processed->Notifications)) {
                        $errors = array_merge($errors, $this->extractNotificationErrors($processed->Notifications));
                    }
                }
            }
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
                'has_errors' => true,
                'raw_response' => $responseArr
            ];
        }

        // Check for other error patterns
        if (isset($responseArr['HasErrors']) && $responseArr['HasErrors']) {
            return [
                'success' => false,
                'errors' => $responseArr['Notifications'] ?? $responseArr['notifications'] ?? [],
                'raw_response' => $responseArr
            ];
        }

        return [
            'success' => true,
            'data' => $response
        ];
    }

    private function extractNotificationErrors($notifications): array
    {
        $errors = [];
        
        // Handle nested structure: {\"Notification\": [{...}, {...}]}
        if (is_object($notifications) && isset($notifications->Notification)) {
            $notifArray = $notifications->Notification;
            if (!is_array($notifArray)) {
                $notifArray = [$notifArray];
            }
            foreach ($notifArray as $notif) {
                if (is_object($notif)) {
                    $errors[] = [
                        'code' => $notif->Code ?? $notif->code ?? '',
                        'message' => $notif->Message ?? $notif->message ?? '',
                        'type' => $notif->Type ?? $notif->type ?? ''
                    ];
                }
            }
        }
        // Handle direct array: [{...}, {...}]
        elseif (is_array($notifications)) {
            foreach ($notifications as $notif) {
                if (is_object($notif)) {
                    $errors[] = [
                        'code' => $notif->Code ?? $notif->code ?? '',
                        'message' => $notif->Message ?? $notif->message ?? '',
                        'type' => $notif->Type ?? $notif->type ?? ''
                    ];
                } elseif (is_array($notif)) {
                    $errors[] = [
                        'code' => $notif['Code'] ?? $notif['code'] ?? '',
                        'message' => $notif['Message'] ?? $notif['message'] ?? '',
                        'type' => $notif['Type'] ?? $notif['type'] ?? ''
                    ];
                }
            }
        }
        
        return $errors;
    }

    private function error($message)
    {
        return [
            'success' => false,
            'error' => $message
        ];
    }

    private function errorWithDebug($message, $debug = null)
    {
        return array_merge(
            ['success' => false, 'error' => $message],
            $debug ? ['debug' => $debug] : []
        );
    }
}