<?php
class Roles {
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
            $where = "WHERE vr.role_name LIKE('%$search%') AND is_active = '$status_filter'";
        } else {
            if (!empty($search)) {
                $search = $this->conn->real_escape_string($search);
                $where = "WHERE vr.role_name LIKE('%$search%')";
            }

            if (!empty($status_filter)) {
                $search = $this->conn->real_escape_string($status_filter);   
                $where = "WHERE vr.is_active = '$status_filter'";
            }
        }

		// total records
        $sql = "SELECT COUNT(*) AS total FROM vp_roles as vr $where";
        $resultCount = $this->conn->query($sql);
        $rowCount = $resultCount->fetch_assoc();
        $totalRecords = $rowCount['total'];

        $totalPages = ceil($totalRecords / $limit);

        $modules_str = "";
        $roles = array();

        $sql = "SELECT vr.id, vr.role_name, vr.is_active FROM vp_roles AS vr $where ORDER BY vr.role_name";
        $result = $this->conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $role_id = $row['id'];
                if (!isset($roles[$role_id])) {
                    $roles[$role_id] = [
                        'id' => $row['id'],
                        'role_name' => $row['role_name'],
                        'is_active' => $row['is_active'],
                        'permissions' => []
                    ];
                }
                /*if ($row['module_name'] && $row['action_name']) {
                    $roles[$role_id]['permissions'][$row['module_name']][] = $row['action_name'];
                }*/
            }
            $result->free();

            /*$sql = "SELECT DISTINCT module_name FROM vp_permissions ORDER BY module_name";
            $modules = $this->conn->query($sql);
            while($m = mysqli_fetch_assoc($modules)) {
                $modules_str .= "<div class='border rounded p-2 mb-2 bg-white text-sm font-medium text-gray-700'>";
                $modules_str .= "<strong>".ucfirst($m['module_name'])."</strong><br>";
                $perms = $this->conn->query("SELECT * FROM vp_permissions WHERE module_name='{$m['module_name']}'");
                while($p = mysqli_fetch_assoc($perms)) {
                    $modules_str .= "<label class='me-3 mb-2 d-inline-block text-sm font-medium text-gray-700'>
                            <input type='checkbox' name='permissions[]' value='{$p['id']}'> ".ucfirst($p['action_name'])."
                        </label>";
                }
                $modules_str .= "</div>";
            }*/
        }

        // return structured data
        return [
            'roles'        => array_values($roles),
            'modules_str'      => $modules_str,
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'limit'        => $limit,
            'totalRecords' => $totalRecords,
            'search'       => $search
        ];
	}
    public function addRRecord($id) {
        $roles = array();
        $modules_str = "";

        $sql = "SELECT vr.id, vr.role_name, vr.role_description, vr.is_active FROM vp_roles as vr WHERE vr.id = $id";
        $result = $this->conn->query($sql);
        if ($result) {
            $roles = $result->fetch_assoc();
            $result->free();

            $currentPerms = [];
            $res =  $this->conn->query("SELECT permission_id FROM vp_role_permissions WHERE role_id=$id");
            while($r=mysqli_fetch_assoc($res)) { $currentPerms[] = $r['permission_id']; }

            $modules = $this->conn->query("SELECT DISTINCT module_name FROM vp_permissions");
            while($m = mysqli_fetch_assoc($modules)) {
                $modules_str .= "<div class='border rounded p-2 mb-2 bg-white text-sm font-medium text-gray-700'>";
                $modules_str .= "<strong>".ucfirst($m['module_name'])."</strong><br>";
                $perms = $this->conn->query("SELECT * FROM vp_permissions WHERE module_name='{$m['module_name']}'");
                while($p = mysqli_fetch_assoc($perms)) {
                    $checked = in_array($p['id'], $currentPerms) ? 'checked' : '';
                    $modules_str .= "<label class='me-3 mb-2 d-inline-block text-sm font-medium text-gray-700'>
                            <input type='checkbox' name='permissions[]' value='{$p['id']}' $checked> ".ucfirst($p['action_name'])."
                        </label>";
                }
                $modules_str .= "</div>";
            }
        }
        return [
            'roles'        => $roles,
            'modules_str'      => $modules_str
        ];
	}
	public function addRecord($data) {
        $permissions = $data['permissions'] ?? [];
        $query = "SELECT COUNT(*) AS total FROM vp_roles WHERE role_name = '".$data['addRName']."'";
        $result = $this->conn->query($query);
        $data_tmp = mysqli_fetch_assoc($result);
        if ($data_tmp['total'] > 0) {
            return [
                'success' => false,
                'message' => "Role name already exists."
            ];
        } else {
            $sql = "INSERT INTO vp_roles (role_name, role_description, user_id, is_active) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('ssii',
                $data['addRName'],
                $data['addRDescription'],
                $_SESSION["user"]["id"],
                $data['addStatus']
            );
            if ($stmt->execute()) {
                $role_id = $this->conn->insert_id;
                foreach($permissions as $pid) {
                    $this->conn->query("INSERT INTO vp_role_permissions (role_id, permission_id, user_id) VALUES ('$role_id', '$pid', '{$_SESSION["user"]["id"]}')");
                }
                return ['success' => true, 'message' => 'Record added successfully.'];
            }
            return [
                'success' => false,
                'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
            ];
        }
    }
    public function updateRecord($id, $data) {
        $role_id = $id;
        $sql = "UPDATE vp_roles SET role_name = ?, role_description = ?, user_id = ?, is_active = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssiii',
            $data['editRName'],
            $data['editRDescription'],
            $_SESSION["user"]["id"],
            $data['editStatus'],
            $role_id
        );
        if ($stmt->execute()) {
            $permissions = $data['permissions'] ?? [];
            $sql = "DELETE FROM vp_role_permissions WHERE role_id=$role_id";
            $this->conn->query($sql);

            foreach($permissions as $pid) {
                $this->conn->query("INSERT INTO vp_role_permissions (role_id, permission_id, user_id) VALUES ('$role_id', '$pid', '{$_SESSION["user"]["id"]}')");
            }
            return ['success' => true, 'message' => 'Record updated successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function deleteRecord($role_id) {
        $query = "SELECT COUNT(*) AS total FROM vp_users WHERE role_id = $role_id";
        $result = $this->conn->query($query);
        $data = mysqli_fetch_assoc($result);
        if ($data['total'] > 0) {
            return [
                'success' => false,
                'message' => "Cannot delete this role because it is currently assigned to {$data['total']} user(s)."
            ];
        } else {
            $sql = "DELETE FROM vp_roles WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $role_id);
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Record deleted successfully.'];
            }
            return [
                'success' => false,
                'message' => 'Delete failed: ' . $stmt->error . '. Please try again later.'
            ];
        }
    }
    public function getRecord($id) {
        $roles = array();
        $modules_str = "";
        if($id > 0){ // Edit Record
            $sql = "SELECT vr.id, vr.role_name, vr.role_description, vr.is_active FROM vp_roles as vr WHERE vr.id = $id AND vr.is_active = 1";
            $result = $this->conn->query($sql);
            if ($result) {
                $roles = $result->fetch_assoc();
                $result->free();

                $currentPerms = [];
                $res =  $this->conn->query("SELECT permission_id FROM vp_role_permissions WHERE role_id=$id");
                while($r=mysqli_fetch_assoc($res)) { $currentPerms[] = $r['permission_id']; }

                $modules = $this->conn->query("SELECT DISTINCT module_name FROM vp_permissions");
                while($m = mysqli_fetch_assoc($modules)) {
                    $modules_str .= "<div class='border rounded p-2 mb-2 bg-white text-sm font-medium text-gray-700'>";
                    $modules_str .= "<strong>".ucfirst($m['module_name'])."</strong><br>";
                    $perms = $this->conn->query("SELECT * FROM vp_permissions WHERE module_name='{$m['module_name']}'");
                    while($p = mysqli_fetch_assoc($perms)) {
                        $checked = in_array($p['id'], $currentPerms) ? 'checked' : '';
                        $modules_str .= "<label class='me-3 mb-2 d-inline-block text-sm font-medium text-gray-700'>
                                <input type='checkbox' name='permissions[]' value='{$p['id']}' $checked> ".ucfirst($p['action_name'])."
                            </label>";
                    }
                    $modules_str .= "</div>";
                }
            }
        } else { // Add Record
            $sql = "SELECT vr.id, vr.role_name, vr.role_description, vr.is_active FROM vp_roles as vr WHERE vr.is_active = 1";
            $result = $this->conn->query($sql);
            if ($result) {
                $roles = $result->fetch_assoc();
                $result->free();
            }
            $modules = $this->conn->query("SELECT DISTINCT module_name FROM vp_permissions");
            while($m = mysqli_fetch_assoc($modules)) {
                $modules_str .= "<div class='border rounded p-2 mb-2 bg-white text-sm font-medium text-gray-700'>";
                $modules_str .= "<strong>".ucfirst($m['module_name'])."</strong><br>";
                $perms = $this->conn->query("SELECT * FROM vp_permissions WHERE module_name='{$m['module_name']}'");
                while($p = mysqli_fetch_assoc($perms)) {
                    $modules_str .= "<label class='me-3 mb-2 d-inline-block text-sm font-medium text-gray-700'>
                            <input type='checkbox' name='permissions[]' value='{$p['id']}'> ".ucfirst($p['action_name'])."
                        </label>";
                }
                $modules_str .= "</div>";
            }
        }
        return [
            'roles'        => $roles,
            'modules_str'      => $modules_str
        ];
	}
}
?>