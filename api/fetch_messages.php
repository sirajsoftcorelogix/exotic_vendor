<?php
require 'auth.php';

$currentUser = $_SESSION["user"]['id'] ?? null;
if (!$currentUser) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$convId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
if (!$convId) {
    echo json_encode([]);
    exit;
}

// ensure membership
$stmt = $pdo->prepare("SELECT 1 FROM conversation_members WHERE conversation_id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$convId, $currentUser]);
if (!$stmt->fetchColumn()) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$limit = isset($_GET['limit']) ? max(1, min((int)$_GET['limit'], 200)) : 100;

$sql = "SELECT
    m.id,
    m.conversation_id,
    m.sender_id,
    m.message,
    m.file_path,
    m.original_name,
    DATE_FORMAT(m.created_at, '%d %m %Y %H %i %s') AS created_at,
    CASE
        WHEN r.last_read = 1 THEN 'read'
        WHEN r.message_id IS NOT NULL THEN 'delivered'
        ELSE 'sent'
    END AS delivery_status
FROM messages m
LEFT JOIN message_read_status r
    ON r.message_id = m.id
    AND r.user_id = ?
WHERE m.conversation_id = ?
ORDER BY m.id ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$currentUser, $convId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$deliveredStmt = $pdo->prepare("
    INSERT IGNORE INTO message_read_status (message_id, user_id, delivered_at)
    SELECT m.id, ?, NOW()
    FROM messages m
    WHERE m.conversation_id = ?
");
$deliveredStmt->execute([$currentUser, $convId]);

header('Content-Type: application/json');
echo json_encode($messages);