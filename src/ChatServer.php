<?php
namespace ChatModule;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use PDO;
use Throwable;

final class ChatServer implements MessageComponentInterface
{
    /** @var \SplObjectStorage<ConnectionInterface, null> */
    private \SplObjectStorage $clients;

    private PDO $conn;
    private LoopInterface $loop;

    /** resourceId => userId */
    private array $users = [];

    /** userId => [ resourceId => ConnectionInterface ] */
    private array $connectionsByUser = [];

    public function __construct(PDO $pdo, LoopInterface $loop)
    {
        $this->clients = new \SplObjectStorage();
        $this->conn    = $pdo;
        $this->loop    = $loop;

        $this->initDbKeepAlive();
    }

    /* ============================================================
       =============== DATABASE SAFETY LAYER ======================
       ============================================================ */

    private function dbRun(callable $fn)
    {
        try {
            return $fn($this->conn);
        } catch (\PDOException $e) {
            if ($this->isGoneAway($e)) {
                $this->reconnectDb();
                return $fn($this->conn);
            }
            throw $e;
        }
    }

    private function isGoneAway(\PDOException $e): bool
    {
        return (int)$e->getCode() === 2006
            || str_contains($e->getMessage(), 'server has gone away');
    }

    private function initDbKeepAlive(): void
    {
        $this->loop->addPeriodicTimer(60, function () {
            try {
                $this->conn->query('SELECT 1');
            } catch (Throwable) {
                $this->reconnectDb();
            }
        });
    }

    private function reconnectDb(): void
    {
        static $retrying = false;
        if ($retrying) return;
        $retrying = true;

        try {
            $config = require __DIR__ . '/../config.php';
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $config['db']['host'],
                $config['db']['port'] ?? 3306,
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
        } finally {
            $retrying = false;
        }
    }

    /* ============================================================
       ================= WEBSOCKET LIFECYCLE ======================
       ============================================================ */

