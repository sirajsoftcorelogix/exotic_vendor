<?php

class Invoice {
    private $db;

    public function __construct($conn) {
        $this->db = $conn;
    }

    public function getAllInvoices($limit, $offset) {
        $sql = "SELECT * FROM vp_invoices ORDER BY invoice_date DESC LIMIT $limit OFFSET $offset";
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
        $sql = "INSERT INTO vp_invoices (invoice_number, invoice_date, customer_id, vp_address_info_id, currency, subtotal, tax_amount, discount_amount, total_amount, status, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $invoice_number = 'INV-' . date('Ymd') . '-' . mt_rand(1000, 9999);
        $stmt->bind_param(
            'ssisssdddsds',
            $invoice_number,
            $data['invoice_date'],
            $data['customer_id'],
            $data['vp_address_info_id'],
            $data['currency'],
            $data['subtotal'],
            $data['tax_amount'],
            $data['discount_amount'],
            $data['total_amount'],
            $data['status'],
            $data['created_by'],
            $data['created_at']
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function createInvoiceItem($data) {
        $sql = "INSERT INTO vp_invoice_items (invoice_id, order_number, item_code, item_name, description, box_no, quantity, unit_price, tax_rate, cgst, sgst, igst, tax_amount, line_total)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param(
            'issssiiddddddd',
            $data['invoice_id'],
            $data['order_number'],
            $data['item_code'],
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
            $data['line_total']
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
}
