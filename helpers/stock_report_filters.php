<?php

require_once __DIR__ . '/order_filter_autocomplete.php';

/** @return 'all'|'out'|'low'|'in' */
function normalizeStockReportStatusFilter(string $value): string
{
    $value = trim($value);
    return in_array($value, ['all', 'out', 'low', 'in'], true) ? $value : 'all';
}

function appendStockReportQuantityStatusSql(string &$where, string $quantityExpr, string $status): void
{
    $status = normalizeStockReportStatusFilter($status);
    if ($status === 'out') {
        $where .= " AND COALESCE({$quantityExpr}, 0) = 0 ";
    } elseif ($status === 'low') {
        $where .= " AND {$quantityExpr} BETWEEN 1 AND 5 ";
    } elseif ($status === 'in') {
        $where .= " AND COALESCE({$quantityExpr}, 0) > 0 ";
    }
}

/**
 * @param array<string, mixed> $get
 * @return array{physical_stock_status:string,local_stock_status:string}
 */
function parseStockReportStockStatusFilters(array $get): array
{
    $legacyStatus = trim((string) ($get['stock_status'] ?? ''));
    $physicalStatus = trim((string) ($get['physical_stock_status'] ?? ''));
    $localStatus = trim((string) ($get['local_stock_status'] ?? ''));

    if ($physicalStatus === '' && $legacyStatus !== '') {
        $physicalStatus = $legacyStatus;
    }

    return [
        'physical_stock_status' => normalizeStockReportStatusFilter($physicalStatus !== '' ? $physicalStatus : 'all'),
        'local_stock_status' => normalizeStockReportStatusFilter($localStatus !== '' ? $localStatus : 'all'),
    ];
}

/**
 * @param array<string, mixed> $filters
 */
function appendStockReportStockStatusFiltersSql(string &$where, array $filters): void
{
    appendStockReportQuantityStatusSql(
        $where,
        'sm.running_stock',
        (string) ($filters['physical_stock_status'] ?? 'all')
    );
    appendStockReportQuantityStatusSql(
        $where,
        'p.local_stock',
        (string) ($filters['local_stock_status'] ?? 'all')
    );
}

/**
 * Filter by bin/shelf location from the latest stock movement row.
 *
 * @param array<string, mixed> $filters
 */
function appendStockReportLocationFilterSql(
    string &$where,
    array &$params,
    string &$types,
    array $filters
): void {
    $location = normalizeOrderFilterSearchText((string) ($filters['location'] ?? ''));
    if ($location === '') {
        return;
    }

    $where .= " AND IFNULL(sm.location, '') LIKE ? ";
    $params[] = '%' . $location . '%';
    $types .= 's';
}

/**
 * Group-specific stock report filter field definitions.
 *
 * @return array<string, array{label:string,placeholder:string,groups:list<string>,labels?:array<string,string>,autocomplete?:string}>
 */
function getStockReportGroupFilterFieldDefinitions(): array
{
    return [
        'size' => [
            'label' => 'Size',
            'placeholder' => 'Size',
            'groups' => ['textiles', 'jewelry', 'paintings', 'sculptures', 'homeandliving'],
        ],
        'color' => [
            'label' => 'Color',
            'placeholder' => 'Color',
            'groups' => ['textiles', 'jewelry'],
        ],
        'material' => [
            'label' => 'Material',
            'placeholder' => 'Search material by name…',
            'groups' => ['textiles', 'jewelry', 'paintings', 'sculptures', 'homeandliving'],
            'autocomplete' => 'material',
        ],
        'author' => [
            'label' => 'Author',
            'placeholder' => 'Search author by name…',
            'groups' => ['book'],
            'autocomplete' => 'author',
        ],
        'artist' => [
            'label' => 'Artist',
            'placeholder' => 'Search artist by name…',
            'groups' => ['paintings', 'sculptures'],
            'autocomplete' => 'artist',
        ],
        'publisher' => [
            'label' => 'Publisher',
            'placeholder' => 'Search publisher by name…',
            'groups' => ['book'],
            'autocomplete' => 'publisher',
        ],
        'isbn' => [
            'label' => 'ISBN',
            'placeholder' => 'ISBN',
            'groups' => ['book'],
        ],
        'language' => [
            'label' => 'Language',
            'placeholder' => 'Search language by name…',
            'groups' => ['book'],
            'autocomplete' => 'language',
        ],
    ];
}

