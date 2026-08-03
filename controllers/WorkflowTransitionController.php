<?php

require_once 'models/workflow/WorkflowTransition.php';

$workflowTransitionModel = new WorkflowTransition($conn);

class WorkflowTransitionController
{
    public function index()
    {
        is_login();
        global $workflowTransitionModel;

        $search = isset($_GET['search_text']) ? trim((string) $_GET['search_text']) : '';
        $statusFilter = isset($_GET['status_filter']) ? trim((string) $_GET['status_filter']) : '';
        $fromFilter = isset($_GET['from_filter']) ? (int) $_GET['from_filter'] : 0;
        $toFilter = isset($_GET['to_filter']) ? (int) $_GET['to_filter'] : 0;

        $pageNo = isset($_GET['page_no']) ? (int) $_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;

        $data = $workflowTransitionModel->getAll($pageNo, $limit, $search, $statusFilter, $fromFilter, $toFilter);

        renderTemplate('views/workflow_transition/index.php', [
            'transition_rows' => $data['rows'],
            'page_no' => $pageNo,
            'total_pages' => $data['totalPages'],
            'search' => $search,
            'totalPages' => $data['totalPages'],
            'currentPage' => $data['currentPage'],
            'limit' => $limit,
            'totalRecords' => $data['totalRecords'],
            'status_filter' => $statusFilter,
            'from_filter' => $fromFilter,
            'to_filter' => $toFilter,
            'status_options' => $workflowTransitionModel->getSelectableStatuses(),
            'enforcement_active' => $workflowTransitionModel->isEnforcementActive(),
        ], 'Workflow Transitions');
    }

    public function addRecord()
    {
        is_login();
        global $workflowTransitionModel;

        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }

        $data = $_POST;
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        $data['created_by'] = (int) ($_SESSION['user']['id'] ?? 0);

        if ($id > 0) {
            echo json_encode($workflowTransitionModel->updateRecord($id, $data));
        } else {
            echo json_encode($workflowTransitionModel->addRecord($data));
        }
        exit;
    }

    public function delete()
    {
        is_login();
        global $workflowTransitionModel;

        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $data = $_POST;
        }
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            exit;
        }

        echo json_encode($workflowTransitionModel->deleteRecord($id));
        exit;
    }

    public function toggleActive()
    {
        is_login();
        global $workflowTransitionModel;

        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $data = $_POST;
        }
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        $active = !empty($data['is_active']);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            exit;
        }

        echo json_encode($workflowTransitionModel->setActive($id, $active));
        exit;
    }

    public function getDetails()
    {
        is_login();
        global $workflowTransitionModel;

        header('Content-Type: application/json; charset=utf-8');
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            exit;
        }

        $record = $workflowTransitionModel->getRecord($id);
        if (!$record) {
            echo json_encode(['success' => false, 'message' => 'Record not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'record' => $record], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function allowedTargets()
    {
        is_login();
        global $conn;

        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../helpers/order_workflow.php';

        $fromSlug = isset($_GET['from_slug']) ? trim((string) $_GET['from_slug']) : '';
        $payload = order_workflow_allowed_targets($conn, $fromSlug);

        echo json_encode(array_merge(['success' => true], $payload), JSON_UNESCAPED_UNICODE);
        exit;
    }
}
