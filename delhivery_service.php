<?php
/**
 * Delhivery HTTP Client
 * 
 * Thin wrapper for Delhivery REST API calls.
 * Called by DelhiveryAdapter::getRates() and createShipment().
 * 
 * TODO: Implement actual Delhivery API integration
 * - Serviceability API: /api/backend/api_pick_expected_del_date/
 * - Create Shipment: /api/cmu/create/revision/1/
 * - Get Tracking: /api/track/shipment/
 * 
 * Reference: https://www.delhivery.com/api/
 */

class DelhiveryService
{
    private string $apiKey;
    private string $baseUrl;
    private string $environment;

    /**
     * @param string $apiKey Delhivery API key from credentials
     * @param string $environment 'sandbox' or 'production'
     */
    public function __construct(string $apiKey, string $environment = 'production', string $baseUrlOverride = '')
    {
        $this->apiKey = $apiKey;
        $this->environment = $environment;

        $baseUrlOverride = trim($baseUrlOverride);
        if ($baseUrlOverride !== '') {
            $this->baseUrl = rtrim($baseUrlOverride, '/');
            return;
        }

        // Delhivery uses different hostnames for staging vs production.
        // Production docs commonly reference track.delhivery.com.
        $this->baseUrl = $environment === 'sandbox'
            ? 'https://staging-express.delhivery.com'
            : 'https://track.delhivery.com';
    }

    /**
     * Estimate charges using Delhivery Invoice - Shipping Charge API.
     *
     * Uses endpoint (prod): /api/kinko/v1/invoice/charges/.json
     * Docs mention mandatory params: md, cgm, o_pin, d_pin, ss.
     *
     * @param array{md:string,cgm:int,o_pin:string,d_pin:string,ss:string,cl?:string,pt?:string} $params
     * @return array{success:bool,http_code?:int,data?:mixed,message?:string,request_url?:string,curl_error?:string}
     */
    public function estimateFreightCharges(array $params): array
    {
        $endpoint = '/api/kinko/v1/invoice/charges/.json';
        $url = rtrim($this->baseUrl, '/') . $endpoint;
        $query = http_build_query($params);
        if ($query !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . $query;
        }

        return $this->makeRequest('GET', $url);
    }

    /**
     * Create a shipment via Delhivery.
     * 
     * TODO: Implement actual Delhivery API call
     * 
     * @param array $shipmentData Shipment details (customer, items, dimensions, etc.)
     * @return array ['success' => bool, 'awb' => string, 'label_url' => string, 'error' => string]
     */
    public function createShipment(array $shipmentData): array
    {
        // TODO: Call Delhivery create shipment API
        // Example:
        // $response = $this->makeRequest('POST', '/cmu/create/revision/1/', [
        //     'waybills' => $shipmentData['waybills'],
        //     'pickup' => $shipmentData['pickup'],
        // ]);
        
        return [
            'success' => false,
            'message' => 'Delhivery create shipment API not implemented yet',
        ];
    }

    /**
     * Get tracking status for a shipment.
     * 
     * TODO: Implement actual Delhivery API call
     * 
     * @param string $awb Air Waybill number
     * @return array ['success' => bool, 'status' => string, 'tracking' => [], 'error' => string]
     */
    public function getTracking(string $awb): array
    {
        // TODO: Call Delhivery tracking API
        // Example:
        // $response = $this->makeRequest('GET', '/track/shipment/', [
        //     'waybills' => $awb,
        // ]);
        
        return [
            'success' => false,
            'message' => 'Delhivery tracking API not implemented yet',
        ];
    }

    /**
     * Make HTTP request to Delhivery API.
     * 
     * TODO: Implement actual HTTP request logic with error handling
     * 
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint path
     * @param array $data Request parameters or body
     * @return array API response
     */
    private function makeRequest(string $method, string $url): array
    {
        $method = strtoupper(trim($method));
        if (!in_array($method, ['GET', 'POST'], true)) {
            return ['success' => false, 'message' => 'Unsupported HTTP method: ' . $method];
        }

        $headers = [
            'Accept: application/json, text/plain, */*',
            'Authorization: Token ' . $this->apiKey,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $rawStr = is_string($raw) ? trim($raw) : '';
        $decoded = null;
        if ($rawStr !== '') {
            $decoded = json_decode($rawStr, true);
            if (!is_array($decoded)) {
                $decoded = $rawStr;
            }
        }

        $ok = $httpCode >= 200 && $httpCode < 300;
        return [
            'success' => $ok,
            'http_code' => $httpCode,
            'data' => $decoded,
            'raw' => $rawStr,
            'message' => $ok ? 'OK' : ('HTTP ' . $httpCode),
            'request_url' => preg_replace('/Token\s+\S+/', 'Token ***', $url),
            'curl_error' => $curlError,
        ];
    }
}

// Example usage (when implemented):
// $service = new DelhiveryService($credentials['api_key'], $credentials['environment']);
// $result = $service->getServiceability('110001', '400001', 2.5);
