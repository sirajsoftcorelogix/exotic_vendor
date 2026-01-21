<?php 
class Customer{
    private $conn;  
    public function __construct($conn) {
        $this->conn = $conn;
    }
    public function getCustomers() {
        $query = "SELECT * FROM vp_customers";
        $stmt = $this->conn->prepare($query);        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>