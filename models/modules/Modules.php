<?php
class Modules {
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
            $where = "WHERE module_name LIKE('%$search%') AND active = '$status_filter'";
        } else {
            if (!empty($search)) {
                $search = $this->conn->real_escape_string($search);
                $where = "WHERE module_name LIKE('%$search%')";
            }

            if (!empty($status_filter)) {
                $search = $this->conn->real_escape_string($status_filter);   
                $where = "WHERE active = '$status_filter'";
            }
        }

		// total records
        $resultCount = $this->conn->query("SELECT COUNT(*) AS total FROM modules $where");
        $rowCount = $resultCount->fetch_assoc();
        $totalRecords = $rowCount['total'];

        $totalPages = ceil($totalRecords / $limit);

        // fetch data
        $sql = "SELECT * FROM modules $where LIMIT $limit OFFSET $offset";
        $result = $this->conn->query($sql);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        // return structured data
        return [
            'modules'        => $data,
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'limit'        => $limit,
            'totalRecords' => $totalRecords,
            'search'       => $search
        ];
	}
	public function addRecord($data) {
        $icon = (trim($data['addFontAwesomeIcon']) != "") ? $this->conn->real_escape_string($data['addFontAwesomeIcon']) : $this->conn->real_escape_string('<i class="fa fa-clipboard-list mr-2">');
        $addParentMenu = $this->conn->real_escape_string($data['addParentMenu']);
        $addModuleName = $this->conn->real_escape_string($data['addModuleName']);
        $addSlug = $this->conn->real_escape_string($data['addSlug']);
        $addAction = $this->conn->real_escape_string($data['addAction']);
        
        $sql = "INSERT INTO modules (parent_id, module_name, slug, `action`, font_awesome_icon, active, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('issssii', $addParentMenu, $addModuleName, $addSlug, $addAction, $icon, $data['addStatus'], $_SESSION["user"]["id"]);

        if ($stmt->execute()) {
            $module_id = $this->conn->insert_id;

            $sql = "SELECT id, access_name FROM vp_role_access where is_active = '1' ORDER BY id ASC";
            $modules = $this->conn->query($sql);
            while($m = mysqli_fetch_assoc($modules)) {
                $actions[] = $m['access_name'];
            }

            $sql = "SELECT id, role_name FROM vp_roles where is_active = '1' ORDER BY id ASC";
            $modules = $this->conn->query($sql);
            while($m = mysqli_fetch_assoc($modules)) {
                $roles[] = $m['id'];
            }

            $sql = "SELECT COUNT(*) AS total FROM modules AS m JOIN vp_permissions AS vpp ON m.id = vpp.module_id JOIN vp_role_permissions AS vrp ON vpp.id = vrp.permission_id WHERE m.id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $module_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                if ($row['total'] == 0) {
                    foreach ($actions as $action) {
                        $permission_name = $data['addModuleName'];
                        $sql_1 = "INSERT INTO vp_permissions (module_id, module_name, action_name, is_active, user_id) VALUES (?, ?, ?, '1', ?)";
                        $stmt_1 = $this->conn->prepare($sql_1);
                        $stmt_1->bind_param('issi',
                            $module_id,
                            $permission_name,
                            $action,
                            $_SESSION["user"]["id"]
                        );
                        if ($stmt_1->execute()) {
                            $permission_id = $this->conn->insert_id;
                            // Assign permission to all roles by default
                            foreach ($roles as $role_id) {
                                $sql_2 = "INSERT INTO vp_role_permissions (role_id, permission_id, user_id) VALUES (?, ?, ?)";
                                $stmt_2 = $this->conn->prepare($sql_2);
                                $stmt_2->bind_param('iii',
                                    $role_id,
                                    $permission_id,
                                    $_SESSION["user"]["id"]
                                );
                                $stmt_2->execute();
                                $stmt_2->close();
                            }
                        }
                    }
                }
            }
            return ['success' => true, 'message' => 'Record added successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function updateRecord($id, $data) {
        $icon = (trim($data['editFontAwesomeIcon']) != "") ? $this->conn->real_escape_string($data['editFontAwesomeIcon']) : $this->conn->real_escape_string('<i class="fa fa-clipboard-list mr-2">');
        $editParentMenu = $this->conn->real_escape_string($data['editParentMenu']);
        $editModuleName = $this->conn->real_escape_string($data['editModuleName']);
        $editSlug = $this->conn->real_escape_string($data['editSlug']);
        $editAction = $this->conn->real_escape_string($data['editAction']);

        $sql = "UPDATE modules SET parent_id = ?, module_name = ?, slug = ?, action = ?, font_awesome_icon = ?, active = ?, user_id = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('issssiii',
            $editParentMenu,
            $editModuleName,
            $editSlug,
            $editAction,
            $icon,
            $data['editStatus'],
            $_SESSION["user"]["id"],
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
    public function deleteRecord($id) {
        $sql = "SELECT module_name FROM modules WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $stmt->close();
            $sql_1 = "SELECT id FROM vp_permissions WHERE module_id = ?";
            $stmt_1 = $this->conn->prepare($sql_1);
            $stmt_1->bind_param('i', $id);
            if($stmt_1->execute()) {
                $result_1 = $stmt_1->get_result();
                while ($row_1 = $result_1->fetch_assoc()) {
                    $sql_2 = "DELETE FROM vp_role_permissions WHERE permission_id = ?";
                    $stmt_2 = $this->conn->prepare($sql_2);
                    $stmt_2->bind_param('i', $row_1['id']);
                    $stmt_2->execute();
                }
                $stmt_1->close();
                $sql_3 = "DELETE FROM vp_permissions WHERE module_id = ?";
                $stmt_3 = $this->conn->prepare($sql_3);
                $stmt_3->bind_param('i', $id);
                if($stmt_3->execute()) {
                    $stmt_3->close();
                    $sql_4 = "DELETE FROM modules WHERE id = ?";
                    $stmt_4 = $this->conn->prepare($sql_4);
                    $stmt_4->bind_param('i', $id);
                    if ($stmt_4->execute()) {
                        $stmt_4->close();
                        // Permissions deleted
                        return ['success' => true, 'message' => 'Record and associated permissions deleted successfully.'];
                    } else {
                        return [
                            'success' => false,
                            'message' => 'Failed to delete module ' . $stmt_4->error . '. Please try again later.'
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'message' => 'Failed to delete associated permissions: ' . $stmt_3->error . '. Please try again later.'
                    ];
                }
            } else {
                $sql_3 = "DELETE FROM modules WHERE id = ?";
                $stmt_3 = $this->conn->prepare($sql_3);
                $stmt_3->bind_param('i', $id);
                if ($stmt_3->execute()) {
                    $stmt_3->close();
                    // Permissions deleted
                    return ['success' => true, 'message' => 'Record and associated permissions deleted successfully.'];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Failed to delete module ' . $stmt_3->error . '. Please try again later.'
                    ];
                }
            }
        } else {
            return [
                'success' => false,
                'message' => 'Delete failed: Module not dound. ' . $stmt->error . '. Please try again later.'
            ];
        }
    }
	public function getRecord($id) {
        $result = $this->conn->query("SELECT id, parent_id, module_name, slug, 'action', font_awesome_icon, active 
                      FROM modules 
                      WHERE id = $id");
        $row = $result->fetch_assoc();

        return json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
    public function getAllParentMenus() {
        $result = $this->conn->query("SELECT id, parent_id, module_name, slug, 'action' FROM modules WHERE parent_id = 0 ORDER BY module_name ASC");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
}
?>