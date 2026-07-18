<?php

function orderFilterAutocompleteMinLength(): int
{
    return 2;
}

function normalizeOrderFilterSearchText($value): string
{
    return trim(strip_tags((string) $value));
}

function orderFilterLikeTerm(string $value): string
{
    return '%' . $value . '%';
}

function appendOrderVendorNameFilterSql(string &$sql, array &$params, string $vendor): void
{
    $vendor = normalizeOrderFilterSearchText($vendor);
    if ($vendor === '') {
        return;
    }

    $sql .= " AND IFNULL(vp_orders.vendor, '') LIKE ?";
    $params[] = orderFilterLikeTerm($vendor);
}

function appendOrderAuthorNameFilterSql(string &$sql, array &$params, string $author): void
{
    $author = normalizeOrderFilterSearchText($author);
    if ($author === '') {
        return;
    }

    $sql .= " AND IFNULL(vp_orders.author, '') LIKE ?";
    $params[] = orderFilterLikeTerm($author);
}

function appendOrderPublisherNameFilterSql(string &$sql, array &$params, string $publisher): void
{
    $publisher = normalizeOrderFilterSearchText($publisher);
    if ($publisher === '') {
        return;
    }

    $sql .= " AND IFNULL(vp_orders.publisher, '') LIKE ?";
    $params[] = orderFilterLikeTerm($publisher);
}

/**
 * @return array<int, array{id:int|string, name:string}>
 */
function searchOrderFilterVendors(mysqli $conn, string $query): array
{
    $query = trim($query);
    if (strlen($query) < orderFilterAutocompleteMinLength()) {
        return [];
    }

    $like = '%' . $query . '%';
    $stmt = $conn->prepare(
        'SELECT id, vendor_name AS name
         FROM vp_vendors
         WHERE vendor_name LIKE ?
         ORDER BY vendor_name
         LIMIT 20'
    );
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('s', $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return array_map(static function (array $row): array {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
        ];
    }, $rows);
}

/**
 * @return array<int, array{id:int|string, name:string}>
 */
function searchOrderFilterAuthors(mysqli $conn, string $query): array
{
    $query = trim($query);
    if (strlen($query) < orderFilterAutocompleteMinLength()) {
        return [];
    }

    $like = '%' . $query . '%';
    $numericId = ctype_digit($query) ? (int) $query : 0;
    $stmt = $conn->prepare(
        'SELECT author_id AS id, author AS name
         FROM vp_author
         WHERE is_active = 1 AND (author LIKE ? OR author_id = ?)
         ORDER BY author
         LIMIT 20'
    );
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('si', $like, $numericId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return array_map(static function (array $row): array {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
        ];
    }, $rows);
}

/**
 * @return array<int, array{id:int|string, name:string}>
 */
function searchOrderFilterPublishers(mysqli $conn, string $query): array
{
    $query = trim($query);
    if (strlen($query) < orderFilterAutocompleteMinLength()) {
        return [];
    }

    $like = '%' . $query . '%';
    $numericId = ctype_digit($query) ? (int) $query : 0;
    $stmt = $conn->prepare(
        'SELECT publishers_id AS id, publishers AS name
         FROM vp_publishers
         WHERE is_active = 1 AND (publishers LIKE ? OR publishers_id = ?)
         ORDER BY publishers
         LIMIT 20'
    );
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('si', $like, $numericId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return array_map(static function (array $row): array {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
        ];
    }, $rows);
}

function orderFilterAutocompleteJson(mysqli $conn, string $type, string $query): void
{
    $results = match ($type) {
        'vendor' => searchOrderFilterVendors($conn, $query),
        'author' => searchOrderFilterAuthors($conn, $query),
        'publisher' => searchOrderFilterPublishers($conn, $query),
        default => [],
    };

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => true, 'data' => $results], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function resolveOrderListVendorFilter(array $get): string
{
    if (!empty($get['vendor'])) {
        return normalizeOrderFilterSearchText($get['vendor']);
    }

    if (!empty($get['vendor_name'])) {
        return normalizeOrderFilterSearchText($get['vendor_name']);
    }

    return '';
}

function resolveOrderListAuthorFilter(array $get): string
{
    return !empty($get['author']) ? normalizeOrderFilterSearchText($get['author']) : '';
}

function resolveOrderListPublisherFilter(array $get): string
{
    return !empty($get['publisher']) ? normalizeOrderFilterSearchText($get['publisher']) : '';
}

/** vp_orders.store_name holds exotic_address.id for POS / store-origin orders. */
function orderListHasStoreNameSql(string $ordersAlias = 'vp_orders'): string
{
    return "{$ordersAlias}.store_name IS NOT NULL"
        . " AND TRIM(CAST({$ordersAlias}.store_name AS CHAR)) <> ''"
        . " AND LOWER(TRIM(CAST({$ordersAlias}.store_name AS CHAR))) <> 'null'"
        . " AND CAST({$ordersAlias}.store_name AS UNSIGNED) > 0";
}

function orderListExoticAddressJoinSql(string $ordersAlias = 'vp_orders'): string
{
    $hasStore = orderListHasStoreNameSql($ordersAlias);

    return " LEFT JOIN exotic_address ea_store ON ea_store.id = CAST({$ordersAlias}.store_name AS UNSIGNED)"
        . " AND {$hasStore}";
}

/** Order list Staff Name: store address when store_name is set, else PO staff user name. */
function orderListStaffNameSelectSql(string $usersAlias = 'vp_users', string $ordersAlias = 'vp_orders'): string
{
    $hasStore = orderListHasStoreNameSql($ordersAlias);

    return 'CASE'
        . " WHEN {$hasStore} THEN COALESCE(NULLIF(TRIM(ea_store.display_name), ''), NULLIF(TRIM(ea_store.address_title), ''), CAST({$ordersAlias}.store_name AS CHAR))"
        . " ELSE {$usersAlias}.name"
        . ' END AS staff_name';
}

/** Whether order list row should show Store Name instead of Staff Name (matches orderListHasStoreNameSql). */
function orderListUsesStoreName(array $row): bool
{
    if (!array_key_exists('store_name', $row) || $row['store_name'] === null) {
        return false;
    }
    $raw = trim((string) $row['store_name']);
    if ($raw === '' || strtolower($raw) === 'null') {
        return false;
    }

    return (int) $raw > 0;
}
