<?php 
require_once 'models/comman/tables.php';
$commanModel = new Tables($conn);
class GlobalsController {
    public function settings() {
        // update invoice_prefix and all other global_settings table settings
        global $commanModel;
        $allSettings = $commanModel->getAllGlobalSettings();       
        renderTemplate('views/globals/settings.php', ['data' => $allSettings]);
    }
    public function update_settings() {
        global $commanModel;
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