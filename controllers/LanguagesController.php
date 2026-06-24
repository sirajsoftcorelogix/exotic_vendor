<?php

require_once 'models/languages/Language.php';

$languagesModel = new Language($conn);

class LanguagesController
{
    public function index()
    {
        is_login();
        global $languagesModel;

        $search = isset($_GET['search_text']) ? trim((string) $_GET['search_text']) : '';
        $status_filter = isset($_GET['status_filter']) ? trim((string) $_GET['status_filter']) : '';

        $page_no = isset($_GET['page_no']) ? (int) $_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;

        $pt_data = $languagesModel->getAll($page_no, $limit, $search, $status_filter);

        $data = [
            'languages_data' => $pt_data['languages'],
            'page_no' => $page_no,
            'total_pages' => $pt_data['totalPages'],
            'search' => $search,
            'totalPages' => $pt_data['totalPages'],
            'currentPage' => $pt_data['currentPage'],
            'limit' => $limit,
            'totalRecords' => $pt_data['totalRecords'],
            'status_filter' => $status_filter,
        ];

        renderTemplate('views/languages/index.php', $data, 'Book Languages');
    }

    public function addRecord()
    {
        is_login();
        global $languagesModel;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }

        $data = $_POST;
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        if ($id > 0) {
            $result = $languagesModel->updateRecord($id, $data);
        } else {
            $result = $languagesModel->addRecord($data);
        }
        echo json_encode($result);
        exit;
    }

    public function delete()
    {
        is_login();
        global $languagesModel;

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $data = $_POST;
        }
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        if ($id > 0) {
            echo json_encode($languagesModel->deleteRecord($id));
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        }
        exit;
    }

    public function permanentDelete()
    {
        is_login();
        global $languagesModel;

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $data = $_POST;
        }
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        if ($id > 0) {
            echo json_encode($languagesModel->permanentlyDeleteRecord($id));
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        }
        exit;
    }

    public function getDetails()
    {
        is_login();
        global $languagesModel;

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id > 0) {
            $record = $languagesModel->getRecord($id);
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
