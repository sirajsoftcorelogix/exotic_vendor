<?php
/**
 * CLI probe: is Blue Dart legacy netconnect (Ver1.10 SOAP) reachable?
 *
 * Usage: php tools/bluedart_legacy_healthcheck.php
 * Does NOT use account credentials — only checks endpoint reachability.
 */

declare(strict_types=1);

require_once __DIR__ . '/../helpers/courier/bluedart_legacy_soap.php';

$endpoints = [
    'waybill_svc' => 'https://netconnect.bluedart.com/Ver1.10/ShippingAPI/WayBill/WayBillGeneration.svc',
    'waybill_wsdl' => 'https://netconnect.bluedart.com/Ver1.10/ShippingAPI/WayBill/WayBillGeneration.svc?wsdl',
    'finder_svc' => 'https://netconnect.bluedart.com/Ver1.10/ShippingAPI/Finder/ServiceFinderQuery.svc',
    'finder_wsdl' => 'https://netconnect.bluedart.com/Ver1.10/ShippingAPI/Finder/ServiceFinderQuery.svc?wsdl',
    'samples' => 'https://netconnect.bluedart.com/samples/',
    'rest_gateway' => 'https://apigateway.bluedart.com/in/transportation/waybill/v1/',
];

/** @return array<string, mixed> */
function probeHttp(string $url, string $method = 'GET', ?string $body = null, array $headers = []): array
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
    ];
    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = $body;
    }
    curl_setopt_array($ch, $opts);

    $raw = curl_exec($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);

    $text = $raw === false ? '' : (string) $raw;
    $snippet = trim(substr(preg_replace('/\s+/', ' ', $text) ?? '', 0, 200));

    return [
        'http' => $http,
        'bytes' => strlen($text),
        'curl_errno' => $errno,
        'curl_error' => $err,
        'looks_like_wsdl' => stripos($text, '<wsdl:') !== false || stripos($text, 'definitions') !== false,
        'looks_like_html' => stripos($text, '<html') !== false,
        'looks_like_soap' => stripos($text, 'Envelope') !== false || stripos($text, 'Fault') !== false,
        'snippet' => $snippet,
    ];
}

$minimalSoap12 = '<?xml version="1.0" encoding="utf-8"?>'
    . '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:tem="http://tempuri.org/">'
    . '<soap:Header>'
    . '<a:Action xmlns:a="http://www.w3.org/2005/08/addressing">http://tempuri.org/IWayBillGeneration/GenerateWayBill</a:Action>'
    . '<a:To xmlns:a="http://www.w3.org/2005/08/addressing">https://netconnect.bluedart.com/Ver1.10/ShippingAPI/WayBill/WayBillGeneration.svc</a:To>'
    . '</soap:Header>'
    . '<soap:Body><tem:GenerateWayBill xmlns:tem="http://tempuri.org/">'
    . '<Request></Request>'
    . '<Profile><LoginID>healthcheck</LoginID><LicenceKey>healthcheck</LicenceKey><Version>1.3</Version><Api_type>S</Api_type></Profile>'
    . '</tem:GenerateWayBill></soap:Body></soap:Envelope>';

$minimalSoap11 = '<?xml version="1.0" encoding="utf-8"?>'
    . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/">'
    . '<soapenv:Body><tem:GenerateWayBill xmlns:tem="http://tempuri.org/">'
    . '<Request></Request>'
    . '<Profile><LoginID>healthcheck</LoginID><LicenceKey>healthcheck</LicenceKey><Version>1.3</Version><Api_type>S</Api_type></Profile>'
    . '</tem:GenerateWayBill></soapenv:Body></soap:Envelope>';

echo "Blue Dart legacy netconnect health check\n";
echo str_repeat('=', 60) . "\n";
echo 'Time: ' . date('c') . "\n\n";

