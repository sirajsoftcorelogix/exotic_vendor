<?php
require_once 'models/publisher/Publisher.php';
require_once 'models/country/country.php';
require_once 'models/country/state.php';
require_once 'models/vendor/vendor.php';
require_once __DIR__ . '/../integrations/exotic/vendor_product_api.php';
require_once __DIR__ . '/../helpers/publisher_vendor_sync.php';

class PublishersController
{
    private Publisher $publisherModel;
    private Country $countryModel;
    private State $stateModel;
    private vendor $vendorModel;

    public function __construct(mysqli $conn)
    {
        $this->publisherModel = new Publisher($conn);
        $this->countryModel = new Country($conn);
        $this->stateModel = new State($conn);
        $this->vendorModel = new vendor($conn);
    }

    public function index(): void
    {
        is_login();

        $search = trim((string)($_GET['search_text'] ?? ''));
        $status = trim((string)($_GET['status_filter'] ?? ''));
        $pageNo = max(1, (int)($_GET['page_no'] ?? 1));
        $limit = (int)($_GET['limit'] ?? 20);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;

        $listing = $this->publisherModel->getPublishers($pageNo, $limit, $search, $status);
        $countryList = $this->countryModel->getAllCountries();
        $stateList = $this->stateModel->getAllStates(105);
        renderTemplate('views/publishers/index.php', [
            'publishers' => $listing['publishers'],
            'search' => $search,
            'status_filter' => $status,
            'currentPage' => $listing['currentPage'],
            'totalPages' => $listing['totalPages'],
            'totalRecords' => $listing['totalRecords'],
            'limit' => $listing['limit'],
            'countryList' => $countryList['countries'] ?? [],
            'stateList' => $stateList['states'] ?? [],
        ], 'Manage Publishers');
    }

