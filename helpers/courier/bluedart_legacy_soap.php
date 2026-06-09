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
    $attempts = [
        [
            'label' => 'soap12',
            'envelope' => '<?xml version="1.0" encoding="utf-8"?>'
                . '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:tem="http://tempuri.org/">'
                . '<soap:Header>'
                . '<a:Action xmlns:a="http://www.w3.org/2005/08/addressing">' . htmlspecialchars($soapAction, ENT_XML1) . '</a:Action>'
                . '<a:To xmlns:a="http://www.w3.org/2005/08/addressing">' . htmlspecialchars($endpoint, ENT_XML1) . '</a:To>'
                . '</soap:Header>'
                . '<soap:Body>' . $payloadXml . '</soap:Body>'
                . '</soap:Envelope>',
            'headers' => [
                'Content-Type: application/soap+xml; charset=utf-8; action="' . $soapAction . '"',
                'Accept: application/soap+xml, application/xml, text/xml, */*',
            ],
        ],
        [
            'label' => 'soap11',
            'envelope' => '<?xml version="1.0" encoding="utf-8"?>'
                . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/">'
                . '<soapenv:Header/>'
                . '<soapenv:Body>' . $payloadXml . '</soapenv:Body>'
                . '</soapenv:Envelope>',
            'headers' => [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: "' . $soapAction . '"',
                'Accept: text/xml, application/xml, */*',
            ],
        ],
    ];

    $last = ['success' => false, 'error' => 'Blue Dart legacy SOAP request failed.', 'http_code' => 0];
    $lastMeta = [
        'endpoint' => $endpoint,
        'soap_variant' => '',
        'request_xml' => '',
        'response_raw' => '',
    ];
    foreach ($attempts as $attempt) {
        $lastMeta['soap_variant'] = (string) ($attempt['label'] ?? '');
        $lastMeta['request_xml'] = bluedartRedactSoapXml((string) ($attempt['envelope'] ?? ''));

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $attempt['envelope'],
            CURLOPT_HTTPHEADER => $attempt['headers'],
            CURLOPT_TIMEOUT => 90,
            CURLOPT_CONNECTTIMEOUT => 30,
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($raw === false) {
            $last = array_merge([
                'success' => false,
                'error' => $curlError !== '' ? $curlError : 'Blue Dart legacy SOAP request failed.',
                'http_code' => $httpCode,
                'curl_error' => $curlError,
                'curl_errno' => $curlErrno,
                'bytes_received' => 0,
            ], $lastMeta);
            continue;
        }

        $rawString = (string) $raw;
        $responseRaw = bluedartRedactSoapXml($rawString);
        $lastMeta['response_raw'] = $responseRaw;

        $parsed = bluedartParseLegacySoapResponse($rawString, $httpCode);
        if (trim($rawString) === '' && !empty($parsed['fault'])) {
            $parsed['fault'] .= ' 0 bytes received from netconnect.';
            if ($curlError !== '') {
                $parsed['fault'] .= ' cURL: ' . $curlError;
            }
        }

        $retryableFault = !empty($parsed['fault'])
            && (
                bluedartLegacyResponseLooksLikeHtml($rawString)
                || (
                    trim($rawString) === ''
                    && ($attempt['label'] ?? '') === 'soap12'
                )
            );

        if (!empty($parsed['fault']) && $retryableFault) {
            $last = array_merge([
                'success' => false,
                'error' => (string) $parsed['fault'],
                'http_code' => $httpCode,
                'raw' => $rawString,
                'data' => $parsed,
                'curl_error' => $curlError,
                'curl_errno' => $curlErrno,
                'bytes_received' => strlen($rawString),
            ], $lastMeta);
            continue;
        }

        if (!empty($parsed['fault'])) {
            return array_merge([
                'success' => false,
                'error' => bluedartSanitizeErrorMessage((string) $parsed['fault']),
                'http_code' => $httpCode,
                'raw' => $rawString,
                'data' => $parsed,
                'curl_error' => $curlError,
                'curl_errno' => $curlErrno,
                'bytes_received' => strlen($rawString),
            ], $lastMeta);
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $businessError = bluedartLegacyFormatBusinessError($parsed['values'] ?? []);
            if ($businessError !== null) {
            return array_merge([
                'success' => false,
                'error' => bluedartSanitizeErrorMessage($businessError),
                'http_code' => $httpCode,
                'raw' => $rawString,
                'data' => $parsed,
                'curl_error' => $curlError,
                'curl_errno' => $curlErrno,
                'bytes_received' => strlen($rawString),
            ], $lastMeta);
            }

            return array_merge([
                'success' => true,
                'http_code' => $httpCode,
                'raw' => $rawString,
                'data' => $parsed,
                'bytes_received' => strlen($rawString),
            ], $lastMeta);
        }

        $last = array_merge([
            'success' => false,
            'error' => bluedartLegacyFormatBusinessError($parsed['values'] ?? [])
                ?? ('Blue Dart legacy SOAP HTTP ' . $httpCode . ' (' . $attempt['label'] . ')'),
            'http_code' => $httpCode,
            'raw' => $rawString,
            'data' => $parsed,
            'curl_error' => $curlError,
            'curl_errno' => $curlErrno,
            'bytes_received' => strlen($rawString),
        ], $lastMeta);
    }

    return array_merge($last, $lastMeta);
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

