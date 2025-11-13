<?php
require_once 'models/vendor/vendor.php';
require_once 'models/country/country.php';
require_once 'models/country/state.php';
require_once 'models/teams/Teams.php';

$vendorsModel = new Vendor($conn);
$countryModel = new Country($conn);
$stateModel = new State($conn);
$teamModel = new Teams($conn);

global $root_path;
global $domain;
class VendorsController {
    public function index() {
        is_login();
        global $vendorsModel;
        global $countryModel;
        global $stateModel;
        global $teamModel;

        $search = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
        $status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
        
        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Users per page, default 5
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 20; // If user select value from dropdown

        $vendors_data = $vendorsModel->getAllVendorsListing($page_no, $limit, $search, $status_filter); //$vendorsModel->getAllVendors($limit, $offset);

        $countryList = $countryModel->getAllCountries();
        $stateList = $stateModel->getAllStates(105); // India ID = 105
        $teamList = $teamModel->getAllTeams();

        $data = [
            'vendors' => $vendors_data["vendors"],
            'page_no' => $page_no,
            'total_pages' => $vendors_data["totalPages"],
            'search' => $search,
            'totalPages'   => $vendors_data["totalPages"],
            'currentPage'  => $vendors_data["currentPage"],
            'limit'        => $limit,
            'totalRecords' => $vendors_data["totalRecords"],
            'status_filter'=> $status_filter,
            'countryList' => $countryList["countries"],
            'stateList' => $stateList["states"],
            'category' => $vendorsModel->listCategory(),
            'teamList' => $teamList
        ];
        
        renderTemplate('views/vendors/index.php', $data, 'Manage Vendors');
    }
    public function addVendorRecord() {
        is_login();
        global $vendorsModel;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $id = isset($data['id']) ? (int)$data['id'] : 0;
            if ($id > 0) {
                $result = $vendorsModel->updateVendor($id, $data);
            } else {
                $result = $vendorsModel->addVendor($data);            
            }
            echo json_encode($result);
        }
        exit;
    }
    public function delete() {
        global $vendorsModel;
        // Try to get id from JSON or POST
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int)$data['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
        if ($id > 0) {
            $result = $vendorsModel->deleteVendor($id);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid vendor ID.'.$id]);
        }
        exit;
    }
    public function getVendorDetails() {
        global $vendorsModel;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $vendor = $vendorsModel->getVendorById($id);
            $vendor['categories'] = $vendorsModel->getVendorCategories($id);
            $vendor['teamIds'] = $vendorsModel->getVendorTeams($id);
            if ($vendor) {
                echo json_encode($vendor);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Vendor not found.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid vendor ID.']);
        }
        exit;
    }
    public function getAllCountries() {
        global $countryModel;
        $countries = $countryModel->getAllCountries();
        echo json_encode($countries);
        exit;
    }
    public function getStatesByCountry($country_id) {
        global $stateModel;
        $states = $stateModel->getAllStates($country_id);
        echo json_encode($states["states"]);
        exit;
    }
    public function getBankDetails() {
        global $vendorsModel;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $bankdtls = $vendorsModel->getBankDetailsById($id);
            if (!empty($bankdtls) && is_array($bankdtls)) {
                echo json_encode($bankdtls);
            } else {
                echo json_encode(['status' => 'success', 'message' => '']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid vendor ID.']);
        }
        exit;
    }
    public function addBankDetails() {
        global $vendorsModel;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['bdStatus'] = 1;
            $vendor_id = isset($data['vendor_id']) ? (int)$data['vendor_id'] : 0;
            $bankdtls = $vendorsModel->getBankDetailsById($vendor_id);
            if ($bankdtls) {
                $result = $vendorsModel->updateBankDetails($data);
            } else {
                $result = $vendorsModel->saveBankDetails($data);            
            }
            echo json_encode($result);
        }
        exit;
    }
    public function getTeamMembers() {
        global $vendorsModel;
        $team_id = isset($_GET['teamId']) ? $_GET['teamId'] : 0;
        if ($team_id > 0) {
            $members = $vendorsModel->getTeamMembers($team_id);
            echo json_encode($members);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid team ID.']);
        }
        exit;
    }
    public function checkVendorName() {
        global $vendorsModel;
        if (!isset($_GET['vendorName']) || empty($_GET['vendorName'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Vendor Name.']);
            exit;
        }

        $vendorName = trim($_GET['vendorName']);
        $result = $vendorsModel->checkVendorName($vendorName);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    public function checkEmail() {
        global $vendorsModel;
        if (!isset($_GET['email']) || empty($_GET['email'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Email Address.']);
            exit;
        }

        $email = trim($_GET['email']);
        $result = $vendorsModel->checkEmail($email);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    public function checkPhoneNumber() {
        global $vendorsModel;

        if (!isset($_GET['phone']) || empty($_GET['phone'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Phone Number.']);
            exit;
        }

        $phone = trim($_GET['phone']);
        $result = $vendorsModel->checkPhoneNumber($phone);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;

    }
}
?>