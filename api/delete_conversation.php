<?php
require 'auth.php';

$data = json_decode(file_get_contents("php://input"), true);
$conversationId = (int)($data['conversation_id'] ?? 0);
$userId = $_SESSION['user']['id'];

if (!$conversationId) {
    echo json_encode(['error' => 'invalid']);
    exit;
}

// Fetch conversation
$stmt = $pdo->prepare("
    SELECT type, created_by 
    FROM conversations 
    WHERE id = ?
");
$stmt->execute([$conversationId]);
$conv = $stmt->fetch();

if (!$conv) {
    echo json_encode(['error' => 'not_found']);
    exit;
}

// Only owner can delete GROUP
if ($conv['type'] === 'group' && $conv['created_by'] != $userId) {
    http_response_code(403);
    echo json_encode(['error' => 'only_owner_can_delete']);
    exit;
}

// Safe delete (soft delete recommended)
//$pdo->prepare("DELETE FROM conversations WHERE id = ?")->execute([$conversationId]);
//$pdo->prepare("DELETE FROM conversation_members WHERE conversation_id = ?")->execute([$conversationId]);

$stmt = $pdo->prepare("SELECT user_id FROM conversation_members WHERE conversation_id = ?");
$stmt->execute([$conversationId]);
$members = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Delete conversation
$pdo->prepare("DELETE FROM conversations WHERE id = ?")->execute([$conversationId]);
$pdo->prepare("DELETE FROM conversation_members WHERE conversation_id = ?")->execute([$conversationId]);

// Notify WebSocket server via temp table or direct trigger
$pdo->prepare("
    INSERT INTO ws_events (event_type, conversation_id, created_at)
    VALUES ('conversation_deleted', ?, NOW())
")->execute([$conversationId]);

echo json_encode(['success' => true]);