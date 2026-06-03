<?php
require_once 'models/account_group/AccountGroup.php';

class AccountGroupsController
{
    private AccountGroup $accountGroupModel;

    public function __construct(mysqli $conn)
    {
        $this->accountGroupModel = new AccountGroup($conn);
    }

    public function index(): void
    {
        is_login();

        $search = trim((string)($_GET['search_text'] ?? ''));
        $status = trim((string)($_GET['status_filter'] ?? ''));
        $itemGroupFilter = trim((string)($_GET['item_group_filter'] ?? ''));
        $pageNo = max(1, (int)($_GET['page_no'] ?? 1));
        $limit = (int)($_GET['limit'] ?? 20);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;

        if ($itemGroupFilter !== '' && !$this->accountGroupModel->isValidItemGroup($itemGroupFilter)) {
            $itemGroupFilter = '';
        }

        $listing = $this->accountGroupModel->getAccountGroups($pageNo, $limit, $search, $status, $itemGroupFilter);
        renderTemplate('views/account_groups/index.php', [
            'account_groups' => $listing['account_groups'],
            'item_groups' => $this->accountGroupModel->getParentItemGroups(),
            'item_group_labels' => $this->accountGroupModel->getItemGroupLabelMap(),
            'search' => $search,
            'status_filter' => $status,
            'item_group_filter' => $itemGroupFilter,
            'currentPage' => $listing['currentPage'],
            'totalPages' => $listing['totalPages'],
            'totalRecords' => $listing['totalRecords'],
            'limit' => $listing['limit'],
        ], 'Manage Account Groups');
    }

    public function save(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $id = trim((string)($_POST['id'] ?? '')) !== '' ? (int)$_POST['id'] : null;
        $name = trim((string)($_POST['account_group_name'] ?? ''));
        $itemGroupRaw = trim((string)($_POST['item_group'] ?? ''));
        $itemGroup = $itemGroupRaw !== '' ? $itemGroupRaw : null;
        $isActive = (int)($_POST['is_active'] ?? 1);

        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Account group name is required.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        if (!$this->accountGroupModel->isValidItemGroup($itemGroup)) {
            echo json_encode(['success' => false, 'message' => 'Please select a valid item group.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        if ($this->accountGroupModel->accountGroupNameExists($name, ($id && $id > 0) ? $id : null)) {
            echo json_encode(['success' => false, 'message' => 'Account group name already exists'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        if ($id && $id > 0) {
            $existing = $this->accountGroupModel->getAccountGroupById($id);
            if (!$existing) {
                echo json_encode(['success' => false, 'message' => 'Account group not found.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }

            $result = $this->accountGroupModel->saveAccountGroup($id, $name, $itemGroup, $isActive);
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $result = $this->accountGroupModel->insertAccountGroup($name, $itemGroup, $isActive);
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function details(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)($_GET['id'] ?? 0);
        $row = $this->accountGroupModel->getAccountGroupById($id);
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Account group not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'account_group' => $row], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function status(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 0);
        echo json_encode($this->accountGroupModel->setStatus($id, $isActive), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function delete(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid account group id.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $existing = $this->accountGroupModel->getAccountGroupById($id);
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Account group not found.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        echo json_encode($this->accountGroupModel->deleteAccountGroup($id), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function checkName(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $name = trim((string)($_GET['name'] ?? ''));
        $excludeId = isset($_GET['excludeId']) ? (int) $_GET['excludeId'] : 0;
        echo json_encode(
            $this->accountGroupModel->checkAccountGroupName($name, $excludeId > 0 ? $excludeId : null),
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        exit;
    }
}
