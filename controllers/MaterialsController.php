<?php

require_once 'models/materials/Material.php';

$materialsModel = new Material($conn);

class MaterialsController
{
    public function index()
    {
        is_login();
        global $materialsModel;

        $search = isset($_GET['search_text']) ? trim((string) $_GET['search_text']) : '';
        $status_filter = isset($_GET['status_filter']) ? trim((string) $_GET['status_filter']) : '';

        $page_no = isset($_GET['page_no']) ? (int) $_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;

        $pt_data = $materialsModel->getAll($page_no, $limit, $search, $status_filter);

        $data = [
            'materials_data' => $pt_data['materials'],
            'page_no' => $page_no,
            'total_pages' => $pt_data['totalPages'],
            'search' => $search,
            'totalPages' => $pt_data['totalPages'],
            'currentPage' => $pt_data['currentPage'],
            'limit' => $limit,
            'totalRecords' => $pt_data['totalRecords'],
            'status_filter' => $status_filter,
            'next_display_order' => $materialsModel->getNextDisplayOrder(),
        ];

        renderTemplate('views/materials/index.php', $data, 'Material listing');
    }

    public function addRecord()
    {
        is_login();
        global $materialsModel;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }

        $data = $_POST;
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        if ($id > 0) {
            $result = $materialsModel->updateRecord($id, $data);
        } else {
            $userId = (int) ($_SESSION['user']['id'] ?? $_POST['user_id'] ?? 0);
            $result = $materialsModel->addRecord($data, $userId);
        }
        echo json_encode($result);
        exit;
    }

    public function delete()
    {
        is_login();
        global $materialsModel;

        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int) $data['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
        if ($id > 0) {
            echo json_encode($materialsModel->deleteRecord($id));
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        }
        exit;
    }

    public function getDetails()
    {
        is_login();
        global $materialsModel;

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id > 0) {
            $record = $materialsModel->getRecord($id);
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
