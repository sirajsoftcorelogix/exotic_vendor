<?php

class VendorReferenceCache
{
    public const KEY_COLORMAPS = 'colormaps';
    public const KEY_OPTIONALS = 'optionals';

    private mysqli $conn;

    public function __construct(mysqli $db)
    {
        $this->conn = $db;
    }

    /**
     * Payload shape expected by desktopform / form3 views.
     *
     * @return array{gecolormaps: mixed, optionals_data: mixed}
     */
    public function getDesktopformRefs(bool $bootstrapSyncIfMissing = true): array
    {
        $colormaps = $this->getPayload(self::KEY_COLORMAPS);
        $optionals = $this->getPayload(self::KEY_OPTIONALS);

        if ($bootstrapSyncIfMissing && ($colormaps === null || $optionals === null)) {
            $this->syncAll();
            $colormaps = $this->getPayload(self::KEY_COLORMAPS);
            $optionals = $this->getPayload(self::KEY_OPTIONALS);
        }

        return [
            'gecolormaps' => $colormaps ?? false,
            'optionals_data' => $optionals ?? false,
        ];
    }

    public function getPayload(string $cacheKey): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT payload_json, sync_status FROM vendor_reference_cache WHERE cache_key = ? LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $cacheKey);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }

        $decoded = json_decode((string) $row['payload_json'], true);
        if (!is_array($decoded) || $decoded === []) {
            return null;
        }

        return $decoded;
    }

    /**
     * @return array<string, array{ok: bool, http_code: int|null, error: string|null, synced_at: string|null}>
     */
    public function syncAll(?int $updatedByUserId = null): array
    {
        return $this->syncKeys([self::KEY_COLORMAPS, self::KEY_OPTIONALS], $updatedByUserId);
    }

    /**
     * @param string[] $keys
     * @return array<string, array{ok: bool, http_code: int|null, error: string|null, synced_at: string|null}>
     */
    public function syncKeys(array $keys, ?int $updatedByUserId = null): array
    {
        $endpoints = $this->apiEndpoints();
        $toFetch = [];
        foreach ($keys as $key) {
            if (isset($endpoints[$key])) {
                $toFetch[$key] = $endpoints[$key];
            }
        }

        $fetched = $this->fetchFromApiParallel($toFetch);
        $results = [];

        foreach ($toFetch as $key => $url) {
            $item = $fetched[$key] ?? ['ok' => false, 'http_code' => null, 'error' => 'No response', 'payload' => null];
            $syncedAt = date('Y-m-d H:i:s');

            if ($item['ok'] && is_array($item['payload'])) {
                $this->upsert(
                    $key,
                    $item['payload'],
                    'ok',
                    (int) $item['http_code'],
                    null,
                    $updatedByUserId,
                    $syncedAt
                );
                $results[$key] = [
                    'ok' => true,
                    'http_code' => (int) $item['http_code'],
                    'error' => null,
                    'synced_at' => $syncedAt,
                ];
            } else {
                $error = (string) ($item['error'] ?? 'Sync failed');
                $this->markError($key, (int) ($item['http_code'] ?? 0), $error, $updatedByUserId, $syncedAt);
                $results[$key] = [
                    'ok' => false,
                    'http_code' => $item['http_code'] ?? null,
                    'error' => $error,
                    'synced_at' => $syncedAt,
                ];
            }
        }

        return $results;
    }

    /**
     * @return array<int, array{cache_key: string, synced_at: string|null, sync_status: string, http_code: int|null, error_message: string|null}>
     */
    public function getSyncMeta(): array
    {
        $sql = 'SELECT cache_key, synced_at, sync_status, http_code, error_message
                FROM vendor_reference_cache
                ORDER BY cache_key ASC';
        $result = $this->conn->query($sql);
        if (!$result) {
            return [];
        }

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    private function apiEndpoints(): array
    {
        $base = 'https://www.exoticindia.com/vendor-api/product';
        return [
            self::KEY_COLORMAPS => $base . '/colormaps',
            self::KEY_OPTIONALS => $base . '/optionals',
        ];
    }

    private function apiHeaders(): array
    {
        return [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Accept: application/json',
        ];
    }

    /**
     * @param array<string, string> $endpoints
     * @return array<string, array{ok: bool, http_code: int|null, error: string|null, payload: mixed}>
     */
    private function fetchFromApiParallel(array $endpoints): array
    {
        if ($endpoints === []) {
            return [];
        }

        $headers = $this->apiHeaders();
        $mh = curl_multi_init();
        $handles = [];

        foreach ($endpoints as $key => $url) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_HTTPGET => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_ENCODING => '',
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }

        $running = null;
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 1.0);
            }
        } while ($running && $status === CURLM_OK);

        $out = [];
        foreach ($handles as $key => $ch) {
            if (curl_errno($ch)) {
                $out[$key] = [
                    'ok' => false,
                    'http_code' => null,
                    'error' => curl_error($ch),
                    'payload' => null,
                ];
                error_log('VendorReferenceCache cURL (' . $key . '): ' . curl_error($ch));
            } else {
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $response = (string) curl_multi_getcontent($ch);
                if ($httpCode === 200) {
                    $payload = json_decode($response, true);
                    if (is_array($payload)) {
                        $out[$key] = [
                            'ok' => true,
                            'http_code' => $httpCode,
                            'error' => null,
                            'payload' => $payload,
                        ];
                    } else {
                        $out[$key] = [
                            'ok' => false,
                            'http_code' => $httpCode,
                            'error' => 'Invalid JSON response',
                            'payload' => null,
                        ];
                    }
                } else {
                    $out[$key] = [
                        'ok' => false,
                        'http_code' => $httpCode,
                        'error' => 'HTTP ' . $httpCode,
                        'payload' => null,
                    ];
                    error_log('VendorReferenceCache HTTP (' . $key . '): ' . $httpCode . ' - ' . $response);
                }
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        return $out;
    }

    private function upsert(
        string $cacheKey,
        array $payload,
        string $syncStatus,
        ?int $httpCode,
        ?string $errorMessage,
        ?int $updatedByUserId,
        string $syncedAt
    ): void {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return;
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO vendor_reference_cache
                (cache_key, payload_json, synced_at, sync_status, http_code, error_message, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                payload_json = VALUES(payload_json),
                synced_at = VALUES(synced_at),
                sync_status = VALUES(sync_status),
                http_code = VALUES(http_code),
                error_message = VALUES(error_message),
                updated_by = VALUES(updated_by)'
        );
        if (!$stmt) {
            error_log('VendorReferenceCache upsert prepare failed: ' . $this->conn->error);
            return;
        }

        $updatedBy = $updatedByUserId ?? 0;
        $httpCodeVal = $httpCode ?? 0;

        $stmt->bind_param(
            'ssssisi',
            $cacheKey,
            $json,
            $syncedAt,
            $syncStatus,
            $httpCodeVal,
            $errorMessage,
            $updatedBy
        );
        if (!$stmt->execute()) {
            error_log('VendorReferenceCache upsert failed: ' . $stmt->error);
        }
        $stmt->close();
    }

    private function markError(
        string $cacheKey,
        int $httpCode,
        string $errorMessage,
        ?int $updatedByUserId,
        string $syncedAt
    ): void {
        $existing = $this->getRawRow($cacheKey);
        if ($existing && ($existing['sync_status'] ?? '') === 'ok') {
            $stmt = $this->conn->prepare(
                'UPDATE vendor_reference_cache
                 SET sync_status = ?, http_code = ?, error_message = ?, updated_by = ?, synced_at = ?
                 WHERE cache_key = ?'
            );
            if (!$stmt) {
                return;
            }
            $status = 'error';
            $updatedBy = $updatedByUserId ?? 0;
            $stmt->bind_param('sisiss', $status, $httpCode, $errorMessage, $updatedBy, $syncedAt, $cacheKey);
            $stmt->execute();
            $stmt->close();
            return;
        }

        $this->upsert($cacheKey, [], 'error', $httpCode ?: null, $errorMessage, $updatedByUserId, $syncedAt);
    }

    private function getRawRow(string $cacheKey): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT cache_key, payload_json, sync_status FROM vendor_reference_cache WHERE cache_key = ? LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $cacheKey);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}
