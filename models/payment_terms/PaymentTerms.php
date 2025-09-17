<?php
class PaymentTerms {
    private $conn;
    public function __construct($db) {
        $this->conn = $db;
    }
    public function getAll($page = 1, $limit = 10, $search = '', $status_filter = '') {
		$page = (int)$page;
        if ($page < 1) $page = 1;

        $limit = (int)$limit;
        if ($limit < 1) $limit = 10;

        // calculate offset
        $offset = ($page - 1) * $limit;
		$where = "";
        
		if (!empty($search) && !empty($status_filter)) {
            $search = $this->conn->real_escape_string($search);
            $status_filter = $this->conn->real_escape_string($status_filter);
            $where = "WHERE term_conditions LIKE('%$search%') AND is_active = '$status_filter'";
        } else {
            if (!empty($search)) {
                $search = $this->conn->real_escape_string($search);
                $where = "WHERE term_conditions LIKE('%$search%')";
            }

            if (!empty($status_filter)) {
                $search = $this->conn->real_escape_string($status_filter);   
                $where = "WHERE is_active = '$status_filter'";
            }
        }

		// total records
        $resultCount = $this->conn->query("SELECT COUNT(*) AS total FROM vp_payment_term_conditions $where");
        $rowCount = $resultCount->fetch_assoc();
        $totalRecords = $rowCount['total'];

        $totalPages = ceil($totalRecords / $limit);

        // fetch data
        $sql = "SELECT * FROM vp_payment_term_conditions $where LIMIT $limit OFFSET $offset";
        $result = $this->conn->query($sql);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        // return structured data
        return [
            'terms_condition'        => $data,
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'limit'        => $limit,
            'totalRecords' => $totalRecords,
            'search'       => $search
        ];
	}
	public function addPTRecord($data) {
        $sql = "INSERT INTO vp_payment_term_conditions (title, term_conditions, is_active) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sss',
            $data['addTitle'],
            $data['addNotes'],
            $data['addStatus']
        );
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Record added successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function updatePTRecord($id, $data) {
        $sql = "UPDATE vp_payment_term_conditions SET title = ?, term_conditions = ?, is_active = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sssi',
            $data['editTitle'],
            $data['editNotes'],
            $data['editStatus'],
            $id
        );
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Record updated successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function deletePTRecord($id) {
        $sql = "DELETE FROM vp_payment_term_conditions WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Record deleted successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Delete failed: ' . $stmt->error . '. Please try again later.'
        ];
    }
	
	public function getTCRecord($id) {
        $result = $this->conn->query("SELECT id, title, term_conditions, is_active 
                      FROM vp_payment_term_conditions 
                      WHERE id = $id");
        $row = $result->fetch_assoc();

        return json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
}
?>