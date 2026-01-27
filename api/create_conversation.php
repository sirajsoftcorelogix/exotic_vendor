<?php
declare(strict_types=1);

header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

require 'auth.php';

try {

    // ----------------------------
    // Auth check
    // ----------------------------
    $currentUser = $_SESSION['user']['id'] ?? null;
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'unauthorized']);
        exit;
    }

    // ----------------------------
    // Read & validate JSON input
    // ----------------------------
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Empty request body']);
        exit;
    }

    $input = json_decode($raw, true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
        exit;
    }

    $type    = (($input['type'] ?? 'single') === 'group') ? 'group' : 'single';
    $name    = trim((string)($input['name'] ?? ''));
    $members = $input['members'] ?? [];

    if (!is_array($members)) {
        $members = [];
    }

    // Normalize members
    $members = array_values(array_unique(array_map('intval', $members)));
    $members = array_filter($members, fn($id) => $id > 0 && $id !== $currentUser);

    // ----------------------------
    // Validation rules
    // ----------------------------
    if ($type === 'single') {
        if (count($members) !== 1) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error'   => 'Single chat requires exactly one other user'
            ]);
            exit;
        }
    } else {
        if (count($members) < 1) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error'   => 'Group chat requires at least one member'
            ]);
            exit;
        }
    }

    // ----------------------------
    // Existing single conversation check
    // ----------------------------
    if ($type === 'single') {
        $other = $members[0];

        $sql = "
            SELECT c.id
            FROM conversations c
            JOIN conversation_members m1 ON m1.conversation_id = c.id
            JOIN conversation_members m2 ON m2.conversation_id = c.id
            WHERE c.type = 'single'
              AND m1.user_id = ?
              AND m2.user_id = ?
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentUser, $other]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            echo json_encode([
                'success' => true,
                'conversation_id' => (int)$row['id'],
                'existing' => true
            ]);
            exit;
        }
    }

    // ----------------------------
    // Transaction start
    // ----------------------------
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO conversations (type, name, created_by)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $type,
        $type === 'group' ? ($name ?: null) : null,
        $currentUser
    ]);

    $convId = (int)$pdo->lastInsertId();

    $addMember = $pdo->prepare("
        INSERT INTO conversation_members (conversation_id, user_id)
        VALUES (?, ?)
    ");

    // Creator always included
    $addMember->execute([$convId, $currentUser]);

    foreach ($members as $uid) {
        $addMember->execute([$convId, $uid]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'conversation_id' => $convId,
        'existing' => false
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Internal server error',
        // ⚠️ Remove `debug` in strict production if needed
        'debug'   => $e->getMessage()
    ]);
}