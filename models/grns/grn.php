<?php 
class grn{
    private $conn;  
    public function __construct($conn) {
        $this->conn = $conn;
    }
    public function getGrnDetails($id) {
        $query = "SELECT * FROM vp_grns WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function createGrn($data) {
        $query = "INSERT INTO vp_grns (po_id,po_number,sku,qty_received,qty_acceptable,remarks,location,received_by,received_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("issssiiss", $data['po_id'], $data['po_number'], $data['sku'], $data['qty_received'], $data['qty_acceptable'], $data['remarks'], $data['location'], $data['received_by'], $data['received_date']);
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        } else {
            return false;
        }
    }
    public function uploadGrnFile($grnId, $fileName) {
        $query = "INSERT INTO vp_grn_files (po_id, file_name) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("is", $grnId, $fileName);
        return $stmt->execute();
    }
}
?>