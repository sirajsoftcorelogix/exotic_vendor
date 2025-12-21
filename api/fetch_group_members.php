<?php
require 'auth.php';

$conversationId = (int)($_GET['conversation_id'] ?? 0);
if (!$conversationId) {
    echo json_encode([]);
    exit;
}

// Ensure user is a member
$stmt = $pdo->prepare("
    SELECT 1 FROM conversation_members 
    WHERE conversation_id = ? AND user_id = ?
");
$stmt->execute([$conversationId, $_SESSION['user']['id']]);
if (!$stmt->fetchColumn()) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

// Fetch members + online status
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.name,
        COALESCE(us.is_online, 0) AS is_online
    FROM conversation_members cm
    JOIN vp_users u ON u.id = cm.user_id
    LEFT JOIN user_status us ON us.user_id = u.id
    WHERE cm.conversation_id = ?
    ORDER BY u.name
");
$stmt->execute([$conversationId]);

echo json_encode($stmt->fetchAll());
