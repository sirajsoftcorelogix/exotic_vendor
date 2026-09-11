<?php

require_once 'models/port/PortMaster.php';
require_once 'models/country/country.php';

class PortsController
{
    private PortMaster $portModel;
    private Country $countryModel;

    public function __construct(mysqli $conn)
    {
        $this->portModel = new PortMaster($conn);
        $this->countryModel = new Country($conn);
    }

    public function index(): void
    {
        is_login();

        $search = trim((string) ($_GET['search_text'] ?? ''));
        $status = trim((string) ($_GET['status_filter'] ?? ''));
        $portTypeFilter = PortMaster::normalizePortType(trim((string) ($_GET['port_type_filter'] ?? '')));
        $pageNo = max(1, (int) ($_GET['page_no'] ?? 1));
        $limit = (int) ($_GET['limit'] ?? 20);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;

        $listing = $this->portModel->getPorts($pageNo, $limit, $search, $status, $portTypeFilter);
        $countryList = $this->countryModel->getAllCountries();

        renderTemplate('views/ports/index.php', [
            'ports' => $listing['ports'],
            'search' => $search,
            'status_filter' => $status,
            'port_type_filter' => $portTypeFilter,
            'port_types' => PortMaster::portTypeLabels(),
            'countryList' => $countryList['countries'] ?? [],
            'default_country_id' => $this->portModel->getDefaultCountryId(),
            'currentPage' => $listing['currentPage'],
            'totalPages' => $listing['totalPages'],
            'totalRecords' => $listing['totalRecords'],
            'limit' => $listing['limit'],
        ], 'Shipping PORTS');
    }

    public function save(): void
    {
        is_login();

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            vendorJsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $id = trim((string) ($_POST['id'] ?? '')) !== '' ? (int) $_POST['id'] : 0;
        $payload = [
            'port_name' => $_POST['port_name'] ?? '',
            'port_code' => $_POST['port_code'] ?? '',
            'port_type' => $_POST['port_type'] ?? '',
            'city' => $_POST['city'] ?? '',
            'country_id' => $_POST['country_id'] ?? 0,
            'pincode' => $_POST['pincode'] ?? '',
            'is_active' => $_POST['is_active'] ?? 1,
        ];
        $userId = (int) ($_SESSION['user']['id'] ?? 0);

        if ($id > 0) {
            vendorJsonResponse($this->portModel->updatePort($id, $payload));
        }

        vendorJsonResponse($this->portModel->insertPort($payload, $userId));
    }

    public function details(): void
    {
        is_login();

        $id = (int) ($_GET['id'] ?? 0);
        $row = $this->portModel->getRecord($id);
        if (!$row) {
            vendorJsonResponse(['success' => false, 'message' => 'Port not found.'], 404);
        }

        vendorJsonResponse(['success' => true, 'port' => $row]);
    }

    public function status(): void
    {
        is_login();

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            vendorJsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $isActive = (int) ($_POST['is_active'] ?? 0);
        vendorJsonResponse($this->portModel->setStatus($id, $isActive));
    }

    public function delete(): void
    {
        is_login();

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            vendorJsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            vendorJsonResponse(['success' => false, 'message' => 'Invalid port id.']);
        }

        vendorJsonResponse($this->portModel->deletePort($id));
    }

    public function checkCode(): void
    {
        is_login();

        $portCode = trim((string) ($_GET['port_code'] ?? ''));
        $excludeId = isset($_GET['excludeId']) ? (int) $_GET['excludeId'] : 0;
        vendorJsonResponse(
            $this->portModel->checkPortCode($portCode, $excludeId > 0 ? $excludeId : null)
        );
    }

    public function search(): void
    {
        is_login();

        $q = trim((string) ($_GET['q'] ?? $_GET['search'] ?? ''));
        $portType = trim((string) ($_GET['port_type'] ?? ''));
        $limit = (int) ($_GET['limit'] ?? 50);
        vendorJsonResponse([
            'success' => true,
            'ports' => $this->portModel->getActivePorts($portType, $q, $limit),
        ]);
    }
}
