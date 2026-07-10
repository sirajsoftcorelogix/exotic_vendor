<?php

/** LEFT JOIN vendors + publishers for vp_direct_purchases supplier resolution. */
function dp_supplier_join(string $alias = 'p'): string
{
    return "LEFT JOIN vp_vendors v ON (COALESCE({$alias}.vendor_type, 'vendor') = 'vendor' AND v.id = {$alias}.vendor_id)
        LEFT JOIN vp_publishers pub ON (COALESCE({$alias}.vendor_type, 'vendor') = 'publisher' AND pub.id = {$alias}.vendor_id)";
}

function dp_supplier_select(string $alias = 'p'): string
{
    return "COALESCE(v.vendor_name, pub.publishers) AS vendor_name,
        COALESCE(v.contact_name, '') AS contact_name,
        CASE WHEN COALESCE({$alias}.vendor_type, 'vendor') = 'publisher' THEN CAST(pub.publishers_id AS CHAR) ELSE v.vendor_id END AS exotic_vendor_id";
}

/** @return array{is_book:bool, selected:string} */
function dp_supplier_form_state(?array $purchase): array
{
    $selected = '';
    $isBook = false;
    if ($purchase) {
        $vendorType = strtolower(trim((string) ($purchase['vendor_type'] ?? 'vendor')));
        $isBook = $vendorType === 'publisher';
        $prefix = $isBook ? 'publisher' : 'vendor';
        $selected = $prefix . ':' . (int) ($purchase['vendor_id'] ?? 0);
    }

    return ['is_book' => $isBook, 'selected' => $selected];
}

/**
 * @return array{key:string, label:string}|null
 */
function dp_supplier_resolve_option(mysqli $conn, string $supplierKey): ?array
{
    if (!preg_match('/^(vendor|publisher):(\d+)$/', trim($supplierKey), $m)) {
        return null;
    }

    $type = $m[1];
    $id = (int) $m[2];
    if ($id <= 0) {
        return null;
    }

    if ($type === 'publisher') {
        $stmt = $conn->prepare('SELECT id, publishers FROM vp_publishers WHERE id = ? AND is_active = 1 LIMIT 1');
    } else {
        $stmt = $conn->prepare(
            "SELECT id, vendor_id, vendor_name FROM vp_vendors
             WHERE id = ? AND (is_active = 'active' OR is_active = 1) LIMIT 1"
        );
    }
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return null;
    }

    if ($type === 'publisher') {
        $name = trim((string) ($row['publishers'] ?? ''));

        return $name !== '' ? ['key' => 'publisher:' . $id, 'label' => $name . ' (publisher)'] : null;
    }

    $exoticId = trim((string) ($row['vendor_id'] ?? ''));
    $name = trim((string) ($row['vendor_name'] ?? ''));
    if ($exoticId !== '' && $name !== '') {
        $label = $exoticId . '-' . $name;
    } elseif ($exoticId !== '') {
        $label = $exoticId;
    } else {
        $label = $name !== '' ? $name : ('Vendor #' . $id);
    }

    return ['key' => 'vendor:' . $id, 'label' => $label];
}

/**
 * @return list<array{id:string,text:string}>
 */
