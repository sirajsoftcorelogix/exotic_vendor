<?php

require_once __DIR__ . '/order_filter_autocomplete.php';

function normalizeFilterOperator(?string $op): string
{
    return strtolower(trim((string) $op)) === 'not_in' ? 'not_in' : 'in';
}

/**
 * @param mixed $selected
 * @return array<int, string>
 */
function normalizeFilterValues($selected): array
{
    if ($selected === null || $selected === '' || $selected === 'all') {
        return [];
    }

    $values = is_array($selected) ? $selected : [$selected];

    return array_values(array_filter($values, static function ($value) {
        return $value !== '' && $value !== 'all';
    }));
}

function appendSqlInOrNotIn(
    string &$sql,
    array &$params,
    string $column,
    array $values,
    string $op = 'in'
): void {
    $values = normalizeFilterValues($values);
    if ($values === []) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($values), '?'));
    $keyword = normalizeFilterOperator($op) === 'not_in' ? 'NOT IN' : 'IN';
    $sql .= " AND {$column} {$keyword} ({$placeholders})";
    foreach ($values as $value) {
        $params[] = $value;
    }
}

function appendOrderStatusFilterSql(string &$sql, array &$params, array $filters, string $column = 'vp_orders.status'): void
{
    if (empty($filters['status_filter']) || $filters['status_filter'] === 'all') {
        return;
    }

    $status = $filters['status_filter'];
    if ($status === 'pending') {
        $sql .= " AND {$column} = 'pending'";
    } elseif ($status === 'processed') {
        $sql .= " AND {$column} IN ('ready_for_packing','po_pending','po_approved','po_inprogress','item_received','added_to_picklist','store_transfer','ready_for_qc','sent_for_repair')";
    } elseif ($status === 'dispatch') {
        $sql .= " AND {$column} IN ('ready_for_dispatch')";
    } elseif ($status === 'shipped') {
        $sql .= " AND {$column} = 'shipped'";
    } elseif (is_array($status)) {
        appendSqlInOrNotIn($sql, $params, $column, $status, $filters['status_op'] ?? 'in');
    } else {
        $sql .= " AND {$column} = '" . $status . "'";
    }
}

function appendOrderPaymentTypeFilterSql(string &$sql, array &$params, array $filters, string $column = 'vp_orders.payment_type'): void
{
    if (empty($filters['payment_type']) || $filters['payment_type'] === 'all') {
        return;
    }

    if (is_array($filters['payment_type'])) {
        appendSqlInOrNotIn($sql, $params, $column, $filters['payment_type'], $filters['payment_type_op'] ?? 'in');
        return;
    }

    $sql .= normalizeFilterOperator($filters['payment_type_op'] ?? 'in') === 'not_in'
        ? " AND {$column} != ?"
        : " AND {$column} = ?";
    $params[] = $filters['payment_type'];
}

/**
 * @param mixed $value
 * @return array<int, string>
 */
function parseOrderNumberFilter($value): array
{
    if ($value === null || $value === '') {
        return [];
    }

    $parts = is_array($value)
        ? $value
        : preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);

    $normalized = [];
    foreach ($parts as $part) {
        $part = trim((string) $part);
        if ($part !== '') {
            $normalized[] = $part;
        }
    }

    return array_values(array_unique($normalized));
}

function appendOrderNumberFilterSql(
    string &$sql,
    array &$params,
    $orderNumber,
    string $column = 'vp_orders.order_number'
): void {
    $orderNumbers = parseOrderNumberFilter($orderNumber);
    if ($orderNumbers === []) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($orderNumbers), '?'));
    $sql .= " AND {$column} IN ($placeholders)";
    foreach ($orderNumbers as $orderNum) {
        $params[] = $orderNum;
    }
}

function normalizeStockAvailableFilter(?string $value): string
{
    $value = strtolower(trim((string) $value));

    return in_array($value, ['yes', 'no'], true) ? $value : '';
}

function resolveOrderListDefaultWarehouseId(): int
{
    // Login assignment (vp_users.warehouse_id) is authoritative for list scoping.
    $warehouseId = (int) ($_SESSION['user']['warehouse_id'] ?? 0);
    if ($warehouseId <= 0) {
        $warehouseId = (int) ($_SESSION['warehouse_id'] ?? 0);
    }

    return max(0, $warehouseId);
}

