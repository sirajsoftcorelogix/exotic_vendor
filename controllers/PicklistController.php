<?php

require_once 'models/picklist/Picklist.php';
require_once 'models/order/order.php';
require_once 'models/comman/tables.php';

$picklistModel = new Picklist($conn);
$ordersModel = new Order($conn);
$commanModel = new Tables($conn);

class PicklistController
{
    public function index()
    {
        is_login();
        global $picklistModel;
        global $commanModel;

        $pageNo = isset($_GET['page_no']) ? (int) $_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;

        $filters = [
            'search_text' => isset($_GET['search_text']) ? trim((string) $_GET['search_text']) : '',
            'status' => isset($_GET['status']) ? trim((string) $_GET['status']) : '',
            'picker_id' => isset($_GET['picker_id']) ? (int) $_GET['picker_id'] : 0,
        ];

        $result = $picklistModel->searchPicklists($filters, $pageNo, $limit);
        $totalPages = $limit > 0 ? (int) ceil($result['total'] / $limit) : 1;

        renderTemplate('views/picklist/index.php', [
            'picklists' => $result['rows'],
            'total_records' => $result['total'],
            'page_no' => $pageNo,
            'total_pages' => max(1, $totalPages),
            'limit' => $limit,
            'filters' => $filters,
            'picker_list' => $commanModel->get_picker_list(),
        ], 'Picklists');
    }

    public function view()
    {
        is_login();
        global $picklistModel;

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $picklist = $picklistModel->getPicklistById($id);
        if (!$picklist) {
            $_SESSION['picklist_flash'] = ['type' => 'error', 'text' => 'Picklist not found.'];
            header('Location: index.php?page=picklist&action=list');
            exit;
        }

        $items = $picklistModel->getPicklistItems($id);
        $isPrint = isset($_GET['print']) && (string) $_GET['print'] === '1';
        $template = $isPrint ? 'views/picklist/print.php' : 'views/picklist/view.php';
        $title = $isPrint ? 'Print Picklist' : 'Picklist ' . ($picklist['picklist_number'] ?? '');

        renderTemplate($template, [
            'picklist' => $picklist,
            'items' => $items,
        ], $title);
    }

    public function tablet()
    {
        is_login();
        global $picklistModel;
        global $commanModel;

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $picklist = $picklistModel->getPicklistById($id);
        if (!$picklist) {
            $_SESSION['picklist_flash'] = ['type' => 'error', 'text' => 'Picklist not found.'];
            header('Location: index.php?page=picklist&action=list');
            exit;
        }

        renderTemplate('views/picklist/tablet.php', [
            'picklist' => $picklist,
            'items' => $picklistModel->getPicklistItems($id),
            'picker_list' => $commanModel->get_picker_list(),
        ], 'Pick — ' . ($picklist['picklist_number'] ?? ''));
    }

    public function bulkAddFromOrders()
    {
        is_login();
        global $picklistModel;
        global $ordersModel;
        global $commanModel;

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $orderIds = isset($_POST['order_ids']) && is_array($_POST['order_ids']) ? $_POST['order_ids'] : [];
        $pickerId = (int) ($_POST['picker_id'] ?? 0);
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $createdBy = (int) ($_SESSION['user']['id'] ?? 0);

        $result = $picklistModel->createFromOrders($orderIds, $pickerId, $createdBy, $notes);
        if (empty($result['success'])) {
            echo json_encode($result);
            exit;
        }

        $addedOrderIds = $result['order_ids'] ?? [];
        if ($addedOrderIds !== []) {
            $this->syncOrderStatusBulk($addedOrderIds, 'added_to_picklist', $ordersModel, $commanModel);
        }

        $msg = 'Picklist ' . ($result['picklist_number'] ?? '') . ' created with ' . (int) ($result['added'] ?? 0) . ' item(s).';
        if (!empty($result['skipped'])) {
            $msg .= ' ' . count($result['skipped']) . ' order(s) skipped.';
        }

        echo json_encode([
            'success' => true,
            'message' => $msg,
            'picklist_id' => $result['picklist_id'] ?? 0,
            'picklist_number' => $result['picklist_number'] ?? '',
            'redirect' => 'index.php?page=picklist&action=view&id=' . (int) ($result['picklist_id'] ?? 0),
            'skipped' => $result['skipped'] ?? [],
        ]);
        exit;
    }