/** @return list<string> */
function stockReportAllowedExtraFilterKeys(string $category): array
{
    $category = trim($category);
    if ($category === '' || $category === 'allProducts') {
        return [];
    }

    $keys = [];
    foreach (getStockReportGroupFilterFieldDefinitions() as $key => $def) {
        if (in_array($category, $def['groups'] ?? [], true)) {
            $keys[] = $key;
        }
    }

    return $keys;
}

/**
 * @param array<string, mixed> $get
 * @return array<string, mixed>
 */
function parseStockReportFiltersFromRequest(array $get, int $warehouseId): array
{
    $limit = isset($get['limit']) ? (int) $get['limit'] : 200;
    if ($limit < 1) {
        $limit = 200;
    }
    if ($limit > 500) {
        $limit = 500;
    }

    $category = trim((string) ($get['category'] ?? 'allProducts'));
    $allowedKeys = stockReportAllowedExtraFilterKeys($category);
    $stockStatuses = parseStockReportStockStatusFilters($get);

    $filters = [
        'search' => trim((string) ($get['search'] ?? '')),
        'location' => trim((string) ($get['location'] ?? '')),
        'category' => $category !== '' ? $category : 'allProducts',
        'physical_stock_status' => $stockStatuses['physical_stock_status'],
        'local_stock_status' => $stockStatuses['local_stock_status'],
        'limit' => $limit,
        'page_no' => isset($get['page_no']) ? max(1, (int) $get['page_no']) : 1,
        'warehouse_id' => $warehouseId,
    ];

    foreach (['size', 'color', 'isbn'] as $key) {
        if (in_array($key, $allowedKeys, true)) {
            $filters[$key] = trim((string) ($get[$key] ?? ''));
        }
    }

    if (in_array('material', $allowedKeys, true)) {
        $filters['material'] = resolveProductListMaterialFilter($get);
    }
    if (in_array('language', $allowedKeys, true)) {
        $filters['language'] = resolveProductListLanguageFilter($get);
    }
    if (in_array('author', $allowedKeys, true)) {
        $filters['author'] = resolveProductListAuthorFilter($get);
    }
    if (in_array('artist', $allowedKeys, true)) {
        $filters['artist'] = resolveProductListArtistFilter($get);
    }
    if (in_array('publisher', $allowedKeys, true)) {
        $filters['publisher'] = resolveProductListPublisherFilter($get);
    }

    return $filters;
}

/** @param array<string, mixed> $filters */
function stockReportFiltersPanelOpen(array $filters, bool $canChangeWarehouse): bool
{
    if (trim((string) ($filters['search'] ?? '')) !== '') {
        return true;
    }
    if (trim((string) ($filters['location'] ?? '')) !== '') {
        return true;
    }
    if (($filters['category'] ?? 'allProducts') !== 'allProducts') {
        return true;
    }
    if (($filters['physical_stock_status'] ?? 'all') !== 'all') {
        return true;
    }
    if (($filters['local_stock_status'] ?? 'all') !== 'all') {
        return true;
    }
    if ($canChangeWarehouse && (int) ($filters['warehouse_id'] ?? 0) > 0) {
        return true;
    }

    foreach (stockReportAllowedExtraFilterKeys((string) ($filters['category'] ?? 'allProducts')) as $key) {
        if (trim((string) ($filters[$key] ?? '')) !== '') {
            return true;
        }
    }

    return false;
}

/**
 * Apply scalar LIKE filters and author/publisher filters to stock report WHERE clause.
 *
 * @param array<string, mixed> $filters
 */
