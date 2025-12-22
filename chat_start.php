<?php
session_start();
if (!isset($_SESSION["user"]['id'])) {
    die("Not logged in");
}

$currentUser = $_SESSION["user"]['id'];
$otherUser = intval($_GET['user_id'] ?? null);

if (!$otherUser || $otherUser == $currentUser) {
    die("Invalid user");
}

// Load DB config
$config = require __DIR__ . '/config.php';

$pdo = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
    $config['db']['user'],
    $config['db']['pass'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

// Check if conversation already exists
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
$stmt->execute([$currentUser, $otherUser]);
$existing = $stmt->fetchColumn();

if ($existing) {
    // Redirect to chat page
    header("Location: chat.php?conversation_id={$existing}");
    exit;
}

// Create new conversation
$pdo->beginTransaction();

$stmt = $pdo->prepare("INSERT INTO conversations (type, name, created_by) VALUES ('single', NULL, ?)");
$stmt->execute([$currentUser]);
$convId = $pdo->lastInsertId();

$add = $pdo->prepare("INSERT INTO conversation_members (conversation_id, user_id) VALUES (?, ?)");
$add->execute([$convId, $currentUser]);
$add->execute([$convId, $otherUser]);

$pdo->commit();

// Redirect to chat page
header("Location: chat.php?conversation_id={$convId}");
exit;
?>
