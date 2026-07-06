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
require_once __DIR__ . '/../pos/pos.php';

final class StockRebuildService
{
    private const SCOPE_TABLE = '_stock_rebuild_scope';

    /** @var mysqli */
    private $conn;

    /** @var product */
    private $productModel;

    /** @var pos|null */
    private $posModel = null;

    /** @var string */
    private $debugSqlStep = '';

    /** @var string */
    private $debugSql = '';

    /** @var array<string, mixed> */
    private $debugSqlContext = [];

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

    public function preview(array $request): array
    {
        try {
            $filters = $this->parseStockReportFilters($request);

            return $this->buildPreview($filters);
        } catch (StockRebuildSqlException $e) {
            return $this->formatSqlErrorResponse($e);
        } catch (Throwable $e) {
            return $this->formatSqlErrorResponse(
                $this->sqlException(
                    $this->debugSqlStep !== '' ? $this->debugSqlStep : 'preview.unknown',
                    $this->debugSql !== '' ? $this->debugSql : ('[no SQL captured] ' . get_class($e)),
                    $this->debugSqlContext,
                    $e
                )
            );
        }
    }

    private function posModel(): pos
    {
        if ($this->posModel === null) {
            $this->posModel = new pos($this->conn);
        }

        return $this->posModel;
    }

    /** @param array<string, mixed> $request */
    public function parseStockReportFilters(array $request): array
    {
        $limit = (int) ($request['limit'] ?? 200);
        if ($limit < 50) {
            $limit = 50;
        }
        if ($limit > 500) {
            $limit = 500;
        }

        return [
            'warehouse_id' => (int) ($request['warehouse_id'] ?? 0),
            'search' => trim((string) ($request['search'] ?? '')),
            'category' => trim((string) ($request['category'] ?? 'allProducts')),
            'stock_status' => trim((string) ($request['stock_status'] ?? 'all')),
            'limit' => $limit,
            'page_no' => max(1, (int) ($request['page_no'] ?? 1)),
        ];
    }

    /** @param array<string, mixed> $filters */
    private function stockReportPaginationMeta(array $filters): array
    {
        $limit = max(1, (int) ($filters['limit'] ?? 200));
        $pageNo = max(1, (int) ($filters['page_no'] ?? 1));
        $totalRows = $this->posModel()->getStockReportCount($filters);
        $totalPages = $limit > 0 ? (int) ceil($totalRows / $limit) : 1;
        if ($totalPages < 1) {
            $totalPages = 1;
        }
        if ($pageNo > $totalPages) {
            $pageNo = $totalPages;
        }

        return [
            'page_no' => $pageNo,
            'limit' => $limit,
            'total_rows' => $totalRows,
            'total_pages' => $totalPages,
            'row_from' => $totalRows > 0 ? (($pageNo - 1) * $limit + 1) : 0,
            'row_to' => $totalRows > 0 ? min($pageNo * $limit, $totalRows) : 0,
            'baseline' => 'pos_stock_report',
            'baseline_url' => '?page=pos_register&action=stock-report',
        ];
    }

    /** @param array<string, mixed> $filters @return array<string, array<string, mixed>> */
    private function collectScopeFromStockReportBaseline(array $filters): array
    {
        $pageFilters = $filters;
        $pagination = $this->stockReportPaginationMeta($filters);
        $pageFilters['page_no'] = $pagination['page_no'];

        $rows = $this->posModel()->getStockReport($pageFilters);
        if ($rows === []) {
            return [];
        }

        $productIds = [];
        foreach ($rows as $row) {
            $productId = (int) ($row['id'] ?? 0);
            if ($productId > 0) {
                $productIds[$productId] = true;
            }
        }
        $localStockById = $this->fetchLocalStockForProductIds(array_map('intval', array_keys($productIds)));

        $scope = [];
        foreach ($rows as $row) {
            $productId = (int) ($row['id'] ?? 0);
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($productId <= 0 || $sku === '') {
                continue;
            }
            $this->addScopeRow($scope, [
                'product_id' => $productId,
                'sku' => $sku,
                'item_code' => trim((string) ($row['item_code'] ?? '')),
                'size' => trim((string) ($row['size'] ?? '')),
                'color' => trim((string) ($row['color'] ?? '')),
                'title' => trim((string) ($row['title'] ?? '')),
                'local_stock' => (int) ($localStockById[$productId] ?? 0),
                'warehouse_stock' => (int) ($row['stock_qty'] ?? 0),
            ]);
        }

        return $scope;
    }

    /** @param list<int> $productIds @return array<int, int> */
    private function fetchLocalStockForProductIds(array $productIds): array
    {
        $productIds = array_values(array_filter(array_map('intval', $productIds), static function (int $id): bool {
            return $id > 0;
        }));
        if ($productIds === []) {
            return [];
        }

        $sql = 'SELECT id, IFNULL(local_stock, 0) AS local_stock FROM vp_products WHERE id IN ('
            . $this->quoteIntsForIn($productIds) . ')';
        $res = $this->conn->query($sql);
        if (!$res) {
            return [];
        }

        $map = [];
        while ($row = $res->fetch_assoc()) {
            $map[(int) ($row['id'] ?? 0)] = (int) ($row['local_stock'] ?? 0);
        }
        $res->free();

        return $map;
    }

