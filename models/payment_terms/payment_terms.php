<?php
class PaymentTerms {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
		
		$sql = "SELECT id, term_conditions FROM vp_po_term_conditions";
		$sql .= " ORDER BY id DESC";

		$stmt = $this->conn->prepare($sql);
		$stmt->execute();

		$result = $stmt->get_result();

		$data = [];
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}

		return ['terms_condition' => $data];
	}
	
	public function getTCRecord($id) {
		$sql = "SELECT id, term_conditions FROM vp_po_term_conditions WHERE id = ?";

		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $id);
		$stmt->execute();

		$result = $stmt->get_result();
		$data = $result->fetch_assoc();  // Get only one row as associative array

		return json_encode($data);
	}
}
?>