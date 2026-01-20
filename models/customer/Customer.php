<?php 
class Customer{
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
}
?>