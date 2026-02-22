<?php
class purchaseListComment
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function listByPurchaseListId(int $purchaseListId): array
    {
        // Replace `users` and `name` with your actual user table/field
        $sql = "
            SELECT
              c.id,
              c.purchase_list_id,
              c.parent_id,
              c.user_id,
              c.comment,
              c.is_deleted,
              c.created_at,
              u.name AS user_name
            FROM purchase_list_comments c
            LEFT JOIN vp_users u ON u.id = c.user_id
            WHERE c.purchase_list_id = ?
            ORDER BY c.created_at ASC, c.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param("i", $purchaseListId);
        $stmt->execute();
        $res = $stmt->get_result();

        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function add(int $purchaseListId, int $userId, string $comment, ?int $parentId = null,$sku = null,$order_id=null): ?array
    {
      
      require_once ($_SERVER['DOCUMENT_ROOT']."/models/product/product.php");
      $objProduct =  new product($this->db);

      $sql = "
            INSERT INTO purchase_list_comments
              (purchase_list_id, parent_id, user_id, comment, created_at)
            VALUES
              (?, ?, ?, ?, NOW())
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        // parent_id nullable: bind as i but pass null is ok
        $stmt->bind_param("iiis", $purchaseListId, $parentId, $userId, $comment);

        if (!$stmt->execute()) return null;

        $newId = (int)$stmt->insert_id;

        // Return inserted record including user name
        $sql2 = "
            SELECT
              c.id,
              c.purchase_list_id,
              c.parent_id,
              c.user_id,
              c.comment,
              c.is_deleted,
              c.created_at,
              u.name AS user_name
            FROM purchase_list_comments c
            LEFT JOIN vp_users u ON u.id = c.user_id
            WHERE c.id = ?
            LIMIT 1
        ";

        $stmt2 = $this->db->prepare($sql2);
        if (!$stmt2) return null;

        $stmt2->bind_param("i", $newId);
        $stmt2->execute();
        $res = $stmt2->get_result();

        if($res){
            $data = $res->fetch_assoc();
            // ✅ logged-in user info
            $loggedUserId = (int)($_SESSION['user']['id'] ?? 0);
            $loggedUserName = $_SESSION['user']['name'] ?? 'Unknown';

            $statusText = "Comment Added  (".$comment.")";

            $objProduct->createOrderStatusLog(
                $order_id,         // order_id (required by vp_order_status_log)
                $statusText,                    // status text
                $loggedUserId,                  // changed_by
                $loggedUserName,                // saved inside api_response JSON
                0,         
                [
                    'action' => 'Comment Added',
                    'purchase_list_id' => $purchaseListId,
                    'user_id' => (int)$data['user_id'],
                    'sku' => $sku,
                    'date_added' => date('Y/m/d h:i:s'),
                ]
            );

            return $data;
        } else {
            return null;
        }    
    }
}
