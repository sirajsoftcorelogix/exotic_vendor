<?php

require_once __DIR__ . '/../models/export_document/ExportDocument.php';
require_once __DIR__ . '/../helpers/export_document_helper.php';

/**
 * Presenter / Controller for Export Document Generation Module.
 * Follows strict MVP layer separation.
 */
class ExportDocumentsController
{
    private \mysqli $conn;
    private ExportDocument $exportDocModel;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
        $this->exportDocModel = new ExportDocument($this->conn);
    }

    /**
     * Check permissions using role access hierarchy helper.
     */
    private function checkAccess(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if (!hasTieredAccess($userId, 'Sr Emp Access')) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Access denied. Sr Emp Access required.']);
                exit;
            } else {
                $_SESSION['error'] = 'Access denied. Sr Emp Access required.';
                header('Location: index.php?page=dashboard&action=dashboard');
                exit;
            }
        }
    }

    private function isAjaxRequest(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_GET['ajax']) && $_GET['ajax'] == '1');
    }

    /**
     * Parent / Main Page: Invoice search, matrix configuration, common info.
     */
    public function index(): void
    {
        $this->checkAccess();

        $shipmentTypes = getExportShipmentTypes();
        $categories = getExportCategories();
        $courierPartners = getExportCourierPartners();

        $page = (int)($_GET['p'] ?? 1);
        $limit = 15;
        $offset = max(0, ($page - 1) * $limit);

        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'shipment_type' => trim($_GET['shipment_type'] ?? ''),
            'status' => trim($_GET['status'] ?? '')
        ];

        $recentSessions = $this->exportDocModel->listSessions($filters, $limit, $offset);
        $totalSessions = $this->exportDocModel->countSessions($filters);
        $totalPages = (int)ceil($totalSessions / $limit);

        $initialQuery = trim($_GET['query'] ?? $_GET['invoice_number'] ?? '');

        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/export_documents/index.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * Autocomplete endpoint for searching invoice / order numbers.
     */
    public function autocomplete(): void
    {
        $this->checkAccess();
        header('Content-Type: application/json');

        $term = trim($_GET['term'] ?? $_GET['q'] ?? '');
        $results = $this->exportDocModel->searchInvoicesForAutocomplete($term, 20);

        echo json_encode(['success' => true, 'results' => $results]);
        exit;
    }

    /**
     * AJAX endpoint to fetch auto-pulled invoice details & preview required document matrix.
     */
    public function fetch_invoice(): void
    {
        $this->checkAccess();
        header('Content-Type: application/json');

        $query = trim($_GET['query'] ?? '');
        if ($query === '') {
            echo json_encode(['success' => false, 'message' => 'Invoice or Order number is required.']);
            exit;
        }

        $data = $this->exportDocModel->findInvoiceAndOrderDetails($query);
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'No matching invoice or order found for: ' . htmlspecialchars($query)]);
            exit;
        }

        $commonData = buildCommonExportSessionData($data);

        // Standard defaults
        $shipmentType = $_GET['shipment_type'] ?? 'csb5';
        $category = $_GET['category'] ?? 'sculpture_painting_home';
        $courierPartner = $_GET['courier_partner'] ?? 'ups';
        $isDrawback = !empty($_GET['is_drawback']);
        $hasRodtep = !empty($_GET['has_rodtep']);
        $hasLacey = !empty($_GET['has_lacey']);

        $requiredDocs = resolveRequiredExportDocuments(
            $shipmentType,
            $category,
            $courierPartner,
            $isDrawback,
            $hasRodtep,
            $hasLacey
        );

        echo json_encode([
            'success' => true,
            'auto_pulled' => $data,
            'common_data' => $commonData,
            'required_documents' => $requiredDocs
        ]);
        exit;
    }

    /**
     * POST handler to initialize or update a document session and redirect to generator wizard.
     */
    public function start_session(): void
    {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=export_documents');
            exit;
        }

        $query = trim($_POST['invoice_search'] ?? '');
        $shipmentType = trim($_POST['shipment_type'] ?? 'csb5');
        $category = trim($_POST['category'] ?? 'sculpture_painting_home');
        $courierPartner = trim($_POST['courier_partner'] ?? 'ups');
        $isDrawback = !empty($_POST['is_drawback']);
        $hasRodtep = !empty($_POST['has_rodtep']);
        $hasLacey = !empty($_POST['has_lacey']);

        $autoPulled = $this->exportDocModel->findInvoiceAndOrderDetails($query);
        $commonData = buildCommonExportSessionData($autoPulled ?? []);

        // Override common data with user edits if provided in form
        if (isset($_POST['common']) && is_array($_POST['common'])) {
            foreach ($_POST['common'] as $k => $v) {
                $commonData[$k] = trim((string)$v);
            }
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);

        $sessionPayload = [
            'invoice_id' => $autoPulled['invoice']['id'] ?? null,
            'invoice_number' => $autoPulled['invoice']['invoice_number'] ?? $query,
            'order_number' => $autoPulled['invoice']['order_number'] ?? $query,
            'shipment_type' => $shipmentType,
            'category' => $category,
            'courier_partner' => $courierPartner,
            'is_drawback' => $isDrawback ? 1 : 0,
            'has_rodtep' => $hasRodtep ? 1 : 0,
            'has_lacey' => $hasLacey ? 1 : 0,
            'common_data' => $commonData,
            'status' => 'draft',
            'created_by' => $userId
        ];

        $session = $this->exportDocModel->createSession($sessionPayload);
        $sessionId = (int)$session['id'];

        // Determine required documents
        $requiredDocs = resolveRequiredExportDocuments(
            $shipmentType,
            $category,
            $courierPartner,
            $isDrawback,
            $hasRodtep,
            $hasLacey
        );

        // Pre-populate form templates in DB if not existing
        $items = $autoPulled['items'] ?? [];
        foreach ($requiredDocs as $docCode => $docTitle) {
            $defaultFormData = buildDefaultDocumentFormData($docCode, $commonData, $items);
            $this->exportDocModel->saveForm($sessionId, $docCode, $docTitle, $defaultFormData, false);
        }

        header('Location: index.php?page=export_documents&action=generate&session_code=' . urlencode($session['session_code']));
        exit;
    }

    /**
     * Wizard / Form Filler Page: Displays tabbed form filler for a session.
     */
    public function generate(): void
    {
        $this->checkAccess();

        $sessionCode = trim($_GET['session_code'] ?? '');
        $session = $this->exportDocModel->getSessionByCode($sessionCode);

        if (!$session) {
            $_SESSION['error'] = 'Export document session not found.';
            header('Location: index.php?page=export_documents');
            exit;
        }

        $sessionId = (int)$session['id'];
        $forms = $this->exportDocModel->getAllFormsForSession($sessionId);

        $requiredDocs = resolveRequiredExportDocuments(
            $session['shipment_type'],
            $session['category'],
            $session['courier_partner'],
            !empty($session['is_drawback']),
            !empty($session['has_rodtep']),
            !empty($session['has_lacey'])
        );

        $activeTab = trim($_GET['tab'] ?? '');
        if ($activeTab === '' || !array_key_exists($activeTab, $requiredDocs)) {
            $activeTab = array_key_first($requiredDocs) ?? 'csb5_invoice';
        }

        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/export_documents/generate.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * AJAX handler to save progress on a single document form.
     */
    public function save_form(): void
    {
        $this->checkAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $sessionCode = trim($_POST['session_code'] ?? '');
        $docCode = trim($_POST['document_code'] ?? '');
        $docTitle = trim($_POST['document_title'] ?? '');
        $isCompleted = !empty($_POST['is_completed']);
        $formData = $_POST['form_data'] ?? [];

        if (is_string($formData)) {
            $formData = json_decode($formData, true) ?? [];
        }

        $session = $this->exportDocModel->getSessionByCode($sessionCode);
        if (!$session) {
            echo json_encode(['success' => false, 'message' => 'Session not found.']);
            exit;
        }

        $ok = $this->exportDocModel->saveForm((int)$session['id'], $docCode, $docTitle, $formData, $isCompleted);

        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Document form saved successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save document form data.']);
        }
        exit;
    }

    /**
     * Print Preview & Print Page: Renders printable HTML templates.
     */
    public function preview(): void
    {
        $this->checkAccess();

        $sessionCode = trim($_GET['session_code'] ?? '');
        $session = $this->exportDocModel->getSessionByCode($sessionCode);

        if (!$session) {
            $_SESSION['error'] = 'Export document session not found.';
            header('Location: index.php?page=export_documents');
            exit;
        }

        $sessionId = (int)$session['id'];
        $forms = $this->exportDocModel->getAllFormsForSession($sessionId);

        $requiredDocs = resolveRequiredExportDocuments(
            $session['shipment_type'],
            $session['category'],
            $session['courier_partner'],
            !empty($session['is_drawback']),
            !empty($session['has_rodtep']),
            !empty($session['has_lacey'])
        );

        $docFilter = trim($_GET['doc'] ?? 'all'); // 'all' or specific doc_code

        require __DIR__ . '/../views/export_documents/preview.php';
    }
}