function appendStockReportExtraFiltersSql(
    string &$where,
    array &$params,
    string &$types,
    array $filters,
    mysqli $db
): void {
    $category = trim((string) ($filters['category'] ?? 'allProducts'));
    $allowedKeys = stockReportAllowedExtraFilterKeys($category);

    $likeColumns = [
        'size' => 'size',
        'color' => 'color',
        'isbn' => 'isbn',
    ];

    foreach ($likeColumns as $filterKey => $column) {
        if (!in_array($filterKey, $allowedKeys, true)) {
            continue;
        }
        $value = normalizeOrderFilterSearchText((string) ($filters[$filterKey] ?? ''));
        if ($value === '') {
            continue;
        }
        $where .= " AND p.{$column} LIKE ? ";
        $params[] = '%' . $value . '%';
        $types .= 's';
    }

    if (in_array('material', $allowedKeys, true)) {
        $material = normalizeOrderFilterSearchText((string) ($filters['material'] ?? ''));
        if ($material !== '') {
            $where .= ' AND IFNULL(p.material, \'\') LIKE ? ';
            $params[] = '%' . $material . '%';
            $types .= 's';
        }
    }

    if (in_array('language', $allowedKeys, true)) {
        $language = normalizeOrderFilterSearchText((string) ($filters['language'] ?? ''));
        if ($language !== '') {
            $where .= ' AND IFNULL(p.language, \'\') LIKE ? ';
            $params[] = '%' . $language . '%';
            $types .= 's';
        }
    }

    $authorPublisherSql = '';
    if (in_array('author', $allowedKeys, true) && !empty($filters['author'])) {
        appendProductListAuthorFilterSql($authorPublisherSql, $db, (string) $filters['author']);
    }
    if (in_array('artist', $allowedKeys, true) && !empty($filters['artist'])) {
        appendProductListAuthorFilterSql($authorPublisherSql, $db, (string) $filters['artist']);
    }
    if (in_array('publisher', $allowedKeys, true) && !empty($filters['publisher'])) {
        appendProductListPublisherFilterSql($authorPublisherSql, $db, (string) $filters['publisher']);
    }

    if ($authorPublisherSql !== '') {
        $authorPublisherSql = str_replace('vp_products.', 'p.', $authorPublisherSql);
        $where .= $authorPublisherSql;
    }
}

/** @param array<string, mixed> $filters @return array<string, mixed> */
function stockReportFiltersForExportPayload(array $filters): array
{
    $payload = [
        'search' => $filters['search'] ?? '',
        'location' => $filters['location'] ?? '',
        'category' => $filters['category'] ?? 'allProducts',
        'physical_stock_status' => $filters['physical_stock_status'] ?? 'all',
        'local_stock_status' => $filters['local_stock_status'] ?? 'all',
        'warehouse_id' => (int) ($filters['warehouse_id'] ?? 0),
    ];

    foreach (stockReportAllowedExtraFilterKeys((string) ($payload['category'] ?? 'allProducts')) as $key) {
        if (trim((string) ($filters[$key] ?? '')) !== '') {
            $payload[$key] = $filters[$key];
        }
    }

    return $payload;
}

/**
 * Manage listing (products list) — same group-specific filters as stock report.
 *
 * @param array<string, mixed> $get
 * @return array<string, mixed>
 */
function parseProductListExtraFiltersFromRequest(array $get, string $itemGroup): array
{
    $itemGroup = trim($itemGroup);
    $allowedKeys = stockReportAllowedExtraFilterKeys($itemGroup);
    $extra = [];

    foreach (['size', 'color', 'isbn'] as $key) {
        if (in_array($key, $allowedKeys, true)) {
            $value = trim((string) ($get[$key] ?? ''));
            if ($value !== '') {
                $extra[$key] = $value;
            }
        }
    }

    if (in_array('material', $allowedKeys, true)) {
        $material = resolveProductListMaterialFilter($get);
        if ($material !== '') {
            $extra['material'] = $material;
        }
    }
    if (in_array('language', $allowedKeys, true)) {
        $language = resolveProductListLanguageFilter($get);
        if ($language !== '') {
            $extra['language'] = $language;
        }
    }
    if (in_array('author', $allowedKeys, true)) {
        $author = resolveProductListAuthorFilter($get);
        if ($author !== '') {
            $extra['author'] = $author;
        }
    }
    if (in_array('artist', $allowedKeys, true)) {
        $artist = resolveProductListArtistFilter($get);
        if ($artist !== '') {
            $extra['artist'] = $artist;
        }
    }
    if (in_array('publisher', $allowedKeys, true)) {
        $publisher = resolveProductListPublisherFilter($get);
        if ($publisher !== '') {
            $extra['publisher'] = $publisher;
        }
    }

    return $extra;
}

