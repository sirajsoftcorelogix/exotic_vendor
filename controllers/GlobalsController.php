<?php

require_once 'models/globals/AppSettings.php';

$appSettingsModel = new AppSettings($conn);

class GlobalsController
{
    public function settings()
    {
        is_login();
        global $appSettingsModel;

        $groups = $appSettingsModel->getAllGrouped();
        $auditRows = $appSettingsModel->getRecentAudit(15);
        $tableReady = $appSettingsModel->tableExists('app_settings');

        renderTemplate('views/globals/settings.php', [
            'groups' => $groups,
            'audit_rows' => $auditRows,
            'table_ready' => $tableReady,
            'active_group' => isset($_GET['group']) ? trim((string) $_GET['group']) : '',
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

        $submittedActive = $_POST['active'] ?? [];
        if (!is_array($submittedActive)) {
            $submittedActive = [];
        }

        $result = $appSettingsModel->updateSettings($submitted, $submittedActive, $userId);
        $group = isset($_POST['group']) ? trim((string) $_POST['group']) : '';
        $groupQuery = $group !== '' ? '&group=' . urlencode($group) : '';

        if ($result['success']) {
            header('Location: ' . base_url('?page=globals&action=settings&status=success' . $groupQuery));
            exit;
        }

        header('Location: ' . base_url('?page=globals&action=settings&status=error&message=' . urlencode($result['message']) . $groupQuery));
        exit;
    }
}
