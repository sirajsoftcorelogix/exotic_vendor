<!-- Button to bring the menu back -->
<button id="open-menu-button" class="hidden absolute top-8 left-8 z-30 p-2 bg-white rounded-md shadow-md focus:outline-none">
      <svg width="18" height="15" viewBox="0 0 18 9" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M1 0.5H17" stroke="black" stroke-linecap="round"/>
      <path d="M4 4L14 4" stroke="black" stroke-linecap="round"/>
      <path d="M1 8H17" stroke="black" stroke-linecap="round"/>
      </svg>
</button>

<!-- Floating Sidebar -->
<aside id="sidebar" class="w-64 bg-white flex flex-col flex-shrink-0 rounded-[11px] border border-gray-200 shadow-sm overflow-hidden h-full max-h-full">
        <!-- Header -->
        <div class="h-16 flex items-center justify-between px-4 flex-shrink-0 border-b border-gray-100">
            <!-- Logo -->
            <div class="flex items-center space-x-2">
                <img src="images/EI_Logo_130x27_SVG_1.svg" width="170"/>
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
            <?php
                  require_once __DIR__ . '/../../models/navigation/NavigationMenu.php';

                  if (!function_exists('renderNavigationMenu')) {
                        function renderNavigationMenu(array $menu, string $currentPage = '', string $currentAction = ''): void
                        {
                              $dashboardActive = ($currentPage === 'dashboard' && $currentAction === 'dashboard') ? 'active' : '';
                              echo '<ul class="mt-1">';
                              echo '<li>';
                              echo '<a href="index.php?page=dashboard&action=dashboard" class="nav-link text-gray-800 ' . $dashboardActive . '">';
                              echo '<div class="content-wrapper"><i class="fas fa-shield-alt mr-2"></i>&nbsp;&nbsp;';
                              echo '<span>Dashboard</span></div>';
                              echo '</a>';
                              echo '</li>';
                              echo '</ul>';

                              foreach ($menu as $item) {
                                    if (($item['slug'] ?? '') === 'dashboard' && ($item['action'] ?? '') === 'dashboard') {
                                          continue;
                                    }

                                    $children = is_array($item['children'] ?? null) ? $item['children'] : [];
                                    if ($children === []) {
                                          continue;
                                    }

                                    echo '<div>';
                                    echo '<h3 class="px-3 py-2 text-gray-700">' . htmlspecialchars((string) ($item['name'] ?? '')) . '</h3>';
                                    echo '<ul class="mt-1">';
                                    foreach ($children as $child) {
                                          if (($child['slug'] ?? '') === 'dashboard' && ($child['action'] ?? '') === 'dashboard') {
                                                continue;
                                          }

                                          $childActive = ($currentPage === ($child['slug'] ?? '') && $currentAction === ($child['action'] ?? ''))
                                                ? 'active'
                                                : '';
                                          $slug = rawurlencode((string) ($child['slug'] ?? ''));
                                          $action = rawurlencode((string) ($child['action'] ?? ''));

                                          echo '<li>';
                                          echo '<a href="index.php?page=' . $slug . '&action=' . $action . '" class="nav-link text-gray-800 ' . $childActive . '">';
                                          echo '<div class="content-wrapper">' . trim((string) ($child['icon'] ?? '')) . '&nbsp;&nbsp;';
                                          echo '<span>' . htmlspecialchars((string) ($child['name'] ?? '')) . '</span></div>';
                                          echo '</a>';
                                          echo '</li>';
                                    }
                                    echo '</ul>';
                                    echo '</div>';
                              }
                        }
                  }

                  is_login();
                  global $conn;

                  $userId = (int) ($_SESSION['user']['id'] ?? 0);
                  $primaryRoleId = (int) ($_SESSION['user']['role_id'] ?? 0);
                  $navigationMenuModel = new NavigationMenu($conn);
                  $menu = $navigationMenuModel->buildMenuTreeForUser($userId, $primaryRoleId);

                  renderNavigationMenu($menu, (string) ($page ?? ''), (string) ($action ?? ''));
            ?>
      </nav>
</aside>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const menuToggle = document.getElementById('menu-toggle');
        const openMenuButton = document.getElementById('open-menu-button');
        const sidebar = document.getElementById('sidebar');

        if (menuToggle && sidebar && openMenuButton) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.add('hidden');
                openMenuButton.classList.remove('hidden');
            });

            openMenuButton.addEventListener('click', function() {
                sidebar.classList.remove('hidden');
                openMenuButton.classList.add('hidden');
            });
        }
    });
</script>
