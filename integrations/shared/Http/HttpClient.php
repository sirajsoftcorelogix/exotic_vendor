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
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
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
        curl_close($ch);

        if (!is_string($body) || $httpCode >= 400) {
            return null;
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }
}