foreach ($endpoints as $label => $url) {
    $r = probeHttp($url);
    printf("[%s]\n  URL: %s\n  HTTP %d | %d bytes | cURL %d %s\n",
        $label,
        $url,
        $r['http'],
        $r['bytes'],
        $r['curl_errno'],
        $r['curl_error'] !== '' ? ('| ' . $r['curl_error']) : ''
    );
    if ($r['looks_like_wsdl']) {
        echo "  -> WSDL/XML service description detected\n";
    } elseif ($r['looks_like_soap']) {
        echo "  -> SOAP response detected\n";
    } elseif ($r['looks_like_html']) {
        echo "  -> HTML page (often IIS error)\n";
    }
    if ($r['snippet'] !== '') {
        echo '  Snippet: ' . $r['snippet'] . "\n";
    }
    echo "\n";
}

$waybillUrl = $endpoints['waybill_svc'];

echo "[waybill_soap12_post]\n";
$r12 = probeHttp($waybillUrl, 'POST', $minimalSoap12, [
    'Content-Type: application/soap+xml; charset=utf-8; action="http://tempuri.org/IWayBillGeneration/GenerateWayBill"',
    'Accept: application/soap+xml, application/xml, text/xml, */*',
]);
printf("  HTTP %d | %d bytes\n", $r12['http'], $r12['bytes']);
if ($r12['snippet'] !== '') {
    echo '  Snippet: ' . $r12['snippet'] . "\n";
}
echo "\n";

echo "[waybill_soap11_post]\n";
$r11 = probeHttp($waybillUrl, 'POST', $minimalSoap11, [
    'Content-Type: text/xml; charset=utf-8',
    'SOAPAction: "http://tempuri.org/IWayBillGeneration/GenerateWayBill"',
    'Accept: text/xml, application/xml, */*',
]);
printf("  HTTP %d | %d bytes\n", $r11['http'], $r11['bytes']);
if ($r11['snippet'] !== '') {
    echo '  Snippet: ' . $r11['snippet'] . "\n";
}
echo "\n";

$alternateUrls = [
    'ver1.9_waybill' => 'https://netconnect.bluedart.com/Ver1.9/ShippingAPI/WayBill/WayBillGeneration.svc',
    'ver1.8_waybill' => 'https://netconnect.bluedart.com/Ver1.8/ShippingAPI/WayBill/WayBillGeneration.svc',
    'ver2_waybill' => 'https://netconnect.bluedart.com/Ver2.0/ShippingAPI/WayBill/WayBillGeneration.svc',
    'no_ver_waybill' => 'https://netconnect.bluedart.com/ShippingAPI/WayBill/WayBillGeneration.svc',
    'routing_servlet' => 'https://api.bluedart.com/servlet/RoutingServlet',
    'rest_generate_short' => 'https://apigateway.bluedart.com/waybill/GenerateWayBill',
    'rest_generate_v1' => 'https://apigateway.bluedart.com/in/transportation/waybill/v1/GenerateWayBill',
];

echo "Alternate endpoint probes\n";
echo str_repeat('-', 60) . "\n";
foreach ($alternateUrls as $label => $url) {
    $r = probeHttp($url);
    printf("[%s] HTTP %d | %d bytes | %s\n", $label, $r['http'], $r['bytes'], $url);
    if ($r['snippet'] !== '') {
        echo '  ' . $r['snippet'] . "\n";
    }
}
echo "\n";

echo "- WSDL GET returns XML  -> legacy host is up (service may still require valid Profile creds).\n";
echo "- POST returns SOAP Fault / IsError -> API is alive; auth/payload issue only.\n";
echo "- POST returns 0 bytes or HTML 500/404 -> legacy endpoint likely deprecated or blocked.\n";
echo "- DHL REST gateway (apigateway.bluedart.com) is Blue Dart's current supported API.\n";
echo "- eShipz login_id/licence_key work on legacy SOAP; REST needs developer.dhl.com keys.\n";
