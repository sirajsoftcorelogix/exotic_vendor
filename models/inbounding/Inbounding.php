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
                $status_filter = $this->conn->real_escape_string($status_filter);   
                $where = "WHERE is_active = '$status_filter'";
            }
        }

        // total records
        $resultCount = $this->conn->query("SELECT COUNT(*) AS total FROM vp_inbound $where");
        $rowCount = $resultCount->fetch_assoc();
        $totalRecords = $rowCount['total'];

        $totalPages = ceil($totalRecords / $limit);

        // fetch data - ADDED ORDER BY id DESC HERE
        $sql = "SELECT * FROM vp_inbound $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
        $result = $this->conn->query($sql);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        // return structured data
        return [
            'inbounding'       => $data,
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

    // Fetch all images for a specific item
    public function getitem_imgs($item_id) {
        $item_id = intval($item_id);
        $result = $this->conn->query("SELECT * FROM `item_images` WHERE item_id = $item_id");
        
        $images = [];
        if ($result) {
            $images = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        }
        return $images;
    }

    // Insert new image record
    public function add_image($item_id, $filename) {
        $stmt = $this->conn->prepare("INSERT INTO `item_images` (item_id, file_name) VALUES (?, ?)");
        $stmt->bind_param("is", $item_id, $filename);
        return $stmt->execute();
    }

    // Delete image from DB and Server
    public function delete_image($img_id) {
        $img_id = intval($img_id);
        
        // 1. Get filename to delete from disk
        $res = $this->conn->query("SELECT file_name FROM `item_images` WHERE id = $img_id");
        if ($row = $res->fetch_assoc()) {
            // Use the exact same path defined in the controller
            $path = __DIR__ . '/../uploads/itm_img/' . $row['file_name'];
            if (file_exists($path)) {
                unlink($path);
            }
        }
        
        // 2. Delete from DB
        return $this->conn->query("DELETE FROM `item_images` WHERE id = $img_id");
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
        $sql4 = "SELECT * FROM `category`";
        $result4 = $this->conn->query($sql4);

        if ($result4) {
            $category = $result4->fetch_all(MYSQLI_ASSOC);
            $result4->free();
        }
        return [
            'form1'   => $inbounding,
            'vendors' => $vendors,
            'category' => $category
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
        // 1. Prepare the query with placeholders (?)
        $sql = "UPDATE vp_inbound 
                SET vendor_code = ?, 
                    invoice_image = ?, 
                    invoice_no = ? 
                WHERE id = ?";

        // 2. Prepare statement
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            // Optional: Error handling
            // echo "Prepare failed: (" . $this->conn->errno . ") " . $this->conn->error;
            return false;
        }

        // 3. Extract and cast variables
        $id = intval($data['id']);
        $vendor_code = $data['vendor_id'];
        $invoice_img = $data['invoice'];
        $invoice_no  = $data['invoice_no'];

        // 4. Bind Parameters
        // "sssi" means: String, String, String, Integer
        $stmt->bind_param("sssi", $vendor_code, $invoice_img, $invoice_no, $id);

        // 5. Execute and return result
        return $stmt->execute();
    }
    public function updateForm3($id, $data) {
        // 1. The SQL has 11 placeholders (?)
        $sql = "UPDATE vp_inbound 
                SET gate_entry_date_time = ?, 
                    material_code = ?, 
                    height = ?, 
                    width = ?, 
                    depth = ?, 
                    weight = ?, 
                    color = ?, 
                    received_by_user_id = ?,
                    dimention_unit = ?,
                    weight_unit = ?
                WHERE id = ?";
                
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return [
                'success' => false,
                'message' => "Prepare failed: " . $this->conn->error
            ];
        }

        // 2. Corrected bind_param
        // The type string "sssssssissi" corresponds to the 11 variables below:
        // s (string), s (string), s (string), s (string), s (string), s (string), s (string), i (int), s (string), s (string), i (int)
        $stmt->bind_param(
            "sssssssissi", 
            $data['gate_entry_date_time'], // 1. Matches gate_entry_date_time
            $data['material_code'],        // 2. Matches material_code
            $data['height'],               // 3. Matches height
            $data['width'],                // 4. Matches width
            $data['depth'],                // 5. Matches depth
            $data['weight'],               // 6. Matches weight
            $data['color'],                // 7. Matches color
            $data['received_by_user_id'],  // 8. Matches received_by_user_id (int)
            $data['dimention_unit'],       // 9. Matches dimention_unit (Added this)
            $data['weight_unit'],          // 10. Matches weight_unit (Added this)
            $id                            // 11. Matches WHERE id (int)
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
    // --- Add these functions inside your InboundingModel class ---

    /**
     * Helper to get category/group name by ID
     */
    public function getCategoryName($id) {
        if (empty($id)) return '';
        
        // Prepare statement to prevent injection and ensure connection usage
        $stmt = $this->conn->prepare("SELECT display_name FROM category WHERE id = ?");
        if (!$stmt) return ''; // Error handling
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['display_name'];
        }
        return '';
    }

    /**
     * Helper to get the next incremental ID count from vp_products
     */
    public function getNextProductCount() {
        // We run a simple query to count existing rows
        $result = $this->conn->query("SELECT COUNT(*) as total FROM vp_products");
        if ($row = $result->fetch_assoc()) {
            return (int)$row['total'] + 1;
        }
        return 1; // Default to 1 if table is empty
    }
    public function saveform2($id,$data) {
         global $conn;
        $sql = "UPDATE vp_inbound SET vendor_code = ?, invoice_image = ?,temp_code = ?,invoice_no = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Prepare failed: ' . $this->conn->error
            ];
        }
        $stmt->bind_param('sssii',
            $data['vendor_id'],   
            $data['invoice'],  
            $data['temp_code'],  
            $data['invoice_no'],  
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
            'ssdddssiii',
            $data['gate_entry_date_time'], // s
            $data['material_code'],        // s
            $data['height'],               // d
            $data['width'],                // d
            $data['depth'],                // d
            $data['weight'],               // d
            $data['color'],                // s
            $data['Quantity'],             // i
            $data['received_by_user_id'],  // i
            $id                            // i
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