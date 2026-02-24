<?php
require_once 'models/product/purchaseListComment.php';
class PurchaseListCommentController
{
    private purchaseListComment $model;

    public function __construct(mysqli $db)
    {
        $this->model = new purchaseListComment($db);

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
   

    /* -------------------------------------------
       Actions
    ------------------------------------------- */
    public function list(): void
    {
        is_login();

        $purchaseListId = isset($_REQUEST['purchase_list_id'])
            ? (int)$_REQUEST['purchase_list_id']
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
        is_login();

        $purchaseListId = isset($_POST['purchase_list_id'])
            ? (int)$_POST['purchase_list_id']
            : 0;

        $comment = isset($_POST['comment'])
            ? trim($_POST['comment'])
            : '';

        $sku = isset($_POST['sku'])
            ? trim($_POST['sku'])
            : '';

        $order_id = isset($_POST['orderID'])
            ? trim($_POST['orderID'])
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
            $_SESSION['user']['id'],
            $comment,
            $parentId,
            $sku,
            $order_id 
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
