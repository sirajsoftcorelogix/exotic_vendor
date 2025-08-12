<?php
class Addons {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($search = '', $sort_by = 'id', $sort_order = 'asc', $limit = 10, $offset = 0) {
		
		$sql = "SELECT * FROM addons";

		$params = [];
		if ($search !== '') {
			$sql .= " WHERE addon_name LIKE ? OR description LIKE ?";
			$searchTerm = "%$search%";
			$params = [$searchTerm, $searchTerm];
		}

		$sql .= " ORDER BY $sort_by $sort_order LIMIT ?, ?";
		$params[] = $offset;
		$params[] = $limit;

		$stmt = $this->conn->prepare($sql);
		$types = str_repeat('s', count($params) - 2) . 'ii';
		$stmt->bind_param($types, ...$params);
		$stmt->execute();

		$result = $stmt->get_result();

		$data = [];
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}

		return ['addons' => $data];
	}
	
	public function countAll($search = '') {
		$sql = "SELECT * FROM addons";
		$params = [];
		if ($search !== '') {
			$sql .= " WHERE addon_name LIKE ? OR description LIKE ?";
			$searchTerm = "%$search%";
			$params = [$searchTerm, $searchTerm];
		}

		$stmt = $this->conn->prepare($sql);

		if (!empty($params)) {
			$types = str_repeat('s', count($params));
			$stmt->bind_param($types, ...$params);
		}

		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();

		return $row['total'] ?? 0;
	}
	
	public function getAddon($id) {
		$sql = "SELECT * FROM addons WHERE id = ?";

		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("s", $id);
		$stmt->execute();

		$result = $stmt->get_result();
		$data = $result->fetch_assoc();  // Get only one row as associative array

		return json_encode($data);
	}
	
    public function insert($data) {
		
		$table_name = 'addons';
		
		$allFields = ['addon_name', 'description', 'active'];
		$required = ['addon_name', 'active'];
		
		$response = validateRequiredFields($data, $required);
		if (!$response['success']) return $response;
		
		// Build SQL query
		$columns = implode(', ', $allFields);
		$placeholders = rtrim(str_repeat('?, ', count($allFields)), ', ');

		$sql = "INSERT INTO {$table_name} ({$columns}) VALUES ({$placeholders})";
		
		$stmt = $this->conn->prepare($sql);
		if (!$stmt) {
			return ['success' => false, 'error' => 'Prepare failed: ' . $conn->error];
		}

		$types = '';
		$values = [];
		foreach ($allFields as $field) {
			$value = isset($data[$field]) ? $data[$field] : null;
			$types .= is_int($value) ? 'i' : 's';
			$values[] = $value;
		}

		// Bind dynamically
		$stmt->bind_param($types, ...$values);

		if ($stmt->execute()) {
			return ['success' => true, 'message' => 'Product added successfully.'];
		} else {
			return ['success' => false, 'error' => 'Insert failed: ' . $stmt->error];
		}
    }
	
	public function update($id, $data) {
		//print_array($data);
		$table_name = 'addons';
		
		$allFields = ['id','addon_name', 'description', 'active'];
		$required = ['id','addon_name', 'active'];
		
		$response = validateRequiredFields($data, $required);
		if (!$response['success']) return $response;

		// Dynamically build SET clause
		$setClause = implode(', ', array_map(fn($f) => "$f = ?", $allFields));

		// Prepare the SQL
		$sql = "UPDATE $table_name SET $setClause WHERE id = ?";

		// Prepare and bind
		$stmt = $this->conn->prepare($sql);
		if (!$stmt) {
			return ['success' => false, 'error' => 'Prepare failed: ' . $this->conn->error];
		}

		// Create types string dynamically
		$types = str_repeat('s', count($allFields)) . 'i'; // Assuming all fields are string except id
		$params = array_map(fn($f) => $data[$f] ?? null, $allFields);
		$params[] = $data['id']; // Append ID at the end

		// Bind using ...$params with call_user_func_array
		$stmt->bind_param($types, ...$params);

		// Execute
		if ($stmt->execute()) {
			return ['success' => true, 'message' => 'Product updated successfully'];
		} else {
			return ['success' => false, 'error' => 'Execute failed: ' . $stmt->error];
		}
    }
	
	public function getAddons() {
		$sql = "SELECT * FROM addons WHERE active=1";
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$result = $stmt->get_result();
		$rows = $result->fetch_assoc();
		return $rows;
	}
	
	public function getProductAddons($productId) {
		$sql = "SELECT a.id, p.addon_id, p.product_id,a.addon_name ,a.description 
				FROM addons a
				LEFT JOIN product_adons p ON p.addon_id=a.id AND p.product_id=?
				ORDER BY a.id ASC";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $productId);
		$stmt->execute();
		$result = $stmt->get_result();
		$products = array();
		while ($row = $result->fetch_assoc()) {
			$products[] = [
			'id' => $row['id'],
			'addon_id' => $row['addon_id'],
			'addon_name' => $row['addon_name'],
			'description' => $row['description'],
		];
		}
		return $products;
	}
	
	public function syncProductAddons($data) {
		$productId = $data['product_id'];
		$addons = $data['addon'];
		
		// 1. Delete old
		$deleteSql = "DELETE FROM product_adons WHERE product_id = ?";
		$deleteStmt = $this->conn->prepare($deleteSql);
		$deleteStmt->bind_param("i", $productId);
		$deleteStmt->execute();

		// 2. Insert new
		$insertSql = "INSERT INTO product_adons (product_id, addon_id, created_at) VALUES (?, ?, NOW())";
		$insertStmt = $this->conn->prepare($insertSql);

		foreach ($addons as $addonId => $value) {
			$insertStmt->bind_param("ii", $productId, $addonId);
			$insertStmt->execute();
		}

		return ['success' => true, 'message' => 'Add-ons mapping saved'];
	}

    public function delete($id) {
		header('Content-Type: application/json');
		
		if ($id == '') {
			echo json_encode(['success' => false, 'message' => 'ID fields are not available.']);
			exit;
		}
		
		$sql = "DELETE FROM addons WHERE id = ?";
		$stmt = $this->conn->prepare($sql);

		if (!$stmt) {
			echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error]);
			exit;
		}

		$stmt->bind_param("i",$id);

		if ($stmt->execute()) {
			echo json_encode(['success' => true, 'message' => 'Addons deleted successfully.']);
			exit;
		} else {
			echo json_encode(['success' => false, 'message' => 'Deletion failed: ' . $stmt->error]);
			exit;
		}
    }
	
}
/*
CREATE TABLE addons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE item_addon_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    addon_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE, -- optional
    is_optional BOOLEAN DEFAULT TRUE, -- optional
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (addon_id) REFERENCES addons(id)
);
CREATE TABLE invoice_item_addons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_item_id INT NOT NULL,
    addon_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (invoice_item_id) REFERENCES invoice_items(id),
    FOREIGN KEY (addon_id) REFERENCES addons(id)
);

*/
?>