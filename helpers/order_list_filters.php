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

function buildOrderListFiltersFromRequest(array $request): array
{
    $filters = [];
    if (!empty($request['order_number'])) {
        $filters['order_number'] = $request['order_number'];
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

    return $filters;
}
