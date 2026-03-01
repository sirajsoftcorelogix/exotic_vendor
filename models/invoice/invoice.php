<?php

class Invoice {
    private $db;

    public function __construct($conn) {
        $this->db = $conn;
    }

    public function getAllInvoices($limit, $offset) {
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

    public function countAllInvoices() {
        $sql = "SELECT COUNT(*) AS cnt FROM vp_invoices";
        $result = $this->db->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            return isset($row['cnt']) ? (int)$row['cnt'] : 0;
        }
        return 0;
    }

    public function createInvoice($data) {
        $sql = "INSERT INTO vp_invoices (invoice_number, invoice_date, customer_id, vp_order_info_id, currency, subtotal, tax_amount, discount_amount, total_amount, status, created_by, created_at, exchange_text, converted_amount) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $invoice_number = 'INV-' . date('Ymd') . '-' . mt_rand(1000, 9999);
        $stmt->bind_param(
            'ssisssdddsdssd',
            $data['invoice_number'],
            $data['invoice_date'],
            $data['customer_id'],
            $data['vp_order_info_id'],
            $data['currency'],
            $data['subtotal'],
            $data['tax_amount'],
            $data['discount_amount'],
            $data['total_amount'],
            $data['status'],
            $data['created_by'],
            $data['created_at'],
            $data['exchange_text'],
            $data['converted_amount']
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function createInvoiceItem($data) {
        $sql = "INSERT INTO vp_invoice_items (invoice_id, order_number, item_code, hsn, item_name, description, box_no, quantity, unit_price, tax_rate, cgst, sgst, igst, tax_amount, line_total, image_url, groupname)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param(
            'isssssiddddddddss',
            $data['invoice_id'],
            $data['order_number'],
            $data['item_code'],
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

    public function getInvoiceById($id) {
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

    public function getInvoiceItems($invoice_id) {
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

    public function updateInvoiceStatus($id, $status) {
        $sql = "UPDATE vp_invoices SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param('si', $status, $id);
        return $stmt->execute();
    }

    public function deleteInvoice($id) {
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
    public function getCustomerById($customer_id) {
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
    public function getInvoiceByOrderNumber($order_number) {
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
    public function insert_international_invoice_data($data) {
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
    public function getInternationalInvoiceByInvoiceId($invoice_id) {
        $sql = "SELECT * FROM vp_international_invoices WHERE invoice_id = ?";
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
    public function updateInvoice($invoice_id, $data) {
        $sql = "UPDATE vp_international_invoices SET irn = ?, ack_number = ?, ack_date = ?, signed_invoice = ?, qrcode_string = ?, irn_status = ?, updated_at = NOW() WHERE invoice_id = ?";
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
    public function getInvoicesCount() {
        $sql = "SELECT COUNT(*) AS cnt FROM vp_invoices";
        $result = $this->db->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            return isset($row['cnt']) ? (int)$row['cnt'] : 0;
        }
        return 0;
    }
    public function getAllInvoicesPaginated($limit, $offset, $filters = []) {
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
            $whereClause[] = "i.status = '" . $this->db->real_escape_string($filters['status']) . "'";
        }
        if (isset($filters['category']) && $filters['category'] !== '') {
            $whereClause[] = "d.groupname LIKE '%" . $this->db->real_escape_string($filters['category']) . "%'";
        }

        if (!empty($whereClause)) {
            $sql .= "WHERE " . implode(" AND ", $whereClause) . " ";
        }

        if (isset($filters['sort']) && in_array($filters['sort'], ['asc','desc'])) {
            $sql .= "ORDER BY i.invoice_date " . (($filters['sort'] === 'asc') ? 'ASC' : 'DESC') . " ";
        } else {
            $sql .= "ORDER BY i.invoice_date DESC ";
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
}
