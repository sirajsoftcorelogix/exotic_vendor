<?php

/**
 * One JSON file per outbound API call; prunes oldest files when count exceeds limit.
 * Logs UTC + configured app timezone timestamps and redacts sensitive headers.
 */

if (!defined('API_CALL_LOG_DIR')) {
    define('API_CALL_LOG_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'api_calls');
}

if (!defined('API_CALL_LOG_MAX_FILES')) {
    define('API_CALL_LOG_MAX_FILES', 100);
}

if (!defined('API_CALL_LOG_ENABLED')) {
    define('API_CALL_LOG_ENABLED', true);
}

/**
 * Header names whose values must not be stored verbatim.
 */
function api_call_log_sensitive_header_names(): array
{
    return [
        'x-api-key',
        'x-api-jwt',
        'authorization',
        'cookie',
        'set-cookie',
    ];
}

/**
 * @param list<string> $headerLines "Name: value"
 * @return list<string>
 */
function api_call_log_sanitize_header_lines(array $headerLines): array
{
    $sensitive = api_call_log_sensitive_header_names();
    $out = [];
    foreach ($headerLines as $line) {
        if (!is_string($line) || strpos($line, ':') === false) {
            $out[] = $line;
            continue;
        }
        [$name, $value] = explode(':', $line, 2);
        $lower = strtolower(trim($name));
        if (in_array($lower, $sensitive, true)) {
            $trim = trim((string) $value);
            $out[] = $name . ': ' . ($trim !== '' ? '(redacted)' : '');
        } else {
            $out[] = $line;
        }
    }

    return $out;
}

function api_call_log_ensure_dir(string $dir): bool
{
    if (is_dir($dir)) {
        return true;
    }
    return @mkdir($dir, 0755, true);
}

/**
 * Delete oldest files matching api_call_*.json until at most $maxFiles remain.
 */
function api_call_log_prune(string $dir, int $maxFiles = API_CALL_LOG_MAX_FILES): void
{
    if ($maxFiles < 1 || !is_dir($dir)) {
        return;
    }
    $pattern = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'api_call_*.json';
    $files = glob($pattern) ?: [];
    if (count($files) <= $maxFiles) {
        return;
    }
    usort($files, static function ($a, $b) {
        $ta = @filemtime($a) ?: 0;
        $tb = @filemtime($b) ?: 0;
        if ($ta === $tb) {
            return strcmp($a, $b);
        }

        return $ta <=> $tb;
    });
    $toDelete = count($files) - $maxFiles;
    for ($i = 0; $i < $toDelete; $i++) {
        @unlink($files[$i]);
    }
}

/**
 * Safe filename fragment from API endpoint.
 */
function api_call_log_endpoint_slug(string $endpoint): string
{
    $s = trim((string) $endpoint, '/');
    $s = str_replace(['/', '\\', '?', '&', ':'], '_', $s);
    $s = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $s);
    $s = trim((string) $s, '_');
    if ($s === '') {
        $s = 'call';
    }

    return substr($s, 0, 80);
}

/**
 * @param array<string, mixed> $payload
 */
function api_call_log_write(array $payload): void
{
    if (!API_CALL_LOG_ENABLED) {
        return;
    }
    $dir = API_CALL_LOG_DIR;
    if (!api_call_log_ensure_dir($dir)) {
        return;
    }

    $tzApp = date_default_timezone_get() ?: 'UTC';
    try {
        $nowUtc = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    } catch (\Throwable $e) {
        $nowUtc = new DateTimeImmutable('now');
    }
    try {
        $nowLocal = $nowUtc->setTimezone(new DateTimeZone($tzApp));
    } catch (\Throwable $e) {
        $nowLocal = $nowUtc;
    }

    $payload['logged_at_utc'] = $nowUtc->format('Y-m-d H:i:s.v \U\T\C');
    $payload['logged_at_local'] = $nowLocal->format('Y-m-d H:i:s.v') . ' (' . $tzApp . ')';

    $slug = api_call_log_endpoint_slug((string) ($payload['endpoint'] ?? 'unknown'));
    $uniq = substr(bin2hex(random_bytes(4)), 0, 8);
    $file = $dir . DIRECTORY_SEPARATOR . sprintf(
        'api_call_%s_%s_%s.json',
        $nowUtc->format('Ymd_His'),
        $uniq,
        $slug
    );

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRETTY_PRINT);
    if ($json === false) {
        $json = '{"error":"json_encode_failed"}';
    }
    @file_put_contents($file, $json . "\n", LOCK_EX);

    api_call_log_prune($dir, (int) API_CALL_LOG_MAX_FILES);
}
