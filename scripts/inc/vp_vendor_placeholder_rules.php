<?php
/**
 * Shared rules for detecting junk vp_vendors.vendor_name values (geo/city/state/country, empty, etc.).
 * Used by clean_vp_vendors_placeholder_names.php and dedupe_vp_vendors_by_name.php.
 *
 * Rows whose name matches the geo/city/state list are NOT treated as placeholders if they have any
 * non-empty vendor_email, vendor_phone, or alt_phone (likely a real vendor with a bad display name).
 */
declare(strict_types=1);

function vp_vendor_placeholder_blocklist(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = array_fill_keys(array_map('strtolower', [
        // Country / generic
        'india', 'bharat', 'hindustan',
        // States & UTs (common spellings)
        'andhra pradesh', 'arunachal pradesh', 'assam', 'bihar', 'chhattisgarh', 'goa', 'gujarat',
        'haryana', 'himachal pradesh', 'jharkhand', 'karnataka', 'kerala', 'madhya pradesh',
        'maharashtra', 'manipur', 'meghalaya', 'mizoram', 'nagaland', 'odisha', 'orissa', 'punjab',
        'rajasthan', 'sikkim', 'tamil nadu', 'telangana', 'tripura', 'uttar pradesh', 'uttarakhand',
        'west bengal', 'delhi', 'new delhi', 'chandigarh', 'puducherry', 'pondicherry', 'jammu and kashmir',
        'jammu & kashmir', 'ladakh', 'andaman and nicobar', 'lakshadweep', 'dadra and nagar haveli',
        'daman and diu',
        // User examples & frequent city-as-name mistakes
        'agra', 'allahabad', 'prayagraj', 'bangalore', 'bengaluru', 'bengalore',
        'mumbai', 'chennai', 'kolkata', 'calcutta', 'hyderabad', 'pune', 'ahmedabad', 'jaipur',
        'lucknow', 'kanpur', 'nagpur', 'indore', 'thane', 'bhopal', 'visakhapatnam', 'patna',
        'vadodara', 'ghaziabad', 'ludhiana', 'nashik', 'faridabad', 'meerut', 'rajkot',
        'varanasi', 'benares', 'kashi', 'srinagar', 'amritsar', 'coimbatore',
        'kochi', 'ernakulam', 'mysore', 'mysuru', 'vijayawada', 'gwalior', 'ranchi', 'guwahati',
        'howrah', 'jabalpur', 'vasai', 'navi mumbai', 'solapur', 'hubli', 'hubballi',
        'janakpuri', 'rohini', 'dwarka', 'karol bagh', 'connaught place',
        'surat', 'bhavnagar', 'jamnagar', 'udaipur', 'ajmer', 'jodhpur',
        'kota', 'bikaner', 'alwar', 'bharatpur', 'sikar', 'pali', 'tonk',
        'mirzapur', 'moradabad', 'bareilly', 'aligarh', 'saharanpur', 'mathura', 'firozabad',
        'jhansi', 'shahjahanpur', 'rampur', 'modinagar', 'hapur', 'etawah', 'bahraich',
        'puri', 'bhubaneswar', 'bhubaneshwar', 'cuttack', 'rourkela', 'berhampur',
        'madurai', 'salem', 'tiruchirappalli', 'erode', 'vellore', 'thanjavur',
        'tirunelveli', 'kanyakumari', 'kumbakonam', 'hosur', 'karur', 'nagercoil',
        'dehradun', 'haridwar', 'roorkee', 'haldwani', 'rudrapur',
        'siliguri', 'asansol', 'durgapur', 'bardhaman', 'malda', 'howrah', 'darjeeling',
        'gangtok', 'shillong', 'aizawl', 'kohima', 'itanagar', 'dispur', 'imphal',
        // Typos / region words alone
        'south india', 'north india', 'east india', 'west india', 'central india',
    ]), true);

    return $cache;
}

/**
 * @return array{reason: string}|null null = treat as a normal vendor name
 */
function classify_placeholder_vendor_name(string $vendorName, ?array $geoBlocklist = null): ?array
{
    $geoBlocklist ??= vp_vendor_placeholder_blocklist();

    $t = trim($vendorName);
    if ($t === '') {
        return ['reason' => 'empty_name'];
    }
    if ($t === '0' || strcasecmp($t, 'null') === 0) {
        return ['reason' => 'literal_zero_or_null'];
    }
    if (preg_match('/^[0-9]+$/', $t)) {
        return ['reason' => 'digits_only'];
    }
    $lower = mb_strtolower($t, 'UTF-8');
    if (isset($geoBlocklist[$lower])) {
        return ['reason' => 'geo_blocklist'];
    }

    return null;
}

function vp_vendor_row_has_meaningful_contact(?string $email, ?string $phone, ?string $altPhone = null): bool
{
    foreach ([$email, $phone, $altPhone] as $v) {
        if ($v !== null && trim((string) $v) !== '') {
            return true;
        }
    }

    return false;
}

/**
 * Same as classify_placeholder_vendor_name except geo_blocklist hits are ignored when email or phone exists.
 *
 * @return array{reason: string}|null
 */
function classify_placeholder_vendor_name_respecting_contact(
    string $vendorName,
    ?string $vendorEmail,
    ?string $vendorPhone,
    ?string $altPhone = null,
    ?array $geoBlocklist = null
): ?array {
    $c = classify_placeholder_vendor_name($vendorName, $geoBlocklist);
    if ($c === null) {
        return null;
    }
    if (($c['reason'] ?? '') === 'geo_blocklist'
        && vp_vendor_row_has_meaningful_contact($vendorEmail, $vendorPhone, $altPhone)) {
        return null;
    }

    return $c;
}
