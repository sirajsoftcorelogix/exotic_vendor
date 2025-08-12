<?php
global $conn;
$sql = "SELECT * FROM modules WHERE active = 1 ORDER BY sort_order ASC, id ASC";
$result = $conn->query($sql);
//print_r($result);
//echo 'Hello from left_menu.php';
//$result = [];
//$modules = [];
while ($row = $result->fetch_assoc()) {
    $modules[] = $row;
}

// Recursive menu builder
function buildTree(array $elements, $parentId = null) {
    $branch = [];
    foreach ($elements as $element) {
        if ($element['parent_id'] == $parentId) {
            $children = buildTree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[] = $element;
        }
    }
    return $branch;
}
$tree = buildTree($modules);

function renderMenuItems($items, $parentId = 0) {
    $html = '';

    foreach ($items as $item) {
        if ($item['parent_id'] == $parentId) {
            $children = array_filter($items, fn($i) => $i['parent_id'] == $item['id']);

            if ($children) {
                $submenuId = 'submenu-' . $item['id'];
                $html .= '<li class="nav-item">';
                $html .= '<a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#' . $submenuId . '" role="button" aria-expanded="false" aria-controls="' . $submenuId . '">';
                $html .= '<i class="' . htmlspecialchars($item['font_awesome_icon'] ?: 'fas fa-folder') . ' me-2 icon-class"></i>';
                $html .= '<span class="menu-text">' . htmlspecialchars($item['module_name']) . '</span>';
                $html .= '<i class="fas fa-chevron-down small arrow-icon"></i>';
                $html .= '</a>';
                $html .= '<ul class="collapse list-unstyled ps-3" id="' . $submenuId . '">';
                $html .= renderMenuItems($items, $item['id']);
                $html .= '</ul>';
                $html .= '</li>';
            } else {
                $html .= '<li class="nav-item">';
                $html .= '<a href="index.php?page=' . urlencode($item['slug']) . '&action='.urlencode($item['action']).'" class="nav-link d-flex align-items-center">';
                if (!empty($item['font_awesome_icon'])) {
                    $html .= '<i class="' . htmlspecialchars($item['font_awesome_icon']) . ' me-2 icon-class"></i>';
                }
                $html .= '<span class="menu-text">' . htmlspecialchars($item['module_name']) . '</span>';
                $html .= '</a>';
                $html .= '</li>';
            }
        }
    }

    return $html;
}
?>

<!-- Sidebar -->
<div id="sidebar" class="sidebar position-fixed d-none d-md-block">
    <div class="d-flex align-items-left  ms-0 my-2">
        <img src="images/logo_exocit.png" alt="Exotic Logo"
             style="max-width: 180px; height: auto; transition: opacity 0.3s ease;" id="sidebarLogo">
    </div>
    <nav id="sidebarMenu" class="sidebar accordion mb-5">
        <ul class="nav flex-column">
            <?php echo renderMenuItems($modules); ?>
        </ul>
    </nav>
</div>