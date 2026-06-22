<?php

/**
 * Cookie cache for inbound list filter dropdowns (vendors, users, groups).
 * Reduces repeated DISTINCT queries on every list page load.
 */

if (!defined('INBOUND_FILTER_DD_COOKIE')) {
    define('INBOUND_FILTER_DD_COOKIE', 'vp_inbound_filter_dd');
}

if (!defined('INBOUND_FILTER_DD_TTL')) {
    $ttlEnv = getenv('EXOTIC_INBOUND_FILTER_DD_TTL');
    define('INBOUND_FILTER_DD_TTL', $ttlEnv !== false && $ttlEnv !== '' ? (int) $ttlEnv : 300);
}

if (!defined('INBOUND_FILTER_DD_MAX_COOKIE_BYTES')) {
    define('INBOUND_FILTER_DD_MAX_COOKIE_BYTES', 4000);
}

/**
 * @return array{vendors: array, users: array, groups: array, updated_users: array}|null
 */
function inbound_filter_dropdowns_from_cookie(): ?array
{
    $name = INBOUND_FILTER_DD_COOKIE;
    if (empty($_COOKIE[$name]) || !is_string($_COOKIE[$name])) {
        return null;
    }

    $encoded = $_COOKIE[$name];
    $raw = inbound_filter_dropdown_decode_payload($encoded);
    if ($raw === null) {
        return null;
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload) || !isset($payload['exp'], $payload['data'])) {
        return null;
    }

    if ((int) $payload['exp'] < time()) {
        return null;
    }

    $data = $payload['data'];
    if (!is_array($data)) {
        return null;
    }

    foreach (['vendors', 'users', 'groups', 'updated_users'] as $key) {
        if (!isset($data[$key]) || !is_array($data[$key])) {
            return null;
        }
    }

    return $data;
}

/**
 * @param array{vendors: array, users: array, groups: array, updated_users: array} $data
 */
function inbound_filter_dropdowns_to_cookie(array $data): void
{
    if (headers_sent()) {
        return;
    }

    $payload = json_encode([
        'exp' => time() + INBOUND_FILTER_DD_TTL,
        'data' => [
            'vendors' => $data['vendors'] ?? [],
            'users' => $data['users'] ?? [],
            'groups' => $data['groups'] ?? [],
            'updated_users' => $data['updated_users'] ?? [],
        ],
    ], JSON_UNESCAPED_UNICODE);

    if ($payload === false) {
        return;
    }

    $encoded = inbound_filter_dropdown_encode_payload($payload);
    if ($encoded === null || strlen($encoded) > INBOUND_FILTER_DD_MAX_COOKIE_BYTES) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie(
        INBOUND_FILTER_DD_COOKIE,
        $encoded,
        [
            'expires' => time() + INBOUND_FILTER_DD_TTL,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

/**
 * @return array{vendors: array, users: array, groups: array, updated_users: array}
 */
function inbound_filter_dropdowns_get(Inbounding $model): array
{
    $cached = inbound_filter_dropdowns_from_cookie();
    if ($cached !== null) {
        return $cached;
    }

    $data = $model->getFilterDropdowns();
    inbound_filter_dropdowns_to_cookie($data);

    return $data;
}

function inbound_filter_dropdown_encode_payload(string $payload): ?string
{
    $encoded = base64_encode($payload);
    if (strlen($encoded) <= INBOUND_FILTER_DD_MAX_COOKIE_BYTES) {
        return $encoded;
    }

    if (!function_exists('gzcompress')) {
        return null;
    }

    $compressed = gzcompress($payload, 6);
    if ($compressed === false) {
        return null;
    }

    $encoded = 'z:' . base64_encode($compressed);
    if (strlen($encoded) > INBOUND_FILTER_DD_MAX_COOKIE_BYTES) {
        return null;
    }

    return $encoded;
}

function inbound_filter_dropdown_decode_payload(string $encoded): ?string
{
    if (strncmp($encoded, 'z:', 2) === 0) {
        if (!function_exists('gzuncompress')) {
            return null;
        }
        $decoded = base64_decode(substr($encoded, 2), true);
        if ($decoded === false) {
            return null;
        }
        $raw = gzuncompress($decoded);
        return $raw === false ? null : $raw;
    }

    $raw = base64_decode($encoded, true);
    return $raw === false ? null : $raw;
}