/**
 * @param array<string, mixed> $filters
 */
function appendProductListStockStatusFiltersSql(string &$search, array $filters): void
{
    appendStockReportQuantityStatusSql(
        $search,
        'vp_products.physical_stock',
        (string) ($filters['physical_stock_status'] ?? 'all')
    );
    appendStockReportQuantityStatusSql(
        $search,
        'vp_products.local_stock',
        (string) ($filters['local_stock_status'] ?? 'all')
    );
}

/**
 * @param array<string, mixed> $filters
 */
function appendProductListExtraFiltersSql(string &$search, mysqli $db, array $filters): void
{
    $itemGroup = trim((string) ($filters['groupname'] ?? ''));
    $allowedKeys = stockReportAllowedExtraFilterKeys($itemGroup);

    $likeColumns = [
        'size' => 'size',
        'color' => 'color',
        'isbn' => 'isbn',
    ];

    foreach ($likeColumns as $filterKey => $column) {
        if (!in_array($filterKey, $allowedKeys, true)) {
            continue;
        }
        $value = normalizeOrderFilterSearchText((string) ($filters[$filterKey] ?? ''));
        if ($value === '') {
            continue;
        }
        $escaped = $db->real_escape_string($value);
        $search .= " AND vp_products.{$column} LIKE '%{$escaped}%'";
    }

    if (in_array('material', $allowedKeys, true)) {
        $material = normalizeOrderFilterSearchText((string) ($filters['material'] ?? ''));
        if ($material !== '') {
            $escaped = $db->real_escape_string($material);
            $search .= " AND IFNULL(vp_products.material, '') LIKE '%{$escaped}%'";
        }
    }

    if (in_array('language', $allowedKeys, true)) {
        $language = normalizeOrderFilterSearchText((string) ($filters['language'] ?? ''));
        if ($language !== '') {
            $escaped = $db->real_escape_string($language);
            $search .= " AND IFNULL(vp_products.language, '') LIKE '%{$escaped}%'";
        }
    }

    if (!empty($filters['author']) && in_array('author', $allowedKeys, true)) {
        appendProductListAuthorFilterSql($search, $db, (string) $filters['author']);
    }
    if (!empty($filters['artist']) && in_array('artist', $allowedKeys, true)) {
        appendProductListAuthorFilterSql($search, $db, (string) $filters['artist']);
    }
    if (!empty($filters['publisher']) && in_array('publisher', $allowedKeys, true)) {
        appendProductListPublisherFilterSql($search, $db, (string) $filters['publisher']);
    }
}

/** @param array<string, mixed> $get */
function productListFiltersPanelOpen(array $get): bool
{
    foreach (['item_code', 'item_name', 'vendor_name', 'sku', 'marketplace', 'size', 'color', 'isbn', 'material', 'language', 'author', 'artist', 'publisher'] as $key) {
        if (trim((string) ($get[$key] ?? '')) !== '') {
            return true;
        }
    }

    if (trim((string) ($get['item_group'] ?? '')) !== '') {
        return true;
    }

    $stockStatuses = parseStockReportStockStatusFilters($get);
    if (($stockStatuses['physical_stock_status'] ?? 'all') !== 'all') {
        return true;
    }
    if (($stockStatuses['local_stock_status'] ?? 'all') !== 'all') {
        return true;
    }

    foreach (['low_stock', 'permanently_available', 'local_stock'] as $key) {
        if (isset($get[$key]) && (string) $get[$key] !== '') {
            return true;
        }
    }

    return false;
}
