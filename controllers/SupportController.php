<?php

require_once 'models/support/Support.php';

class SupportController
{
    private Support $supportModel;

    public function __construct(mysqli $conn)
    {
        $this->supportModel = new Support($conn);
    }

    public function index(): void
    {
        is_login();

        $page = max(1, (int) ($_GET['page_no'] ?? 1));
        $limit = 20;
        $search = trim((string) ($_GET['search'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $type = trim((string) ($_GET['type'] ?? ''));
        $priority = trim((string) ($_GET['priority'] ?? ''));
        $filterScope = trim((string) ($_GET['scope'] ?? 'all')); // 'all' or 'my'

        $currentUserId = (int) ($_SESSION['user']['id'] ?? 0);
        $isAdmin = isAdministratorUser();

        $filters = [
            'search' => $search,
            'status' => $status,
            'type' => $type,
            'priority' => $priority,
        ];

        // Non-admin or explicit 'my' filter scope restricts to own tickets
        if (!$isAdmin || $filterScope === 'my') {
            $filters['user_id'] = $currentUserId;
        }

        $result = $this->supportModel->getTickets($page, $limit, $filters);
        $stats = $this->supportModel->getTicketStats((!$isAdmin || $filterScope === 'my') ? $currentUserId : null);

        renderTemplate('views/support/index.php', [
            'tickets' => $result['tickets'],
            'total' => $result['total'],
            'currentPage' => $result['page'],
            'totalPages' => $result['totalPages'],
            'limit' => $result['limit'],
            'search' => $search,
            'status' => $status,
            'type' => $type,
            'priority' => $priority,
            'scope' => $filterScope,
            'stats' => $stats,
            'isAdmin' => $isAdmin,
        ], 'Support & Issue Tracker');
    }

    public function view(): void
    {
        is_login();

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: index.php?page=support&action=list');
            exit;
        }

        $ticket = $this->supportModel->getTicketById($id);
        if (!$ticket) {
            header('Location: index.php?page=support&action=list');
            exit;
        }

        $currentUserId = (int) ($_SESSION['user']['id'] ?? 0);
        $isAdmin = isAdministratorUser();

        // Non-admin can only view their own tickets
        if (!$isAdmin && (int) $ticket['user_id'] !== $currentUserId) {
            header('Location: index.php?page=support&action=list');
            exit;
        }

        $comments = $this->supportModel->getComments($id);

        renderTemplate('views/support/view.php', [
            'ticket' => $ticket,
            'comments' => $comments,
            'isAdmin' => $isAdmin,
            'currentUserId' => $currentUserId,
        ], 'Ticket ' . htmlspecialchars($ticket['ticket_number']));
    }

    public function save(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $type = trim((string) ($_POST['type'] ?? 'bug'));
        $priority = trim((string) ($_POST['priority'] ?? 'medium'));
        $moduleName = trim((string) ($_POST['module_name'] ?? ''));
        $pageUrl = trim((string) ($_POST['page_url'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        if ($title === '') {
            echo json_encode(['success' => false, 'message' => 'Please provide a title/subject for the issue.']);
            exit;
        }

        if ($description === '') {
            echo json_encode(['success' => false, 'message' => 'Please provide a description of the issue or feature request.']);
            exit;
        }

        $attachmentPath = '';
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['attachment']['tmp_name'];
            $origName = $_FILES['attachment']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'txt', 'zip'];

            if (in_array($ext, $allowedExts, true)) {
                $uploadDir = __DIR__ . '/../uploads/support/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $newFileName = 'supp_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
                    $attachmentPath = 'uploads/support/' . $newFileName;
                }
            }
        }

        $ticketData = [
            'user_id' => (int) ($_SESSION['user']['id'] ?? 0),
            'title' => $title,
            'type' => $type,
            'priority' => $priority,
            'module_name' => $moduleName,
            'page_url' => $pageUrl,
            'description' => $description,
            'attachment' => $attachmentPath,
        ];

        $newId = $this->supportModel->createTicket($ticketData);

        if ($newId > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Support request submitted successfully!',
                'ticket_id' => $newId,
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create support ticket. Please try again.']);
        }
        exit;
    }

    public function add_comment(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));
        $newStatus = trim((string) ($_POST['status'] ?? ''));

        if ($ticketId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ticket ID.']);
            exit;
        }

        $ticket = $this->supportModel->getTicketById($ticketId);
        if (!$ticket) {
            echo json_encode(['success' => false, 'message' => 'Ticket not found.']);
            exit;
        }

        $currentUserId = (int) ($_SESSION['user']['id'] ?? 0);
        $isAdmin = isAdministratorUser();

        if (!$isAdmin && (int) $ticket['user_id'] !== $currentUserId) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }

        if ($comment === '' && $newStatus === '') {
            echo json_encode(['success' => false, 'message' => 'Please enter a reply or change the status.']);
            exit;
        }

        $attachmentPath = '';
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['attachment']['tmp_name'];
            $origName = $_FILES['attachment']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'txt', 'zip'];

            if (in_array($ext, $allowedExts, true)) {
                $uploadDir = __DIR__ . '/../uploads/support/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $newFileName = 'supp_reply_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
                    $attachmentPath = 'uploads/support/' . $newFileName;
                }
            }
        }

        if ($comment !== '') {
            $this->supportModel->addComment($ticketId, $currentUserId, $comment, $attachmentPath);
        }

        if ($newStatus !== '' && in_array($newStatus, ['open', 'in_progress', 'resolved', 'closed'], true)) {
            $newPriority = trim((string) ($_POST['priority'] ?? ''));
            $this->supportModel->updateTicketStatus($ticketId, $newStatus, $newPriority !== '' ? $newPriority : null);
        }

        echo json_encode(['success' => true, 'message' => 'Response submitted successfully!']);
        exit;
    }

    public function update_status(): void
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));
        $priority = trim((string) ($_POST['priority'] ?? ''));

        if ($ticketId <= 0 || $status === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid inputs.']);
            exit;
        }

        $isAdmin = isAdministratorUser();
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'message' => 'Only administrators can modify ticket status.']);
            exit;
        }

        $updated = $this->supportModel->updateTicketStatus($ticketId, $status, $priority !== '' ? $priority : null);

        if ($updated) {
            echo json_encode(['success' => true, 'message' => 'Ticket updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update ticket.']);
        }
        exit;
    }
}
