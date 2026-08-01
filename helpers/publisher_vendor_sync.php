<?php

/**
 * Map publisher master fields to vendor master (vp_vendors) when creating a linked vendor.
 *
 * Shared / mapped:
 *   publishers          → vendor_name
 *   contact_name        → contact_name (falls back to publisher name)
 *   publisher_email     → vendor_email
 *   country_code        → country_code (defaults 91)
 *   publisher_phone     → vendor_phone
 *   alt_phones[0]       → alt_phone
 *   gst_number          → gst_number
 *   pan_number          → pan_number
 *   address             → address
 *   city                → city
 *   state               → state
 *   country             → country
 *   postal_code         → postal_code
 *   stock_replenishment_months → stock_replenishment_months
 *   discount            → discount
 *   webpage             → addWebpage / Exotic India webpage flag
 *   is_active           → is_active (active/inactive)
 *
 * Publisher-only (stored in vendor notes when present):
 *   display_name, website
 *
 * Vendor-only (defaults when creating from publisher):
 *   groupname = book, vendor_code auto, broker/team/category unset, rating optional
 */
function build_vendor_add_payload_from_publisher(string $publisherName, array $extra, int $isActive): array
{
    $publisherName = trim($publisherName);
    $contactName = trim((string) ($extra['contact_name'] ?? ''));
    if ($contactName === '') {
        $contactName = $publisherName;
    }

    $countryCode = trim((string) ($extra['country_code'] ?? ''));
    if ($countryCode === '') {
        $countryCode = '91';
    }

    $phone = preg_replace('/\D+/', '', trim((string) ($extra['publisher_phone'] ?? '')));
    if (strlen($phone) > 10) {
        $phone = substr($phone, 0, 10);
    }

    $altPhone = '';
    $altPhones = $extra['alt_phones'] ?? [];
    if (is_array($altPhones)) {
        foreach ($altPhones as $alt) {
            if (!is_array($alt)) {
                continue;
            }
            $candidate = preg_replace('/\D+/', '', trim((string) ($alt['phone'] ?? '')));
            if (strlen($candidate) > 10) {
                $candidate = substr($candidate, 0, 10);
            }
            if ($candidate !== '' && $candidate !== $phone) {
                $altPhone = $candidate;
                break;
            }
        }
    }

    $notesParts = [];
    $displayName = trim((string) ($extra['display_name'] ?? ''));
    if ($displayName !== '' && strcasecmp($displayName, $publisherName) !== 0) {
        $notesParts[] = 'Display name: ' . $displayName;
    }
    $website = trim((string) ($extra['website'] ?? ''));
    if ($website !== '') {
        $notesParts[] = 'Website: ' . $website;
    }

    return [
        'addVendorName' => $publisherName,
        'addContactPerson' => $contactName,
        'addEmail' => trim((string) ($extra['publisher_email'] ?? '')),
        'addCountryCode' => $countryCode,
        'addPhone' => $phone,
        'addAltPhone' => $altPhone,
        'addGstNumber' => trim((string) ($extra['gst_number'] ?? '')),
        'addPanNumber' => trim((string) ($extra['pan_number'] ?? '')),
        'addAddress' => trim((string) ($extra['address'] ?? '')),
        'addCity' => trim((string) ($extra['city'] ?? '')),
        'addState' => trim((string) ($extra['state'] ?? '')),
        'addCountry' => trim((string) ($extra['country'] ?? '')) !== '' ? trim((string) $extra['country']) : 'India',
        'addPostalCode' => trim((string) ($extra['postal_code'] ?? '')),
        'addRating' => '',
        'addNotes' => implode("\n", $notesParts),
        'addStatus' => $isActive === 1 ? 'active' : 'inactive',
        'groupname' => ['book'],
        'addWebpage' => (string) ($extra['webpage'] ?? '0') === '1' ? '1' : '0',
        'stock_replenishment_months' => $extra['stock_replenishment_months'] ?? '',
        'discount' => $extra['discount'] ?? '',
        'addTeam' => 0,
        'addTeamMember' => 0,
    ];
}

/**
 * @return array{success:false,message:string}|null
 */
function publisher_vendor_create_validation_error(array $vendorPayload): ?array
{
    if (trim((string) ($vendorPayload['addPhone'] ?? '')) === '') {
        return [
            'success' => false,
            'message' => 'Primary phone is required on the publisher form when "Also create vendor" is checked.',
        ];
    }

    return null;
}
