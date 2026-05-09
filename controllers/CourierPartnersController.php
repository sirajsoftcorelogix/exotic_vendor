<?php
require_once 'models/courier/CourierPartner.php';

$courierPartnerModel = new CourierPartner($conn);

class CourierPartnersController
{
    public function index()
    {
        is_login();
        global $courierPartnerModel;

        $search = isset($_GET['search_text']) ? trim((string)$_GET['search_text']) : '';
        $status = isset($_GET['status_filter']) ? trim((string)$_GET['status_filter']) : '';
        $pageNo = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $limit = in_array($limit, [5, 20, 50, 100], true) ? $limit : 20;

        $res = $courierPartnerModel->getAll($pageNo, $limit, $search, $status);

        renderTemplate('views/courier_partners/index.php', [
            'rows' => $res['rows'],
            'currentPage' => $res['currentPage'],
            'totalPages' => $res['totalPages'],
            'totalRecords' => $res['totalRecords'],
            'limit' => $res['limit'],
            'search' => $search,
            'status_filter' => $status,
        ], 'Courier Partner Master');
    }

    public function addRecord()
    {
        is_login();
        global $courierPartnerModel;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=courier_partners&action=list');
            exit;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $result = $id > 0
            ? $courierPartnerModel->updateRecord($id, $_POST)
            : $courierPartnerModel->addRecord($_POST);

        $_SESSION['courier_partner_flash'] = $result;
        header('Location: ?page=courier_partners&action=list');
        exit;
    }

    public function deleteRecord()
    {
        is_login();
        global $courierPartnerModel;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=courier_partners&action=list');
            exit;
        }
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $result = $courierPartnerModel->deleteRecord($id);
        $_SESSION['courier_partner_flash'] = $result;
        header('Location: ?page=courier_partners&action=list');
        exit;
    }
}

