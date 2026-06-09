<?php

require_once __DIR__ . '/bluedart_rate_helpers.php';

/**
 * Blue Dart legacy netconnect SOAP client (curl + XML, no PHP soap extension).
 *
 * Matches credentials used by eShipz / ShipDart-style integrations:
 * login_id + licence_key in Profile, no DHL Developer Portal JWT.
 */

/** @return array{waybill:string,finder:string} */
function bluedartLegacyDefaultEndpoints(): array
{
    return [
        'waybill' => 'https://netconnect.bluedart.com/Ver1.10/ShippingAPI/WayBill/WayBillGeneration.svc',
        'finder' => 'https://netconnect.bluedart.com/Ver1.10/ShippingAPI/Finder/ServiceFinderQuery.svc',
    ];
}

/**
 * @param array<string, mixed> $credentials
 * @return array{waybill:string,finder:string}
 */
function bluedartResolveLegacyEndpoints(array $credentials): array
{
    $defaults = bluedartLegacyDefaultEndpoints();
    $waybill = trim((string) ($credentials['legacy_waybill_endpoint'] ?? ''));
    if ($waybill === '') {
        $wsdl = trim((string) ($credentials['production_shipping_wsdl'] ?? $credentials['shipping_wsdl'] ?? ''));
        $waybill = $wsdl !== '' ? preg_replace('/\?wsdl$/i', '', $wsdl) : $defaults['waybill'];
    }

    $finder = trim((string) ($credentials['legacy_finder_endpoint'] ?? ''));
    if ($finder === '') {
        $finderWsdl = trim((string) ($credentials['production_finder_wsdl'] ?? $credentials['finder_wsdl'] ?? ''));
        $finder = $finderWsdl !== '' ? preg_replace('/\?wsdl$/i', '', $finderWsdl) : $defaults['finder'];
    }

    return [
        'waybill' => $waybill !== '' ? $waybill : $defaults['waybill'],
        'finder' => $finder !== '' ? $finder : $defaults['finder'],
    ];
}

/**
 * @param array<string, mixed> $credentials
 */
function bluedartUsesLegacyApi(array $credentials): bool
{
    $mode = strtolower(trim((string) ($credentials['api_mode'] ?? 'legacy')));
    if ($mode === 'legacy' || $mode === 'netconnect' || $mode === 'webservice') {
        return true;
    }
    if ($mode === 'rest' || $mode === 'gateway' || $mode === 'dhl') {
        return false;
    }

    // auto: legacy when only eShipz-style profile creds exist
    return bluedartHasProfileCredentials($credentials)
        && !bluedartHasCachedJwtToken($credentials)
        && !bluedartHasDhlPortalCredentials($credentials);
}

/**
 * @param array<string, mixed> $body
 * @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string}
 */
function bluedartLegacySoapRequest(
    string $endpoint,
    string $soapAction,
    string $operationName,
    array $body
): array {
    $endpoint = trim($endpoint);
    if ($endpoint === '') {
        return ['success' => false, 'error' => 'Blue Dart legacy SOAP endpoint is not configured.'];
    }

    $payloadXml = bluedartBuildLegacySoapBodyXml($operationName, $body);
    $envelope = '<?xml version="1.0" encoding="utf-8"?>'
        . '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:tem="http://tempuri.org/">'
        . '<soap:Header>'
        . '<a:Action xmlns:a="http://www.w3.org/2005/08/addressing">' . htmlspecialchars($soapAction, ENT_XML1) . '</a:Action>'
        . '<a:To xmlns:a="http://www.w3.org/2005/08/addressing">' . htmlspecialchars($endpoint, ENT_XML1) . '</a:To>'
        . '</soap:Header>'
        . '<soap:Body>' . $payloadXml . '</soap:Body>'
        . '</soap:Envelope>';

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $envelope,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/soap+xml; charset=utf-8; action="' . $soapAction . '"',
            'Accept: application/soap+xml, application/xml, text/xml, */*',
        ],
        CURLOPT_TIMEOUT => 90,
    ]);

    $raw = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return [
            'success' => false,
            'error' => $curlError !== '' ? $curlError : 'Blue Dart legacy SOAP request failed.',
            'http_code' => $httpCode,
        ];
    }

    $parsed = bluedartParseLegacySoapResponse((string) $raw);
    if (!empty($parsed['fault'])) {
        return [
            'success' => false,
            'error' => bluedartSanitizeErrorMessage((string) ($parsed['fault'] ?? 'Blue Dart SOAP fault.')),
            'http_code' => $httpCode,
            'raw' => (string) $raw,
            'data' => $parsed,
        ];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        return [
            'success' => false,
            'error' => 'Blue Dart legacy SOAP HTTP ' . $httpCode,
            'http_code' => $httpCode,
            'raw' => (string) $raw,
            'data' => $parsed,
        ];
    }

    return [
        'success' => true,
        'http_code' => $httpCode,
        'raw' => (string) $raw,
        'data' => $parsed,
    ];
}

