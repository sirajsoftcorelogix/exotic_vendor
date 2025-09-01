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

}
?>