<?php

class Location
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    private static function allowedAddressTypes()
    {
        return ['retail_store', 'warehouse'];
    }

    public function normalizeAddressType($type)
    {
        $type = is_string($type) ? trim($type) : '';
        return in_array($type, self::allowedAddressTypes(), true) ? $type : 'retail_store';
    }

    public function getAll($page = 1, $limit = 10, $search = '', $status_filter = '', $type_filter = '')
    {
        $page = (int) $page;
        if ($page < 1) {
            $page = 1;
        }

        $limit = (int) $limit;
        if ($limit < 1) {
            $limit = 10;
        }

        $offset = ($page - 1) * $limit;
        $where = [];
        $types = '';
        $params = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(address_title LIKE ? OR display_name LIKE ? OR address LIKE ?)';
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($status_filter !== '' && $status_filter !== null) {
            $where[] = 'is_active = ?';
            $types .= 'i';
            $params[] = (int) $status_filter;
        }

        if ($type_filter !== '' && $type_filter !== null) {
            $tf = $this->normalizeAddressType($type_filter);
            $where[] = 'address_type = ?';
            $types .= 's';
            $params[] = $tf;
        }

        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) AS total FROM exotic_address $whereSql";
        $stmtCount = $this->conn->prepare($countSql);
        if ($types !== '') {
            $stmtCount->bind_param($types, ...$params);
        }
        $stmtCount->execute();
        $countRes = $stmtCount->get_result()->fetch_assoc();
        $totalRecords = (int) ($countRes['total'] ?? 0);
        $stmtCount->close();

        $totalPages = $limit > 0 ? (int) ceil($totalRecords / $limit) : 1;

        $sql = "SELECT * FROM exotic_address $whereSql ORDER BY order_no ASC, address_title ASC, id ASC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        if ($types !== '') {
            $types2 = $types . 'ii';
            $stmt->bind_param($types2, ...array_merge($params, [$limit, $offset]));
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();

        return [
            'locations' => $data,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'limit' => $limit,
            'totalRecords' => $totalRecords,
            'search' => $search,
        ];
    }

    private function clearDefaultExcept($exceptId)
    {
        $sql = 'UPDATE exotic_address SET is_default = 0 WHERE id != ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $exceptId);
        $stmt->execute();
        $stmt->close();
    }

    public function addRecord($data)
    {
        $address_type = $this->normalizeAddressType($data['addAddressType'] ?? '');
        $address_title = isset($data['addAddressTitle']) ? trim((string) $data['addAddressTitle']) : '';
        $display_name = isset($data['addDisplayName']) ? trim((string) $data['addDisplayName']) : '';
        $address = isset($data['addAddress']) ? trim((string) $data['addAddress']) : '';
        $order_no = isset($data['addOrderNo']) ? (int) $data['addOrderNo'] : 0;
        $is_default = isset($data['addIsDefault']) ? (int) $data['addIsDefault'] : 0;
        $is_active = isset($data['addStatus']) ? (int) $data['addStatus'] : 1;

        if ($address === '') {
            return ['success' => false, 'message' => 'Address is required.'];
        }

        $is_default = $is_default ? 1 : 0;

        $this->conn->begin_transaction();
        try {
            $sql = 'INSERT INTO exotic_address (address_type, address_title, display_name, address, order_no, is_default, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?)';
            $stmt = $this->conn->prepare($sql);
            $addrTitleNull = $address_title === '' ? null : $address_title;
            $dispNull = $display_name === '' ? null : $display_name;
            $stmt->bind_param(
                'ssssiii',
                $address_type,
                $addrTitleNull,
                $dispNull,
                $address,
                $order_no,
                $is_default,
                $is_active
            );
            if (!$stmt->execute()) {
                throw new \Exception($stmt->error);
            }
            $newId = (int) $this->conn->insert_id;
            $stmt->close();

            if ($is_default === 1) {
                $this->clearDefaultExcept($newId);
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'Location added successfully.'];
        } catch (\Throwable $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Could not save: ' . $e->getMessage()];
        }
    }

    public function updateRecord($id, $data)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid location id.'];
        }

        $address_type = $this->normalizeAddressType($data['editAddressType'] ?? '');
        $address_title = isset($data['editAddressTitle']) ? trim((string) $data['editAddressTitle']) : '';
        $display_name = isset($data['editDisplayName']) ? trim((string) $data['editDisplayName']) : '';
        $address = isset($data['editAddress']) ? trim((string) $data['editAddress']) : '';
        $order_no = isset($data['editOrderNo']) ? (int) $data['editOrderNo'] : 0;
        $is_default = isset($data['editIsDefault']) ? (int) $data['editIsDefault'] : 0;
        $is_active = isset($data['editStatus']) ? (int) $data['editStatus'] : 1;

        if ($address === '') {
            return ['success' => false, 'message' => 'Address is required.'];
        }

        $is_default = $is_default ? 1 : 0;

        $this->conn->begin_transaction();
        try {
            $sql = 'UPDATE exotic_address SET address_type = ?, address_title = ?, display_name = ?, address = ?, order_no = ?, is_default = ?, is_active = ? WHERE id = ?';
            $stmt = $this->conn->prepare($sql);
            $addrTitleNull = $address_title === '' ? null : $address_title;
            $dispNull = $display_name === '' ? null : $display_name;
            $stmt->bind_param(
                'ssssiiii',
                $address_type,
                $addrTitleNull,
                $dispNull,
                $address,
                $order_no,
                $is_default,
                $is_active,
                $id
            );
            if (!$stmt->execute()) {
                throw new \Exception($stmt->error);
            }
            $stmt->close();

            if ($is_default === 1) {
                $this->clearDefaultExcept($id);
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'Location updated successfully.'];
        } catch (\Throwable $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Could not update: ' . $e->getMessage()];
        }
    }

    /**
     * Soft-delete: marks location inactive so existing foreign keys remain valid.
     */
    public function deleteRecord($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid ID.'];
        }

        $sql = 'UPDATE exotic_address SET is_active = 0, is_default = 0 WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Location deactivated.'];
        }
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Update failed: ' . $err];
    }

    public function getRecord($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->conn->prepare(
            'SELECT id, address_type, address_title, display_name, address, order_no, is_default, is_active, created_on FROM exotic_address WHERE id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}