/** @param array<string, mixed> $body */
function bluedartBuildLegacySoapBodyXml(string $operationName, array $body): string
{
    $inner = bluedartArrayToLegacyXml($body);
    return '<tem:' . htmlspecialchars($operationName, ENT_XML1) . ' xmlns:tem="http://tempuri.org/">'
        . $inner
        . '</tem:' . htmlspecialchars($operationName, ENT_XML1) . '>';
}

/**
 * @param mixed $value
 */
function bluedartArrayToLegacyXml($value, ?string $forcedName = null): string
{
    if (!is_array($value)) {
        $text = htmlspecialchars(bluedartLegacyScalarToString($value), ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
        $name = $forcedName ?? 'Value';
        return '<' . $name . '>' . $text . '</' . $name . '>';
    }

    if ($value === []) {
        return '';
    }

    $isList = array_keys($value) === range(0, count($value) - 1);
    if ($isList) {
        $xml = '';
        foreach ($value as $item) {
            $xml .= bluedartArrayToLegacyXml($item, $forcedName ?? 'Item');
        }
        return $xml;
    }

    $xml = '';
    foreach ($value as $key => $child) {
        $name = bluedartLegacyXmlElementName((string) $key);
        if (is_array($child) && bluedartLegacyIsNumericList($child)) {
            foreach ($child as $row) {
                $xml .= bluedartArrayToLegacyXml($row, $name);
            }
            continue;
        }
        if (is_array($child)) {
            $xml .= '<' . $name . '>' . bluedartArrayToLegacyXml($child) . '</' . $name . '>';
            continue;
        }
        $xml .= bluedartArrayToLegacyXml($child, $name);
    }

    return $xml;
}

/** @param mixed $value */
function bluedartLegacyScalarToString($value): string
{
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if ($value === null) {
        return '';
    }
    return (string) $value;
}

function bluedartLegacyXmlElementName(string $key): string
{
    $key = preg_replace('/[^A-Za-z0-9_]/', '', $key) ?? 'Field';
    if ($key === '') {
        return 'Field';
    }
    return $key;
}

/** @param array<mixed> $value */
function bluedartLegacyIsNumericList(array $value): bool
{
    if ($value === []) {
        return false;
    }
    return array_keys($value) === range(0, count($value) - 1);
}

/** @return array{fault?:string,values:array<string,string>} */
function bluedartParseLegacySoapResponse(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return ['values' => [], 'fault' => 'Empty SOAP response from Blue Dart.'];
    }

    $prev = libxml_use_internal_errors(true);
    $xml = simplexml_load_string($raw);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    if ($xml === false) {
        return ['values' => [], 'fault' => 'Could not parse Blue Dart SOAP response.'];
    }

    $namespaces = $xml->getNamespaces(true);
    $soapNs = $namespaces['s'] ?? $namespaces['soap'] ?? 'http://www.w3.org/2003/05/soap-envelope';
    $body = $xml->children($soapNs)->Body ?? null;
    if ($body === null) {
        return ['values' => [], 'fault' => 'Blue Dart SOAP response missing Body.'];
    }

    foreach ($body->children() as $child) {
        $childNs = $child->getNamespaces(true);
        $faultNs = $childNs['s'] ?? $soapNs;
        if ($child->getName() === 'Fault' || stripos($child->getName(), 'fault') !== false) {
            $reason = trim((string) ($child->children($faultNs)->Reason->Text ?? $child->faultstring ?? ''));
            return ['values' => [], 'fault' => $reason !== '' ? $reason : 'Blue Dart SOAP fault.'];
        }
    }

    $values = bluedartFlattenLegacySoapXml($body);
    $fault = '';
    if (bluedartLegacyTruthy($values['IsError'] ?? $values['iserror'] ?? '')) {
        $fault = trim((string) (
            $values['StatusInformation']
            ?? $values['statusinformation']
            ?? $values['ErrorMessage']
            ?? $values['errormessage']
            ?? 'Blue Dart legacy API returned an error.'
        ));
    }

    return ['values' => $values, 'fault' => $fault !== '' ? $fault : null];
}

