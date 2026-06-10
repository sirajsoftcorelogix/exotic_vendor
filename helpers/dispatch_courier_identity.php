<?php

require_once __DIR__ . '/../models/courier/CourierPartner.php';

function dispatchCourierNameIsPlaceholder(?string $name): bool
{
    $normalized = strtolower(trim((string) $name));

    return $normalized === '' || in_array($normalized, ['shiprocket', 'n/a', 'na', 'unknown', 'pending'], true);
}

/** Booking platform from UI context: rate_source / partner_code, default Shiprocket. */
function resolveDispatchBookingPartnerCode(array $context, array $payload = []): string
{
    foreach (['rate_source', 'partner_code'] as $key) {
        $code = strtoupper(str_replace(' ', '', trim((string) ($context[$key] ?? ''))));
        if ($code !== '' && $code !== 'SHIPROCKET') {
            return $code;
        }
    }

    return 'SHIPROCKET';
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $context courier_name, partner_code, rate_source, courier_id
 * @return array<string, mixed>
 */
function mergeDispatchCourierIdentity(array $payload, $conn, array $context = []): array
{
    if (!($conn instanceof mysqli)) {
        return $payload;
    }

    $partnerModel = new CourierPartner($conn);
    $courierName = trim((string) ($context['courier_name'] ?? $payload['courier_name'] ?? ''));
    $companyId = (int) ($context['courier_company_id'] ?? $context['courier_id'] ?? $payload['courier_company_id'] ?? 0);
    $existingName = trim((string) ($payload['courier_name'] ?? ''));
    $bookingCode = resolveDispatchBookingPartnerCode($context, $payload);

    if ($courierName !== '' && (dispatchCourierNameIsPlaceholder($existingName) || $existingName === '')) {
        $payload['courier_name'] = $courierName;
    }
    if ($companyId > 0 && empty($payload['courier_company_id'])) {
        $payload['courier_company_id'] = $companyId;
    }

    if (empty($payload['courier_partner_id'])) {
        $partnerId = $partnerModel->resolvePartnerIdByCode($bookingCode);
        if ($partnerId !== null) {
            $payload['courier_partner_id'] = $partnerId;
        }
    }

    if (empty($payload['shipper_id'])) {
        $displayName = (string) ($payload['courier_name'] ?? $courierName);
        $shipperCode = $bookingCode === 'SHIPROCKET' ? null : $bookingCode;
        $shipperId = $partnerModel->resolveShipperId($displayName, $shipperCode);
        if ($shipperId !== null) {
            $payload['shipper_id'] = $shipperId;
        }
    }

    return $payload;
}

/** @param array<string, mixed> $awbInfoResponse */
function buildShiprocketAssignmentUpdate($conn, array $awbInfoResponse, array $fallback = []): array
{
    $data = is_array($awbInfoResponse['response']['data'] ?? null)
        ? $awbInfoResponse['response']['data']
        : [];

    $payload = [];
    $awb = trim((string) ($data['awb_code'] ?? $awbInfoResponse['awb_code'] ?? ''));
    if ($awb !== '') {
        $payload['awb_code'] = $awb;
    }

    $courierName = trim((string) ($data['courier_name'] ?? $fallback['courier_name'] ?? ''));
    if ($courierName !== '') {
        $payload['courier_name'] = $courierName;
    }

    $companyId = (int) ($data['courier_company_id'] ?? $fallback['courier_company_id'] ?? $fallback['courier_id'] ?? 0);
    if ($companyId > 0) {
        $payload['courier_company_id'] = $companyId;
    }

    return mergeDispatchCourierIdentity($payload, $conn, array_merge($fallback, [
        'courier_name' => $courierName,
        'partner_code' => 'shiprocket',
        'rate_source' => $fallback['rate_source'] ?? 'shiprocket',
    ]));
}
