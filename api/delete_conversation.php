<?php
session_start();
require __DIR__ . '/../config.php';

$config = require __DIR__ . '/../config.php';

$pdo = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
    $config['db']['user'],
    $config['db']['pass'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$currentUser = $_SESSION['user']['id'] ?? null;
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$conversationId = $data['conversation_id'] ?? null;

if (!$conversationId) {
    echo json_encode(['error' => 'Invalid conversation ID']);
    exit;
}

// Soft delete for THIS USER only
$stmt = $pdo->prepare("
    UPDATE conversation_members
    SET deleted_at = NOW()
    WHERE conversation_id = ? AND user_id = ?
");
$stmt->execute([$conversationId, $currentUser]);

echo json_encode(['success' => true]);
