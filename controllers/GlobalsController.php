<?php 
require_once 'models/comman/tables.php';
$commanModel = new Tables($conn);
class GlobalsController {
    private function ensureHighValueSettingColumn(): void {
        global $conn;
        if (!($conn instanceof mysqli)) {
            return;
        }
        $stmt = $conn->prepare(
            'SELECT 1
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1'
        );
        if ($stmt) {
            $table = 'global_settings';
            $column = 'high_value_transaction_limit';
            $stmt->bind_param('ss', $table, $column);
            $stmt->execute();
            $exists = (bool)$stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$exists) {
                @$conn->query("ALTER TABLE global_settings ADD COLUMN high_value_transaction_limit DECIMAL(15,2) NOT NULL DEFAULT 200000.00 AFTER terms_and_conditions");
            }
        }
        @$conn->query("UPDATE global_settings SET high_value_transaction_limit = 200000.00 WHERE id = 1 AND (high_value_transaction_limit IS NULL OR high_value_transaction_limit <= 0)");
    }

    public function settings() {
        // update invoice_prefix and all other global_settings table settings
        global $commanModel;
        $this->ensureHighValueSettingColumn();
        $allSettings = $commanModel->getAllGlobalSettings();       
        renderTemplate('views/globals/settings.php', ['data' => $allSettings]);
    }
    public function update_settings() {
        global $commanModel;
        $this->ensureHighValueSettingColumn();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settings = $_POST;
            $data = [];
            foreach ($settings as $key => $value) {
                $data[$key] = trim($value);                
            }
            $commanModel->updateGlobalSettings($data, 1); // Assuming ID 1 is the global settings record
            header('Location: ' . base_url('?page=globals&action=settings&status=success'));
            exit();
        }

    }
}