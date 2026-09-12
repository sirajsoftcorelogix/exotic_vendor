<?php
require_once __DIR__ . '/../../helpers/international_invoice_defaults.php';

class Invoice
{
    private $db;

    public function __construct($conn)
    {
        $this->db = $conn;
    }

    public function getAllInvoices($limit, $offset)
    {
        $sql = "SELECT i.*, c.id AS customer_id, c.name, c.email, c.phone FROM vp_invoices i 
                LEFT JOIN vp_customers c ON i.customer_id = c.id 
                ORDER BY i.invoice_date DESC LIMIT $limit OFFSET $offset";
        $result = $this->db->query($sql);
        $invoices = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $invoices[] = $row;
            }
        }
        return $invoices;
    }

    public function countAllInvoices()
    {
        $sql = "SELECT COUNT(*) AS cnt FROM vp_invoices";
        $result = $this->db->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            return isset($row['cnt']) ? (int)$row['cnt'] : 0;
        }
        return 0;
    }

    public function createInvoice($data)
    {
        $sql = "INSERT INTO vp_invoices (invoice_number, invoice_date, customer_id, vp_order_info_id, currency, subtotal, tax_amount, discount_amount, total_amount, status, created_by, created_at, exchange_text, converted_amount, batch_no, warehouse_id, pos_flag) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $warehouse_id = (int)($_SESSION['warehouse_id'] ?? 0);
        $pos_flag = (int)($data['pos_flag'] ?? 0);
        $customer_id = (int)($data['customer_id'] ?? 0);
        $vp_order_info_id = (int)($data['vp_order_info_id'] ?? 0);
        $batch_no = (string)($data['batch_no'] ?? '');
        $invoice_number = (string)($data['invoice_number'] ?? '');
        $invoice_date = (string)($data['invoice_date'] ?? '');
        $currency = (string)($data['currency'] ?? '');
        $subtotal = (float)($data['subtotal'] ?? 0);
        $tax_amount = (float)($data['tax_amount'] ?? 0);
        $discount_amount = (float)($data['discount_amount'] ?? 0);
        $total_amount = (float)($data['total_amount'] ?? 0);
        $status = (string)($data['status'] ?? '');
        $created_by = (int)($data['created_by'] ?? 0);
        $created_at = (string)($data['created_at'] ?? '');
        $exchange_text = (string)($data['exchange_text'] ?? '');
        $converted_amount = (float)($data['converted_amount'] ?? 0);

        $stmt->bind_param(
            'ssiisddddsissdsii',
            $invoice_number,
            $invoice_date,
            $customer_id,
            $vp_order_info_id,
            $currency,
            $subtotal,
            $tax_amount,
            $discount_amount,
            $total_amount,
            $status,
            $created_by,
            $created_at,
            $exchange_text,
            $converted_amount,
            $batch_no,
            $warehouse_id,
            $pos_flag
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    private function ensureInvoiceItemsProductIdColumn(): void
    {
        $r = @$this->db->query("SHOW COLUMNS FROM vp_invoice_items LIKE 'product_id'");
        if ($r && $r->num_rows > 0) {
            return;
        }
        @$this->db->query("ALTER TABLE vp_invoice_items ADD COLUMN product_id INT UNSIGNED NULL DEFAULT NULL AFTER item_code");
    }

    public function createInvoiceItem($data)
    {
        $this->ensureInvoiceItemsProductIdColumn();
        $sql = "INSERT INTO vp_invoice_items (invoice_id, order_number, item_code, product_id, hsn, item_name, description, box_no, quantity, unit_price, tax_rate, cgst, sgst, igst, tax_amount, line_total, image_url, groupname)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $productId = isset($data['product_id']) ? (int)$data['product_id'] : 0;

        $stmt->bind_param(
            'ississssidddddddss',
            $data['invoice_id'],
            $data['order_number'],
            $data['item_code'],
            $productId,
            $data['hsn'],
            $data['item_name'],
            $data['description'],
            $data['box_no'],
            $data['quantity'],
            $data['unit_price'],
            $data['tax_rate'],
            $data['cgst'],
            $data['sgst'],
            $data['igst'],
            $data['tax_amount'],
            $data['line_total'],
            $data['image_url'],
            $data['groupname']
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function getInvoiceById($id)
    {
        $sql = "SELECT * FROM vp_invoices WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public function getInvoiceItems($invoice_id)
    {
        $sql = "SELECT * FROM vp_invoice_items WHERE invoice_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param('i', $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        return $items;
    }

    public function syncInvoiceEwbData($invoice_id, $data = [])
    {
        $irn = $data['irn'] ?? null;
        $ewbNumber = $data['ewb_number'] ?? null;
        $ackNumber = $data['ack_number'] ?? null;
        $ackDate = $data['ack_date'] ?? null;
        $invoiceId = (int) $invoice_id;

        $stmt = $this->db->prepare(
            "UPDATE vp_invoices
            SET irn = COALESCE(?, irn),
                ewb_number = COALESCE(?, ewb_number),
                ack_number = COALESCE(?, ack_number),
                ack_date = COALESCE(?, ack_date)
            WHERE id = ?"
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            'ssssi',
            $irn,
            $ewbNumber,
            $ackNumber,
            $ackDate,
            $invoiceId
        );

        return $stmt->execute();
    }

    public function updateInvoiceStatus($id, $status)
    {
        $sql = "UPDATE vp_invoices SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param('si', $status, $id);
        return $stmt->execute();
    }

    public function deleteInvoice($id)
    {
        // Delete items first
        $sql1 = "DELETE FROM vp_invoice_items WHERE invoice_id = ?";
        $stmt1 = $this->db->prepare($sql1);
        if (!$stmt1) return false;
        $stmt1->bind_param('i', $id);
        $stmt1->execute();

        // Delete invoice
        $sql2 = "DELETE FROM vp_invoices WHERE id = ?";
        $stmt2 = $this->db->prepare($sql2);
        if (!$stmt2) return false;
        $stmt2->bind_param('i', $id);
        return $stmt2->execute();
    }
    public function getCustomerById($customer_id)
    {
        $sql = "SELECT * FROM vp_customers WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    public function getInvoiceByOrderNumber($order_number)
    {
        $sql = "SELECT * FROM vp_invoices WHERE vp_order_info_id = (SELECT id FROM vp_order_info WHERE order_number = ? LIMIT 1) LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param('s', $order_number);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    /**
     * Invoice that still blocks creating a new invoice for this order_number (excludes cancelled).
     */
    public function getActiveInvoiceForOrderNumber($order_number)
    {
        $order_number = trim((string)$order_number);
        if ($order_number === '') {
            return null;
        }
        $sql = "SELECT i.* FROM vp_invoices i
                INNER JOIN vp_invoice_items ii ON ii.invoice_id = i.id
                WHERE ii.order_number = ?
                AND LOWER(TRIM(COALESCE(i.status, ''))) <> 'cancelled'
                ORDER BY i.id DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $order_number);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    public function insert_international_invoice_data($data)
    {
        $sql = "INSERT INTO vp_invoices_international (invoice_id, transport_selection, trans_mode, veh_no, veh_type, trans_doc_no, trans_doc_dt, trans_id, trans_name, pre_carriage_by, port_of_loading, port_of_discharge, country_of_origin, country_of_final_destination, final_destination, usd_export_rate, ap_cost, freight_charge, insurance_charge, shipping_bill_number, shipping_bill_date, shipping_port, shipping_ref_clm, shipping_currency, shipping_country_code, shipping_exp_duty, irn, qrcode_string)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $shippingPort = (string)($data['shipping_port'] ?? $data['shipping_port_code'] ?? 'INABG1');
        $preCarriage = (string)($data['pre_carriage_by'] ?? 'Air');
        $portLoading = (string)($data['port_of_loading'] ?? 'New Delhi');
        $portDischarge = (string)($data['port_of_discharge'] ?? '');
        $countryOrigin = (string)($data['country_of_origin'] ?? 'India');
        $countryFinal = (string)($data['country_of_final_destination'] ?? '');
        $finalDest = (string)($data['final_destination'] ?? '');
        $usdRate = (float)($data['usd_export_rate'] ?? 0);
        $apCost = (float)($data['ap_cost'] ?? 0);
        $freightCharge = (float)($data['freight_charge'] ?? 0);
        $insuranceCharge = (float)($data['insurance_charge'] ?? 0);
        $shippingBillNo = (string)($data['shipping_bill_number'] ?? '');
        $shippingBillDate = normalize_mysql_date($data['shipping_bill_date'] ?? null) ?? date('Y-m-d');
        $shippingRefClm = (string)($data['shipping_ref_clm'] ?? 'N');
        $shippingCurrency = (string)($data['shipping_currency'] ?? 'USD');
        $shippingCountryCode = (string)($data['shipping_country_code'] ?? '');
        $shippingExpDuty = (float)($data['shipping_exp_duty'] ?? 0);
        $transportSelection = (string)($data['transport_selection'] ?? 'id');
        $transMode = trim((string)($data['trans_mode'] ?? ''));
        $vehNo = trim((string)($data['veh_no'] ?? ''));
        $vehType = trim((string)($data['veh_type'] ?? ''));
        $transDocNo = trim((string)($data['trans_doc_no'] ?? ''));
        $transDocDt = trim((string)($data['trans_doc_dt'] ?? ''));
        $transId = strtoupper(trim((string)($data['trans_id'] ?? '')));
        $transName = trim((string)($data['trans_name'] ?? ''));
        $irn = (string)($data['irn'] ?? '');
        $qrcodeString = (string)($data['qrcode_string'] ?? '');

        $stmt->bind_param(
            'issssssssssssssddddssssssdss',
            $data['invoice_id'],
            $transportSelection,
            $transMode,
            $vehNo,
            $vehType,
            $transDocNo,
            $transDocDt,
            $transId,
            $transName,
            $preCarriage,
            $portLoading,
            $portDischarge,
            $countryOrigin,
            $countryFinal,
            $finalDest,
            $usdRate,
            $apCost,
            $freightCharge,
            $insuranceCharge,
            $shippingBillNo,
            $shippingBillDate,
            $shippingPort,
            $shippingRefClm,
            $shippingCurrency,
            $shippingCountryCode,
            $shippingExpDuty,
            $irn,
            $qrcodeString
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }
    public function getInternationalInvoiceByInvoiceId($invoice_id)
    {
        $sql = "SELECT * FROM vp_invoices_international WHERE invoice_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param('i', $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    private function ensureEwbColumnsExist(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $checkRes = @$this->db->query("SHOW COLUMNS FROM vp_invoices_international LIKE 'ewb_error_message'");
        if ($checkRes && $checkRes->num_rows > 0) {
            return;
        }

        @$this->db->query("ALTER TABLE vp_invoices_international
            ADD COLUMN `ewb_no` VARCHAR(50) NULL AFTER `irn_error_message`,
            ADD COLUMN `ewb_date` DATETIME NULL AFTER `ewb_no`,
            ADD COLUMN `ewb_valid_till` DATETIME NULL AFTER `ewb_date`,
            ADD COLUMN `ewb_request_payload` LONGTEXT NULL,
            ADD COLUMN `ewb_response_payload` LONGTEXT NULL,
            ADD COLUMN `ewb_error_message` LONGTEXT NULL");
    }

    public function updateInvoiceInternational($invoice_id, $data)
    {
        $this->ensureEwbColumnsExist();
        // Build dynamic UPDATE query based on provided fields
        $allowedFields = ['transport_selection', 'trans_mode', 'veh_no', 'veh_type', 'trans_doc_no', 'trans_doc_dt', 'trans_id', 'trans_name', 'pre_carriage_by', 'port_of_loading', 'port_of_discharge', 'port_code', 'country_of_origin', 'country_of_final_destination', 'final_destination', 'usd_export_rate', 'ap_cost', 'freight_charge', 'insurance_charge', 'shipping_bill_number', 'shipping_bill_date', 'shipping_port', 'shipping_ref_clm', 'shipping_currency', 'shipping_country_code', 'shipping_exp_duty', 'irn', 'ack_number', 'ack_date', 'signed_invoice', 'qrcode_string', 'irn_status', 'request_payload', 'response_payload', 'irn_error_message', 'ewb_no', 'ewb_date', 'ewb_valid_till', 'ewb_request_payload', 'ewb_response_payload', 'ewb_error_message'];
        $updateFields = [];
        $bindParams = [];
        $bindTypes = '';

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $val = $data[$field];
                if ($field === 'shipping_bill_date' && $val !== null) {
                    $val = normalize_mysql_date((string)$val) ?? date('Y-m-d');
                }
                $updateFields[] = "$field = ?";
                $bindParams[] = $val;
                $bindTypes .= 's'; // All fields treated as strings
            }
        }

        if (empty($updateFields)) {
            return false; // No fields to update
        }

        // Add invoice_id as last parameter
        $bindParams[] = $invoice_id;
        $bindTypes .= 'i'; // invoice_id is integer

        $sql = "UPDATE vp_invoices_international SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE invoice_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        // Dynamically bind parameters
        $stmt->bind_param($bindTypes, ...$bindParams);
        return $stmt->execute();
    }
    private function buildInvoiceWhereClause($filters = [])
    {
        $whereClause = [];

        if (isset($filters['customer_name']) && $filters['customer_name'] !== '') {
            $whereClause[] = "c.name LIKE '%" . $this->db->real_escape_string($filters['customer_name']) . "%'";
        }
        if (isset($filters['start_date']) && $filters['start_date'] !== '') {
            $whereClause[] = "i.invoice_date >= '" . $this->db->real_escape_string($filters['start_date']) . "'";
        }
        if (isset($filters['end_date']) && $filters['end_date'] !== '') {
            $whereClause[] = "i.invoice_date <= '" . $this->db->real_escape_string($filters['end_date']) . "'";
        }
        if (isset($filters['invoice_number']) && $filters['invoice_number'] !== '') {
            $whereClause[] = "i.invoice_number LIKE '%" . $this->db->real_escape_string($filters['invoice_number']) . "%'";
        }

        // dispatch-table & multi-field filters
        if (isset($filters['awb_number']) && $filters['awb_number'] !== '') {
            $escapedAwb = $this->db->real_escape_string($filters['awb_number']);
            $whereClause[] = "(d.awb_code LIKE '%" . $escapedAwb . "%' OR d.tracking_url LIKE '%" . $escapedAwb . "%')";
        }
        if (isset($filters['order_number']) && $filters['order_number'] !== '') {
            $escapedOrder = $this->db->real_escape_string($filters['order_number']);
            $whereClause[] = "(d.order_number LIKE '%" . $escapedOrder . "%' OR i.id IN (SELECT invoice_id FROM vp_invoice_items WHERE order_number LIKE '%" . $escapedOrder . "%') OR i.vp_order_info_id IN (SELECT id FROM vp_order_info WHERE order_number LIKE '%" . $escapedOrder . "%'))";
        }
        if (isset($filters['box_size']) && $filters['box_size'] !== '') {
            if ($filters['box_size'] === 'R-1') {
                $whereClause[] = "d.length >= 22 AND d.width >= 17 AND d.height >= 5";
            } elseif ($filters['box_size'] === 'R-2') {
                $whereClause[] = "d.length >= 16 AND d.width >= 13 AND d.height >= 13";
            } elseif ($filters['box_size'] === 'R-3') {
                $whereClause[] = "d.length >= 16 AND d.width >= 11 AND d.height >= 7";
            } elseif ($filters['box_size'] === 'R-4') {
                $whereClause[] = "d.length >= 13 AND d.width >= 10 AND d.height >= 7";
            } elseif ($filters['box_size'] === 'R-5') {
                $whereClause[] = "d.length >= 13 AND d.width >= 10 AND d.height >= 4";
            } elseif ($filters['box_size'] === 'R-6') {
                $whereClause[] = "d.length >= 11 AND d.width >= 9 AND d.height >= 6";
            } elseif ($filters['box_size'] === 'R-7') {
                $whereClause[] = "d.length >= 11 AND d.width >= 9 AND d.height >= 4";
            } elseif ($filters['box_size'] === 'R-8') {
                $whereClause[] = "d.length >= 10 AND d.width >= 8 AND d.height >= 5";
            } elseif ($filters['box_size'] === 'R-9') {
                $whereClause[] = "d.length >= 10 AND d.width >= 8 AND d.height >= 4";
            } elseif ($filters['box_size'] === 'R-10') {
                $whereClause[] = "d.length >= 9 AND d.width >= 7 AND d.height >= 5";
            } elseif ($filters['box_size'] === 'R-11') {
                $whereClause[] = "d.length >= 9 AND d.width >= 7 AND d.height >= 4";
            } elseif ($filters['box_size'] === 'R-12') {
                $whereClause[] = "d.length >= 8 AND d.width >= 6 AND d.height >= 4";
            } elseif ($filters['box_size'] === 'R-13') {
                $whereClause[] = "d.length >= 7 AND d.width >= 5 AND d.height >= 3";
            } elseif ($filters['box_size'] === 'R-14') {
                $whereClause[] = "d.length >= 14 AND d.width >= 12 AND d.height >= 10";
            }
        }

        if (isset($filters['customer_contact']) && $filters['customer_contact'] !== '') {
            $whereClause[] = "c.phone LIKE '%" . $this->db->real_escape_string($filters['customer_contact']) . "%'";
        }
        if (isset($filters['payment_mode']) && $filters['payment_mode'] !== '') {
            $whereClause[] = "i.payment_mode = '" . $this->db->real_escape_string($filters['payment_mode']) . "'";
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $whereClause[] = "d.shipment_status = '" . $this->db->real_escape_string($filters['status']) . "'";
        }
        if (isset($filters['category']) && $filters['category'] !== '') {
            $whereClause[] = "d.groupname LIKE '%" . $this->db->real_escape_string($filters['category']) . "%'";
        }
        if (isset($filters['invoice_value_min']) && is_numeric($filters['invoice_value_min'])) {
            $whereClause[] = "i.total_amount >= " . floatval($filters['invoice_value_min']);
        }
        if (isset($filters['invoice_value_max']) && is_numeric($filters['invoice_value_max'])) {
            $whereClause[] = "i.total_amount <= " . floatval($filters['invoice_value_max']);
        }
        if (isset($filters['batch_no']) && $filters['batch_no'] !== '') {
            $whereClause[] = "i.batch_no = '" . $this->db->real_escape_string($filters['batch_no']) . "'";
        }
        if (isset($filters['item_code']) && $filters['item_code'] !== '') {
            $whereClause[] = "i.id IN (SELECT invoice_id FROM vp_invoice_items WHERE item_code LIKE '%" . $this->db->real_escape_string($filters['item_code']) . "%')";
        }
        if (isset($filters['created_by']) && $filters['created_by'] !== '') {
            $whereClause[] = "d.created_by = " . intval($filters['created_by']);
        }
        if (isset($filters['item_name']) && $filters['item_name'] !== '') {
            $whereClause[] = "i.id IN (SELECT invoice_id FROM vp_invoice_items WHERE item_name LIKE '%" . $this->db->real_escape_string($filters['item_name']) . "%')";
        }
        if (isset($filters['box_weight_min']) && is_numeric($filters['box_weight_min'])) {
            $whereClause[] = "i.id IN (SELECT invoice_id FROM vp_dispatch_details WHERE weight >= " . floatval($filters['box_weight_min']) . ")";
        }
        if (isset($filters['box_weight_max']) && is_numeric($filters['box_weight_max'])) {
            $whereClause[] = "i.id IN (SELECT invoice_id FROM vp_dispatch_details WHERE weight <= " . floatval($filters['box_weight_max']) . ")";
        }

        return $whereClause;
    }

    public function getInvoicesCount($filters = [])
    {
        $sql = "SELECT COUNT(DISTINCT i.id) AS cnt
                FROM vp_invoices i
                LEFT JOIN vp_customers c ON i.customer_id = c.id
                LEFT JOIN vp_dispatch_details d ON d.invoice_id = i.id ";

        $whereClause = $this->buildInvoiceWhereClause($filters);
        if (!empty($whereClause)) {
            $sql .= "WHERE " . implode(" AND ", $whereClause);
        }

        $result = $this->db->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            return isset($row['cnt']) ? (int)$row['cnt'] : 0;
        }
        return 0;
    }

    public function  getAllInvoicesPaginated($limit, $offset, $filters = [])
    {
        // join dispatch details and vp_order_info so we can get order info total
        $sql  = "SELECT DISTINCT i.*, c.id AS customer_id, c.name, c.email, c.phone, oi.total AS order_info_total
                FROM vp_invoices i
                LEFT JOIN vp_customers c ON i.customer_id = c.id
                LEFT JOIN vp_dispatch_details d ON d.invoice_id = i.id
                LEFT JOIN vp_order_info oi ON oi.id = i.vp_order_info_id ";

        $whereClause = $this->buildInvoiceWhereClause($filters);


        if (!empty($whereClause)) {
            $sql .= "WHERE " . implode(" AND ", $whereClause) . " ";
        }

        if (isset($filters['sort']) && in_array($filters['sort'], ['asc', 'desc'])) {
            $sql .= "ORDER BY i.id " . (($filters['sort'] === 'asc') ? 'ASC' : 'DESC') . " ";
        } else {
            $sql .= "ORDER BY i.id DESC ";
        }

        $sql .= "LIMIT $limit OFFSET $offset";

        //echo $sql; // debug if needed
        $result = $this->db->query($sql);
        $invoices = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $invoices[] = $row;
            }
        }
        return $invoices;
    }
    public function getInvoiceItemsByInvoiceIds($invoiceIds)
    {
        if (empty($invoiceIds)) {
            return [];
        }
        $idsString = implode(',', array_map('intval', $invoiceIds));
        $sql = "SELECT * FROM vp_invoice_items WHERE invoice_id IN ($idsString)";
        $result = $this->db->query($sql);
        $items = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $invoiceId = (int)($row['invoice_id'] ?? 0);
                if ($invoiceId <= 0) {
                    continue;
                }
                if (!isset($items[$invoiceId])) {
                    $items[$invoiceId] = [];
                }
                $items[$invoiceId][] = $row;
            }
        }
        return $items;
    }

    public function updateInvoiceNotes(int $invoiceId, string $notesJson): bool
    {
        $stmt = $this->db->prepare('UPDATE vp_invoices SET notes = ? WHERE id = ?');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('si', $notesJson, $invoiceId);
        $ok = $stmt->execute();
        $stmt->close();

        return (bool)$ok;
    }
}
