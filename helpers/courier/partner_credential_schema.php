<?php

/**
 * Partner credential schema helpers.
 *
 * Runtime uses DB only (courier_partner_accounts.credentials_json).
 * partner_credential_schemas.php may include a `reference` block for developers — never loaded here.
 */

/** @return array<string, array<string, mixed>> */
function getPartnerCredentialSchemas(): array
{
    static $schemas = null;
    if ($schemas === null) {
        $schemas = require __DIR__ . '/partner_credential_schemas.php';
    }

    return $schemas;
}

/**
 * Schemas safe for admin UI (strips file-only reference credentials from page output).
 *
 * @return array<string, array<string, mixed>>
 */
function getPartnerCredentialSchemasForUi(): array
{
    $schemas = getPartnerCredentialSchemas();
    $forUi = [];

    foreach ($schemas as $code => $schema) {
        if (!is_array($schema)) {
            continue;
        }
        unset($schema['reference']);
        $forUi[$code] = $schema;
    }

    return $forUi;
}

/**
 * Empty JSON skeleton for "Load partner template" in Courier accounts UI.
 *
 * @return array<string, mixed>
 */
function getPartnerCredentialSkeleton(string $partnerCode): array
{
    $schemas = getPartnerCredentialSchemas();
    $code = strtolower(trim($partnerCode));
    $fields = $schemas[$code]['fields'] ?? $schemas['_default']['fields'] ?? [];

    if (!is_array($fields)) {
        return [];
    }

    $copy = json_decode(json_encode($fields), true);
    return is_array($copy) ? $copy : [];
}

function isPartnerProductionOnly(string $partnerCode): bool
{
    $schemas = getPartnerCredentialSchemas();
    $code = strtolower(trim($partnerCode));

    return !empty($schemas[$code]['production_only']);
}
