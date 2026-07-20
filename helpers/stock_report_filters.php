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
            'placeholder' => 'Material',
            'groups' => ['textiles', 'jewelry', 'paintings', 'sculptures', 'homeandliving'],
        ],
        'author' => [
            'label' => 'Artist / Author',
            'placeholder' => 'Artist or author name',
            'labels' => [
                'book' => 'Author',
                'paintings' => 'Artist',
                'sculptures' => 'Artist',
            ],
            'groups' => ['book', 'paintings', 'sculptures'],
            'autocomplete' => 'author',
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
            'placeholder' => 'Language',
            'groups' => ['book'],
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
        'category' => $category !== '' ? $category : 'allProducts',
        'physical_stock_status' => $stockStatuses['physical_stock_status'],
        'local_stock_status' => $stockStatuses['local_stock_status'],
        'limit' => $limit,
        'page_no' => isset($get['page_no']) ? max(1, (int) $get['page_no']) : 1,
        'warehouse_id' => $warehouseId,
    ];

    foreach (['size', 'color', 'material', 'isbn', 'language'] as $key) {
        if (in_array($key, $allowedKeys, true)) {
            $filters[$key] = trim((string) ($get[$key] ?? ''));
        }
    }

    if (in_array('author', $allowedKeys, true)) {
        $filters['author'] = resolveProductListAuthorFilter($get);
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
        'material' => 'material',
        'isbn' => 'isbn',
        'language' => 'language',
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

    $authorPublisherSql = '';
    if (in_array('author', $allowedKeys, true) && !empty($filters['author'])) {
        appendProductListAuthorFilterSql($authorPublisherSql, $db, (string) $filters['author']);
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
