<?php

/**
 * Maps local vp_order_status.slug to Exotic India admin_id (order_status code).
 */
class OrderStatusResolver
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function resolveAdminId(string $slug): int
    {
        $row = $this->getStatusRow($slug);

        return (int) ($row['admin_id'] ?? 0);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getStatusRow(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $sql = 'SELECT * FROM vp_order_status WHERE slug = ? LIMIT 1';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
        $stmt->close();

        return is_array($row) ? $row : null;
    }
}
