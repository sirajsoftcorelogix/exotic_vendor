<?php

require_once 'models/category/Category.php';
require_once __DIR__ . '/../integrations/exotic/vendor_product_api.php';

class CategoryController
{
    private Category $categoryModel;

    public function __construct(mysqli $conn)
    {
        $this->categoryModel = new Category($conn);
    }

    /**
     * Render Category Master listing page.
     */
    public function index(): void
    {
        is_login();

        $search = trim((string) ($_GET['search_text'] ?? ''));
        $sortBy = trim((string) ($_GET['sort_by'] ?? 'category'));
        $sortDir = trim((string) ($_GET['sort_dir'] ?? 'ASC'));
        $pageNo = max(1, (int) ($_GET['page_no'] ?? 1));
        $limit = (int) ($_GET['limit'] ?? 20);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;

        $listing = $this->categoryModel->getAll($pageNo, $limit, $search, $sortBy, $sortDir);

        renderTemplate('views/category/index.php', [
            'categories' => $listing['categories'],
            'currentPage' => $listing['currentPage'],
            'totalPages' => $listing['totalPages'],
            'totalRecords' => $listing['totalRecords'],
            'limit' => $listing['limit'],
            'search' => $search,
            'sortBy' => $listing['sortBy'],
            'sortDir' => $listing['sortDir'],
        ], 'Category Master');
    }

    /**
     * AJAX endpoint to pull latest categories from Exotic India API.
     */
    public function pullCategories(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $apiRes = vendor_external_api_fetch_category_list();
        if (!$apiRes['success']) {
            echo json_encode([
                'success' => false,
                'message' => $apiRes['message'],
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $categories = $apiRes['categories'] ?? [];
        if (!is_array($categories)) {
            echo json_encode([
                'success' => false,
                'message' => 'API returned invalid categories data structure.',
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        $syncRes = $this->categoryModel->syncFromApi($categories, $userId, $ipAddress);
        echo json_encode($syncRes, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /**
     * AJAX endpoint to fetch category details by ID for edit modal.
     */
    public function getDetails(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid category ID.']);
            exit;
        }

        $category = $this->categoryModel->getCategoryById($id);
        if ($category) {
            echo json_encode(['success' => true, 'category' => $category], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } else {
            echo json_encode(['success' => false, 'message' => 'Category not found.']);
        }
        exit;
    }

    /**
     * AJAX endpoint to edit category details.
     */
    public function edit(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid category ID.']);
            exit;
        }

        $result = $this->categoryModel->updateCategory($id, $_POST);
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /**
     * AJAX endpoint to check if a category is in use in vp_inbound or vp_products.
     */
    public function checkUsage(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid category ID.']);
            exit;
        }

        $usage = $this->categoryModel->getCategoryUsage($id);
        $details = [];
        if ($usage['inbound_count'] > 0) {
            $details[] = sprintf('vp_inbound (%d record%s)', $usage['inbound_count'], $usage['inbound_count'] === 1 ? '' : 's');
        }
        if ($usage['product_count'] > 0) {
            $details[] = sprintf('vp_products (%d product%s)', $usage['product_count'], $usage['product_count'] === 1 ? '' : 's');
        }

        $message = $usage['in_use']
            ? 'Cannot delete category: it is currently in use in ' . implode(' and ', $details) . '. Please reassign or remove those references first.'
            : 'Category is not in use and can be safely deleted.';

        echo json_encode([
            'success' => true,
            'in_use' => $usage['in_use'],
            'inbound_count' => $usage['inbound_count'],
            'product_count' => $usage['product_count'],
            'can_delete' => !$usage['in_use'],
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /**
     * AJAX endpoint to delete a category.
     */
    public function delete(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid category ID.']);
            exit;
        }

        $result = $this->categoryModel->deleteCategory($id);
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /**
     * Legacy action for backward compatibility.
     */
    public function updateMarkup(): void
    {
        is_login();
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['markup'])) {
            $status = $this->categoryModel->updateCategoryMarkups($_POST['markup']);
            if ($status) {
                header('Location: ' . base_url('?page=category&action=list'));
                exit;
            }
            echo 'Error saving data.';
        }
    }
}
