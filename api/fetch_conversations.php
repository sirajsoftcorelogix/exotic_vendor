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

// conversations with last message & unread count
$sql = "
SELECT c.id,
       c.type,
       c.name,
       (
         SELECT m.message
         FROM messages m
         WHERE m.conversation_id = c.id
         ORDER BY m.created_at DESC
         LIMIT 1
       ) AS last_message,
       (
         SELECT m.created_at
         FROM messages m
         WHERE m.conversation_id = c.id
         ORDER BY m.created_at DESC
         LIMIT 1
       ) AS last_message_at,
       (
         SELECT COUNT(*)
         FROM messages m
         LEFT JOIN message_read_status r
           ON r.message_id = m.id AND r.user_id = :uid
         WHERE m.conversation_id = c.id
           AND (r.message_id IS NULL)
           AND m.sender_id <> :uid
       ) AS unread_count,
      -- NEW: get the other user's name for 1-to-1 chat
    (
        SELECT u.name 
        FROM vp_users u
        JOIN conversation_members cm2 ON cm2.user_id = u.id
        WHERE cm2.conversation_id = c.id
          AND u.id != :uid
        LIMIT 1
    ) AS other_user_name
FROM conversations c
JOIN conversation_members cm ON cm.conversation_id = c.id
WHERE cm.user_id = :uid AND cm.deleted_at IS NULL
ORDER BY last_message_at DESC, c.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $currentUser]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as &$r) {
    $r['id'] = (int)$r['id'];
    $r['unread_count'] = (int)($r['unread_count'] ?? 0);
    $r['last_message'] = $r['last_message'] ?? '';
    if ($r['type'] === 'single') {
        $r['display_name'] = $r['other_user_name'] ?: "User";
    } else {
        $r['display_name'] = $r['group_name'] ?: "Group Chat";
    }
}

header('Content-Type: application/json');
echo json_encode($rows);
