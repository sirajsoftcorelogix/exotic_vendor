<?php
namespace ChatModule;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use PDO;
use Throwable;

/**
 * Modernized ChatServer for Ratchet 0.5+ and PHP 8.x
 *
 * - Uses SplObjectStorage->remove() instead of deprecated detach()
 * - Defensive checks and error handling
 * - Session-based authentication reading session files
 * - Clean helpers for broadcasting and per-user sends
 */
class ChatServer implements MessageComponentInterface
{
    /** @var \SplObjectStorage<ConnectionInterface, array> */
    protected \SplObjectStorage $clients;

    protected PDO $conn; // PDO instance for DB access

    /** resourceId => userId */
    protected array $users = [];

    /** userId => [ resourceId => ConnectionInterface, ... ] */
    protected array $connectionsByUser = [];

    protected LoopInterface $loop;

    public function __construct(PDO $pdo, LoopInterface $loop)
    {
        $this->clients = new \SplObjectStorage();
        $this->conn = $pdo;
        $this->loop    = $loop;

        $this->initDbKeepAlive();
    }

    private function initDbKeepAlive(): void
    {
        // Ping DB every 60 seconds
        $this->loop->addPeriodicTimer(60, function () {
            try {
                $this->conn->query('SELECT 1');
            } catch (\Throwable $e) {
                error_log('[ChatServer] MySQL disconnected, reconnecting...');
                $this->reconnectDb();
            }
        });
    }
    private function reconnectDb(): void
    {
        static $retrying = false;
        if ($retrying) {
            return;
        }
        $retrying = true;
        try {
            $config = require __DIR__ . '/../config.php';
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $config['db']['host'],
                $config['db']['name'],
                $config['db']['charset']
            );
            $this->conn = new PDO(
                $dsn,
                $config['db']['user'],
                $config['db']['pass'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            echo "[ChatServer] MySQL reconnected successfully\n";
        } catch (\Throwable $e) {
            echo "[ChatServer] MySQL reconnect failed: {$e->getMessage()}\n";
        } finally {
            $retrying = false;
        }
    }
    private function safeExecute(callable $fn): void
    {
        try {
            $fn();
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), '2006')) {
                echo "[ChatServer] MySQL disconnected, reconnecting...\n";
                $this->reconnectDb();
            } else {
                throw $e;
            }
        }
    }
    /**
     * A new connection opened
     */
    public function onOpen(ConnectionInterface $conn): void
    {
        try {
            // Read cookies from handshake and restore session user id
            $cookiesHeader = $conn->httpRequest->getHeader('Cookie') ?? '';
            $userId = $this->getUserIdFromSession($cookiesHeader);

            if (!$userId) {
                $conn->send(json_encode(['type' => 'error', 'msg' => 'Unauthorized (no valid session)']));
                $conn->close();
                return;
            }

            $conn->userId = (int)$userId;

            // Track connection
            $this->clients->attach($conn);
            $this->users[$conn->resourceId] = $conn->userId;

            if (!isset($this->connectionsByUser[$conn->userId])) {
                $this->connectionsByUser[$conn->userId] = [];
            }
            $this->connectionsByUser[$conn->userId][$conn->resourceId] = $conn;

            // Mark user online and broadcast presence
            $this->setUserOnline($conn->userId, true);
            $this->broadcastPresence($conn->userId, true);

            // Confirm to client
            $conn->send(json_encode([
                'type' => 'system',
                'msg'  => 'connected',
                'user_id' => $conn->userId,
            ]));

            echo "WS CONNECT: user {$conn->userId}, resourceId {$conn->resourceId}\n";
        } catch (Throwable $e) {
            // If anything goes wrong here, close the connection safely
            error_log("onOpen error: " . $e->getMessage());
            try { $conn->send(json_encode(['type'=>'error','msg'=>'Server error'])); } catch (\Throwable $_) {}
            $conn->close();
        }
    }

    /**
     * Message received from a client
     */
    public function onMessage(ConnectionInterface $from, $msg): void
    {
        try {
            $payload = json_decode($msg, true);
            if (!is_array($payload)) {
                // ignore non-json or malformed messages
                $from->send(json_encode(['type' => 'error', 'msg' => 'Invalid payload']));
                return;
            }

            $type = $payload['type'] ?? '';
            switch ($type) {
                case 'send_message':
                    $this->handleSendMessage($from, $payload);
                    break;
                case 'typing':
                    $this->handleTyping($from, $payload);
                    break;
                case 'mark_read':
                    $this->handleMarkRead($from, $payload);
                    break;
                case 'ping':
                    $from->send(json_encode(['type' => 'pong']));
                    break;
                case 'delete_message':
                    $this->handleDeleteMessage($from, $payload);
                    break;
                case 'delete_conversation':
                    $this->handleDeleteConversation($from, $payload);
                    break;
                default:
                    $from->send(json_encode(['type' => 'error', 'msg' => 'Unknown command']));
                    break;
            }
        } catch (Throwable $e) {
            error_log("onMessage error: " . $e->getMessage());
            error_log($e->getTraceAsString());

            try { $from->send(json_encode(['type'=>'error','msg'=>'Server error'])); } catch (\Throwable $_) {}
        }
    }

    private function handleDeleteMessage(ConnectionInterface $from, array $payload)
    {
        $messageId = (int)($payload['message_id'] ?? 0);
        $userId = $from->userId;

        $stmt = $this->conn->prepare("SELECT sender_id, conversation_id FROM messages WHERE id = ?");
        $stmt->execute([$messageId]);
        $row = $stmt->fetch();

        if (!$row || $row['sender_id'] != $userId) {
            $from->send(json_encode(['type'=>'error','msg'=>'Not allowed']));
            return;
        }

        $this->conn->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ?")
                ->execute([$messageId]);

        // Notify all participants
        $payloadOut = [
            'type' => 'message_deleted',
            'message_id' => $messageId,
            'conversation_id' => $row['conversation_id']
        ];

        $stmt = $this->conn->prepare("SELECT user_id FROM conversation_members WHERE conversation_id = ?");
        $stmt->execute([$row['conversation_id']]);
        $members = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $this->sendToMembers($members, $payloadOut);
    }


    /**
     * Connection closed
     */
    public function onClose(ConnectionInterface $conn): void
    {
        try {
            // Use contains() and remove() per modern SplObjectStorage API
            /*if ($this->clients->contains($conn)) {
                $this->clients->remove($conn);
            }*/

            $rid = $conn->resourceId;
            if (isset($this->users[$rid])) {
                $uid = $this->users[$rid];
                unset($this->users[$rid]);

                // Remove that connection from the per-user map
                if (isset($this->connectionsByUser[$uid][$rid])) {
                    unset($this->connectionsByUser[$uid][$rid]);
                }

                // If no more connections left for that user => offline
                if (empty($this->connectionsByUser[$uid])) {
                    unset($this->connectionsByUser[$uid]);
                    $this->setUserOnline($uid, false);
                    $this->broadcastPresence($uid, false);
                }

                // Update online_users table as well (defensive)
                try {
                    $sql = "UPDATE online_users SET is_online = 0, last_seen = NOW() WHERE user_id = ?";
                    $stmt = $this->conn->prepare($sql);
                    //$stmt->execute([$uid]);
                    $this->safeExecute(function () use ($stmt, $uid) {
                        $stmt->execute([$uid]);
                    });
                } catch (Throwable $e) {
                    // log and continue
                    error_log("onClose DB update failed: " . $e->getMessage());
                }
            }

            echo "CONNECTION CLOSED for user: " . ($conn->userId ?? 'unknown') . PHP_EOL;
        } catch (Throwable $e) {
            error_log("onClose error: " . $e->getMessage());
        }
    }

    /**
     * Error on connection
     */
    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        echo "WS ERROR: " . $e->getMessage() . PHP_EOL;
        try { $conn->close(); } catch (\Throwable $_) {}
    }

    /**
     * Get user id from PHP session using cookies in handshake.
     *
     * $cookiesHeader may be array|string (Ratchet provides array of headers)
     */
    private function getUserIdFromSession($cookiesHeader)
    {
        if (empty($cookiesHeader)) {
            return false;
        }

        // Normalize header to string
        $cookiesStr = is_array($cookiesHeader) ? implode(';', $cookiesHeader) : $cookiesHeader;
        $cookies = [];
        foreach (explode(';', $cookiesStr) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2) {
                $cookies[$kv[0]] = urldecode($kv[1]);
            }
        }

        // Use configured session name or default
        $sessionName = "PHPSESSID"; //session_name() ?: 'PHPSESSID';
        if (!isset($cookies[$sessionName])) {
            return false;
        }

        $sessionId = $cookies[$sessionName];

        // Resolve session.save_path (may contain prefix like "N;MODE;path")
        $sessionPath = "/var/lib/php/sessions"; //ini_get('session.save_path');
        if (!$sessionPath) {
            $sessionPath = sys_get_temp_dir();
        } elseif (strpos($sessionPath, ';') !== false) {
            $parts = explode(';', $sessionPath);
            $sessionPath = end($parts);
        }

        $sessionFile = rtrim($sessionPath, "/\\") . DIRECTORY_SEPARATOR . 'sess_' . $sessionId;

        if (!is_file($sessionFile) || !is_readable($sessionFile)) {
            return false;
        }

        $data = @file_get_contents($sessionFile);
        if ($data === false || $data === '') {
            return false;
        }

        $sessionData = $this->decodeSessionData($data);

        // Adjust to your app's session shape: here we expect $_SESSION['user']['id']
        return $sessionData['user']['id'] ?? false;
    }

    /**
     * Robust session decode supporting typical PHP session serialization.
     *
     * This tries to parse "name|serialized" pairs.
     */
    private function decodeSessionData(string $data): array
    {
        $result = [];
        $offset = 0;
        $length = strlen($data);

        while ($offset < $length) {
            $pos = strpos($data, '|', $offset);
            if ($pos === false) break;

            $varname = substr($data, $offset, $pos - $offset);
            $offset = $pos + 1;

            // read serialized value; find end by attempting unserialize progressively
            $serialized = '';
            $i = $offset;
            $found = false;
            for (; $i < $length; $i++) {
                $serialized .= $data[$i];
                // attempt unserialize if looks reasonable (ends with ; or })
                if ($data[$i] === ';' || $data[$i] === '}') {
                    $try = @unserialize($serialized);
                    if ($try !== false || $serialized === 'b:0;') {
                        $result[$varname] = $try;
                        $offset = $i + 1;
                        $found = true;
                        break;
                    }
                }
            }

            if (!$found) {
                // fallback: store raw and break
                $result[$varname] = null;
                break;
            }
        }

        return $result;
    }

    /**
     * Mark user online/offline in DB (user_status or online_users)
     */
    private function setUserOnline(int $userId, bool $online): void
    {
        try {
            // Prefer an 'online_users' table if present; otherwise fallback to user_status
            $sql1 = "INSERT INTO online_users (user_id, is_online, last_seen) VALUES (?, ?, NOW())
                     ON DUPLICATE KEY UPDATE is_online = VALUES(is_online), last_seen = VALUES(last_seen)";
            $stmt = $this->conn->prepare($sql1);
            //$stmt->execute([$userId, $online ? 1 : 0]);
            $this->safeExecute(function () use ($stmt, $userId, $online) {
                $stmt->execute([$userId, $online ? 1 : 0]);
            });
            return;
        } catch (Throwable $e) {
            // fallback to user_status table if online_users doesn't exist
            try {
                $sql2 = "INSERT INTO user_status (user_id, is_online, last_seen) VALUES (?, ?, NOW())
                         ON DUPLICATE KEY UPDATE is_online = VALUES(is_online), last_seen = VALUES(last_seen)";
                $stmt2 = $this->conn->prepare($sql2);
                $stmt2->execute([$userId, $online ? 1 : 0]);
                return;
            } catch (Throwable $_) {
                // last resort: log error
                error_log("setUserOnline failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Broadcast presence change to all connected clients
     */
    private function broadcastPresence(int $userId, bool $online): void
    {
        $payload = json_encode([
            'type' => 'presence',
            'user_id' => $userId,
            'is_online' => $online ? 1 : 0,
        ]);

        $this->broadcastRaw($payload);
    }

    /**
     * Broadcast a raw string to all connected clients
     */
    private function broadcastRaw(string $payload): void
    {
        foreach ($this->clients as $client) {
            try {
                $client->send($payload);
            } catch (Throwable $e) {
                // ignore send errors for individual clients
                error_log("broadcast send failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Send payload (array or string) to all connections of a specific userId
     */
    private function sendToUser(int $userId, array|string $payload): void
    {
        $payloadStr = is_array($payload) ? json_encode($payload) : $payload;

        if (!isset($this->connectionsByUser[$userId])) {
            return;
        }
        foreach ($this->connectionsByUser[$userId] as $c) {
            try {
                $c->send($payloadStr);
            } catch (Throwable $e) {
                error_log("sendToUser failed for user {$userId}: " . $e->getMessage());
            }
        }
    }

    /**
     * Send payload to multiple members (array of user ids)
     */
    private function sendToMembers(array $members, array|string $payload): void
    {
        $payloadStr = is_array($payload) ? json_encode($payload) : $payload;
        foreach ($members as $uid) {
            if (isset($this->connectionsByUser[$uid])) {
                foreach ($this->connectionsByUser[$uid] as $c) {
                    try {
                        $c->send($payloadStr);
                    } catch (Throwable $e) {
                        error_log("sendToMembers send failed: " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Handle an outgoing message from a client (insert into DB, broadcast to members)
     */
    private function handleSendMessage(ConnectionInterface $from, array $payload): void
    {
        $conversationId = (int)($payload['conversation_id'] ?? 0);
        $text = trim($payload['message'] ?? '');
        $senderId = $from->userId ?? null;
        $filePath = isset($payload['file_path']) && $payload['file_path'] !== ''
            ? trim($payload['file_path'])
            : null;
        $originalName = isset($payload['original_name']) && $payload['original_name'] !== ''
            ? trim($payload['original_name'])
            : null;

        if (!$conversationId || !$senderId) {
            $from->send(json_encode(['type' => 'error', 'msg' => 'Invalid conversation or sender']));
            return;
        }

        if ($text === '' && empty($filePath)) {
            $from->send(json_encode(['type' => 'error', 'msg' => 'Empty message']));
            return;
        }

        // Security: ensure membership
        $stmt = $this->conn->prepare("SELECT 1 FROM conversation_members WHERE conversation_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$conversationId, $senderId]);
        if (!$stmt->fetchColumn()) {
            $from->send(json_encode(['type' => 'error', 'msg' => 'Not a member of conversation']));
            return;
        }

        $createdAt = date('Y-m-d H:i:s');
        // Insert message
        $insert = $this->conn->prepare("INSERT INTO messages (conversation_id, sender_id, `message`, file_path, original_name, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        /*$insert->execute([
            $conversationId,
            $senderId,
            $text ?: null,
            $filePath ?: null,
            $originalName,
            $createdAt
        ]);*/
        $this->safeExecute(function () use (
            $insert,
            $conversationId,
            $senderId,
            $text,
            $filePath,
            $originalName,
            $createdAt
        ) {
            $insert->execute([
                $conversationId,
                $senderId,
                $text ?: null,
                $filePath ?: null,
                $originalName,
                $createdAt
            ]);
        });
        $msgId = (int)$this->conn->lastInsertId();

        $msgRow = [
            'type' => 'new_message',
            'message' => [
                'id' => (int)$msgId,
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'message' => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                'file_path' => $filePath,
                'original_name' => $originalName,
                'created_at' => $createdAt,
            ],
        ];

        // Fetch conversation members
        $stmt = $this->conn->prepare("SELECT user_id FROM conversation_members WHERE conversation_id = ?");
        $stmt->execute([$conversationId]);
        $members = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $mentions = $this->extractMentions($text);

        foreach ($mentions as $mentionName) {
            $stmt = $this->conn->prepare("SELECT id FROM vp_users WHERE name = ? LIMIT 1");
            $stmt->execute([$mentionName]);
            $uid = $stmt->fetchColumn();

            if ($uid && isset($this->connectionsByUser[$uid])) {
                $this->sendToUser($uid, [
                    'type' => 'mention',
                    'conversation_id' => $conversationId,
                    'sender_id' => $senderId,
                    'message_id' => $msgId
                ]);
            }
        }

        // Save mentions in DB if JSON column exists
        if (!empty($mentions)) {
            $stmt = $this->conn->prepare("
                UPDATE messages SET mentioned_users = ?
                WHERE id = ?
            ");
            $stmt->execute([json_encode($mentions), $msgId]);
        }

        foreach ($mentions as $mentionName) {
            $stmt = $this->conn->prepare("SELECT id FROM vp_users WHERE name = ?");
            $stmt->execute([$mentionName]);
            $uid = $stmt->fetchColumn();

            if ($uid && isset($this->connectionsByUser[$uid])) {
                $this->sendToUser($uid, [
                    'type' => 'mention',
                    'conversation_id' => $conversationId,
                    'sender_id' => $senderId,
                    'message_id' => $msgId,
                ]);
            }
        }
        // Delivery status even if user is offline.
        foreach ($members as $uid) {
            if ($uid != $senderId) {
                $stmt = $this->conn->prepare("
                    INSERT INTO message_read_status (message_id, user_id, last_read)
                    VALUES (?, ?, 0)
                    ON DUPLICATE KEY UPDATE last_read = last_read
                ");
                $stmt->execute([$msgId, $uid]);
            }
        }

        // Send only to members that are connected
        $this->sendToMembers($members, $msgRow);
    }

    private function extractMentions(string $text): array
    {
        preg_match_all('/@([A-Za-z0-9_]+)/', $text, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Typing indicator — broadcast to other members in conversation
     */
    private function handleTyping(ConnectionInterface $from, array $payload): void
    {
        $conversationId = (int)($payload['conversation_id'] ?? 0);
        $senderId = $from->userId ?? null;
        if (!$conversationId || !$senderId) {
            return;
        }

        $stmt = $this->conn->prepare("SELECT user_id FROM conversation_members WHERE conversation_id = ?");
        $stmt->execute([$conversationId]);
        $members = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $out = [
            'type' => 'typing',
            'conversation_id' => $conversationId,
            'from' => $senderId,
        ];

        $this->sendToMembers(array_filter($members, fn($u) => $u != $senderId), $out);
    }

    /**
     * Mark read handling and broadcast read receipt
     */
    private function handleMarkRead(ConnectionInterface $from, array $payload): void
    {
        $userId = $from->userId ?? null;
        $conversationId = (int)($payload['conversation_id'] ?? 0);
        $lastReadId = (int)($payload['last_read_message_id'] ?? 0);

        if (!$conversationId || !$userId || !$lastReadId) {
            return;
        }

        // Ensure membership
        $stmt = $this->conn->prepare("
            SELECT 1 FROM conversation_members 
            WHERE conversation_id = ? AND user_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$conversationId, $userId]);
        if (!$stmt->fetchColumn()) {
            return;
        }

        /**
         * 1️⃣ INSERT missing rows (delivered → read)
         */
        $stmt = $this->conn->prepare("
            INSERT INTO message_read_status (message_id, user_id, last_read, read_at)
            SELECT m.id, ?, 1, NOW()
            FROM messages m
            LEFT JOIN message_read_status r 
                ON r.message_id = m.id AND r.user_id = ?
            WHERE m.conversation_id = ?
            AND m.id = ?
            AND r.message_id IS NULL
        ");
        //$stmt->execute([$userId, $userId, $conversationId, $lastReadId]);
        $this->safeExecute(function () use ($stmt, $userId, $conversationId, $lastReadId) {
            $stmt->execute([$userId, $userId, $conversationId, $lastReadId]);
        });
        /**
         * 2️⃣ UPDATE existing rows (important!)
         */
        $stmt = $this->conn->prepare("
            UPDATE message_read_status r
            JOIN messages m ON m.id = r.message_id
            SET r.last_read = 1,
                r.read_at = IFNULL(r.read_at, NOW())
            WHERE r.user_id = ?
            AND m.conversation_id = ?
            AND m.id <= ?
            AND r.last_read = 0
        ");
        $stmt->execute([$userId, $conversationId, $lastReadId]);

        /**
         * 3️⃣ Broadcast receipt
         */
        $payloadOut = [
            'type' => 'read_receipt',
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'last_read_message_id' => $lastReadId,
        ];

        $stmt = $this->conn->prepare("
            SELECT user_id FROM conversation_members 
            WHERE conversation_id = ?
        ");
        $stmt->execute([$conversationId]);
        $members = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $this->sendToMembers($members, $payloadOut);
    }
    private function handleDeleteConversation(ConnectionInterface $from, array $payload)
    {
        $conversationId = (int)($payload['conversation_id'] ?? 0);
        $userId = $from->userId;

        if (!$conversationId || !$userId) {
            return;
        }

        // Check group + owner
        $stmt = $this->conn->prepare("SELECT type, created_by FROM conversations WHERE id = ?");
        $stmt->execute([$conversationId]);
        $conv = $stmt->fetch();

        if (!$conv) return;
        if ($conv['type'] !== 'group') return; // only groups deleted here
        if ($conv['created_by'] != $userId) {
            $from->send(json_encode(['type' => 'error', 'msg' => 'Only the owner can delete this group']));
            return;
        }

        // Fetch members before deleting
        $stmt = $this->conn->prepare("SELECT user_id FROM conversation_members WHERE conversation_id = ?");
        $stmt->execute([$conversationId]);
        $members = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Delete the conversation
        $this->conn->prepare("DELETE FROM conversations WHERE id = ?")->execute([$conversationId]);
        $this->conn->prepare("DELETE FROM conversation_members WHERE conversation_id = ?")->execute([$conversationId]);
        $this->conn->prepare("DELETE FROM messages WHERE conversation_id = ?")->execute([$conversationId]);

        // 🔥 EXACT PLACE YOU MUST CALL THE BROADCAST
        $this->broadcastConversationDeleted($conversationId, $members);
    }
    private function broadcastConversationDeleted(int $conversationId, array $members)
    {
        $payload = json_encode([
            'type' => 'conversation_deleted',
            'conversation_id' => $conversationId
        ]);

        foreach ($members as $uid) {
            if (isset($this->connectionsByUser[$uid])) {
                foreach ($this->connectionsByUser[$uid] as $conn) {
                    $conn->send($payload);
                }
            }
        }
    }
}
