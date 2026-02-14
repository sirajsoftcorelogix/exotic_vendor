<?php

class Tables {

    protected $ci;

    public function __construct($ci) {
        $this->ci = $ci;
    }

    // public function get_vendors() {
    //     $query = $this->ci->db->get('vendors');
    //     return $query->result_array();
    // }

    // public function get_users() {
    //     $query = $this->ci->db->get('users');
    //     return $query->result_array();
    // }

    // public function get_orders() {
    //     $query = $this->ci->db->get('purchase_orders');
    //     return $query->result_array();
    // }
    

    public function get_exotic_address() {
        $sql = "SELECT * FROM exotic_address WHERE is_active = 1 order by `order_no` ASC";
        $result = $this->ci->query($sql);
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;

    }

    public function get_terms_and_conditions() {
        $sql = "SELECT * FROM terms_and_conditions";
        $result = $this->ci->query($sql);
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }
    public function get_payment_terms_and_conditions() {
        $sql = "SELECT * FROM vp_payment_term_conditions WHERE is_active = 'active'";
        $result = $this->ci->query($sql);
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }
    public function add_po_status_log($data) {
        $sql = "INSERT INTO vp_po_status_log (po_id, status, changed_by, change_date) VALUES (?, ?, ?, ?)";
        $stmt = $this->ci->prepare($sql);
        $stmt->bind_param('isis', $data['po_id'], $data['status'], $data['changed_by'], $data['change_date']);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    public function get_order_status() {
        $sql = "SELECT * FROM vp_order_status WHERE is_active = 1";
        $stmt = $this->ci->prepare($sql);        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function get_order_status_list() {
        $sql = "SELECT * FROM vp_order_status WHERE is_active = 1 and parent_id != 0";
        $stmt = $this->ci->prepare($sql);        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[$row['slug']] = $row['title'];
            }
        }
        return $data;
    }
    public function get_counry_list() {
        $sql = "SELECT * FROM countries WHERE 1=1";
        $stmt = $this->ci->prepare($sql);        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               if (!empty($row['name']) && !empty($row['country_code'])) {
                    $data[$row['country_code']] = $row['name'];
                }

            }
        }
        return $data;
    }

    //order status log
    public function add_order_status_log($data) {
        $sql = "INSERT INTO vp_order_status_log (order_id, status, changed_by, api_response, change_date) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->ci->prepare($sql);
        $stmt->bind_param('issss', $data['order_id'], $data['status'], $data['changed_by'], $data['api_response'], $data['change_date']);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    //get order status log
    public function get_order_status_log($order_id) {
        $sql = "SELECT osl.*, u.name AS changed_by_username FROM vp_order_status_log osl JOIN vp_users u ON osl.changed_by = u.id WHERE osl.order_id = ? ORDER BY osl.id ASC";
        $stmt = $this->ci->prepare($sql);
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }
    public function getExoticIndiaOrderStatusCode($slug) {
        $sql = "SELECT * FROM vp_order_status WHERE slug = ? LIMIT 1";
        $stmt = $this->ci->prepare($sql);
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    public function updateExoticIndiaOrderStatus($apidata) {
        // This is a placeholder function. Implement the actual API call here.
        //print_r($new_status);
        //echo "Updating Exotic India order status for order ID: " . $order_id . "\n";
        // For example, you might use cURL or any HTTP client to send a request to the Exotic India API.
        $url = "https://www.exoticindia.com/vendor-api/order/modify";
        $postData = [
            'makeRequestOf' => 'vendors-orderjson',
            'orderid' => $apidata['orderid'],
            'level' => $apidata['level'],
            'order_status' => $apidata['order_status'],
            'itemcode' => $apidata['itemcode'],
            'size' => $apidata['size'],
            'color' => $apidata['color']
        ];
        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        // Initialize cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            // Handle cURL error
            //echo "cURL Error: " . $error;
            return  "cURL Error: " . $error;
        }
        return $response;
    }
    public function get_staff_list() {
        //$sql = "SELECT id, name FROM vp_users WHERE is_active = 1 AND role_id ='4'";
        $sql = "SELECT id, name FROM vp_users WHERE is_active = 1";
        $stmt = $this->ci->prepare($sql);        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[$row['id']] = $row['name'];
            }
        }
        return $data;
    }
    public function getUserNameById($user_id) {
        $sql = "SELECT name FROM vp_users WHERE id = ? LIMIT 1";
        $stmt = $this->ci->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['name'];
        }
        return null;
    }
    public function getRecordById($table, $id) {
        $sql = "SELECT * FROM " . $table . " WHERE id = ? LIMIT 1";
        $stmt = $this->ci->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    public function get_customer_address($order_number) {
        $sql = "SELECT * FROM vp_order_info WHERE order_number = ? LIMIT 1";
        $stmt = $this->ci->prepare($sql);
        $stmt->bind_param('s', $order_number);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    public function updateRecord($table, $data, $id) {
        $setClause = [];
        $types = '';
        $values = [];
        foreach ($data as $key => $value) {
            $setClause[] = "$key = ?";
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_double($value) || is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $value;
        }
        $values[] = $id;
        $types .= 'i';

        $sql = "UPDATE " . $table . " SET " . implode(', ', $setClause) . " WHERE id = ?";
        $stmt = $this->ci->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param($types, ...$values);

        return $stmt->execute();
    }
    public function getRecordByField($table, $field, $value) {
        $sql = "SELECT * FROM " . $table . " WHERE " . $field . " = ? LIMIT 1";
        $stmt = $this->ci->prepare($sql);
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    public function prepareIrisIrpInvoiceData($invoice, $items) {
        $data = [
            // Map invoice fields to Iris IRP API fields
            "invoice_number" => $invoice['invoice_number'],
            "invoice_date" => $invoice['invoice_date'],
            "customer_gstin" => $invoice['customer_gstin'],
            "total_amount" => $invoice['total_amount'],
            // Add other necessary fields
            "items" => []
        ];

        foreach ($items as $item) {
            $data['items'][] = [
                "description" => $item['description'],
                "hsn_code" => $item['hsn_code'],
                "quantity" => $item['quantity'],
                "unit_price" => $item['unit_price'],
                "total_price" => $item['total_price'],
                // Add other necessary item fields
            ];
        }

        return $data;
    }
    public function updateGlobalSettings($data, $id) {
        //print_r($data); Array ( [setting_key] => invoice_prefix [setting_value] => inv/2025-26/ )
        $setClause = [];
        $types = '';
        $values = [];
        foreach ($data as $key => $value) {
            $setClause[] = "$key = ?";
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_double($value) || is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $value;
        }
        $values[] = $id;
        $types .= 'i';

        $sql = "UPDATE global_settings SET " . implode(', ', $setClause) . " WHERE id = ?";
        $stmt = $this->ci->prepare($sql);
        if (!$stmt) return false; 
        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }
    public function getAllGlobalSettings() {
        $sql = "SELECT * FROM global_settings";
        $stmt = $this->ci->prepare($sql);        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        return $data;
    }
    public function getCommittedStockBySku($sku) {
        //$sql = "SELECT SUM(quantity) AS committed_stock FROM vp_po_items WHERE sku = ? AND purchase_orders_id IN (SELECT id FROM purchase_orders WHERE status IN ('pending', 'processing'))";
        $sql = "SELECT SUM(quantity) AS committed_stock FROM vp_orders WHERE sku = ? AND status NOT IN ('delivered', 'cancelled', 'returned', 'shipped')";
        $stmt = $this->ci->prepare($sql);
        $stmt->bind_param('s', $sku);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['committed_stock'] ?? 0;
        }
        return 0;
    }
    public function isInPurchaseList($sku) {
        //$sql = "SELECT id, po_number FROM vp_po_items WHERE sku = ? AND purchase_orders_id IN (SELECT id FROM purchase_orders WHERE status IN ('pending', 'ordered'))";
        $sql = "SELECT po.id, po.po_number FROM purchase_orders po join vp_po_items p on po.id = p.purchase_orders_id WHERE p.sku = ? AND po.status IN ('pending', 'ordered')";
        $stmt = $this->ci->prepare($sql);
        $stmt->bind_param('s', $sku);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        if ($result && $result->num_rows > 0) {
           while ($row = $result->fetch_assoc()) {
                $data[$row['id']] = $row['po_number'];
            }
        }
        return $data;
    }
}
?>
