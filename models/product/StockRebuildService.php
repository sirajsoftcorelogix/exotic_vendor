<?php

final class StockRebuildSqlException extends RuntimeException
{
    /** @var array<string, mixed> */
    private array $detail;

    /** @param array<string, mixed> $detail */
    public function __construct(string $message, array $detail = [], ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->detail = $detail;
    }

    /** @return array<string, mixed> */
    public function getDetail(): array
    {
        return $this->detail;
    }
}

require_once __DIR__ . '/StockMovement.php';
require_once __DIR__ . '/product.php';
require_once __DIR__ . '/../direct_purchase/DirectPurchaseStock.php';

final class StockRebuildService
{
    /** @var mysqli */
    private $conn;

    /** @var product */
    private $productModel;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->productModel = new product($conn);
    }

    public function getDefaultWarehouse(): array
    {
        $id = $this->resolveDefaultWarehouseId();

        return [
            'id' => $id,
            'name' => $this->warehouseLocationLabel($id),
        ];
    }

    public function preview(int $selectedWarehouseId): array
    {
        $selectedWarehouseId = (int) $selectedWarehouseId;
        if ($selectedWarehouseId <= 0) {
            return ['success' => false, 'message' => 'Please select a warehouse.'];
        }

        $defaultWarehouse = $this->getDefaultWarehouse();
        if ((int) ($defaultWarehouse['id'] ?? 0) <= 0) {
            return ['success' => false, 'message' => 'Default warehouse could not be resolved.'];
        }

        $scopeRows = $this->collectScopeProducts($selectedWarehouseId);
        if ($scopeRows === []) {
            return [
                'success' => false,
                'message' => 'No scoped SKUs were found for the selected warehouse.',
                'selected_warehouse' => [
                    'id' => $selectedWarehouseId,
                    'name' => $this->warehouseLocationLabel($selectedWarehouseId),
                ],
                'default_warehouse' => $defaultWarehouse,
            ];
        }

        $scopeSkus = array_keys($scopeRows);
        $scopeProductIds = $this->extractScopeProductIds($scopeRows);
        $scopeProductIdSet = array_fill_keys($scopeProductIds, true);

        $purchaseBatches = $this->collectPurchaseBatches((int) $defaultWarehouse['id'], $scopeRows);
        $returnBatches = $this->collectReturnBatches((int) $defaultWarehouse['id'], $scopeRows);
        $transferIns = $this->collectTransferInRows($selectedWarehouseId, $scopeRows);
        $transferOuts = $this->collectTransferOutRows($selectedWarehouseId, $scopeRows);
        $invoiceReplay = $this->collectInvoiceReplayData($selectedWarehouseId, $scopeProductIdSet);
        $deleteCounts = $this->collectDeleteCounts($scopeSkus, $scopeProductIds);
        $otherWarehouseUsage = $this->collectOtherWarehouseUsage(
            $scopeSkus,
            $scopeProductIds,
            (int) $defaultWarehouse['id'],
            $selectedWarehouseId
        );

        $openingCandidates = 0;
        foreach ($scopeRows as $row) {
            if ((int) ($row['local_stock'] ?? 0) > 0) {
                $openingCandidates++;
            }
        }

        $purchaseLineCount = 0;
        foreach ($purchaseBatches as $batch) {
            $purchaseLineCount += count((array) ($batch['lines'] ?? []));
        }

        $returnLineCount = 0;
        foreach ($returnBatches as $batch) {
            $returnLineCount += count((array) ($batch['lines'] ?? []));
        }

        $invoiceLineCount = 0;
        $invoiceHeaderCount = 0;
        $cancelHeaderCount = 0;
        foreach ((array) ($invoiceReplay['invoices'] ?? []) as $invoice) {
            $lines = (array) ($invoice['lines'] ?? []);
            if ($lines === []) {
                continue;
            }
            $invoiceHeaderCount++;
            $invoiceLineCount += count($lines);
            if (strtolower((string) ($invoice['status'] ?? '')) === 'cancelled') {
                $cancelHeaderCount++;
            }
        }

        $blockingWarnings = [];
        if ($otherWarehouseUsage !== []) {
            $warehouseNames = [];
            foreach ($otherWarehouseUsage as $row) {
                $warehouseNames[] = trim((string) ($row['warehouse_name'] ?? ('Warehouse #' . (int) ($row['warehouse_id'] ?? 0))));
            }
            $blockingWarnings[] = 'Scoped SKUs also exist in other warehouses outside the default/selected pair: '
                . implode(', ', array_unique($warehouseNames))
                . '. Current execution page blocks rebuild in this case to avoid removing stock history that it will not replay.';
        }

        return [
            'success' => true,
            'message' => 'Preview prepared.',
            'selected_warehouse' => [
                'id' => $selectedWarehouseId,
                'name' => $this->warehouseLocationLabel($selectedWarehouseId),
            ],
            'default_warehouse' => $defaultWarehouse,
            'scope' => [
                'sku_count' => count($scopeSkus),
                'product_count' => count($scopeProductIds),
                'sample' => array_slice(array_values($scopeRows), 0, 25),
            ],
            'delete_counts' => $deleteCounts,
            'phase_counts' => [
                'opening_candidates' => $openingCandidates,
                'purchase_headers' => count($purchaseBatches),
                'purchase_lines' => $purchaseLineCount,
                'return_headers' => count($returnBatches),
                'return_lines' => $returnLineCount,
                'transfer_in_lines' => count($transferIns),
                'transfer_out_lines' => count($transferOuts),
                'invoice_headers' => $invoiceHeaderCount,
                'invoice_lines' => $invoiceLineCount,
                'cancel_invoice_headers' => $cancelHeaderCount,
            ],
            'warnings' => [
                'local_stock_baseline' => 'This execution path preserves current vp_products.local_stock and uses it as the opening-stock baseline in the default warehouse.',
                'global_delete' => 'Step 4 fully deletes vp_stock_movements and vp_stock rows for the scoped SKUs before replay.',
                'other_warehouse_usage' => $otherWarehouseUsage,
            ],
            'blocking_warnings' => $blockingWarnings,
            'can_execute' => $blockingWarnings === [],
        ];
    }

    public function execute(int $selectedWarehouseId, int $userId): array
    {
        $selectedWarehouseId = (int) $selectedWarehouseId;
        $userId = (int) $userId;
        if ($selectedWarehouseId <= 0) {
            return ['success' => false, 'message' => 'Please select a warehouse.'];
        }

        $preview = $this->preview($selectedWarehouseId);
        if (empty($preview['success'])) {
            return $preview;
        }
        if (!empty($preview['blocking_warnings'])) {
            return [
                'success' => false,
                'message' => 'Execution blocked until preview warnings are resolved.',
                'preview' => $preview,
            ];
        }

        $defaultWarehouseId = (int) (($preview['default_warehouse']['id'] ?? 0));
        $defaultWarehouseName = (string) (($preview['default_warehouse']['name'] ?? ''));
        $scopeRows = $this->collectScopeProducts($selectedWarehouseId);
        $scopeSkus = array_keys($scopeRows);
        $scopeProductIds = $this->extractScopeProductIds($scopeRows);
        $scopeProductIdSet = array_fill_keys($scopeProductIds, true);

        $purchaseBatches = $this->collectPurchaseBatches($defaultWarehouseId, $scopeRows);
        $returnBatches = $this->collectReturnBatches($defaultWarehouseId, $scopeRows);
        $transferIns = $this->collectTransferInRows($selectedWarehouseId, $scopeRows);
        $transferOuts = $this->collectTransferOutRows($selectedWarehouseId, $scopeRows);
        $invoiceReplay = $this->collectInvoiceReplayData($selectedWarehouseId, $scopeProductIdSet);

        $logs = [];
        $stats = [
            'deleted_movement_rows' => 0,
            'deleted_vp_stock_rows' => 0,
            'zeroed_products' => 0,
            'opening_rows' => 0,
            'purchase_lines' => 0,
            'return_lines' => 0,
            'transfer_in_lines' => 0,
            'transfer_out_lines' => 0,
            'invoice_out_lines' => 0,
            'invoice_cancel_lines' => 0,
            'vp_stock_rows_rebuilt' => 0,
            'physical_products_synced' => 0,
        ];

        $this->ensureOpeningStockEnum();
        $this->ensurePhysicalStockColumn();

        $this->conn->begin_transaction();
        try {
            $stats['deleted_movement_rows'] = $this->deleteScopedMovements($scopeSkus, $scopeProductIds);
            $logs[] = 'Deleted ' . $stats['deleted_movement_rows'] . ' row(s) from vp_stock_movements for scoped SKUs.';

            $stats['deleted_vp_stock_rows'] = $this->deleteScopedVpStockRows($scopeSkus);
            $logs[] = 'Deleted ' . $stats['deleted_vp_stock_rows'] . ' row(s) from vp_stock for scoped SKUs.';

            $stats['zeroed_products'] = $this->zeroScopedPhysicalStock($scopeProductIds);
            $logs[] = 'Reset physical_stock to 0 for ' . $stats['zeroed_products'] . ' scoped product row(s).';

            foreach ($scopeRows as $row) {
                $qty = max(0, (int) ($row['local_stock'] ?? 0));
                if ($qty <= 0) {
                    continue;
                }
                StockMovement::insert($this->conn, [
                    'product_id' => (int) ($row['product_id'] ?? 0),
                    'sku' => (string) ($row['sku'] ?? ''),
                    'item_code' => (string) ($row['item_code'] ?? ''),
                    'size' => (string) ($row['size'] ?? ''),
                    'color' => (string) ($row['color'] ?? ''),
                    'warehouse_id' => $defaultWarehouseId,
                    'location' => $defaultWarehouseName,
                    'movement_type' => 'OPENING_STOCK',
                    'quantity' => $qty,
                    'ref_type' => 'STOCK_REBUILD',
                    'ref_id' => 'stock-rebuild:' . $selectedWarehouseId . ':' . (int) ($row['product_id'] ?? 0),
                    'reason' => 'Opening stock from local_stock (stock rebuild)',
                    'update_by_user' => $userId,
                    'strict_stock_check' => false,
                    'sync_physical_stock' => false,
                ]);
                $stats['opening_rows']++;
            }
            $logs[] = 'Seeded ' . $stats['opening_rows'] . ' OPENING_STOCK row(s) in the default warehouse.';

            foreach ($purchaseBatches as $batch) {
                $lines = (array) ($batch['lines'] ?? []);
                if ($lines === []) {
                    continue;
                }
                DirectPurchaseStock::applyPurchaseIn(
                    $this->conn,
                    (int) $batch['purchase_id'],
                    $defaultWarehouseId,
                    $lines,
                    (int) ($batch['created_by'] ?? 0)
                );
                $stats['purchase_lines'] += count($lines);
            }
            $logs[] = 'Replayed ' . $stats['purchase_lines'] . ' direct-purchase line(s) in the default warehouse.';

            foreach ($returnBatches as $batch) {
                $lines = (array) ($batch['lines'] ?? []);
                if ($lines === []) {
                    continue;
                }
                DirectPurchaseStock::applyReturnOut(
                    $this->conn,
                    (int) $batch['return_id'],
                    $defaultWarehouseId,
                    $lines,
                    (int) ($batch['created_by'] ?? 0)
                );
                $stats['return_lines'] += count($lines);
            }
            $logs[] = 'Replayed ' . $stats['return_lines'] . ' direct-purchase return line(s) in the default warehouse.';

            foreach ($transferIns as $row) {
                $this->insertReplayMovement($row);
                $stats['transfer_in_lines']++;
            }
            $logs[] = 'Replayed ' . $stats['transfer_in_lines'] . ' transfer-in / GRN line(s) for the selected warehouse.';

            foreach ($transferOuts as $row) {
                $this->insertReplayMovement($row);
                $stats['transfer_out_lines']++;
            }
            $logs[] = 'Replayed ' . $stats['transfer_out_lines'] . ' transfer-out line(s) for the selected warehouse.';

            foreach ((array) ($invoiceReplay['invoices'] ?? []) as $invoice) {
                $invoiceId = (int) ($invoice['invoice_id'] ?? 0);
                $status = strtolower(trim((string) ($invoice['status'] ?? '')));
                $lines = (array) ($invoice['lines'] ?? []);
                foreach ($lines as $line) {
                    $this->insertInvoiceOutMovement($selectedWarehouseId, $invoiceId, $line, $userId);
                    $stats['invoice_out_lines']++;
                    if ($status === 'cancelled') {
                        $this->insertInvoiceCancelMovement($selectedWarehouseId, $invoiceId, $line, $userId);
                        $stats['invoice_cancel_lines']++;
                    }
                }
            }
            $logs[] = 'Replayed ' . $stats['invoice_out_lines'] . ' invoice OUT line(s) for the selected warehouse.';
            $logs[] = 'Replayed ' . $stats['invoice_cancel_lines'] . ' invoice cancellation line(s) for the selected warehouse.';

            $stats['vp_stock_rows_rebuilt'] = $this->rebuildVpStockFromMovements($scopeSkus);
            $logs[] = 'Rebuilt ' . $stats['vp_stock_rows_rebuilt'] . ' vp_stock row(s) from the latest movement ledger.';

            $stats['physical_products_synced'] = $this->syncPhysicalStockForProducts($scopeProductIds);
            $logs[] = 'Synced physical_stock for ' . $stats['physical_products_synced'] . ' product(s).';

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Stock rebuild completed successfully.',
                'stats' => $stats,
                'logs' => $logs,
                'preview' => $preview,
            ];
        } catch (StockRebuildSqlException $e) {
            $this->conn->rollback();
            $logs[] = 'Execution failed: ' . $e->getMessage();

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_detail' => $e->getDetail(),
                'stats' => $stats,
                'logs' => $logs,
                'preview' => $preview,
            ];
        } catch (Throwable $e) {
            $this->conn->rollback();
            $logs[] = 'Execution failed: ' . $e->getMessage();

            return [
                'success' => false,
                'message' => 'Stock rebuild failed: ' . $e->getMessage(),
                'stats' => $stats,
                'logs' => $logs,
                'preview' => $preview,
            ];
        }
    }

    private function collectScopeProducts(int $selectedWarehouseId): array
    {
        $selectedWarehouseId = (int) $selectedWarehouseId;
        $scope = [];

        $scopeQueries = [
            'preview.collect_scope.vp_stock_sku_join' => [
                'sql' => "SELECT DISTINCT p.id AS product_id, p.sku, p.item_code, IFNULL(p.size, '') AS size, IFNULL(p.color, '') AS color,
                    p.title, IFNULL(p.local_stock, 0) AS local_stock
             FROM vp_stock s
             INNER JOIN vp_products p ON p.sku = s.sku
             WHERE s.warehouse_id = {$selectedWarehouseId}
               AND TRIM(COALESCE(s.sku, '')) <> ''",
                'tables' => ['vp_stock', 'vp_products'],
                'columns' => ['vp_stock.sku', 'vp_products.sku'],
                'comparison' => 'vp_products.sku = vp_stock.sku',
            ],
            'preview.collect_scope.vp_stock_movements' => [
                'sql' => "SELECT DISTINCT p.id AS product_id, COALESCE(NULLIF(TRIM(sm.sku), ''), p.sku) AS sku, p.item_code,
                    IFNULL(p.size, '') AS size, IFNULL(p.color, '') AS color, p.title, IFNULL(p.local_stock, 0) AS local_stock
             FROM vp_stock_movements sm
             INNER JOIN vp_products p ON p.id = sm.product_id
             WHERE sm.warehouse_id = {$selectedWarehouseId}
               AND TRIM(COALESCE(sm.sku, '')) <> ''",
                'tables' => ['vp_stock_movements', 'vp_products'],
                'columns' => ['vp_stock_movements.sku', 'vp_products.sku', 'vp_stock_movements.product_id', 'vp_products.id'],
                'comparison' => 'vp_products.id = vp_stock_movements.product_id',
            ],
            'preview.collect_scope.transfer_out_lines' => [
                'sql' => "SELECT DISTINCT p.id AS product_id, COALESCE(NULLIF(TRIM(ist.sku), ''), p.sku) AS sku, p.item_code,
                    IFNULL(p.size, '') AS size, IFNULL(p.color, '') AS color, p.title, IFNULL(p.local_stock, 0) AS local_stock
             FROM vp_stock_transfer st
             INNER JOIN vp_item_stock_transfer ist ON ist.transfer_order_no = st.transfer_order_no
             INNER JOIN vp_products p ON p.id = ist.product_id
             WHERE st.from_warehouse = {$selectedWarehouseId}
               AND TRIM(COALESCE(ist.sku, '')) <> ''",
                'tables' => ['vp_stock_transfer', 'vp_item_stock_transfer', 'vp_products'],
                'columns' => ['vp_item_stock_transfer.transfer_order_no', 'vp_stock_transfer.transfer_order_no'],
                'comparison' => 'vp_item_stock_transfer.transfer_order_no = vp_stock_transfer.transfer_order_no',
            ],
            'preview.collect_scope.transfer_grn_lines' => [
                'sql' => "SELECT DISTINCT p.id AS product_id, COALESCE(NULLIF(TRIM(grn.sku), ''), p.sku) AS sku, p.item_code,
                    IFNULL(p.size, '') AS size, IFNULL(p.color, '') AS color, p.title, IFNULL(p.local_stock, 0) AS local_stock
             FROM vp_stock_transfer st
             INNER JOIN vp_stock_transfer_grns grn ON grn.transfer_id = st.id
             INNER JOIN vp_products p
                ON (TRIM(COALESCE(grn.sku, '')) <> '' AND p.sku = grn.sku)
                OR (TRIM(COALESCE(grn.sku, '')) = '' AND TRIM(COALESCE(grn.item_code, '')) <> '' AND p.item_code = grn.item_code)
             WHERE COALESCE(NULLIF(grn.location, 0), st.to_warehouse) = {$selectedWarehouseId}
               AND (
                    TRIM(COALESCE(grn.sku, '')) <> ''
                    OR TRIM(COALESCE(grn.item_code, '')) <> ''
               )",
                'tables' => ['vp_stock_transfer', 'vp_stock_transfer_grns', 'vp_products'],
                'columns' => ['vp_products.sku', 'vp_stock_transfer_grns.sku', 'vp_products.item_code', 'vp_stock_transfer_grns.item_code'],
                'comparison' => 'vp_products.sku = vp_stock_transfer_grns.sku OR vp_products.item_code = vp_stock_transfer_grns.item_code',
            ],
        ];

        foreach ($scopeQueries as $step => $queryMeta) {
            $res = $this->queryOrFail((string) $queryMeta['sql'], $step, [
                'phase' => 'preview',
                'tables' => $queryMeta['tables'] ?? [],
                'columns' => $queryMeta['columns'] ?? [],
                'comparison' => $queryMeta['comparison'] ?? null,
            ]);
            while ($row = $res->fetch_assoc()) {
                $this->addScopeRow($scope, $row);
            }
            $res->free();
        }

        $invoiceSql = "SELECT i.id AS invoice_id, i.status, ii.product_id, ii.order_number, ii.item_code
            FROM vp_invoices i
            INNER JOIN vp_invoice_items ii ON ii.invoice_id = i.id
            WHERE i.warehouse_id = {$selectedWarehouseId}
              AND i.status IN ('final', 'cancelled')";
        $invoiceRes = $this->queryOrFail($invoiceSql, 'preview.collect_scope.invoices', [
            'phase' => 'preview',
            'tables' => ['vp_invoices', 'vp_invoice_items'],
            'columns' => ['vp_invoice_items.invoice_id', 'vp_invoices.id'],
            'comparison' => 'vp_invoice_items.invoice_id = vp_invoices.id',
        ]);
        if ($invoiceRes) {
            while ($row = $invoiceRes->fetch_assoc()) {
                $productId = (int) ($row['product_id'] ?? 0);
                if ($productId <= 0) {
                    $productId = $this->productModel->getProductIdForInvoiceLine(
                        (string) ($row['order_number'] ?? ''),
                        (string) ($row['item_code'] ?? '')
                    );
                }
                if ($productId <= 0) {
                    continue;
                }
                $product = $this->productModel->getProduct($productId);
                if (!$product) {
                    continue;
                }
                $this->addScopeRow($scope, [
                    'product_id' => (int) ($product['id'] ?? 0),
                    'sku' => (string) ($product['sku'] ?? ''),
                    'item_code' => (string) ($product['item_code'] ?? ''),
                    'size' => (string) ($product['size'] ?? ''),
                    'color' => (string) ($product['color'] ?? ''),
                    'title' => (string) ($product['title'] ?? ''),
                    'local_stock' => (int) ($product['local_stock'] ?? 0),
                ]);
            }
            $invoiceRes->free();
        }

        ksort($scope);

        return $scope;
    }

    private function addScopeRow(array &$scope, array $row): void
    {
        $sku = trim((string) ($row['sku'] ?? ''));
        $productId = (int) ($row['product_id'] ?? 0);
        if ($sku === '' || $productId <= 0) {
            return;
        }

        if (!isset($scope[$sku])) {
            $scope[$sku] = [
                'product_id' => $productId,
                'sku' => $sku,
                'item_code' => trim((string) ($row['item_code'] ?? '')),
                'size' => trim((string) ($row['size'] ?? '')),
                'color' => trim((string) ($row['color'] ?? '')),
                'title' => trim((string) ($row['title'] ?? '')),
                'local_stock' => (int) ($row['local_stock'] ?? 0),
            ];
            return;
        }

        if ((int) $scope[$sku]['product_id'] <= 0 && $productId > 0) {
            $scope[$sku]['product_id'] = $productId;
        }
        if ($scope[$sku]['item_code'] === '') {
            $scope[$sku]['item_code'] = trim((string) ($row['item_code'] ?? ''));
        }
        if ($scope[$sku]['size'] === '') {
            $scope[$sku]['size'] = trim((string) ($row['size'] ?? ''));
        }
        if ($scope[$sku]['color'] === '') {
            $scope[$sku]['color'] = trim((string) ($row['color'] ?? ''));
        }
        if ($scope[$sku]['title'] === '') {
            $scope[$sku]['title'] = trim((string) ($row['title'] ?? ''));
        }
        if ((int) $scope[$sku]['local_stock'] <= 0 && (int) ($row['local_stock'] ?? 0) > 0) {
            $scope[$sku]['local_stock'] = (int) ($row['local_stock'] ?? 0);
        }
    }

    private function extractScopeProductIds(array $scopeRows): array
    {
        $ids = [];
        foreach ($scopeRows as $row) {
            $pid = (int) ($row['product_id'] ?? 0);
            if ($pid > 0) {
                $ids[$pid] = true;
            }
        }

        return array_map('intval', array_keys($ids));
    }

    private function collectDeleteCounts(array $scopeSkus, array $scopeProductIds): array
    {
        return [
            'vp_stock_movements' => $this->countScopedRows('vp_stock_movements', $scopeSkus, $scopeProductIds, true, 'preview.count_delete_targets.vp_stock_movements'),
            'vp_stock' => $this->countScopedRows('vp_stock', $scopeSkus, [], false, 'preview.count_delete_targets.vp_stock'),
        ];
    }

    private function countScopedRows(string $table, array $scopeSkus, array $scopeProductIds, bool $hasProductId, string $step): int
    {
        $clauses = [];
        if ($scopeSkus !== []) {
            $clauses[] = "sku IN (" . $this->quoteStringsForIn($scopeSkus) . ")";
        }
        if ($hasProductId && $scopeProductIds !== []) {
            $clauses[] = "product_id IN (" . $this->quoteIntsForIn($scopeProductIds) . ")";
        }
        if ($clauses === []) {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS c FROM {$table} WHERE " . implode(' OR ', $clauses);
        $res = $this->queryOrFail($sql, $step, [
            'phase' => 'preview',
            'tables' => [$table],
            'columns' => $scopeSkus !== [] ? ["{$table}.sku"] : ["{$table}.product_id"],
            'comparison' => $scopeSkus !== [] ? "{$table}.sku IN (scoped SKU list)" : "{$table}.product_id IN (scoped product ids)",
        ]);
        $row = $res->fetch_assoc();
        $res->free();

        return (int) ($row['c'] ?? 0);
    }

    private function collectOtherWarehouseUsage(array $scopeSkus, array $scopeProductIds, int $defaultWarehouseId, int $selectedWarehouseId): array
    {
        if ($scopeSkus === [] && $scopeProductIds === []) {
            return [];
        }

        $allowedWarehouseIds = array_unique(array_filter([(int) $defaultWarehouseId, (int) $selectedWarehouseId]));
        $allowedList = $allowedWarehouseIds === [] ? '0' : implode(',', array_map('intval', $allowedWarehouseIds));
        $rows = [];

        if ($scopeSkus !== []) {
            $skuList = $this->quoteStringsForIn($scopeSkus);
            $otherWarehouseQueries = [
                'preview.other_warehouse_usage.vp_stock' => [
                    'sql' => "SELECT 'vp_stock' AS source_table, warehouse_id, COUNT(*) AS row_count
                 FROM vp_stock
                 WHERE sku IN ({$skuList}) AND warehouse_id NOT IN ({$allowedList})
                 GROUP BY warehouse_id",
                    'table' => 'vp_stock',
                ],
                'preview.other_warehouse_usage.vp_stock_movements' => [
                    'sql' => "SELECT 'vp_stock_movements' AS source_table, warehouse_id, COUNT(*) AS row_count
                 FROM vp_stock_movements
                 WHERE sku IN ({$skuList}) AND warehouse_id NOT IN ({$allowedList})
                 GROUP BY warehouse_id",
                    'table' => 'vp_stock_movements',
                ],
            ];
            foreach ($otherWarehouseQueries as $step => $queryMeta) {
                $res = $this->queryOrFail((string) $queryMeta['sql'], $step, [
                    'phase' => 'preview',
                    'tables' => [(string) $queryMeta['table']],
                    'columns' => [(string) $queryMeta['table'] . '.sku'],
                    'comparison' => (string) $queryMeta['table'] . '.sku IN (scoped SKU list)',
                ]);
                while ($row = $res->fetch_assoc()) {
                    $warehouseId = (int) ($row['warehouse_id'] ?? 0);
                    if ($warehouseId <= 0) {
                        continue;
                    }
                    $key = ($row['source_table'] ?? '') . ':' . $warehouseId;
                    $rows[$key] = [
                        'source_table' => (string) ($row['source_table'] ?? ''),
                        'warehouse_id' => $warehouseId,
                        'warehouse_name' => $this->warehouseLocationLabel($warehouseId),
                        'row_count' => (int) ($row['row_count'] ?? 0),
                    ];
                }
                $res->free();
            }
        }

        return array_values($rows);
    }

    private function collectPurchaseBatches(int $warehouseId, array $scopeRows): array
    {
        if ($warehouseId <= 0 || $scopeRows === []) {
            return [];
        }

        $scopeSkuSet = array_fill_keys(array_keys($scopeRows), true);
        $sql = "SELECT p.id AS purchase_id, p.created_by, i.sku, i.qty, i.item_code, i.size, i.color
            FROM vp_direct_purchases p
            INNER JOIN vp_direct_purchase_items i ON i.direct_purchase_id = p.id
            WHERE p.warehouse_id = " . (int) $warehouseId . "
            ORDER BY p.id ASC, i.id ASC";
        $res = $this->conn->query($sql);
        if (!$res) {
            return [];
        }

        $grouped = [];
        while ($row = $res->fetch_assoc()) {
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '' || !isset($scopeSkuSet[$sku])) {
                continue;
            }
            $purchaseId = (int) ($row['purchase_id'] ?? 0);
            if ($purchaseId <= 0) {
                continue;
            }
            if (!isset($grouped[$purchaseId])) {
                $grouped[$purchaseId] = [
                    'purchase_id' => $purchaseId,
                    'created_by' => (int) ($row['created_by'] ?? 0),
                    'lines' => [],
                ];
            }
            $scopeMeta = $scopeRows[$sku] ?? [];
            $grouped[$purchaseId]['lines'][] = [
                'sku' => $sku,
                'qty' => (float) ($row['qty'] ?? 0),
                'product_id' => (int) ($scopeMeta['product_id'] ?? 0),
                'item_code' => trim((string) ($row['item_code'] ?? ($scopeMeta['item_code'] ?? ''))),
                'size' => trim((string) ($row['size'] ?? ($scopeMeta['size'] ?? ''))),
                'color' => trim((string) ($row['color'] ?? ($scopeMeta['color'] ?? ''))),
            ];
        }
        $res->free();

        return array_values($grouped);
    }

    private function collectReturnBatches(int $warehouseId, array $scopeRows): array
    {
        if ($warehouseId <= 0 || $scopeRows === []) {
            return [];
        }

        $scopeSkuSet = array_fill_keys(array_keys($scopeRows), true);
        $sql = "SELECT r.id AS return_id, r.created_by, dpi.sku, dpi.item_code, dpi.size, dpi.color, ri.return_qty
            FROM vp_direct_purchase_returns r
            INNER JOIN vp_direct_purchase_return_items ri ON ri.direct_purchase_return_id = r.id
            INNER JOIN vp_direct_purchase_items dpi ON dpi.id = ri.direct_purchase_item_id
            WHERE r.warehouse_id = " . (int) $warehouseId . "
            ORDER BY r.id ASC, ri.id ASC";
        $res = $this->conn->query($sql);
        if (!$res) {
            return [];
        }

        $grouped = [];
        while ($row = $res->fetch_assoc()) {
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '' || !isset($scopeSkuSet[$sku])) {
                continue;
            }
            $returnId = (int) ($row['return_id'] ?? 0);
            if ($returnId <= 0) {
                continue;
            }
            if (!isset($grouped[$returnId])) {
                $grouped[$returnId] = [
                    'return_id' => $returnId,
                    'created_by' => (int) ($row['created_by'] ?? 0),
                    'lines' => [],
                ];
            }
            $scopeMeta = $scopeRows[$sku] ?? [];
            $grouped[$returnId]['lines'][] = [
                'sku' => $sku,
                'return_qty' => (float) ($row['return_qty'] ?? 0),
                'product_id' => (int) ($scopeMeta['product_id'] ?? 0),
                'item_code' => trim((string) ($row['item_code'] ?? ($scopeMeta['item_code'] ?? ''))),
                'size' => trim((string) ($row['size'] ?? ($scopeMeta['size'] ?? ''))),
                'color' => trim((string) ($row['color'] ?? ($scopeMeta['color'] ?? ''))),
            ];
        }
        $res->free();

        return array_values($grouped);
    }

    private function collectTransferInRows(int $selectedWarehouseId, array $scopeRows): array
    {
        if ($selectedWarehouseId <= 0 || $scopeRows === []) {
            return [];
        }

        $scopeSkuSet = array_fill_keys(array_keys($scopeRows), true);
        $sql = "SELECT st.transfer_order_no, grn.id AS grn_id, grn.sku, grn.item_code, grn.size, grn.color, grn.qty_received,
                    grn.location, grn.created_by
            FROM vp_stock_transfer st
            INNER JOIN vp_stock_transfer_grns grn ON grn.transfer_id = st.id
            WHERE COALESCE(NULLIF(grn.location, 0), st.to_warehouse) = " . (int) $selectedWarehouseId . "
            ORDER BY st.id ASC, grn.id ASC";
        $res = $this->conn->query($sql);
        if (!$res) {
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $qty = (float) ($row['qty_received'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '' || !isset($scopeSkuSet[$sku])) {
                continue;
            }
            $scopeMeta = $scopeRows[$sku] ?? [];
            $rows[] = [
                'product_id' => (int) ($scopeMeta['product_id'] ?? 0),
                'sku' => $sku,
                'item_code' => trim((string) ($row['item_code'] ?? ($scopeMeta['item_code'] ?? ''))),
                'size' => trim((string) ($row['size'] ?? ($scopeMeta['size'] ?? ''))),
                'color' => trim((string) ($row['color'] ?? ($scopeMeta['color'] ?? ''))),
                'warehouse_id' => $selectedWarehouseId,
                'location' => $this->warehouseLocationLabel($selectedWarehouseId),
                'movement_type' => 'TRANSFER_IN',
                'quantity' => $qty,
                'ref_type' => 'GRN',
                'ref_id' => (string) ((int) ($row['grn_id'] ?? 0)),
                'reason' => 'Replay transfer in (stock rebuild) ' . trim((string) ($row['transfer_order_no'] ?? '')),
                'update_by_user' => (int) ($row['created_by'] ?? 0),
                'strict_stock_check' => false,
                'sync_physical_stock' => false,
            ];
        }
        $res->free();

        return $rows;
    }

    private function collectTransferOutRows(int $selectedWarehouseId, array $scopeRows): array
    {
        if ($selectedWarehouseId <= 0 || $scopeRows === []) {
            return [];
        }

        $scopeSkuSet = array_fill_keys(array_keys($scopeRows), true);
        $sql = "SELECT st.transfer_order_no, st.created_by, ist.product_id, ist.item_code, ist.sku, ist.transfer_qty
            FROM vp_stock_transfer st
            INNER JOIN vp_item_stock_transfer ist ON ist.transfer_order_no = st.transfer_order_no
            WHERE st.from_warehouse = " . (int) $selectedWarehouseId . "
            ORDER BY st.id ASC, ist.id ASC";
        $res = $this->conn->query($sql);
        if (!$res) {
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $qty = (float) ($row['transfer_qty'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '' || !isset($scopeSkuSet[$sku])) {
                continue;
            }
            $scopeMeta = $scopeRows[$sku] ?? [];
            $rows[] = [
                'product_id' => (int) ($row['product_id'] ?? ($scopeMeta['product_id'] ?? 0)),
                'sku' => $sku,
                'item_code' => trim((string) ($row['item_code'] ?? ($scopeMeta['item_code'] ?? ''))),
                'size' => trim((string) ($scopeMeta['size'] ?? '')),
                'color' => trim((string) ($scopeMeta['color'] ?? '')),
                'warehouse_id' => $selectedWarehouseId,
                'location' => $this->warehouseLocationLabel($selectedWarehouseId),
                'movement_type' => 'TRANSFER_OUT',
                'quantity' => $qty,
                'ref_type' => 'TRANSFER_ORDER',
                'ref_id' => trim((string) ($row['transfer_order_no'] ?? '')),
                'reason' => 'Replay transfer out (stock rebuild)',
                'update_by_user' => (int) ($row['created_by'] ?? 0),
                'strict_stock_check' => false,
                'sync_physical_stock' => false,
            ];
        }
        $res->free();

        return $rows;
    }

    private function collectInvoiceReplayData(int $selectedWarehouseId, array $scopeProductIdSet): array
    {
        if ($selectedWarehouseId <= 0 || $scopeProductIdSet === []) {
            return ['invoices' => []];
        }

        $headers = [];
        $sql = "SELECT id, status, warehouse_id
            FROM vp_invoices
            WHERE warehouse_id = " . (int) $selectedWarehouseId . "
              AND status IN ('final', 'cancelled')
            ORDER BY id ASC";
        $res = $this->conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $invoiceId = (int) ($row['id'] ?? 0);
                if ($invoiceId <= 0) {
                    continue;
                }
                $lines = $this->fetchResolvedInvoiceLines($invoiceId, $scopeProductIdSet);
                if ($lines === []) {
                    continue;
                }
                $headers[] = [
                    'invoice_id' => $invoiceId,
                    'status' => (string) ($row['status'] ?? ''),
                    'warehouse_id' => (int) ($row['warehouse_id'] ?? 0),
                    'lines' => $lines,
                ];
            }
            $res->free();
        }

        return ['invoices' => $headers];
    }

    private function fetchResolvedInvoiceLines(int $invoiceId, array $scopeProductIdSet): array
    {
        $invoiceId = (int) $invoiceId;
        if ($invoiceId <= 0) {
            return [];
        }

        $rows = [];
        $sql = 'SELECT id, product_id, quantity, order_number, item_code FROM vp_invoice_items WHERE invoice_id = ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $invoiceId);
        $stmt->execute();
        $res = $stmt->get_result();
        $invoiceItems = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        $orderByInvoiceSql = 'SELECT size, color FROM vp_orders WHERE invoice_id = ? AND order_number = ? AND item_code = ? ORDER BY id ASC LIMIT 1';
        $orderByNumberSql = 'SELECT size, color FROM vp_orders WHERE order_number = ? AND item_code = ? ORDER BY id ASC LIMIT 1';
        $orderByInvoiceStmt = $this->prepareOrFail($orderByInvoiceSql, 'preview.invoice_replay.vp_orders_by_invoice', [
            'phase' => 'preview',
            'tables' => ['vp_orders', 'vp_invoice_items'],
            'columns' => ['vp_orders.order_number', 'vp_orders.item_code', 'vp_invoice_items.order_number', 'vp_invoice_items.item_code'],
            'comparison' => 'vp_orders.order_number / item_code matched to invoice line values',
        ]);
        $orderByNumberStmt = $this->prepareOrFail($orderByNumberSql, 'preview.invoice_replay.vp_orders_by_number', [
            'phase' => 'preview',
            'tables' => ['vp_orders', 'vp_invoice_items'],
            'columns' => ['vp_orders.order_number', 'vp_orders.item_code', 'vp_invoice_items.order_number', 'vp_invoice_items.item_code'],
            'comparison' => 'vp_orders.order_number / item_code matched to invoice line values',
        ]);

        foreach ($invoiceItems as $item) {
            $qty = (int) round((float) ($item['quantity'] ?? 0));
            if ($qty <= 0) {
                continue;
            }
            $orderNumber = trim((string) ($item['order_number'] ?? ''));
            $itemCode = trim((string) ($item['item_code'] ?? ''));
            $size = '';
            $color = '';

            if ($orderByInvoiceStmt && $orderNumber !== '' && $itemCode !== '') {
                $orderByInvoiceStmt->bind_param('iss', $invoiceId, $orderNumber, $itemCode);
                $orderByInvoiceStmt->execute();
                $orderRow = $orderByInvoiceStmt->get_result()->fetch_assoc();
                if ($orderRow) {
                    $size = trim((string) ($orderRow['size'] ?? ''));
                    $color = trim((string) ($orderRow['color'] ?? ''));
                }
            }
            if ($size === '' && $color === '' && $orderByNumberStmt && $orderNumber !== '' && $itemCode !== '') {
                $orderByNumberStmt->bind_param('ss', $orderNumber, $itemCode);
                $orderByNumberStmt->execute();
                $orderRow = $orderByNumberStmt->get_result()->fetch_assoc();
                if ($orderRow) {
                    $size = trim((string) ($orderRow['size'] ?? ''));
                    $color = trim((string) ($orderRow['color'] ?? ''));
                }
            }

            $productId = (int) ($item['product_id'] ?? 0);
            if ($productId <= 0 && $itemCode !== '') {
                $productId = $this->productModel->getProductIdForInvoiceLine($orderNumber, $itemCode, $size, $color);
            }
            if ($productId <= 0 || !isset($scopeProductIdSet[$productId])) {
                continue;
            }

            $product = $this->productModel->getProduct($productId);
            if (!$product) {
                continue;
            }

            $rows[] = [
                'product_id' => $productId,
                'sku' => (string) ($product['sku'] ?? ''),
                'item_code' => (string) ($product['item_code'] ?? ''),
                'size' => (string) ($product['size'] ?? $size),
                'color' => (string) ($product['color'] ?? $color),
                'quantity' => $qty,
            ];
        }

        if ($orderByInvoiceStmt) {
            $orderByInvoiceStmt->close();
        }
        if ($orderByNumberStmt) {
            $orderByNumberStmt->close();
        }

        return $rows;
    }

    private function insertReplayMovement(array $row): void
    {
        StockMovement::insert($this->conn, array_merge($row, [
            'strict_stock_check' => $row['strict_stock_check'] ?? false,
            'sync_physical_stock' => $row['sync_physical_stock'] ?? false,
        ]));
    }

    private function insertInvoiceOutMovement(int $warehouseId, int $invoiceId, array $line, int $userId): void
    {
        StockMovement::insert($this->conn, [
            'product_id' => (int) ($line['product_id'] ?? 0),
            'sku' => (string) ($line['sku'] ?? ''),
            'item_code' => (string) ($line['item_code'] ?? ''),
            'size' => (string) ($line['size'] ?? ''),
            'color' => (string) ($line['color'] ?? ''),
            'warehouse_id' => $warehouseId,
            'location' => $this->warehouseLocationLabel($warehouseId),
            'movement_type' => 'OUT',
            'quantity' => (int) ($line['quantity'] ?? 0),
            'ref_type' => 'INVOICE',
            'ref_id' => (string) $invoiceId,
            'reason' => 'Invoice #' . $invoiceId,
            'update_by_user' => $userId,
            'strict_stock_check' => true,
            'sync_physical_stock' => false,
        ]);
    }

    private function insertInvoiceCancelMovement(int $warehouseId, int $invoiceId, array $line, int $userId): void
    {
        StockMovement::insert($this->conn, [
            'product_id' => (int) ($line['product_id'] ?? 0),
            'sku' => (string) ($line['sku'] ?? ''),
            'item_code' => (string) ($line['item_code'] ?? ''),
            'size' => (string) ($line['size'] ?? ''),
            'color' => (string) ($line['color'] ?? ''),
            'warehouse_id' => $warehouseId,
            'location' => $this->warehouseLocationLabel($warehouseId),
            'movement_type' => 'IN',
            'quantity' => (int) ($line['quantity'] ?? 0),
            'ref_type' => 'INVOICE_CANCEL',
            'ref_id' => (string) $invoiceId,
            'reason' => 'Invoice cancelled / dispatch cancelled #' . $invoiceId,
            'update_by_user' => $userId,
            'strict_stock_check' => false,
            'sync_physical_stock' => false,
        ]);
    }

    private function deleteScopedMovements(array $scopeSkus, array $scopeProductIds): int
    {
        $clauses = [];
        if ($scopeSkus !== []) {
            $clauses[] = "sku IN (" . $this->quoteStringsForIn($scopeSkus) . ")";
        }
        if ($scopeProductIds !== []) {
            $clauses[] = "product_id IN (" . $this->quoteIntsForIn($scopeProductIds) . ")";
        }
        if ($clauses === []) {
            return 0;
        }

        $sql = "DELETE FROM vp_stock_movements WHERE " . implode(' OR ', $clauses);
        $this->queryOrFail($sql, 'execute.delete_scoped_movements', [
            'phase' => 'execute',
            'tables' => ['vp_stock_movements'],
            'columns' => ['vp_stock_movements.sku', 'vp_stock_movements.product_id'],
            'comparison' => 'vp_stock_movements.sku IN (scoped SKU list) OR product_id IN (scoped product ids)',
        ]);

        return (int) $this->conn->affected_rows;
    }

    private function deleteScopedVpStockRows(array $scopeSkus): int
    {
        if ($scopeSkus === []) {
            return 0;
        }

        $sql = "DELETE FROM vp_stock WHERE sku IN (" . $this->quoteStringsForIn($scopeSkus) . ")";
        $this->queryOrFail($sql, 'execute.delete_scoped_vp_stock', [
            'phase' => 'execute',
            'tables' => ['vp_stock'],
            'columns' => ['vp_stock.sku'],
            'comparison' => 'vp_stock.sku IN (scoped SKU list)',
        ]);

        return (int) $this->conn->affected_rows;
    }

    private function zeroScopedPhysicalStock(array $scopeProductIds): int
    {
        if ($scopeProductIds === []) {
            return 0;
        }

        $sql = "UPDATE vp_products SET physical_stock = 0 WHERE id IN (" . $this->quoteIntsForIn($scopeProductIds) . ")";
        $this->conn->query($sql);

        return (int) $this->conn->affected_rows;
    }

    private function rebuildVpStockFromMovements(array $scopeSkus): int
    {
        if ($scopeSkus === []) {
            return 0;
        }

        $skuList = $this->quoteStringsForIn($scopeSkus);
        $sql = "
            SELECT sm.sku, sm.warehouse_id, sm.running_stock, sm.id AS movement_id
            FROM vp_stock_movements sm
            INNER JOIN (
                SELECT sku, warehouse_id, MAX(id) AS max_id
                FROM vp_stock_movements
                WHERE warehouse_id > 0 AND TRIM(COALESCE(sku, '')) <> ''
                  AND sku IN ({$skuList})
                GROUP BY sku, warehouse_id
            ) latest ON latest.max_id = sm.id";
        $res = $this->queryOrFail($sql, 'execute.rebuild_vp_stock_from_movements', [
            'phase' => 'execute',
            'tables' => ['vp_stock_movements'],
            'columns' => ['vp_stock_movements.sku'],
            'comparison' => 'vp_stock_movements.sku IN (scoped SKU list)',
        ]);

        $select = $this->conn->prepare('SELECT id FROM vp_stock WHERE sku = ? AND warehouse_id = ? LIMIT 1');
        $update = $this->conn->prepare('UPDATE vp_stock SET current_stock = ?, last_trans_id = ? WHERE id = ?');
        $insert = $this->conn->prepare('INSERT INTO vp_stock (sku, warehouse_id, current_stock, last_trans_id) VALUES (?, ?, ?, ?)');
        if (!$select || !$update || !$insert) {
            if ($select) {
                $select->close();
            }
            if ($update) {
                $update->close();
            }
            if ($insert) {
                $insert->close();
            }
            $res->free();
            throw new RuntimeException('Could not prepare vp_stock rebuild statements.');
        }

        $count = 0;
        while ($row = $res->fetch_assoc()) {
            $sku = (string) ($row['sku'] ?? '');
            $warehouseId = (int) ($row['warehouse_id'] ?? 0);
            $running = (float) ($row['running_stock'] ?? 0);
            $movementId = (int) ($row['movement_id'] ?? 0);
            if ($sku === '' || $warehouseId <= 0) {
                continue;
            }

            $select->bind_param('si', $sku, $warehouseId);
            $select->execute();
            $existing = $select->get_result()->fetch_assoc();
            if ($existing) {
                $stockId = (int) $existing['id'];
                $update->bind_param('dii', $running, $movementId, $stockId);
                $update->execute();
            } else {
                $insert->bind_param('sidi', $sku, $warehouseId, $running, $movementId);
                $insert->execute();
            }
            $count++;
        }
        $res->free();
        $select->close();
        $update->close();
        $insert->close();

        return $count;
    }

    private function syncPhysicalStockForProducts(array $scopeProductIds): int
    {
        $count = 0;
        foreach ($scopeProductIds as $productId) {
            $productId = (int) $productId;
            if ($productId <= 0) {
                continue;
            }
            StockMovement::syncProductPhysicalStock($this->conn, $productId);
            $count++;
        }

        return $count;
    }

    private function resolveDefaultWarehouseId(): int
    {
        $res = $this->conn->query('SELECT id FROM exotic_address WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
        if ($res && ($row = $res->fetch_assoc())) {
            $res->free();

            return (int) ($row['id'] ?? 0);
        }
        if ($res) {
            $res->free();
        }

        return 0;
    }

    private function warehouseLocationLabel(int $warehouseId): string
    {
        if ($warehouseId <= 0) {
            return '-';
        }
        $stmt = $this->conn->prepare('SELECT address_title FROM exotic_address WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return '-';
        }
        $stmt->bind_param('i', $warehouseId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $label = trim((string) ($row['address_title'] ?? ''));

        return $label !== '' ? $label : ('Warehouse #' . $warehouseId);
    }

    private function ensureOpeningStockEnum(): void
    {
        $res = @$this->conn->query("SHOW COLUMNS FROM vp_stock_movements LIKE 'movement_type'");
        if (!$res) {
            return;
        }
        $row = $res->fetch_assoc();
        $res->free();
        if (!$row) {
            return;
        }
        $type = strtolower((string) ($row['Type'] ?? ''));
        if (strpos($type, 'opening_stock') !== false) {
            return;
        }
        @$this->conn->query(
            "ALTER TABLE vp_stock_movements MODIFY COLUMN movement_type ENUM('IN','OUT','TRANSFER_IN','TRANSFER_OUT','OPENING_STOCK') NOT NULL"
        );
    }

    private function ensurePhysicalStockColumn(): void
    {
        $res = @$this->conn->query("SHOW COLUMNS FROM vp_products LIKE 'physical_stock'");
        if ($res && $res->num_rows > 0) {
            $res->free();
            return;
        }
        if ($res) {
            $res->free();
        }
        @$this->conn->query('ALTER TABLE vp_products ADD COLUMN physical_stock INT NOT NULL DEFAULT 0 AFTER local_stock');
    }

    /** @return mysqli_result */
    private function queryOrFail(string $sql, string $step, array $context = []): mysqli_result
    {
        $res = $this->conn->query($sql);
        if ($res instanceof mysqli_result) {
            return $res;
        }

        throw $this->sqlException($step, $sql, $context);
    }

    /** @param array<string, mixed> $context */
    private function prepareOrFail(string $sql, string $step, array $context = []): mysqli_stmt
    {
        $stmt = $this->conn->prepare($sql);
        if ($stmt instanceof mysqli_stmt) {
            return $stmt;
        }

        throw $this->sqlException($step, $sql, $context);
    }

    /** @param array<string, mixed> $context */
    private function sqlException(string $step, string $sql, array $context = []): StockRebuildSqlException
    {
        $mysqlError = (string) ($this->conn->error ?? '');
        $mysqlErrno = (int) ($this->conn->errno ?? 0);
        $database = $this->currentDatabase();
        $connectionCollation = $this->connectionCollation();
        $isCollationError = stripos($mysqlError, 'collation') !== false || stripos($mysqlError, 'charset') !== false;

        $detail = array_merge([
            'step' => $step,
            'phase' => $context['phase'] ?? null,
            'mysql_error' => $mysqlError,
            'mysql_errno' => $mysqlErrno,
            'database' => $database,
            'connection_collation' => $connectionCollation,
            'tables' => array_values((array) ($context['tables'] ?? [])),
            'columns' => array_values((array) ($context['columns'] ?? [])),
            'comparison' => $context['comparison'] ?? null,
            'sql_preview' => $this->compactSql($sql),
            'diagnostic_sql' => $isCollationError ? $this->collationDiagnosticSql((array) ($context['tables'] ?? []), (array) ($context['columns'] ?? [])) : null,
        ], $context);

        $message = 'Stock rebuild failed at step "' . $step . '"';
        if ($mysqlError !== '') {
            $message .= '. MySQL [' . $mysqlErrno . ']: ' . $mysqlError;
        }
        if (!empty($detail['comparison'])) {
            $message .= '. Comparison: ' . $detail['comparison'];
        }
        if (!empty($detail['tables'])) {
            $message .= '. Tables: ' . implode(', ', $detail['tables']);
        }
        if (!empty($detail['columns'])) {
            $message .= '. Columns: ' . implode(', ', $detail['columns']);
        }
        if ($database !== '') {
            $message .= '. Database: ' . $database;
        }
        if ($connectionCollation !== '') {
            $message .= '. Connection collation: ' . $connectionCollation;
        }
        if ($isCollationError) {
            $message .= '. Hint: compare CHARACTER SET / COLLATION on the listed columns using SHOW FULL COLUMNS or information_schema.COLUMNS.';
        }

        return new StockRebuildSqlException($message, $detail);
    }

    private function currentDatabase(): string
    {
        $res = $this->conn->query('SELECT DATABASE() AS db');
        if (!$res) {
            return '';
        }
        $row = $res->fetch_assoc();
        $res->free();

        return trim((string) ($row['db'] ?? ''));
    }

    private function connectionCollation(): string
    {
        $res = $this->conn->query('SELECT @@collation_connection AS collation_connection');
        if (!$res) {
            return '';
        }
        $row = $res->fetch_assoc();
        $res->free();

        return trim((string) ($row['collation_connection'] ?? ''));
    }

    private function compactSql(string $sql): string
    {
        $compact = preg_replace('/\s+/', ' ', trim($sql)) ?? trim($sql);
        if (strlen($compact) <= 1200) {
            return $compact;
        }

        return substr($compact, 0, 1200) . '...';
    }

    /** @param list<string> $tables @param list<string> $columns */
    private function collationDiagnosticSql(array $tables, array $columns): ?string
    {
        $tableNames = [];
        foreach ($tables as $table) {
            $table = trim((string) $table);
            if ($table !== '') {
                $tableNames[$table] = true;
            }
        }

        $columnNames = [];
        foreach ($columns as $column) {
            $column = trim((string) $column);
            if ($column === '') {
                continue;
            }
            if (strpos($column, '.') !== false) {
                $parts = explode('.', $column, 2);
                $tableNames[trim($parts[0])] = true;
                $columnNames[trim($parts[1])] = true;
            } else {
                $columnNames[$column] = true;
            }
        }

        if ($tableNames === [] && $columnNames === []) {
            return null;
        }

        $tableList = implode(', ', array_map(static function (string $table): string {
            return "'" . str_replace("'", "''", $table) . "'";
        }, array_keys($tableNames)));

        $sql = "SELECT TABLE_NAME, COLUMN_NAME, CHARACTER_SET_NAME, COLLATION_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()";

        if ($tableList !== '') {
            $sql .= ' AND TABLE_NAME IN (' . $tableList . ')';
        }
        if ($columnNames !== []) {
            $columnList = implode(', ', array_map(static function (string $column): string {
                return "'" . str_replace("'", "''", $column) . "'";
            }, array_keys($columnNames)));
            $sql .= ' AND COLUMN_NAME IN (' . $columnList . ')';
        }

        return $sql . ' ORDER BY COLUMN_NAME, TABLE_NAME;';
    }

    private function quoteStringsForIn(array $values): string
    {
        $quoted = [];
        foreach ($values as $value) {
            $quoted[] = "'" . $this->conn->real_escape_string((string) $value) . "'";
        }

        return implode(',', $quoted);
    }

    private function quoteIntsForIn(array $values): string
    {
        $ints = [];
        foreach ($values as $value) {
            $ints[] = (string) ((int) $value);
        }

        return implode(',', $ints);
    }
}
