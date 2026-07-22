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
    $warehouseId = (int) ($_SESSION['warehouse_id'] ?? 0);
    if ($warehouseId <= 0 && !empty($_SESSION['user']['warehouse_id'])) {
        $warehouseId = (int) $_SESSION['user']['warehouse_id'];
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
