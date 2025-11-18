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
            global $conn;
            $sql = "SELECT id, parent_id, module_name, slug, `action` FROM modules WHERE active=1 ORDER BY parent_id ASC";
            $result = $conn->query($sql);
            $moduleRows = [];
            if ($result && $result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                        $parent_id = $row['parent_id'];
                        if (!isset($moduleRows[$parent_id])) {
                              $moduleRows[$parent_id] = [];
                        }
                        $moduleRows[$parent_id][] = $row;
                  }
            }

            foreach($moduleRows[0] as $key => $value): ?>
            <?php if ($value['parent_id'] == 0): ?>
                  <div><h3 class="px-3 py-2 text-gray-700"><?php echo $value['module_name']; ?></h3>
                  <ul class="mt-1">
                  <?php if(isset($moduleRows[$value['id']])) { ?>
                  <?php foreach($moduleRows[$value['id']] as $subKey => $subValue): 
                        if ($subValue['parent_id'] == $value['id']): ?>
                              <li>
                                    <a href="<?=base_url('index.php?page='.$subValue["slug"].'&action='.$subValue["action"]);?>" class="nav-link text-gray-800 <?= ($page == $subValue["slug"]) ? 'active' : '' ?>">
                                    <div class="content-wrapper">
                                          <i class="fa fa-clipboard-list mr-2"></i>
                                          <span><?php echo($subValue["module_name"]);?></span>
                                    </div>
                                    </a>
                              </li>
                        <!-- <option value="<?php echo $subValue['id']; ?>" title="<?php echo $subValue['module_name']; ?>" <?php echo ($data['category_filter'] == $subValue['id']) ? "selected" : ""?>><?php echo $subValue['module_name']; ?></option> -->
                  <?php endif; endforeach; ?>
                  <?php } ?>
                  </ul>
                  </div>
            <?php endif; ?>
      <?php endforeach; ?>
      </nav>
</aside>

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

      //   const nav = document.getElementById('main-nav');
      //   if (nav) {
      //       const navLinks = nav.querySelectorAll('.nav-link');

      //       nav.addEventListener('click', function(e) {
      //           const clickedLink = e.target.closest('.nav-link');
      //           if (!clickedLink) return;

      //           // This line was preventing navigation. It has been removed.
      //           // e.preventDefault();

      //           navLinks.forEach(link => {
      //               link.classList.remove('active');
      //           });

      //           clickedLink.classList.add('active');
      //       });
      //   }
    });
</script>