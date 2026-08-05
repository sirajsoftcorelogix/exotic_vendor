<?php

require_once __DIR__ . '/../models/currency/CurrencyModel.php';
require_once __DIR__ . '/../helpers/IcegatePdfParser.php';

class CurrencyController {
    private $db;
    private $model;
    
    public function __construct($database) {
        $this->db = $database;
        $this->model = new CurrencyModel($database);
    }
    
    // Display currency list
    public function index() {
        $currencies = $this->model->getAllCurrencies();
        renderTemplate('views/currency/list.php', ['currencies' => $currencies]);
    }
    
    // Handle add/edit currency form
    public function addCurrencyRecord() {
        $isEdit = isset($_GET['id']) && !empty($_GET['id']);
        $currency = null;
        $errors = [];
        $successMessage = '';
        
        if ($isEdit) {
            $currency = $this->model->getCurrencyById($_GET['id']);
            if (!$currency) {
                die('Currency not found');
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'currency_code'  => trim($_POST['currency_code'] ?? ''),
                'currency_name'  => trim($_POST['currency_name'] ?? ''),
                'currency_unit'  => trim($_POST['currency_unit'] ?? ''),
                'display_symbol' => trim($_POST['display_symbol'] ?? ''),
                'rate_import'    => floatval($_POST['rate_import'] ?? 0),
                'rate_export'    => floatval($_POST['rate_export'] ?? 0)
            ];
            
            $errors = $this->validate($data, $isEdit);
            
            if (empty($errors)) {
                if ($isEdit) {
                    $result = $this->model->updateCurrency($_GET['id'], $data);
                } else {
                    $result = $this->model->addCurrency($data);
                }
                
                if ($result['success']) {
                    $successMessage = $result['message'];
                    if (!$isEdit) {
                        header('Location: index.php?page=currency&action=list&success=1');
                        exit;
                    } else {
                        $currency = $this->model->getCurrencyById($_GET['id']);
                    }
                }
            }
        }
        
        renderTemplate('views/currency/form.php', [
            'isEdit' => $isEdit,
            'currency' => $currency,
            'errors' => $errors,
            'successMessage' => $successMessage
        ]);
    }
    
    // Delete/Deactivate currency
    public function delete() {
        if (!isset($_GET['id'])) {
            header('Location: index.php?page=currency&action=list');
            exit;
        }
        
        $result = $this->model->deactivateCurrency($_GET['id']);
        
        if ($result['success']) {
            header('Location: index.php?page=currency&action=list&success=1');
        } else {
            header('Location: index.php?page=currency&action=list&error=1');
        }
        exit;
    }
    
    // Get currency details (AJAX)
    public function getCurrencyDetails() {
        header('Content-Type: application/json');
        
        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'ID not provided']);
            exit;
        }
        
        $currency = $this->model->getCurrencyById($_GET['id']);
        echo json_encode($currency ?: ['error' => 'Currency not found']);
        exit;
    }

    /**
     * Preview exchange rates from uploaded PDF
     */
    public function uploadPdfPreview() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $filePath = null;

        // Check file upload or server file path parameter
        if (isset($_FILES['exchange_rate_pdf']) && $_FILES['exchange_rate_pdf']['error'] === UPLOAD_ERR_OK) {
            $filePath = $_FILES['exchange_rate_pdf']['tmp_name'];
        } elseif (!empty($_POST['file_path'])) {
            $filePath = trim($_POST['file_path']);
        }

        if (!$filePath) {
            echo json_encode(['success' => false, 'message' => 'No PDF file provided or uploaded.']);
            exit;
        }

        $parseResult = IcegatePdfParser::parsePdf($filePath);

        if (!$parseResult['success']) {
            echo json_encode($parseResult);
            exit;
        }

        // Compare parsed rates with existing database records
        $parseResult['rates'] = $this->enrichWithCurrentRates($parseResult['rates']);

        echo json_encode($parseResult);
        exit;
    }

    /**
     * Apply confirmed bulk rates to database
     */
    public function applyBulkRates() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $payload = json_decode($rawInput, true);

        if (!$payload && !empty($_POST['rates'])) {
            $payload = [
                'rates'           => is_string($_POST['rates']) ? json_decode($_POST['rates'], true) : $_POST['rates'],
                'effective_date'  => $_POST['effective_date'] ?? date('Y-m-d'),
                'notification_no' => $_POST['notification_no'] ?? 'CBIC/ICEGATE',
                'source'          => $_POST['source'] ?? 'PDF'
            ];
        }

        $rates = $payload['rates'] ?? [];
        $effectiveDate = $payload['effective_date'] ?? date('Y-m-d');
        $notificationNo = $payload['notification_no'] ?? 'CBIC/ICEGATE';
        $source = $payload['source'] ?? 'PDF';

        if (empty($rates) || !is_array($rates)) {
            echo json_encode(['success' => false, 'message' => 'No rates provided to apply.']);
            exit;
        }

        $result = $this->model->bulkUpdateExchangeRates($rates, $source, $effectiveDate, $notificationNo);
        echo json_encode($result);
        exit;
    }

    /**
     * Helper to add current rates from database for side-by-side UI comparison
     */
    private function enrichWithCurrentRates(array $rates): array
    {
        $enriched = [];
        foreach ($rates as $row) {
            $code = $row['currency_code'];
            $existing = $this->model->getCurrencyByCode($code);

            $row['current_import_rate'] = $existing ? floatval($existing['rate_import']) : 0.0;
            $row['current_export_rate'] = $existing ? floatval($existing['rate_export']) : 0.0;
            $row['exists_in_db']        = $existing ? true : false;
            $row['is_active_in_db']     = $existing ? (int)$existing['is_active'] === 1 : false;

            // Flag whether rate changed
            $row['import_changed'] = abs($row['current_import_rate'] - $row['rate_import']) > 0.000001;
            $row['export_changed'] = abs($row['current_export_rate'] - $row['rate_export']) > 0.000001;

            $enriched[] = $row;
        }
        return $enriched;
    }

    // --- Backward Compatibility Delegate Methods ---
    public function getAllCurrencies() { return $this->model->getAllCurrencies(); }
    public function getCurrencyById($id) { return $this->model->getCurrencyById($id); }
    public function getCurrencyByCode($code) { return $this->model->getCurrencyByCode($code); }
    public function addCurrency($data) { return $this->model->addCurrency($data); }
    public function updateCurrency($id, $data) { return $this->model->updateCurrency($id, $data); }
    public function deactivateCurrency($id) { return $this->model->deactivateCurrency($id); }
    public function getRateHistory($code, $limit = 30) { return $this->model->getRateHistory($code, $limit); }

    // Validate currency data
    public function validate($data, $isEdit = false) {
        $errors = [];
        
        if (empty($data['currency_code']) || strlen($data['currency_code']) !== 3) {
            $errors[] = 'Currency code must be 3 characters';
        }
        
        if (empty($data['currency_name'])) {
            $errors[] = 'Currency name is required';
        }
        
        if (empty($data['currency_unit'])) {
            $errors[] = 'Currency unit is required';
        }
        
        if (!is_numeric($data['rate_import']) || $data['rate_import'] < 0) {
            $errors[] = 'Invalid import rate';
        }
        
        if (!is_numeric($data['rate_export']) || $data['rate_export'] < 0) {
            $errors[] = 'Invalid export rate';
        }
        
        if (!$isEdit) {
            $existing = $this->model->getCurrencyByCode($data['currency_code']);
            if ($existing && (int)$existing['is_active'] === 1) {
                $errors[] = 'Currency code already exists';
            }
        }
        
        return $errors;
    }
}
