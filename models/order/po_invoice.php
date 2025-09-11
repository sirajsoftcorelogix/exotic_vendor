<?php

class POInvoice{
    private $db;    
    public function __construct($db) {
        $this->db = $db;
    }

    public function updateFile($id, $invoicePath) {
        $sql = "UPDATE vp_po_invoice SET invoice = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $invoicePath, $id);
        $stmt->execute();
        if ($stmt->error) {
            return false;
        }
        return true;
    }
    public function updateInvoice($id, $data) {
        // Build SQL dynamically based on whether 'invoice' is set
        $fields = [
            'po_id = ?',
            'invoice_date = ?',
            'gst_reg = ?',
            'sub_total = ?',
            'gst_total = ?',
            'shipping = ?',
            'grand_total = ?'
        ];
        $params = [
            $data['po_id'],
            $data['invoice_date'],
            $data['gst_reg'],
            $data['sub_total'],
            $data['gst_total'],
            $data['shipping'],
            $data['grand_total']
        ];
        $types = 'issssss';

        if (isset($data['invoice'])) {
            $fields[] = 'invoice = ?';
            $params[] = $data['invoice'];
            $types .= 's';
        }

        $fields_str = implode(', ', $fields);
        $sql = "UPDATE vp_po_invoice SET $fields_str WHERE id = ?";
        $params[] = $id;
        $types .= 'i';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            return true; // Return true on success
        }
        return false; // Return false on failure
    }
    public function addPoInvoice($data){
        $sql = "INSERT into vp_po_invoice (po_id, invoice_date, gst_reg, sub_total, gst_total, shipping, grand_total, invoice) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('isssssss', $data['po_id'], $data['invoice_date'], $data['gst_reg'], $data['sub_total'], $data['gst_total'], $data['shipping'], $data['grand_total'], $data['invoice']);
        $stmt->execute();
        if ($stmt->error) {
            return false;
        }
        return true;
    }
    public function getInvoiceByPoId($poId) {
        $sql = "SELECT * FROM vp_po_invoice WHERE po_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $poId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function deletePoInvoice($id) {
        $sql = "DELETE FROM vp_po_invoice WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
    public function getInvoiceById($id) {
        $sql = "SELECT * FROM vp_po_invoice WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
