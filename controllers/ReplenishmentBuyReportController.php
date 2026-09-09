<?php

require_once 'models/replenishment/ReplenishmentBuyReport.php';
require_once 'helpers/DailyBookReplenishment.php';

class ReplenishmentBuyReportController
{
    private ReplenishmentBuyReport $reportModel;

    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->reportModel = new ReplenishmentBuyReport($conn);
    }

    public function index(): void
    {
        is_login();

        $filters = [
            'search' => trim((string) ($_GET['search_text'] ?? '')),
            'purchased' => trim((string) ($_GET['purchased'] ?? 'no')),
            'run_date' => trim((string) ($_GET['run_date'] ?? '')),
            'page' => max(1, (int) ($_GET['page_no'] ?? 1)),
            'limit' => (int) ($_GET['limit'] ?? 20),
        ];
        if (!in_array($filters['purchased'], ['no', 'yes', 'all'], true)) {
            $filters['purchased'] = 'no';
        }

        $listing = $this->reportModel->tableExists()
            ? $this->reportModel->searchList($filters)
            : ['rows' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'limit' => 20];

        renderTemplate('views/replenishment_buy_report/index.php', [
            'rows' => $listing['rows'],
            'search' => $filters['search'],
            'purchased' => $filters['purchased'],
            'run_date' => $filters['run_date'],
            'currentPage' => $listing['page'],
            'totalPages' => $listing['pages'],
            'totalRecords' => $listing['total'],
            'limit' => $listing['limit'],
            'table_ready' => $this->reportModel->tableExists(),
            'can_run' => function_exists('canSrEmpAccess') && canSrEmpAccess(),
        ], 'Replenishment Buy Report');
    }

    public function markPurchased(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $id = (int) ($payload['id'] ?? 0);
        $purchased = !empty($payload['purchased']);
        $userId = (int) ($_SESSION['user']['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid row.']);
            exit;
        }

        $ok = $this->reportModel->setPurchased($id, $purchased, $userId);
        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Updated.' : 'Could not update purchased status.',
        ]);
        exit;
    }

    public function runYesterday(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (!function_exists('canSrEmpAccess') || !canSrEmpAccess()) {
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }

        $salesDate = date('Y-m-d', strtotime('-1 day'));
        $job = new DailyBookReplenishment($this->conn);
        $summary = $job->runForDate($salesDate, false);

        echo json_encode([
            'success' => true,
            'message' => 'Replenishment job finished for ' . $salesDate . '.',
            'summary' => $summary,
        ]);
        exit;
    }
}
