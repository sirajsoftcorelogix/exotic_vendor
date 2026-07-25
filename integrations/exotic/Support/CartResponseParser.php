<?php

/**
 * Parse Exotic retail cart API responses (https://www.exoticindia.com/api/cart/*).
 */
class CartResponseParser
{
    /**
     * @param array{data?: mixed, code?: int, raw?: string} $res
     */
    public static function isSuccess(array $res): bool
    {
        $c = (int) ($res['code'] ?? 0);
        if ($c < 200 || $c >= 300) {
            return false;
        }
        $d = $res['data'] ?? [];
        if (!is_array($d)) {
            return true;
        }
        if (array_key_exists('success', $d)) {
            $sv = $d['success'];
            if ($sv === false || $sv === 0 || $sv === '0' || $sv === 'false' || $sv === 'False') {
                return false;
            }
        }
        if (isset($d['status'])) {
            $st = strtolower((string) $d['status']);
            if (in_array($st, ['error', 'fail', 'failed'], true)) {
                return false;
            }
        }
        if (isset($d['error'])) {
            $ev = $d['error'];
            if ($ev === true) {
                return false;
            }
            if (is_string($ev) && trim($ev) !== '') {
                return false;
            }
            if (is_array($ev) && $ev !== []) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array{data?: mixed, raw?: string} $res
     */
    public static function extractUserMessage(array $res): string
    {
        $d = $res['data'] ?? null;
        if (is_array($d)) {
            $msg = self::extractMessageFromAssoc($d, 0);
            if ($msg !== '') {
                return $msg;
            }
        }
        $raw = trim((string) ($res['raw'] ?? ''));
        if ($raw !== '' && strpos($raw, '{') !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $msg = self::extractMessageFromAssoc($decoded, 0);
                if ($msg !== '') {
                    return $msg;
                }
            }
        }
        if ($raw !== '' && strlen($raw) < 400 && strpos($raw, '<') === false) {
            return $raw;
        }

        return '';
    }

    /**
     * @param array{data?: mixed, code?: int, raw?: string} $res
     * @return array<string, mixed>
     */
    public static function compactUpstreamSnapshot(array $res): array
    {
        $raw = (string) ($res['raw'] ?? '');
        if (strlen($raw) > 65536) {
            $raw = substr($raw, 0, 65536) . '…(truncated)';
        }

        return [
            'http_code' => (int) ($res['code'] ?? 0),
            'success_evaluated' => self::isSuccess($res),
            'message_extracted' => self::extractUserMessage($res),
            'data' => $res['data'] ?? [],
            'raw' => $raw,
        ];
    }

    /**
     * @param array<string, string|int|float> $queryParams
     */
    public static function cartAddPublicUrl(array $queryParams, string $baseUrl = 'https://www.exoticindia.com/api'): string
    {
        $url = rtrim($baseUrl, '/') . '/cart/add';
        if ($queryParams !== []) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($queryParams);
        }

        return $url;
    }

    /**
     * @param array{data?: mixed, code?: int, raw?: string} $res
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    public static function buildEmitPayload(array $res, array $extra = []): array
    {
        $raw = (string) ($res['raw'] ?? '');
        if (strlen($raw) > 65536) {
            $raw = substr($raw, 0, 65536) . '…(truncated)';
        }
        $ok = self::isSuccess($res);
        $msg = self::extractUserMessage($res);
        if (!$ok && $msg === '' && $raw !== '' && strpos(trim($raw), '<') !== false) {
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($raw)));
            if (strlen($plain) >= 12 && strlen($plain) <= 4000) {
                $msg = $plain;
            }
        }
        if (!$ok && $msg === '') {
            $msg = 'Cart request failed (HTTP ' . (int) ($res['code'] ?? 0) . ').';
        }

        return array_merge([
            'success' => $ok,
            'message' => $msg,
            'http_code' => (int) ($res['code'] ?? 0),
            'data' => $res['data'] ?? [],
            'raw' => $raw,
        ], $extra);
    }

    /**
     * @param array<string, mixed> $arr
     */
    private static function extractMessageFromAssoc(array $arr, int $depth = 0): string
    {
        if ($depth > 10) {
            return '';
        }

        return self::humanizeMixedValue($arr, $depth);
    }

    /**
     * @param mixed $value
     */
    private static function humanizeMixedValue($value, int $depth = 0): string
    {
        if ($depth > 10) {
            return '';
        }
        if (is_string($value)) {
            return trim($value);
        }
        if (is_int($value) || is_float($value)) {
            return trim((string) $value);
        }
        if (!is_array($value)) {
            return '';
        }
        if ($value === []) {
            return '';
        }
        if (array_keys($value) === range(0, count($value) - 1)) {
            $parts = [];
            foreach ($value as $item) {
                $s = self::humanizeMixedValue($item, $depth + 1);
                if ($s !== '') {
                    $parts[] = $s;
                }
                if (count($parts) >= 5) {
                    break;
                }
            }

            return implode('; ', $parts);
        }
        $msgKeys = [
            'message', 'Message', 'error', 'Error', 'errormessage', 'msg', 'reason', 'detail',
            'description', 'error_description', 'title', 'text', 'errorMessage',
            'UserMessage', 'userMessage', 'statusMessage', 'StatusMessage', 'exceptionMessage',
        ];
        foreach ($msgKeys as $k) {
            if (!array_key_exists($k, $value)) {
                continue;
            }
            $s = self::humanizeMixedValue($value[$k], $depth + 1);
            if ($s !== '') {
                return $s;
            }
        }
        foreach (['errors', 'Errors', 'validation', 'ValidationErrors'] as $ek) {
            if (empty($value[$ek])) {
                continue;
            }
            $s = self::humanizeMixedValue($value[$ek], $depth + 1);
            if ($s !== '') {
                return $s;
            }
        }
        foreach (['data', 'result', 'payload', 'response'] as $wrap) {
            if (empty($value[$wrap]) || !is_array($value[$wrap])) {
                continue;
            }
            $inner = self::extractMessageFromAssoc($value[$wrap], $depth + 1);
            if ($inner !== '') {
                return $inner;
            }
        }
        foreach ($value as $sub) {
            if (!is_array($sub)) {
                continue;
            }
            $nested = self::humanizeMixedValue($sub, $depth + 1);
            if ($nested !== '') {
                return $nested;
            }
        }
        foreach ($value as $item) {
            if (!is_string($item)) {
                continue;
            }
            $t = trim($item);
            if ($t !== '') {
                return $t;
            }
        }

        return '';
    }
}
