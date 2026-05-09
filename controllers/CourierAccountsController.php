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
        foreach ($accounts as &$accRow) {
            $accRow['credentials'] = $courierAccountModel->getCredentials((int) ($accRow['id'] ?? 0));
        }
        unset($accRow);

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

        global $conn;
        $effectiveId = $id;
        if (!empty($res['success']) && $id === 0) {
            $effectiveId = (int) $conn->insert_id;
        }

        if (!empty($res['success']) && $effectiveId > 0 && isset($_POST['cred_key']) && is_array($_POST['cred_key'])) {
            $credRes = $courierAccountModel->saveCredentials(
                $effectiveId,
                (array) ($_POST['cred_key'] ?? []),
                (array) ($_POST['cred_value'] ?? []),
                (array) ($_POST['cred_secret'] ?? [])
            );
            if (empty($credRes['success'])) {
                $res['message'] = trim((string) ($res['message'] ?? '') . ' — Credentials: ' . ($credRes['message'] ?? ''));
            }
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

