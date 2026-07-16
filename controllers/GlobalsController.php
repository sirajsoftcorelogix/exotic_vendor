<?php

require_once 'models/globals/AppSettings.php';

$appSettingsModel = new AppSettings($conn);

class GlobalsController
{
    public function settings()
    {
        is_login();
        global $appSettingsModel;

        renderTemplate('views/globals/settings.php', [
            'settings' => $appSettingsModel->getAllSettings(),
            'audit_rows' => $appSettingsModel->getRecentAudit(15),
            'table_ready' => $appSettingsModel->tableExists('app_settings'),
        ], 'Global Settings');
    }

    public function update_settings()
    {
        is_login();
        global $appSettingsModel;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . base_url('?page=globals&action=settings'));
            exit;
        }

        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        if ($userId <= 0) {
            header('Location: ' . base_url('?page=globals&action=settings&status=error&message=' . urlencode('Invalid session.')));
            exit;
        }

        $submitted = $_POST['values'] ?? [];
        if (!is_array($submitted)) {
            $submitted = [];
        }

        $result = $appSettingsModel->updateSettings($submitted, $userId);

        if ($result['success']) {
            header('Location: ' . base_url('?page=globals&action=settings&status=success'));
            exit;
        }

        header('Location: ' . base_url('?page=globals&action=settings&status=error&message=' . urlencode($result['message'])));
        exit;
    }
}
