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
            //'po_id = ?',
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
            //$data['po_id'],
            $data['invoice_type'],
            $data['invoice_no'],
            $data['invoice_date'],
            $data['gst_reg'],
            $data['sub_total'],
            $data['gst_total'],
            $data['shipping'],
            $data['grand_total']
        ];
        $types = 'ssssssss';

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
        // Support mapping one invoice to multiple PO IDs (po_ids as array or comma-separated string)
        $poIds = [];
        if (!empty($data['po_ids'])) {
            if (is_string($data['po_ids'])) {
                $poIds = array_map('trim', explode(',', $data['po_ids']));
            } elseif (is_array($data['po_ids'])) {
                $poIds = $data['po_ids'];
            }
        } else {
            $poIds = [isset($data['po_id']) ? $data['po_id'] : 0];
        }
        // Normalize to unique ints
        $poIds = array_values(array_unique(array_map('intval', $poIds)));
        if (empty($poIds)) {
            return ['success' => false, 'message' => 'No Purchase Order IDs provided.'];
        }

        // Determine which PO IDs already have an invoice of this type
        $skipped = [];
        $toMap = [];
        foreach ($poIds as $pid) {
            $existing = $this->getInvoiceByPoId($pid, $data['invoice_type'] ?? null);
            if ($existing) {
                $skipped[] = $pid;
            } else {
                $toMap[] = $pid;
            }
        }
        if (empty($toMap)) {
            return ['success' => false, 'message' => 'All selected POs already have an invoice.', 'skipped' => $skipped];
        }

        // Insert invoice record (use the first mapped PO as representative in vp_po_invoice.po_id)
        $representativePoId = $toMap[0];
        $sql = "INSERT INTO vp_po_invoice (po_id, invoice_type, invoice_no, invoice_date, gst_reg, sub_total, gst_total, shipping, grand_total, invoice) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'isssssssss',
            $representativePoId,
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

        try {
            $this->db->autocommit(FALSE);
            if (!$stmt->execute()) {
                $this->db->rollback();
                $this->db->autocommit(TRUE);
                return ['success' => false, 'message' => 'Failed to insert invoice record.'];
            }
            $invoiceId = $stmt->insert_id;
            $sqlMap = "INSERT INTO vp_po_invoice_map (po_id, invoice_id) VALUES (?, ?)";
            $stmtMap = $this->db->prepare($sqlMap);
            foreach ($toMap as $pid) {
                $stmtMap->bind_param('ii', $pid, $invoiceId);
                if (!$stmtMap->execute()) {
                    $this->db->rollback();
                    $this->db->autocommit(TRUE);
                    return ['success' => false, 'message' => 'Failed to map invoice to PO: ' . $pid];
                }
            }
            $this->db->commit();
            $this->db->autocommit(TRUE);
        } catch (Exception $e) {
            $this->db->rollback();
            $this->db->autocommit(TRUE);
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }

        if ($stmt->error) {
            return ['success' => false, 'message' => 'Database error saving invoice.'];
        }
        return ['success' => true, 'mapped' => $toMap, 'skipped' => $skipped, 'invoice_id' => $invoiceId];
    
    }
    public function getInvoiceByPoId($poId, $invoiceType = null)
    {
        // First, check mapping table to support invoices mapped to multiple POs
        $sql = "SELECT p.* FROM vp_po_invoice p JOIN vp_po_invoice_map m ON p.id = m.invoice_id WHERE m.po_id = ? ";
        if ($invoiceType) {
            $sql .= "AND p.invoice_type = ?";
        }
        $stmt = $this->db->prepare($sql);
        if ($invoiceType) {
            $stmt->bind_param('is', $poId, $invoiceType);
        } else {
            $stmt->bind_param('i', $poId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row) {
            return $row;
        }
        // Fallback: check direct po_id column (legacy behavior)
        $sql2 = "SELECT * FROM vp_po_invoice WHERE po_id = ? ";
        if ($invoiceType) {
            $sql2 .= "AND invoice_type = ?";
        }
        $stmt2 = $this->db->prepare($sql2);
        if ($invoiceType) {
            $stmt2->bind_param('is', $poId, $invoiceType);
        } else {
            $stmt2->bind_param('i', $poId);
        }
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        return $result2->fetch_assoc();
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

    /**
     * Get POs mapped to a group invoice (returns array of ['po_id','po_number','total_cost'])
     */
    public function getPOsByInvoiceId($invoiceId)
    {
        $sql = "SELECT m.po_id, p.po_number, p.total_cost FROM vp_po_invoice_map m JOIN purchase_orders p ON p.id = m.po_id WHERE m.invoice_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $invoiceId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function addPayment($data)
    {
        global $secretKey;
        if($data['id'] > 0){
            // If ID is greater than 0, update the existing record instead of inserting a new one            
            // Update existing payment
            $invoice_id = $data['invoice_id'];
            $po_id = $data['po_id'];
            $vendor_id = $data['vendor_id'];
            $payment_date = $data['payment_date'];
            $amount_paid = $data['amount_paid'];
            $payment_mode = $data['payment_mode'];
            $payment_type = $data['payment_type'];
            $payment_note = $data['payment_note'];
            $payment_currency = $data['payment_currency'];
            $vendor_bank_account_number = $data['vendor_bank_account_number'];
            $vendor_bank_account_name = $data['vendor_bank_account_name'];
            $vendor_bank_ifsc_code = $data['vendor_bank_ifsc_code'];
            $vendor_branch_name = $data['vendor_branch_name'];
            $vendor_bank_name = $data['vendor_bank_name'];
            $bank_transaction_reference_no = $data['bank_transaction_reference_no'];
            $user_id = $data['user_id'];
            $id = $data['id'];

            $sql = "UPDATE vp_invoice_payments SET invoice_id = ?, po_id = ?, vendor_id = ?, payment_date = ?, amount_paid = ?, payment_mode = ?, payment_type = ?, payment_note = ?, payment_currency = ?, vendor_bank_account_number = AES_ENCRYPT(?, UNHEX(SHA2(?,512))), vendor_bank_account_name = AES_ENCRYPT(?, UNHEX(SHA2(?,512))), vendor_bank_ifsc_code = AES_ENCRYPT(?, UNHEX(SHA2(?,512))), vendor_branch_name = AES_ENCRYPT(?, UNHEX(SHA2(?,512))), vendor_bank_name = AES_ENCRYPT(?, UNHEX(SHA2(?,512))), bank_transaction_reference_no = ?, user_id = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return false;
            }

            // types: 3 ints, 17 strings (including secretKey placeholders), 2 ints (user_id, id)
            $types = 'iii' . str_repeat('s', 17) . 'ii';
            $stmt->bind_param(
                $types,
                $invoice_id,
                $po_id,
                $vendor_id,
                $payment_date,
                $amount_paid,
                $payment_mode,
                $payment_type,
                $payment_note,
                $payment_currency,
                $vendor_bank_account_number,
                $secretKey,
                $vendor_bank_account_name,
                $secretKey,
                $vendor_bank_ifsc_code,
                $secretKey,
                $vendor_branch_name,
                $secretKey,
                $vendor_bank_name,
                $secretKey,
                $bank_transaction_reference_no,
                $user_id,
                $id
            );

        }else{
            $sql = "INSERT INTO vp_invoice_payments (invoice_id, po_id, vendor_id, payment_date, amount_paid, payment_mode, payment_type, payment_note, payment_currency, vendor_bank_account_number, vendor_bank_account_name, vendor_bank_ifsc_code, vendor_branch_name, vendor_bank_name, bank_transaction_reference_no, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), ?, ?)";
            $stmt = $this->db->prepare($sql);
             // types: 3 ints, 17 strings (including secretKey placeholders), 2 ints (user_id)
            $types = 'iii' . str_repeat('s', 17) . 'i';
            $stmt->bind_param($types,
                $data['invoice_id'],
                $data['po_id'],
                $data['vendor_id'],
                $data['payment_date'],
                $data['amount_paid'],
                $data['payment_mode'],
                $data['payment_type'],
                $data['payment_note'],
                $data['payment_currency'],
                $data['vendor_bank_account_number'],
                $secretKey,
                $data['vendor_bank_account_name'],
                $secretKey,
                $data['vendor_bank_ifsc_code'],
                $secretKey,
                $data['vendor_branch_name'],
                $secretKey,
                $data['vendor_bank_name'],
                $secretKey,
                $data['bank_transaction_reference_no'],
                $data['user_id']
            );
            
        }
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    public function getPaymentsByPoId($poId)
    {
        global $secretKey;
        if (isset($secretKey)) {
            $sql = "SELECT *,
                    CAST(AES_DECRYPT(vendor_bank_account_number, UNHEX(SHA2(?,512))) AS CHAR) AS vendor_bank_account_number,
                    CAST(AES_DECRYPT(vendor_bank_account_name, UNHEX(SHA2(?,512))) AS CHAR) AS vendor_bank_account_name,
                    CAST(AES_DECRYPT(vendor_bank_ifsc_code, UNHEX(SHA2(?,512))) AS CHAR) AS vendor_bank_ifsc_code,
                    CAST(AES_DECRYPT(vendor_branch_name, UNHEX(SHA2(?,512))) AS CHAR) AS vendor_branch_name,
                    CAST(AES_DECRYPT(vendor_bank_name, UNHEX(SHA2(?,512))) AS CHAR) AS vendor_bank_name FROM vp_invoice_payments WHERE po_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sssssi', $secretKey, $secretKey, $secretKey, $secretKey, $secretKey, $poId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            return ['success' => false, 'message' => 'Secret key is not set. Cannot encrypt bank details.'];
        }
    }
    public function getPaymentsById($id)
    {
        global $secretKey;
        $sql = "SELECT *, 
            CAST(AES_DECRYPT(vendor_bank_account_number, UNHEX(SHA2('$secretKey',512))) AS CHAR) AS vendor_bank_account_number,
            CAST(AES_DECRYPT(vendor_bank_account_name, UNHEX(SHA2('$secretKey',512))) AS CHAR) AS vendor_bank_account_name,
            CAST(AES_DECRYPT(vendor_bank_ifsc_code, UNHEX(SHA2('$secretKey',512))) AS CHAR) AS vendor_bank_ifsc_code,
            CAST(AES_DECRYPT(vendor_branch_name, UNHEX(SHA2('$secretKey',512))) AS CHAR) AS vendor_branch_name,
            CAST(AES_DECRYPT(vendor_bank_name, UNHEX(SHA2('$secretKey',512))) AS CHAR) AS vendor_bank_name
            FROM vp_invoice_payments WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function getVendorBankInfo($vendorId)
    {
        global $secretKey;
        $sql = "SELECT CAST(AES_DECRYPT(account_holder_name, UNHEX(SHA2('$secretKey',512))) AS CHAR) AS account_holder_name,
        CAST(AES_DECRYPT(account_number, UNHEX(SHA2('$secretKey',512))) AS CHAR) AS account_number,
        CAST(AES_DECRYPT(ifsc_code, UNHEX(SHA2('$secretKey',512))) AS CHAR) AS ifsc_code,
        CAST(AES_DECRYPT(branch_name, UNHEX(SHA2('$secretKey',512))) AS CHAR) AS branch_name,
        CAST(AES_DECRYPT(bank_name, UNHEX(SHA2('$secretKey',512))) AS CHAR) AS bank_name
        FROM vendor_bank_details WHERE vendor_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $vendorId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function getBankDetailsById($vendor_id) {
        global $secretKey;
        if (isset($secretKey)) {
            $sql = "SELECT vendor_id,
                    CAST(AES_DECRYPT(account_holder_name, UNHEX(SHA2(?,512))) AS CHAR) AS account_name,
                    CAST(AES_DECRYPT(account_number, UNHEX(SHA2(?,512))) AS CHAR) AS account_number,
                    CAST(AES_DECRYPT(ifsc_code, UNHEX(SHA2(?,512))) AS CHAR) AS ifsc_code,
                    CAST(AES_DECRYPT(bank_name, UNHEX(SHA2(?,512))) AS CHAR) AS bank_name,
                    CAST(AES_DECRYPT(branch_name, UNHEX(SHA2(?,512))) AS CHAR) AS branch_name, is_active FROM vendor_bank_details WHERE vendor_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sssssi', $secretKey, $secretKey, $secretKey, $secretKey, $secretKey, $vendor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } else {
            return ['success' => false, 'message' => 'Secret key is not set. Cannot encrypt bank details.'];
        }
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
    public function getAllInvoices($limit = 0, $offset = 0)
    {
        $sql = "SELECT * FROM vp_po_invoice ORDER BY id DESC";
        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
        }
        $stmt = $this->db->prepare($sql);
        if ($limit > 0) {
            $stmt->bind_param('ii', $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function getTotalInvoices($limit = 0, $offset = 0)
    {
        $sql = "SELECT COUNT(*) as total FROM vp_po_invoice";
        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
        }
        $stmt = $this->db->prepare($sql);
        if ($limit > 0) {
            $stmt->bind_param('ii', $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }
}