    public function onOpen(ConnectionInterface $conn): void
    {
        try {
            $userId = $this->getUserIdFromSession(
                $conn->httpRequest->getHeader('Cookie') ?? ''
            );

            if (!$userId) {
                $conn->close();
                return;
            }

            $conn->userId = (int)$userId;

            $this->clients->attach($conn);
            $this->users[$conn->resourceId] = $conn->userId;
            $this->connectionsByUser[$conn->userId][$conn->resourceId] = $conn;

            $this->setUserOnline($conn->userId, true);
            $this->broadcastPresence($conn->userId, true);

            $conn->send(json_encode([
                'type'    => 'system',
                'status'  => 'connected',
                'user_id' => $conn->userId,
            ]));
        } catch (Throwable) {
            $conn->close();
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        if ($this->clients->contains($conn)) {
            $this->clients->remove($conn);
        }

        $rid = $conn->resourceId;
        if (!isset($this->users[$rid])) return;

        $uid = $this->users[$rid];
        unset($this->users[$rid], $this->connectionsByUser[$uid][$rid]);

        if (empty($this->connectionsByUser[$uid])) {
            unset($this->connectionsByUser[$uid]);
            $this->setUserOnline($uid, false);
            $this->broadcastPresence($uid, false);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $conn->close();
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        try {
            $payload = json_decode($msg, true);
            if (!is_array($payload)) return;

            match ($payload['type'] ?? '') {
                'send_message'        => $this->handleSendMessage($from, $payload),
                'typing'              => $this->handleTyping($from, $payload),
                'mark_read'           => $this->handleMarkRead($from, $payload),
                'delete_message'      => $this->handleDeleteMessage($from, $payload),
                'delete_conversation' => $this->handleDeleteConversation($from, $payload),
                'ping'                => $from->send('{"type":"pong"}'),
                default               => null
            };
        } catch (Throwable) {
            try { $from->send('{"type":"error"}'); } catch (Throwable) {}
        }
    }

    /* ============================================================
       ================= MESSAGE HANDLING =========================
       ============================================================ */

    private function handleSendMessage(ConnectionInterface $from, array $p): void
    {
        $cid = (int)($p['conversation_id'] ?? 0);
        $uid = $from->userId ?? 0;
        $txt = trim($p['message'] ?? '');

        if (!$cid || !$uid || ($txt === '' && empty($p['file_path']))) return;

        $isMember = $this->dbRun(function (PDO $db) use ($cid, $uid) {
            $s = $db->prepare(
                "SELECT 1 FROM conversation_members WHERE conversation_id=? AND user_id=? LIMIT 1"
            );
            $s->execute([$cid, $uid]);
            return (bool)$s->fetchColumn();
        });
        if (!$isMember) return;

        $msgId = $this->dbRun(function (PDO $db) use ($cid, $uid, $txt, $p) {
            $s = $db->prepare("
                INSERT INTO messages
                (conversation_id, sender_id, message, file_path, original_name, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $s->execute([
                $cid,
                $uid,
                $txt ?: null,
                $p['file_path'] ?? null,
                $p['original_name'] ?? null
            ]);
            return (int)$db->lastInsertId();
        });

        $members = $this->dbRun(function (PDO $db) use ($cid) {
            $s = $db->prepare("SELECT user_id FROM conversation_members WHERE conversation_id=?");
            $s->execute([$cid]);
            return $s->fetchAll(PDO::FETCH_COLUMN);
        });

        $mentions = $this->extractMentions($txt);
        if ($mentions) {
            $this->handleMentions($mentions, $cid, $uid, $msgId);
        }

        $this->dbRun(function (PDO $db) use ($members, $msgId, $uid) {
            $s = $db->prepare("
                INSERT INTO message_read_status (message_id, user_id, last_read)
                VALUES (?, ?, 0)
                ON DUPLICATE KEY UPDATE last_read = last_read
            ");
            foreach ($members as $m) {
                if ($m != $uid) {
                    $s->execute([$msgId, $m]);
                }
            }
        });

        $this->sendToMembers($members, [
            'type' => 'new_message',
            'message' => [
                'id' => $msgId,
                'conversation_id' => $cid,
                'sender_id' => $uid,
                'message' => $txt,
            ]
        ]);
    }

    private function handleMentions(array $mentions, int $cid, int $sid, int $mid): void
    {
        $this->dbRun(function (PDO $db) use ($mentions, $cid, $sid, $mid) {
            $s = $db->prepare("SELECT id FROM vp_users WHERE name=? LIMIT 1");
            foreach ($mentions as $name) {
                $s->execute([$name]);
                $uid = (int)$s->fetchColumn();
                if ($uid && isset($this->connectionsByUser[$uid])) {
                    $this->sendToUser($uid, [
                        'type' => 'mention',
                        'conversation_id' => $cid,
                        'sender_id' => $sid,
                        'message_id' => $mid
                    ]);
                }
            }
        });
    }

    private function handleTyping(ConnectionInterface $from, array $p): void
    {
        $cid = (int)($p['conversation_id'] ?? 0);
        $uid = $from->userId ?? 0;
        if (!$cid || !$uid) return;

        $members = $this->dbRun(function (PDO $db) use ($cid) {
            $s = $db->prepare("SELECT user_id FROM conversation_members WHERE conversation_id=?");
            $s->execute([$cid]);
            return $s->fetchAll(PDO::FETCH_COLUMN);
        });

        $this->sendToMembers(array_filter($members, fn($m) => $m != $uid), [
            'type' => 'typing',
            'conversation_id' => $cid,
            'from' => $uid
        ]);
    }

    private function handleMarkRead(ConnectionInterface $from, array $p): void
    {
        $uid = $from->userId ?? 0;
        $cid = (int)($p['conversation_id'] ?? 0);
        $mid = (int)($p['last_read_message_id'] ?? 0);
        if (!$uid || !$cid || !$mid) return;

        $this->dbRun(function (PDO $db) use ($uid, $cid, $mid) {
            $s = $db->prepare("
                UPDATE message_read_status r
                JOIN messages m ON m.id = r.message_id
                SET r.last_read=1, r.read_at=IFNULL(r.read_at,NOW())
                WHERE r.user_id=? AND m.conversation_id=? AND m.id<=?
            ");
            $s->execute([$uid, $cid, $mid]);
        });
    }

    private function handleDeleteMessage(ConnectionInterface $from, array $p): void
    {
        $mid = (int)($p['message_id'] ?? 0);
        $uid = $from->userId ?? 0;
        if (!$mid || !$uid) return;

        $row = $this->dbRun(function (PDO $db) use ($mid) {
            $s = $db->prepare("SELECT sender_id, conversation_id FROM messages WHERE id=?");
            $s->execute([$mid]);
            return $s->fetch();
        });

        if (!$row || (int)$row['sender_id'] !== $uid) return;

        $this->dbRun(fn(PDO $db) =>
            $db->prepare("UPDATE messages SET is_deleted=1 WHERE id=?")->execute([$mid])
        );
    }

    private function handleDeleteConversation(ConnectionInterface $from, array $p): void
    {
        $cid = (int)($p['conversation_id'] ?? 0);
        $uid = $from->userId ?? 0;
        if (!$cid || !$uid) return;

        $conv = $this->dbRun(function (PDO $db) use ($cid) {
            $s = $db->prepare("SELECT type, created_by FROM conversations WHERE id=?");
            $s->execute([$cid]);
            return $s->fetch();
        });

        if (!$conv || $conv['type'] !== 'group' || (int)$conv['created_by'] !== $uid) return;

        $members = $this->dbRun(function (PDO $db) use ($cid) {
            $s = $db->prepare("SELECT user_id FROM conversation_members WHERE conversation_id=?");
            $s->execute([$cid]);
            return $s->fetchAll(PDO::FETCH_COLUMN);
        });

        $this->dbRun(function (PDO $db) use ($cid) {
            $db->prepare("DELETE FROM conversations WHERE id=?")->execute([$cid]);
            $db->prepare("DELETE FROM conversation_members WHERE conversation_id=?")->execute([$cid]);
            $db->prepare("DELETE FROM messages WHERE conversation_id=?")->execute([$cid]);
        });

        $this->sendToMembers($members, [
            'type' => 'conversation_deleted',
            'conversation_id' => $cid
        ]);
    }

    /* ============================================================
       ================= PRESENCE & BROADCAST =====================
       ============================================================ */

    private function setUserOnline(int $uid, bool $online): void
    {
        $this->dbRun(function (PDO $db) use ($uid, $online) {
            $s = $db->prepare("
                INSERT INTO online_users (user_id,is_online,last_seen)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE is_online=VALUES(is_online), last_seen=VALUES(last_seen)
            ");
            $s->execute([$uid, $online ? 1 : 0]);
        });
    }

    private function broadcastPresence(int $uid, bool $online): void
    {
        $this->broadcastRaw(json_encode([
            'type' => 'presence',
            'user_id' => $uid,
            'is_online' => $online ? 1 : 0
        ]));
    }

    private function broadcastRaw(string $payload): void
    {
        foreach ($this->clients as $c) {
            try { $c->send($payload); } catch (Throwable) {}
        }
    }

    private function sendToUser(int $uid, array $payload): void
    {
        if (!isset($this->connectionsByUser[$uid])) return;
        $json = json_encode($payload);
        foreach ($this->connectionsByUser[$uid] as $c) {
            try { $c->send($json); } catch (Throwable) {}
        }
    }

    private function sendToMembers(array $members, array $payload): void
    {
        $json = json_encode($payload);
        foreach ($members as $uid) {
            if (!isset($this->connectionsByUser[$uid])) continue;
            foreach ($this->connectionsByUser[$uid] as $c) {
                try { $c->send($json); } catch (Throwable) {}
            }
        }
    }

    /* ============================================================
       ================= UTILITIES ================================
       ============================================================ */

    private function extractMentions(string $text): array
    {
        preg_match_all('/@([A-Za-z0-9_]+)/', $text, $m);
        return $m[1] ?? [];
    }

    private function getUserIdFromSession($cookieHeader)
    {
        if (!$cookieHeader) return false;
        $cookies = [];
        foreach (explode(';', is_array($cookieHeader) ? implode(';', $cookieHeader) : $cookieHeader) as $c) {
            [$k, $v] = array_pad(explode('=', trim($c), 2), 2, null);
            if ($k && $v) $cookies[$k] = urldecode($v);
        }

        if (!isset($cookies['PHPSESSID'])) return false;

        $file = "/var/lib/php/sessions/sess_" . $cookies['PHPSESSID'];
        if (!is_readable($file)) return false;

        $data = file_get_contents($file);
        if (!$data) return false;

        preg_match('/user\|a:\d+:{.*?id";i:(\d+)/', $data, $m);
        return $m[1] ?? false;
    }
}
