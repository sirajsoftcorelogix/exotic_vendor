<?php
	require_once 'models/addons/addons.php';
	$addonsModel = new Addons($conn);

	class AddonsController {
		public function addons_list() {
			global $addonsModel;
			$sort_by = $_GET['sort_by'] ?? 'id';
			$sort_order = $_GET['sort_order'] ?? 'desc';
			$search = $_GET['search'] ?? '';
			
			$page_no = isset($_GET['page_no']) && is_numeric($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
			$limit = 20;
			$offset = ($page_no - 1) * $limit;

			$addons_data = $addonsModel->getAll($search, $sort_by, $sort_order, $limit, $offset);
			$total_records = $addonsModel->countAll($search);
			
			$data = [
				'addons' => $addons_data,
				'page_no' => $page_no,
				'total_pages' => ceil($total_records / $limit),
				'sort_by' => $sort_by,
				'sort_order' => $sort_order,
				'search' => $search
			];
			renderTemplate('views/addons/index.php', $data, 'AddOns');
		}
		
		public function addEditAddon() {
			global $addonsModel;
			$data=array();
			try {
				$id = isset($_GET['id']) ? $_GET['id'] : 0;
				
				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					
					$id = isset($_POST['id']) ? $_POST['id'] : 0;
					//print_array($_POST);
					if($id>=0){
						$data['message'] = $addonsModel->update($id,$_POST);
					}else{
						$data['message'] = $addonsModel->insert($_POST);
					}
				}
			} catch (Exception $e) {
				$data['message'] = ['success' => false, 'error' => $e->getMessage()];
			}
			if($id>=0){
				$data['addon'] = $addonsModel->getAddon($id);
			}
			renderTemplate('views/addons/addEditAddon.php', $data, 'Addons');
		}
		
		public function delete() {
			global $addonsModel;
			try {
				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					$id = $_POST['id'];
					unset($_POST['id']);
					$result = $addonsModel->delete($id);
					echo json_encode(['success' => $result]);
				} else {
					echo json_encode(['success' => false, 'error' => 'Invalid request']);
				}
			} catch (Exception $e) {
				echo json_encode(['success' => false, 'error' => $e->getMessage()]);
			}
		}
		
	}
?>