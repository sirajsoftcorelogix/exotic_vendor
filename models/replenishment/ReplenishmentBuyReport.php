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
            $where .= ' AND purchased = 1';
        } elseif ($purchased === 'no') {
            $where .= ' AND purchased = 0';
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? $filters['run_date'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo === '' && trim((string) ($filters['run_date'] ?? '')) !== '' && $dateFrom === trim((string) $filters['run_date'])) {
            $dateTo = $dateFrom;
        }
        $dateFromOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) === 1;
        $dateToOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) === 1;
        if ($dateFromOk && $dateToOk) {
            $where .= ' AND run_date BETWEEN ? AND ?';
            $types .= 'ss';
            $params[] = $dateFrom;
            $params[] = $dateTo;
        } elseif ($dateFromOk) {
            $where .= ' AND run_date >= ?';
            $types .= 's';
            $params[] = $dateFrom;
        } elseif ($dateToOk) {
            $where .= ' AND run_date <= ?';
            $types .= 's';
            $params[] = $dateTo;
        }

        $sku = trim((string) ($filters['sku'] ?? ''));
        if ($sku !== '') {
            $where .= ' AND sku LIKE ?';
            $types .= 's';
            $params[] = '%' . $sku . '%';
        }

        $itemCode = trim((string) ($filters['item_code'] ?? ''));
        if ($itemCode !== '') {
            $where .= ' AND item_code LIKE ?';
            $types .= 's';
            $params[] = '%' . $itemCode . '%';
        }

        $title = trim((string) ($filters['title'] ?? ''));
        if ($title !== '') {
            $where .= ' AND title LIKE ?';
            $types .= 's';
            $params[] = '%' . $title . '%';
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where .= ' AND (sku LIKE ? OR item_code LIKE ? OR title LIKE ?)';
            $like = '%' . $search . '%';
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $source = strtolower(trim((string) ($filters['lookback_source'] ?? '')));
        if (in_array($source, ['product', 'publisher', 'vendor', 'global'], true)) {
            $where .= ' AND lookback_source = ?';
            $types .= 's';
            $params[] = $source;
        }

        if (isset($filters['min_buy_qty']) && $filters['min_buy_qty'] !== '' && is_numeric($filters['min_buy_qty'])) {
            $where .= ' AND replenishment_buy_qty >= ?';
            $types .= 'i';
            $params[] = max(0, (int) $filters['min_buy_qty']);
        }

        return ['where' => $where, 'types' => $types, 'params' => $params];
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
        $countSql = 'SELECT COUNT(*) AS cnt FROM vp_replenishment_buy_report' . $where;
        $countStmt = $this->conn->prepare($countSql);
        if ($countStmt) {
            if ($types !== '') {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $total = (int) ($countStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
            $countStmt->close();
        }

        $sql = 'SELECT * FROM vp_replenishment_buy_report' . $where
            . ' ORDER BY run_date DESC, id DESC LIMIT ? OFFSET ?';
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
        $sql = 'SELECT * FROM vp_replenishment_buy_report' . $clause['where']
            . ' ORDER BY run_date DESC, id DESC LIMIT ?';
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
