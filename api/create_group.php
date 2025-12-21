<?php
require 'auth.php';

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$members = $data['members'] ?? [];

if (!$name || empty($members)) {
    echo json_encode(['error' => 'Name and members required']);
    exit;
}

$pdo->beginTransaction();

// Create conversation
$stmt = $pdo->prepare("INSERT INTO conversations (`type`, `name`, created_by, created_at) VALUES ('group', ?, ?, now())");
$stmt->execute([$name, $_SESSION['user']['id']]);
$convId = $pdo->lastInsertId();

// Add members
$stmt2 = $pdo->prepare("INSERT INTO conversation_members (conversation_id, user_id) VALUES (?, ?)");
foreach ($members as $uid) {
    $stmt2->execute([$convId, $uid]);
}

// Add creator as member if not included
if (!in_array($_SESSION['user']['id'], $members)) {
    $stmt2->execute([$convId, $_SESSION['user']['id']]);
}

$pdo->commit();

echo json_encode(['conversation_id' => $convId]);
