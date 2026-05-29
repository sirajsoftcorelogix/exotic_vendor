<?php

/**
 * Exotic India vendor-api/product endpoints: vendorcreate, vendormodify, vendordelete.
 */

function vendor_external_api_base_url(): string
{
    return 'https://www.exoticindia.com/vendor-api/product/';
}

function vendor_external_api_headers(): array
{
    return [
        'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
        'x-adminapitest: 1',
        'Content-Type: application/x-www-form-urlencoded',
    ];
}

/**
 * @return array{success:bool,message:string,http_code:int,vendor_id?:int,data?:array,raw?:string}
 */
function vendor_external_api_post(string $action, array $postData): array
{
    $action = trim($action);
    if ($action === '') {
        return ['success' => false, 'message' => 'Vendor API action is required.', 'http_code' => 0];
    }

    $apiUrl = vendor_external_api_base_url() . $action;
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => vendor_external_api_headers(),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $raw = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return [
            'success' => false,
            'message' => 'Vendor API call failed: ' . $error,
            'http_code' => $httpCode,
            'raw' => '',
        ];
    }

    $decoded = json_decode((string) $raw, true);
    $data = is_array($decoded) ? $decoded : [];

    if ($httpCode >= 400) {
        $msg = !empty($data['message']) ? (string) $data['message'] : 'HTTP ' . $httpCode;

        return [
            'success' => false,
            'message' => 'Vendor API failed: ' . $msg,
            'http_code' => $httpCode,
            'data' => $data,
            'raw' => (string) $raw,
        ];
    }

    if ((isset($data['success']) && $data['success'] === false)
        || (isset($data['status']) && strtolower((string) $data['status']) === 'error')) {
        $msg = !empty($data['message']) ? (string) $data['message'] : 'Remote API returned failure.';

        return [
            'success' => false,
            'message' => $msg,
            'http_code' => $httpCode,
            'data' => $data,
            'raw' => (string) $raw,
        ];
    }

    $vendorId = 0;
    if (isset($data['vendor_id'])) {
        $vendorId = (int) preg_replace('/\D/', '', (string) $data['vendor_id']);
    }

    return [
        'success' => true,
        'message' => !empty($data['message']) ? (string) $data['message'] : 'Vendor API call succeeded.',
        'http_code' => $httpCode,
        'vendor_id' => $vendorId > 0 ? $vendorId : null,
        'data' => $data,
        'raw' => (string) $raw,
    ];
}

/**
 * @return array{success:bool,message:string,http_code:int,vendor_id?:int,data?:array,raw?:string}
 */
function vendor_external_api_create(array $postData): array
{
    return vendor_external_api_post('vendorcreate', $postData);
}

/**
 * @return array{success:bool,message:string,http_code:int,vendor_id?:int,data?:array,raw?:string}
 */
function vendor_external_api_modify(array $postData): array
{
    if (trim((string) ($postData['vendor_id'] ?? '')) === '') {
        return ['success' => false, 'message' => 'vendor_id is required for vendormodify.', 'http_code' => 0];
    }

    return vendor_external_api_post('vendormodify', $postData);
}

/**
 * @return array{success:bool,message:string,http_code:int,data?:array,raw?:string}
 */
function vendor_external_api_delete(string $vendorId): array
{
    $vendorId = trim($vendorId);
    if ($vendorId === '') {
        return ['success' => false, 'message' => 'vendor_id is required for vendordelete.', 'http_code' => 0];
    }

    $result = vendor_external_api_post('vendordelete', ['vendor_id' => $vendorId]);
    if (!$result['success']) {
        return $result;
    }

    return [
        'success' => true,
        'message' => 'Remote vendor deleted.',
        'http_code' => $result['http_code'],
        'data' => $result['data'] ?? [],
        'raw' => $result['raw'] ?? '',
    ];
}

/**
 * POST body for author/publisher vendorcreate.
 */
function vendor_external_api_creator_payload(string $vendorType, string $name, string $webpage = '0'): array
{
    return [
        'name' => trim($name),
        'groupname' => 'book',
        'vendor_type' => $vendorType,
        'webpage' => ((string) $webpage === '1') ? '1' : '0',
    ];
}

/**
 * POST body for author/publisher vendormodify.
 */
function vendor_external_api_modify_creator_payload(string $vendorId, string $vendorType, string $name, string $webpage = '0'): array
{
    return [
        'vendor_id' => trim($vendorId),
        'name' => trim($name),
        'groupname' => 'book',
        'vendor_type' => $vendorType,
        'webpage' => ((string) $webpage === '1') ? '1' : '0',
    ];
}
