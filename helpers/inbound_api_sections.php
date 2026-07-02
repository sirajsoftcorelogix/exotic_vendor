<?php

/**
 * Inbound → vendor product/modify section field maps (flat POST, not create JSON).
 */

/** @return list<string> */
function inbound_api_section_keys(): array
{
    return ['item_details', 'book_details'];
}

function inbound_api_section_label(string $section): string
{
    $labels = [
        'item_details' => 'Item details (dimensions, pricing & stock)',
        'book_details' => 'Book details',
    ];

    return $labels[$section] ?? $section;
}

/**
 * @param array<string, mixed> $d Row from Inbounding::getpublishdata()['data']
 */
function inbound_api_is_book_group(array $d): bool
{
    if (strtolower(trim((string) ($d['groupname'] ?? ''))) === 'book') {
        return true;
    }

    $groupName = trim((string) ($d['group_name'] ?? ''));
    if ($groupName === '-8') {
        return true;
    }

    return $groupName !== '' && stripos($groupName, 'book') !== false;
}

/**
 * @param array<string, mixed> $d
 */
function inbound_api_resolve_book_language_for_modify(array $d): string
{
    $stored = trim((string) ($d['language'] ?? ''));
    if ($stored !== '') {
        return $stored;
    }

    require_once __DIR__ . '/book_language_formatter.php';
    require_once __DIR__ . '/../models/languages/Language.php';
    global $conn;

    $roleIdCsvMap = [];
    foreach (BookLanguageFormatter::orderedRoleKeys() as $key) {
        $roleIdCsvMap[$key] = trim((string) ($d[$key] ?? ''));
    }

    if ($conn instanceof mysqli) {
        $languageModel = new Language($conn);
        return $languageModel->buildFormattedBookLanguage($roleIdCsvMap);
    }

    return '';
}

/**
 * @param array<string, mixed> $d Row from Inbounding::getpublishdata()['data']
 * @return array<string, string|int|float>
 */
function inbound_api_build_item_details_modify_fields(array $d): array
{
    $fields = [];
    $append = static function (string $key, $value) use (&$fields): void {
        if ($value === null) {
            return;
        }
        if (is_string($value) && trim($value) === '') {
            return;
        }
        $fields[$key] = $value;
    };

    $append('prod_height', $d['height'] ?? '');
    $append('prod_width', $d['width'] ?? '');
    $append('prod_length', $d['depth'] ?? '');
    $append('product_weight', $d['weight'] ?? '');
    if (isset($fields['product_weight']) || isset($fields['prod_height']) || isset($fields['prod_width']) || isset($fields['prod_length'])) {
        $fields['product_weight_unit'] = 'kg';
        $fields['length_unit'] = 'inch';
    }
    $append('dimensions', $d['dimensions'] ?? '');

    $usd = (int) ($d['usd_price'] ?? 0);
    if ($usd > 0) {
        $fields['price'] = $usd;
        $fields['usd'] = $usd;
    }

    $append('price_india', (int) ($d['price_india'] ?? 0) ?: null);
    $mrp = (int) ($d['price_india_mrp'] ?? 0);
    if ($mrp > 0) {
        $fields['mrp_india'] = $mrp;
    }

    $cp = $d['cp'] ?? null;
    if ($cp !== null && $cp !== '' && (float) $cp >= 0) {
        $fields['cp'] = $cp;
    }

    if (array_key_exists('gst_rate', $d) && $d['gst_rate'] !== '' && $d['gst_rate'] !== null) {
        $fields['gst'] = (int) $d['gst_rate'];
    }

    $append('hscode', $d['hsn_code'] ?? '');
    $append('upc', $d['upc'] ?? '');
    $append('colormap', $d['colormaps'] ?? '');
    $append('location', $d['store_location'] ?? '');

    $fields['amazon_sold'] = (string) ((int) ($d['amazon_sold'] ?? 0));
    $fields['amazon_leadtime'] = (string) ((int) ($d['amazon_leadtime'] ?? 0));
    $fields['permanent_discount'] = (string) ((int) ($d['permanent_discount'] ?? 0));
    $fields['discount_global'] = (string) ((int) ($d['discount_global'] ?? 0));
    $fields['discount_india'] = (string) ((int) ($d['discount_india'] ?? 0));

    $redirect = trim((string) ($d['redirect'] ?? ''));
    if ($redirect !== '' && preg_match('/^[A-Za-z0-9][A-Za-z0-9_\-\.]*$/', $redirect)) {
        $fields['redirect'] = $redirect;
    }

    return $fields;
}

/**
 * @param array<string, mixed> $d Row from Inbounding::getpublishdata()['data']
 * @return array<string, string|int|float>
 */
function inbound_api_build_book_details_modify_fields(array $d, Inbounding $model): array
{
    if (!inbound_api_is_book_group($d)) {
        return [];
    }

    $fields = [];
    $append = static function (string $key, $value) use (&$fields): void {
        if ($value === null) {
            return;
        }
        if (is_string($value) && trim($value) === '') {
            return;
        }
        $fields[$key] = $value;
    };

    $creator = $model->buildBookCreatorApiValue($d['author'] ?? '', $d['edited_by'] ?? '');
    $append('creator', $creator);

    $publisherId = (int) ($d['publisher'] ?? 0);
    if ($publisherId > 0) {
        $fields['publisher_vendor_id'] = $publisherId;
    }

    $append('language', inbound_api_resolve_book_language_for_modify($d));
    $append('isbn', $d['isbn'] ?? '');

    if (!empty($d['cover_type'])) {
        $fields['cover_type'] = $d['cover_type'];
    }
    if (!empty($d['edition'])) {
        $fields['edition'] = $d['edition'];
    }

    $pubDate = trim((string) ($d['publication_date'] ?? ''));
    if ($pubDate !== '' && $pubDate !== '0000-00-00') {
        $fields['publication_date'] = $pubDate;
    }

    if (isset($d['pages']) && $d['pages'] !== '' && $d['pages'] !== null) {
        $fields['pages'] = (int) $d['pages'];
    }

    $sourcingFee = trim((string) ($d['sourcingfee'] ?? ''));
    if ($sourcingFee !== '') {
        $fields['sourcingfee'] = round((float) $sourcingFee, 2);
    }

    $shippingFee = $d['shippingfee'] ?? null;
    if ($shippingFee !== null && $shippingFee !== '') {
        $fields['shippingfee'] = round((float) $shippingFee, 2);
    }

    return $fields;
}

/**
 * @return array{itemcode:string,size:string,color:string,fields:array<string,mixed>,section:string}|null
 */
function inbound_api_build_section_modify_payload(Inbounding $model, int $inboundId, string $section): ?array
{
    $section = trim($section);
    if (!in_array($section, inbound_api_section_keys(), true)) {
        return null;
    }

    $publish = $model->getpublishdata($inboundId);
    $d = $publish['data'] ?? null;
    if (!is_array($d) || empty($d['Item_code'])) {
        return null;
    }

    $fields = [];
    if ($section === 'item_details') {
        $fields = inbound_api_build_item_details_modify_fields($d);
    } elseif ($section === 'book_details') {
        if (!inbound_api_is_book_group($d)) {
            return null;
        }
        $fields = inbound_api_build_book_details_modify_fields($d, $model);
    }

    return [
        'itemcode' => trim((string) $d['Item_code']),
        'size' => trim((string) ($d['size'] ?? '')),
        'color' => trim((string) ($d['color'] ?? '')),
        'fields' => $fields,
        'section' => $section,
    ];
}
