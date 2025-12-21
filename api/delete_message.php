<?php
require 'auth.php';

$data = json_decode(file_get_contents("php://input"), true);
$messageId = $data['message_id'] ?? null;

if (!$messageId) {
    echo json_encode(['error' => 'no_message']);
    exit;
}

// Verify sender owns the message
$stmt = $pdo->prepare("SELECT sender_id, conversation_id FROM messages WHERE id = ?");
$stmt->execute([$messageId]);
$row = $stmt->fetch();

if (!$row || $row['sender_id'] != $_SESSION['user']['id']) {
    echo json_encode(['error' => 'not_allowed']);
    exit;
}

// Mark as deleted
$pdo->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ?")->execute([$messageId]);

echo json_encode([
    'success' => true,
    'conversation_id' => $row['conversation_id'],
    'message_id' => $messageId
]);
