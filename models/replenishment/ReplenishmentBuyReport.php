<?php

class ReplenishmentBuyReport
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function tableExists(): bool
    {
        $res = $this->conn->query("SHOW TABLES LIKE 'vp_replenishment_buy_report'");

        return $res && $res->num_rows > 0;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function upsertRecommendation(array $row): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        $sql = 'INSERT INTO vp_replenishment_buy_report (
                    run_date, product_id, sku, item_code, title, yesterday_sold_qty,
                    numsold_replenishment, lookback_months, lookback_source, numsold_source,
                    physical_stock, pending_po_qty, available_stock,
                    purchase_threshold_percent, purchase_threshold_qty, min_stock_percent,
                    replenishment_buy_qty, purchased
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
                ON DUPLICATE KEY UPDATE
                    sku = VALUES(sku),
                    item_code = VALUES(item_code),
                    title = VALUES(title),
                    yesterday_sold_qty = VALUES(yesterday_sold_qty),
                    numsold_replenishment = VALUES(numsold_replenishment),
                    lookback_months = VALUES(lookback_months),
                    lookback_source = VALUES(lookback_source),
                    numsold_source = VALUES(numsold_source),
                    physical_stock = VALUES(physical_stock),
                    pending_po_qty = VALUES(pending_po_qty),
                    available_stock = VALUES(available_stock),
                    purchase_threshold_percent = VALUES(purchase_threshold_percent),
                    purchase_threshold_qty = VALUES(purchase_threshold_qty),
                    min_stock_percent = VALUES(min_stock_percent),
                    replenishment_buy_qty = VALUES(replenishment_buy_qty),
                    updated_at = CURRENT_TIMESTAMP';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $runDate = (string) $row['run_date'];
        $productId = (int) $row['product_id'];
        $sku = (string) $row['sku'];
        $itemCode = (string) $row['item_code'];
        $title = (string) $row['title'];
        $yesterdayQty = (int) $row['yesterday_sold_qty'];
        $numsold = (int) $row['numsold_replenishment'];
        $lookbackMonths = (int) $row['lookback_months'];
        $lookbackSource = (string) $row['lookback_source'];
        $numsoldSource = (string) $row['numsold_source'];
        $physical = (int) $row['physical_stock'];
        $pendingPo = (int) $row['pending_po_qty'];
        $available = (int) $row['available_stock'];
        $thresholdPct = (int) $row['purchase_threshold_percent'];
        $thresholdQty = (int) $row['purchase_threshold_qty'];
        $minPct = (int) $row['min_stock_percent'];
        $buyQty = (int) $row['replenishment_buy_qty'];

        $stmt->bind_param(
            'sisssiiissiiiiiii',
            $runDate,
            $productId,
            $sku,
            $itemCode,
            $title,
            $yesterdayQty,
            $numsold,
            $lookbackMonths,
            $lookbackSource,
            $numsoldSource,
            $physical,
            $pendingPo,
            $available,
            $thresholdPct,
            $thresholdQty,
            $minPct,
            $buyQty
        );
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    /**
     * @return array{where:string,types:string,params:array<int,mixed>}
     */
    private function buildSearchWhere(array $filters): array
    {
        $where = ' WHERE 1=1';
        $types = '';
        $params = [];

        $purchased = trim((string) ($filters['purchased'] ?? 'no'));
        if ($purchased === 'yes') {
            $where .= ' AND r.purchased = 1';
        } elseif ($purchased === 'no') {
            $where .= ' AND r.purchased = 0';
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? $filters['run_date'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo === '' && trim((string) ($filters['run_date'] ?? '')) !== '' && $dateFrom === trim((string) $filters['run_date'])) {
            $dateTo = $dateFrom;
        }
        $dateFromOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) === 1;
        $dateToOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) === 1;
        if ($dateFromOk && $dateToOk) {
            $where .= ' AND r.run_date BETWEEN ? AND ?';
            $types .= 'ss';
            $params[] = $dateFrom;
            $params[] = $dateTo;
        } elseif ($dateFromOk) {
            $where .= ' AND r.run_date >= ?';
            $types .= 's';
            $params[] = $dateFrom;
        } elseif ($dateToOk) {
            $where .= ' AND r.run_date <= ?';
            $types .= 's';
            $params[] = $dateTo;
        }

        $sku = trim((string) ($filters['sku'] ?? ''));
        if ($sku !== '') {
            $where .= ' AND r.sku LIKE ?';
            $types .= 's';
            $params[] = '%' . $sku . '%';
        }

        $itemCode = trim((string) ($filters['item_code'] ?? ''));
        if ($itemCode !== '') {
            $where .= ' AND r.item_code LIKE ?';
            $types .= 's';
            $params[] = '%' . $itemCode . '%';
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where .= ' AND (r.sku LIKE ? OR r.item_code LIKE ? OR r.title LIKE ?)';
            $like = '%' . $search . '%';
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $this->appendPublisherFilter($where, $types, $params, $filters);
        $this->appendVendorFilter($where, $types, $params, $filters);

        return ['where' => $where, 'types' => $types, 'params' => $params];
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<int, mixed> $params
     */
    private function appendPublisherFilter(string &$where, string &$types, array &$params, array $filters): void
    {
        $publisherId = (int) ($filters['publisher_id'] ?? 0);
        $publisherName = trim((string) ($filters['publisher'] ?? ''));
        if ($publisherId <= 0 && $publisherName === '') {
            return;
        }

        if ($publisherId > 0) {
            $idStr = (string) $publisherId;
            $where .= ' AND (
                EXISTS (
                    SELECT 1 FROM vp_products p
                    WHERE p.id = r.product_id
                      AND (
                          TRIM(IFNULL(p.publisher, \'\')) = ?
                          OR TRIM(IFNULL(p.publisher, \'\')) = ?
                      )
                )
                OR EXISTS (
                    SELECT 1 FROM product_vendor_map pvm
                    INNER JOIN publisher_vendor_mapping map ON map.vendor_id = pvm.vendor_id
                    INNER JOIN vp_publishers pub ON pub.id = map.publisher_id
                    WHERE ' . $this->itemCodeEquals('pvm.item_code', 'r.item_code') . '
                      AND pub.publishers_id = ?
                )
            )';
            $types .= 'ssi';
            $params[] = $idStr;
            $params[] = $publisherName;
            $params[] = $publisherId;
            return;
        }

        $like = '%' . $publisherName . '%';
        $where .= ' AND (
            EXISTS (
                SELECT 1 FROM vp_products p
                LEFT JOIN vp_publishers pub
                    ON (
                        ' . $this->utf8('CAST(pub.publishers_id AS CHAR)') . ' = ' . $this->utf8("TRIM(IFNULL(p.publisher, ''))") . '
                        OR ' . $this->utf8('pub.publishers') . ' = ' . $this->utf8("TRIM(IFNULL(p.publisher, ''))") . '
                    )
                WHERE p.id = r.product_id
                  AND (
                      TRIM(IFNULL(p.publisher, \'\')) LIKE ?
                      OR pub.publishers LIKE ?
                  )
            )
            OR EXISTS (
                SELECT 1 FROM product_vendor_map pvm
                INNER JOIN publisher_vendor_mapping map ON map.vendor_id = pvm.vendor_id
                INNER JOIN vp_publishers pub2 ON pub2.id = map.publisher_id
                WHERE ' . $this->itemCodeEquals('pvm.item_code', 'r.item_code') . '
                  AND pub2.publishers LIKE ?
            )
        )';
        $types .= 'sss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<int, mixed> $params
     */
    private function appendVendorFilter(string &$where, string &$types, array &$params, array $filters): void
    {
        $vendorId = (int) ($filters['vendor_id'] ?? 0);
        $vendorName = trim((string) ($filters['vendor'] ?? ''));
        if ($vendorId <= 0 && $vendorName === '') {
            return;
        }

        if ($vendorId > 0) {
            $where .= ' AND EXISTS (
                SELECT 1 FROM product_vendor_map pvm
                WHERE ' . $this->itemCodeEquals('pvm.item_code', 'r.item_code') . '
                  AND pvm.vendor_id = ?
            )';
            $types .= 'i';
            $params[] = $vendorId;
            return;
        }

        $like = '%' . $vendorName . '%';
        $where .= ' AND EXISTS (
            SELECT 1 FROM product_vendor_map pvm
            INNER JOIN vp_vendors v ON v.id = pvm.vendor_id
            WHERE ' . $this->itemCodeEquals('pvm.item_code', 'r.item_code') . '
              AND v.vendor_name LIKE ?
        )';
        $types .= 's';
        $params[] = $like;
    }

    /**
     * @return array{rows:array<int,array<string,mixed>>,total:int,page:int,pages:int,limit:int}
     */
    public function searchList(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = (int) ($filters['limit'] ?? 20);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;
        $offset = ($page - 1) * $limit;

        $clause = $this->buildSearchWhere($filters);
        $where = $clause['where'];
        $types = $clause['types'];
        $params = $clause['params'];

        $total = 0;
        $countSql = 'SELECT COUNT(*) AS cnt FROM vp_replenishment_buy_report r' . $where;
        $countStmt = $this->conn->prepare($countSql);
        if ($countStmt) {
            if ($types !== '') {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $total = (int) ($countStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
            $countStmt->close();
        }

        $sql = $this->selectListSql() . $where
            . ' ORDER BY r.run_date DESC, r.id DESC LIMIT ? OFFSET ?';
        $stmt = $this->conn->prepare($sql);
        $rows = [];
        if ($stmt) {
            $listTypes = $types . 'ii';
            $listParams = array_merge($params, [$limit, $offset]);
            $stmt->bind_param($listTypes, ...$listParams);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $stmt->close();
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $limit)),
            'limit' => $limit,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchAll(array $filters, int $maxRows = 10000): array
    {
        $maxRows = max(1, min(20000, $maxRows));
        $clause = $this->buildSearchWhere($filters);
        $sql = $this->selectListSql() . $clause['where']
            . ' ORDER BY r.run_date DESC, r.id DESC LIMIT ?';
        $stmt = $this->conn->prepare($sql);
        $rows = [];
        if (!$stmt) {
            return $rows;
        }
        $types = $clause['types'] . 'i';
        $params = array_merge($clause['params'], [$maxRows]);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

        return $rows;
    }

    private function utf8(string $expr): string
    {
        return 'CONVERT(' . $expr . ' USING utf8mb4) COLLATE utf8mb4_unicode_ci';
    }

    private function itemCodeEquals(string $left, string $right): string
    {
        return $this->utf8($left) . ' = ' . $this->utf8($right);
    }

    private function selectListSql(): string
    {
        $empty = $this->utf8("''");
        $publisherKey = $this->utf8("TRIM(IFNULL(p.publisher, ''))");
        $pubJoin = '('
            . $this->utf8('CAST(pub.publishers_id AS CHAR)') . ' = ' . $publisherKey
            . ' OR ' . $this->utf8('CAST(pub.id AS CHAR)') . ' = ' . $publisherKey
            . ' OR ' . $this->utf8('pub.publishers') . ' = ' . $publisherKey
            . ')';
        $fromProduct = 'COALESCE(NULLIF(' . $this->utf8('TRIM(pub.publishers)') . ', ' . $empty . '), ' . $publisherKey . ')';

        return 'SELECT r.*,
            COALESCE(
                NULLIF((
                    SELECT ' . $fromProduct . '
                    FROM vp_products p
                    LEFT JOIN vp_publishers pub ON ' . $pubJoin . '
                    WHERE p.id = r.product_id
                    LIMIT 1
                ), ' . $empty . '),
                (
                    SELECT ' . $this->utf8('pub2.publishers') . '
                    FROM product_vendor_map pvm2
                    INNER JOIN publisher_vendor_mapping map ON map.vendor_id = pvm2.vendor_id
                    INNER JOIN vp_publishers pub2 ON pub2.id = map.publisher_id
                    WHERE ' . $this->itemCodeEquals('pvm2.item_code', 'r.item_code') . '
                    ORDER BY pvm2.priority ASC, pvm2.id ASC, map.sort_order ASC, map.id ASC
                    LIMIT 1
                )
            ) AS publisher_name,
            (
                SELECT ' . $this->utf8('v.vendor_name') . '
                FROM product_vendor_map pvm
                INNER JOIN vp_vendors v ON v.id = pvm.vendor_id
                WHERE ' . $this->itemCodeEquals('pvm.item_code', 'r.item_code') . '
                ORDER BY pvm.priority ASC, pvm.id ASC
                LIMIT 1
            ) AS vendor_name
            FROM vp_replenishment_buy_report r';
    }

    public function setPurchased(int $id, bool $purchased, int $userId): bool
    {
        $id = max(0, $id);
        if ($id <= 0 || !$this->tableExists()) {
            return false;
        }

        $flag = $purchased ? 1 : 0;
        if ($purchased) {
            $sql = 'UPDATE vp_replenishment_buy_report
                    SET purchased = 1, purchased_at = NOW(), purchased_by = ?
                    WHERE id = ?';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('ii', $userId, $id);
        } else {
            $sql = 'UPDATE vp_replenishment_buy_report
                    SET purchased = 0, purchased_at = NULL, purchased_by = NULL
                    WHERE id = ?';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('i', $id);
        }

        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }
}
