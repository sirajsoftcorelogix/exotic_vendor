<?php

/**
 * Normalize environment flag to sandbox or production.
 */
function normalizeCourierEnvironment($value): string
{
    $env = strtolower(trim((string) $value));
    if (in_array($env, ['live', 'production', 'prod'], true)) {
        return 'production';
    }
    if (in_array($env, ['test', 'sandbox', 'uat', 'staging', 'dev'], true)) {
        return 'sandbox';
    }
    return $env === 'production' ? 'production' : 'sandbox';
}

/**
 * Resolve API / WSDL URLs from credentials JSON using environment + sandbox/production URL fields.
 *
 * @return array{api_base_url:string,shipping_wsdl:string,tracking_wsdl:string,environment:string,is_production:bool}
 */
function resolveCourierCredentialUrls(array $credentials): array
{
    $env = normalizeCourierEnvironment($credentials['environment'] ?? 'sandbox');
    $isProduction = $env === 'production';
    $bucket = $isProduction ? 'production' : 'sandbox';

    $nested = $credentials['urls'][$bucket] ?? null;
    if (is_array($nested)) {
        $apiBase = trim((string) ($nested['api_base_url'] ?? ''));
        $shippingWsdl = trim((string) ($nested['shipping_wsdl'] ?? ''));
        $trackingWsdl = trim((string) ($nested['tracking_wsdl'] ?? ''));
    } else {
        $prefix = $bucket . '_';
        $apiBase = trim((string) ($credentials[$prefix . 'api_base_url'] ?? ''));
        $shippingWsdl = trim((string) ($credentials[$prefix . 'shipping_wsdl'] ?? ''));
        $trackingWsdl = trim((string) ($credentials[$prefix . 'tracking_wsdl'] ?? ''));
    }

    // Legacy single-URL keys (same value often used for both environments).
    if ($apiBase === '') {
        $apiBase = trim((string) ($credentials['api_base_url'] ?? ''));
    }
    if ($shippingWsdl === '') {
        $shippingWsdl = trim((string) ($credentials['shipping_wsdl'] ?? $credentials['wsdl'] ?? ''));
    }
    if ($trackingWsdl === '') {
        $trackingWsdl = trim((string) ($credentials['tracking_wsdl'] ?? ''));
    }

    return [
        'api_base_url' => $apiBase,
        'shipping_wsdl' => $shippingWsdl,
        'tracking_wsdl' => $trackingWsdl,
        'environment' => $env,
        'is_production' => $isProduction,
    ];
}
