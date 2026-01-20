<?php
class PurchaseListCommentModel
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
            LEFT JOIN users u ON u.id = c.user_id
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

    public function add(int $purchaseListId, int $userId, string $comment, ?int $parentId = null): ?array
    {
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
            LEFT JOIN users u ON u.id = c.user_id
            WHERE c.id = ?
            LIMIT 1
        ";

        $stmt2 = $this->db->prepare($sql2);
        if (!$stmt2) return null;

        $stmt2->bind_param("i", $newId);
        $stmt2->execute();
        $res = $stmt2->get_result();

        return $res ? $res->fetch_assoc() : null;
    }
}
