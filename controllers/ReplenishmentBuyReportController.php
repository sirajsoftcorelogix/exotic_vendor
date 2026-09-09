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

        $filters = $this->filtersFromRequest();

        $listing = $this->reportModel->tableExists()
            ? $this->reportModel->searchList($filters)
            : ['rows' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'limit' => 20];

        $rows = [];
        foreach ($listing['rows'] as $row) {
            $row['run_date_display'] = $this->formatSalesDate((string) ($row['run_date'] ?? ''));
            $rows[] = $row;
        }

        renderTemplate('views/replenishment_buy_report/index.php', [
            'rows' => $rows,
            'search' => $filters['search'],
            'sku' => $filters['sku'],
            'item_code' => $filters['item_code'],
            'purchased' => $filters['purchased'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'publisher' => $filters['publisher'],
            'publisher_id' => $filters['publisher_id'],
            'vendor' => $filters['vendor'],
            'vendor_id' => $filters['vendor_id'],
            'currentPage' => $listing['page'],
            'totalPages' => $listing['pages'],
            'totalRecords' => $listing['total'],
            'limit' => $listing['limit'],
            'table_ready' => $this->reportModel->tableExists(),
            'can_run' => function_exists('canSrEmpAccess') && canSrEmpAccess(),
            'export_query' => http_build_query($this->exportQueryParams($filters)),
        ], 'Replenishment Buy Report');
    }

    public function exportExcel(): void
    {
        is_login();

        if (!$this->reportModel->tableExists()) {
            vendorJsonResponse(['success' => false, 'message' => 'Report table is not ready.']);
        }

        $filters = $this->filtersFromRequest();
        $rows = $this->reportModel->searchAll($filters, 10000);

        require_once 'vendor/autoload.php';

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Buy report');

            $headers = [
                'Sales date',
                'SKU',
                'Item code',
                'Title',
                "Y'day sold",
                'Sold',
                'Lookback',
                'Lookback source',
                'Period source',
                'Available Stock',
                'Available stock calculation',
                'Threshold %',
                'Purchase threshold qty',
                'Min stock %',
                'Recommended buy',
                'Purchased',
                'Purchased at',
            ];
            $sheet->fromArray($headers, null, 'A1');
            $sheet->getStyle('A1:Q1')->getFont()->setBold(true);
            $sheet->getStyle('A1:Q1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF3F4F6');

            $rowNum = 2;
            foreach ($rows as $row) {
                $sheet->fromArray([
                    $this->formatSalesDate((string) ($row['run_date'] ?? '')),
                    (string) ($row['sku'] ?? ''),
                    (string) ($row['item_code'] ?? ''),
                    (string) ($row['title'] ?? ''),
                    (int) ($row['yesterday_sold_qty'] ?? 0),
                    (int) ($row['numsold_replenishment'] ?? 0),
                    (int) ($row['lookback_months'] ?? 0),
                    (string) ($row['lookback_source'] ?? ''),
                    (string) ($row['numsold_source'] ?? ''),
                    (int) ($row['available_stock'] ?? 0),
                    'Available stock = Physical stock ' . (int) ($row['physical_stock'] ?? 0)
                        . ' + Pending PO ' . (int) ($row['pending_po_qty'] ?? 0),
                    (int) ($row['purchase_threshold_percent'] ?? 0),
                    (int) ($row['purchase_threshold_qty'] ?? 0),
                    (int) ($row['min_stock_percent'] ?? 0),
                    (int) ($row['replenishment_buy_qty'] ?? 0),
                    ((int) ($row['purchased'] ?? 0) === 1) ? 'Yes' : 'No',
                    (string) ($row['purchased_at'] ?? ''),
                ], null, 'A' . $rowNum);
                $rowNum++;
            }

            foreach (range('A', 'Q') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = 'replenishment_buy_report_' . date('Y-m-d_His') . '.xlsx';

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        } catch (Throwable $e) {
            vendorJsonResponse([
                'success' => false,
                'message' => 'Could not generate Excel file.',
            ], 500);
        }
        exit;
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

        if (!function_exists('canSrEmpAccess') || !canSrEmpAccess()) {
            vendorJsonResponse(['success' => false, 'message' => 'Access denied.']);
        }

        @set_time_limit(300);
        ignore_user_abort(true);

        $salesDate = date('Y-m-d', strtotime('-1 day'));

        try {
            $job = new DailyBookReplenishment($this->conn);
            $summary = $job->runForDate($salesDate, false);
        } catch (Throwable $e) {
            vendorJsonResponse([
                'success' => false,
                'message' => 'Replenishment job failed: ' . $e->getMessage(),
            ], 500);
        }

        $scanned = (int) ($summary['scanned'] ?? 0);
        $books = (int) ($summary['books'] ?? 0);
        $triggered = (int) ($summary['triggered'] ?? 0);
        $written = (int) ($summary['written'] ?? 0);

        if ($scanned === 0) {
            $message = 'No book sales found for ' . $salesDate . '. Import yesterday’s orders first, then run again.';
        } elseif ($triggered === 0) {
            $message = 'Checked ' . $books . ' book SKU(s) sold on ' . $salesDate
                . '. None are below the purchase threshold, so nothing was added to the buy list.';
        } else {
            $message = 'Added ' . $written . ' book SKU(s) to the buy report for ' . $salesDate . '.';
        }

        vendorJsonResponse([
            'success' => true,
            'message' => $message,
            'summary' => $summary,
        ]);
    }

    private function formatSalesDate(string $date): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }

        $day = (int) date('j', $timestamp);
        $mod100 = $day % 100;
        if ($mod100 >= 11 && $mod100 <= 13) {
            $suffix = 'th';
        } else {
            $suffix = match ($day % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };
        }

        return $day . $suffix . ' ' . date('M, y', $timestamp);
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersFromRequest(): array
    {
        $runDate = trim((string) ($_GET['run_date'] ?? ''));
        $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
        $dateTo = trim((string) ($_GET['date_to'] ?? ''));
        if ($dateFrom === '' && $dateTo === '' && $runDate !== '') {
            $dateFrom = $runDate;
            $dateTo = $runDate;
        }

        $purchased = trim((string) ($_GET['purchased'] ?? 'no'));
        if (!in_array($purchased, ['no', 'yes', 'all'], true)) {
            $purchased = 'no';
        }

        return [
            'search' => trim((string) ($_GET['search_text'] ?? '')),
            'sku' => trim((string) ($_GET['sku'] ?? '')),
            'item_code' => trim((string) ($_GET['item_code'] ?? '')),
            'purchased' => $purchased,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'publisher' => trim((string) ($_GET['publisher'] ?? '')),
            'publisher_id' => (int) ($_GET['publisher_id'] ?? 0),
            'vendor' => trim((string) ($_GET['vendor'] ?? '')),
            'vendor_id' => (int) ($_GET['vendor_id'] ?? 0),
            'page' => max(1, (int) ($_GET['page_no'] ?? 1)),
            'limit' => (int) ($_GET['limit'] ?? 20),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, string|int>
     */
    private function exportQueryParams(array $filters): array
    {
        return [
            'page' => 'replenishment_buy_report',
            'action' => 'export_excel',
            'search_text' => (string) ($filters['search'] ?? ''),
            'sku' => (string) ($filters['sku'] ?? ''),
            'item_code' => (string) ($filters['item_code'] ?? ''),
            'purchased' => (string) ($filters['purchased'] ?? 'no'),
            'date_from' => (string) ($filters['date_from'] ?? ''),
            'date_to' => (string) ($filters['date_to'] ?? ''),
            'publisher' => (string) ($filters['publisher'] ?? ''),
            'publisher_id' => (int) ($filters['publisher_id'] ?? 0),
            'vendor' => (string) ($filters['vendor'] ?? ''),
            'vendor_id' => (int) ($filters['vendor_id'] ?? 0),
        ];
    }
}
