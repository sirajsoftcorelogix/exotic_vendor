<?php
require __DIR__ . '/../config.php';
$config = require __DIR__ . '/../config.php';

$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

session_start();
$currentUser = $_SESSION['user']['id'] ?? null;
if (!$currentUser) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

// fetch all users except current
$sql = "SELECT id, `name`, (SELECT is_online FROM user_status WHERE user_id = vp_users.id LIMIT 1) as is_online FROM vp_users WHERE id != ? ORDER BY name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$currentUser]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($rows);
