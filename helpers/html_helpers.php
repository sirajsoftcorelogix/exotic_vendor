<?php

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
?>