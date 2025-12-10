<?php
require __DIR__ . '/../config.php';
$config = require __DIR__ . '/../config.php';

$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

session_start();
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

$sql = "SELECT id, conversation_id, sender_id, `message`, file_path, original_name, created_at
        FROM messages
        WHERE conversation_id = ?
        ORDER BY created_at DESC
        LIMIT ?";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(1, $convId, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->execute();
$rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

foreach ($rows as &$r) {
    $r['id'] = (int)$r['id'];
    $r['conversation_id'] = (int)$r['conversation_id'];
    $r['sender_id'] = (int)$r['sender_id'];
    $r['message'] = $r['message'] ? htmlspecialchars($r['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';
}

header('Content-Type: application/json');
echo json_encode($rows);
