<?php
class State {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllStates($country_id = '') {

		$sql = "SELECT * FROM states WHERE country_id = ?";
		$sql .= " ORDER BY name ASC";
		
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $country_id);
		$stmt->execute();

		$result = $stmt->get_result();

		$data = [];
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}

		return ['states' => $data];
	}
	
	public function getState($id) {
		$sql = "SELECT * FROM states WHERE id = ?";

		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $id);
		$stmt->execute();

		$result = $stmt->get_result();
		$data = $result->fetch_assoc();  // Get only one row as associative array

		return $data;
	}
}
?>