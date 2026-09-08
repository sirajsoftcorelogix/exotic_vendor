<?php

require_once 'models/size/SizeMaster.php';
require_once 'models/account_group/AccountGroup.php';

class SizesController
{
    private SizeMaster $sizeModel;
    private AccountGroup $accountGroupModel;

    public function __construct(mysqli $conn)
    {
        $this->sizeModel = new SizeMaster($conn);
        $this->accountGroupModel = new AccountGroup($conn);
    }

    public function index(): void
    {
        is_login();

        $search = trim((string) ($_GET['search_text'] ?? ''));
        $status = trim((string) ($_GET['status_filter'] ?? ''));
        $itemGroupFilter = trim((string) ($_GET['item_group_filter'] ?? ''));
        $pageNo = max(1, (int) ($_GET['page_no'] ?? 1));
        $limit = (int) ($_GET['limit'] ?? 20);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;

        if ($itemGroupFilter !== '' && !$this->accountGroupModel->isValidItemGroup($itemGroupFilter)) {
            $itemGroupFilter = '';
        }

        $listing = $this->sizeModel->getSizes($pageNo, $limit, $search, $status, $itemGroupFilter);
        renderTemplate('views/sizes/index.php', [
            'sizes' => $listing['sizes'],
            'item_groups' => $this->accountGroupModel->getParentItemGroups(),
            'item_group_labels' => $this->accountGroupModel->getItemGroupLabelMap(),
            'search' => $search,
            'status_filter' => $status,
            'item_group_filter' => $itemGroupFilter,
            'currentPage' => $listing['currentPage'],
            'totalPages' => $listing['totalPages'],
            'totalRecords' => $listing['totalRecords'],
            'limit' => $listing['limit'],
            'next_display_order' => $this->sizeModel->getNextDisplayOrder($itemGroupFilter),
        ], 'Manage Sizes');
    }

    public function save(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $id = trim((string) ($_POST['id'] ?? '')) !== '' ? (int) $_POST['id'] : 0;
        $itemGroup = trim((string) ($_POST['item_group'] ?? ''));
        $sizeCode = trim((string) ($_POST['size_code'] ?? ''));
        $sizeLabel = trim((string) ($_POST['size_label'] ?? ''));
        $displayOrder = (int) ($_POST['display_order'] ?? 0);
        $isActive = (int) ($_POST['is_active'] ?? 1);
        $userId = (int) ($_SESSION['user']['id'] ?? 0);

        if ($id > 0) {
            echo json_encode(
                $this->sizeModel->updateSize($id, $itemGroup, $sizeCode, $sizeLabel, $displayOrder, $isActive),
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );
            exit;
        }

        echo json_encode(
            $this->sizeModel->insertSize($itemGroup, $sizeCode, $sizeLabel, $displayOrder, $isActive, $userId),
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        exit;
    }

    public function details(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $id = (int) ($_GET['id'] ?? 0);
        $row = $this->sizeModel->getRecord($id);
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Size not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'size' => $row], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
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

        $id = (int) ($_POST['id'] ?? 0);
        $isActive = (int) ($_POST['is_active'] ?? 0);
        echo json_encode($this->sizeModel->setStatus($id, $isActive), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
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

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid size id.']);
            exit;
        }

        echo json_encode($this->sizeModel->deleteSize($id), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function checkCode(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $itemGroup = trim((string) ($_GET['item_group'] ?? ''));
        $sizeCode = trim((string) ($_GET['size_code'] ?? ''));
        $excludeId = isset($_GET['excludeId']) ? (int) $_GET['excludeId'] : 0;
        echo json_encode(
            $this->sizeModel->checkSizeCode($itemGroup, $sizeCode, $excludeId > 0 ? $excludeId : null),
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        exit;
    }

    public function nextOrder(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $itemGroup = trim((string) ($_GET['item_group'] ?? ''));
        echo json_encode([
            'success' => true,
            'next' => $this->sizeModel->getNextDisplayOrder($itemGroup),
        ]);
        exit;
    }
}
