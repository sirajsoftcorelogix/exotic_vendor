<?php

/**
 * Map publisher master fields to vendor master (vp_vendors) when creating a linked vendor.
 *
 * Shared / mapped:
 *   publishers              → vendor_name
 *   website                 → website
 *   contact_name            → contact_name (falls back to publisher name)
 *   publisher_email         → vendor_email
 *   publisher_email_is_primary → vendor_email_is_primary
 *   country_code            → country_code (defaults 91)
 *   publisher_phone         → vendor_phone
 *   publisher_phone_is_whatsapp → vendor_phone_is_whatsapp
 *   alt_phones              → alt_phones (vendor_phones)
 *   alt_emails              → alt_emails (vendor_emails)
 *   gst_number              → gst_number
 *   pan_number              → pan_number
 *   address                 → address
 *   city                    → city
 *   state                   → state
 *   country                 → country
 *   postal_code             → postal_code
 *   stock_replenishment_months → stock_replenishment_months
 *   discount                → discount
 *   webpage                 → addWebpage / Exotic India webpage flag
 *   is_active               → is_active (active/inactive)
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

    $altPhones = [];
    $rawPhones = $extra['alt_phones'] ?? [];
    if (is_array($rawPhones)) {
        foreach ($rawPhones as $alt) {
            if (!is_array($alt)) {
                continue;
            }
            $candidate = preg_replace('/\D+/', '', trim((string) ($alt['phone'] ?? '')));
            if (strlen($candidate) > 10) {
                $candidate = substr($candidate, 0, 10);
            }
            if ($candidate === '' || $candidate === $phone) {
                continue;
            }
            $altPhones[] = [
                'phone' => $candidate,
                'is_whatsapp' => !empty($alt['is_whatsapp']) ? 1 : 0,
            ];
            if (count($altPhones) >= 5) {
                break;
            }
        }
    }

    $altEmails = [];
    $rawEmails = $extra['alt_emails'] ?? [];
    if (is_array($rawEmails)) {
        foreach ($rawEmails as $alt) {
            if (!is_array($alt)) {
                continue;
            }
            $email = trim((string) ($alt['email'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $altEmails[] = [
                'email' => $email,
                'is_primary' => !empty($alt['is_primary']) ? 1 : 0,
            ];
            if (count($altEmails) >= 5) {
                break;
            }
        }
    }

    $payload = [
        'addVendorName' => $publisherName,
        'addContactPerson' => $contactName,
        'website' => trim((string) ($extra['website'] ?? '')),
        'addEmail' => trim((string) ($extra['publisher_email'] ?? '')),
        'addCountryCode' => $countryCode,
        'addPhone' => $phone,
        'addGstNumber' => trim((string) ($extra['gst_number'] ?? '')),
        'addPanNumber' => trim((string) ($extra['pan_number'] ?? '')),
        'addAddress' => trim((string) ($extra['address'] ?? '')),
        'addCity' => trim((string) ($extra['city'] ?? '')),
        'addState' => trim((string) ($extra['state'] ?? '')),
        'addCountry' => trim((string) ($extra['country'] ?? '')) !== '' ? trim((string) $extra['country']) : 'India',
        'addPostalCode' => trim((string) ($extra['postal_code'] ?? '')),
        'addRating' => '',
        'addNotes' => '',
        'addStatus' => $isActive === 1 ? 'active' : 'inactive',
        'groupname' => ['book'],
        'addWebpage' => (string) ($extra['webpage'] ?? '0') === '1' ? '1' : '0',
        'stock_replenishment_months' => $extra['stock_replenishment_months'] ?? '',
        'discount' => $extra['discount'] ?? '',
        'addTeam' => 0,
        'addTeamMember' => 0,
    ];

    if (!empty($extra['publisher_email_is_primary'])) {
        $payload['vendor_email_is_primary'] = '1';
    }
    if (!empty($extra['publisher_phone_is_whatsapp'])) {
        $payload['vendor_phone_is_whatsapp'] = '1';
    }
    if ($altPhones !== []) {
        $payload['alt_phones'] = $altPhones;
    }
    if ($altEmails !== []) {
        $payload['alt_emails'] = $altEmails;
    }

    return $payload;
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
