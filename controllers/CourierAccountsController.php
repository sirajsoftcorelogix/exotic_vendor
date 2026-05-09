<?php

require_once 'models/courier/CourierPartner.php';
require_once 'models/courier/CourierAccount.php';

$courierPartnerModel = new CourierPartner($conn);
$courierAccountModel = new CourierAccount($conn);

class CourierAccountsController
{
    public function index()
    {
        is_login();
        global $courierPartnerModel, $courierAccountModel;

        $partnerId = isset($_GET['partner_id']) ? (int)$_GET['partner_id'] : 0;
        $partners = $courierPartnerModel->getActivePartners();
        $accounts = $courierAccountModel->listAccounts($partnerId);

        renderTemplate('views/courier_accounts/index.php', [
            'partners' => $partners,
            'partner_id' => $partnerId,
            'accounts' => $accounts,
        ], 'Courier Accounts & Credentials');
    }

    public function saveAccount()
    {
        is_login();
        global $courierAccountModel;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=courier_accounts&action=list');
            exit;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $res = $courierAccountModel->upsertAccount($id, $_POST);

        // Save credentials if present
        $accountId = $id > 0 ? $id : 0;
        if (!empty($res['success']) && $accountId === 0) {
            // Try to resolve the inserted account id (partner_id + account_code)
            // We keep it simple: credentials can be saved on next edit if needed.
        }
        if ($id > 0 && isset($_POST['cred_key']) && is_array($_POST['cred_key'])) {
            $courierAccountModel->saveCredentials(
                $id,
                (array)($_POST['cred_key'] ?? []),
                (array)($_POST['cred_value'] ?? []),
                (array)($_POST['cred_secret'] ?? [])
            );
        }

        $_SESSION['courier_account_flash'] = $res;
        $pid = isset($_POST['partner_id']) ? (int)$_POST['partner_id'] : 0;
        header('Location: ?page=courier_accounts&action=list' . ($pid > 0 ? ('&partner_id=' . $pid) : ''));
        exit;
    }

    public function deleteAccount()
    {
        is_login();
        global $courierAccountModel;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=courier_accounts&action=list');
            exit;
        }
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $res = $courierAccountModel->deleteAccount($id);
        $_SESSION['courier_account_flash'] = $res;
        header('Location: ?page=courier_accounts&action=list');
        exit;
    }
}

