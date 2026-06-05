<?php
/**
 * Delhivery HTTP Client
 *
 * Used by DelhiveryAdapter for rates, order creation, and packing slip (label data).
 *
 * @see https://delhivery-express-api-doc.readme.io/reference/order-creation-api
 * @see https://delhivery-express-api-doc.readme.io/reference/packing-slip-api
 */

class DelhiveryService
{
    private string $apiKey;
    private string $baseUrl;
    private string $environment;

    public function __construct(string $apiKey, string $environment = 'production', string $baseUrlOverride = '')
    {
        $this->apiKey = $apiKey;
        $this->environment = $environment;

        $baseUrlOverride = trim($baseUrlOverride);
        if ($baseUrlOverride !== '') {
            $this->baseUrl = rtrim($baseUrlOverride, '/');
            return;
        }

        $this->baseUrl = $environment === 'sandbox'
            ? 'https://staging-express.delhivery.com'
            : 'https://track.delhivery.com';
    }

    /**
     * @param array{md:string,cgm:int,o_pin:string,d_pin:string,ss:string,cl?:string,pt?:string,cod?:int} $params
     */
    public function estimateFreightCharges(array $params, string $rateApiPath = ''): array
    {
        $url = $this->resolveApiUrl($rateApiPath, '/api/kinko/v1/invoice/charges/.json');
        $query = http_build_query($params);
        if ($query !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . $query;
        }

        return $this->makeRequest('GET', $url);
    }

    /**
     * Create a B2C package order (manifestation).
     *
     * POST body must be format=json&data={JSON} per Delhivery docs.
     *
     * @param array{pickup_location:array<string,mixed>,shipments:list<array<string,mixed>>} $payload
     */
    public function createPackageOrder(array $payload, string $createApiPath = ''): array
    {
        $url = $this->resolveApiUrl($createApiPath, '/api/cmu/create.json');
        $body = 'format=json&data=' . rawurlencode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $this->makeRequest('POST', $url, [
            'body' => $body,
            'content_type' => 'application/x-www-form-urlencoded',
        ]);
    }

    /**
     * Fetch packing slip / label data for one or more waybills (comma-separated).
     *
     * @see https://delhivery-express-api-doc.readme.io/reference/packing-slip-api
     */
    public function getPackingSlip(string $waybills, string $packingSlipPath = ''): array
    {
        $waybills = trim($waybills);
        if ($waybills === '') {
            return ['success' => false, 'message' => 'Waybill is required for packing slip.'];
        }

        $basePath = $packingSlipPath !== '' ? $packingSlipPath : '/api/p/packing_slip';
        $url = $this->resolveApiUrl($basePath, '/api/p/packing_slip');
        $url .= (str_contains($url, '?') ? '&' : '?') . 'wbns=' . rawurlencode($waybills);

        return $this->makeRequest('GET', $url);
    }

    /**
     * @deprecated Use createPackageOrder()
     */
    public function createShipment(array $shipmentData): array
    {
        return $this->createPackageOrder($shipmentData);
    }

    public function getTracking(string $awb): array
    {
        $url = rtrim($this->baseUrl, '/') . '/api/v1/packages/json/?waybill=' . rawurlencode($awb);
        return $this->makeRequest('GET', $url);
    }

    private function resolveApiUrl(string $override, string $defaultPath): string
    {
        $override = trim($override);
        if ($override === '') {
            return rtrim($this->baseUrl, '/') . $defaultPath;
        }
        if (preg_match('#^https?://#i', $override)) {
            return $override;
        }
        return rtrim($this->baseUrl, '/') . '/' . ltrim($override, '/');
    }

    /**
     * @param array{body?:string,content_type?:string} $options
     * @return array{success:bool,http_code?:int,data?:mixed,message?:string,request_url?:string,curl_error?:string,raw?:string}
     */
    private function makeRequest(string $method, string $url, array $options = []): array
    {
        $method = strtoupper(trim($method));
        if (!in_array($method, ['GET', 'POST'], true)) {
            return ['success' => false, 'message' => 'Unsupported HTTP method: ' . $method];
        }

        $headers = [
            'Accept: application/json, text/plain, */*',
            'Authorization: Token ' . $this->apiKey,
        ];
        if (!empty($options['content_type'])) {
            $headers[] = 'Content-Type: ' . $options['content_type'];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (isset($options['body'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $options['body']);
            }
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
        if ($ok && is_array($decoded)) {
            if (isset($decoded['success']) && $decoded['success'] === false) {
                $ok = false;
            }
            if (isset($decoded['error']) && $decoded['error'] === true) {
                $ok = false;
            }
        }

        $message = $ok ? 'OK' : ('HTTP ' . $httpCode);
        if (!$ok && is_array($decoded)) {
            $message = $this->extractErrorMessage($decoded) ?: $message;
        }

        return [
            'success' => $ok,
            'http_code' => $httpCode,
            'data' => $decoded,
            'raw' => $rawStr,
            'message' => $message,
            'request_url' => preg_replace('/Token\s+\S+/', 'Token ***', $url),
            'curl_error' => $curlError,
        ];
    }

    /** @param array<string, mixed> $decoded */
    private function extractErrorMessage(array $decoded): ?string
    {
        foreach (['rmk', 'remark', 'message', 'error'] as $key) {
            if (!empty($decoded[$key]) && is_string($decoded[$key])) {
                return $decoded[$key];
            }
        }
        if (!empty($decoded['packages']) && is_array($decoded['packages'])) {
            foreach ($decoded['packages'] as $pkg) {
                if (!is_array($pkg)) {
                    continue;
                }
                if (!empty($pkg['remarks']) && is_array($pkg['remarks'])) {
                    return implode('; ', array_map('strval', $pkg['remarks']));
                }
                if (!empty($pkg['status']) && strtolower((string) $pkg['status']) !== 'success') {
                    return (string) $pkg['status'];
                }
            }
        }
        return null;
    }
}
