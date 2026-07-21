<?php

/**
 * Thin JSON HTTP client for third-party integrations.
 */
class HttpClient
{
    private string $userAgent;

    public function __construct(string $userAgent = 'ExoticVendorInbound/1.0')
    {
        $this->userAgent = $userAgent;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getJson(string $url, int $connectTimeout = 8, int $timeout = 15): ?array
    {
        $response = $this->requestJson($url, $connectTimeout, $timeout);

        return $response['ok'] ? ($response['data'] ?? null) : null;
    }

    /**
     * @return array{ok:bool,http_code:int,data:?array,error:?string,curl_error:?string}
     */
    public function requestJson(string $url, int $connectTimeout = 8, int $timeout = 15): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return [
                'ok' => false,
                'http_code' => 0,
                'data' => null,
                'error' => 'Could not initialize HTTP client.',
                'curl_error' => 'curl_init failed',
            ];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: ' . $this->userAgent,
            ],
        ]);

        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!is_string($body)) {
            return [
                'ok' => false,
                'http_code' => $httpCode,
                'data' => null,
                'error' => $curlError !== '' ? $curlError : 'Empty HTTP response.',
                'curl_error' => $curlError !== '' ? $curlError : null,
            ];
        }

        $decoded = json_decode($body, true);
        $data = is_array($decoded) ? $decoded : null;

        if ($httpCode >= 400) {
            $apiMessage = trim((string) ($data['error']['message'] ?? ''));
            if ($apiMessage === '' && is_array($data['error'] ?? null)) {
                $apiMessage = trim((string) ($data['error']['errors'][0]['message'] ?? ''));
            }
            if ($apiMessage === '') {
                $apiMessage = 'HTTP ' . $httpCode . ' from upstream API.';
            }

            return [
                'ok' => false,
                'http_code' => $httpCode,
                'data' => $data,
                'error' => $apiMessage,
                'curl_error' => $curlError !== '' ? $curlError : null,
            ];
        }

        return [
            'ok' => true,
            'http_code' => $httpCode,
            'data' => $data,
            'error' => null,
            'curl_error' => $curlError !== '' ? $curlError : null,
        ];
    }
}
