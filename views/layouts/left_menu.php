<!-- Button to bring the menu back -->
<button id="open-menu-button" class="hidden absolute top-8 left-8 z-30 p-2 bg-white rounded-md shadow-md focus:outline-none">
      <svg width="18" height="15" viewBox="0 0 18 9" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M1 0.5H17" stroke="black" stroke-linecap="round"/>
      <path d="M4 4L14 4" stroke="black" stroke-linecap="round"/>
      <path d="M1 8H17" stroke="black" stroke-linecap="round"/>
      </svg>
</button>

<!-- Floating Sidebar -->
<aside id="sidebar" class="w-64 bg-white flex flex-col flex-shrink-0 rounded-[11px] border border-gray-200 shadow-sm overflow-hidden m-4">
        <!-- Header -->
        <div class="h-16 flex items-center justify-between px-4 flex-shrink-0 border-b border-gray-100">
            <!-- Logo -->
            <div class="flex items-center space-x-2">
                <img src="images/logo2.png"/>
            </div>
            <!-- Menu Toggle Button (to close) -->
            <button id="menu-toggle" class="text-gray-600 focus:outline-none">
                <svg width="18" height="15" viewBox="0 0 18 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 0.5H17" stroke="black" stroke-linecap="round"/>
                    <path d="M4 4L14 4" stroke="black" stroke-linecap="round"/>
                    <path d="M1 8H17" stroke="black" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
      <nav id="main-nav" class="flex-1 overflow-y-auto p-4">
            <!-- Navigation Links -->
            <?php
                  $userRoleId = (int)$_SESSION["user"]["role_id"];
                  global $conn;
                  if($userRoleId == 1) {
                        $sql = "SELECT DISTINCT p.module_id FROM vp_role_permissions rp JOIN vp_permissions p ON rp.permission_id = p.id";
                        $stmt = $conn->prepare($sql);
                  }else{
                        $sql = "SELECT DISTINCT p.module_id FROM vp_role_permissions rp JOIN vp_permissions p ON rp.permission_id = p.id WHERE rp.role_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('i', $userRoleId);
                  }
                  $stmt->execute();
                  $res = $stmt->get_result();

                  $allowedModuleIds = [];
                  while ($r = $res->fetch_assoc()) {
                        $allowedModuleIds[] = (int)$r['module_id'];
                  }
                  $stmt->close();

                  if (empty($allowedModuleIds)) {
                        $menu = [];
                  } else {
                        $idsList = implode(',', $allowedModuleIds);
                        if($userRoleId == 1) {
                              $sql = "SELECT DISTINCT m.id, m.parent_id, m.module_name, m.slug, m.action, m.font_awesome_icon, m.sort_order FROM modules m WHERE m.active = 1 ORDER BY COALESCE(m.parent_id, 0), m.id, m.module_name";
                        } else {
                              $sql = "SELECT DISTINCT m.id, m.parent_id, m.module_name, m.slug, m.action, m.font_awesome_icon, m.sort_order FROM modules m WHERE m.active = 1 AND ( m.id IN ($idsList) OR m.id IN (SELECT DISTINCT parent_id FROM modules WHERE id IN ($idsList) AND parent_id IS NOT NULL)) ORDER BY COALESCE(m.parent_id, 0), m.id, m.module_name";
                        }
                        $result = $conn->query($sql);

                        // collect modules by id
                        $modules = []; // final tree
                        $items = [];   // flat list

                        while ($row = $result->fetch_assoc()) {
                              $id = (int)$row['id'];
                              $items[$id] = [
                                    'id' => $id,
                                    'parent_id' => isset($row['parent_id']) ? (int)$row['parent_id'] : 0,
                                    'name' => $row['module_name'],
                                    'slug' => $row['slug'],
                                    'action' => $row['action'],
                                    'icon' => ($row['font_awesome_icon']),
                                    'sort_order'=> $row['sort_order'],
                                    'children' => []
                              ];
                        }

                        // Build tree using parent_id
                        foreach ($items as $id => &$item) {
                        if ($item['parent_id'] == 0) {
                              $modules[$id] = &$item;
                        } else {
                              $items[$item['parent_id']]['children'][$id] = &$item;
                        }
                        }
                        unset($item); // break reference
                        sortMenu($modules); //sorting order implementation

                        $menu = [];
                        foreach ($modules as $id => $m) {
                              $parentId = (int)$m['parent_id'];

                              if ($parentId === 0 || $parentId === null) {
                                    // top-level
                                    $menu[$id] = $m;
                              } else {
                                    // child: but parent may or may not exist in $modules (we included parents above)
                                    if (!isset($modules[$parentId])) {
                                          // parent not present (rare because we selected parents), fetch parent quickly
                                          $pstmt = $conn->prepare("SELECT id, parent_id, module_name, slug, font_awesome_icon FROM modules WHERE id = ? AND active = 1");
                                          $pstmt->bind_param('i', $parentId);
                                          $pstmt->execute();
                                          $pres = $pstmt->get_result()->fetch_assoc();
                                          $pstmt->close();
                                          if ($pres) {
                                                $modules[$parentId] = [
                                                      'id' => (int)$pres['id'],
                                                      'parent_id' => isset($pres['parent_id']) ? (int)$pres['parent_id'] : 0,
                                                      'name' => $pres['module_name'],
                                                      'slug' => $pres['slug'],
                                                      'action' => $pres['action'],
                                                      'icon' => $pres['font_awesome_icon'],
                                                      'children' => []
                                                ];
                                          }
                                    }
                                    // attach to parent in modules array
                                    if (isset($modules[$parentId])) {
                                          // ensure children array uses child id as key to prevent duplicates
                                          $modules[$parentId]['children'][$id] = [
                                                'id' => $m['id'],
                                                'parent_id' => $parentId,
                                                'name' => $m['name'],
                                                'slug' => $m['slug'],
                                                'action' => $m['action'],
                                                'icon' => $m['icon']
                                          ];
                                    } else {
                                          // fallback: put child as top-level if parent missing
                                          $menu[$id] = $m;
                                    }
                              }
                        }

                        foreach ($modules as $id => $m) {
                              if ((int)$m['parent_id'] === 0 || $m['parent_id'] === null) {
                                    // convert children associative array to numeric indexed array (optional)
                                    $children = array_values($m['children']);
                                    $menu[$id] = [
                                          'id' => $m['id'],
                                          'parent_id' => $m['parent_id'],
                                          'name' => $m['name'],
                                          'slug' => $m['slug'],
                                          'action' => $m['action'],
                                          'icon' => $m['icon'],
                                          'children' => $children
                                    ];
                              }
                        }
                  }
                  renderMenu($menu, $page, $action); //generate HTML menu
            ?>
      </nav>
