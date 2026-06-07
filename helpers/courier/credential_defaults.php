<?php

/**
 * Fill missing courier credential fields from partner_credential_schemas.php templates.
 * Stored account values always win; secrets from the template are never injected.
 *
 * @param array<string, mixed> $credentials
 * @return array<string, mixed>
 */
function mergePartnerCredentialDefaults(string $partnerCode, array $credentials): array
{
    static $schemas = null;
    if ($schemas === null) {
        $schemas = require __DIR__ . '/partner_credential_schemas.php';
    }

    $code = strtolower(trim($partnerCode));
    if ($code === '' || !is_array($schemas[$code]['template'] ?? null)) {
        return $credentials;
    }

    $template = $schemas[$code]['template'];
    $secretKeys = array_flip(array_map('strval', $schemas[$code]['secret_keys'] ?? []));

    foreach ($template as $key => $defaultValue) {
        if (isset($secretKeys[$key])) {
            continue;
        }

        if (!array_key_exists($key, $credentials) || isCourierCredentialValueEmpty($credentials[$key])) {
            $credentials[$key] = $defaultValue;
            continue;
        }

        if ($key === 'pickup_location_aliases' && is_array($defaultValue) && is_array($credentials[$key])) {
            $credentials[$key] = array_merge($defaultValue, $credentials[$key]);
            continue;
        }

        if ($key === 'shipper' && is_array($defaultValue) && is_array($credentials[$key])) {
            foreach ($defaultValue as $shipperKey => $shipperValue) {
                if (
                    !array_key_exists($shipperKey, $credentials[$key])
                    || isCourierCredentialValueEmpty($credentials[$key][$shipperKey])
                ) {
                    $credentials[$key][$shipperKey] = $shipperValue;
                }
            }
        }
    }

    return $credentials;
}

/** @param mixed $value */
function isCourierCredentialValueEmpty($value): bool
{
    if ($value === null) {
        return true;
    }
    if (is_string($value) && trim($value) === '') {
        return true;
    }
    return is_array($value) && $value === [];
}
