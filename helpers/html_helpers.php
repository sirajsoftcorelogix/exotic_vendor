<?php
	function is_login(){
		global $domain;
		if (session_status() === PHP_SESSION_NONE) session_start();

		if (!isset($_SESSION) || !isset($_SESSION['user'])) {
			// store current URL to redirect back after successful login
			$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
				. '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

			if (empty($_SESSION['redirect_after_login'])) {
				$_SESSION['redirect_after_login'] = $currentUrl;
			}

			header('Location: ' . $domain . '?page=users&action=login');
			exit;
		}

		return true;
	}
	function base_url($path = '') {
		global $domain;
		return $domain . '/' . ltrim($path, '/');
	}
	function print_array($data){
		echo "<pre>";
		print_r($data);
	}
	
	function image_upload_path(){
		return $_SERVER['DOCUMENT_ROOT'].'/laundryERP/images/tenants/';
	}
	
	function image_path(){
		return '/laundryERP/images/tenants/';
	}
	
	function getloginUser(){
		//tenant details
		$user['tenant_id'] 			= $_SESSION['tenant_id'];
		$user['currency_symbol'] 	= $_SESSION['currency_symbol'];
		$user['tenant_Name'] 		= $_SESSION['tenant_Name'];
		//store details
		$user['store_id'] 			= $_SESSION['store_id'];
		$user['store_code'] 		= $_SESSION['store_code'];
		//user details
		$user['user_id'] 			= $_SESSION['user_id'];
		$user['user_full_name'] 	= $_SESSION['user_full_name'];
		return $user;
	}
	
	function renderTemplate($viewFile, $data = [], $title = null) 
	{
		extract($data);
		ob_start();
		include $viewFile;
		$content = ob_get_clean();
		include 'views/layouts/main.php';
	}
	function renderTemplateClean($viewFile, $data = [], $title = null) 
	{
		extract($data);
		ob_start();
		include $viewFile;
		$content = ob_get_clean();
		include 'views/layouts/cleanMain.php';
	}
	function renderPartial($viewFile, $data = []) {
		extract($data);
		include $viewFile;
	}
	
	// Generates a sortable table header link
	function sort_link($column, $label, $sort_by = 'id', $sort_order = 'asc', $page = '', $search = '',$action = '' ) {
		$new_order = ($sort_by === $column && $sort_order === 'asc') ? 'desc' : 'asc';
		$icon = '';

		if ($sort_by === $column) {
			$icon = $sort_order === 'asc' ? ' ▲' : ' ▼';
		}

		$query = [
			'page' => $page,
			'action' => $action,
			'sort_by' => $column,
			'sort_order' => $new_order
		];

		if (!empty($search)) {
			$query['search'] = $search;
		}

		$url = 'index.php?' . http_build_query($query);
		return "<a class='link-underline-light' href=\"$url\">$label$icon</a>";
	}

	// Generates pagination links
	function pagination_links($total_pages, $current_page, $page, $sort_by, $sort_order, $search = '')
	{
		$html = '<nav><ul class="pagination justify-content-center">';

		for ($i = 1; $i <= $total_pages; $i++) {
			$is_active = $i == $current_page ? 'active' : '';
			$url = "index.php?page=$page&page_number=$i&sort_by=$sort_by&sort_order=$sort_order";
			if (!empty($search)) {
				$url .= "&search=" . urlencode($search);
			}

			$html .= "<li class=\"page-item $is_active\"><a class=\"page-link\" href=\"$url\">$i</a></li>";
		}

		$html .= '</ul></nav>';
		return $html;
	}

	// Displays Bootstrap-style alert messages
	function flash_message($message, $type = 'success')
	{
		if (!$message) return '';

		return "<div class=\"alert alert-$type alert-dismissible fade show\" role=\"alert\">
					$message
					<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
				</div>";
	}

	// Sanitizes output for HTML
	function e($string)
	{
		return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
	}
	
	function user_roles_dropdown(){
		// Fetch active roles
		global $conn;
		$sql = "SELECT id, role_name FROM user_roles WHERE active = 1 ORDER BY role_name ASC";
		return $conn->query($sql);
	}
	
	function modules_dropdown() {
		// Fetch active roles
		global $conn;
		$sql = "SELECT 
				m1.id,
				m2.module_name AS parent_name,
				m1.module_name AS module_name
			FROM 
				modules m1
			LEFT JOIN 
				modules m2 ON m1.parent_id = m2.id
			WHERE 
				m1.active = 1
			ORDER BY parent_name, module_name ASC";
		return $conn->query($sql);
	}
	
	function getCountryList() {
		return [
			['id' => 1,  'iso_code' => 'IN', 'timezone' => 'Asia/Kolkata',         'name' => 'India'],
			['id' => 2,  'iso_code' => 'US', 'timezone' => 'America/New_York',     'name' => 'United States'],
			['id' => 3,  'iso_code' => 'GB', 'timezone' => 'Europe/London',        'name' => 'United Kingdom'],
			['id' => 4,  'iso_code' => 'CA', 'timezone' => 'America/Toronto',      'name' => 'Canada'],
			['id' => 5,  'iso_code' => 'AE', 'timezone' => 'Asia/Dubai',           'name' => 'United Arab Emirates'],
			['id' => 6,  'iso_code' => 'QA', 'timezone' => 'Asia/Qatar',           'name' => 'Qatar'],
			['id' => 7,  'iso_code' => 'SA', 'timezone' => 'Asia/Riyadh',          'name' => 'Saudi Arabia'],
			['id' => 8,  'iso_code' => 'AU', 'timezone' => 'Australia/Sydney',     'name' => 'Australia'],
			['id' => 9,  'iso_code' => 'SG', 'timezone' => 'Asia/Singapore',       'name' => 'Singapore'],
			['id' => 10, 'iso_code' => 'MY', 'timezone' => 'Asia/Kuala_Lumpur',    'name' => 'Malaysia'],
		];
	}

	function getWeekDays() {
		return [
			['id' => 'sunday', 'name' => 'Sunday'],
			['id' => 'sunday', 'name' => 'Monday'],
			['id' => 'sunday', 'name' => 'Tuesday'],
			['id' => 'sunday', 'name' => 'Wednessday'],
			['id' => 'sunday', 'name' => 'Thursady'],
			['id' => 'sunday', 'name' => 'Friday'],
			['id' => 'sunday', 'name' => 'Saturday']
		];
	}
	
	function getParentCategoryList() {
		// Fetch active roles
		global $conn;
		$sql = "SELECT id, category_name
				FROM product_categories 
				WHERE active = 1 AND parent_id=0
				ORDER BY category_name ASC";
		$result = $conn->query($sql);
		$categories = array();
		while ($row = $result->fetch_assoc()) {
			$categories[$row['id']] = $row['category_name'];
		}
		return $categories;
	}
	
	function getProductCategoryList() {
		// Fetch active roles
		global $conn;
		$sql = "SELECT id, category_name
				FROM product_categories 
				WHERE active = 1
				ORDER BY category_name ASC";
		$result = $conn->query($sql);
		$categories = array();
		while ($row = $result->fetch_assoc()) {
			$categories[$row['id']] = $row['category_name'];
		}
		return $categories;
	}
	
	function getActiveServices() {
		// Fetch active roles
		global $conn;
		$sql = "SELECT id,service_name FROM services WHERE active=1 ORDER BY id";
		$result = $conn->query($sql);
		$services = array();
		while ($row = $result->fetch_assoc()) {
			$services[$row['id']] = $row['service_name'];
		}
		return $services;
	}

	
	function getSubscriptionPlans() {
		global $conn;
		$sql = "SELECT * FROM subscription_plans m1 WHERE m1.active = 1 ORDER BY id ASC";
		$result = $conn->query($sql);
	}
	
	function getLaundryUnits() {
		return [
			'Piece' => 'Piece',
			'Kg'    => 'Kg',
			'Set'   => 'Set',
			'Pair'  => 'Pair',
			'Meter' => 'Meter',
			'Bundle'=> 'Bundle',
			'Dozen'	=> 'Dozen',
			'Load'	=> 'Load'
		];
	}
	
	function getCustomerTypes() {
		return [
			'regular','corporate','reseller','staff','guest'
		];
	}
	
	function validateRequiredFields(array $data, array $fields): array {
		$missing = array_filter($fields, function ($field) use ($data) {
			return !isset($data[$field]) || $data[$field] === '';
		});
		return empty($missing)
			? ['success' => true]
			: ['success' => false, 'error' => 'Missing: ' . implode(', ', $missing)];
	}

	function getCategories() {
		return [			
			'paintings' => 'Paintings',
			'sculptures' => 'Sculptures',
			'textiles' => 'Textiles',
			'jewelry' => 'Jewelry',
			'homeandliving' => 'Home and Living'
		];
	}
	function getVendorCategory(){
		return [
			'ThanjavurPainting' => 'Thanjavur Painting',
			'MusicalInstruments' => 'Musical Instruments',
			'PattachitraPainting' => 'Pattachitra Painting',
			'GondPainting' => 'Gond Painting',
			'PichwaiiPainting' => 'Pichwaii Painting',
			'MysoreGoldPainting' => 'Mysore Gold Painting',
			'MadhubaniPainting' => 'Madhubani Painting',
			'RugsCarpet' => 'Rugs & Carpet',
			'VaranasiToys' => 'Varanasi Toys',
			'LeatherPainting' => 'Leather Painting',
			'MataNiPachediPainting' => 'Mata Ni Pachedi Painting',
			'KalamkariPainting' => 'Kalamkari Painting',
			'MysoreWoodPanelsStatues' => 'Mysore Wood Panels & Statues',
			'PujaTemples' => 'Puja Temples',
			'PaperMachie' => 'Paper Machie',
			'GoldenGrass' => 'Golden Grass',
			'PashminaShawls' => 'Pashmina Shawls',
			'SilverJewelry' => 'Silver Jewelry',
			'OdishaStoneCarving' => 'Odisha Stone Carving',
			'CaneBambooCraft' => 'Cane & Bamboo Craft',
			'CoconutShellCraft' => 'Coconut Shell Craft',
			'MiniaturePainting' => 'Miniature Painting',
			'PhadPainting' => 'Phad Painting',
			'KerelaMural' => 'Kerela Mural',
			'WarliPainting' => 'Warli Painting',
			'BatikPainting' => 'Batik Painting',
			'OilPainting' => 'Oil Painting',
			'AcrylicPainting' => 'Acrylic Painting',
			'ThangkaPainting' => 'Thangka Painting',
			'Yantras' => 'Yantras',
			'BrassStatues' => 'Brass Statues',
			'PanchalohaBronzeStatues' => 'Panchaloha Bronze Statues',
			'HoysalaBronzeStatues' => 'Hoysala Bronze Statues',
			'MahabalipuramStoneStatues' => 'Mahabalipuram Stone Statues',
			'KulluShawls' => 'Kullu Shawls',
			'WoodenStatues' => 'Wooden Statues',
			'MarbleStatues' => 'Marble Statues',
			'HomeDecor' => 'Home Decor',
			'DhokraArt' => 'Dhokra Art',
			'NepaleseStatues' => 'Nepalese Statues',
			'FashionJewelry' => 'Fashion Jewelry',
			'Artifacts' => 'Artifacts',
			'CopperStatues' => 'Copper Statues',
			'GamesDolls' => 'Games & Dolls',
			'BrassJewelry' => 'Brass Jewelry',
			'KalighatPainting' => 'Kalighat Painting',
			'Sarees' => 'Sarees',
			'SalwarKameez' => 'Salwar Kameez',
			'NepalShawls' => 'Nepal Shawls',
			'KutchShawls' => 'Kutch Shawls',
			'Dupattas' => 'Dupattas',
			'KashmiriShawls' => 'Kashmiri Shawls',
			'Stoles' => 'Stoles',
			'Dhotis' => 'Dhotis',
			'Skirts' => 'Skirts',
			'Footwear' => 'Footwear',
			'BagsAccessories' => 'Bags & Accessories',
			'IndianDoors' => 'Indian Doors',
			'Furniture' => 'Furniture',
			'Bedding' => 'Bedding',
			'AipanPainting' => 'Aipan Painting',
			'CheriyalPainting' => 'Cheriyal Painting',
			'Bidriware' => 'Bidriware'
		];
	}
	function updateRoles() { // Run this function once to populate permissions table
		global $conn;

		$actions = ['add', 'edit', 'view', 'delete', 'list']; // Standard permissions
		$roles = [2, 3]; // Role IDs to assign permissions to (e.g., Editor and Viewer)

        $sql = "SELECT id, module_name, slug FROM modules where module_name != 'Administrator' ORDER BY module_name";
        $modules = $conn->query($sql);
        while($m = mysqli_fetch_assoc($modules)) {
            
            $stmt = $conn->prepare("INSERT INTO vp_permissions (module_id, module_name, action_name) VALUES (?, ?, ?)");
            foreach ($modules as $module) {
                foreach ($actions as $action) {
                    $stmt->bind_param("iss", $module["id"], $module["module_name"], $action);
                    if ($stmt->execute()) {
						$last_insert_id = $conn->insert_id;
						$stmt_p = $conn->prepare("INSERT INTO vp_role_permissions (role_id, permission_id) VALUES (?, ?)");
						foreach ($roles as $role) {
							$stmt_p->bind_param("ii", $role, $last_insert_id);
							if ($stmt_p->execute()) {
								echo "<br>Inserted Linking permission: " . $module['module_name'] . " - $action\n\n\n";
							} else {
								echo "<br>Error Linking inserting " . $module['module_name'] . " - $action: " . $stmt_p->error . "\n\n\n";
							}
						}
                        echo "<br><br><br>Inserted permission: " . $module['module_name'] . " - $action\n";
                    } else {
                        echo "<br>Error inserting " . $module['module_name'] . " - $action: " . $stmt->error . "\n";
                    }
                }
            }
            $stmt->close();
        }
		$conn->close();
	}
	function hasPermission($user_id, $module, $action) {
		
		if($_SESSION["user"]["role_id"] == 1 || $_SESSION["user"]["role"] == "admin"){ // Admin has all permissions
			return true;
		}
		global $conn;
		$sql = "SELECT COUNT(*) AS total
				FROM vp_role_permissions rp
				JOIN vp_permissions p ON rp.permission_id = p.id
				JOIN vp_users u ON u.role_id = rp.role_id
				WHERE u.id='$user_id' AND p.module_name='$module' AND p.action_name='$action'";
		$res = $conn->query($sql);
		$row = mysqli_fetch_assoc($res);
		return $row['total'] > 0;
	}
	function checkPermission($module, $action) {
		session_start();
		$user_id = $_SESSION["user"]["id"] ?? 0;
		if (!$user_id || !hasPermission($user_id, $module, $action)) {
			header("Location: index.php?page=dashboard");
			exit;
		}
	}
?>