</aside>
<?php
// Sort children based on sort_order
function sortMenu(&$menu) {
    uasort($menu, function($a, $b) {
        return $a['sort_order'] <=> $b['sort_order'];
    });

    foreach ($menu as &$item) {
        if (!empty($item['children'])) {
            sortMenu($item['children']);
        }
    }
}
function renderMenu($menu, $currentPage = '', $currentAction = '')
{ 
      $active = '';
      $cnt = 1;
      $active = ($currentPage == 'dashboard' && $currentAction == 'dashboard') 
                              ? 'active' 
                              : '';
      echo '<ul class="mt-1">';
      echo '<li>';
      echo '<a href="index.php?page=dashboard&action=dashboard" 
                        class="nav-link text-gray-800 ' . $active . '">';
            // icon
      echo '<div class="content-wrapper"><i class="fas fa-shield-alt mr-2"></i>&nbsp;&nbsp;';
      // name
      echo '<span>Dashboard</span></div>';
      echo '</a>';
      echo '</li>';
      echo '</ul>';
      foreach ($menu as $item) {
		if($cnt == 1 && $item['slug'] == 'dashboard' && $item['action'] == 'dashboard') {
                  $active = ($currentPage == 'dashboard' && $currentAction == 'dashboard') 
                              ? 'active' 
                              : '';
                  echo '<ul class="mt-1">';
                  echo '<li>';
                  echo '<a href="index.php?page=dashboard&action=dashboard" 
                                    class="nav-link text-gray-800 ' . $active . '">';
                        // icon
                  echo '<div class="content-wrapper"><i class="fas fa-shield-alt mr-2"></i>&nbsp;&nbsp;';
                  // name
                  echo '<span>Dashboard</span></div>';
                  echo '</a>';
                  echo '</li>';
                  echo '</ul>';
                  $cnt++;
                  continue; // Skip the first item (Dashboard)
            }
            // Parent category title
            echo '<div>';
            echo '<h3 class="px-3 py-2 text-gray-700">'
                        . htmlspecialchars($item['name'])
                  . '</h3>';
            echo '<ul class="mt-1">';
            // If parent has children
            if (!empty($item['children'])) {
                  foreach ($item['children'] as $child) {

                        $active = ($currentPage == $child['slug'] && $currentAction == $child['action']) 
                                          ? 'active' 
                                          : '';
                        echo '<li>';
                        echo '<a href="index.php?page=' . $child['slug'] . '&action=' . $child['action'] . '" 
                                    class="nav-link text-gray-800 ' . $active . '">';
                        // icon
                        echo '<div class="content-wrapper">' . trim($child['icon']) . '</i>&nbsp;&nbsp;';
                        // name
                        echo '<span>' . htmlspecialchars($child['name']) . '</span></div>';
                        echo '</a>';
                        echo '</li>';
                  }
            }
            echo '</ul>';
            echo '</div>';
      }
}
?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const menuToggle = document.getElementById('menu-toggle');
        const openMenuButton = document.getElementById('open-menu-button');
        const sidebar = document.getElementById('sidebar');

        if (menuToggle && sidebar && openMenuButton) {
            // Event listener for the close button (inside the sidebar)
            menuToggle.addEventListener('click', function() {
                sidebar.classList.add('hidden');
                openMenuButton.classList.remove('hidden');
            });

            // Event listener for the open button (outside the sidebar)
            openMenuButton.addEventListener('click', function() {
                sidebar.classList.remove('hidden');
                openMenuButton.classList.add('hidden');
            });
        }
    });
</script>