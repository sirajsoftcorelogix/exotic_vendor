<?php

require_once 'models/locations/Location.php';

$locationsModel = new Location($conn);

class LocationsController
{
    public function index()
    {
        is_login();
        global $locationsModel;
        $search = isset($_GET['search_text']) ? trim((string) $_GET['search_text']) : '';
        $status_filter = isset($_GET['status_filter']) ? trim((string) $_GET['status_filter']) : '';
        $type_filter = isset($_GET['type_filter']) ? trim((string) $_GET['type_filter']) : '';

        $page_no = isset($_GET['page_no']) ? (int) $_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $limit = in_array($limit, [5, 20, 50, 100], true) ? $limit : 20;

        $pt_data = $locationsModel->getAll($page_no, $limit, $search, $status_filter, $type_filter);

        $data = [
            'locations_data' => $pt_data['locations'],
            'page_no' => $page_no,
            'total_pages' => $pt_data['totalPages'],
            'search' => $search,
            'totalPages' => $pt_data['totalPages'],
            'currentPage' => $pt_data['currentPage'],
            'limit' => $limit,
            'totalRecords' => $pt_data['totalRecords'],
            'status_filter' => $status_filter,
            'type_filter' => $type_filter,
        ];

        renderTemplate('views/locations/index.php', $data, 'Manage Locations');
    }

    public function addRecord()
    {
        is_login();
        global $locationsModel;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $id = isset($data['id']) ? (int) $data['id'] : 0;
            if ($id > 0) {
                $result = $locationsModel->updateRecord($id, $data);
            } else {
                $result = $locationsModel->addRecord($data);
            }
            echo json_encode($result);
        }
        exit;
    }

    public function delete()
    {
        is_login();
        global $locationsModel;
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int) $data['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
        if ($id > 0) {
            $result = $locationsModel->deleteRecord($id);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        }
        exit;
    }

    public function getDetails()
    {
        is_login();
        global $locationsModel;
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id > 0) {
            $record = $locationsModel->getRecord($id);
            if ($record) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Record not found.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID.']);
        }
        exit;
    }
}
