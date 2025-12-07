<?php
class Inbounding {
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
            $where = "WHERE team_name LIKE('%$search%') AND is_active = '$status_filter'";
        } else {
            if (!empty($search)) {
                $search = $this->conn->real_escape_string($search);
                $where = "WHERE team_name LIKE('%$search%')";
            }

            if (!empty($status_filter)) {
                $search = $this->conn->real_escape_string($status_filter);   
                $where = "WHERE is_active = '$status_filter'";
            }
        }

		// total records
        $resultCount = $this->conn->query("SELECT COUNT(*) AS total FROM vp_inbound $where");
        $rowCount = $resultCount->fetch_assoc();
        $totalRecords = $rowCount['total'];

        $totalPages = ceil($totalRecords / $limit);

        // fetch data
        $sql = "SELECT * FROM vp_inbound $where LIMIT $limit OFFSET $offset";
        $result = $this->conn->query($sql);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        // return structured data
        return [
            'inbounding'        => $data,
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'limit'        => $limit,
            'totalRecords' => $totalRecords,
            'search'       => $search
        ];
	}
   
    public function getItamcode(){
        $result1 = $this->conn->query("SELECT `item_code`,`title` FROM `vp_products`");
        if ($result1) {
            $ItamcodeData = $result1->fetch_all(MYSQLI_ASSOC);
            $result1->free();
        }
        return $ItamcodeData;
    }
	public function getform1data($id){
        $inbounding = null;
        $vendors = null;
        $result = $this->conn->query("SELECT * FROM `vp_inbound` WHERE id = $id");
        $result1 = $this->conn->query("SELECT * FROM `vp_vendors`");
        if ($result) {
            $inbounding = $result->fetch_assoc();
            $result->free();
        }
        if ($result1) {
             $vendors = $result1->fetch_all(MYSQLI_ASSOC);
            $result1->free();
        }
        return [
            'form1'   => $inbounding,
            'vendors' => $vendors
        ];
    }


    public function getform2data($id) {
    // Always secure the ID
    $id = (int)$id;

    $sql = "SELECT vi.*,vv.vendor_name  FROM vp_inbound AS vi LEFT JOIN vp_vendors AS vv ON vi.vendor_code = vv.id WHERE vi.id = $id";
    $result = $this->conn->query($sql);
    $inbounding = [];
    if ($result) {
        $inbounding = $result->fetch_assoc();
        $result->free();
    }
    $sql1 = "SELECT * FROM `vp_users`";
    $result1 = $this->conn->query($sql1);
    if ($result1) {
        $user = $result1->fetch_all(MYSQLI_ASSOC);
        $result1->free();
    }
    $sql2 = "SELECT * FROM `vp_vendors`";
    $result2 = $this->conn->query($sql2);

    if ($result2) {
        $vendors = $result2->fetch_all(MYSQLI_ASSOC);
        $result2->free();
    }
    $sql3 = "SELECT * FROM `material`";
    $result3 = $this->conn->query($sql3);

    if ($result3) {
        $material = $result3->fetch_all(MYSQLI_ASSOC);
        $result3->free();
    }
    $sql4 = "SELECT * FROM `category`";
    $result4 = $this->conn->query($sql4);

    if ($result4) {
        $category = $result4->fetch_all(MYSQLI_ASSOC);
        $result4->free();
    }
    return [
        'form2' => $inbounding,
        'user'  => $user,
        'vendors' => $vendors,
        'material' => $material,
        'category' => $category
    ];
}
    public function getform2($id) {
        $result = $this->conn->query("SELECT * FROM `vp_inbound` where id=$id");
        if ($result) {
            $inbounding = $result->fetch_assoc();
            $result->free();
        }
        return [
            'inbounding'        => $inbounding
        ];
    }
    public function getAllInbounding() {
        $result = $this->conn->query("SELECT * FROM `vp_inbound` ORDER BY id ASC");
        $inbounding = [];
        while ($row = $result->fetch_assoc()) {
            $inbounding[] = $row;
        }
        return $inbounding;
    }
    public function saveform1($data) {
        global $conn; // DB connection
        $category = $data['category'];
        $photo    = $data['photo'];
        $sql = "INSERT INTO vp_inbound (category_code, product_photo)
                VALUES ('$category', '$photo')";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            return mysqli_insert_id($conn);
        } else {
            return false;
        }
    }
    public function updateform1($data) {
        global $conn;

        $id       = intval($data['id']);
        $category = mysqli_real_escape_string($conn, $data['category']);
        $photo    = mysqli_real_escape_string($conn, $data['photo']);

        $sql = "UPDATE vp_inbound 
                SET category_code='$category', 
                    product_photo='$photo'
                WHERE id=$id";

        return mysqli_query($conn, $sql);
    }
    
    public function updateform2($data) {
        global $conn;

        $id       = intval($data['id']);
        $vendor_code = mysqli_real_escape_string($conn, $data['vendor_id']);
        $invoice    = mysqli_real_escape_string($conn, $data['invoice']);
        $sql = "UPDATE vp_inbound 
                SET vendor_code='$vendor_code', 
                    invoice_image='$invoice'
                WHERE id=$id";

        return mysqli_query($conn, $sql);
    }
    public function updateForm3($id, $data){
        $sql = "UPDATE vp_inbound 
                SET gate_entry_date_time = ?, 
                    material_code = ?, 
                    height = ?, 
                    width = ?, 
                    depth = ?, 
                    weight = ?, 
                    color = ?, 
                    quantity_received = ?, 
                    item_code = ?,
                    received_by_user_id = ?
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => "Prepare failed: " . $this->conn->error
            ];
        }
        $stmt->bind_param(
            "sssiiiiisii",
            $data['gate_entry_date_time'],
            $data['material_code'],
            $data['height'],
            $data['width'],
            $data['depth'],
            $data['weight'],
            $data['color'],
            $data['quantity_received'],
            $data['item_code'],
            $data['received_by_user_id'],
            $id
        );
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => "Record updated successfully."
            ];
        }
        return [
            'success' => false,
            'message' => "Update failed: " . $stmt->error
        ];
    }
    public function updatedesktopform($id, $data) {
        if (isset($data['id'])) unset($data['id']);
        $cols = [];
        $values = [];
        $types = "";
        foreach ($data as $key => $val) {
            if ($val !== '' && $val !== null) {
                $cols[] = "$key = ?";
                $values[] = $val;
                if (is_int($val)) {
                    $types .= "i";
                } elseif (is_float($val) || (is_numeric($val) && strpos((string)$val, '.') !== false)) {
                    $types .= "d";
                } else {
                    $types .= "s";
                }
            }
        }
        if (empty($cols)) {
            return ['success' => true, 'message' => "No changes made (all fields were empty)."];
        }
        $types .= "i";
        $values[] = $id;
        $sql = "UPDATE vp_inbound SET " . implode(', ', $cols) . " WHERE id = ?";        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => "Prepare failed: " . $this->conn->error];
        }
        $stmt->bind_param($types, ...$values);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => "Record updated successfully."];
        }
        return ['success' => false, 'message' => "Update failed: " . $stmt->error];
    }
    public function saveform2($id,$data) {
         global $conn;
        $sql = "UPDATE vp_inbound SET vendor_code = ?, invoice_image = ?,temp_code = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Prepare failed: ' . $this->conn->error
            ];
        }
        $stmt->bind_param('sssi',
            $data['vendor_id'],   
            $data['invoice'],  
            $data['temp_code'],  
            $id              
        );
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Record updated successfully.'
            ];
        }
        return [
            'success' => false,
            'message' => 'Update failed: ' . $stmt->error
        ];
    }
    public function saveform3($id, $data) {
        $sql = "UPDATE vp_inbound SET 
            gate_entry_date_time = ?,
            material_code = ?,
            height = ?,
            width = ?,
            depth = ?,
            weight = ?,
            color = ?,
            quantity_received = ?,
            Item_code = ?,
            received_by_user_id = ?
        WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Prepare failed: ' . $this->conn->error
            ];
        }
        $stmt->bind_param(
            'ssdddssisi',
            $data['gate_entry_date_time'], // s
            $data['material_code'],        // s
            $data['height'],               // d
            $data['width'],                // d
            $data['depth'],                // d
            $data['weight'],               // d
            $data['color'],                // s
            $data['Quantity'],             // i
            $data['Item_code'],            // s
            $data['received_by_user_id'],   // i
            $id                             // i
        );
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Record updated successfully.'
            ];
        }
        return [
            'success' => false,
            'message' => 'Update failed: ' . $stmt->error
        ];
    }


    public function updateRecord($id, $data) {
        $sql = "UPDATE vp_inbound SET name = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si',
            $data['name'],
            $id
        );

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Record updated successfully.'];
        }

        return [
            'success' => false,
            'message' => 'Update failed: ' . $stmt->error . '. Please check your input and try again.'
        ];
    }
    public function isCodeExists($code) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS c FROM vp_inbound WHERE temp_code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['c'] > 0;
    }

}
?>