/** @return array<string, string> */
function bluedartFlattenLegacySoapXml(SimpleXMLElement $node): array
{
    $out = [];
    bluedartCollectLegacySoapValues($node, $out);
    return $out;
}

/** @param array<string, string> $out */
function bluedartCollectLegacySoapValues(SimpleXMLElement $node, array &$out): void
{
    $name = $node->getName();
    $children = $node->children();
    $hasElementChildren = false;
    foreach ($children as $child) {
        $hasElementChildren = true;
        bluedartCollectLegacySoapValues($child, $out);
    }

    if (!$hasElementChildren) {
        $value = trim((string) $node);
        if ($value !== '') {
            $out[$name] = $value;
        }
    }
}

/** @param mixed $value */
function bluedartLegacyTruthy($value): bool
{
    if (is_bool($value)) {
        return $value;
    }
    $v = strtolower(trim((string) $value));
    return in_array($v, ['1', 'true', 'yes', 'y'], true);
}

/**
 * @param array<string, mixed> $shipment REST-style Request subtree
 * @return array<string, mixed>
 */
function bluedartAdaptShipmentForLegacySoap(array $shipment): array
{
    $adapted = $shipment;
    if (!isset($adapted['Services']) || !is_array($adapted['Services'])) {
        return $adapted;
    }

    $services = $adapted['Services'];

    if (isset($services['CollactableAmount']) && !isset($services['CollectableAmount'])) {
        $services['CollectableAmount'] = $services['CollactableAmount'];
        unset($services['CollactableAmount']);
    }

    if (isset($services['PickupDate']) && is_string($services['PickupDate'])) {
        $services['PickupDate'] = bluedartFormatLegacyPickupDate($services['PickupDate']);
    }

    if (isset($services['Dimensions']) && is_array($services['Dimensions'])) {
        $dimensions = [];
        foreach ($services['Dimensions'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $dimensions[] = [
                'Length' => $row['Length'] ?? $row['length'] ?? 1,
                'Breadth' => $row['Breadth'] ?? $row['breadth'] ?? $row['Width'] ?? 1,
                'Height' => $row['Height'] ?? $row['height'] ?? 1,
                'Count' => $row['Count'] ?? $row['count'] ?? 1,
            ];
        }
        if (count($dimensions) === 1) {
            $services['Dimensions'] = ['Dimension' => $dimensions[0]];
        } elseif ($dimensions !== []) {
            $services['Dimensions'] = ['Dimension' => $dimensions];
        }
    }

    unset($services['PDFOutputNotRequired'], $services['AWBNo']);

    $adapted['Services'] = $services;
    return $adapted;
}

function bluedartFormatLegacyPickupDate(string $value): string
{
    $value = trim($value);
    if (preg_match('/\/Date\((\d+)\)\//', $value, $m)) {
        $ts = (int) (((int) $m[1]) / 1000);
        if ($ts > 0) {
            return date('Y-m-d', $ts);
        }
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }

    return date('Y-m-d');
}

/** @param array<string, string> $values */
function bluedartParseLegacyWaybillValues(array $values): array
{
    $awb = trim((string) ($values['AWBNo'] ?? $values['awbno'] ?? ''));
    if ($awb === '') {
        return ['success' => false, 'error' => 'Blue Dart legacy API did not return an AWB number.'];
    }

    $pdfContent = (string) ($values['AWBPrintContent'] ?? $values['awbprintcontent'] ?? '');
    $pdfBinary = bluedartDecodeLegacyPdfContent($pdfContent);
    if ($pdfBinary === '') {
        return [
            'success' => false,
            'error' => 'Blue Dart returned AWB ' . $awb . ' but no label PDF (AWBPrintContent).',
            'awb' => $awb,
        ];
    }

    return [
        'success' => true,
        'awb' => $awb,
        'pdf_binary' => $pdfBinary,
        'destination_area' => trim((string) ($values['DestinationArea'] ?? $values['destinationarea'] ?? $values['DestinationLocation'] ?? '')),
    ];
}

function bluedartDecodeLegacyPdfContent(string $content): string
{
    $content = trim($content);
    if ($content === '') {
        return '';
    }
    if (strncmp($content, '%PDF', 4) === 0) {
        return $content;
    }
    $decoded = base64_decode($content, true);
    if (is_string($decoded) && $decoded !== '' && strncmp($decoded, '%PDF', 4) === 0) {
        return $decoded;
    }

    return '';
}
