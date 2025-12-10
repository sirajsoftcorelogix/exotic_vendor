<?php
namespace ChatModule;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface
{
    protected $clients;
    protected $conn; // PDO
    protected $users = []; // connId => userId
    protected $connectionsByUser = []; // userId => [connId => conn]

    public function __construct(\PDO $pdo)
    {
        $this->clients = new \SplObjectStorage();
        $this->conn = $pdo;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Read cookies to get PHP session id and restore session
        $cookiesHeader = $conn->httpRequest->getHeader('Cookie');
        $userId = $this->getUserIdFromSession($cookiesHeader);

        if (!$userId) {
            $conn->send(json_encode(['type' => 'error', 'msg' => 'Unauthorized (no valid session)']));
            $conn->close();
            return;
        }

        $conn->userId = (int)$userId;
        $this->clients->attach($conn);
        $this->users[$conn->resourceId] = $conn->userId;

        if (!isset($this->connectionsByUser[$conn->userId])) {
            $this->connectionsByUser[$conn->userId] = [];
        }
        $this->connectionsByUser[$conn->userId][$conn->resourceId] = $conn;

        // Mark user online in DB
        $this->setUserOnline($conn->userId, true);

        // Broadcast presence to all
        $this->broadcastPresence($conn->userId, true);

        // Confirm connection
        $conn->send(json_encode([
            'type' => 'system',
            'msg'  => 'connected',
            'user_id' => $conn->userId,
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $payload = json_decode($msg, true);
        if (!$payload) {
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
            default:
                $from->send(json_encode(['type' => 'error', 'msg' => 'Unknown command']));
                break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        $rid = $conn->resourceId;
        if (isset($this->users[$rid])) {
            $uid = $this->users[$rid];
            unset($this->users[$rid]);

            if (isset($this->connectionsByUser[$uid][$rid])) {
                unset($this->connectionsByUser[$uid][$rid]);
            }
            if (empty($this->connectionsByUser[$uid])) {
                unset($this->connectionsByUser[$uid]);

                // this was last connection for this user -> offline
                $this->setUserOnline($uid, false);
                $this->broadcastPresence($uid, false);
            }
            $sql = "UPDATE online_users SET is_online = 0, last_seen = NOW() WHERE user_id = ?"; // User gets Offline
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$uid]);
        }
        echo "CONNECTION CLOSED for user: " . ($conn->userId ?? 'unknown') . PHP_EOL;
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "WS ERROR: " . $e->getMessage() . PHP_EOL;
        $conn->close();
    }

    /**
     * Get user id from PHP session using cookies in handshake.
     */
    private function getUserIdFromSession($cookiesHeader)
    {
        if (empty($cookiesHeader)) {
            return false;
        }

        // Normalize cookies header
        $cookiesStr = is_array($cookiesHeader) ? implode(';', $cookiesHeader) : $cookiesHeader;
        $cookies = [];
        foreach (explode(';', $cookiesStr) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2) {
                $cookies[$kv[0]] = urldecode($kv[1]);
            }
        }

        // Use current PHP session name, fallback to PHPSESSID
        $sessionName = session_name() ?: 'PHPSESSID';
        if (!isset($cookies[$sessionName])) {
            // Uncomment for debugging:
            // echo "No session cookie ({$sessionName}) found\n";
            return false;
        }

        $sessionId = $cookies[$sessionName];

        // Resolve session.save_path (it might be "N;MODE;path")
        $sessionPath = ini_get('session.save_path');
        if (!$sessionPath) {
            $sessionPath = sys_get_temp_dir();
        } elseif (strpos($sessionPath, ';') !== false) {
            $parts = explode(';', $sessionPath);
            $sessionPath = end($parts);
        }

        $sessionFile = rtrim($sessionPath, "/\\") . DIRECTORY_SEPARATOR . 'sess_' . $sessionId;

        if (!is_file($sessionFile) || !is_readable($sessionFile)) {
            // echo "Session file not found: {$sessionFile}\n";
            return false;
        }

        $data = @file_get_contents($sessionFile);
        if ($data === false || $data === '') {
            // echo "Session file empty or unreadable: {$sessionFile}\n";
            return false;
        }

        $sessionData = $this->decodeSessionData($data);

        /*echo "Cookies: {$cookiesStr}\n";
        echo "Session file: {$sessionFile}\n";
        echo "User from session: " . print_r($sessionData, true) . "\n";*/

        // IMPORTANT: adjust 'user_id' if your app uses another key
        return $sessionData['user']['id'] ?? false;
    }

    private function decodeSessionData($data)
    {
        $result = [];
        $offset = 0;
        $length = strlen($data);

        while ($offset < $length) {
            if (!strstr(substr($data, $offset), '|')) {
                break;
            }

            $pos = strpos($data, '|', $offset);
            if ($pos === false) {
                break;
            }

            $varname = substr($data, $offset, $pos - $offset);
            $offset  = $pos + 1;

            // Extract serialized value
            $serialized = '';
            $inString   = false;
            $braceLevel = 0;

            for ($i = $offset; $i < $length; $i++) {
                $ch = $data[$i];
                $serialized .= $ch;

                if ($ch === '"') {
                    $inString = !$inString;
                }

                if (!$inString) {
                    if ($ch === '{') {
                        $braceLevel++;
                    } elseif ($ch === '}') {
                        $braceLevel--;
                    }

                    if ($braceLevel <= 0 && $ch === ';') {
                        $i++;
                        break;
                    }
                }
            }

            $offset += strlen($serialized);

            $value = @unserialize($serialized);
            $result[$varname] = $value;
        }

        return $result;
    }

    private function setUserOnline($userId, $online)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO user_status (user_id, is_online, last_seen)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE is_online = VALUES(is_online), last_seen = VALUES(last_seen)
        ");
        $stmt->execute([$userId, $online ? 1 : 0]);
    }