function appendOrderStockAvailabilityFilterSql(string &$sql, array &$params, array $filters): void
{
    $stockAvailable = normalizeStockAvailableFilter($filters['stock_available'] ?? '');
    $warehouseId = (int) ($filters['stock_warehouse_id'] ?? 0);
    if ($stockAvailable === '' || $warehouseId <= 0) {
        return;
    }

    $skuMatchSql = 's.sku COLLATE utf8mb4_unicode_ci = vp_orders.sku COLLATE utf8mb4_unicode_ci';
    $stockExistsSql = "EXISTS (
        SELECT 1 FROM vp_stock s
        WHERE {$skuMatchSql}
          AND s.warehouse_id = ?
          AND s.current_stock >= COALESCE(vp_orders.quantity, 1)
    )";

    if ($stockAvailable === 'yes') {
        $sql .= " AND vp_orders.sku IS NOT NULL AND vp_orders.sku <> '' AND {$stockExistsSql}";
        $params[] = $warehouseId;
        return;
    }

    $sql .= " AND (
        vp_orders.sku IS NULL OR vp_orders.sku = ''
        OR NOT EXISTS (
            SELECT 1 FROM vp_stock s
            WHERE {$skuMatchSql}
              AND s.warehouse_id = ?
              AND s.current_stock >= COALESCE(vp_orders.quantity, 1)
        )
    )";
    $params[] = $warehouseId;
}

/**
 * Restrict order list to the user's assigned warehouse unless they have Sr Emp+ on the page module.
 *
 * @param array<string, mixed> $filters
 * @return array<string, mixed>
 */
function applyPosOrderListWarehouseScope(array $filters, ?string $pageSlug = null, ?string $pageAction = null): array
{
    if (!function_exists('canViewAllWarehousesForPage')) {
        require_once __DIR__ . '/html_helpers.php';
    }
    if (canViewAllWarehousesForPage($pageSlug, $pageAction)) {
        return $filters;
    }

    $filters['warehouse_scope_enforced'] = true;
    $filters['warehouse_scope_id'] = resolveOrderListDefaultWarehouseId();

    return $filters;
}

/**
 * @param array<string, mixed> $filters
 */
function appendPosOrderWarehouseScopeFilterSql(
    string &$sql,
    array &$params,
    array $filters,
    string $ordersAlias = 'vp_orders'
): void {
    if (empty($filters['warehouse_scope_enforced'])) {
        return;
    }

    $warehouseId = (int) ($filters['warehouse_scope_id'] ?? 0);
    if ($warehouseId <= 0) {
        $sql .= ' AND 1=0';

        return;
    }

    $warehouseKey = (string) $warehouseId;
    $sql .= " AND (
        CAST({$ordersAlias}.store_name AS UNSIGNED) = ?
        OR TRIM({$ordersAlias}.store_name) = ?
    )";
    $params[] = $warehouseId;
    $params[] = $warehouseKey;
}

/**
 * @param array<int, mixed> $params
 * @return array{sql:string,params:array<int,mixed>,types:?string,interpolated:string}
 */
function buildSqlQueryDebugSnapshot(string $sql, array $params, ?string $types = null): array
{
    return [
        'sql' => $sql,
        'params' => $params,
        'types' => $types,
        'interpolated' => interpolateSqlDebugQuery($sql, $params, $types),
    ];
}

/**
 * @param array<int, mixed> $params
 */
function interpolateSqlDebugQuery(string $sql, array $params, ?string $types = null): string
{
    $index = 0;

    return (string) preg_replace_callback('/\?/', static function () use (&$index, $params, $types) {
        if (!array_key_exists($index, $params)) {
            return '?';
        }

        $value = $params[$index];
        $type = ($types !== null && isset($types[$index])) ? $types[$index] : 's';
        $index++;

        if ($type === 'i') {
            return (string) (int) $value;
        }
        if ($type === 'd') {
            return (string) (float) $value;
        }

        return "'" . str_replace("'", "''", (string) $value) . "'";
    }, $sql);
}

/**
 * Debug context for POS Orders list (warehouse scope + SQL).
 *
 * @param array<string, mixed> $filters
 * @return array<string, mixed>
 */
