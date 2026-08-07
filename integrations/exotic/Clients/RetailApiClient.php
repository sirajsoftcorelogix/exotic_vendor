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
    private ?string $customCountryCode = null;

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
                if (stripos(trim($line), 'x-api-countrycode:') === 0) {
                    $headers = array_values(array_filter($headers, function ($h) {
                        return stripos(trim($h), 'x-api-countrycode:') !== 0;
                    }));
                }
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

    public function setCustomerCountryCode(?string $code): self
    {
        $code = trim((string)$code);
        $this->customCountryCode = $code !== '' ? $code : null;

        return $this;
    }

    public function getCustomerCountryCode(): ?string
    {
        return $this->customCountryCode;
    }

    /**
     * Resolve ISO 2-letter country code for active customer to pass in x-api-countrycode header.
     */
    public function resolveCustomerCountryCode(): string
    {
        $conn = $this->conn ?: ($GLOBALS['conn'] instanceof mysqli ? $GLOBALS['conn'] : null);

        if (!empty($this->customCountryCode)) {
            require_once dirname(__DIR__, 3) . '/helpers/courier/country_codes.php';
            $iso2 = normalizeCountryIso2($this->customCountryCode, $conn);
            if (!empty($iso2) && strlen($iso2) === 2 && ctype_alpha($iso2)) {
                return strtoupper($iso2);
            }
        }

        $countryStr = '';

        if (!empty($_SESSION['pos_customer_id'])) {
            $cid = (int)$_SESSION['pos_customer_id'];
            if ($cid > 0 && $conn instanceof mysqli) {
                $stmt = $conn->prepare('SELECT country_of_residence FROM vp_customers WHERE id = ? LIMIT 1');
                if ($stmt) {
                    $stmt->bind_param('i', $cid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $row = $res ? $res->fetch_assoc() : null;
                    $stmt->close();
                    if ($row && !empty($row['country_of_residence'])) {
                        $countryStr = trim((string)$row['country_of_residence']);
                    }
                }

                if ($countryStr === '') {
                    $detStmt = $conn->prepare('SELECT bill_country, ship_country FROM pos_customer_details WHERE customer_id = ? LIMIT 1');
                    if ($detStmt) {
                        $detStmt->bind_param('i', $cid);
                        $detStmt->execute();
                        $det = $detStmt->get_result()->fetch_assoc();
                        $detStmt->close();
                        if ($det) {
                            $countryStr = trim((string)($det['bill_country'] ?? ''));
                            if ($countryStr === '') {
                                $countryStr = trim((string)($det['ship_country'] ?? ''));
                            }
                        }
                    }
                }
            }
        }

        if ($countryStr === '' && !empty($_SESSION['pos_customer_form'])) {
            $form = $_SESSION['pos_customer_form'];
            if (is_array($form)) {
                $countryStr = trim((string)($form['country_of_residence'] ?? $form['country'] ?? ''));
            }
        }

        if ($countryStr !== '') {
            require_once dirname(__DIR__, 3) . '/helpers/courier/country_codes.php';
            $iso2 = normalizeCountryIso2($countryStr, $conn);
            if (!empty($iso2) && strlen($iso2) === 2 && ctype_alpha($iso2)) {
                return strtoupper($iso2);
            }
        }

        return 'IN';
    }

    /** @return list<string> */
    public function buildRequestHeaders(): array
    {
        $warehouseId = isset($_SESSION['warehouse_id']) ? (int) $_SESSION['warehouse_id'] : 0;
        $deviceId = RetailApiDeviceIdResolver::resolve($this->conn, $warehouseId > 0 ? $warehouseId : null);
        $countryCode = $this->resolveCustomerCountryCode();
        $headers = [
            'x-api-key: aeRGoUvQLCxztK0Wzxmv9O2VRJ2H1B44',
            'x-api-deviceid: ' . $deviceId,
            'x-api-appplayerid: POS-Web-Terminal',
            'x-api-countrycode: ' . $countryCode,
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

    /**
     * Modify POS order item-level price in order and order total.
     * POST https://www.exoticindia.com/api/order/pos_editorderprices
     *
     * @param string $orderId Order ID / order_number of the order
     * @param list<array{itemcode?:string,item_code?:string,size?:string,color?:string,price?:float|int|string}> $items All items in the order
     * @return array{data: array, code: int, raw: string}
     */
    public function editOrderPrices(string $orderId, array $items): array
    {
        $postData = [
            'orderid' => $orderId,
        ];

        foreach (array_values($items) as $i => $item) {
            $postData["itemcode[{$i}]"] = (string)($item['itemcode'] ?? $item['item_code'] ?? '');
            $postData["size[{$i}]"] = (string)($item['size'] ?? '');
            $postData["color[{$i}]"] = (string)($item['color'] ?? '');
            $postData["price[{$i}]"] = (float)($item['price'] ?? $item['finalprice'] ?? 0);
        }

        return $this->call('/order/pos_editorderprices', 'POST', [], $postData);
    }
}
