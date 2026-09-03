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
    private function buildModulesHtml(array $currentPerms = []): string
    {
        $srEmpModuleDescriptions = [
            'orders' => 'Edit order unit prices & invoice numbers, order follow-up notes, cancel sales returns, view all warehouses, order JSON API & Exotic publish sync.',
            'pos orders' => 'Edit order unit prices & invoice numbers, order follow-up notes, cancel sales returns, view all warehouses, order JSON API & Exotic publish sync.',
            'products' => 'Bulk Stock Adjustment, Bulk Stock Refresh, and editing Product Added-on dates.',
            'sales return' => 'Cancel finalized sales returns and process return requests.',
            'export documents' => 'Full access to export documents and RODTEP settings.',
            'pos invoice' => 'Edit invoice numbers and access elevated invoice management tools.',
            'inbounding' => 'Senior inbound overrides, photo/stock adjustments, and multi-warehouse viewing.',
            'reports' => 'Senior level analytics, data export options, and multi-warehouse scope.',
            'users' => 'Elevated user management and role assignment privileges.',
            'roles' => 'Elevated role management and permission settings.',
            'modules' => 'Elevated module configuration and permission assignment.',
            'pos register' => 'Bulk stock refresh, offline payment overrides, and senior cashier actions.',
        ];

        $topMgmtModuleDescriptions = [
            'orders' => 'Executive level oversight of orders, financial overrides, and top management reports across all stores.',
            'pos orders' => 'Executive level oversight of POS orders, financial overrides, and top management reports across all stores.',
            'reports' => 'Executive level financial, sales, and management reporting across all departments.',
        ];

        $modules_str = "";
        $modules = $this->conn->query("SELECT DISTINCT module_name FROM vp_permissions ORDER BY module_name ASC");
        if ($modules) {
            while ($m = mysqli_fetch_assoc($modules)) {
                $moduleName = $m['module_name'];
                if ($moduleName === null || trim($moduleName) === '') {
                    continue;
                }
                $escapedModuleName = $this->conn->real_escape_string($moduleName);
                $permsQuery = $this->conn->query("SELECT id, action_name FROM vp_permissions WHERE module_name='{$escapedModuleName}' ORDER BY id ASC");
                
                $groupedActions = [];
                if ($permsQuery) {
                    while ($p = mysqli_fetch_assoc($permsQuery)) {
                        $actionName = $p['action_name'];
                        if (!isset($groupedActions[$actionName])) {
                            $groupedActions[$actionName] = [];
                        }
                        $groupedActions[$actionName][] = (int)$p['id'];
                    }
                    $permsQuery->free();
                }

                if (!empty($groupedActions)) {
                    $actionsHtml = "";
                    $totalActions = count($groupedActions);
                    $checkedActions = 0;

                    $modKey = strtolower(trim($moduleName));

                    foreach ($groupedActions as $actionName => $pids) {
                        $pidsStr = implode(',', $pids);
                        $checked = '';
                        if (!empty($currentPerms) && count(array_intersect($pids, $currentPerms)) > 0) {
                            $checked = 'checked';
                            $checkedActions++;
                        }

                        $actionLower = strtolower(trim($actionName));
                        $extraBadge = '';

                        if (strpos($actionLower, 'sr emp') !== false || strpos($actionLower, 'sr. emp') !== false) {
                            $desc = $srEmpModuleDescriptions[$modKey] ?? 'Senior Employee tier privileges, multi-warehouse visibility, and elevated administrative actions for this module.';
                            $jsTier = htmlspecialchars(addslashes('Sr. Emp Access'), ENT_QUOTES, 'UTF-8');
                            $jsMod = htmlspecialchars(addslashes($moduleName), ENT_QUOTES, 'UTF-8');
                            $jsDesc = htmlspecialchars(addslashes($desc), ENT_QUOTES, 'UTF-8');
                            $extraBadge = "<button type='button' class='ms-1 px-1.5 py-0.5 text-xs font-semibold text-indigo-700 bg-indigo-100 hover:bg-indigo-200 rounded inline-flex items-center gap-1 transition cursor-pointer' onclick='event.preventDefault(); event.stopPropagation(); showAccessDescriptionModal(\"{$jsTier}\", \"{$jsMod}\", \"{$jsDesc}\")'><svg class=\"w-3 h-3 text-indigo-600\" fill=\"currentColor\" viewBox=\"0 0 20 20\"><path fill-rule=\"evenodd\" d=\"M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z\" clip-rule=\"evenodd\"></path></svg> Info</button>";
                        } elseif (strpos($actionLower, 'top management') !== false) {
                            $desc = $topMgmtModuleDescriptions[$modKey] ?? 'Top Management executive privileges (includes all Sr. Emp features plus executive oversight for this module).';
                            $jsTier = htmlspecialchars(addslashes('Top Management Access'), ENT_QUOTES, 'UTF-8');
                            $jsMod = htmlspecialchars(addslashes($moduleName), ENT_QUOTES, 'UTF-8');
                            $jsDesc = htmlspecialchars(addslashes($desc), ENT_QUOTES, 'UTF-8');
                            $extraBadge = "<button type='button' class='ms-1 px-1.5 py-0.5 text-xs font-semibold text-purple-700 bg-purple-100 hover:bg-purple-200 rounded inline-flex items-center gap-1 transition cursor-pointer' onclick='event.preventDefault(); event.stopPropagation(); showAccessDescriptionModal(\"{$jsTier}\", \"{$jsMod}\", \"{$jsDesc}\")'><svg class=\"w-3 h-3 text-purple-600\" fill=\"currentColor\" viewBox=\"0 0 20 20\"><path fill-rule=\"evenodd\" d=\"M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z\" clip-rule=\"evenodd\"></path></svg> Info</button>";
                        }

                        $actionsHtml .= "<label class='me-3 mb-2 d-inline-block text-sm font-medium text-gray-700 cursor-pointer'>
                                <input type='checkbox' name='permissions[]' class='module-permission-checkbox me-1' value='{$pidsStr}' {$checked} onchange='updateModuleSelectAll(this)'> " . ucfirst(htmlspecialchars($actionName)) . "{$extraBadge}
                            </label>";
                    }

                    $allChecked = ($totalActions > 0 && $checkedActions === $totalActions) ? 'checked' : '';

                    $modules_str .= "<div class='module-permission-group border rounded p-2.5 mb-3 bg-white text-sm font-medium text-gray-700 shadow-sm'>";
                    $modules_str .= "<div class='flex items-center justify-between border-b pb-1.5 mb-2'>";
                    $modules_str .= "<strong>" . ucfirst(htmlspecialchars($moduleName)) . "</strong>";
                    $modules_str .= "<label class='text-xs font-semibold text-indigo-600 cursor-pointer select-none me-1 flex items-center gap-1'>";
                    $modules_str .= "<input type='checkbox' class='module-select-all' {$allChecked} onchange='toggleModulePermissions(this)'> Select All";
                    $modules_str .= "</label>";
                    $modules_str .= "</div>";
                    $modules_str .= "<div>" . $actionsHtml . "</div>";
                    $modules_str .= "</div>";
                }
            }
            $modules->free();
        }

        $modules_str .= "<script>
            if (typeof window.toggleModulePermissions !== 'function') {
                window.toggleModulePermissions = function(selectAllCb) {
                    const group = selectAllCb.closest('.module-permission-group');
                    if (!group) return;
                    const checkboxes = group.querySelectorAll('.module-permission-checkbox');
                    checkboxes.forEach(cb => { cb.checked = selectAllCb.checked; });
                };
            }
            if (typeof window.updateModuleSelectAll !== 'function') {
                window.updateModuleSelectAll = function(actionCb) {
                    const group = actionCb.closest('.module-permission-group');
                    if (!group) return;
                    const checkboxes = group.querySelectorAll('.module-permission-checkbox');
                    const selectAll = group.querySelector('.module-select-all');
                    if (selectAll && checkboxes.length > 0) {
                        selectAll.checked = Array.from(checkboxes).every(cb => cb.checked);
                    }
                };
            }
            if (typeof window.showAccessDescriptionModal !== 'function') {
                window.showAccessDescriptionModal = function(tierName, moduleName, description) {
                    let modal = document.getElementById('accessDescriptionModal');
                    if (!modal) {
                        modal = document.createElement('div');
                        modal.id = 'accessDescriptionModal';
                        modal.className = 'fixed inset-0 bg-black/60 backdrop-blur-xs z-[99999] flex items-center justify-center p-4 hidden';
                        modal.setAttribute('role', 'dialog');
                        modal.setAttribute('aria-modal', 'true');
                        modal.innerHTML = `
                            <div class=\"bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 relative border border-gray-200 transform transition-all\">
                                <div class=\"flex items-center justify-between pb-3 border-b border-gray-200\">
                                    <div class=\"flex items-center gap-2\">
                                        <span id=\"accessModalBadge\" class=\"px-2.5 py-0.5 text-xs font-bold text-indigo-700 bg-indigo-100 rounded-full\"></span>
                                        <h3 id=\"accessModalTitle\" class=\"text-base font-bold text-gray-900\"></h3>
                                    </div>
                                    <button type=\"button\" onclick=\"closeAccessDescriptionModal()\" class=\"text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition cursor-pointer\">
                                        <svg class=\"w-5 h-5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M6 18L18 6M6 6l12 12\"></path></svg>
                                    </button>
                                </div>
                                <div class=\"py-4 text-sm text-gray-700 leading-relaxed max-h-[60vh] overflow-y-auto\" id=\"accessModalBody\"></div>
                                <div class=\"flex justify-end pt-3 border-t border-gray-100\">
                                    <button type=\"button\" onclick=\"closeAccessDescriptionModal()\" class=\"px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-lg shadow-sm transition cursor-pointer\">Got it</button>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(modal);
                        modal.addEventListener('click', function(e) {
                            if (e.target === modal) {
                                closeAccessDescriptionModal();
                            }
                        });
                    }
                    
                    document.getElementById('accessModalBadge').innerText = tierName;
                    document.getElementById('accessModalTitle').innerText = moduleName + ' Module Access';
                    
                    function escapeHtmlStr(str) {
                        const div = document.createElement('div');
                        div.innerText = str;
                        return div.innerHTML;
                    }

                    const rawItems = description.split(/[,;\.]/).map(s => s.trim()).filter(s => s.length > 0);
                    let bodyHtml = '<p class=\"font-semibold text-gray-900 mb-3\">Features & Privileges granted under <span class=\"text-indigo-700\">' + escapeHtmlStr(tierName) + '</span> for <span class=\"text-indigo-700\">' + escapeHtmlStr(moduleName) + '</span>:</p>';
                    if (rawItems.length > 1) {
                        bodyHtml += '<ul class=\"space-y-2\">';
                        rawItems.forEach(item => {
                            bodyHtml += '<li class=\"flex items-start gap-2.5 text-gray-700 bg-gray-50/80 p-2 rounded-lg border border-gray-100\"><svg class=\"w-4 h-4 text-emerald-600 shrink-0 mt-0.5\" fill=\"currentColor\" viewBox=\"0 0 20 20\"><path fill-rule=\"evenodd\" d=\"M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z\" clip-rule=\"evenodd\"></path></svg><span class=\"font-medium text-xs leading-relaxed\">' + escapeHtmlStr(item) + '</span></li>';
                        });
                        bodyHtml += '</ul>';
                    } else {
                        bodyHtml += '<div class=\"p-3.5 bg-gray-50 rounded-lg text-gray-700 border border-gray-100 text-xs leading-relaxed font-medium\">' + escapeHtmlStr(description) + '</div>';
                    }
                    
                    document.getElementById('accessModalBody').innerHTML = bodyHtml;
                    modal.classList.remove('hidden');
                };
            }
            if (typeof window.closeAccessDescriptionModal !== 'function') {
                window.closeAccessDescriptionModal = function() {
                    const modal = document.getElementById('accessDescriptionModal');
                    if (modal) {
                        modal.classList.add('hidden');
                    }
                };
            }
        </script>";

        return $modules_str;
    }

    public function addRRecord($id) {
        $roles = array();
        $modules_str = "";
        $id = (int)$id;

        $sql = "SELECT vr.id, vr.role_name, vr.role_description, vr.is_active FROM vp_roles as vr WHERE vr.id = $id";
        $result = $this->conn->query($sql);
        if ($result) {
            $roles = $result->fetch_assoc();
            $result->free();

            $currentPerms = [];
            $res =  $this->conn->query("SELECT permission_id FROM vp_role_permissions WHERE role_id=$id");
            while($r=mysqli_fetch_assoc($res)) { $currentPerms[] = (int)$r['permission_id']; }

            $modules_str = $this->buildModulesHtml($currentPerms);
        }
        return [
            'roles'        => $roles,
            'modules_str'  => $modules_str
        ];
	}
	public function addRecord($data) {
        $permissionsInput = $data['permissions'] ?? [];
        $permissions = [];
        foreach ($permissionsInput as $pid) {
            if (is_array($pid)) {
                foreach ($pid as $subPid) {
                    foreach (explode(',', (string)$subPid) as $p) {
                        $pVal = (int) trim($p);
                        if ($pVal > 0 && !in_array($pVal, $permissions, true)) {
                            $permissions[] = $pVal;
                        }
                    }
                }
            } else {
                foreach (explode(',', (string)$pid) as $p) {
                    $pVal = (int) trim($p);
                    if ($pVal > 0 && !in_array($pVal, $permissions, true)) {
                        $permissions[] = $pVal;
                    }
                }
            }
        }
        $query = "SELECT COUNT(*) AS total FROM vp_roles WHERE role_name = '".$this->conn->real_escape_string($data['addRName'] ?? '')."'";
        $result = $this->conn->query($query);
        $exist = mysqli_fetch_assoc($result);
        if ($exist['total'] > 0) {
            return [
                'success' => false,
                'message' => "Role name already exists."
            ];
        } else {
			//print_array($data);
            $sql = "INSERT INTO vp_roles (role_name, role_description, user_id, is_active) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $userId = (int)($_SESSION["user"]["id"] ?? 0);
            $status = (int)($data['addStatus'] ?? 1);
            $stmt->bind_param('ssii',
                $data['addRName'],
                $data['addRDescription'],
                $userId,
                $status
            );
            if ($stmt->execute()) {
                $role_id = $this->conn->insert_id;
                foreach($permissions as $pid) {
                    $this->conn->query("INSERT INTO vp_role_permissions (role_id, permission_id, user_id) VALUES ('$role_id', '$pid', '$userId')");
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
        $role_id = (int)$id;
        $sql = "UPDATE vp_roles SET role_name = ?, role_description = ?, user_id = ?, is_active = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $userId = (int)($_SESSION["user"]["id"] ?? 0);
        $status = (int)($data['editStatus'] ?? 1);
        $stmt->bind_param('ssiii',
            $data['editRName'],
            $data['editRDescription'],
            $userId,
            $status,
            $role_id
        );
        if ($stmt->execute()) {
            $permissionsInput = $data['permissions'] ?? [];
            $permissions = [];
            foreach ($permissionsInput as $pid) {
                if (is_array($pid)) {
                    foreach ($pid as $subPid) {
                        foreach (explode(',', (string)$subPid) as $p) {
                            $pVal = (int) trim($p);
                            if ($pVal > 0 && !in_array($pVal, $permissions, true)) {
                                $permissions[] = $pVal;
                            }
                        }
                    }
                } else {
                    foreach (explode(',', (string)$pid) as $p) {
                        $pVal = (int) trim($p);
                        if ($pVal > 0 && !in_array($pVal, $permissions, true)) {
                            $permissions[] = $pVal;
                        }
                    }
                }
            }
            $sql = "DELETE FROM vp_role_permissions WHERE role_id=$role_id";
            $this->conn->query($sql);

            foreach($permissions as $pid) {
                $this->conn->query("INSERT INTO vp_role_permissions (role_id, permission_id, user_id) VALUES ('$role_id', '$pid', '$userId')");
            }
            return ['success' => true, 'message' => 'Record updated successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function copyRecord(int $sourceRoleId): array
    {
        if ($sourceRoleId <= 0) {
            return ['success' => false, 'message' => 'Invalid role ID.'];
        }

        $stmt = $this->conn->prepare(
            'SELECT id, role_name, role_description, is_active FROM vp_roles WHERE id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $sourceRoleId);
        $stmt->execute();
        $source = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$source) {
            return ['success' => false, 'message' => 'Source role not found.'];
        }

        $newName = $this->generateUniqueCopyRoleName($source['role_name']);

        $stmt = $this->conn->prepare(
            'INSERT INTO vp_roles (role_name, role_description, user_id, is_active) VALUES (?, ?, ?, ?)'
        );
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $isActive = (int) $source['is_active'];
        $stmt->bind_param('ssii', $newName, $source['role_description'], $userId, $isActive);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Copy failed: ' . $error];
        }

        $newRoleId = (int) $this->conn->insert_id;
        $stmt->close();

        $permStmt = $this->conn->prepare(
            'SELECT permission_id FROM vp_role_permissions WHERE role_id = ?'
        );
        $permStmt->bind_param('i', $sourceRoleId);
        $permStmt->execute();
        $permResult = $permStmt->get_result();

        $insertPerm = $this->conn->prepare(
            'INSERT INTO vp_role_permissions (role_id, permission_id, user_id) VALUES (?, ?, ?)'
        );

        while ($row = $permResult->fetch_assoc()) {
            $permissionId = (int) $row['permission_id'];
            $insertPerm->bind_param('iii', $newRoleId, $permissionId, $userId);
            $insertPerm->execute();
        }

        $permStmt->close();
        $insertPerm->close();

        return [
            'success' => true,
            'message' => 'Role copied successfully.',
            'role_id' => $newRoleId,
        ];
    }

    private function generateUniqueCopyRoleName(string $roleName): string
    {
        $candidate = $roleName . ' (Copy)';
        $counter = 2;

        while ($this->roleNameExists($candidate)) {
            $candidate = $roleName . ' (Copy ' . $counter . ')';
            $counter++;
        }

        return $candidate;
    }

    private function roleNameExists(string $roleName): bool
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) AS total FROM vp_roles WHERE role_name = ?');
        $stmt->bind_param('s', $roleName);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return ((int) ($row['total'] ?? 0)) > 0;
    }

    public function deleteRecord($role_id) {
        $query = "SELECT COUNT(*) AS total FROM vp_users WHERE role_id = $role_id AND is_deleted = 0";
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
        $id = (int)$id;
        if($id > 0){ // Edit Record
            $sql = "SELECT vr.id, vr.role_name, vr.role_description, vr.is_active FROM vp_roles as vr WHERE vr.id = $id AND vr.is_active = 1";
            $result = $this->conn->query($sql);
            if ($result) {
                $roles = $result->fetch_assoc();
                $result->free();

                $currentPerms = [];
                $res =  $this->conn->query("SELECT permission_id FROM vp_role_permissions WHERE role_id=$id");
                while($r=mysqli_fetch_assoc($res)) { $currentPerms[] = (int)$r['permission_id']; }

                $modules_str = $this->buildModulesHtml($currentPerms);
            }
        } else { // Add Record
            $sql = "SELECT vr.id, vr.role_name, vr.role_description, vr.is_active FROM vp_roles as vr WHERE vr.is_active = 1";
            $result = $this->conn->query($sql);
            if ($result) {
                $roles = $result->fetch_assoc();
                $result->free();
            }
            $modules_str = $this->buildModulesHtml([]);
        }
        return [
            'roles'        => $roles,
            'modules_str'  => $modules_str
        ];
	}
}
?>