/**
 * @return array{fault?:?string,values:array<string,string>}
 */
function bluedartParseLegacySoapResponse(string $raw, int $httpCode = 0): array
{
    $raw = trim($raw);
    if ($raw === '') {
        $msg = 'Empty SOAP response from Blue Dart';
        if ($httpCode > 0) {
            $msg .= ' (HTTP ' . $httpCode . ')';
        }
        $msg .= '.';

        return ['values' => [], 'fault' => $msg];
    }

    if (bluedartLegacyResponseLooksLikeHtml($raw)) {
        $title = bluedartLegacyExtractHtmlErrorTitle($raw);
        $msg = 'Blue Dart netconnect server returned an HTML error page'
            . ($httpCode > 0 ? ' (HTTP ' . $httpCode . ')' : '')
            . ($title !== '' ? ': ' . $title : '')
            . '. Verify legacy_waybill_endpoint or contact Blue Dart support.';
        return ['values' => [], 'fault' => $msg];
    }

    $values = bluedartExtractLegacySoapValuesFromRaw($raw);
    if (!empty($values['_fault'])) {
        return ['values' => $values, 'fault' => $values['_fault']];
    }

    if ($values === []) {
        return [
            'values' => [],
            'fault' => 'Could not parse Blue Dart SOAP response'
                . ($httpCode > 0 ? ' (HTTP ' . $httpCode . ')' : '')
                . '. Response was not valid SOAP XML.',
        ];
    }

    unset($values['_fault'], $values['_html_error']);

    $fault = bluedartLegacyFormatBusinessError($values);
    return ['values' => $values, 'fault' => $fault];
}

function bluedartLegacyResponseLooksLikeHtml(string $raw): bool
{
    $head = strtolower(substr($raw, 0, 512));
    return str_contains($head, '<!doctype html')
        || str_contains($head, '<html')
        || str_contains($head, '<h1>server error');
}

function bluedartLegacyExtractHtmlErrorTitle(string $raw): string
{
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $raw, $m)) {
        return trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $raw, $m)) {
        return trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    return '';
}

/** @return array<string, string> */
function bluedartExtractLegacySoapValuesFromRaw(string $raw): array
{
    $values = [];
    $parseXml = preg_replace(
        '/<((?:[\\w-]+:)?AWBPrintContent)\\b[^>]*>.*?<\\/\\1>/is',
        '<$1></$1>',
        $raw
    );

    $prev = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = @$dom->loadXML($parseXml, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_PARSEHUGE);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    if ($loaded) {
        $xpath = new DOMXPath($dom);
        foreach ([
            'AWBNo', 'IsError', 'StatusInformation', 'ErrorMessage', 'DestinationArea',
            'DestinationLocation', 'AreaCode', 'TokenNumber',
        ] as $tag) {
            $nodes = $xpath->query('//*[local-name()="' . $tag . '"]');
            if ($nodes instanceof DOMNodeList && $nodes->length > 0) {
                $values[$tag] = trim((string) $nodes->item(0)->textContent);
            }
        }

        $faultNodes = $xpath->query('//*[local-name()="Fault"]//*[local-name()="Text"]');
        if ($faultNodes instanceof DOMNodeList && $faultNodes->length > 0) {
            $values['_fault'] = trim((string) $faultNodes->item(0)->textContent);
        }
        if (empty($values['_fault'])) {
            $faultString = $xpath->query('//*[local-name()="faultstring"]');
            if ($faultString instanceof DOMNodeList && $faultString->length > 0) {
                $values['_fault'] = trim((string) $faultString->item(0)->textContent);
            }
        }
    }

    bluedartLegacyRegexExtractValues($raw, $values);

    return $values;
}