function dp_supplier_search(mysqli $conn, string $query, bool $includePublishers, int $limit = 40): array
{
    $query = trim($query);
    if (function_exists('mb_strlen')) {
        if (mb_strlen($query) < 2) {
            return [];
        }
    } elseif (strlen($query) < 2) {
        return [];
    }

    $limit = max(1, min(50, $limit));
    $like = '%' . $query . '%';
    $results = [];

    $stmt = $conn->prepare(
        "SELECT id, vendor_id, vendor_name FROM vp_vendors
         WHERE (is_active = 'active' OR is_active = 1)
           AND vendor_id IS NOT NULL AND TRIM(vendor_id) <> ''
           AND (vendor_name LIKE ? OR vendor_id LIKE ? OR contact_name LIKE ? OR vendor_code LIKE ?)
         ORDER BY vendor_name ASC
         LIMIT ?"
    );
    if ($stmt) {
        $stmt->bind_param('ssssi', $like, $like, $like, $like, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $exoticId = trim((string) ($row['vendor_id'] ?? ''));
                $name = trim((string) ($row['vendor_name'] ?? ''));
                if ($exoticId !== '' && $name !== '') {
                    $label = $exoticId . '-' . $name;
                } elseif ($exoticId !== '') {
                    $label = $exoticId;
                } else {
                    $label = $name !== '' ? $name : ('Vendor #' . $id);
                }
                $results[] = ['id' => 'vendor:' . $id, 'text' => $label];
            }
        }
        $stmt->close();
    }

    if (!$includePublishers) {
        return $results;
    }

    $pubLimit = max(1, $limit - count($results));
    $stmt = $conn->prepare(
        'SELECT id, publishers_id, publishers FROM vp_publishers
         WHERE is_active = 1
           AND (publishers LIKE ? OR CAST(publishers_id AS CHAR) LIKE ? OR CAST(id AS CHAR) LIKE ?)
         ORDER BY publishers ASC
         LIMIT ?'
    );
    if ($stmt) {
        $stmt->bind_param('sssi', $like, $like, $like, $pubLimit);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $id = (int) ($row['id'] ?? 0);
                $name = trim((string) ($row['publishers'] ?? ''));
                if ($id <= 0 || $name === '') {
                    continue;
                }
                $results[] = ['id' => 'publisher:' . $id, 'text' => $name . ' (publisher)'];
            }
        }
        $stmt->close();
    }

    return $results;
}

/**
 * @return array{vendor_id:int,vendor_type:string,error?:string}
 */
function dp_supplier_parse_post(array $post, ?array $existing, bool $isEdit, mysqli $conn): array
{
    if ($isEdit && is_array($existing)) {
        $isBook = strtolower(trim((string) ($existing['vendor_type'] ?? 'vendor'))) === 'publisher';
    } else {
        $isBook = strtolower(trim((string) ($post['purchase_type'] ?? 'non_book'))) === 'book';
    }

    $supplierKey = trim((string) ($post['supplier'] ?? ''));
    if (!preg_match('/^(vendor|publisher):(\d+)$/', $supplierKey, $m)) {
        return ['vendor_id' => 0, 'vendor_type' => 'vendor', 'error' => 'Vendor is required.'];
    }

    if (!$isBook && $m[1] !== 'vendor') {
        return ['vendor_id' => 0, 'vendor_type' => 'vendor', 'error' => 'Invalid supplier for non-book purchase.'];
    }

    $vendorType = $m[1];
    $vendorId = (int) $m[2];
    if ($vendorId <= 0) {
        return ['vendor_id' => 0, 'vendor_type' => $vendorType, 'error' => 'Vendor is required.'];
    }

    if ($vendorType === 'publisher') {
        $stmt = $conn->prepare('SELECT id FROM vp_publishers WHERE id = ? AND is_active = 1 LIMIT 1');
    } else {
        $stmt = $conn->prepare("SELECT id FROM vp_vendors WHERE id = ? AND (is_active = 'active' OR is_active = 1) LIMIT 1");
    }
    if (!$stmt) {
        return ['vendor_id' => $vendorId, 'vendor_type' => $vendorType, 'error' => 'Could not validate supplier.'];
    }
    $stmt->bind_param('i', $vendorId);
    $stmt->execute();
    $ok = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ok) {
        return ['vendor_id' => $vendorId, 'vendor_type' => $vendorType, 'error' => 'Selected supplier is invalid or inactive.'];
    }

    return ['vendor_id' => $vendorId, 'vendor_type' => $vendorType];
}

function dp_supplier_list_label(array $row): string
{
    $exoticId = trim((string) ($row['exotic_vendor_id'] ?? ''));
    $name = trim((string) ($row['vendor_name'] ?? ''));
    if ($exoticId !== '' && $name !== '') {
        $label = $exoticId . '-' . $name;
    } elseif ($exoticId !== '') {
        $label = $exoticId;
    } else {
        $label = $name;
    }
    if (strtolower(trim((string) ($row['vendor_type'] ?? 'vendor'))) === 'publisher') {
        $label .= ' (publisher)';
    }

    return $label;
}
