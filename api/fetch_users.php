<?php
require 'auth.php';

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
