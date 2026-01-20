<?php
require_once __DIR__ . '/../models/PurchaseListComment.php';
class PurchaseListCommentController
{
    private PurchaseListComment $model;

    public function __construct(mysqli $db)
    {
        $this->model = new PurchaseListComment($db);

        // JSON responses only
        header('Content-Type: application/json');
    }

    /* -------------------------------------------
       Helpers
    ------------------------------------------- */

    private function respond(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    private function requireAuth(): int
    {
        if (empty($_SESSION['user_id'])) {
            $this->respond([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        return (int) $_SESSION['user_id'];
    }

    /* -------------------------------------------
       Actions
    ------------------------------------------- */
    public function list(): void
    {
        $userId = $this->requireAuth();

        $purchaseListId = isset($_GET['purchase_list_id'])
            ? (int)$_GET['purchase_list_id']
            : 0;

        if ($purchaseListId <= 0) {
            $this->respond([
                'success' => false,
                'message' => 'Invalid purchase_list_id'
            ], 400);
        }

        $comments = $this->model->listByPurchaseListId($purchaseListId);

        $this->respond([
            'success'  => true,
            'comments' => $comments
        ]);
        exit;    
    }

    // POST /comments?action=add
    public function add(): void
    {
        $userId = $this->requireAuth();

        $purchaseListId = isset($_POST['purchase_list_id'])
            ? (int)$_POST['purchase_list_id']
            : 0;

        $comment = isset($_POST['comment'])
            ? trim($_POST['comment'])
            : '';

        $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== ''
            ? (int)$_POST['parent_id']
            : null;

        if ($purchaseListId <= 0) {
            $this->respond([
                'success' => false,
                'message' => 'Invalid purchase_list_id'
            ], 400);
        }

        if ($comment === '') {
            $this->respond([
                'success' => false,
                'message' => 'Comment is required'
            ], 400);
        }

        if (mb_strlen($comment) > 5000) {
            $this->respond([
                'success' => false,
                'message' => 'Comment too long'
            ], 400);
        }

        $row = $this->model->add(
            $purchaseListId,
            $userId,
            $comment,
            $parentId
        );

        if (!$row) {
            $this->respond([
                'success' => false,
                'message' => 'Failed to add comment'
            ], 500);
        }

        $this->respond([
            'success' => true,
            'comment' => $row
        ]);
    }
}
