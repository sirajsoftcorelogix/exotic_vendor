<?php
require_once 'models/author/Author.php';
require_once __DIR__ . '/../helpers/vendor_external_api.php';

class AuthorsController
{
    private Author $authorModel;

    public function __construct(mysqli $conn)
    {
        $this->authorModel = new Author($conn);
    }

    public function index(): void
    {
        is_login();

        $search = trim((string)($_GET['search_text'] ?? ''));
        $status = trim((string)($_GET['status_filter'] ?? ''));
        $pageNo = max(1, (int)($_GET['page_no'] ?? 1));
        $limit = (int)($_GET['limit'] ?? 20);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;

        $listing = $this->authorModel->getAuthors($pageNo, $limit, $search, $status);
        renderTemplate('views/authors/index.php', [
            'authors' => $listing['authors'],
            'search' => $search,
            'status_filter' => $status,
            'currentPage' => $listing['currentPage'],
            'totalPages' => $listing['totalPages'],
            'totalRecords' => $listing['totalRecords'],
            'limit' => $listing['limit'],
        ], 'Manage Authors');
    }

    public function save(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $id = trim((string)($_POST['author_id'] ?? '')) !== '' ? (int)$_POST['author_id'] : null;
        $name = trim((string)($_POST['author'] ?? ''));
        $isActive = (int)($_POST['is_active'] ?? 1);
        $webpage = (string)($_POST['webpage'] ?? '0') === '1' ? '1' : '0';

        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Author name is required.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        if ($this->authorModel->authorNameExists($name, ($id && $id > 0) ? $id : null)) {
            echo json_encode(['success' => false, 'message' => 'Author name already exists'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        if ($id && $id > 0) {
            $existing = $this->authorModel->getAuthorById($id);
            if (!$existing) {
                echo json_encode(['success' => false, 'message' => 'Author not found.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }

            $api = vendor_external_api_modify(
                vendor_external_api_modify_creator_payload((string) $id, 'author', $name, $webpage)
            );
            if (!$api['success']) {
                echo json_encode($api, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }

            $result = $this->authorModel->saveAuthor($id, $name, $isActive);
            if ($result['success']) {
                $result['message'] = 'Author saved on Exotic India and locally.';
            }
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $api = vendor_external_api_create(vendor_external_api_creator_payload('author', $name, $webpage));
        if (!$api['success']) {
            echo json_encode($api, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $remoteId = (int) ($api['vendor_id'] ?? 0);
        if ($remoteId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Author API did not return vendor_id.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $result = $this->authorModel->insertAuthor($remoteId, $name, $isActive);
        if ($result['success']) {
            $result['message'] = 'Author created on Exotic India and saved locally.';
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function details(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)($_GET['id'] ?? 0);
        $row = $this->authorModel->getAuthorById($id);
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Author not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'author' => $row], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
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

        $id = (int)($_POST['author_id'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 0);
        echo json_encode($this->authorModel->setStatus($id, $isActive), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
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

        $id = (int)($_POST['author_id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid author id.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $existing = $this->authorModel->getAuthorById($id);
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Author not found.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $api = vendor_external_api_delete((string) $id);
        if (!$api['success']) {
            echo json_encode($api, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $result = $this->authorModel->deleteAuthor($id);
        if ($result['success']) {
            $result['message'] = 'Author deleted on Exotic India and locally.';
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
            $this->authorModel->checkAuthorName($name, $excludeId > 0 ? $excludeId : null),
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        exit;
    }

    public function syncFromAdmin(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['user']['role_id']) || (int)$_SESSION['user']['role_id'] !== 1) {
            echo json_encode(['success' => false, 'message' => 'Only admin users can sync authors from Admin.']);
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $api = $this->fetchAuthorCreatorList();
        if (!$api['success']) {
            echo json_encode($api, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $import = $this->authorModel->importFromCreators($api['creators']);
        $import['api_count'] = count($api['creators']);
        echo json_encode($import, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /**
     * @return array{success:bool,message:string,creators?:array<int|string,string>,http_code?:int}
     */
    private function fetchAuthorCreatorList(): array
    {
        $ch = curl_init('https://www.exoticindia.com/vendor-api/product/creatorlist?type=author');
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
                'x-adminapitest: 1',
                'Accept: application/json',
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '') {
            return ['success' => false, 'message' => 'Author API error: ' . $error, 'http_code' => $httpCode];
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            return ['success' => false, 'message' => 'Author API returned HTTP ' . $httpCode, 'http_code' => $httpCode];
        }

        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded) || !is_array($decoded['creators'] ?? null)) {
            return ['success' => false, 'message' => 'Author API returned invalid JSON.', 'http_code' => $httpCode];
        }

        return [
            'success' => true,
            'message' => 'Author API fetched successfully.',
            'http_code' => $httpCode,
            'creators' => $decoded['creators'],
        ];
    }
}
