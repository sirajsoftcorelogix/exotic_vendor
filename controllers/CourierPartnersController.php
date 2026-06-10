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

    public function syncShippers()
    {
        is_login();
        global $courierPartnerModel;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=courier_partners&action=list');
            exit;
        }

        $ch = curl_init('https://www.exoticindia.com/api/order/shipper-fetch');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
                'x-adminapitest: 1',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);

        $data = is_string($raw) ? json_decode($raw, true) : null;
        $list = is_array($data['shippers'] ?? null) ? $data['shippers'] : (is_array($data) && array_is_list($data) ? $data : []);
        if (!$list) {
            $_SESSION['courier_partner_flash'] = [
                'success' => false,
                'message' => (string) ($data['error'] ?? $data['message'] ?? 'Could not fetch shippers.'),
            ];
            header('Location: ?page=courier_partners&action=list');
            exit;
        }

        $_SESSION['courier_partner_flash'] = $courierPartnerModel->syncShippers($list);
        header('Location: ?page=courier_partners&action=list');
        exit;
    }
}

