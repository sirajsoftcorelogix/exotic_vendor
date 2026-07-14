<?php

function orderFilterAutocompleteMinLength(): int
{
    return 2;
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
