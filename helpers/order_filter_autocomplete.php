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

/**
 * @return array<int, array{id:int|string, name:string}>
 */
function searchOrderFilterMaterials(mysqli $conn, string $query): array
{
    $query = trim($query);
    if (strlen($query) < orderFilterAutocompleteMinLength()) {
        return [];
    }

    $like = '%' . $query . '%';
    $numericId = ctype_digit($query) ? (int) $query : 0;
    $stmt = $conn->prepare(
        'SELECT id, material_name AS name
         FROM material
         WHERE is_active = 1 AND (material_name LIKE ? OR material_slug LIKE ? OR id = ?)
         ORDER BY material_name
         LIMIT 20'
    );
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('ssi', $like, $like, $numericId);
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
function searchOrderFilterLanguages(mysqli $conn, string $query): array
{
    $query = trim($query);
    if (strlen($query) < orderFilterAutocompleteMinLength()) {
        return [];
    }

    $like = '%' . $query . '%';
    $numericId = ctype_digit($query) ? (int) $query : 0;
    $stmt = $conn->prepare(
        'SELECT id, language_name AS name
         FROM book_languages
         WHERE active = 1 AND (language_name LIKE ? OR iso LIKE ? OR id = ?)
         ORDER BY language_name
         LIMIT 20'
    );
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('ssi', $like, $like, $numericId);
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
        'material' => searchOrderFilterMaterials($conn, $query),
        'language' => searchOrderFilterLanguages($conn, $query),
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

function resolveProductListAuthorFilter(array $get): string
{
    return !empty($get['author']) ? normalizeOrderFilterSearchText($get['author']) : '';
}

function resolveProductListPublisherFilter(array $get): string
{
    return !empty($get['publisher']) ? normalizeOrderFilterSearchText($get['publisher']) : '';
}

function resolveProductListMaterialFilter(array $get): string
{
    return !empty($get['material']) ? normalizeOrderFilterSearchText($get['material']) : '';
}

function resolveProductListArtistFilter(array $get): string
{
    return !empty($get['artist']) ? normalizeOrderFilterSearchText($get['artist']) : '';
}

function resolveProductListLanguageFilter(array $get): string
{
    return !empty($get['language']) ? normalizeOrderFilterSearchText($get['language']) : '';
}

function appendProductListAuthorFilterSql(string &$search, mysqli $db, string $author): void
{
    $author = normalizeOrderFilterSearchText($author);
    if ($author === '') {
        return;
    }

    $escaped = $db->real_escape_string($author);
    $parts = ["IFNULL(vp_products.author, '') LIKE '%{$escaped}%'"];

    $authorIds = [];
    if (ctype_digit($author)) {
        $authorIds[] = (int) $author;
    }

    $stmt = $db->prepare('SELECT author_id FROM vp_author WHERE is_active = 1 AND LOWER(TRIM(author)) = LOWER(TRIM(?)) LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $author);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        if (!empty($row['author_id'])) {
            $authorIds[] = (int) $row['author_id'];
        }
    }

    foreach (array_values(array_unique(array_filter($authorIds))) as $authorId) {
        $authorId = (int) $authorId;
        if ($authorId <= 0) {
            continue;
        }
        $parts[] = "vp_products.author = '{$authorId}'";
        $parts[] = "FIND_IN_SET('{$authorId}', REPLACE(IFNULL(vp_products.author, ''), ' ', ''))";
    }

    $search .= ' AND (' . implode(' OR ', $parts) . ')';
}

function appendProductListPublisherFilterSql(string &$search, mysqli $db, string $publisher): void
{
    $publisher = normalizeOrderFilterSearchText($publisher);
    if ($publisher === '') {
        return;
    }

    $escaped = $db->real_escape_string($publisher);
    $parts = ["IFNULL(vp_products.publisher, '') LIKE '%{$escaped}%'"];

    $publisherIds = [];
    if (ctype_digit($publisher)) {
        $publisherIds[] = (int) $publisher;
    }

    $stmt = $db->prepare('SELECT publishers_id FROM vp_publishers WHERE is_active = 1 AND LOWER(TRIM(publishers)) = LOWER(TRIM(?)) LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $publisher);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        if (!empty($row['publishers_id'])) {
            $publisherIds[] = (int) $row['publishers_id'];
        }
    }

    foreach (array_values(array_unique(array_filter($publisherIds))) as $publisherId) {
        $publisherId = (int) $publisherId;
        if ($publisherId <= 0) {
            continue;
        }
        $parts[] = "vp_products.publisher = '{$publisherId}'";
    }

    $search .= ' AND (' . implode(' OR ', $parts) . ')';
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