    private function broadcastPresence($userId, $online)
    {
        $payload = json_encode([
            'type' => 'presence',
            'user_id' => $userId,
            'is_online' => $online ? 1 : 0,
        ]);

        foreach ($this->clients as $client) {
            $client->send($payload);
        }
    }

    /**
     * MESSAGE HANDLERS
     */
    private function handleSendMessage(ConnectionInterface $from, array $payload)
    {
        $conversationId = (int)($payload['conversation_id'] ?? 0);
        $text = trim($payload['message'] ?? '');
        $senderId = $from->userId ?? null;
        $filePath = $payload['file_path'] ?? null;

        if (!$conversationId || !$senderId) {
            return;
        }

        if ($text === '' && empty($filePath)) {
            return;
        }

        // Security: ensure membership
        $stmt = $this->conn->prepare("SELECT 1 FROM conversation_members WHERE conversation_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$conversationId, $senderId]);
        if (!$stmt->fetchColumn()) {
            $from->send(json_encode(['type' => 'error', 'msg' => 'Not a member of conversation']));
            return;
        }

        // Insert message
        $stmt = $this->conn->prepare("INSERT INTO messages (conversation_id, sender_id, message, file_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$conversationId, $senderId, $text ?: null, $filePath ?: null]);
        $msgId = $this->conn->lastInsertId();

        $createdAt = date('Y-m-d H:i:s');

        $msgRow = [
            'type' => 'new_message',
            'message' => [
                'id' => (int)$msgId,
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'message' => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                'file_path' => $filePath,
                'created_at' => $createdAt,
            ],
        ];

        // Broadcast to all members
        $stmt = $this->conn->prepare("SELECT user_id FROM conversation_members WHERE conversation_id = ?");
        $stmt->execute([$conversationId]);
        $members = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($members as $uid) {
            if (isset($this->connectionsByUser[$uid])) {
                foreach ($this->connectionsByUser[$uid] as $c) {
                    $c->send(json_encode($msgRow));
                }
            }
        }
    }

    private function handleTyping(ConnectionInterface $from, array $payload)
    {
        $conversationId = (int)($payload['conversation_id'] ?? 0);
        $senderId = $from->userId ?? null;
        if (!$conversationId || !$senderId) {
            return;
        }

        $stmt = $this->conn->prepare("SELECT user_id FROM conversation_members WHERE conversation_id = ?");
        $stmt->execute([$conversationId]);
        $members = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($members as $uid) {
            if ($uid == $senderId) {
                continue;
            }
            if (isset($this->connectionsByUser[$uid])) {
                foreach ($this->connectionsByUser[$uid] as $c) {
                    $c->send(json_encode([
                        'type' => 'typing',
                        'conversation_id' => $conversationId,
                        'from' => $senderId,
                    ]));
                }
            }
        }
    }

    private function handleMarkRead(ConnectionInterface $from, array $payload)
    {
        $conversationId = (int)($payload['conversation_id'] ?? 0);
        $lastReadId = (int)($payload['last_read_message_id'] ?? 0);
        $userId = $from->userId ?? null;

        if (!$conversationId || !$userId || !$lastReadId) {
            return;
        }

        // ensure membership
        $stmt = $this->conn->prepare("SELECT 1 FROM conversation_members WHERE conversation_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$conversationId, $userId]);
        if (!$stmt->fetchColumn()) {
            return;
        }

        // mark all messages up to lastReadId as read
        $sql = "
            INSERT INTO message_read_status (message_id, user_id, last_read, read_at)
            SELECT m.id, ?, 1, NOW()
            FROM messages m
            LEFT JOIN message_read_status r ON r.message_id = m.id AND r.user_id = ?
            WHERE m.conversation_id = ? AND m.id <= ? AND r.message_id IS NULL
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $userId, $conversationId, $lastReadId]);

        // Broadcast read receipt to other members
        $payload = json_encode([
            'type' => 'read_receipt',
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'last_read_message_id' => $lastReadId,
        ]);

        $stmt = $this->conn->prepare("SELECT user_id FROM conversation_members WHERE conversation_id = ?");
        $stmt->execute([$conversationId]);
        $members = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($members as $uid) {
            if (isset($this->connectionsByUser[$uid])) {
                foreach ($this->connectionsByUser[$uid] as $c) {
                    $c->send($payload);
                }
            }
        }
    }
}
