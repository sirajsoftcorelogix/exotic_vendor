<?php

class POSInvoice
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
        $pos_flag = (int)($data['pos_flag'] ?? 1);
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
        $sql = "INSERT INTO vp_invoices_international (invoice_id, pre_carriage_by, port_of_loading, port_of_discharge, country_of_origin, country_of_final_destination, final_destination, usd_export_rate, ap_cost, freight_charge, insurance_charge, irn, qrcode_string) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param(
            'issssssddddss',
            $data['invoice_id'],
            $data['pre_carriage_by'],
            $data['port_of_loading'],
            $data['port_of_discharge'],
            $data['country_of_origin'],
            $data['country_of_final_destination'],
            $data['final_destination'],
            $data['usd_export_rate'],
            $data['ap_cost'],
            $data['freight_charge'],
            $data['insurance_charge'],
            $data['irn'],
            $data['qrcode_string']
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
    public function updateInvoice($invoice_id, $data)
    {
        $sql = "UPDATE vp_invoices_international SET irn = ?, ack_number = ?, ack_date = ?, signed_invoice = ?, qrcode_string = ?, irn_status = ?, updated_at = NOW() WHERE invoice_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param(
            'sisssdddsdssi',
            $data['irn'],
            $data['ack_number'],
            $data['ack_date'],
            $data['signed_invoice'],
            $data['qrcode_string'],
            $data['irn_status'],
            $invoice_id
        );

        return $stmt->execute();
    }
    public function getInvoicesCount()
    {
        $sql = "SELECT COUNT(*) AS cnt FROM vp_invoices";
        $result = $this->db->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            return isset($row['cnt']) ? (int)$row['cnt'] : 0;
        }
        return 0;
    }
    public function  getAllInvoicesPaginated($limit, $offset, $filters = [])
    {
        // join dispatch details so we can filter on its columns
        $sql  = "SELECT DISTINCT i.*, c.id AS customer_id, c.name, c.email, c.phone
                FROM vp_invoices i
                LEFT JOIN vp_customers c ON i.customer_id = c.id
                LEFT JOIN vp_dispatch_details d ON d.invoice_id = i.id ";
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

        // dispatch‑table filters
        if (isset($filters['awb_number']) && $filters['awb_number'] !== '') {
            $whereClause[] = "d.awb_code LIKE '%" . $this->db->real_escape_string($filters['awb_number']) . "%'";
        }
        if (isset($filters['order_number']) && $filters['order_number'] !== '') {
            $whereClause[] = "d.order_number LIKE '%" . $this->db->real_escape_string($filters['order_number']) . "%'";
        }
        if (isset($filters['box_size']) && $filters['box_size'] !== '') {
            if ($filters['box_size'] === 'R-1') {
                $whereClause[] = "d.length >= 22 AND d.width >= 17 AND d.height >= 5";
                //$whereClause[] = "d.box_size NOT IN ('R-1', 'R-2', 'R-3', 'R-4', 'R-5', 'R-6', 'R-7', 'R-8', 'R-9', 'R-10', 'R-11', 'R-12', 'R-13', 'R-14')";
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
            } else {
                // custom size filter
                // expected format: LxWxH (e.g. 20x15x10)
                // $parts = explode('x', $filters['box_size']);
                // if (count($parts) == 3) {
                //     $length = (int)$parts[0];
                //     $width = (int)$parts[1];
                //     $height = (int)$parts[2];

                //     $whereClause[] = "d.length >= $length AND d.width >= $width AND d.height >= $height";
                // }
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
        //Box Weight
        if (isset($filters['box_weight_min']) && is_numeric($filters['box_weight_min'])) {
            $whereClause[] = "i.id IN (SELECT invoice_id FROM vp_dispatch_details WHERE weight >= " . floatval($filters['box_weight_min']) . ")";
        }
        if (isset($filters['box_weight_max']) && is_numeric($filters['box_weight_max'])) {
            $whereClause[] = "i.id IN (SELECT invoice_id FROM vp_dispatch_details WHERE weight <= " . floatval($filters['box_weight_max']) . ")";
        }


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

    private function posInvoiceNotesDiscountSumSql(): string
    {
        return "(
            IFNULL(CAST(JSON_UNQUOTE(JSON_EXTRACT(i.notes, '$.pos_discounts.coupon_discount')) AS DECIMAL(15,2)), 0)
            + IFNULL(CAST(JSON_UNQUOTE(JSON_EXTRACT(i.notes, '$.pos_discounts.cash_discount')) AS DECIMAL(15,2)), 0)
            + IFNULL(CAST(JSON_UNQUOTE(JSON_EXTRACT(i.notes, '$.pos_discounts.gift_discount')) AS DECIMAL(15,2)), 0)
            + IFNULL(CAST(JSON_UNQUOTE(JSON_EXTRACT(i.notes, '$.pos_discounts.line_discount')) AS DECIMAL(15,2)), 0)
        )";
    }

    /**
     * Net payable total for a POS invoice row.
     * Invoice notes grand_total is authoritative for discounted POS checkout orders.
     */
    private function posInvoicePayableAmountSql(): string
    {
        $discountSum = $this->posInvoiceNotesDiscountSumSql();
        $grandTotal = "NULLIF(CAST(JSON_UNQUOTE(JSON_EXTRACT(i.notes, '$.pos_discounts.grand_total')) AS DECIMAL(15,2)), 0)";
        $subtotalGoods = "NULLIF(CAST(JSON_UNQUOTE(JSON_EXTRACT(i.notes, '$.pos_discounts.subtotal_goods')) AS DECIMAL(15,2)), 0)";

        return "COALESCE(
            {$grandTotal},
            NULLIF(GREATEST(0, ROUND({$subtotalGoods} - {$discountSum}, 2)), 0),
            NULLIF(GREATEST(0, ROUND(i.total_amount - {$discountSum}, 2)), 0),
            NULLIF(GREATEST(0, ROUND(i.total_amount - IFNULL(i.discount_amount, 0), 2)), 0),
            (
                SELECT MAX(pp2.order_amount)
                FROM pos_payments pp2
                WHERE CONVERT(pp2.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci =
                      CONVERT(o.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci
                  AND pp2.order_amount > 0
            ),
            NULLIF(GREATEST(0, ROUND(NULLIF(o.total, 0) - {$discountSum}, 2)), 0),
            NULLIF(o.total, 0),
            i.total_amount
        )";
    }

    /**
     * POS checkout discount total from notes, legacy column, or gross-minus-payable fallback.
     */
    private function posInvoiceDiscountAmountSql(string $payableSql): string
    {
        $discountSum = $this->posInvoiceNotesDiscountSumSql();
        $subtotalGoods = "NULLIF(CAST(JSON_UNQUOTE(JSON_EXTRACT(i.notes, '$.pos_discounts.subtotal_goods')) AS DECIMAL(15,2)), 0)";

        return "GREATEST(0, ROUND(
            CASE
                WHEN {$discountSum} > 0.001 THEN {$discountSum}
                WHEN IFNULL(i.discount_amount, 0) > 0 THEN i.discount_amount
                WHEN {$subtotalGoods} > ({$payableSql}) + 0.001 THEN GREATEST(0, ROUND({$subtotalGoods} - ({$payableSql}), 2))
                ELSE GREATEST(0, i.total_amount - ({$payableSql}))
            END,
        2))";
    }

    private function posInvoicePaidAmountSql(): string
    {
        return "IFNULL((
            SELECT SUM(pp.payment_amount)
            FROM pos_payments pp
            WHERE CONVERT(pp.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci =
                  CONVERT(o.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci
        ), 0)";
    }

    /** Primary POS checkout mode from pos_payments (not vp_order_info.payment_type, which is always offline). */
    private function posInvoicePrimaryPaymentModeSql(): string
    {
        return "(SELECT IFNULL(pp_mode.payment_mode, '')
            FROM pos_payments pp_mode
            WHERE CONVERT(pp_mode.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci =
                  CONVERT(o.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci
            ORDER BY pp_mode.id ASC
            LIMIT 1)";
    }

    /**
     * @return list<string>
     */
    private function posInvoicePaymentModesForFilterType(string $type): array
    {
        $key = strtolower(trim($type));

        return match ($key) {
            'cod', 'cash' => ['cash', 'cod'],
            'offline' => ['offline'],
            'razorpay' => ['razorpay'],
            'bank_transfer' => ['bank_transfer'],
            'upi' => ['upi'],
            'pos_machine' => ['pos_machine'],
            'cheque' => ['cheque'],
            default => $key !== '' ? [$key] : [],
        };
    }

    /**
     * POS invoice AJAX list with payment pending amounts.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function searchPosListAjax(array $filters): array
    {
        $payableSql = $this->posInvoicePayableAmountSql();
        $paidSql = $this->posInvoicePaidAmountSql();
        $discountSql = $this->posInvoiceDiscountAmountSql($payableSql);

        $sql = "
            SELECT
                i.id,
                i.invoice_number,
                i.invoice_date,
                i.status,
                i.total_amount,
                i.warehouse_id,
                o.order_number,
                {$this->posInvoicePrimaryPaymentModeSql()} AS payment_type,
                c.name AS customer_name,
                o.state AS customer_billing_state,
                COALESCE(cnt.name, o.country) AS customer_billing_country,
                COALESCE(ea.address_title, CONCAT('Warehouse #', i.warehouse_id)) AS warehouse_name,
                ROUND({$payableSql}, 2) AS payable_amount,
                {$discountSql} AS discount_amount,
                {$paidSql} AS paid_amount,
                GREATEST(0, ROUND({$payableSql} - {$paidSql}, 2)) AS pending_amount
            FROM vp_invoices i
            LEFT JOIN vp_order_info o ON o.id = i.vp_order_info_id
            LEFT JOIN vp_customers c ON c.id = i.customer_id
            LEFT JOIN countries cnt ON CONVERT(UPPER(cnt.country_code) USING utf8mb4) COLLATE utf8mb4_unicode_ci
                = CONVERT(UPPER(o.country) USING utf8mb4) COLLATE utf8mb4_unicode_ci
            LEFT JOIN exotic_address ea ON ea.id = i.warehouse_id
            WHERE i.pos_flag = 1
        ";

        $this->appendPosInvoiceListFiltersSql($sql, $filters, $payableSql, $discountSql);

        $sql .= ' ORDER BY i.id DESC';

        $res = $this->db->query($sql);
        if (!$res) {
            return [];
        }

        $data = [];
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * POS sales totals grouped by store / warehouse.
     *
     * @param array<string, mixed> $filters
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, float|int>}
     */
    public function searchPosSalesSummaryByStore(array $filters): array
    {
        $payableSql = $this->posInvoicePayableAmountSql();
        $paidSql = $this->posInvoicePaidAmountSql();
        $discountSql = $this->posInvoiceDiscountAmountSql($payableSql);
        $pendingExpr = "GREATEST(0, ROUND({$payableSql} - {$paidSql}, 2))";

        $sql = "
            SELECT
                i.warehouse_id,
                COALESCE(ea.address_title, CONCAT('Warehouse #', i.warehouse_id)) AS warehouse_name,
                COUNT(*) AS invoice_count,
                ROUND(SUM({$payableSql}), 2) AS net_sales,
                ROUND(SUM({$discountSql}), 2) AS discount_total,
                ROUND(SUM({$paidSql}), 2) AS collected_total,
                ROUND(SUM({$pendingExpr}), 2) AS pending_total,
                ROUND(SUM(i.total_amount), 2) AS gross_total
            FROM vp_invoices i
            LEFT JOIN vp_order_info o ON o.id = i.vp_order_info_id
            LEFT JOIN exotic_address ea ON ea.id = i.warehouse_id
            WHERE i.pos_flag = 1
        ";

        $this->appendPosInvoiceListFiltersSql($sql, $filters, $payableSql, $discountSql);

        $sql .= "
            GROUP BY i.warehouse_id, warehouse_name
            ORDER BY warehouse_name ASC
        ";

        $res = $this->db->query($sql);
        if (!$res) {
            return [
                'rows' => [],
                'totals' => $this->emptyPosSalesSummaryTotals(),
            ];
        }

        $rows = [];
        $totals = $this->emptyPosSalesSummaryTotals();
        while ($row = $res->fetch_assoc()) {
            $invoiceCount = (int) ($row['invoice_count'] ?? 0);
            $netSales = round((float) ($row['net_sales'] ?? 0), 2);
            $row['invoice_count'] = $invoiceCount;
            $row['net_sales'] = $netSales;
            $row['discount_total'] = round((float) ($row['discount_total'] ?? 0), 2);
            $row['collected_total'] = round((float) ($row['collected_total'] ?? 0), 2);
            $row['pending_total'] = round((float) ($row['pending_total'] ?? 0), 2);
            $row['gross_total'] = round((float) ($row['gross_total'] ?? 0), 2);
            $row['avg_ticket'] = $invoiceCount > 0 ? round($netSales / $invoiceCount, 2) : 0.0;
            $rows[] = $row;

            $totals['invoice_count'] += $invoiceCount;
            $totals['net_sales'] += $row['net_sales'];
            $totals['discount_total'] += $row['discount_total'];
            $totals['collected_total'] += $row['collected_total'];
            $totals['pending_total'] += $row['pending_total'];
            $totals['gross_total'] += $row['gross_total'];
        }

        foreach (['net_sales', 'discount_total', 'collected_total', 'pending_total', 'gross_total'] as $key) {
            $totals[$key] = round((float) $totals[$key], 2);
        }
        $totals['avg_ticket'] = $totals['invoice_count'] > 0
            ? round($totals['net_sales'] / $totals['invoice_count'], 2)
            : 0.0;

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * POS sales totals for one store, grouped by invoice date.
     *
     * @param array<string, mixed> $filters warehouse_id required
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, float|int>, warehouse_id: int, warehouse_name: string}
     */
    public function searchPosSalesSummaryByDate(array $filters): array
    {
        $warehouseId = (int) ($filters['warehouse_id'] ?? 0);
        if ($warehouseId <= 0) {
            return [
                'rows' => [],
                'totals' => $this->emptyPosSalesSummaryTotals(),
                'warehouse_id' => 0,
                'warehouse_name' => '',
            ];
        }

        $filters['warehouse_id'] = $warehouseId;

        $payableSql = $this->posInvoicePayableAmountSql();
        $paidSql = $this->posInvoicePaidAmountSql();
        $discountSql = $this->posInvoiceDiscountAmountSql($payableSql);
        $pendingExpr = "GREATEST(0, ROUND({$payableSql} - {$paidSql}, 2))";

        $sql = "
            SELECT
                i.invoice_date AS summary_date,
                COALESCE(ea.address_title, CONCAT('Warehouse #', i.warehouse_id)) AS warehouse_name,
                COUNT(*) AS invoice_count,
                ROUND(SUM({$payableSql}), 2) AS net_sales,
                ROUND(SUM({$discountSql}), 2) AS discount_total,
                ROUND(SUM({$paidSql}), 2) AS collected_total,
                ROUND(SUM({$pendingExpr}), 2) AS pending_total,
                ROUND(SUM(i.total_amount), 2) AS gross_total
            FROM vp_invoices i
            LEFT JOIN vp_order_info o ON o.id = i.vp_order_info_id
            LEFT JOIN exotic_address ea ON ea.id = i.warehouse_id
            WHERE i.pos_flag = 1
        ";

        $this->appendPosInvoiceListFiltersSql($sql, $filters, $payableSql, $discountSql);

        $sql .= "
            GROUP BY i.invoice_date, warehouse_name
            ORDER BY i.invoice_date DESC
        ";

        $res = $this->db->query($sql);
        if (!$res) {
            return [
                'rows' => [],
                'totals' => $this->emptyPosSalesSummaryTotals(),
                'warehouse_id' => $warehouseId,
                'warehouse_name' => '',
            ];
        }

        $rows = [];
        $totals = $this->emptyPosSalesSummaryTotals();
        $warehouseName = '';
        while ($row = $res->fetch_assoc()) {
            if ($warehouseName === '') {
                $warehouseName = trim((string) ($row['warehouse_name'] ?? ''));
            }

            $invoiceCount = (int) ($row['invoice_count'] ?? 0);
            $netSales = round((float) ($row['net_sales'] ?? 0), 2);
            $row['summary_date'] = (string) ($row['summary_date'] ?? '');
            $row['invoice_count'] = $invoiceCount;
            $row['net_sales'] = $netSales;
            $row['discount_total'] = round((float) ($row['discount_total'] ?? 0), 2);
            $row['collected_total'] = round((float) ($row['collected_total'] ?? 0), 2);
            $row['pending_total'] = round((float) ($row['pending_total'] ?? 0), 2);
            $row['gross_total'] = round((float) ($row['gross_total'] ?? 0), 2);
            $row['avg_ticket'] = $invoiceCount > 0 ? round($netSales / $invoiceCount, 2) : 0.0;
            $rows[] = $row;

            $totals['invoice_count'] += $invoiceCount;
            $totals['net_sales'] += $row['net_sales'];
            $totals['discount_total'] += $row['discount_total'];
            $totals['collected_total'] += $row['collected_total'];
            $totals['pending_total'] += $row['pending_total'];
            $totals['gross_total'] += $row['gross_total'];
        }

        foreach (['net_sales', 'discount_total', 'collected_total', 'pending_total', 'gross_total'] as $key) {
            $totals[$key] = round((float) $totals[$key], 2);
        }
        $totals['avg_ticket'] = $totals['invoice_count'] > 0
            ? round($totals['net_sales'] / $totals['invoice_count'], 2)
            : 0.0;

        if ($warehouseName === '') {
            $warehouseName = 'Warehouse #' . $warehouseId;
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $warehouseName,
        ];
    }

    /**
     * Full store-level sales summary: overview + breakdowns (not invoice rows).
     *
     * @param array<string, mixed> $filters warehouse_id required
     * @return array<string, mixed>
     */
    public function searchPosSalesStoreDetailSummary(array $filters): array
    {
        $warehouseId = (int) ($filters['warehouse_id'] ?? 0);
        if ($warehouseId <= 0) {
            return [
                'warehouse_id' => 0,
                'warehouse_name' => '',
                'overview' => $this->emptyPosSalesSummaryTotals(),
                'by_payment_type' => ['rows' => [], 'totals' => $this->emptyPosSalesSummaryTotals()],
                'by_status' => ['rows' => [], 'totals' => $this->emptyPosSalesSummaryTotals()],
                'by_discount' => ['rows' => [], 'totals' => $this->emptyPosSalesSummaryTotals()],
                'by_date' => ['rows' => [], 'totals' => $this->emptyPosSalesSummaryTotals()],
            ];
        }

        $filters['warehouse_id'] = $warehouseId;
        $byDate = $this->searchPosSalesSummaryByDate($filters);

        return [
            'warehouse_id' => $warehouseId,
            'warehouse_name' => (string) ($byDate['warehouse_name'] ?? ('Warehouse #' . $warehouseId)),
            'overview' => $byDate['totals'] ?? $this->emptyPosSalesSummaryTotals(),
            'by_payment_type' => $this->searchPosSalesSummaryGrouped($filters, 'payment_type'),
            'by_status' => $this->searchPosSalesSummaryGrouped($filters, 'status'),
            'by_discount' => $this->searchPosSalesSummaryGrouped($filters, 'discount'),
            'by_date' => [
                'rows' => $byDate['rows'] ?? [],
                'totals' => $byDate['totals'] ?? $this->emptyPosSalesSummaryTotals(),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, float|int>}
     */
    private function searchPosSalesSummaryGrouped(array $filters, string $dimension): array
    {
        $warehouseId = (int) ($filters['warehouse_id'] ?? 0);
        if ($warehouseId <= 0) {
            return ['rows' => [], 'totals' => $this->emptyPosSalesSummaryTotals()];
        }

        $payableSql = $this->posInvoicePayableAmountSql();
        $paidSql = $this->posInvoicePaidAmountSql();
        $discountSql = $this->posInvoiceDiscountAmountSql($payableSql);
        $pendingExpr = "GREATEST(0, ROUND({$payableSql} - {$paidSql}, 2))";

        $groupExpr = match ($dimension) {
            'payment_type' => $this->posInvoicePrimaryPaymentModeSql(),
            'status' => "IFNULL(i.status, '')",
            'discount' => "CASE WHEN ({$discountSql}) > 0.001 THEN '1' ELSE '0' END",
            default => "''",
        };

        $orderBy = match ($dimension) {
            'payment_type' => 'group_key ASC',
            'status' => 'group_key ASC',
            'discount' => "group_key DESC",
            default => 'group_key ASC',
        };

        $sql = "
            SELECT
                {$groupExpr} AS group_key,
                COUNT(*) AS invoice_count,
                ROUND(SUM({$payableSql}), 2) AS net_sales,
                ROUND(SUM({$discountSql}), 2) AS discount_total,
                ROUND(SUM({$paidSql}), 2) AS collected_total,
                ROUND(SUM({$pendingExpr}), 2) AS pending_total,
                ROUND(SUM(i.total_amount), 2) AS gross_total
            FROM vp_invoices i
            LEFT JOIN vp_order_info o ON o.id = i.vp_order_info_id
            WHERE i.pos_flag = 1
        ";

        $this->appendPosInvoiceListFiltersSql($sql, $filters, $payableSql, $discountSql);

        $sql .= "
            GROUP BY group_key
            ORDER BY {$orderBy}
        ";

        $res = $this->db->query($sql);
        if (!$res) {
            return ['rows' => [], 'totals' => $this->emptyPosSalesSummaryTotals()];
        }

        return $this->mapPosSalesAggregateRows($res);
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, float|int>}
     */
    private function mapPosSalesAggregateRows(\mysqli_result $res): array
    {
        $rows = [];
        $totals = $this->emptyPosSalesSummaryTotals();

        while ($row = $res->fetch_assoc()) {
            $invoiceCount = (int) ($row['invoice_count'] ?? 0);
            $netSales = round((float) ($row['net_sales'] ?? 0), 2);
            $mapped = [
                'group_key' => (string) ($row['group_key'] ?? ''),
                'invoice_count' => $invoiceCount,
                'net_sales' => $netSales,
                'discount_total' => round((float) ($row['discount_total'] ?? 0), 2),
                'collected_total' => round((float) ($row['collected_total'] ?? 0), 2),
                'pending_total' => round((float) ($row['pending_total'] ?? 0), 2),
                'gross_total' => round((float) ($row['gross_total'] ?? 0), 2),
                'avg_ticket' => $invoiceCount > 0 ? round($netSales / $invoiceCount, 2) : 0.0,
            ];
            $rows[] = $mapped;

            $totals['invoice_count'] += $invoiceCount;
            $totals['net_sales'] += $mapped['net_sales'];
            $totals['discount_total'] += $mapped['discount_total'];
            $totals['collected_total'] += $mapped['collected_total'];
            $totals['pending_total'] += $mapped['pending_total'];
            $totals['gross_total'] += $mapped['gross_total'];
        }

        foreach (['net_sales', 'discount_total', 'collected_total', 'pending_total', 'gross_total'] as $key) {
            $totals[$key] = round((float) $totals[$key], 2);
        }
        $totals['avg_ticket'] = $totals['invoice_count'] > 0
            ? round($totals['net_sales'] / $totals['invoice_count'], 2)
            : 0.0;

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    /** @return array<string, float|int> */
    private function emptyPosSalesSummaryTotals(): array
    {
        return [
            'invoice_count' => 0,
            'net_sales' => 0.0,
            'discount_total' => 0.0,
            'collected_total' => 0.0,
            'pending_total' => 0.0,
            'gross_total' => 0.0,
            'avg_ticket' => 0.0,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function appendPosInvoiceListFiltersSql(string &$sql, array $filters, string $payableSql, string $discountSql): void
    {
        if (isset($filters['warehouse_id']) && $filters['warehouse_id'] !== null && $filters['warehouse_id'] !== '') {
            $sql .= ' AND i.warehouse_id = ' . (int) $filters['warehouse_id'];
        }

        if (!empty($filters['order_number'])) {
            $sql .= " AND o.order_number LIKE '%" . $this->db->real_escape_string((string) $filters['order_number']) . "%'";
        }

        if (!empty($filters['invoice_number'])) {
            $sql .= " AND i.invoice_number LIKE '%" . $this->db->real_escape_string((string) $filters['invoice_number']) . "%'";
        }

        if (!empty($filters['status'])) {
            $sql .= " AND i.status = '" . $this->db->real_escape_string((string) $filters['status']) . "'";
        }

        if (!empty($filters['from_date'])) {
            $sql .= " AND i.invoice_date >= '" . $this->db->real_escape_string((string) $filters['from_date']) . "'";
        }

        if (!empty($filters['to_date'])) {
            $sql .= " AND i.invoice_date <= '" . $this->db->real_escape_string((string) $filters['to_date']) . "'";
        }

        if (!empty($filters['type'])) {
            $modes = $this->posInvoicePaymentModesForFilterType((string) $filters['type']);
            if ($modes !== []) {
                $escapedModes = array_map(
                    fn(string $mode): string => "'" . $this->db->real_escape_string($mode) . "'",
                    $modes
                );
                $sql .= ' AND ' . $this->posInvoicePrimaryPaymentModeSql() . ' IN (' . implode(', ', $escapedModes) . ')';
            }
        }

        if (!empty($filters['customer_id'])) {
            $sql .= ' AND i.customer_id = ' . (int) $filters['customer_id'];
        }

        if (!empty($filters['amount_min'])) {
            $sql .= ' AND ROUND(' . $payableSql . ', 2) >= ' . (float) $filters['amount_min'];
        }

        if (!empty($filters['amount_max'])) {
            $sql .= ' AND ROUND(' . $payableSql . ', 2) <= ' . (float) $filters['amount_max'];
        }

        if (isset($filters['discount_applied']) && $filters['discount_applied'] !== '') {
            if ((string) $filters['discount_applied'] === '1') {
                $sql .= " AND ({$discountSql}) > 0.001";
            } elseif ((string) $filters['discount_applied'] === '0') {
                $sql .= " AND ({$discountSql}) <= 0.001";
            }
        }
    }

    /**
     * @return array<int, string>
     */
    public function getDistinctOrderNumbersForInvoice(int $invoiceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT order_number FROM vp_invoice_items
             WHERE invoice_id = ? AND order_number IS NOT NULL AND TRIM(order_number) != ''"
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $invoiceId);
        $stmt->execute();
        $res = $stmt->get_result();
        $orderNumbers = [];
        while ($row = $res->fetch_assoc()) {
            $on = trim((string)($row['order_number'] ?? ''));
            if ($on !== '') {
                $orderNumbers[] = $on;
            }
        }
        $stmt->close();

        return array_values(array_unique($orderNumbers));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getOrderLinesForCancelSync(string $orderNumber): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, item_code, size, color FROM vp_orders WHERE order_number = ? ORDER BY id ASC'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $lines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
        $stmt->close();

        return $lines;
    }

    public function cancelLinkedOrderLines(string $orderNumber, int $invoiceId): int
    {
        $stmt = $this->db->prepare(
            "UPDATE vp_orders SET status = 'cancelled', invoice_id = NULL WHERE order_number = ? AND invoice_id = ?"
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('si', $orderNumber, $invoiceId);
        $stmt->execute();
        $affected = (int)$stmt->affected_rows;
        $stmt->close();

        return $affected;
    }

    /**
     * Legacy preview table used by older POS invoice preview.
     *
     * @return mysqli_result|false
     */
    public function getLegacyPreviewItemsResult(int $invoiceId)
    {
        $invoiceId = (int)$invoiceId;

        return $this->db->query(
            'SELECT * FROM invoice_items WHERE invoice_id = ' . $invoiceId
        );
    }

    public function findInvoiceIdByLegacyOrderNumber(string $orderNumber): int
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return 0;
        }

        $stmt = $this->db->prepare(
            'SELECT i.id
             FROM vp_invoices i
             INNER JOIN vp_invoice_items ii ON ii.invoice_id = i.id
             WHERE ii.order_number = ?
             ORDER BY i.id DESC
             LIMIT 1'
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($row['id'] ?? 0);
    }

    /**
     * @return mysqli_result|false
     */
    public function getLegacyInvoiceItemsResult(int $invoiceId)
    {
        $invoiceId = (int)$invoiceId;

        return $this->db->query(
            'SELECT * FROM invoice_items WHERE invoice_id = ' . $invoiceId
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findInvoiceByOrderNumberColumn(string $orderNumber): ?array
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT i.*
             FROM vp_invoices i
             INNER JOIN vp_invoice_items ii ON ii.invoice_id = i.id
             WHERE ii.order_number = ?
             ORDER BY i.id DESC
             LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function finalizeInvoiceStatus(int $invoiceId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE vp_invoices SET status = 'final', invoice_date = CURDATE() WHERE id = ?"
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $invoiceId);
        $ok = $stmt->execute();
        $stmt->close();

        return (bool)$ok;
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

    public function findInvoiceIdByOrderNumber(string $orderNumber): int
    {
        $existing = $this->getActiveInvoiceForOrderNumber($orderNumber);
        if ($existing) {
            return (int)($existing['id'] ?? 0);
        }

        return $this->findInvoiceIdByLegacyOrderNumber($orderNumber);
    }

    /**
     * @return array{success: bool, message?: string, invoice_number?: string}
     */
    public function updateInvoiceNumber(int $invoiceId, string $newInvoiceNumber): array
    {
        $invoiceId = (int)$invoiceId;
        $newInvoiceNumber = trim($newInvoiceNumber);
        if ($invoiceId <= 0) {
            return ['success' => false, 'message' => 'Invalid invoice.'];
        }
        if ($newInvoiceNumber === '') {
            return ['success' => false, 'message' => 'Invoice number is required.'];
        }
        if (strlen($newInvoiceNumber) > 100) {
            return ['success' => false, 'message' => 'Invoice number is too long.'];
        }

        $invoice = $this->getInvoiceById($invoiceId);
        if (!$invoice) {
            return ['success' => false, 'message' => 'Invoice not found.'];
        }

        $current = trim((string)($invoice['invoice_number'] ?? ''));
        if ($current === $newInvoiceNumber) {
            return ['success' => true, 'message' => 'No change.', 'invoice_number' => $newInvoiceNumber];
        }

        $stmt = $this->db->prepare('SELECT id FROM vp_invoices WHERE invoice_number = ? AND id <> ? LIMIT 1');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Could not validate invoice number.'];
        }
        $stmt->bind_param('si', $newInvoiceNumber, $invoiceId);
        $stmt->execute();
        $duplicate = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($duplicate) {
            return ['success' => false, 'message' => 'That invoice number is already in use.'];
        }

        $stmt = $this->db->prepare('UPDATE vp_invoices SET invoice_number = ? WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Could not update invoice number.'];
        }
        $stmt->bind_param('si', $newInvoiceNumber, $invoiceId);
        $ok = $stmt->execute();
        $stmt->close();
        if (!$ok) {
            return ['success' => false, 'message' => 'Could not update invoice number.'];
        }

        return [
            'success' => true,
            'message' => 'Invoice number updated.',
            'invoice_number' => $newInvoiceNumber,
        ];
    }
}
