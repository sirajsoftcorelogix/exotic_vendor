<?php
class Country {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllCountries() {
		
		$sql = "SELECT * FROM countries";
		$sql .= " ORDER BY name ASC";

		$stmt = $this->conn->prepare($sql);
		$stmt->execute();

		$result = $stmt->get_result();

		$data = [];
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}

		return ['countries' => $data];
	}
	
	public function getCountry($id) {
		$sql = "SELECT * FROM countries WHERE id = ?";

		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $id);
		$stmt->execute();

		$result = $stmt->get_result();
		$data = $result->fetch_assoc();  // Get only one row as associative array

		return json_encode($data);
	}
}
?>