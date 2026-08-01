<?php

require_once 'models/broker/Broker.php';
require_once 'models/country/state.php';

$brokerModel = new Broker($conn);

class BrokersController
{
    public function index(): void
    {
        is_login();
        global $brokerModel;

        $search = isset($_GET['search_text']) ? trim((string) $_GET['search_text']) : '';
        $statusFilter = isset($_GET['status_filter']) ? trim((string) $_GET['status_filter']) : '';

        $pageNo = isset($_GET['page_no']) ? (int) $_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;

        global $conn;
        $listing = $brokerModel->getAll($pageNo, $limit, $search, $statusFilter);
        $stateModel = new State($conn);
        $stateList = $stateModel->getAllStates(105);

        renderTemplate('views/brokers/index.php', [
            'brokers_data' => $listing['brokers'],
            'page_no' => $pageNo,
            'total_pages' => $listing['totalPages'],
            'search' => $search,
            'totalPages' => $listing['totalPages'],
            'currentPage' => $listing['currentPage'],
            'limit' => $limit,
            'totalRecords' => $listing['totalRecords'],
            'status_filter' => $statusFilter,
            'stateList' => $stateList['states'] ?? [],
        ], 'Broker Master');
    }

    public function addRecord(): void
    {
        is_login();
        global $brokerModel;

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }

        $data = $_POST;
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        $result = $id > 0
            ? $brokerModel->updateRecord($id, $data)
            : $brokerModel->addRecord($data);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function delete(): void
    {
        is_login();
        global $brokerModel;

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $id = isset($data['id']) ? (int) $data['id'] : 0;
        header('Content-Type: application/json; charset=utf-8');
        if ($id > 0) {
            echo json_encode($brokerModel->deleteRecord($id), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        }
        exit;
    }

    public function permanentDelete(): void
    {
        is_login();
        global $brokerModel;

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $id = isset($data['id']) ? (int) $data['id'] : 0;
        header('Content-Type: application/json; charset=utf-8');
        if ($id > 0) {
            echo json_encode($brokerModel->permanentlyDeleteRecord($id), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        }
        exit;
    }

    public function getDetails(): void
    {
        is_login();
        global $brokerModel;

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        header('Content-Type: application/json; charset=utf-8');
        if ($id > 0) {
            $record = $brokerModel->getRecord($id);
            if ($record) {
                echo json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Record not found.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID.']);
        }
        exit;
    }

    public function searchBrokers(): void
    {
        is_login();
        global $brokerModel;

        $query = trim((string) ($_GET['q'] ?? ''));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            $brokerModel->searchActiveBrokers($query),
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        exit;
    }
}
