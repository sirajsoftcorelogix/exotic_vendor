<?php
class Inbounding {
    private $conn;
    public function __construct($db) {
        $this->conn = $db;
    }
    // models/inbounding/InboundingModel.php (or wherever your getAll is defined)

    public function getAll($page = 1, $limit = 10, $search = '', $filters = []) {
        $page = (int)$page;
        if ($page < 1) $page = 1;

        $limit = (int)$limit;
        if ($limit < 1) $limit = 10;

        $offset = ($page - 1) * $limit;
        $where = [];

        // 1. Text Search (using vi alias for vp_inbound)
        if (!empty($search)) {
            $search = $this->conn->real_escape_string($search);
            // Note: Added 'vi.' prefix to fields to prevent ambiguity
            $where[] = "(vi.Item_code LIKE '%$search%' OR vi.product_title LIKE '%$search%' OR vi.key_words LIKE '%$search%')";
        }

        // 2. Vendor Filter
        if (!empty($filters['vendor_code'])) {
            $v_code = $this->conn->real_escape_string($filters['vendor_code']);
            $where[] = "vi.vendor_code = '$v_code'";
        }

        // 3. Agent (Received By) Filter
        if (!empty($filters['received_by_user_id'])) {
            $u_id = (int)$filters['received_by_user_id'];
            $where[] = "vi.received_by_user_id = $u_id";
        }

        // 4. Group Filter
        if (!empty($filters['group_name'])) {
            $g_name = $this->conn->real_escape_string($filters['group_name']);
            $where[] = "vi.group_name = '$g_name'";
        }

        // 5. Status Filter (PENDING Logic)
        if (!empty($filters['status_step'])) {
            $step = $this->conn->real_escape_string($filters['status_step']);
            $where[] = "vi.id NOT IN (SELECT i_id FROM inbound_logs WHERE stat = '$step')";
        }

        // Combine WHERE clauses
        $whereSql = "";
        if (!empty($where)) {
            $whereSql = "WHERE " . implode(' AND ', $where);
        }

        // Total Count (using alias vi)
        $resultCount = $this->conn->query("SELECT COUNT(*) AS total FROM vp_inbound as vi $whereSql");
        $rowCount = $resultCount->fetch_assoc();
        $totalRecords = $rowCount['total'];
        $totalPages = ceil($totalRecords / $limit);

        // Fetch Data (Added JOIN and specific SELECT)
        // We select vi.* (all inbound data) AND c.display_name as 'group_name_display'
        // (I used 'group_name_display' to avoid conflict, or you can overwrite 'group_name' if you prefer)
        $sql = "SELECT vi.*, c.display_name as group_name_display 
                FROM vp_inbound as vi
                LEFT JOIN category as c ON vi.group_name = c.category
                $whereSql 
                ORDER BY vi.id DESC 
                LIMIT $limit OFFSET $offset";
                
        $result = $this->conn->query($sql);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $current_id = (int)$row['id'];
            
            // If you want the main 'group_name' key to be the human readable name, uncomment this:
            // $row['group_name'] = $row['group_name_display']; 

            // Fetch logs
            $log_sql = "SELECT il.*, u.name FROM inbound_logs as il LEFT JOIN vp_users as u on il.userid_log=u.id WHERE il.i_id = $current_id";
            $log_result = $this->conn->query($log_sql);
            
            $logs = [];
            if ($log_result) {
                while ($log_row = $log_result->fetch_assoc()) {
                    $logs[] = $log_row;
                }
            }
            $row['stat_logs'] = $logs;
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

    // --- NEW HELPER FOR DROPDOWNS ---
    // --- HELPER FOR FILTERS ---
    public function getFilterDropdowns() {
        $data = ['vendors' => [], 'users' => [], 'groups' => []];

        // 1. Get Vendors (Only those present in vp_inbound)
        $v_sql = "SELECT DISTINCT v.id, v.vendor_name 
                  FROM vp_vendors v 
                  INNER JOIN vp_inbound i ON i.vendor_code = v.id 
                  ORDER BY v.vendor_name ASC";
        $v_res = $this->conn->query($v_sql);
        if($v_res) {
            while($r = $v_res->fetch_assoc()) { 
                $data['vendors'][] = $r; 
            }
        }

        // 2. Get Agents / Users (Only those present in vp_inbound)
        $u_sql = "SELECT DISTINCT u.id, u.name 
                  FROM vp_users u 
                  INNER JOIN vp_inbound i ON i.received_by_user_id = u.id 
                  ORDER BY u.name ASC";
        $u_res = $this->conn->query($u_sql);
        if($u_res) {
            while($r = $u_res->fetch_assoc()) { 
                $data['users'][] = $r; 
            }
        }

        // 3. Get Groups (Joined with Category Table)
        // Logic: vp_inbound.group_name stores the ID -> matches vp_categories.category
        $g_sql = "SELECT DISTINCT c.category as id, c.display_name as name 
                  FROM category c 
                  INNER JOIN vp_inbound i ON i.group_name = c.category 
                  WHERE i.group_name != '' 
                  ORDER BY c.display_name ASC";
                  
        $g_res = $this->conn->query($g_sql);
        if($g_res) {
            while($r = $g_res->fetch_assoc()) { 
                $data['groups'][] = $r; 
            }
        }

        return $data;
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
    public function add_image($item_id, $filename, $caption = '', $order = 0, $variation_id = -1) {
        // Added variation_id to query
        $sql = "INSERT INTO `item_images` (item_id, file_name, display_order, image_caption, variation_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        // types: integer, string, integer, string, integer
        $stmt->bind_param("isisi", $item_id, $filename, $order, $caption, $variation_id);
        
        return $stmt->execute();
    }

    // 4. Update Image Metadata (NEW: For Captions & Order)
    public function update_image_meta($img_id, $caption, $order) {
        // 1. Prepare the query with placeholders (?)
        $sql = "UPDATE item_images 
                SET image_caption = ?, 
                    display_order = ? 
                WHERE id = ?";

        // 2. Prepare statement
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            // Optional: Error handling
            // echo "Prepare failed: (" . $this->conn->errno . ") " . $this->conn->error;
            return false;
        }

        // 3. Extract and cast variables
        // It is good practice to ensure integers are actually integers before binding
        $id = intval($img_id);
        $display_order = intval($order);
        // Note: No need for real_escape_string() on $caption when using bind_param

        // 4. Bind Parameters
        // "sii" means: String (caption), Integer (order), Integer (id)
        $stmt->bind_param("sii", $caption, $display_order, $id);

        // 5. Execute and return result
        return $stmt->execute();
    }
    public function update_image_order($img_id, $order) {
        // 1. Prepare the query with placeholders (?)
        $sql = "UPDATE item_images 
                SET display_order = ? 
                WHERE id = ?";

        // 2. Prepare statement
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            // Optional: Error handling
            return false;
        }

        // 3. Extract and cast variables
        $id = intval($img_id);
        $display_order = intval($order);

        // 4. Bind Parameters
        // "ii" means: Integer (order), Integer (id)
        $stmt->bind_param("ii", $display_order, $id);

        // 5. Execute and return result
        return $stmt->execute();
    }
    // 5. Delete image from DB and Server (KEPT EXISTING)
    public function delete_image($img_id) {
        $id = intval($img_id);

        // ---------------------------------------------------------
        // 1. Get filename to delete from disk (SELECT)
        // ---------------------------------------------------------
        $sql_select = "SELECT file_name FROM item_images WHERE id = ?";
        $stmt = $this->conn->prepare($sql_select);

        if ($stmt) {
            // Bind ID (Integer)
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Get the result set
            $res = $stmt->get_result();

            if ($row = $res->fetch_assoc()) {
                // Use the exact same path defined in the controller
                $path = __DIR__ . '/../uploads/itm_img/' . $row['file_name'];
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            // CRITICAL: Close the first statement before creating a new one
            $stmt->close(); 
        }

        // ---------------------------------------------------------
        // 2. Delete from DB
        // ---------------------------------------------------------
        $sql_delete = "DELETE FROM item_images WHERE id = ?";
        $stmt = $this->conn->prepare($sql_delete);

        if (!$stmt) {
            return false;
        }

        // Bind ID (Integer)
        $stmt->bind_param("i", $id);

        // Execute and return result
        return $stmt->execute();
    }
    public function get_temp_code($item_id){
        $result = $this->conn->query("SELECT temp_code FROM `vp_inbound` WHERE id = $item_id");
        $id = intval($item_id);
        $sql = "SELECT temp_code FROM vp_inbound WHERE id = $id";
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
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
        $id = intval($img_id);

        // ---------------------------------------------------------
        // 1. Get filename to delete from disk (SELECT)
        // ---------------------------------------------------------
        $sql_select = "SELECT file_name FROM item_raw_images WHERE id = ?";
        $stmt = $this->conn->prepare($sql_select);

        if ($stmt) {
            // Bind ID (Integer)
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Get the result set
            $res = $stmt->get_result();

            if ($row = $res->fetch_assoc()) {
                // Note the folder change here: 'itm_raw_img'
                $path = __DIR__ . '/../uploads/itm_raw_img/' . $row['file_name'];
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            // CRITICAL: Close the first statement before creating a new one
            $stmt->close();
        }

        // ---------------------------------------------------------
        // 2. Delete from DB
        // ---------------------------------------------------------
        $sql_delete = "DELETE FROM item_raw_images WHERE id = ?";
        $stmt = $this->conn->prepare($sql_delete);

        if (!$stmt) {
            return false;
        }

        // Bind ID (Integer)
        $stmt->bind_param("i", $id);

        // Execute and return result
        return $stmt->execute();
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
	public function getform1data($id) {
        $id = intval($id);
        $inbounding = null;
        $vendors = null;
        $category = null;

        // ---------------------------------------------------------
        // 1. Secure Query: Get specific inbound record (Requires Prepared Statement)
        // ---------------------------------------------------------
        $sql_inbound = "SELECT * FROM vp_inbound WHERE id = ?";
        $stmt = $this->conn->prepare($sql_inbound);

        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $res = $stmt->get_result();
            if ($res) {
                $inbounding = $res->fetch_assoc();
            }
            // Critical: Close statement to free the connection for the next queries
            $stmt->close();
        }

        // ---------------------------------------------------------
        // 2. Static Query: Get all vendors (Standard query is safe here)
        // ---------------------------------------------------------
        $res_vendors = $this->conn->query("SELECT * FROM vp_vendors");
        if ($res_vendors) {
            $vendors = $res_vendors->fetch_all(MYSQLI_ASSOC);
            // Free result set memory
            $res_vendors->free(); 
        }

        // ---------------------------------------------------------
        // 3. Static Query: Get all categories (Standard query is safe here)
        // ---------------------------------------------------------
        $res_cat = $this->conn->query("SELECT * FROM category");
        if ($res_cat) {
            $category = $res_cat->fetch_all(MYSQLI_ASSOC);
            // Free result set memory
            $res_cat->free();
        }

        // Return the combined array
        return [
            'form1'    => $inbounding,
            'vendors'  => $vendors,
            'category' => $category
        ];
    }
public function update_image_variation($img_id, $variation_id) {
    // Updates the variation_id for a specific image
    $sql = "UPDATE item_images SET variation_id = ? WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    
    if (!$stmt) return false;

    // "ii" = Integer, Integer
    $stmt->bind_param("ii", $variation_id, $img_id);
    
    return $stmt->execute();
}

    public function getform2data($id) {
        $id = (int)$id;

        // 1. Get Main Inbound Data
        $sql = "SELECT vi.*, vv.vendor_name FROM vp_inbound AS vi LEFT JOIN vp_vendors AS vv ON vi.vendor_code = vv.id WHERE vi.id = $id";
        $result = $this->conn->query($sql);
        $inbounding = $result ? $result->fetch_assoc() : [];

        // 2. Fetch Helper Tables (Users, Vendors, Materials, etc.)
        $user = $this->conn->query("SELECT * FROM `vp_users`")->fetch_all(MYSQLI_ASSOC);
        $vendors = $this->conn->query("SELECT * FROM `vp_vendors`")->fetch_all(MYSQLI_ASSOC);
        $material = $this->conn->query("SELECT * FROM `material`")->fetch_all(MYSQLI_ASSOC);
        $category = $this->conn->query("SELECT * FROM `category`")->fetch_all(MYSQLI_ASSOC);
        $address = $this->conn->query("SELECT * FROM `exotic_address`")->fetch_all(MYSQLI_ASSOC);

        // 3. NEW: Fetch Variations linked to this item
        $variations = [];
        $sqlVar = "SELECT * FROM `vp_variations` WHERE it_id = $id ORDER BY id ASC";
        $resVar = $this->conn->query($sqlVar);
        if($resVar) {
            $variations = $resVar->fetch_all(MYSQLI_ASSOC);
        }

        return [
            'form2'      => $inbounding,
            'user'       => $user,
            'vendors'    => $vendors,
            'material'   => $material,
            'category'   => $category,
            'address'    => $address,
            'variations' => $variations // <--- Added this
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
    public function saveform1($record_id, $data) {
        $vendor_code = $data['vendor_id'] ?? '';
        $invoice_img = $data['invoice'] ?? '';
        $invoice_no  = $data['invoice_no'] ?? '';

        $sql = "INSERT INTO vp_inbound (vendor_code, invoice_image, invoice_no) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return false;
        }

        // Changed 'isi' to 'sss' to prevent errors if invoice_no or vendor_code contain letters
        $stmt->bind_param("sss", $vendor_code, $invoice_img, $invoice_no);

        if ($stmt->execute()) {
            return $stmt->insert_id;
        }

        return false;
    }
    public function stat_logs($value = []) {
        // echo "<pre>";print_r($value);exit;
        // 1. Extract variables safely
        $stat       = $value['stat'] ?? '';
        $userid_log = $value['userid_log'] ?? 0;
        $i_id       = $value['i_id'] ?? 0;

        // 2. CHECK: Does this specific log entry already exist?
        // We check for a match on ALL 3 fields (i_id, stat, userid_log)
        $checkSql = "SELECT id FROM `inbound_logs` WHERE `i_id` = ? AND `stat` = ? AND `userid_log` = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        
        if (!$checkStmt) {
            return false;
        }

        $checkStmt->bind_param("isi", $i_id, $stat, $userid_log);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        $exists = $checkStmt->num_rows > 0; // True if match found
        $checkStmt->close();

        // 3. ACTION: Update or Insert based on check
        $stmt = null;

        if ($exists) {
            // --- UPDATE Existing Row ---
            $sql = "UPDATE `inbound_logs` 
                    SET `modified_at` = NOW() 
                    WHERE `i_id` = ? AND `stat` = ? AND `userid_log` = ?";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            
            $stmt->bind_param("isi", $i_id, $stat, $userid_log);

        } else {
            // --- INSERT New Row ---
            $sql = "INSERT INTO `inbound_logs` (`id`, `i_id`, `stat`, `userid_log`, `created_at`, `modified_at`) 
                    VALUES (NULL, ?, ?, ?, NOW(), NULL)";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            
            $stmt->bind_param("isi", $i_id, $stat, $userid_log);
        }

        // 4. Execute final query
        return $stmt->execute();
    }

    public function updateform1($data) {
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
    // 2. UNIFIED SAVE/UPDATE FUNCTION
    
   
    // --- Add this to your Inbounding.php Model ---
    public function getById($id) {
        $id = (int)$id;
        $sql = "SELECT * FROM vp_inbound WHERE id = $id";
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }
    public function getlabeldata($id){
        $sql = "SELECT v.*,c.display_name as category,m.material_name,vv.vendor_name as vendor_name,ea.address_title as location  FROM vp_inbound as v 
        LEFT JOIN category as c on v.group_name=c.category
        LEFT JOIN material as m on v.material_code=m.id
        LEFT JOIN vp_vendors as vv on v.vendor_code=vv.id
        LEFT JOIN exotic_address as ea on v.ware_house_code = ea.id
        WHERE v.id = $id";
        $result = $this->conn->query($sql);
        $inbounding = [];
        if ($result) {
            $labeldata = $result->fetch_assoc();
            $result->free();
        }
        return [
            'form2' => $labeldata
        ];
    }
    // 1. Fetch Category Name
    public function getCategoryById($id) {
        $sql = "SELECT * FROM category WHERE category = '$id'"; // Check your actual column name
        $result = $this->conn->query($sql);
        return $result->fetch_assoc();
    }

    // 2. Fetch Material Name
    public function getMaterialById($id) {
        $sql = "SELECT * FROM material WHERE id = '$id'"; // Check your actual table name
        $result = $this->conn->query($sql);
        return $result->fetch_assoc();
    }

    // 3. Generate Incremental Temp Code
    public function generateNextTempCode($prefix) {
        // Search for codes like 'BSB%'
        // Order by length first (to sort 10 after 9), then value DESC
        $sql = "SELECT temp_code FROM vp_inbound 
                WHERE temp_code LIKE '$prefix%' 
                AND temp_code REGEXP '^{$prefix}[0-9]+$' 
                ORDER BY LENGTH(temp_code) DESC, temp_code DESC LIMIT 1";
                
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $lastCode = $row['temp_code'];
            
            // Extract number (Remove prefix)
            $numberStr = substr($lastCode, 3); 
            $number = (int)$numberStr;
            $nextNumber = $number + 1;
        } else {
            // No existing code found, start at 1
            $nextNumber = 1;
        }

        // Pad with zeros (e.g., 1 -> 001)
        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    // 1. UPDATE MAIN TABLE (Includes Variant 1 Data)
    public function updateMainInbound($id, $data) {
        // 1. EXTRACT DATA DIRECTLY FROM ARGUMENT
        // The Controller now passes exact keys, so we just use them.
        
        $height = (float) ($data['height'] ?? 0);
        $width  = (float) ($data['width']  ?? 0);
        $depth  = (float) ($data['depth']  ?? 0);
        $weight = (float) ($data['weight'] ?? 0);
        
        $color  = $data['color'] ?? '';
        $size   = $data['size'] ?? '';
        
        // FIX: Read 'quantity_received' directly
        $qty    = (int)   ($data['quantity_received'] ?? 0);
        $cp     = (float) ($data['cp'] ?? 0);
        $photo  = $data['product_photo'] ?? '';

        // NEW FIELDS
        $wh     = (int)   ($data['ware_house_code'] ?? 0);
        $p_ind  = (float) ($data['price_india'] ?? 0);
        $p_mrp  = (float) ($data['price_india_mrp'] ?? 0);

        // 2. UPDATE SQL
        $sql = "UPDATE vp_inbound 
                SET gate_entry_date_time = ?, material_code = ?,  group_name = ?, 
                    height = ?, width = ?, depth = ?, weight = ?, 
                    color = ?, size = ?, cp = ?, quantity_received = ?, 
                    received_by_user_id = ?, temp_code = ?, product_photo = ?,
                    ware_house_code = ?, price_india = ?, price_india_mrp = ?
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['success' => false, 'message' => $this->conn->error];

        // BIND PARAMETERS
        // sssddddssdiissiidd
        $stmt->bind_param(
            'sssddddssdiissiidd', 
            $data['gate_entry_date_time'], 
            $data['material_code'], 
            $data['group_name'], 
            $height, $width, $depth, $weight, 
            $color, $size, $cp, $qty, 
            $data['received_by_user_id'], 
            $data['temp_code'],       
            $photo,
            $wh, $p_ind, $p_mrp,
            $id
        );

        if ($stmt->execute()) return ['success' => true];
        return ['success' => false, 'message' => $stmt->error];
    }

    // 2. SAVE EXTRA VARIATIONS (Delete Old -> Insert New)
    public function saveVariations($it_id, $variations, $temp_code) {
        // 1. Filter out IDs to Keep (Skipping Index 0)
        $submittedIds = [];
        foreach ($variations as $key => $var) {
            if ($key == 0) continue; 
            if (!empty($var['id'])) {
                $submittedIds[] = (int)$var['id'];
            }
        }

        // 2. DELETE old variations
        if (!empty($submittedIds)) {
            $idsStr = implode(',', $submittedIds);
            $sql = "DELETE FROM vp_variations WHERE it_id = $it_id AND id NOT IN ($idsStr)";
            $this->conn->query($sql);
        } else {
            $this->conn->query("DELETE FROM vp_variations WHERE it_id = $it_id");
        }

        // 3. INSERT & UPDATE QUERIES
        // Count: 19 Placeholders (?)
        $insertSql = "INSERT INTO vp_variations 
                      (it_id, temp_code, color, size, quantity_received, cp, variation_image, height, width, depth, weight, ware_house_code, price_india, price_india_mrp, inr_pricing, amazon_price, usd_price, hsn_code, gst_rate) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Count: 18 Sets + 1 Where = 19 Placeholders (?)
        $updateSql = "UPDATE vp_variations 
                      SET temp_code=?, color=?, size=?, quantity_received=?, cp=?, variation_image=?, height=?, width=?, depth=?, weight=?, ware_house_code=?, price_india=?, price_india_mrp=?, inr_pricing=?, amazon_price=?, usd_price=?, hsn_code=?, gst_rate=? 
                      WHERE id=?";

        $stmtInsert = $this->conn->prepare($insertSql);
        $stmtUpdate = $this->conn->prepare($updateSql);

        foreach ($variations as $key => $var) {
            if ($key == 0) continue; 

            $id = $var['id'] ?? '';
            $img = $var['photo'] ?? '';
            
            // Defaults
            $h = !empty($var['height']) ? $var['height'] : 0.00;
            $w = !empty($var['width']) ? $var['width'] : 0.00;
            $d = !empty($var['depth']) ? $var['depth'] : 0.00;
            $wt = !empty($var['weight']) ? $var['weight'] : 0.00;
            $wh = !empty($var['ware_house_code']) ? (int)$var['ware_house_code'] : 0;
            $qty = !empty($var['quantity']) ? (int)$var['quantity'] : 0;
            
            // Pricing defaults
            $pi = !empty($var['price_india']) ? (float)$var['price_india'] : 0.00;
            $pm = !empty($var['price_india_mrp']) ? (float)$var['price_india_mrp'] : 0.00;
            $inr = !empty($var['inr_pricing']) ? (float)$var['inr_pricing'] : 0.00;
            $amz = !empty($var['amazon_price']) ? (float)$var['amazon_price'] : 0.00;
            $usd = !empty($var['usd_price']) ? (float)$var['usd_price'] : 0.00;
            
            // String/Int defaults
            $hsn = !empty($var['hsn_code']) ? $var['hsn_code'] : '';
            $gst = !empty($var['gst_rate']) ? (int)$var['gst_rate'] : 0;

            if (!empty($id)) {
                // UPDATE: 19 Variables -> 19 Characters in string
                // String: sssidsddddidddddsii (added missing 'd')
                $stmtUpdate->bind_param("sssidsddddidddddsii", 
                    $temp_code, $var['color'], $var['size'], $qty, $var['cp'], $img, 
                    $h, $w, $d, $wt, $wh, $pi, $pm, 
                    $inr, $amz, $usd, // 5 Doubles here
                    $hsn, $gst, 
                    $id
                );
                $stmtUpdate->execute();
            } else {
                // INSERT: 19 Variables -> 19 Characters in string
                // String: isssidsddddidddddsi (added missing 'd')
                $stmtInsert->bind_param("isssidsddddidddddsi", 
                    $it_id, $temp_code, $var['color'], $var['size'], $qty, $var['cp'], $img, 
                    $h, $w, $d, $wt, $wh, $pi, $pm, 
                    $inr, $amz, $usd, // 5 Doubles here
                    $hsn, $gst
                );
                $stmtInsert->execute();
            }
        }
        return true;
    }

    public function getVariations($it_id) {
        // Added 'quantity_received as quantity' for HTML compatibility
        $sql = "SELECT id, color, size, quantity_received, quantity_received as quantity, cp, variation_image, height, width, depth, weight, ware_house_code, price_india, price_india_mrp, 
                inr_pricing, amazon_price, usd_price, hsn_code, gst_rate
                FROM vp_variations WHERE it_id = ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $it_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    // Get the next available display order
    public function getNextMaterialOrder() {
        // FIX 1: Table name changed to 'material'
        // FIX 2: Added backticks around `display order` because it has a space
        $sql = "SELECT MAX(`display order`) as max_val FROM material";
        $result = $this->conn->query($sql);
        
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['max_val'] + 1;
        }
        return 1;
    }

    // Updated Insert Function with Duplicate Check
    public function insertMaterial($name, $slug, $isActive, $displayOrder, $userId) {
        
        // --- 1. Check for Duplicate Name ---
        $checkSql = "SELECT id FROM material WHERE material_name = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("s", $name);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            // Return a specific string to identify duplicate error
            return "DUPLICATE"; 
        }
        $checkStmt->close();

        // --- 2. Insert New Record ---
        // FIX: Table 'material' and backticks for `display order`
        $sql = "INSERT INTO material 
                (material_name, material_slug, is_active, `display order`, user_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            // Log error for debugging: error_log($this->conn->error);
            return false;
        }

        // types: s (string), s (string), i (int), i (int), i (int)
        $stmt->bind_param("ssiii", $name, $slug, $isActive, $displayOrder, $userId);

        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        
        return false;
    }
    public function getpublishdata($id) {
        $id = (int)$id;

        // 1. Get Main Inbound Data
        $sql = "SELECT vi.*, vv.vendor_name, c.display_name as category, c.category as category_id, c.name as groupname, m.material_name 
                FROM vp_inbound AS vi 
                LEFT JOIN vp_vendors AS vv ON vi.vendor_code = vv.id
                LEFT JOIN category as c on vi.group_name = c.category
                LEFT JOIN material as m on vi.material_code = m.id
                WHERE vi.id = $id";

        $result = $this->conn->query($sql);
        
        // Check if data was found
        $inbounding = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : [];
        
        $category_rows = []; // Renamed to avoid confusion with the final string

        // 2. Only run the second query if we have data and category_code is not empty
        if (!empty($inbounding) && !empty($inbounding['category_code'])) {
            
            $cat_ids_input = $inbounding['category_code']; 
            $sub_cat_ids_input = $inbounding['sub_category_code'];
            $sub_sub_cat_ids_input = $inbounding['sub_sub_category_code'];
            $all_cat_ids = $cat_ids_input.','.$sub_cat_ids_input.','.$sub_sub_cat_ids_input;
            $cat_result = $this->conn->query("SELECT * FROM `category` WHERE id IN ($all_cat_ids)");
            
            if ($cat_result) {
                $category_rows = $cat_result->fetch_all(MYSQLI_ASSOC);
            }
        }

        // 3. Process the loop to create the string
        $cat_id_string = ''; // Initialize variable to avoid "Undefined variable" error
        
        foreach ($category_rows as $key => $value) {
            $cat_id_string .= $value['category'];
            $cat_id_string .= ',';
        }
        
        // Trim the trailing comma
        $final_cat_ids = rtrim($cat_id_string, ',');
        $inbounding['final_cat_ids'] = $final_cat_ids;
        // Add to main array
        $inbounding['cat_ids'] = $final_cat_ids; // Added missing semicolon here
        $var_result = $this->conn->query("SELECT * FROM `vp_variations` WHERE it_id = $id");
        $var_rows = $var_result->fetch_all(MYSQLI_ASSOC);
        if (isset($var_rows) && !empty($var_rows)) {
            $inbounding['var_rows'] = $var_rows;
        }
        $images_sql = $this->conn->query("SELECT file_name FROM `item_images` WHERE item_id = $id");
        $img_result = $images_sql->fetch_all(MYSQLI_ASSOC);
        if ($img_result) {
            $inbounding['img'] = $img_result;
        }
        return [
            'data' => $inbounding
        ];
    }
}
?>