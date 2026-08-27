<?php

class Support
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->ensureTablesExist();
    }

    private function ensureTablesExist(): void
    {
        $ticketsTableSql = "CREATE TABLE IF NOT EXISTS `vp_support_tickets` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `ticket_number` VARCHAR(50) NOT NULL UNIQUE,
            `user_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `type` ENUM('bug', 'change', 'feature') NOT NULL DEFAULT 'bug',
            `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
            `status` ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
            `module_name` VARCHAR(100) NULL,
            `page_url` TEXT NULL,
            `description` TEXT NOT NULL,
            `attachment` VARCHAR(255) NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`user_id`),
            INDEX (`type`),
            INDEX (`priority`),
            INDEX (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $commentsTableSql = "CREATE TABLE IF NOT EXISTS `vp_support_ticket_comments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `ticket_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `comment` TEXT NOT NULL,
            `attachment` VARCHAR(255) NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (`ticket_id`),
            INDEX (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $this->conn->query($ticketsTableSql);
        $this->conn->query($commentsTableSql);
    }

    public function generateTicketNumber(): string
    {
        $dateStr = date('Ymd');
        $prefix = "TICK-{$dateStr}-";

        $sql = "SELECT ticket_number FROM vp_support_tickets WHERE ticket_number LIKE ? ORDER BY id DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $likeParam = $prefix . '%';
        $stmt->bind_param('s', $likeParam);
        $stmt->execute();
        $res = $stmt->get_result();

        $seq = 1;
        if ($row = $res->fetch_assoc()) {
            $lastNum = (string) $row['ticket_number'];
            $parts = explode('-', $lastNum);
            $lastSeq = (int) end($parts);
            if ($lastSeq > 0) {
                $seq = $lastSeq + 1;
            }
        }
        $stmt->close();

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function createTicket(array $data): int
    {
        $ticketNumber = $this->generateTicketNumber();
        $userId = (int) ($data['user_id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        $type = in_array(($data['type'] ?? ''), ['bug', 'change', 'feature'], true) ? $data['type'] : 'bug';
        $priority = in_array(($data['priority'] ?? ''), ['low', 'medium', 'high', 'urgent'], true) ? $data['priority'] : 'medium';
        $status = 'open';
        $moduleName = trim((string) ($data['module_name'] ?? ''));
        $pageUrl = trim((string) ($data['page_url'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $attachment = trim((string) ($data['attachment'] ?? ''));

        $sql = "INSERT INTO vp_support_tickets 
            (ticket_number, user_id, title, type, priority, status, module_name, page_url, description, attachment, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sissssssss', $ticketNumber, $userId, $title, $type, $priority, $status, $moduleName, $pageUrl, $description, $attachment);
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        return $newId;
    }

    public function getTickets(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $where = ["1=1"];
        $params = [];
        $types = "";

        if (!empty($filters['user_id'])) {
            $where[] = "t.user_id = ?";
            $params[] = (int) $filters['user_id'];
            $types .= "i";
        }

        if (!empty($filters['status'])) {
            $where[] = "t.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }

        if (!empty($filters['type'])) {
            $where[] = "t.type = ?";
            $params[] = $filters['type'];
            $types .= "s";
        }

        if (!empty($filters['priority'])) {
            $where[] = "t.priority = ?";
            $params[] = $filters['priority'];
            $types .= "s";
        }

        if (!empty($filters['search'])) {
            $where[] = "(t.ticket_number LIKE ? OR t.title LIKE ? OR t.description LIKE ? OR t.module_name LIKE ?)";
            $searchTerm = '%' . trim($filters['search']) . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ssss";
        }

        $whereClause = implode(" AND ", $where);

        // Count Total
        $countSql = "SELECT COUNT(*) as total FROM vp_support_tickets t WHERE {$whereClause}";
        $stmtCount = $this->conn->prepare($countSql);
        if ($types !== "") {
            $stmtCount->bind_param($types, ...$params);
        }
        $stmtCount->execute();
        $totalRecords = (int) ($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
        $stmtCount->close();

        // Fetch List
        $sql = "SELECT t.*, u.name as user_name, u.email as user_email,
                (SELECT COUNT(*) FROM vp_support_ticket_comments c WHERE c.ticket_id = t.id) as comment_count
                FROM vp_support_tickets t
                LEFT JOIN vp_users u ON t.user_id = u.id
                WHERE {$whereClause}
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?";

        $typesWithLimit = $types . "ii";
        $paramsWithLimit = array_merge($params, [$limit, $offset]);

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
        $stmt->execute();
        $res = $stmt->get_result();

        $tickets = [];
        while ($row = $res->fetch_assoc()) {
            $tickets[] = $row;
        }
        $stmt->close();

        return [
            'tickets' => $tickets,
            'total' => $totalRecords,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => max(1, (int) ceil($totalRecords / $limit)),
        ];
    }

    public function getTicketById(int $id): ?array
    {
        $sql = "SELECT t.*, u.name as user_name, u.email as user_email 
                FROM vp_support_tickets t
                LEFT JOIN vp_users u ON t.user_id = u.id
                WHERE t.id = ? LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();

        return $row;
    }

    public function updateTicketStatus(int $id, string $status, ?string $priority = null): bool
    {
        $validStatuses = ['open', 'in_progress', 'resolved', 'closed'];
        if (!in_array($status, $validStatuses, true)) {
            return false;
        }

        if ($priority !== null && in_array($priority, ['low', 'medium', 'high', 'urgent'], true)) {
            $sql = "UPDATE vp_support_tickets SET status = ?, priority = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('ssi', $status, $priority, $id);
        } else {
            $sql = "UPDATE vp_support_tickets SET status = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('si', $status, $id);
        }

        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function addComment(int $ticketId, int $userId, string $comment, ?string $attachment = null): int
    {
        $comment = trim($comment);
        if ($comment === '') {
            return 0;
        }

        $sql = "INSERT INTO vp_support_ticket_comments (ticket_id, user_id, comment, attachment, created_at) 
                VALUES (?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iiss', $ticketId, $userId, $comment, $attachment);
        $stmt->execute();
        $commentId = $stmt->insert_id;
        $stmt->close();

        // Touch updated_at on ticket
        $touchSql = "UPDATE vp_support_tickets SET updated_at = NOW() WHERE id = ?";
        $touchStmt = $this->conn->prepare($touchSql);
        $touchStmt->bind_param('i', $ticketId);
        $touchStmt->execute();
        $touchStmt->close();

        return $commentId;
    }

    public function getComments(int $ticketId): array
    {
        $sql = "SELECT c.*, u.name as user_name, u.email as user_email
                FROM vp_support_ticket_comments c
                LEFT JOIN vp_users u ON c.user_id = u.id
                WHERE c.ticket_id = ?
                ORDER BY c.created_at ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $ticketId);
        $stmt->execute();
        $res = $stmt->get_result();

        $comments = [];
        while ($row = $res->fetch_assoc()) {
            $comments[] = $row;
        }
        $stmt->close();

        return $comments;
    }

    public function getTicketStats(?int $userId = null): array
    {
        $where = "1=1";
        $params = [];
        $types = "";

        if ($userId !== null && $userId > 0) {
            $where .= " AND user_id = ?";
            $params[] = $userId;
            $types = "i";
        }

        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count,
                    SUM(CASE WHEN type = 'bug' THEN 1 ELSE 0 END) as bug_count,
                    SUM(CASE WHEN type = 'change' THEN 1 ELSE 0 END) as change_count,
                    SUM(CASE WHEN type = 'feature' THEN 1 ELSE 0 END) as feature_count
                FROM vp_support_tickets 
                WHERE {$where}";

        $stmt = $this->conn->prepare($sql);
        if ($types !== "") {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'open' => (int) ($row['open_count'] ?? 0),
            'in_progress' => (int) ($row['in_progress_count'] ?? 0),
            'resolved' => (int) ($row['resolved_count'] ?? 0),
            'closed' => (int) ($row['closed_count'] ?? 0),
            'bug' => (int) ($row['bug_count'] ?? 0),
            'change' => (int) ($row['change_count'] ?? 0),
            'feature' => (int) ($row['feature_count'] ?? 0),
        ];
    }
}
