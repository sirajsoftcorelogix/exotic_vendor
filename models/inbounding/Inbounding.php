<?php
class Inbounding {
    private $conn;
    public function __construct($db) {
        $this->conn = $db;
    }
    // models/inbounding/InboundingModel.php (or wherever your getAll is defined)

    public function getAll($page = 1, $limit = 10, $search = '', $status_filter = '') {
        $page = (int)$page;
        if ($page < 1) $page = 1;

        $limit = (int)$limit;
        if ($limit < 1) $limit = 10;

        $offset = ($page - 1) * $limit;
        $where = [];

        // 1. Search Logic (Item Code, Title, Keywords)
        if (!empty($search)) {
            $search = $this->conn->real_escape_string($search);
            // Using OR logic for broad search
            $where[] = "(Item_code LIKE '%$search%' OR product_title LIKE '%$search%' OR key_words LIKE '%$search%')";
        }

        // 2. Status Filter
        if (!empty($status_filter)) {
            $status_filter = $this->conn->real_escape_string($status_filter);
            $where[] = "is_active = '$status_filter'";
        }

        // Combine WHERE clauses
        $whereSql = "";
        if (!empty($where)) {
            $whereSql = "WHERE " . implode(' AND ', $where);
        }

        // Total Count
        $resultCount = $this->conn->query("SELECT COUNT(*) AS total FROM vp_inbound $whereSql");
        $rowCount = $resultCount->fetch_assoc();
        $totalRecords = $rowCount['total'];
        $totalPages = ceil($totalRecords / $limit);

        // Fetch Data
        $sql = "SELECT * FROM vp_inbound $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset";
        $result = $this->conn->query($sql);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return [
            'inbounding'   => $data,
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

    // 1. Fetch all images for a specific item (UPDATED: Now sorts by Display Order)
    public function getitem_imgs($item_id) {
        $item_id = intval($item_id);
        // Added ORDER BY display_order ASC so images appear in the correct sequence
        $result = $this->conn->query("SELECT * FROM `item_images` WHERE item_id = $item_id ORDER BY display_order ASC");
        
        $images = [];
        if ($result) {
            $images = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        }
        return $images;
    }

    // 2. Fetch Item Details with Joins (NEW: For the top info header)
    public function getItemDetails($id) {
        $id = intval($id);
        
        // This query joins your tables to get real names (e.g., 'Brass' instead of '1')
        // Adjust table names (e.g., 'vendors' or 'vp_vendors') if needed
        $sql = "SELECT vi.*,c.display_name as category,vv.vendor_name as vendor_name,m.material_name as material,vu.name as recived_by_name FROM vp_inbound as vi
            LEFT JOIN category as c on vi.group_name=c.category
            LEFT JOIN vp_vendors as vv on vi.vendor_code=vv.id
            LEFT JOIN material as m on vi.material_code=m.id
            LEFT JOIN vp_users as vu on vi.received_by_user_id=vu.id
            WHERE vi.id=".$id;
                
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_assoc() : [];
    }

    // 3. Insert new image record (KEPT EXISTING)
    public function add_image($item_id, $filename) {
        // Default order is 0, Caption is NULL
        $stmt = $this->conn->prepare("INSERT INTO `item_images` (item_id, file_name, display_order, image_caption) VALUES (?, ?, 0, '')");
        $stmt->bind_param("is", $item_id, $filename);
        return $stmt->execute();
    }

    // 4. Update Image Metadata (NEW: For Captions & Order)
    public function update_image_meta($img_id, $caption, $order) {
        $img_id = intval($img_id);
        $order = intval($order);
        $caption = $this->conn->real_escape_string($caption);
        
        $sql = "UPDATE item_images SET image_caption = '$caption', display_order = $order WHERE id = $img_id";
        $this->conn->query($sql);
    }
    public function update_image_order($img_id, $order) {
        $img_id = intval($img_id);
        $order = intval($order);
        // SQL that ONLY updates the display_order
        $this->conn->query("UPDATE item_images SET display_order = $order WHERE id = $img_id");
    }
    // 5. Delete image from DB and Server (KEPT EXISTING)
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
    public function get_raw_item_imgs($item_id) {
        $item_id = intval($item_id);
        // Added ORDER BY display_order ASC so images appear in the correct sequence
        $result = $this->conn->query("SELECT * FROM `item_raw_images` WHERE item_id = $item_id");
        
        $images = [];
        if ($result) {
            $images = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        }
        return $images;
    }
    // 1. Fetch Raw Images
    public function get_raw_imgs($item_id) {
        $item_id = intval($item_id);
        // No order needed, usually just by upload date
        $result = $this->conn->query("SELECT * FROM `item_raw_images` WHERE item_id = $item_id ORDER BY id DESC");
        
        $images = [];
        if ($result) {
            $images = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        }
        return $images;
    }

    // 2. Add Raw Image
    public function add_raw_image($item_id, $filename) {
        $stmt = $this->conn->prepare("INSERT INTO `item_raw_images` (item_id, file_name) VALUES (?, ?)");
        $stmt->bind_param("is", $item_id, $filename);
        return $stmt->execute();
    }

    // 3. Delete Raw Image
    public function delete_raw_image($img_id) {
        $img_id = intval($img_id);
        
        // Get filename to delete from disk
        $res = $this->conn->query("SELECT file_name FROM `item_raw_images` WHERE id = $img_id");
        if ($row = $res->fetch_assoc()) {
            // Note the folder change here: 'itm_raw_img'
            $path = __DIR__ . '/../uploads/itm_raw_img/' . $row['file_name'];
            if (file_exists($path)) {
                unlink($path);
            }
        }
        
        return $this->conn->query("DELETE FROM `item_raw_images` WHERE id = $img_id");
    }
    public function getExportData($ids_array = []) { // 1. Fixed argument name
        // 2. Safety check for empty array
        if (empty($ids_array)) return false;

        // 3. Ensure IDs are integers for security
        $ids_clean = implode(',', array_map('intval', $ids_array));
        
        // 4. Added the missing Category Join so the Excel column isn't empty
        // 5. Fixed table names back to standard ones (vp_vendors, etc.)
        $sql = "SELECT 
                    vi.*,
                    grp.display_name AS group_real_name,
                    v.vendor_name AS vendor_real_name,
                    m.material_name AS material_real_name
                FROM vp_inbound vi
                LEFT JOIN category grp ON vi.group_name = grp.category    -- Join for Category Name
                LEFT JOIN vp_vendors v ON vi.vendor_code = v.id        -- Join for Vendor
                LEFT JOIN material m   ON vi.material_code = m.id      -- Join for Material
                WHERE vi.id IN ($ids_clean)";

        return $this->conn->query($sql);
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
    $sql5 = "SELECT * FROM `exotic_address`";
    $result5 = $this->conn->query($sql5);

    if ($result5) {
        $address = $result5->fetch_all(MYSQLI_ASSOC);
        $result5->free();
    }
    return [
        'form2' => $inbounding,
        'user'  => $user,
        'vendors' => $vendors,
        'material' => $material,
        'category' => $category,
        'address' => $address
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
        $sql = "INSERT INTO vp_inbound (group_name, product_photo)
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
                SET group_name='$category', 
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
        $cols = []; $values = []; $types = "";

        foreach ($data as $key => $val) {
            if ($val !== '' && $val !== null) {
                $cols[] = "$key = ?";
                $values[] = $val;
                
                // Type logic: integers, floats, or default to string
                if (is_int($val)) $types .= "i";
                elseif (is_float($val)) $types .= "d";
                else $types .= "s"; // Handles your comma-separated strings and Group Name strings
            }
        }

        if (empty($cols)) return ['success' => true, 'message' => "No changes made."];

        $types .= "i"; 
        $values[] = $id;

        $sql = "UPDATE vp_inbound SET " . implode(', ', $cols) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['success' => false, 'message' => "Prepare failed: " . $this->conn->error];
        
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) return ['success' => true, 'message' => "Updated successfully."];
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
    public function getGroupNameByCode($code) {
        if (empty($code)) return '';

        // Search by the 'category' column, NOT 'id'
        $stmt = $this->conn->prepare("SELECT display_name FROM category WHERE category = ?");
        if (!$stmt) return '';

        // Use 's' (string) because your codes might be "-2" or "10002"
        $stmt->bind_param("s", $code); 
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return $row['display_name'];
        }
        return ''; // Return empty if not found
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