/**
 * @param array<string, string> $values
 */
function bluedartLegacyRegexExtractValues(string $raw, array &$values): void
{
    $patterns = [
        'AWBNo' => '/<(?:[\\w-]+:)?AWBNo\\b[^>]*>([^<]+)</i',
        'IsError' => '/<(?:[\\w-]+:)?IsError\\b[^>]*>([^<]+)</i',
        'StatusInformation' => '/<(?:[\\w-]+:)?StatusInformation\\b[^>]*>([^<]+)</i',
        'DestinationArea' => '/<(?:[\\w-]+:)?DestinationArea\\b[^>]*>([^<]+)</i',
        'AWBPrintContent' => '/<(?:[\\w-]+:)?AWBPrintContent\\b[^>]*>([^<]+)</is',
    ];

    foreach ($patterns as $key => $pattern) {
        if (!empty($values[$key])) {
            continue;
        }
        if (preg_match($pattern, $raw, $m)) {
            $values[$key] = trim($m[1]);
        }
    }
}

/**
 * @param array<string, string> $values
 */
function bluedartLegacyFormatBusinessError(array $values): ?string
{
    if (bluedartLegacyTruthy($values['IsError'] ?? '')) {
        $msg = trim((string) (
            $values['StatusInformation']
            ?? $values['ErrorMessage']
            ?? ''
        ));
        return $msg !== '' ? $msg : 'Blue Dart legacy API returned an error.';
    }

    return null;
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
    $adapted = bluedartLegacyNormalizeScalars($shipment);
    unset($adapted['Returnadds']);

    if (isset($adapted['Consignee']) && is_array($adapted['Consignee'])) {
        $consignee = bluedartLegacyNormalizeScalars($adapted['Consignee']);
        if (empty($consignee['ConsigneeTelephone']) && !empty($consignee['ConsigneeMobile'])) {
            $consignee['ConsigneeTelephone'] = $consignee['ConsigneeMobile'];
        }
        unset($consignee['ConsigneeAddressType'], $consignee['ConsigneeEmailID']);
        $adapted['Consignee'] = $consignee;
    }

    if (isset($adapted['Shipper']) && is_array($adapted['Shipper'])) {
        $shipper = bluedartLegacyNormalizeScalars($adapted['Shipper']);
        if (empty($shipper['CustomerTelephone']) && !empty($shipper['CustomerMobile'])) {
            $shipper['CustomerTelephone'] = $shipper['CustomerMobile'];
        }
        $adapted['Shipper'] = $shipper;
    }

    if (!isset($adapted['Services']) || !is_array($adapted['Services'])) {
        return $adapted;
    }

    $services = bluedartLegacyNormalizeScalars($adapted['Services']);

    if (isset($services['CollactableAmount']) && !isset($services['CollectableAmount'])) {
        $services['CollectableAmount'] = $services['CollactableAmount'];
        unset($services['CollactableAmount']);
    }

    if (isset($services['PickupDate']) && is_string($services['PickupDate'])) {
        $services['PickupDate'] = bluedartFormatLegacyPickupDate($services['PickupDate']);
    }

    if (empty($services['ProductType'])) {
        $services['ProductType'] = 'Dutiables';
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

    unset(
        $services['PDFOutputNotRequired'],
        $services['AWBNo'],
        $services['itemdtl'],
        $services['Commodity']
    );

    $adapted['Services'] = $services;
    return $adapted;
}

/**
 * Blue Dart legacy SOAP rejects strict booleans; use empty string for false.
 *
 * @param mixed $data
 * @return mixed
 */
function bluedartLegacyNormalizeScalars($data)
{
    if (!is_array($data)) {
        if (is_bool($data)) {
            return $data ? 'true' : '';
        }
        return $data;
    }

    $normalized = [];
    foreach ($data as $key => $value) {
        $normalized[$key] = is_array($value)
            ? bluedartLegacyNormalizeScalars($value)
            : bluedartLegacyNormalizeScalars($value);
    }

    return $normalized;
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
