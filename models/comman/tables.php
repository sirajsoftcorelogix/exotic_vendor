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
        $sql = "SELECT * FROM exotic_address";
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

}
?>