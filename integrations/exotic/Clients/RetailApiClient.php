<?php

require_once __DIR__ . '/../Support/RetailApiDeviceIdResolver.php';

/**
 * Exotic India retail API client (https://www.exoticindia.com/api/*).
 * Used by POS register for cart, product, and order/create flows.
 */
class RetailApiClient
{
    private ?mysqli $conn;
    private string $defaultBaseUrl;

    public function __construct(?mysqli $conn = null, string $defaultBaseUrl = 'https://www.exoticindia.com/api')
    {
        $this->conn = $conn;
        $this->defaultBaseUrl = rtrim($defaultBaseUrl, '/');
    }

    public static function create(?mysqli $conn = null): self
    {
        if ($conn === null) {
            global $conn;
        }

        return new self($conn instanceof mysqli ? $conn : null);
    }

    /**
     * @param array<string, mixed>|string|null $postData
     * @param list<string> $extraHttpHeaders
     * @return array{data: array, code: int, raw: string}
     */
    public function call(
        string $endpoint,
        string $method = 'GET',
        array $params = [],
        $postData = null,
        ?string $apiBaseUrl = null,
        array $extraHttpHeaders = []
    ): array {
        require_once dirname(__DIR__, 3) . '/helpers/api_call_logger.php';

        $ep = '/' . ltrim($endpoint, '/');
        if (strtoupper($method) === 'POST' && rtrim($ep, '/') === '/order/create'
            && is_file(dirname(__DIR__, 3) . '/.pos_skip_exotic_order_create_api')) {
            $d = ['orderid' => 'LOCAL-' . gmdate('YmdHis')];
            $j = json_encode($d);
            api_call_log_write([
                'kind' => 'exotic_api_local_stub',
                'endpoint' => $ep,
                'method' => strtoupper($method),
                'note' => '.pos_skip_exotic_order_create_api present — order/create not sent remotely',
                'response_http_code' => 200,
                'response_raw' => $j,
                'response_decoded' => $d,
            ]);

            return ['data' => $d, 'code' => 200, 'raw' => $j];
        }

        $base = $apiBaseUrl ?? $this->defaultBaseUrl;
        $url = rtrim($base, '/') . $endpoint;
        if ($params !== []) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        }

        $encodedPostData = null;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $headers = $this->buildRequestHeaders();
        foreach ($extraHttpHeaders as $line) {
            if (is_string($line) && $line !== '') {
                $headers[] = $line;
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $capturedHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $headerLine) use (&$capturedHeaders) {
            $len = strlen($headerLine);
            $header = explode(':', $headerLine, 2);
            if (count($header) < 2) {
                return $len;
            }
            $name = strtolower(trim($header[0]));
            if (in_array($name, ['x-api-euid', 'x-api-jwt', 'x-api-browsehistory', 'x-api-etd', 'x-api-etd-pincode'], true)) {
                $capturedHeaders[$name] = trim($header[1]);
            }

            return $len;
        });

        if ($method === 'POST' && $postData !== null) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            if (is_array($postData)) {
                $encodedPostData = http_build_query($postData);
            } elseif (is_string($postData)) {
                $encodedPostData = $postData;
            } else {
                $encodedPostData = (string) $postData;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedPostData);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $this->persistSessionHeadersFromResponse($capturedHeaders);

        $body = (string) $response;
        $decoded = json_decode($body, true);
        $data = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];

        api_call_log_write([
            'kind' => 'exotic_api_http',
            'endpoint' => $ep,
            'method' => strtoupper($method),
            'base_url' => $base,
            'request_url' => $url,
            'request_headers' => api_call_log_sanitize_header_lines($headers),
            'request_query_params' => $params,
            'request_post_body' => $encodedPostData,
            'curl_error' => $curlErr !== '' ? $curlErr : null,
            'response_http_code' => $httpCode,
            'response_session_headers_from_api' => $capturedHeaders,
            'response_raw' => $body,
            'response_decoded' => $data,
        ]);

        return [
            'data' => $data,
            'code' => $httpCode,
            'raw' => $body,
        ];
    }

    /** @return list<string> */
    public function buildRequestHeaders(): array
    {
        $warehouseId = isset($_SESSION['warehouse_id']) ? (int) $_SESSION['warehouse_id'] : 0;
        $deviceId = RetailApiDeviceIdResolver::resolve($this->conn, $warehouseId > 0 ? $warehouseId : null);
        $headers = [
            'x-api-key: aeRGoUvQLCxztK0Wzxmv9O2VRJ2H1B44',
            'x-api-deviceid: ' . $deviceId,
            'x-api-appplayerid: POS-Web-Terminal',
            'x-api-countrycode: IN',
            'x-api-euid:' . (string) ($_SESSION['x_api_euid'] ?? ''),
            'User-Agent: ExoticPOS',
        ];
        if (!empty($_SESSION['x_api_jwt'])) {
            $headers[] = 'x-api-jwt:' . (string) $_SESSION['x_api_jwt'];
        }
        if (!empty($_SESSION['x_api_browsehistory'])) {
            $headers[] = 'x-api-browsehistory:' . (string) $_SESSION['x_api_browsehistory'];
        }
        if (!empty($_SESSION['x_api_etd'])) {
            $headers[] = 'x-api-etd:' . (string) $_SESSION['x_api_etd'];
        }
        if (!empty($_SESSION['x_api_etd_pincode'])) {
            $headers[] = 'x-api-etd-pincode:' . (string) $_SESSION['x_api_etd_pincode'];
        }

        return $headers;
    }

    /**
     * @param array<string, string> $capturedHeaders
     */
    private function persistSessionHeadersFromResponse(array $capturedHeaders): void
    {
        if (!empty($capturedHeaders['x-api-euid'])) {
            $_SESSION['x_api_euid'] = $capturedHeaders['x-api-euid'];
        }
        if (!empty($capturedHeaders['x-api-jwt'])) {
            $_SESSION['x_api_jwt'] = $capturedHeaders['x-api-jwt'];
        }
        if (!empty($capturedHeaders['x-api-browsehistory'])) {
            $_SESSION['x_api_browsehistory'] = $capturedHeaders['x-api-browsehistory'];
        }
        if (!empty($capturedHeaders['x-api-etd'])) {
            $_SESSION['x_api_etd'] = $capturedHeaders['x-api-etd'];
        }
        if (!empty($capturedHeaders['x-api-etd-pincode'])) {
            $_SESSION['x_api_etd_pincode'] = $capturedHeaders['x-api-etd-pincode'];
        }
    }
}
