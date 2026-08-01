<?php

/** @return list<string> */
function creatorMasterAuthorFieldNames(): array
{
    return ['author', 'edited_by', 'compiled_by', 'translated_by', 'commentary_by'];
}

function creatorMasterAuthorMatchSql(string $tableAlias, string $authorIdExpr): string
{
    $parts = [];
    foreach (creatorMasterAuthorFieldNames() as $column) {
        $parts[] = "FIND_IN_SET({$authorIdExpr}, REPLACE(IFNULL({$tableAlias}.{$column}, ''), ' ', ''))";
        $parts[] = "CAST({$tableAlias}.{$column} AS CHAR) = CAST({$authorIdExpr} AS CHAR)";
    }

    return '(' . implode(' OR ', $parts) . ')';
}

/**
 * @return array{inbound:int, products:int, total:int}
 */
function creatorMasterCountAuthorUsage(mysqli $conn, int $authorId): array
{
    if ($authorId <= 0) {
        return ['inbound' => 0, 'products' => 0, 'total' => 0];
    }

    $authorIdExpr = (string) $authorId;
    $inboundSql = 'SELECT COUNT(*) AS total FROM vp_inbound t WHERE ' . creatorMasterAuthorMatchSql('t', '?');
    $productSql = 'SELECT COUNT(*) AS total FROM vp_products t WHERE ' . creatorMasterAuthorMatchSql('t', '?');

    $inbound = creatorMasterRunAuthorUsageCount($conn, $inboundSql, $authorIdExpr);
    $products = creatorMasterRunAuthorUsageCount($conn, $productSql, $authorIdExpr);

    return [
        'inbound' => $inbound,
        'products' => $products,
        'total' => $inbound + $products,
    ];
}

/**
 * @return array{inbound:int, products:int, total:int}
 */
function creatorMasterCountPublisherUsage(mysqli $conn, int $publishersId, string $publisherName): array
{
    if ($publishersId <= 0) {
        return ['inbound' => 0, 'products' => 0, 'total' => 0];
    }

    $inbound = 0;
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM vp_inbound WHERE publisher = ?');
    if ($stmt) {
        $stmt->bind_param('i', $publishersId);
        $stmt->execute();
        $inbound = (int) (($stmt->get_result()->fetch_assoc()['total'] ?? 0));
        $stmt->close();
    }

    $products = 0;
    $publisherName = trim($publisherName);
    $publishersIdStr = (string) $publishersId;
    $productSql = 'SELECT COUNT(*) AS total FROM vp_products
                   WHERE CAST(IFNULL(publisher, \'\') AS CHAR) = ?
                      OR LOWER(TRIM(IFNULL(publisher, \'\'))) = LOWER(TRIM(?))';
    $stmt = $conn->prepare($productSql);
    if ($stmt) {
        $stmt->bind_param('ss', $publishersIdStr, $publisherName);
        $stmt->execute();
        $products = (int) (($stmt->get_result()->fetch_assoc()['total'] ?? 0));
        $stmt->close();
    }

    return [
        'inbound' => $inbound,
        'products' => $products,
        'total' => $inbound + $products,
    ];
}

function creatorMasterUsageError(string $entityLabel, int $inboundCount, int $productCount, string $action = 'delete or deactivate'): ?array
{
    $total = $inboundCount + $productCount;
    if ($total <= 0) {
        return null;
    }

    $parts = [];
    if ($inboundCount > 0) {
        $parts[] = $inboundCount === 1 ? '1 inbound record' : $inboundCount . ' inbound records';
    }
    if ($productCount > 0) {
        $parts[] = $productCount === 1 ? '1 product' : $productCount . ' products';
    }

    return [
        'success' => false,
        'message' => 'Cannot ' . $action . ': ' . $entityLabel . ' is used by ' . implode(' and ', $parts) . '. Remove the mapping first.',
    ];
}

function creatorMasterRunAuthorUsageCount(mysqli $conn, string $sql, string $authorIdExpr): int
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }

    $types = str_repeat('is', count(creatorMasterAuthorFieldNames()));
    $params = [];
    for ($i = 0, $count = count(creatorMasterAuthorFieldNames()); $i < $count; $i++) {
        $params[] = $authorIdExpr;
        $params[] = $authorIdExpr;
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = (int) (($stmt->get_result()->fetch_assoc()['total'] ?? 0));
    $stmt->close();

    return $total;
}

function creatorMasterAuthorUsageSelectSql(string $authorIdColumnExpr, string $inboundAlias = 'vi', string $productAlias = 'pr'): string
{
    return '(SELECT COUNT(*) FROM vp_inbound ' . $inboundAlias . ' WHERE ' . creatorMasterAuthorMatchSql($inboundAlias, $authorIdColumnExpr) . ') AS inbound_usage_count, '
        . '(SELECT COUNT(*) FROM vp_products ' . $productAlias . ' WHERE ' . creatorMasterAuthorMatchSql($productAlias, $authorIdColumnExpr) . ') AS product_usage_count';
}

function creatorMasterPublisherUsageSelectSql(string $publishersIdColumnExpr, string $publisherNameColumnExpr): string
{
    return '(SELECT COUNT(*) FROM vp_inbound vi WHERE vi.publisher = ' . $publishersIdColumnExpr . ') AS inbound_usage_count, '
        . '(SELECT COUNT(*) FROM vp_products pr WHERE CAST(IFNULL(pr.publisher, \'\') AS CHAR) = CAST(' . $publishersIdColumnExpr . ' AS CHAR)'
        . ' OR LOWER(TRIM(IFNULL(pr.publisher, \'\'))) = LOWER(TRIM(' . $publisherNameColumnExpr . '))) AS product_usage_count';
}