    public function pickItem()
    {
        is_login();
        global $picklistModel;
        global $ordersModel;
        global $commanModel;

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $itemId = (int) ($_POST['item_id'] ?? 0);
        $userId = (int) ($_SESSION['user']['id'] ?? 0);

        $result = $picklistModel->markItemPicked($itemId, $userId);
        if (empty($result['success'])) {
            echo json_encode($result);
            exit;
        }

        if (!empty($result['order_id'])) {
            $this->syncOrderStatus((int) $result['order_id'], 'item_picked', $ordersModel, $commanModel);
        }

        echo json_encode($result);
        exit;
    }

    public function assignPicker()
    {
        is_login();
        global $picklistModel;

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $picklistId = (int) ($_POST['picklist_id'] ?? 0);
        $pickerId = (int) ($_POST['picker_id'] ?? 0);

        echo json_encode($picklistModel->assignPicker($picklistId, $pickerId));
        exit;
    }

    public function getPickers()
    {
        is_login();
        global $commanModel;

        header('Content-Type: application/json');
        $pickers = [];
        foreach ($commanModel->get_picker_list() as $id => $name) {
            $pickers[] = ['id' => (int) $id, 'name' => (string) $name];
        }
        echo json_encode(['success' => true, 'pickers' => $pickers]);
        exit;
    }

    public function delete()
    {
        is_login();
        global $picklistModel;
        global $ordersModel;
        global $commanModel;

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['picklist_flash'] = ['type' => 'error', 'text' => 'Invalid picklist.'];
            header('Location: index.php?page=picklist&action=list');
            exit;
        }

        $picklist = $picklistModel->getPicklistById($id);
        if (!$picklist) {
            $_SESSION['picklist_flash'] = ['type' => 'error', 'text' => 'Picklist not found.'];
            header('Location: index.php?page=picklist&action=list');
            exit;
        }

        $result = $picklistModel->deletePicklist($id);
        if (empty($result['success'])) {
            $_SESSION['picklist_flash'] = [
                'type' => 'error',
                'text' => (string) ($result['message'] ?? 'Could not delete picklist.'),
            ];
            header('Location: index.php?page=picklist&action=list');
            exit;
        }

        $picklistNumber = (string) ($result['picklist_number'] ?? $picklist['picklist_number'] ?? '');
        $orderIds = $result['order_ids'] ?? [];
        foreach ($orderIds as $oid) {
            $this->revertOrderAfterPicklistRemoval((int) $oid, $picklistNumber, $ordersModel, $commanModel, false);
        }

