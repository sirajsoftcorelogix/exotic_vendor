<?php

require_once 'models/order_status/OrderStatus.php';

$orderStatusModel = new OrderStatus($conn);

class OrderStatusController
{
    public function index()
    {
        is_login();
        global $orderStatusModel;

        $search = isset($_GET['search_text']) ? trim((string) $_GET['search_text']) : '';
        $status_filter = isset($_GET['status_filter']) ? trim((string) $_GET['status_filter']) : '';
        $parent_filter = isset($_GET['parent_filter']) ? trim((string) $_GET['parent_filter']) : '';

        $page_no = isset($_GET['page_no']) ? (int) $_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;

        $pt_data = $orderStatusModel->getAll($page_no, $limit, $search, $status_filter, $parent_filter);

        $data = [
            'order_status_rows' => $pt_data['rows'],
            'page_no' => $page_no,
            'total_pages' => $pt_data['totalPages'],
            'search' => $search,
            'totalPages' => $pt_data['totalPages'],
            'currentPage' => $pt_data['currentPage'],
            'limit' => $limit,
            'totalRecords' => $pt_data['totalRecords'],
            'status_filter' => $status_filter,
            'parent_filter' => $parent_filter,
            'parent_groups' => $orderStatusModel->getParentGroups(),
        ];

        renderTemplate('views/order_status/index.php', $data, 'Order Status Management');
    }

    public function addRecord()
    {
        is_login();
        global $orderStatusModel;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }

        $data = $_POST;
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        if ($id > 0) {
            $result = $orderStatusModel->updateRecord($id, $data);
        } else {
            $result = $orderStatusModel->addRecord($data);
        }
        echo json_encode($result);
        exit;
    }

    public function delete()
    {
        is_login();
        global $orderStatusModel;

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $data = $_POST;
        }
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        if ($id > 0) {
            echo json_encode($orderStatusModel->deleteRecord($id));
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        }
        exit;
    }

    public function permanentDelete()
    {
        is_login();
        global $orderStatusModel;

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $data = $_POST;
        }
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        if ($id > 0) {
            echo json_encode($orderStatusModel->permanentlyDeleteRecord($id));
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        }
        exit;
    }

    public function checkUsage()
    {
        is_login();
        global $orderStatusModel;

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            exit;
        }

        $usage = $orderStatusModel->getUsage($id);
        echo json_encode([
            'success' => true,
            'in_use' => $usage['in_use'],
            'order_count' => $usage['order_count'],
            'child_count' => $usage['child_count'],
            'can_delete' => !$usage['in_use'],
            'can_deactivate' => !$usage['in_use'],
        ]);
        exit;
    }

    public function getDetails()
    {
        is_login();
        global $orderStatusModel;

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id > 0) {
            $record = $orderStatusModel->getRecord($id);
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
