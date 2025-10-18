<?php

class POInvoice
{
    private $db;
    public function __construct($db)
    {
        $this->db = $db;
    }

    public function updateFile($id, $invoiceType, $invoicePath)
    {
        if ($invoiceType === 'performa') {
            $sql = "UPDATE vp_po_invoice SET performa = ? WHERE id = ?";
        } else {
            $sql = "UPDATE vp_po_invoice SET invoice = ? WHERE id = ?";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $invoicePath, $id);
        $stmt->execute();
        if ($stmt->error) {
            return false;
        }
        return true;
    }
    public function updateInvoice($id, $data)
    {
        // Build SQL dynamically based on whether 'invoice' is set
        $fields = [
            'po_id = ?',
            'invoice_type = ?',
            'invoice_no = ?',
            'invoice_date = ?',
            'gst_reg = ?',
            'sub_total = ?',
            'gst_total = ?',
            'shipping = ?',
            'grand_total = ?'
        ];
        $params = [
            $data['po_id'],
            $data['invoice_type'],
            $data['invoice_no'],
            $data['invoice_date'],
            $data['gst_reg'],
            $data['sub_total'],
            $data['gst_total'],
            $data['shipping'],
            $data['grand_total']
        ];
        $types = 'issssssss';

        if (isset($data['invoice'])) {
            $fields[] = 'invoice = ?';
            $params[] = $data['invoice'];
            $types .= 's';
        }
        if (isset($data['performa'])) {
            $fields[] = 'performa = ?';
            $params[] = $data['performa'];
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
    public function addPoInvoice($data)
    {
        //check if po_id already exists
        $existing = $this->getInvoiceByPoId($data['po_id'],$data['invoice_type']);
        if ($existing) {
            return false; // PO ID already has an invoice
        }
        /*if ($data['invoice_type'] === 'performa') {
            $sql = "INSERT INTO vp_po_invoice (po_id, invoice_type, invoice_no, invoice_date, gst_reg, sub_total, gst_total, shipping, grand_total, performa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
            'isssssssss',
            $data['po_id'],
            $data['invoice_type'],
            $data['invoice_no'],
            $data['invoice_date'],
            $data['gst_reg'],
            $data['sub_total'],
            $data['gst_total'],
            $data['shipping'],
            $data['grand_total'],
            $data['performa']
            );
        } else {*/
            $sql = "INSERT INTO vp_po_invoice (po_id, invoice_type, invoice_no, invoice_date, gst_reg, sub_total, gst_total, shipping, grand_total, invoice) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
            'isssssssss',
            $data['po_id'],
            $data['invoice_type'],
            $data['invoice_no'],
            $data['invoice_date'],
            $data['gst_reg'],
            $data['sub_total'],
            $data['gst_total'],
            $data['shipping'],
            $data['grand_total'],
            $data['invoice']
            );
        //}
        $stmt->execute();
        if ($stmt->error) {
            return false;
        }
        return true;
    }
    public function getInvoiceByPoId($poId, $invoiceType = null)
    {
        $sql = "SELECT * FROM vp_po_invoice WHERE po_id = ? ";
        if ($invoiceType) {
            $sql .= "AND invoice_type = ?";
        }
        $stmt = $this->db->prepare($sql);
        
        if ($invoiceType) {
            $stmt->bind_param('is', $poId, $invoiceType);
        }else{
            $stmt->bind_param('i', $poId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function deletePoInvoice($id)
    {
        $sql = "DELETE FROM vp_po_invoice WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
    public function getInvoiceById($id)
    {
        $sql = "SELECT * FROM vp_po_invoice WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function addPayment($data)
    {

        $sql = "INSERT INTO vp_invoice_payments (invoice_id, po_id, vendor_id, payment_date, amount_paid, payment_mode, payment_type, payment_note, payment_currency, vendor_bank_account_number, vendor_bank_account_name, vendor_bank_ifsc_code, vendor_branch_name, vendor_bank_name, bank_transaction_reference_no, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iiissssssisssssi', $data['invoice_id'], $data['po_id'], $data['vendor_id'], $data['payment_date'], $data['amount_paid'], $data['payment_mode'], $data['payment_type'], $data['payment_note'], $data['payment_currency'], $data['vendor_bank_account_number'], $data['vendor_bank_account_name'], $data['vendor_bank_ifsc_code'], $data['vendor_branch_name'], $data['vendor_bank_name'], $data['bank_transaction_reference_no'], $data['user_id']);
        if($data['id'] > 0){
            // If ID is greater than 0, update the existing record instead of inserting a new one            
            $sql = "UPDATE vp_invoice_payments SET invoice_id = ?, po_id = ?, vendor_id = ?, payment_date = ?, amount_paid = ?, payment_mode = ?, payment_type = ?, payment_note = ?, payment_currency = ?, vendor_bank_account_number = ?, vendor_bank_account_name = ?, vendor_bank_ifsc_code = ?, vendor_branch_name = ?, vendor_bank_name = ?, bank_transaction_reference_no = ?, user_id = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iiissssssisssssii', $data['invoice_id'], $data['po_id'], $data['vendor_id'], $data['payment_date'], $data['amount_paid'], $data['payment_mode'], $data['payment_type'], $data['payment_note'], $data['payment_currency'], $data['vendor_bank_account_number'], $data['vendor_bank_account_name'], $data['vendor_bank_ifsc_code'], $data['vendor_branch_name'], $data['vendor_bank_name'], $data['bank_transaction_reference_no'], $data['user_id'], $data['id']);    

        }
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    public function getPaymentsByPoId($poId)
    {
        $sql = "SELECT * FROM vp_invoice_payments WHERE po_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $poId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function getPaymentsById($id)
    {
        $sql = "SELECT * FROM vp_invoice_payments WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function getVendorBankInfo($vendorId)
    {
        global $secretKey;
        echo $sql = "SELECT CAST(AES_DECRYPT(account_holder_name, UNHEX(SHA2('$secretKey',256))) AS CHAR) AS account_holder_name,
        CAST(AES_DECRYPT(account_number, UNHEX(SHA2('$secretKey',256))) AS CHAR) AS account_number,
        CAST(AES_DECRYPT(ifsc_code, UNHEX(SHA2('$secretKey',256))) AS CHAR) AS ifsc_code,
        CAST(AES_DECRYPT(branch_name, UNHEX(SHA2('$secretKey',256))) AS CHAR) AS branch_name,
        CAST(AES_DECRYPT(bank_name, UNHEX(SHA2('$secretKey',256))) AS CHAR) AS bank_name
        FROM vendor_bank_details WHERE vendor_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $vendorId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function findTotalAmountPaid($poId)
    {
        $sql = "SELECT SUM(amount_paid) as total_amount_paid FROM vp_invoice_payments WHERE po_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $poId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total_amount_paid'] ?? 0;
    }
    public function deletePayment($id)
    {
        $sql = "DELETE FROM vp_invoice_payments WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
    public function getChallanByPoId($poId)
    {
        $sql = "SELECT * FROM vp_delivery_challans WHERE po_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $poId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function addChallan($data)
    {
        //check if challan for the po_id already exists
        $existing = $this->getChallanByPoId($data['po_id']);
        if ($existing) {
            // If it exists, you might want to update it instead
            return $this->updateChallan($existing['id'], $data);
        }
        $sql = "INSERT INTO vp_delivery_challans (po_id, invoice_id, delivery_challan_no, delivery_challan_date, mode_of_transport, vehicle_no, transport_purpose, delivery_challan_copy, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('isssssssi', $data['po_id'], $data['invoice_id'], $data['delivery_challan_no'], $data['delivery_challan_date'], $data['mode_of_transport'], $data['vehicle_no'], $data['transport_purpose'], $data['delivery_challan_copy'], $data['user_id']);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    public function updateChallan($id, $data)
    {
        $sql = "UPDATE vp_delivery_challans SET delivery_challan_no = ?, delivery_challan_date = ?, mode_of_transport = ?, vehicle_no = ?, transport_purpose = ?, delivery_challan_copy = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssssssi', $data['delivery_challan_no'], $data['delivery_challan_date'], $data['mode_of_transport'], $data['vehicle_no'], $data['transport_purpose'], $data['delivery_challan_copy'], $id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    public function getChallansById($id)
    {
        $sql = "SELECT * FROM vp_delivery_challans WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function deleteChallan($id)
    {
        $sql = "DELETE FROM vp_delivery_challans WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
