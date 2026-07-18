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

    /**
     * Resolve GST department numeric state code (e.g. 07 for Delhi).
     */
    public function resolveGstStateCode(string $stateName = '', string $alphaStateCode = '', int $countryId = 105): ?string
    {
        $stateName = trim($stateName);
        $alphaStateCode = strtoupper(trim($alphaStateCode));

        if ($stateName !== '') {
            $stmt = $this->conn->prepare(
                'SELECT gst_state_code FROM states
                 WHERE country_id = ? AND name = ?
                 AND gst_state_code IS NOT NULL AND gst_state_code <> \'\'
                 LIMIT 1'
            );
            if ($stmt) {
                $stmt->bind_param('is', $countryId, $stateName);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (is_array($row) && trim((string)($row['gst_state_code'] ?? '')) !== '') {
                    return str_pad(trim((string)$row['gst_state_code']), 2, '0', STR_PAD_LEFT);
                }
            }

            $likeName = $stateName;
            $stmt = $this->conn->prepare(
                'SELECT gst_state_code FROM states
                 WHERE country_id = ? AND name LIKE ?
                 AND gst_state_code IS NOT NULL AND gst_state_code <> \'\'
                 LIMIT 1'
            );
            if ($stmt) {
                $stmt->bind_param('is', $countryId, $likeName);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (is_array($row) && trim((string)($row['gst_state_code'] ?? '')) !== '') {
                    return str_pad(trim((string)$row['gst_state_code']), 2, '0', STR_PAD_LEFT);
                }
            }
        }

        if ($alphaStateCode !== '') {
            $stmt = $this->conn->prepare(
                'SELECT gst_state_code FROM states
                 WHERE country_id = ? AND state_code = ?
                 AND gst_state_code IS NOT NULL AND gst_state_code <> \'\'
                 LIMIT 1'
            );
            if ($stmt) {
                $stmt->bind_param('is', $countryId, $alphaStateCode);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (is_array($row) && trim((string)($row['gst_state_code'] ?? '')) !== '') {
                    return str_pad(trim((string)$row['gst_state_code']), 2, '0', STR_PAD_LEFT);
                }
            }
        }

        return null;
    }
}
?>