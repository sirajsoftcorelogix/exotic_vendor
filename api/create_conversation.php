<?php
require 'auth.php';

$currentUser = $_SESSION["user"]['id'] ?? null;
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$type = ($input['type'] ?? 'single') === 'group' ? 'group' : 'single';
$name = $input['name'] ?? null;
$members = $input['members'] ?? [];

if (!is_array($members)) {
    $members = [];
}
$members = array_map('intval', $members);
$members = array_values(array_unique($members));

if ($type === 'single') {
    if (count($members) !== 1) {
        http_response_code(400);
        echo json_encode(['error' => 'single chat requires exactly one other user']);
        exit;
    }
    $other = $members[0];

    // check existing single conversation between these two
    $sql = "
        SELECT c.id
        FROM conversations c
        JOIN conversation_members m1 ON m1.conversation_id = c.id
        JOIN conversation_members m2 ON m2.conversation_id = c.id
        WHERE c.type = 'single' AND m1.user_id = ? AND m2.user_id = ?
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$currentUser, $other]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo json_encode(['conversation_id' => (int)$row['id']]);
        exit;
    }
}

$pdo->beginTransaction();

$stmt = $pdo->prepare("INSERT INTO conversations (type, name, created_by) VALUES (?, ?, ?)");
$stmt->execute([$type, $name, $currentUser]);
$convId = (int)$pdo->lastInsertId();

$add = $pdo->prepare("INSERT INTO conversation_members (conversation_id, user_id) VALUES (?, ?)");

// creator always member
$add->execute([$convId, $currentUser]);

foreach ($members as $m) {
    if ($m == $currentUser) continue;
    $add->execute([$convId, $m]);
}

$pdo->commit();

echo json_encode(['conversation_id' => $convId]);
