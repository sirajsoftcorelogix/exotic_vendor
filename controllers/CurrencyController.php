<?php

class CurrencyController {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Display currency list
    public function index() {
        $currencies = $this->getAllCurrencies();
        //require_once 'views/currency/list.php';
        renderTemplate('views/currency/list.php', ['currencies' => $currencies]);
    }
    
    // Handle add/edit currency form
    public function addCurrencyRecord() {
        $isEdit = isset($_GET['id']) && !empty($_GET['id']);
        $currency = null;
        $errors = [];
        $successMessage = '';
        
        if ($isEdit) {
            $currency = $this->getCurrencyById($_GET['id']);
            if (!$currency) {
                die('Currency not found');
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'currency_code' => trim($_POST['currency_code'] ?? ''),
                'currency_name' => trim($_POST['currency_name'] ?? ''),
                'currency_unit' => trim($_POST['currency_unit'] ?? ''),
                'rate_import' => floatval($_POST['rate_import'] ?? 0),
                'rate_export' => floatval($_POST['rate_export'] ?? 0)
            ];
            
            $errors = $this->validate($data, $isEdit);
            
            if (empty($errors)) {
                if ($isEdit) {
                    $result = $this->updateCurrency($_GET['id'], $data);
                } else {
                    $result = $this->addCurrency($data);
                }
                
                if ($result['success']) {
                    $successMessage = $result['message'];
                    if (!$isEdit) {
                        header('Location: index.php?page=currency&action=list&success=1');
                        exit;
                    } else {
                        $currency = $this->getCurrencyById($_GET['id']);
                    }
                }
            }
        }
        
        //require_once 'views/currency/form.php';
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
        
        $result = $this->deactivateCurrency($_GET['id']);
        
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
        
        $currency = $this->getCurrencyById($_GET['id']);
        echo json_encode($currency ?: ['error' => 'Currency not found']);
        exit;
    }
    
    // Get all currencies
    public function getAllCurrencies() {
        $query = "SELECT * FROM currency_master WHERE is_active = 1 ORDER BY currency_code";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    // Get currency by ID
    public function getCurrencyById($id) {
        $query = "SELECT * FROM currency_master WHERE id = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }
    
    // Add new currency
    public function addCurrency($data) {
        $code = strtoupper($data['currency_code']);
        
        // Check if currency code exists (inactive)
        $existingCurrency = $this->getCurrencyByCode($code);
        
        if ($existingCurrency) {
            // Reactivate existing currency instead of creating new one
            return $this->reactivateCurrency($existingCurrency['id'], $data);
        }
        
        $query = "INSERT INTO currency_master 
                  (currency_code, currency_name, currency_unit, rate_import, rate_export, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) return ['success' => false, 'message' => 'Prepare failed'];
        
        $name = $data['currency_name'];
        $unit = $data['currency_unit'];
        $rate_import = floatval($data['rate_import'] ?? 0);
        $rate_export = floatval($data['rate_export'] ?? 0);
        $is_active = 1;
        
        $stmt->bind_param('sssddi', $code, $name, $unit, $rate_import, $rate_export, $is_active);
        $result = $stmt->execute();
        
        if ($result) {
            $currencyId = $this->db->insert_id;
            $this->addRateHistory($code, $rate_import, $rate_export);
            return ['success' => true, 'id' => $currencyId, 'message' => 'Currency added successfully'];
        }
        return ['success' => false, 'message' => 'Failed to add currency'];
    }
    
    // Get currency by code (including inactive)
    public function getCurrencyByCode($code) {
        $query = "SELECT * FROM currency_master WHERE currency_code = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) return null;
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }
    
    // Reactivate a deactivated currency
    public function reactivateCurrency($id, $data) {
        $query = "UPDATE currency_master SET 
                  currency_name = ?, 
                  currency_unit = ?, 
                  rate_import = ?, 
                  rate_export = ?,
                  is_active = 1
                  WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) return ['success' => false, 'message' => 'Prepare failed'];
        
        $name = $data['currency_name'];
        $unit = $data['currency_unit'];
        $rate_import = floatval($data['rate_import']);
        $rate_export = floatval($data['rate_export']);
        
        $stmt->bind_param('ssddi', $name, $unit, $rate_import, $rate_export, $id);
        $result = $stmt->execute();
        
        if ($result) {
            $this->addRateHistory(strtoupper($data['currency_code']), $rate_import, $rate_export);
            return ['success' => true, 'id' => $id, 'message' => 'Currency reactivated successfully'];
        }
        return ['success' => false, 'message' => 'Failed to reactivate currency'];
    }
    
    // Update currency
    public function updateCurrency($id, $data) {
        $oldCurrency = $this->getCurrencyById($id);
        
        $query = "UPDATE currency_master SET 
                  currency_name = ?, 
                  currency_unit = ?, 
                  rate_import = ?, 
                  rate_export = ? 
                  WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) return ['success' => false, 'message' => 'Prepare failed'];
        
        $name = $data['currency_name'];
        $unit = $data['currency_unit'];
        $rate_import = floatval($data['rate_import']);
        $rate_export = floatval($data['rate_export']);
        
        $stmt->bind_param('ssddi', $name, $unit, $rate_import, $rate_export, $id);
        $result = $stmt->execute();
        
        if ($result) {
            if ($oldCurrency && ($oldCurrency['rate_import'] != $rate_import || $oldCurrency['rate_export'] != $rate_export)) {
                $this->addRateHistory($oldCurrency['currency_code'], $rate_import, $rate_export);
            }
            return ['success' => true, 'message' => 'Currency updated successfully'];
        }
        return ['success' => false, 'message' => 'Failed to update currency'];
    }
    
    // Add rate history record
    private function addRateHistory($currencyCode, $rateImport, $rateExport) {
        $query = "INSERT INTO currency_rate_history 
                  (currency_code, rate_import, rate_export, rate_date) 
                  VALUES (?, ?, ?, CURDATE())";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) return false;
        $stmt->bind_param('sdd', $currencyCode, $rateImport, $rateExport);
        return $stmt->execute();
    }
    
    // Get rate history for a currency
    public function getRateHistory($currencyCode, $limit = 30) {
        $query = "SELECT * FROM currency_rate_history 
                  WHERE currency_code = ? 
                  ORDER BY rate_date DESC 
                  LIMIT ?";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) return [];
        $stmt->bind_param('si', $currencyCode, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    // Deactivate currency
    public function deactivateCurrency($id) {
        $query = "UPDATE currency_master SET is_active = 0 WHERE id = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) return ['success' => false];
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        
        return $result ? ['success' => true, 'message' => 'Currency deactivated'] : ['success' => false];
    }
    
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
            $query = "SELECT id FROM currency_master WHERE currency_code = ? AND is_active = 1";
            $stmt = $this->db->prepare($query);
            if ($stmt) {
                $code = strtoupper($data['currency_code']);
                $stmt->bind_param('s', $code);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->fetch_assoc()) {
                    $errors[] = 'Currency code already exists';
                }
            }
        }
        
        return $errors;
    }
}
?>
