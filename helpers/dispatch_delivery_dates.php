<?php

/**
 * Normalize courier ETD/EDD values for vp_dispatch_details (DATETIME columns).
 *
 * @param mixed $value Raw value from courier API or UI
 * @param string|null $baseDate Dispatch / reference date (Y-m-d H:i:s)
 */
function normalizeDispatchDeliveryDatetime($value, ?string $baseDate = null): ?string
{
    $value = trim((string) $value);
    if ($value === '' || in_array(strtolower($value), ['n/a', 'na', '—', '-', 'null', 'none'], true)) {
        return null;
    }

    $baseTs = $baseDate !== null && $baseDate !== '' ? strtotime($baseDate) : false;
    if ($baseTs === false) {
        $baseTs = time();
    }

    $hasRelativeUnit = (bool) preg_match('/\b(day|days|hour|hours|hr|hrs)\b/i', $value);
    $isDayRange = (bool) preg_match('/\d+\s*-\s*\d+/', $value);

    if (!$hasRelativeUnit && !$isDayRange) {
        $ts = strtotime($value);
        if ($ts !== false) {
            return date('Y-m-d H:i:s', $ts);
        }
    }

    if (preg_match_all('/\d+/', $value, $matches) && !empty($matches[0])) {
        $nums = array_map('intval', $matches[0]);
        $days = (int) ceil(array_sum($nums) / count($nums));
        if ($days > 0 && $days < 366) {
            return date('Y-m-d H:i:s', strtotime('+' . $days . ' days', $baseTs));
        }
    }

    return null;
}

/**
 * @param array<string, mixed> $source
 * @return array{etd:?string,edd:?string}
 */
function extractDispatchDeliveryDates(array $source, ?string $baseDate = null): array
{
    $etd = null;
    $edd = null;

    $candidates = [
        ['etd', 'edd'],
        ['courier_etd', 'courier_edd'],
        ['estimated_delivery_date', 'expected_delivery_date'],
    ];

    foreach ($candidates as [$etdKey, $eddKey]) {
        if ($etd === null && array_key_exists($etdKey, $source) && $source[$etdKey] !== null && $source[$etdKey] !== '') {
            $etd = normalizeDispatchDeliveryDatetime($source[$etdKey], $baseDate);
        }
        if ($edd === null && array_key_exists($eddKey, $source) && $source[$eddKey] !== null && $source[$eddKey] !== '') {
            $edd = normalizeDispatchDeliveryDatetime($source[$eddKey], $baseDate);
        }
    }

    if (isset($source['tracking_data']) && is_array($source['tracking_data'])) {
        $nested = extractDispatchDeliveryDates($source['tracking_data'], $baseDate);
        $etd = $etd ?? $nested['etd'];
        $edd = $edd ?? $nested['edd'];
    }

    if (isset($source['shipment_track']) && is_array($source['shipment_track'])) {
        foreach ($source['shipment_track'] as $track) {
            if (!is_array($track)) {
                continue;
            }
            if ($edd === null && !empty($track['edd'])) {
                $edd = normalizeDispatchDeliveryDatetime($track['edd'], $baseDate);
            }
            if ($etd === null && !empty($track['etd'])) {
                $etd = normalizeDispatchDeliveryDatetime($track['etd'], $baseDate);
            }
        }
    }

    if (isset($source['metadata']) && is_array($source['metadata'])) {
        $nested = extractDispatchDeliveryDates($source['metadata'], $baseDate);
        $etd = $etd ?? $nested['etd'];
        $edd = $edd ?? $nested['edd'];
    }

    return ['etd' => $etd, 'edd' => $edd];
}

/**
 * @param array<string, mixed> $payload
 * @param array<int, array<string, mixed>> $sources
 * @return array<string, mixed>
 */
function mergeDispatchDeliveryDates(array $payload, array $sources, ?string $baseDate = null): array
{
    $base = $baseDate ?? (string) ($payload['dispatch_date'] ?? date('Y-m-d H:i:s'));

    foreach ($sources as $source) {
        if (!is_array($source)) {
            continue;
        }
        $dates = extractDispatchDeliveryDates($source, $base);
        if (!array_key_exists('etd', $payload) && $dates['etd'] !== null) {
            $payload['etd'] = $dates['etd'];
        }
        if (!array_key_exists('edd', $payload) && $dates['edd'] !== null) {
            $payload['edd'] = $dates['edd'];
        }
    }

    return $payload;
}