        $_SESSION['picklist_flash'] = [
            'type' => 'success',
            'text' => 'Picklist ' . $picklistNumber . ' deleted.',
        ];
        header('Location: index.php?page=picklist&action=list');
        exit;
    }

    public function deleteItem()
    {
        is_login();
        global $picklistModel;
        global $ordersModel;
        global $commanModel;

        $itemId = (int) ($_POST['item_id'] ?? $_GET['item_id'] ?? 0);
        $wantsJson = ($_SERVER['REQUEST_METHOD'] === 'POST')
            || (isset($_GET['format']) && (string) $_GET['format'] === 'json');

        if ($itemId <= 0) {
            if ($wantsJson) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid item.']);
                exit;
            }
            $_SESSION['picklist_flash'] = ['type' => 'error', 'text' => 'Invalid picklist item.'];
            header('Location: index.php?page=picklist&action=list');
            exit;
        }

        $result = $picklistModel->deletePicklistItem($itemId);
        if (empty($result['success'])) {
            if ($wantsJson) {
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
            }
            $_SESSION['picklist_flash'] = [
                'type' => 'error',
                'text' => (string) ($result['message'] ?? 'Could not remove item.'),
            ];
            $picklistId = (int) ($_GET['picklist_id'] ?? 0);
            header('Location: index.php?page=picklist&action=' . ($picklistId > 0 ? 'view&id=' . $picklistId : 'list'));
            exit;
        }

        $orderId = (int) ($result['order_id'] ?? 0);
        $picklistNumber = (string) ($result['picklist_number'] ?? '');
        $picklistId = (int) ($result['picklist_id'] ?? 0);
        $picklistDeleted = !empty($result['picklist_deleted']);

        if ($orderId > 0) {
            $this->revertOrderAfterPicklistRemoval($orderId, $picklistNumber, $ordersModel, $commanModel, true);
        }

        if ($wantsJson) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => (string) ($result['message'] ?? 'Item removed.'),
                'picklist_id' => $picklistId,
                'picklist_deleted' => $picklistDeleted,
                'redirect' => $picklistDeleted
                    ? 'index.php?page=picklist&action=list'
                    : 'index.php?page=picklist&action=view&id=' . $picklistId,
            ]);
            exit;
        }

        if ($picklistDeleted) {
            $_SESSION['picklist_flash'] = [
                'type' => 'success',
                'text' => 'Last item removed — picklist ' . $picklistNumber . ' deleted.',
            ];
            header('Location: index.php?page=picklist&action=list');
            exit;
        }

        $_SESSION['picklist_flash'] = [
            'type' => 'success',
            'text' => 'Item removed from picklist ' . $picklistNumber . '.',
        ];
        header('Location: index.php?page=picklist&action=view&id=' . $picklistId);
        exit;
    }

    private function revertOrderAfterPicklistRemoval(
        int $orderId,
        string $picklistNumber,
        $ordersModel,
        $commanModel,
        bool $singleItemLog = false
    ): void {
        if ($orderId <= 0) {
            return;
        }

        $order = $ordersModel->getOrderById($orderId);
        if (!$order) {
            return;
        }

        $status = (string) ($order['status'] ?? '');
        if ($status === 'added_to_picklist' || $status === 'item_picked') {
            $this->syncOrderStatus($orderId, 'item_received', $ordersModel, $commanModel);
        }

        $logText = $singleItemLog
            ? 'Removed from picklist: ' . $picklistNumber
            : 'Picklist deleted: ' . $picklistNumber;
        $commanModel->add_order_status_log([
            'order_id' => $orderId,
            'status' => $logText,
            'changed_by' => (int) ($_SESSION['user']['id'] ?? 0),
            'api_response' => null,
            'change_date' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param int[] $orderIds
     */
    private function syncOrderStatusBulk(array $orderIds, string $status, $ordersModel, $commanModel): void
    {
        if ($orderIds === []) {
            return;
        }
        $ordersModel->updateStatusBulk($orderIds, $status);
        foreach ($orderIds as $oid) {
            $this->writeStatusLogAndApi((int) $oid, $status, $ordersModel, $commanModel);
        }
    }

    private function syncOrderStatus(int $orderId, string $status, $ordersModel, $commanModel): void
    {
        if ($orderId <= 0) {
            return;
        }
        $ordersModel->updateStatusBulk([$orderId], $status);
        $this->writeStatusLogAndApi($orderId, $status, $ordersModel, $commanModel);
    }

    private function writeStatusLogAndApi(int $orderId, string $status, $ordersModel, $commanModel): void
    {
        $logData = [
            'order_id' => $orderId,
            'status' => 'Status: ' . $status,
            'changed_by' => (int) ($_SESSION['user']['id'] ?? 0),
            'api_response' => null,
            'change_date' => date('Y-m-d H:i:s'),
        ];
        $commanModel->add_order_status_log($logData);

        $orderval = $ordersModel->getOrderById($orderId);
        if (!$orderval) {
            return;
        }

        $statusCode = $commanModel->getExoticIndiaOrderStatusCode($status);
        $adminId = (int) ($statusCode['admin_id'] ?? 0);
        if ($adminId > 0) {
            $apidata = [
                'orderid' => $orderval['order_number'],
                'level' => 'item',
                'order_status' => $adminId,
                'size' => trim((string) ($orderval['size'] ?? '')),
                'color' => trim((string) ($orderval['color'] ?? '')),
                'itemcode' => trim((string) ($orderval['item_code'] ?? '')),
            ];
            $commanModel->updateExoticIndiaOrderStatus($apidata);
        }

        if (!empty($orderval['agent_id']) && (int) $orderval['agent_id'] > 0) {
            $link = base_url('index.php?page=orders&action=list&' . $orderId);
            insertNotification(
                (int) $orderval['agent_id'],
                'Order Status Updated',
                'The status of an order assigned to you has been updated. Please check the order details.',
                $link
            );
        }
    }
}