function buildPosOrderListDebugContext(array $filters, $ordersModel): array
{
    if (!function_exists('canViewAllWarehousesForPage')) {
        require_once __DIR__ . '/html_helpers.php';
    }

    $sessionUser = is_array($_SESSION['user'] ?? null) ? $_SESSION['user'] : [];
    $safeUserKeys = ['id', 'name', 'email', 'phone', 'role_id', 'warehouse_id', 'is_active'];
    $user = [];
    foreach ($safeUserKeys as $key) {
        if (array_key_exists($key, $sessionUser)) {
            $user[$key] = $sessionUser[$key];
        }
    }

    $scopeDecision = resolveWarehouseScopeDecision(null, null);

    return [
        'generated_at' => date('Y-m-d H:i:s'),
        'request_page' => (string) ($_GET['page'] ?? ''),
        'request_action' => (string) ($_GET['action'] ?? ''),
        'user' => $user,
        'session_user_id' => (int) ($_SESSION['user_id'] ?? 0),
        'session_warehouse_id' => $_SESSION['warehouse_id'] ?? null,
        'resolved_warehouse_id' => resolveOrderListDefaultWarehouseId(),
        'page_module_names' => resolvePagePermissionModuleNames(null, null),
        'warehouse_scope_decision' => $scopeDecision,
        'can_view_all_warehouses' => (bool) ($scopeDecision['can_view_all_warehouses'] ?? false),
        'is_administrator' => isAdministratorUser(),
        'filters' => $filters,
        'list_query' => method_exists($ordersModel, 'getLastListQueryDebug') ? $ordersModel->getLastListQueryDebug() : null,
        'count_query' => method_exists($ordersModel, 'getLastCountQueryDebug') ? $ordersModel->getLastCountQueryDebug() : null,
    ];
}

function buildOrderListFiltersFromRequest(array $request): array
{
    $filters = [];
    $orderNumbers = parseOrderNumberFilter($request['order_number'] ?? null);
    if ($orderNumbers !== []) {
        $filters['order_number'] = $orderNumbers;
    }
    if (!empty($request['item_code'])) {
        $filters['item_code'] = $request['item_code'];
    }
    if (!empty($request['sku'])) {
        $filters['sku'] = $request['sku'];
    }
    if (!empty($request['order_from']) && !empty($request['order_till'])) {
        $filters['order_from'] = $request['order_from'];
        $filters['order_till'] = $request['order_till'];
    }
    if (!empty($request['item_name'])) {
        $filters['title'] = $request['item_name'];
    }
    if (!empty($request['min_amount'])) {
        $filters['min_amount'] = $request['min_amount'];
    }
    if (!empty($request['max_amount'])) {
        $filters['max_amount'] = $request['max_amount'];
    }
    if (!empty($request['po_no'])) {
        $filters['po_no'] = $request['po_no'];
    }
    if (!empty($request['status'])) {
        $filters['status_filter'] = $request['status'];
        $filters['status_op'] = normalizeFilterOperator($request['status_op'] ?? 'in');
    }
    if (!empty($request['category']) && $request['category'] != 'all') {
        $filters['category'] = $request['category'];
    } else {
        $filters['category'] = 'all';
    }
    if (!empty($request['country'])) {
        $filters['country'] = $request['country'];
    }
    if (!empty($request['options']) && $request['options'] == 'express') {
        $filters['options'] = 'express';
    }
    if (!empty($request['sort']) && in_array(strtolower((string) $request['sort']), ['asc', 'desc'], true)) {
        $filters['sort'] = strtolower($request['sort']);
    } else {
        $filters['sort'] = 'desc';
    }
    if (!empty($request['payment_type']) && $request['payment_type'] != 'all') {
        $filters['payment_type'] = $request['payment_type'];
        $filters['payment_type_op'] = normalizeFilterOperator($request['payment_type_op'] ?? 'in');
    } else {
        $filters['payment_type'] = 'all';
    }
    if (!empty($request['staff_name'])) {
        $filters['staff_name'] = $request['staff_name'];
    }
    if (!empty($request['priority'])) {
        $filters['priority'] = $request['priority'];
    }
    $vendorFilter = resolveOrderListVendorFilter($request);
    if ($vendorFilter !== '') {
        $filters['vendor'] = $vendorFilter;
    }
    if (!empty($request['agent'])) {
        $filters['agent'] = $request['agent'];
    }
    $publisherFilter = resolveOrderListPublisherFilter($request);
    if ($publisherFilter !== '') {
        $filters['publisher'] = $publisherFilter;
    }
    $authorFilter = resolveOrderListAuthorFilter($request);
    if ($authorFilter !== '') {
        $filters['author'] = $authorFilter;
    }
    if (!empty($request['options']) && $request['options'] == 'unshipped') {
        $filters['unshipped'] = true;
    }
    if (!empty($request['sortdaterange'])) {
        $filters['sortdaterange'] = $request['sortdaterange'];
    }
    $stockAvailable = normalizeStockAvailableFilter($request['stock_available'] ?? '');
    if ($stockAvailable !== '') {
        $warehouseId = (int) ($request['stock_warehouse_id'] ?? 0);
        if ($warehouseId <= 0) {
            $warehouseId = resolveOrderListDefaultWarehouseId();
        }
        if ($warehouseId > 0) {
            $filters['stock_available'] = $stockAvailable;
            $filters['stock_warehouse_id'] = $warehouseId;
        }
    }

    return $filters;
}
