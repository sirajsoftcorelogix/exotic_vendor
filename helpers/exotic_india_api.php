<?php

/**
 * Shared cURL client for https://www.exoticindia.com/api/* endpoints.
 * Matches helpers/vendor_external_api.php request structure (auth headers + curl options).
 */

function exotic_india_api_base_url(): string
{
    $base = getenv('EXOTIC_INDIA_API_BASE');
    if ($base !== false && trim((string) $base) !== '') {
        return rtrim((string) $base, '/');
    }

    return 'https://www.exoticindia.com/api';
}

function exotic_india_api_key(): string
{
    $apiKey = getenv('EXOTIC_INDIA_API_KEY');
    if ($apiKey === false || trim((string) $apiKey) === '') {
        return 'K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9';
    }

    return trim((string) $apiKey);
}

/**
 * Same auth headers as vendor-api/order import calls.
 *
 * @return list<string>
 */
function exotic_india_api_auth_headers(bool $includeAdminApiTest = true): array
{
    $headers = [
        'x-api-key: ' . exotic_india_api_key(),
    ];
    if ($includeAdminApiTest) {
        $headers[] = 'x-adminapitest: 1';
    }

    return $headers;
}

/**
 * @return array{body:string,response_headers:string,curl_error:string,http_code:int,ok:bool}
 */
function exotic_india_curl_exec_capture(string $url, array $curlOptions): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, array_merge([
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ], $curlOptions));

    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $rawStr = is_string($raw) ? $raw : '';
    $responseHeaders = $headerSize > 0 ? substr($rawStr, 0, $headerSize) : '';
    $body = $headerSize > 0 ? substr($rawStr, $headerSize) : $rawStr;

    return [
        'body' => $body,
        'response_headers' => trim($responseHeaders),
        'curl_error' => $curlError,
        'http_code' => $httpCode,
        'ok' => $raw !== false,
    ];
}

/**
 * @param list<string> $extraHeaders e.g. Content-Type, Accept
 * @return array{success:bool,message:string,http_code:int,data:array,raw:string,curl_error?:string,request_url:string,request_headers:list<string>,response_headers?:string}
 */
function exotic_india_api_post(string $endpoint, string $postBody, array $extraHeaders = [], bool $includeAdminApiTest = true): array
{
    $endpoint = '/' . ltrim($endpoint, '/');
    $url = exotic_india_api_base_url() . $endpoint;
    $headers = array_merge(exotic_india_api_auth_headers($includeAdminApiTest), $extraHeaders);

    $transport = exotic_india_curl_exec_capture($url, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postBody,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    $curlError = $transport['curl_error'];
    $httpCode = $transport['http_code'];
    $responseHeaders = $transport['response_headers'];
    $rawStr = $transport['body'];
    $decoded = json_decode($rawStr, true);
    $data = is_array($decoded) ? $decoded : [];

    if (!$transport['ok']) {
        return [
            'success' => false,
            'message' => 'API call failed: ' . $curlError,
            'http_code' => $httpCode,
            'data' => $data,
            'raw' => $rawStr,
            'curl_error' => $curlError,
            'request_url' => $url,
            'request_headers' => $headers,
            'response_headers' => $responseHeaders,
        ];
    }

    if ($httpCode >= 400) {
        $message = trim((string) ($data['message'] ?? ''));
        if ($message === '' && isset($data['error'])) {
            $message = is_string($data['error']) ? trim($data['error']) : json_encode($data['error']);
        }
        if ($message === '' && $rawStr !== '') {
            $message = trim($rawStr);
        }
        if ($message === '' && $rawStr === '') {
            $message = 'HTTP ' . $httpCode . ' with empty response body from Exotic India API'
                . ' (their server did not return JSON — often invalid order/sale/AWB data or a duplicate shipment).';
        } elseif ($message === '') {
            $message = 'HTTP ' . $httpCode;
        }

        return [
            'success' => false,
            'message' => $message,
            'http_code' => $httpCode,
            'data' => $data,
            'raw' => $rawStr,
            'request_url' => $url,
            'request_headers' => $headers,
            'response_headers' => $responseHeaders,
        ];
    }

    $status = strtolower(trim((string) ($data['status'] ?? '')));
    if ((isset($data['success']) && $data['success'] === false)
        || in_array($status, ['error', 'failed'], true)) {
        $message = trim((string) ($data['message'] ?? ''));
        if ($message === '' && isset($data['error'])) {
            $message = is_string($data['error']) ? trim($data['error']) : json_encode($data['error']);
        }
        if ($message === '') {
            $message = 'Remote API returned failure.';
        }

        return [
            'success' => false,
            'message' => $message,
            'http_code' => $httpCode,
            'data' => $data,
            'raw' => $rawStr,
            'request_url' => $url,
            'request_headers' => $headers,
            'response_headers' => $responseHeaders,
        ];
    }

    $message = trim((string) ($data['message'] ?? ''));
    if ($message === '' && in_array($status, ['success', 'sucess'], true)) {
        $message = 'API call succeeded.';
    }
    if ($message === '') {
        $message = 'API call succeeded.';
    }

    return [
        'success' => true,
        'message' => $message,
        'http_code' => $httpCode,
        'data' => $data,
        'raw' => $rawStr,
        'request_url' => $url,
        'request_headers' => $headers,
        'response_headers' => $responseHeaders,
    ];
}

/**
 * @param list<string> $extraHeaders
 * @return array{success:bool,message:string,http_code:int,data:array,raw:string,curl_error?:string,request_url:string,request_headers:list<string>}
 */
function exotic_india_api_get(string $endpoint, array $extraHeaders = [], bool $includeAdminApiTest = true): array
{
    $endpoint = '/' . ltrim($endpoint, '/');
    $url = exotic_india_api_base_url() . $endpoint;
    $headers = array_merge(exotic_india_api_auth_headers($includeAdminApiTest), $extraHeaders);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPGET => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $rawStr = is_string($raw) ? $raw : '';
    $decoded = json_decode($rawStr, true);
    $data = is_array($decoded) ? $decoded : [];

    if ($raw === false) {
        return [
            'success' => false,
            'message' => 'API call failed: ' . $curlError,
            'http_code' => $httpCode,
            'data' => $data,
            'raw' => $rawStr,
            'curl_error' => $curlError,
            'request_url' => $url,
            'request_headers' => $headers,
        ];
    }

    if ($httpCode >= 400) {
        $message = trim((string) ($data['message'] ?? ''));
        if ($message === '' && isset($data['error'])) {
            $message = is_string($data['error']) ? trim($data['error']) : json_encode($data['error']);
        }
        if ($message === '') {
            $message = 'HTTP ' . $httpCode;
        }

        return [
            'success' => false,
            'message' => $message,
            'http_code' => $httpCode,
            'data' => $data,
            'raw' => $rawStr,
            'request_url' => $url,
            'request_headers' => $headers,
        ];
    }

    return [
        'success' => true,
        'message' => trim((string) ($data['message'] ?? 'API call succeeded.')),
        'http_code' => $httpCode,
        'data' => $data,
        'raw' => $rawStr,
        'request_url' => $url,
        'request_headers' => $headers,
    ];
}
