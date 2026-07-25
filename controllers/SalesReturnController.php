<?php

require_once __DIR__ . '/../models/sales_return/SalesReturn.php';
require_once __DIR__ . '/../models/PosInvoice/invoice.php';
require_once __DIR__ . '/../models/order/stock.php';
require_once __DIR__ . '/../models/user/user.php';
require_once __DIR__ . '/../helpers/sales_return_types.php';

$salesReturnModel = new SalesReturn($conn);

class SalesReturnController
{
    private function isAdminUser(): bool
    {
        return isset($_SESSION['user']['role_id']) && (int) $_SESSION['user']['role_id'] === 1;
    }

    private function getSessionWarehouseId(): int
    {
        $warehouseId = (int) ($_SESSION['warehouse_id'] ?? 0);
        if ($warehouseId <= 0 && !empty($_SESSION['user']['warehouse_id'])) {
            $warehouseId = (int) $_SESSION['user']['warehouse_id'];
        }

        return $warehouseId;
    }

    private function resolveWarehouseLabel($conn, int $warehouseId): string
    {
        if ($warehouseId <= 0) {
            return '—';
        }
        $stmt = $conn->prepare('SELECT address_title FROM exotic_address WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return 'Warehouse #' . $warehouseId;
        }
        $stmt->bind_param('i', $warehouseId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $name = trim((string) ($row['address_title'] ?? ''));

        return $name !== '' ? $name : ('Warehouse #' . $warehouseId);
    }

    public function index(): void
    {
        is_login();
        global $conn, $salesReturnModel;

        $pageNo = isset($_GET['page_no']) ? (int) $_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;

        $filters = [
            'search_text' => trim((string) ($_GET['search_text'] ?? '')),
            'return_date_from' => trim((string) ($_GET['return_date_from'] ?? '')),
            'return_date_to' => trim((string) ($_GET['return_date_to'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'warehouse_id' => 0,
        ];

        if (!$this->isAdminUser()) {
            $filters['warehouse_id'] = $this->getSessionWarehouseId();
        } elseif (isset($_GET['warehouse_id']) && (int) $_GET['warehouse_id'] > 0) {
            $filters['warehouse_id'] = (int) $_GET['warehouse_id'];
        }

        $result = $salesReturnModel->searchReturns($filters, $pageNo, $limit);
        $totalPages = $limit > 0 ? (int) ceil($result['total'] / $limit) : 1;

        $usersModel = new User($conn);
        $warehouses = $this->isAdminUser() ? $usersModel->getAllWarehouses() : [];

        renderTemplate('views/sales_return/index.php', [
            'returns' => $result['rows'],
            'total_records' => $result['total'],
            'page_no' => $pageNo,
            'total_pages' => max(1, $totalPages),
            'limit' => $limit,
            'filters' => $filters,
            'warehouses' => $warehouses,
            'is_admin' => $this->isAdminUser(),
            'can_cancel' => canSrEmpAccess(),
        ], 'Sales returns');
    }

    public function create(): void
    {
        is_login();
        global $conn, $salesReturnModel;

        $orderNumber = trim((string) ($_GET['order_number'] ?? ''));
        $invoiceId = isset($_GET['invoice_id']) ? (int) $_GET['invoice_id'] : 0;

        if ($orderNumber === '' && $invoiceId > 0) {
            $invoiceModel = new POSInvoice($conn);
            $invoice = $invoiceModel->getInvoiceById($invoiceId);
            if ($invoice) {
                $items = $invoiceModel->getInvoiceItems($invoiceId);
                $orderNumber = trim((string) ($items[0]['order_number'] ?? ''));
            }
        }

        if ($orderNumber === '') {
            $_SESSION['sales_return_flash'] = ['type' => 'error', 'text' => 'Order number is required.'];
            header('Location: ?page=sales_returns&action=index');
            exit;
        }

        $context = $salesReturnModel->getReturnContext(
            $orderNumber,
            $invoiceId > 0 ? $invoiceId : null
        );

        if ($context['lines'] === []) {
            $_SESSION['sales_return_flash'] = [
                'type' => 'error',
                'text' => 'No returnable lines found for order ' . $orderNumber . '.',
            ];
            header('Location: ?page=sales_returns&action=index');
            exit;
        }

        $warehouseId = (int) ($context['warehouse_id'] ?? 0);
        if (!$this->isAdminUser() && $this->getSessionWarehouseId() > 0 && $warehouseId > 0
            && $warehouseId !== $this->getSessionWarehouseId()) {
            $_SESSION['sales_return_flash'] = ['type' => 'error', 'text' => 'Order not found in your warehouse.'];
            header('Location: ?page=sales_returns&action=index');
            exit;
        }

        renderTemplate('views/sales_return/form.php', [
            'context' => $context,
            'return_types' => sales_return_type_options(),
            'warehouse_name' => $this->resolveWarehouseLabel($conn, $warehouseId),
        ], 'New sales return');
    }

    public function save(): void
    {
        is_login();
        global $conn, $salesReturnModel;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=sales_returns&action=index');
            exit;
        }

        $orderNumber = trim((string) ($_POST['order_number'] ?? ''));
        $invoiceId = isset($_POST['invoice_id']) ? (int) $_POST['invoice_id'] : 0;
        $returnDate = trim((string) ($_POST['return_date'] ?? ''));
        $returnType = sales_return_normalize_type($_POST['return_type'] ?? '');
        $remarks = trim((string) ($_POST['remarks'] ?? ''));

        if ($orderNumber === '' || $returnDate === '') {
            $_SESSION['sales_return_flash'] = ['type' => 'error', 'text' => 'Order number and return date are required.'];
            header('Location: ?page=sales_returns&action=create&order_number=' . rawurlencode($orderNumber));
            exit;
        }

        $orderRowIds = $_POST['order_row_id'] ?? [];
        $returnQtys = $_POST['return_qty'] ?? [];
        if (!is_array($orderRowIds) || !is_array($returnQtys)) {
            $_SESSION['sales_return_flash'] = ['type' => 'error', 'text' => 'Invalid return line data.'];
            header('Location: ?page=sales_returns&action=create&order_number=' . rawurlencode($orderNumber));
            exit;
        }

        $lines = [];
        foreach ($orderRowIds as $idx => $orderRowId) {
            $lines[] = [
                'order_row_id' => (int) $orderRowId,
                'return_qty' => (float) ($returnQtys[$idx] ?? 0),
            ];
        }

        $validation = $salesReturnModel->validateReturnLines(
            [
                'order_number' => $orderNumber,
                'invoice_id' => $invoiceId > 0 ? $invoiceId : null,
            ],
            $lines,
            $this->getSessionWarehouseId(),
            $this->isAdminUser()
        );

        if (!$validation['valid']) {
            $_SESSION['sales_return_flash'] = [
                'type' => 'error',
                'text' => implode(' ', $validation['errors']),
            ];
            header('Location: ?page=sales_returns&action=create&order_number=' . rawurlencode($orderNumber)
                . ($invoiceId > 0 ? '&invoice_id=' . $invoiceId : ''));
            exit;
        }

        $warehouseId = (int) ($validation['warehouse_id'] ?? 0);
        $resolvedInvoiceId = $validation['invoice_id'] ?? null;

        try {
            $returnId = $salesReturnModel->insertReturn([
                'order_number' => $orderNumber,
                'invoice_id' => $resolvedInvoiceId,
                'warehouse_id' => $warehouseId,
                'return_date' => $returnDate,
                'return_type' => $returnType,
                'remarks' => $remarks,
                'status' => 'finalized',
                'created_by' => (int) ($_SESSION['user']['id'] ?? 0),
            ], $validation['normalized_lines']);

            $stockModel = new Stock($conn);
            $stockResult = $stockModel->applySalesReturnStockIn($returnId, $warehouseId);
            $salesReturnModel->updateStockAppliedFlags($returnId, $stockResult);

            $returnedOrderRowIds = array_values(array_unique(array_filter(array_map(static function (array $line): int {
                return (int) ($line['order_row_id'] ?? 0);
            }, $validation['normalized_lines']), static function (int $id): bool {
                return $id > 0;
            })));
            $orderStatusMeta = $salesReturnModel->updateOrderReturnStatus(
                $orderNumber,
                $returnedOrderRowIds,
                (int) ($_SESSION['user']['id'] ?? 0)
            );

            $returnRow = $salesReturnModel->getById($returnId);
            $returnNumber = (string) ($returnRow['return_number'] ?? ('#' . $returnId));
            $applied = (int) ($stockResult['applied_lines'] ?? 0);
            $skipped = (int) ($stockResult['skipped_lines'] ?? 0);

            $msg = 'Sales return ' . $returnNumber . ' saved.';
            if ($applied > 0) {
                $msg .= ' Stock updated for ' . $applied . ' line(s).';
            }
            if ($skipped > 0) {
                $msg .= ' ' . $skipped . ' line(s) had no prior stock OUT (status updated only).';
            }
            $localUpdated = (int) ($orderStatusMeta['local_updated'] ?? 0);
            $apiCalled = (int) ($orderStatusMeta['api_called'] ?? 0);
            $apiFailed = (int) ($orderStatusMeta['api_failed'] ?? 0);
            $statusMessage = trim((string) ($orderStatusMeta['message'] ?? ''));

            if ($statusMessage !== '') {
                $msg .= ' ' . $statusMessage;
            } elseif ($localUpdated > 0) {
                $msg .= ' Order line(s) marked returned.';
            }

            $flashType = ($apiFailed > 0 && $apiCalled > 0) ? 'error' : 'success';
            if ($apiFailed > 0 && $localUpdated <= 0 && $apiCalled <= 0) {
                $flashType = 'error';
            }

            $_SESSION['sales_return_flash'] = ['type' => $flashType, 'text' => $msg];
            header('Location: ?page=sales_returns&action=view&id=' . $returnId);
            exit;
        } catch (Throwable $e) {
            error_log('[SalesReturn save] ' . $e->getMessage());
            $_SESSION['sales_return_flash'] = ['type' => 'error', 'text' => 'Failed to save return: ' . $e->getMessage()];
            header('Location: ?page=sales_returns&action=create&order_number=' . rawurlencode($orderNumber));
            exit;
        }
    }

    public function view(): void
    {
        is_login();
        global $conn, $salesReturnModel;

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?page=sales_returns&action=index');
            exit;
        }

        $returnRow = $salesReturnModel->getById($id);
        if (!$returnRow) {
            $_SESSION['sales_return_flash'] = ['type' => 'error', 'text' => 'Return not found.'];
            header('Location: ?page=sales_returns&action=index');
            exit;
        }

        if (!$this->isAdminUser()) {
            $sessionWh = $this->getSessionWarehouseId();
            if ($sessionWh > 0 && (int) ($returnRow['warehouse_id'] ?? 0) !== $sessionWh) {
                $_SESSION['sales_return_flash'] = ['type' => 'error', 'text' => 'Return not found in your warehouse.'];
                header('Location: ?page=sales_returns&action=index');
                exit;
            }
        }

        $items = $salesReturnModel->getItems($id);
        $returnTypes = sales_return_type_options();

        renderTemplate('views/sales_return/view.php', [
            'return_row' => $returnRow,
            'items' => $items,
            'return_type_label' => $returnTypes[$returnRow['return_type'] ?? ''] ?? ($returnRow['return_type'] ?? ''),
            'warehouse_name' => $this->resolveWarehouseLabel($conn, (int) ($returnRow['warehouse_id'] ?? 0)),
            'can_cancel' => canSrEmpAccess() && strtolower((string) ($returnRow['status'] ?? '')) === 'finalized',
        ], 'Sales return');
    }

    public function cancel(): void
    {
        is_login();

        if (!canSrEmpAccess()) {
            $this->emitJson(['success' => false, 'message' => 'Access denied.'], 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->emitJson(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        global $salesReturnModel;

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $id = (int) ($input['id'] ?? $input['return_id'] ?? 0);
        if ($id <= 0) {
            $this->emitJson(['success' => false, 'message' => 'Missing return id'], 400);
        }

        $returnRow = $salesReturnModel->getById($id);
        if (!$returnRow) {
            $this->emitJson(['success' => false, 'message' => 'Return not found'], 404);
        }

        if (!$this->isAdminUser()) {
            $sessionWh = $this->getSessionWarehouseId();
            if ($sessionWh > 0 && (int) ($returnRow['warehouse_id'] ?? 0) !== $sessionWh) {
                $this->emitJson(['success' => false, 'message' => 'Return not found in your warehouse.'], 403);
            }
        }

        $result = $salesReturnModel->cancelReturn($id);
        $status = !empty($result['success']) ? 200 : 500;
        $this->emitJson($result, $status);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function emitJson(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}