    private function buildPreview(array $filters): array
    {
        $selectedWarehouseId = (int) ($filters['warehouse_id'] ?? 0);
        if ($selectedWarehouseId <= 0) {
            return ['success' => false, 'message' => 'Please select a warehouse.'];
        }

        $defaultWarehouse = $this->getDefaultWarehouse();
        if ((int) ($defaultWarehouse['id'] ?? 0) <= 0) {
            return ['success' => false, 'message' => 'Default warehouse could not be resolved.'];
        }

        $pagination = $this->stockReportPaginationMeta($filters);
        $filters['page_no'] = $pagination['page_no'];

        $scopeRows = $this->collectScopeFromStockReportBaseline($filters);
        if ($scopeRows === []) {
            return [
                'success' => false,
                'message' => 'No products matched the POS stock report baseline for this page.',
                'selected_warehouse' => [
                    'id' => $selectedWarehouseId,
                    'name' => $this->warehouseLocationLabel($selectedWarehouseId),
                ],
                'default_warehouse' => $defaultWarehouse,
                'pagination' => $pagination,
                'filters' => $filters,
            ];
        }

        $scopeSkus = array_keys($scopeRows);
        $scopeProductIds = $this->extractScopeProductIds($scopeRows);

        $this->createScopeTempTable($scopeRows);
        try {
            $deleteCounts = $this->collectDeleteCountsViaScopeTable();
            $otherWarehouseUsage = $this->collectOtherWarehouseUsageViaScopeTable(
                (int) $defaultWarehouse['id'],
                $selectedWarehouseId
            );
            $phaseCounts = $this->countPreviewPhaseStatsViaScopeTable(
                (int) $defaultWarehouse['id'],
                $selectedWarehouseId
            );
        } finally {
            $this->dropScopeTempTable();
        }

        $openingCandidates = 0;
        foreach ($scopeRows as $row) {
            if ((int) ($row['local_stock'] ?? 0) > 0) {
                $openingCandidates++;
            }
        }
        $phaseCounts['opening_candidates'] = $openingCandidates;

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
                'page_products' => array_values($scopeRows),
            ],
            'pagination' => $pagination,
            'filters' => $filters,
            'delete_counts' => $deleteCounts,
            'phase_counts' => $phaseCounts,
            'warnings' => [
                'local_stock_baseline' => 'This execution path preserves current vp_products.local_stock and uses it as the opening-stock baseline in the default warehouse.',
                'global_delete' => 'Step 4 fully deletes vp_stock_movements and vp_stock rows for the scoped SKUs before replay.',
                'preview_mode' => 'Preview and execute run per page using the same product list as POS Register → Stock report (latest movement balance per product in the selected warehouse). Rebuild each page separately.',
                'other_warehouse_usage' => $otherWarehouseUsage,
            ],
            'blocking_warnings' => $blockingWarnings,
            'can_execute' => $blockingWarnings === [],
        ];
    }

    public function execute(array $request, int $userId): array
    {
        $filters = $this->parseStockReportFilters($request);
        $selectedWarehouseId = (int) ($filters['warehouse_id'] ?? 0);
        $userId = (int) $userId;
        if ($selectedWarehouseId <= 0) {
            return ['success' => false, 'message' => 'Please select a warehouse.'];
        }

        $preview = $this->preview($request);
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
        $scopeRows = $this->collectScopeFromStockReportBaseline($filters);
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
                'failed_sql' => $e->getDetail()['failed_sql'] ?? null,
                'failed_condition' => $e->getDetail()['failed_condition'] ?? ($e->getDetail()['comparison'] ?? null),
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

    private function collectScopeProducts(int $selectedWarehouseId, bool $resolveMissingInvoiceProducts = true): array
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
                'sql' => "SELECT p.id AS product_id, p.sku, p.item_code, IFNULL(p.size, '') AS size, IFNULL(p.color, '') AS color,
                    p.title, IFNULL(p.local_stock, 0) AS local_stock
             FROM (
                SELECT DISTINCT sm.product_id
                FROM vp_stock_movements sm
                WHERE sm.warehouse_id = {$selectedWarehouseId}
                  AND sm.product_id > 0
             ) sm_ids
             INNER JOIN vp_products p ON p.id = sm_ids.product_id",
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

        if ($resolveMissingInvoiceProducts) {
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
            while ($row = $invoiceRes->fetch_assoc()) {
                $productId = (int) ($row['product_id'] ?? 0);
                if ($productId <= 0) {
                    $productId = $this->resolveProductIdForInvoiceLine(
                        'preview.collect_scope.invoice_product_lookup',
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
        } else {
            $invoiceScopeSql = "SELECT DISTINCT p.id AS product_id, p.sku, p.item_code, IFNULL(p.size, '') AS size, IFNULL(p.color, '') AS color,
                    p.title, IFNULL(p.local_stock, 0) AS local_stock
                FROM vp_invoices i
                INNER JOIN vp_invoice_items ii ON ii.invoice_id = i.id
                INNER JOIN vp_products p ON p.id = ii.product_id
                WHERE i.warehouse_id = {$selectedWarehouseId}
                  AND i.status IN ('final', 'cancelled')
                  AND ii.product_id > 0";
            $invoiceScopeRes = $this->queryOrFail($invoiceScopeSql, 'preview.collect_scope.invoices_fast', [
                'phase' => 'preview',
                'tables' => ['vp_invoices', 'vp_invoice_items', 'vp_products'],
                'columns' => ['vp_invoice_items.product_id', 'vp_products.id'],
                'comparison' => 'vp_products.id = vp_invoice_items.product_id',
            ]);
            while ($row = $invoiceScopeRes->fetch_assoc()) {
                $this->addScopeRow($scope, $row);
            }
            $invoiceScopeRes->free();
        }

        ksort($scope);

        return $scope;
    }

    /** @param array<string, array<string, mixed>> $scopeRows */
    private function createScopeTempTable(array $scopeRows): void
    {
        $this->ensureScopeTableExists();
        $this->clearScopeTable();

        if ($scopeRows === []) {
            return;
        }

        $batch = [];
        foreach ($scopeRows as $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            $productId = (int) ($row['product_id'] ?? 0);
            if ($sku === '') {
                continue;
            }
            $batch[] = "('" . $this->conn->real_escape_string($sku) . "', " . max(0, $productId) . ')';
            if (count($batch) >= 500) {
                $this->insertScopeTempBatch($batch);
                $batch = [];
            }
        }
        if ($batch !== []) {
            $this->insertScopeTempBatch($batch);
        }
    }

    private function ensureScopeTableExists(): void
    {
        $table = self::SCOPE_TABLE;
        $this->queryOrFail(
            "CREATE TABLE IF NOT EXISTS {$table} (
                sku VARCHAR(191) NOT NULL,
                product_id INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (sku),
                KEY idx_product_id (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'preview.scope_table.ensure',
            [
                'phase' => 'preview',
                'tables' => [$table],
                'columns' => ['sku', 'product_id'],
                'comparison' => 'permanent scope table for preview JOINs',
            ]
        );
    }

    private function clearScopeTable(): void
    {
        $table = self::SCOPE_TABLE;
        if (@$this->conn->query("TRUNCATE TABLE {$table}") === false) {
            @$this->conn->query("DELETE FROM {$table}");
        }
    }

    /** @param list<string> $valueRows */
    private function insertScopeTempBatch(array $valueRows): void
    {
        $table = self::SCOPE_TABLE;
        $sql = "INSERT INTO {$table} (sku, product_id) VALUES " . implode(',', $valueRows)
            . ' ON DUPLICATE KEY UPDATE product_id = GREATEST(product_id, VALUES(product_id))';
        $this->queryOrFail($sql, 'preview.scope_table.insert', [
            'phase' => 'preview',
            'tables' => [$table],
            'columns' => ['sku', 'product_id'],
            'comparison' => 'batch insert scoped SKUs',
        ]);
    }

    private function dropScopeTempTable(): void
    {
        $this->clearScopeTable();
    }

    private function collectDeleteCountsViaScopeTable(): array
    {
        return [
            'vp_stock_movements' => $this->countMovementRowsViaScopeTable(),
            'vp_stock' => $this->countRowsViaScopeJoin('vp_stock', 's', 's.sku = sc.sku', 'preview.count_delete_targets.vp_stock'),
        ];
    }

    private function countMovementRowsViaScopeTable(): int
    {
        $sql = 'SELECT COUNT(*) AS c
            FROM vp_stock_movements sm
            WHERE EXISTS (
                SELECT 1 FROM _stock_rebuild_scope sc
                WHERE sc.sku = sm.sku OR (sc.product_id > 0 AND sc.product_id = sm.product_id)
            )';
        $res = $this->queryOrFail($sql, 'preview.count_delete_targets.vp_stock_movements', [
            'phase' => 'preview',
            'tables' => ['vp_stock_movements', '_stock_rebuild_scope'],
            'columns' => ['vp_stock_movements.sku', 'vp_stock_movements.product_id'],
            'comparison' => 'EXISTS match on _stock_rebuild_scope sku or product_id',
        ]);
        $row = $res->fetch_assoc();
        $res->free();

        return (int) ($row['c'] ?? 0);
    }

    private function countRowsViaScopeJoin(string $table, string $alias, string $joinOn, string $step): int
    {
        $sql = "SELECT COUNT(*) AS c
            FROM {$table} {$alias}
            INNER JOIN _stock_rebuild_scope sc ON {$joinOn}";
        $res = $this->queryOrFail($sql, $step, [
            'phase' => 'preview',
            'tables' => [$table, '_stock_rebuild_scope'],
            'columns' => ["{$table}.sku"],
            'comparison' => "JOIN _stock_rebuild_scope ON {$joinOn}",
        ]);
        $row = $res->fetch_assoc();
        $res->free();

        return (int) ($row['c'] ?? 0);
    }

    private function collectOtherWarehouseUsageViaScopeTable(int $defaultWarehouseId, int $selectedWarehouseId): array
    {
        $allowedWarehouseIds = array_unique(array_filter([(int) $defaultWarehouseId, (int) $selectedWarehouseId]));
        $allowedList = $allowedWarehouseIds === [] ? '0' : implode(',', array_map('intval', $allowedWarehouseIds));
        $rows = [];

        $queries = [
            'preview.other_warehouse_usage.vp_stock' => [
                'sql' => "SELECT 'vp_stock' AS source_table, s.warehouse_id, COUNT(*) AS row_count
                    FROM vp_stock s
                    INNER JOIN _stock_rebuild_scope sc ON sc.sku = s.sku
                    WHERE s.warehouse_id NOT IN ({$allowedList})
                    GROUP BY s.warehouse_id",
                'table' => 'vp_stock',
            ],
            'preview.other_warehouse_usage.vp_stock_movements' => [
                'sql' => "SELECT 'vp_stock_movements' AS source_table, sm.warehouse_id, COUNT(*) AS row_count
                    FROM vp_stock_movements sm
                    INNER JOIN _stock_rebuild_scope sc ON sc.sku = sm.sku
                    WHERE sm.warehouse_id NOT IN ({$allowedList})
                    GROUP BY sm.warehouse_id",
                'table' => 'vp_stock_movements',
            ],
        ];

        foreach ($queries as $step => $queryMeta) {
            $res = $this->queryOrFail((string) $queryMeta['sql'], $step, [
                'phase' => 'preview',
                'tables' => [(string) $queryMeta['table'], '_stock_rebuild_scope'],
                'columns' => [(string) $queryMeta['table'] . '.sku'],
                'comparison' => 'JOIN _stock_rebuild_scope on sku',
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

        return array_values($rows);
    }

    private function countPreviewPhaseStatsViaScopeTable(int $defaultWarehouseId, int $selectedWarehouseId): array
    {
        $purchaseSql = 'SELECT COUNT(DISTINCT p.id) AS headers, COUNT(*) AS lines
            FROM vp_direct_purchases p
            INNER JOIN vp_direct_purchase_items i ON i.direct_purchase_id = p.id
            INNER JOIN _stock_rebuild_scope sc ON sc.sku = i.sku
            WHERE p.warehouse_id = ' . (int) $defaultWarehouseId;
        $purchaseRes = $this->queryOrFail($purchaseSql, 'preview.count_purchases', [
            'phase' => 'preview',
            'tables' => ['vp_direct_purchases', 'vp_direct_purchase_items', '_stock_rebuild_scope'],
            'columns' => ['vp_direct_purchase_items.sku'],
            'comparison' => 'JOIN _stock_rebuild_scope on sku',
        ]);
        $purchaseRow = $purchaseRes->fetch_assoc() ?: [];
        $purchaseRes->free();

        $returnSql = 'SELECT COUNT(DISTINCT r.id) AS headers, COUNT(*) AS lines
            FROM vp_direct_purchase_returns r
            INNER JOIN vp_direct_purchase_return_items ri ON ri.direct_purchase_return_id = r.id
            INNER JOIN vp_direct_purchase_items dpi ON dpi.id = ri.direct_purchase_item_id
            INNER JOIN _stock_rebuild_scope sc ON sc.sku = dpi.sku
            WHERE r.warehouse_id = ' . (int) $defaultWarehouseId;
        $returnRes = $this->queryOrFail($returnSql, 'preview.count_returns', [
            'phase' => 'preview',
            'tables' => ['vp_direct_purchase_returns', 'vp_direct_purchase_items', '_stock_rebuild_scope'],
            'columns' => ['vp_direct_purchase_items.sku'],
            'comparison' => 'JOIN _stock_rebuild_scope on sku',
        ]);
        $returnRow = $returnRes->fetch_assoc() ?: [];
        $returnRes->free();

        $transferInSql = 'SELECT COUNT(*) AS lines
            FROM vp_stock_transfer st
            INNER JOIN vp_stock_transfer_grns grn ON grn.transfer_id = st.id
            INNER JOIN _stock_rebuild_scope sc ON sc.sku = grn.sku
            WHERE COALESCE(NULLIF(grn.location, 0), st.to_warehouse) = ' . (int) $selectedWarehouseId . '
              AND grn.qty_received > 0';
        $transferInRes = $this->queryOrFail($transferInSql, 'preview.count_transfer_in', [
            'phase' => 'preview',
            'tables' => ['vp_stock_transfer', 'vp_stock_transfer_grns', '_stock_rebuild_scope'],
            'columns' => ['vp_stock_transfer_grns.sku'],
            'comparison' => 'JOIN _stock_rebuild_scope on sku',
        ]);
        $transferInRow = $transferInRes->fetch_assoc() ?: [];
        $transferInRes->free();

        $transferOutSql = 'SELECT COUNT(*) AS lines
            FROM vp_stock_transfer st
            INNER JOIN vp_item_stock_transfer ist ON ist.transfer_order_no = st.transfer_order_no
            INNER JOIN _stock_rebuild_scope sc ON sc.sku = ist.sku
            WHERE st.from_warehouse = ' . (int) $selectedWarehouseId . '
              AND ist.transfer_qty > 0';
        $transferOutRes = $this->queryOrFail($transferOutSql, 'preview.count_transfer_out', [
            'phase' => 'preview',
            'tables' => ['vp_stock_transfer', 'vp_item_stock_transfer', '_stock_rebuild_scope'],
            'columns' => ['vp_item_stock_transfer.sku'],
            'comparison' => 'JOIN _stock_rebuild_scope on sku',
        ]);
        $transferOutRow = $transferOutRes->fetch_assoc() ?: [];
        $transferOutRes->free();

        $invoiceSql = 'SELECT
                COUNT(DISTINCT i.id) AS headers,
                COUNT(*) AS lines,
                COUNT(DISTINCT CASE WHEN LOWER(i.status) = \'cancelled\' THEN i.id END) AS cancel_headers
            FROM vp_invoices i
            INNER JOIN vp_invoice_items ii ON ii.invoice_id = i.id
            INNER JOIN _stock_rebuild_scope sc ON sc.product_id = ii.product_id
            WHERE i.warehouse_id = ' . (int) $selectedWarehouseId . "
              AND i.status IN ('final', 'cancelled')
              AND ii.quantity > 0
              AND ii.product_id > 0";
        $invoiceRes = $this->queryOrFail($invoiceSql, 'preview.count_invoices', [
            'phase' => 'preview',
            'tables' => ['vp_invoices', 'vp_invoice_items', '_stock_rebuild_scope'],
            'columns' => ['vp_invoice_items.product_id'],
            'comparison' => 'JOIN _stock_rebuild_scope on product_id',
        ]);
        $invoiceRow = $invoiceRes->fetch_assoc() ?: [];
        $invoiceRes->free();

        return [
            'opening_candidates' => 0,
            'purchase_headers' => (int) ($purchaseRow['headers'] ?? 0),
            'purchase_lines' => (int) ($purchaseRow['lines'] ?? 0),
            'return_headers' => (int) ($returnRow['headers'] ?? 0),
            'return_lines' => (int) ($returnRow['lines'] ?? 0),
            'transfer_in_lines' => (int) ($transferInRow['lines'] ?? 0),
            'transfer_out_lines' => (int) ($transferOutRow['lines'] ?? 0),
            'invoice_headers' => (int) ($invoiceRow['headers'] ?? 0),
            'invoice_lines' => (int) ($invoiceRow['lines'] ?? 0),
            'cancel_invoice_headers' => (int) ($invoiceRow['cancel_headers'] ?? 0),
        ];
    }

    /** @param list<string> $scopeSkus @param list<int> $scopeProductIds */
    private function countPreviewPhaseStats(
        int $defaultWarehouseId,
        int $selectedWarehouseId,
        array $scopeSkus,
        array $scopeProductIds
    ): array {
        if ($scopeSkus === []) {
            return [
                'opening_candidates' => 0,
                'purchase_headers' => 0,
                'purchase_lines' => 0,
                'return_headers' => 0,
                'return_lines' => 0,
                'transfer_in_lines' => 0,
                'transfer_out_lines' => 0,
                'invoice_headers' => 0,
                'invoice_lines' => 0,
                'cancel_invoice_headers' => 0,
            ];
        }

        $skuIn = $this->quoteStringsForIn($scopeSkus);
        $productIdIn = $scopeProductIds !== [] ? $this->quoteIntsForIn($scopeProductIds) : '0';

        $purchaseSql = "SELECT COUNT(DISTINCT p.id) AS headers, COUNT(*) AS lines
            FROM vp_direct_purchases p
            INNER JOIN vp_direct_purchase_items i ON i.direct_purchase_id = p.id
            WHERE p.warehouse_id = " . (int) $defaultWarehouseId . "
              AND i.sku IN ({$skuIn})";
        $purchaseRes = $this->queryOrFail($purchaseSql, 'preview.count_purchases', [
            'phase' => 'preview',
            'tables' => ['vp_direct_purchases', 'vp_direct_purchase_items'],
            'columns' => ['vp_direct_purchase_items.sku'],
            'comparison' => 'vp_direct_purchase_items.sku IN (scoped SKU list)',
        ]);
        $purchaseRow = $purchaseRes->fetch_assoc() ?: [];
        $purchaseRes->free();

        $returnSql = "SELECT COUNT(DISTINCT r.id) AS headers, COUNT(*) AS lines
            FROM vp_direct_purchase_returns r
            INNER JOIN vp_direct_purchase_return_items ri ON ri.direct_purchase_return_id = r.id
            INNER JOIN vp_direct_purchase_items dpi ON dpi.id = ri.direct_purchase_item_id
            WHERE r.warehouse_id = " . (int) $defaultWarehouseId . "
              AND dpi.sku IN ({$skuIn})";
        $returnRes = $this->queryOrFail($returnSql, 'preview.count_returns', [
            'phase' => 'preview',
            'tables' => ['vp_direct_purchase_returns', 'vp_direct_purchase_return_items', 'vp_direct_purchase_items'],
            'columns' => ['vp_direct_purchase_items.sku'],
            'comparison' => 'vp_direct_purchase_items.sku IN (scoped SKU list)',
        ]);
        $returnRow = $returnRes->fetch_assoc() ?: [];
        $returnRes->free();

        $transferInSql = "SELECT COUNT(*) AS lines
            FROM vp_stock_transfer st
            INNER JOIN vp_stock_transfer_grns grn ON grn.transfer_id = st.id
            WHERE COALESCE(NULLIF(grn.location, 0), st.to_warehouse) = " . (int) $selectedWarehouseId . "
              AND grn.qty_received > 0
              AND TRIM(COALESCE(grn.sku, '')) <> ''
              AND grn.sku IN ({$skuIn})";
        $transferInRes = $this->queryOrFail($transferInSql, 'preview.count_transfer_in', [
            'phase' => 'preview',
            'tables' => ['vp_stock_transfer', 'vp_stock_transfer_grns'],
            'columns' => ['vp_stock_transfer_grns.sku'],
            'comparison' => 'vp_stock_transfer_grns.sku IN (scoped SKU list)',
        ]);
        $transferInRow = $transferInRes->fetch_assoc() ?: [];
        $transferInRes->free();

        $transferOutSql = "SELECT COUNT(*) AS lines
            FROM vp_stock_transfer st
            INNER JOIN vp_item_stock_transfer ist ON ist.transfer_order_no = st.transfer_order_no
            WHERE st.from_warehouse = " . (int) $selectedWarehouseId . "
              AND ist.transfer_qty > 0
              AND TRIM(COALESCE(ist.sku, '')) <> ''
              AND ist.sku IN ({$skuIn})";
        $transferOutRes = $this->queryOrFail($transferOutSql, 'preview.count_transfer_out', [
            'phase' => 'preview',
            'tables' => ['vp_stock_transfer', 'vp_item_stock_transfer'],
            'columns' => ['vp_item_stock_transfer.transfer_order_no', 'vp_stock_transfer.transfer_order_no', 'vp_item_stock_transfer.sku'],
            'comparison' => 'vp_item_stock_transfer.sku IN (scoped SKU list)',
        ]);
        $transferOutRow = $transferOutRes->fetch_assoc() ?: [];
        $transferOutRes->free();

        $invoiceSql = "SELECT
                COUNT(DISTINCT i.id) AS headers,
                COUNT(*) AS lines,
                COUNT(DISTINCT CASE WHEN LOWER(i.status) = 'cancelled' THEN i.id END) AS cancel_headers
            FROM vp_invoices i
            INNER JOIN vp_invoice_items ii ON ii.invoice_id = i.id
            WHERE i.warehouse_id = " . (int) $selectedWarehouseId . "
              AND i.status IN ('final', 'cancelled')
              AND ii.quantity > 0
              AND ii.product_id IN ({$productIdIn})";
        $invoiceRes = $this->queryOrFail($invoiceSql, 'preview.count_invoices', [
            'phase' => 'preview',
            'tables' => ['vp_invoices', 'vp_invoice_items'],
            'columns' => ['vp_invoice_items.product_id'],
            'comparison' => 'vp_invoice_items.product_id IN (scoped product ids)',
        ]);
        $invoiceRow = $invoiceRes->fetch_assoc() ?: [];
        $invoiceRes->free();

        return [
            'opening_candidates' => 0,
            'purchase_headers' => (int) ($purchaseRow['headers'] ?? 0),
            'purchase_lines' => (int) ($purchaseRow['lines'] ?? 0),
            'return_headers' => (int) ($returnRow['headers'] ?? 0),
            'return_lines' => (int) ($returnRow['lines'] ?? 0),
            'transfer_in_lines' => (int) ($transferInRow['lines'] ?? 0),
            'transfer_out_lines' => (int) ($transferOutRow['lines'] ?? 0),
            'invoice_headers' => (int) ($invoiceRow['headers'] ?? 0),
            'invoice_lines' => (int) ($invoiceRow['lines'] ?? 0),
            'cancel_invoice_headers' => (int) ($invoiceRow['cancel_headers'] ?? 0),
        ];
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
        $res = $this->queryOrFail($sql, 'preview.collect_purchases', [
            'phase' => 'preview',
            'tables' => ['vp_direct_purchases', 'vp_direct_purchase_items'],
            'columns' => ['vp_direct_purchase_items.direct_purchase_id', 'vp_direct_purchases.id'],
            'comparison' => 'vp_direct_purchase_items.direct_purchase_id = vp_direct_purchases.id',
        ]);

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
        $res = $this->queryOrFail($sql, 'preview.collect_returns', [
            'phase' => 'preview',
            'tables' => ['vp_direct_purchase_returns', 'vp_direct_purchase_return_items', 'vp_direct_purchase_items'],
            'columns' => ['vp_direct_purchase_return_items.direct_purchase_item_id', 'vp_direct_purchase_items.id'],
            'comparison' => 'vp_direct_purchase_return_items.direct_purchase_item_id = vp_direct_purchase_items.id',
        ]);

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
        $res = $this->queryOrFail($sql, 'preview.collect_transfer_in', [
            'phase' => 'preview',
            'tables' => ['vp_stock_transfer', 'vp_stock_transfer_grns'],
            'columns' => ['vp_stock_transfer_grns.transfer_id', 'vp_stock_transfer.id'],
            'comparison' => 'vp_stock_transfer_grns.transfer_id = vp_stock_transfer.id',
        ]);

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
        $transferOrderEquals = 'ist.transfer_order_no = st.transfer_order_no';
        $sql = "SELECT st.transfer_order_no, st.created_by, ist.product_id, ist.item_code, ist.sku, ist.transfer_qty
            FROM vp_stock_transfer st
            INNER JOIN vp_item_stock_transfer ist ON {$transferOrderEquals}
            WHERE st.from_warehouse = " . (int) $selectedWarehouseId . "
            ORDER BY st.id ASC, ist.id ASC";
        $res = $this->queryOrFail($sql, 'preview.collect_transfer_out', [
            'phase' => 'preview',
            'tables' => ['vp_stock_transfer', 'vp_item_stock_transfer'],
            'columns' => ['vp_item_stock_transfer.transfer_order_no', 'vp_stock_transfer.transfer_order_no'],
            'comparison' => 'vp_item_stock_transfer.transfer_order_no = vp_stock_transfer.transfer_order_no',
        ]);

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
        $res = $this->queryOrFail($sql, 'preview.collect_invoices', [
            'phase' => 'preview',
            'tables' => ['vp_invoices'],
            'columns' => ['vp_invoices.warehouse_id'],
            'comparison' => 'vp_invoices.warehouse_id = selected warehouse',
        ]);
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
        $stmt = $this->prepareOrFail($sql, 'preview.invoice_replay.invoice_items', [
            'phase' => 'preview',
            'tables' => ['vp_invoice_items'],
            'columns' => ['vp_invoice_items.invoice_id'],
            'comparison' => 'vp_invoice_items.invoice_id = ?',
        ]);
        $boundSql = $this->debugSqlWithParams($sql, [$invoiceId]);
        $stmt->bind_param('i', $invoiceId);
        $this->executeOrFail($stmt, 'preview.invoice_replay.invoice_items', $boundSql, [
            'phase' => 'preview',
            'tables' => ['vp_invoice_items'],
            'columns' => ['vp_invoice_items.invoice_id'],
            'comparison' => 'vp_invoice_items.invoice_id = ' . $invoiceId,
        ]);
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
                $boundOrderSql = $this->debugSqlWithParams(
                    $orderByInvoiceSql,
                    [$invoiceId, $orderNumber, $itemCode]
                );
                $this->executeOrFail($orderByInvoiceStmt, 'preview.invoice_replay.vp_orders_by_invoice', $boundOrderSql, [
                    'phase' => 'preview',
                    'tables' => ['vp_orders', 'vp_invoice_items'],
                    'columns' => ['vp_orders.order_number', 'vp_orders.item_code', 'vp_invoice_items.order_number', 'vp_invoice_items.item_code'],
                    'comparison' => 'vp_orders.order_number = ' . $orderNumber . ' AND vp_orders.item_code = ' . $itemCode,
                ]);
                $orderRow = $orderByInvoiceStmt->get_result()->fetch_assoc();
                if ($orderRow) {
                    $size = trim((string) ($orderRow['size'] ?? ''));
                    $color = trim((string) ($orderRow['color'] ?? ''));
                }
            }
            if ($size === '' && $color === '' && $orderByNumberStmt && $orderNumber !== '' && $itemCode !== '') {
                $orderByNumberStmt->bind_param('ss', $orderNumber, $itemCode);
                $boundOrderSql = $this->debugSqlWithParams(
                    $orderByNumberSql,
                    [$orderNumber, $itemCode]
                );
                $this->executeOrFail($orderByNumberStmt, 'preview.invoice_replay.vp_orders_by_number', $boundOrderSql, [
                    'phase' => 'preview',
                    'tables' => ['vp_orders', 'vp_invoice_items'],
                    'columns' => ['vp_orders.order_number', 'vp_orders.item_code'],
                    'comparison' => 'vp_orders.order_number = ' . $orderNumber . ' AND vp_orders.item_code = ' . $itemCode,
                ]);
                $orderRow = $orderByNumberStmt->get_result()->fetch_assoc();
                if ($orderRow) {
                    $size = trim((string) ($orderRow['size'] ?? ''));
                    $color = trim((string) ($orderRow['color'] ?? ''));
                }
            }

            $productId = (int) ($item['product_id'] ?? 0);
            if ($productId <= 0 && $itemCode !== '') {
                $productId = $this->resolveProductIdForInvoiceLine(
                    'preview.invoice_replay.product_lookup',
                    $orderNumber,
                    $itemCode,
                    $size,
                    $color
                );
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
        $this->rememberSqlDebug($step, $sql, $context);
        try {
            $res = $this->conn->query($sql);
            if ($res instanceof mysqli_result) {
                return $res;
            }

            throw $this->sqlException($step, $sql, $context);
        } catch (StockRebuildSqlException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw $this->sqlException($step, $sql, $context, $e);
        }
    }

    /** @param array<string, mixed> $context */
    private function prepareOrFail(string $sql, string $step, array $context = []): mysqli_stmt
    {
        $this->rememberSqlDebug($step, $sql, $context);
        try {
            $stmt = $this->conn->prepare($sql);
            if ($stmt instanceof mysqli_stmt) {
                return $stmt;
            }

            throw $this->sqlException($step, $sql, $context);
        } catch (StockRebuildSqlException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw $this->sqlException($step, $sql, $context, $e);
        }
    }

    /** @param array<string, mixed> $context */
    private function executeOrFail(mysqli_stmt $stmt, string $step, string $sql, array $context = []): void
    {
        $this->rememberSqlDebug($step, $sql, $context);
        try {
            if ($stmt->execute()) {
                return;
            }

            throw $this->sqlException($step, $sql, $context);
        } catch (StockRebuildSqlException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw $this->sqlException($step, $sql, $context, $e);
        }
    }

    private function resolveProductIdForInvoiceLine(
        string $step,
        string $orderNumber,
        string $itemCode,
        ?string $size = null,
        ?string $color = null
    ): int {
        $lookupSql = 'SELECT item_code, sku, size, color FROM vp_orders WHERE order_number = ? AND item_code = ? ORDER BY id ASC LIMIT 1';
        $this->rememberSqlDebug($step, $this->debugSqlWithParams($lookupSql, [$orderNumber, $itemCode]), [
            'phase' => 'preview',
            'tables' => ['vp_orders', 'vp_products'],
            'columns' => ['vp_orders.order_number', 'vp_orders.item_code'],
            'comparison' => 'vp_orders.order_number = ? AND vp_orders.item_code = ? (via Products::getProductIdForInvoiceLine)',
        ]);

        try {
            return $this->productModel->getProductIdForInvoiceLine($orderNumber, $itemCode, $size, $color);
        } catch (Throwable $e) {
            throw $this->sqlException($step, $this->debugSql, $this->debugSqlContext, $e);
        }
    }

    /** @param array<string, mixed> $context */
    private function rememberSqlDebug(string $step, string $sql, array $context = []): void
    {
        $this->debugSqlStep = $step;
        $this->debugSql = $this->normalizeSql($sql);
        $this->debugSqlContext = $context;
    }

    /** @param array<string, mixed> $detail */
    private function formatSqlErrorResponse(StockRebuildSqlException $e): array
    {
        $detail = $e->getDetail();

        return [
            'success' => false,
            'message' => $e->getMessage(),
            'failed_sql' => $detail['failed_sql'] ?? null,
            'failed_condition' => $detail['failed_condition'] ?? ($detail['comparison'] ?? null),
            'error_detail' => $detail,
        ];
    }

    /** @param list<mixed> $params */
    private function debugSqlWithParams(string $sql, array $params): string
    {
        $out = $sql;
        foreach ($params as $param) {
            if (is_int($param) || is_float($param)) {
                $quoted = (string) $param;
            } else {
                $quoted = "'" . str_replace("'", "''", (string) $param) . "'";
            }
            $out = preg_replace('/\?/', $quoted, $out, 1) ?? $out;
        }

        return $out;
    }

    /** @param array<string, mixed> $context */
    private function sqlException(string $step, string $sql, array $context = [], ?Throwable $previous = null): StockRebuildSqlException
    {
        $mysqlError = (string) ($this->conn->error ?? '');
        $mysqlErrno = (int) ($this->conn->errno ?? 0);
        if ($mysqlError === '' && $previous !== null) {
            $mysqlError = $previous->getMessage();
            $mysqlErrno = (int) $previous->getCode();
        }
        $database = $this->currentDatabase();
        $connectionCollation = $this->connectionCollation();
        $isCollationError = stripos($mysqlError, 'collation') !== false || stripos($mysqlError, 'charset') !== false;

        $normalizedSql = $this->normalizeSql($sql);
        $failedCondition = $context['comparison'] ?? null;

        $detail = array_merge([
            'step' => $step,
            'phase' => $context['phase'] ?? null,
            'mysql_error' => $mysqlError,
            'mysql_errno' => $mysqlErrno,
            'database' => $database,
            'connection_collation' => $connectionCollation,
            'tables' => array_values((array) ($context['tables'] ?? [])),
            'columns' => array_values((array) ($context['columns'] ?? [])),
            'comparison' => $failedCondition,
            'failed_condition' => $failedCondition,
            'failed_sql' => $normalizedSql,
            'sql_preview' => $this->compactSql($normalizedSql),
            'diagnostic_sql' => $isCollationError ? $this->collationDiagnosticSql((array) ($context['tables'] ?? []), (array) ($context['columns'] ?? [])) : null,
        ], $context);

        $message = 'Stock rebuild failed at step "' . $step . '"';
        if ($mysqlError !== '') {
            $message .= '. MySQL [' . $mysqlErrno . ']: ' . $mysqlError;
        }
        if (!empty($failedCondition)) {
            $message .= '. Failed condition: ' . $failedCondition;
        }
        if ($normalizedSql !== '') {
            $message .= '. Failed query: ' . $this->compactSql($normalizedSql, 2500);
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

        return new StockRebuildSqlException($message, $detail, $previous);
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

    private function normalizeSql(string $sql): string
    {
        return preg_replace('/\s+/', ' ', trim($sql)) ?? trim($sql);
    }

    private function compactSql(string $sql, int $maxLength = 1200): string
    {
        $compact = $this->normalizeSql($sql);
        if (strlen($compact) <= $maxLength) {
            return $compact;
        }

        return substr($compact, 0, $maxLength) . '...';
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
