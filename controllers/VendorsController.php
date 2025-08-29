<?php
require_once 'models/vendor/vendor.php';
$vendorsModel = new Vendor($conn);

global $root_path;
global $domain;
class VendorsController {
    public function index() {
        is_login();
        global $vendorsModel;
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Orders per page
        $offset = ($page - 1) * $limit;

        $vendors = $vendorsModel->getAllVendors($limit, $offset);
        $total_vendors = count($vendors);
        $total_pages = ceil($total_vendors / $limit);
        renderTemplate('views/vendors/index.php', [
            'vendors' => $vendors,
            'total_vendors' => $total_vendors,
            'limit' => $limit,
            'offset' => $offset,
            'total_pages' => $total_pages
            ], 
             'Manage Vendors');
    }
    public function addEditVendor() {
        echo 'Hedya';
        global $vendorsModel;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $vendor = $vendorsModel->getVendorById($id);    
            if ($vendor) {
                renderTemplate('views/vendors/add_edit.php', ['vendor' => $vendor], 'Edit Vendor');
            } else {
                renderTemplate('views/errors/not_found.php', [], 'Vendor Not Found');
            }   
        } else {
            renderTemplate('views/vendors/add_edit.php', [], 'Add New Vendor');
        }
        exit;
    }
    public function addPost() {
        global $vendorsModel;
        $data = $_POST;
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        if ($id > 0) {
            $result = $vendorsModel->updateVendor($id, $data);
            //$message = $result ? 'Vendor updated successfully.' : 'Failed to update vendor.';
        } else {
            $result = $vendorsModel->addVendor($data);            
            //$message = $result ? 'Vendor added successfully.' : 'Failed to add vendor.';
        }
        echo json_encode($result);
        // if (!$result) {
        //     echo json_encode(['success' => false, 'message' => 'Database operation failed.']);
        //     exit;
        // }else {
        //     echo json_encode(['success' => true, 'message' => 'Vendor saved successfully.']);
        // }
        
        exit;
        //$vendors = $vendorsModel->getAllVendors();
        //renderTemplate('views/vendors/index.php', ['vendors' => $vendors, 'message' => $message], 'Manage Vendors');
    }
    public function delete() {
        global $vendorsModel;
        // Try to get id from JSON or POST
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int)$data['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
        if ($id > 0) {
            $result = $vendorsModel->deleteVendor($id);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Vendor deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete vendor.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid vendor ID.'.$id]);
        }
        exit;
    }
    
}
?>