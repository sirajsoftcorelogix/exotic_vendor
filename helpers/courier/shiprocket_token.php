<?php

/**
 * @param array<string, mixed> $credentials
 */
function extractShiprocketToken(array $credentials): string
{
    foreach (['token', 'api_token', 'auth_token', 'access_token', 'bearer_token'] as $key) {
        $token = normalizeShiprocketBearerToken((string) ($credentials[$key] ?? ''));
        if ($token !== '') {
            return $token;
        }
    }

    return '';
}

function normalizeShiprocketBearerToken(string $token): string
{
    $token = trim($token);
    if ($token === '') {
        return '';
    }

    if (stripos($token, 'bearer ') === 0) {
        $token = trim(substr($token, 7));
    }

    return trim($token, "\"' \t\n\r\0\x0B");
}
