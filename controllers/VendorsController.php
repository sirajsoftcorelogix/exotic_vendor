<?php
require_once 'models/vendor/vendor.php';
$vendorsModel = new Vendor($conn);

global $root_path;
global $domain;
class VendorsController {
    public function index() {
        global $vendorsModel;
        $vendors = $vendorsModel->getAllVendors();
        renderTemplate('views/vendors/index.php', ['vendors' => $vendors], 'Manage Vendors');
    }
    public function addEditVendor() {
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
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Database operation failed.']);
            exit;
        }else {
            echo json_encode(['success' => true, 'message' => 'Vendor saved successfully.']);
        }
        
        exit;
        //$vendors = $vendorsModel->getAllVendors();
        //renderTemplate('views/vendors/index.php', ['vendors' => $vendors, 'message' => $message], 'Manage Vendors');
    }
    
}
?>