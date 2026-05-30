<?php

/**
 * Resolve country names / codes to ISO-2 using the `countries` table.
 *
 * Table columns used: name, country_code
 */

/** @var array{by_name: array<string, string>, by_code: array<string, string>}|null */
$GLOBALS['_courier_country_maps'] = null;

/**
 * @return array{by_name: array<string, string>, by_code: array<string, string>}
 */
function courierCountryMaps($conn = null): array
{
    if (is_array($GLOBALS['_courier_country_maps'] ?? null)) {
        return $GLOBALS['_courier_country_maps'];
    }

    $maps = ['by_name' => [], 'by_code' => []];
    $conn = courierCountryConnection($conn);
    if (!$conn) {
        $GLOBALS['_courier_country_maps'] = $maps;
        return $maps;
    }

    $res = $conn->query(
        "SELECT name, country_code
         FROM countries
         WHERE country_code IS NOT NULL AND TRIM(country_code) <> ''"
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $code = strtoupper(trim((string) ($row['country_code'] ?? '')));
            $name = strtoupper(trim((string) ($row['name'] ?? '')));
            if ($code === '') {
                continue;
            }
            $maps['by_code'][$code] = $code;
            if ($name !== '') {
                $maps['by_name'][$name] = $code;
            }
        }
        $res->free();
    }

    $GLOBALS['_courier_country_maps'] = $maps;
    return $maps;
}

/** Reset cached maps (tests or after bulk country imports). */
function resetCourierCountryMapsCache(): void
{
    $GLOBALS['_courier_country_maps'] = null;
}

/**
 * @param mysqli|object|null $conn
 * @return mysqli|null
 */
function courierCountryConnection($conn = null)
{
    if ($conn instanceof mysqli) {
        return $conn;
    }
    $global = $GLOBALS['conn'] ?? null;
    return $global instanceof mysqli ? $global : null;
}

/**
 * @param mysqli|object|null $conn
 */
function normalizeCountryIso2($country, $conn = null): string
{
    $raw = trim((string) $country);
    if ($raw === '') {
        return 'IN';
    }

    $upper = strtoupper($raw);
    if (strlen($upper) === 2 && ctype_alpha($upper)) {
        $maps = courierCountryMaps($conn);
        return $maps['by_code'][$upper] ?? $upper;
    }

    if (strlen($upper) === 3 && $upper === 'IND') {
        return 'IN';
    }

    $maps = courierCountryMaps($conn);
    if (isset($maps['by_name'][$upper])) {
        return $maps['by_name'][$upper];
    }
    if (isset($maps['by_code'][$upper])) {
        return $maps['by_code'][$upper];
    }

    $fromDb = lookupCountryCodeFromDb($upper, $conn);
    if ($fromDb !== '') {
        return $fromDb;
    }

    return $upper;
}

/**
 * @param mysqli|object|null $conn
 */
function lookupCountryCodeFromDb(string $upper, $conn = null): string
{
    $conn = courierCountryConnection($conn);
    if (!$conn || $upper === '') {
        return '';
    }

    $stmt = $conn->prepare(
        'SELECT country_code
         FROM countries
         WHERE UPPER(TRIM(country_code)) = ?
            OR UPPER(TRIM(name)) = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return '';
    }
    $stmt->bind_param('ss', $upper, $upper);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    $code = strtoupper(trim((string) ($row['country_code'] ?? '')));
    if ($code !== '') {
        $maps = courierCountryMaps($conn);
        $maps['by_code'][$code] = $code;
        $maps['by_name'][$upper] = $code;
        $GLOBALS['_courier_country_maps'] = $maps;
    }

    return $code;
}

function isInternationalShipmentCountry($country, $conn = null): bool
{
    return normalizeCountryIso2($country, $conn) !== 'IN';
}

/**
 * @param mysqli|object|null $conn
 * @return array{id:int,name:string,country_code:string,phone_code:?string}|null
 */
function getCountryByIso2(string $iso2, $conn = null): ?array
{
    $iso2 = strtoupper(trim($iso2));
    if ($iso2 === '') {
        return null;
    }

    $conn = courierCountryConnection($conn);
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare(
        'SELECT id, name, country_code, phone_code
         FROM countries
         WHERE UPPER(TRIM(country_code)) = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $iso2);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}