    public function save(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $id = trim((string)($_POST['id'] ?? '')) !== '' ? (int)$_POST['id'] : null;
        $name = trim((string)($_POST['publishers'] ?? ''));
        $isActive = (int)($_POST['is_active'] ?? 1);
        $extra = $this->publisherModel->normalizePublisherFormData($_POST);

        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Publisher name is required.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $existing = ($id && $id > 0) ? $this->publisherModel->getPublisherById($id) : null;
        if ($id && !$existing) {
            echo json_encode(['success' => false, 'message' => 'Publisher not found.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        if ($this->publisherModel->isDuplicatePublisherName($name, $id)) {
            echo json_encode(['success' => false, 'message' => 'Publisher name already exists'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $webpage = $extra['webpage'];

        if ($id && $id > 0) {
            $remoteId = (int) ($existing['publishers_id'] ?? 0);
            $api = vendor_external_api_sync_creator('publisher', $name, $webpage, $remoteId > 0 ? $remoteId : null);
            if (!vendor_external_api_allows_local_save($api)) {
                echo json_encode($api, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }
            if ($remoteId <= 0 && !empty($api['vendor_id'])) {
                $link = $this->publisherModel->updatePublisherRemoteId($id, (int) $api['vendor_id']);
                if (!$link['success']) {
                    echo json_encode($link, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                    exit;
                }
            }

            $result = $this->publisherModel->savePublisher($id, $name, $isActive, $extra);
            if ($result['success']) {
                $result['message'] = 'Publisher saved on Exotic India and locally.';
                $alsoCreateVendor = (string) ($_POST['also_create_vendor'] ?? '') === '1';
                if ($alsoCreateVendor) {
                    $vendorResult = $this->createLinkedVendorFromPublisher($id, $name, $isActive, $extra);
                    if ($vendorResult['success']) {
                        $result['message'] .= ' Linked vendor created and mapped as distributor (Manage Distributors).';
                        if (!empty($vendorResult['vendor_api_warning'])) {
                            $result['vendor_create_warning'] = true;
                        }
                    } else {
                        $result['vendor_create_warning'] = true;
                        $result['message'] .= ' Vendor was not created: ' . ($vendorResult['message'] ?? 'Unknown error.');
                    }
                }
            }
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $api = vendor_external_api_sync_creator('publisher', $name, $webpage, null);
        if (!$api['success']) {
            echo json_encode($api, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $remoteId = (int) ($api['vendor_id'] ?? 0);
        if ($remoteId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Publisher API did not return vendor_id.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $result = $this->publisherModel->insertPublisher($remoteId, $name, $isActive, $extra);
        if ($result['success']) {
            $result['message'] = 'Publisher created on Exotic India and saved locally.';
            $alsoCreateVendor = (string) ($_POST['also_create_vendor'] ?? '') === '1';
            if ($alsoCreateVendor) {
                $publisherLocalId = (int) ($result['id'] ?? 0);
                $vendorResult = $this->createLinkedVendorFromPublisher($publisherLocalId, $name, $isActive, $extra);
                if ($vendorResult['success']) {
                    $result['message'] .= ' Linked vendor created and mapped as distributor (Manage Distributors).';
                    if (!empty($vendorResult['vendor_api_warning'])) {
                        $result['vendor_create_warning'] = true;
                    }
                } else {
                    $result['vendor_create_warning'] = true;
                    $result['message'] .= ' Vendor was not created: ' . ($vendorResult['message'] ?? 'Unknown error.');
                }
            }
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /**
     * Create vp_vendors row from publisher data, sync book vendor to Exotic India, and map to publisher.
     *
     * @return array{success:bool,message?:string,vendor_id?:int}
     */
    private function createLinkedVendorFromPublisher(int $publisherLocalId, string $name, int $isActive, array $extra): array
    {
        if ($publisherLocalId <= 0) {
            return ['success' => false, 'message' => 'Publisher id missing after save.'];
        }

        $vendorPayload = build_vendor_add_payload_from_publisher($name, $extra, $isActive);
        $validationError = publisher_vendor_create_validation_error($vendorPayload);
        if ($validationError !== null) {
            return $validationError;
        }

        $groupsCsv = vendor_external_api_normalize_groupnames_csv($vendorPayload['groupname'] ?? 'book');
        $apiFieldError = vendor_external_api_validate_vendor_fields($name, $groupsCsv);
        if ($apiFieldError !== null) {
            return $apiFieldError;
        }

        $insertResult = $this->vendorModel->addVendor($vendorPayload);
        if (empty($insertResult['success'])) {
            return [
                'success' => false,
                'message' => (string) ($insertResult['message'] ?? 'Could not create vendor locally.'),
            ];
        }

        $localVendorId = (int) ($insertResult['inserted_id'] ?? 0);
        if ($localVendorId <= 0) {
            return ['success' => false, 'message' => 'Vendor insert did not return an id.'];
        }

        $mapResult = $this->publisherModel->addPublisherVendorMapping($publisherLocalId, $localVendorId, [
            'allow_inactive_vendor' => true,
            'idempotent' => true,
        ]);
        if (empty($mapResult['success'])) {
            return [
                'success' => false,
                'message' => (string) ($mapResult['message'] ?? 'Vendor created but could not map as distributor.'),
                'vendor_id' => $localVendorId,
            ];
        }

        $webpage = (string) ($vendorPayload['addWebpage'] ?? '0');
        $apiWarning = null;
        $api = vendor_external_api_sync_catalog($name, $groupsCsv, $webpage, null);
        if (!empty($api['vendor_id'])) {
            $this->vendorModel->updateVendorRemoteId($localVendorId, (string) $api['vendor_id']);
        } elseif (!vendor_external_api_allows_local_save($api)) {
            $apiWarning = (string) ($api['message'] ?? 'Exotic India vendor sync failed.');
        }

        $message = 'Vendor created and mapped as distributor.';
        if ($apiWarning !== null) {
            $message .= ' ' . $apiWarning;
        }

        return [
            'success' => true,
            'message' => $message,
            'vendor_id' => $localVendorId,
            'mapping_id' => (int) ($mapResult['mapping_id'] ?? 0),
            'vendor_api_warning' => $apiWarning !== null,
            'mappings' => $mapResult['mappings'] ?? [],
        ];
    }

    public function details(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)($_GET['id'] ?? 0);
        $row = $this->publisherModel->getPublisherById($id);
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Publisher not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'publisher' => $row], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function status(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 0);
        echo json_encode($this->publisherModel->setStatus($id, $isActive), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function delete(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid publisher id.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $existing = $this->publisherModel->getPublisherById($id);
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Publisher not found.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $remoteId = (int) ($existing['publishers_id'] ?? 0);
        if ($remoteId > 0) {
            $api = vendor_external_api_delete((string) $remoteId);
            if (!$api['success']) {
                echo json_encode($api, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }
        }

        $result = $this->publisherModel->deletePublisher($id);
        if ($result['success']) {
            $result['message'] = 'Publisher deleted on Exotic India and locally.';
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function checkName(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $name = trim((string)($_GET['name'] ?? ''));
        $excludeId = isset($_GET['excludeId']) ? (int) $_GET['excludeId'] : 0;
        echo json_encode(
            $this->publisherModel->checkPublisherName($name, $excludeId > 0 ? $excludeId : null),
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        exit;
    }

    public function getBankDetails(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid publisher ID.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $bankDetails = $this->publisherModel->getBankDetailsById($id);
        if (is_array($bankDetails) && isset($bankDetails['success']) && $bankDetails['success'] === false) {
            echo json_encode($bankDetails, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }
        if (!empty($bankDetails) && is_array($bankDetails)) {
            echo json_encode($bankDetails, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        echo json_encode(['status' => 'success', 'message' => ''], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function addBankDetails(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $data = $_POST;
        $data['bdStatus'] = 1;
        $publisherId = isset($data['publisher_id']) ? (int)$data['publisher_id'] : 0;
        if ($publisherId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid publisher ID.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $existing = $this->publisherModel->getBankDetailsById($publisherId);
        if (is_array($existing) && isset($existing['success']) && $existing['success'] === false) {
            echo json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $result = $existing
            ? $this->publisherModel->updateBankDetails($data)
            : $this->publisherModel->saveBankDetails($data);
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function getVendorMappings(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $publisherId = (int) ($_GET['id'] ?? 0);
        if ($publisherId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid publisher ID.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $publisher = $this->publisherModel->getPublisherById($publisherId);
        if (!$publisher) {
            echo json_encode(['success' => false, 'message' => 'Publisher not found.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        echo json_encode([
            'success' => true,
            'publisher' => [
                'id' => (int) ($publisher['id'] ?? 0),
                'publishers' => (string) ($publisher['publishers'] ?? ''),
            ],
            'mappings' => $this->publisherModel->getVendorMappingsByPublisherId($publisherId),
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function searchMappingVendors(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $publisherId = (int) ($_GET['publisher_id'] ?? 0);
        $query = trim((string) ($_GET['q'] ?? ''));
        if ($publisherId <= 0) {
            echo json_encode([], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        echo json_encode(
            $this->publisherModel->searchVendorsForPublisherMapping($query, $publisherId),
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        exit;
    }

    public function addVendorMapping(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $publisherId = (int) ($_POST['publisher_id'] ?? 0);
        $vendorId = (int) ($_POST['vendor_id'] ?? 0);
        echo json_encode(
            $this->publisherModel->addPublisherVendorMapping($publisherId, $vendorId),
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        exit;
    }

    public function removeVendorMapping(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $publisherId = (int) ($_POST['publisher_id'] ?? 0);
        $mappingId = (int) ($_POST['mapping_id'] ?? 0);
        echo json_encode(
            $this->publisherModel->removePublisherVendorMapping($publisherId, $mappingId),
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        exit;
    }

    public function syncFromAdmin(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['user']['role_id']) || (int)$_SESSION['user']['role_id'] !== 1) {
            echo json_encode(['success' => false, 'message' => 'Only admin users can sync publishers from Admin.']);
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $api = vendor_external_api_fetch_creator_list('publishers');
        if (!$api['success']) {
            echo json_encode($api, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $import = $this->publisherModel->importFromCreators($api['creators']);
        $import['api_count'] = count($api['creators']);
        echo json_encode($import, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}
