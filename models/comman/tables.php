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
        $sql = "SELECT * FROM exotic_address WHERE is_active = 1";
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
                $data[$row['country_code']] = $row['NAME'];
            }
        }
        return $data;
    }

}
?>