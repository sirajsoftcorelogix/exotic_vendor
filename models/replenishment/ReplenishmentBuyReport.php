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
     * @return array{rows:array<int,array<string,mixed>>,total:int,page:int,pages:int,limit:int}
     */
    public function searchList(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = (int) ($filters['limit'] ?? 20);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;
        $offset = ($page - 1) * $limit;
        $search = trim((string) ($filters['search'] ?? ''));
        $purchased = trim((string) ($filters['purchased'] ?? 'no'));
        $runDate = trim((string) ($filters['run_date'] ?? ''));

        $where = ' WHERE 1=1';
        $types = '';
        $params = [];

        if ($purchased === 'yes') {
            $where .= ' AND purchased = 1';
        } elseif ($purchased === 'no') {
            $where .= ' AND purchased = 0';
        }

        if ($runDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $runDate)) {
            $where .= ' AND run_date = ?';
            $types .= 's';
            $params[] = $runDate;
        }

        if ($search !== '') {
            $where .= ' AND (sku LIKE ? OR item_code LIKE ? OR title LIKE ?)';
            $like = '%' . $search . '%';
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

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
